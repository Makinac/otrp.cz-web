<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Manages manual character-slot bonuses assigned to specific users.
 */
class CharBonusModel extends BaseModel
{
    protected string $table = 'char_bonuses';

    /**
     * All char bonuses for a user (including expired), newest first.
     *
     * @return list<array<string,mixed>>
     */
    public function getByUserId(int $userId): array
    {
        return $this->db->query(
            "SELECT b.*, u.username AS created_by_name
             FROM `char_bonuses` b
             LEFT JOIN `users` u ON u.id = b.created_by
             WHERE b.user_id = ?
             ORDER BY b.created_at DESC",
            [$userId]
        )->fetchAll();
    }

    /**
     * Only active (non-expired) char bonuses for a user.
     *
     * @return list<array<string,mixed>>
     */
    public function getActiveByUserId(int $userId): array
    {
        return $this->db->query(
            "SELECT * FROM `char_bonuses`
             WHERE user_id = ?
               AND (expires_at IS NULL OR expires_at > NOW())
             ORDER BY created_at DESC",
            [$userId]
        )->fetchAll();
    }

    /**
     * Sum of active char-slot bonuses for a user.
     */
    public function sumActiveByUserId(int $userId): int
    {
        return (int)$this->db->query(
            "SELECT COALESCE(SUM(amount), 0) AS total
             FROM `char_bonuses`
             WHERE user_id = ?
               AND (expires_at IS NULL OR expires_at > NOW())",
            [$userId]
        )->fetchColumn();
    }

    /**
     * Add a new manual char bonus.
     *
     * @param int         $userId
     * @param int         $amount      Can be negative (penalty).
     * @param string      $reason
     * @param string|null $expiresAt   MySQL DATETIME string or null = permanent.
     * @param int|null    $createdBy   Admin user ID.
     * @return int  New row ID.
     */
    public function add(int $userId, int $amount, string $reason, ?string $expiresAt, ?int $createdBy): int
    {
        $this->db->query(
            "INSERT INTO `char_bonuses` (`user_id`, `amount`, `reason`, `expires_at`, `created_by`)
             VALUES (?, ?, ?, ?, ?)",
            [$userId, $amount, $reason, $expiresAt, $createdBy]
        );
        return (int)$this->db->getConnection()->lastInsertId();
    }

    /**
     * Delete a char bonus by ID.
     */
    public function delete(int $id): bool
    {
        return $this->db->query("DELETE FROM `char_bonuses` WHERE `id` = ?", [$id])->rowCount() > 0;
    }
}
