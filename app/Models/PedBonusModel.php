<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Manages RP ped menu access grants for users.
 * Each active (non-expired) row means the user has ped menu access.
 */
class PedBonusModel extends BaseModel
{
    protected string $table = 'ped_bonuses';

    /**
     * Check whether the user currently has ped menu access.
     */
    public function hasAccess(int $userId): bool
    {
        return (int)$this->db->query(
            "SELECT COUNT(*) FROM `ped_bonuses`
             WHERE user_id = ?
               AND (expires_at IS NULL OR expires_at > NOW())",
            [$userId]
        )->fetchColumn() > 0;
    }

    /**
     * All ped grants for a user (including expired), newest first.
     *
     * @return list<array<string,mixed>>
     */
    public function getByUserId(int $userId): array
    {
        return $this->db->query(
            "SELECT b.*, u.username AS created_by_name
             FROM `ped_bonuses` b
             LEFT JOIN `users` u ON u.id = b.created_by
             WHERE b.user_id = ?
             ORDER BY b.created_at DESC",
            [$userId]
        )->fetchAll();
    }

    /**
     * Grant ped menu access to a user.
     *
     * @param int         $userId
     * @param string      $reason
     * @param string|null $expiresAt   MySQL DATETIME string or null = permanent.
     * @param int|null    $createdBy   Admin user ID.
     * @return int  New row ID.
     */
    public function add(int $userId, string $reason, ?string $expiresAt, ?int $createdBy): int
    {
        $this->db->query(
            "INSERT INTO `ped_bonuses` (`user_id`, `reason`, `expires_at`, `created_by`)
             VALUES (?, ?, ?, ?)",
            [$userId, $reason, $expiresAt, $createdBy]
        );
        return (int)$this->db->getConnection()->lastInsertId();
    }

    /**
     * Delete a ped grant by ID.
     */
    public function delete(int $id): bool
    {
        return $this->db->query("DELETE FROM `ped_bonuses` WHERE `id` = ?", [$id])->rowCount() > 0;
    }
}
