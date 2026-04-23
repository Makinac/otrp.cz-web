<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Manages API keys stored in the `api_keys` table.
 */
class ApiKeyModel extends BaseModel
{
    /**
     * Generate a new API key and store it.
     *
     * @param string      $label      Human-readable label.
     * @param string|null $allowedIps Comma-separated list of allowed IPs (null = any).
     * @param int|null    $createdBy  Internal user ID.
     * @return string The generated API key.
     */
    public function generate(string $label, ?string $allowedIps, ?int $createdBy): string
    {
        $key  = bin2hex(random_bytes(32)); // 64-char hex key
        $hash = hash('sha256', $key);

        $this->db->query(
            "INSERT INTO `api_keys` (`label`, `api_key`, `allowed_ips`, `created_by`)
             VALUES (?, ?, ?, ?)",
            [$label, $hash, $allowedIps, $createdBy]
        );

        return $key;
    }

    /**
     * Find an active key row by its raw key string.
     * Supports both hashed (SHA-256) and legacy plaintext keys.
     * Auto-migrates plaintext keys to hashed on successful match.
     *
     * @return array<string,mixed>|null
     */
    public function findByKey(string $apiKey): ?array
    {
        $hash = hash('sha256', $apiKey);

        // Try hashed lookup first.
        $row = $this->db->query(
            "SELECT * FROM `api_keys` WHERE `api_key` = ? AND `is_active` = 1 LIMIT 1",
            [$hash]
        )->fetch();

        if ($row) {
            return $row;
        }

        // Fallback: legacy plaintext key lookup.
        $row = $this->db->query(
            "SELECT * FROM `api_keys` WHERE `api_key` = ? AND `is_active` = 1 LIMIT 1",
            [$apiKey]
        )->fetch();

        if ($row) {
            // Auto-migrate to hashed storage (best-effort, don't break if schema differs).
            try {
                $this->db->query(
                    "UPDATE `api_keys` SET `api_key` = ? WHERE `id` = ?",
                    [$hash, $row['id']]
                );
            } catch (\Throwable) {
                // Migration failed — key still works, will retry next time.
            }
            return $row;
        }

        return null;
    }

    /**
     * Validate an API key and check the caller's IP against allowed IPs.
     *
     * @param string $apiKey    The raw API key from the request header.
     * @param string $callerIp  The caller's IP address.
     * @return array{valid:bool, error:string|null, key_row:array<string,mixed>|null}
     */
    public function validate(string $apiKey, string $callerIp): array
    {
        if (empty($apiKey)) {
            return ['valid' => false, 'error' => 'Missing API key', 'key_row' => null];
        }

        $row = $this->findByKey($apiKey);

        if (!$row) {
            return ['valid' => false, 'error' => 'Invalid API key', 'key_row' => null];
        }

        // Check IP restriction.
        if (!empty($row['allowed_ips'])) {
            $allowed = array_map('trim', explode(',', $row['allowed_ips']));
            $allowed = array_filter($allowed, static fn(string $v): bool => $v !== '');

            if (!in_array($callerIp, $allowed, true)) {
                return ['valid' => false, 'error' => 'IP not allowed', 'key_row' => $row];
            }
        }

        // Update last-used timestamp.
        $this->db->query(
            "UPDATE `api_keys` SET `last_used_at` = NOW() WHERE `id` = ?",
            [$row['id']]
        );

        return ['valid' => true, 'error' => null, 'key_row' => $row];
    }

    /**
     * All keys ordered newest-first, with creator username joined.
     *
     * @return list<array<string,mixed>>
     */
    public function getAll(): array
    {
        return $this->db->query(
            "SELECT ak.*, u.username AS created_by_name
             FROM `api_keys` ak
             LEFT JOIN `users` u ON u.id = ak.created_by
             ORDER BY ak.created_at DESC"
        )->fetchAll();
    }

    /**
     * Toggle active status.
     */
    public function toggleActive(int $id): void
    {
        $this->db->query(
            "UPDATE `api_keys` SET `is_active` = NOT `is_active` WHERE `id` = ?",
            [$id]
        );
    }

    /**
     * Delete a key by ID.
     */
    public function delete(int $id): void
    {
        $this->db->query("DELETE FROM `api_keys` WHERE `id` = ?", [$id]);
    }

    /**
     * Update allowed IPs for a key.
     */
    public function updateAllowedIps(int $id, ?string $allowedIps): void
    {
        $this->db->query(
            "UPDATE `api_keys` SET `allowed_ips` = ? WHERE `id` = ?",
            [$allowedIps, $id]
        );
    }
}
