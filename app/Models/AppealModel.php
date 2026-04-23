<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Model for the `appeals` table.
 */
class AppealModel extends BaseModel
{
    /**
     * Find an active (pending) appeal for a user.
     *
     * @param int $userId Internal user DB id.
     * @return array<string,mixed>|null
     */
    public function findPendingByUserId(int $userId): ?array
    {
        $row = $this->db->query(
            "SELECT * FROM `appeals` WHERE `user_id` = ? AND `status` = 'pending' LIMIT 1",
            [$userId]
        )->fetch();

        return $row ?: null;
    }

    /**
     * Find the most recent appeal of any status for a user.
     *
     * @param int $userId Internal user DB id.
     * @return array<string,mixed>|null
     */
    public function findLatestByUserId(int $userId): ?array
    {
        $row = $this->db->query(
            'SELECT * FROM `appeals` WHERE `user_id` = ? ORDER BY `created_at` DESC LIMIT 1',
            [$userId]
        )->fetch();

        return $row ?: null;
    }

    /**
     * Create a new appeal.
     *
     * @param int         $userId       Internal user DB id.
     * @param string      $reason       Appeal reason text.
     * @param string      $type         'blacklist' or 'allowlist'.
     * @param string|null $staffPresent Comma-separated staff names (ban/warn).
     * @return int New appeal ID.
     */
    public function create(int $userId, string $reason, string $type, ?string $staffPresent = null): int
    {
        $this->db->query(
            "INSERT INTO `appeals` (`user_id`, `reason`, `staff_present`, `type`) VALUES (?, ?, ?, ?)",
            [$userId, $reason, $staffPresent, $type]
        );

        return (int)$this->db->getConnection()->lastInsertId();
    }

    /**
     * Retrieve all pending appeals (admin view), oldest first.
     *
     * @return array<int, array<string,mixed>>
     */
    public function getAllPending(): array
    {
        return $this->db->query(
            "SELECT ap.*, u.username, u.discord_id
             FROM `appeals` ap
             JOIN `users` u ON u.id = ap.user_id
             WHERE ap.status = 'pending'
             ORDER BY ap.created_at ASC"
        )->fetchAll();
    }

    /**
     * Approve an appeal.
     *
     * @param int $appealId   Appeal DB id.
     * @param int $reviewerId Reviewer's internal user id.
     */
    public function approve(int $appealId, int $reviewerId): void
    {
        $this->db->query(
            "UPDATE `appeals`
             SET `status` = 'approved', `reviewed_by` = ?, `reviewed_at` = NOW()
             WHERE `id` = ?",
            [$reviewerId, $appealId]
        );
    }

    /**
     * Reject an appeal.
     *
     * @param int $appealId   Appeal DB id.
     * @param int $reviewerId Reviewer's internal user id.
     */
    public function reject(int $appealId, int $reviewerId): void
    {
        $this->db->query(
            "UPDATE `appeals`
             SET `status` = 'rejected', `reviewed_by` = ?, `reviewed_at` = NOW()
             WHERE `id` = ?",
            [$reviewerId, $appealId]
        );
    }

    /**
     * Get all appeals for a user, ordered newest first.
     *
     * @param int $userId Internal user DB id.
     * @return array<int, array<string,mixed>>
     */
    public function getAllByUserId(int $userId): array
    {
        return $this->db->query(
            "SELECT ap.*, u.username AS reviewer_name
             FROM `appeals` ap
             LEFT JOIN `users` u ON u.id = ap.reviewed_by
             WHERE ap.user_id = ?
             ORDER BY ap.created_at DESC",
            [$userId]
        )->fetchAll();
    }

    /**
     * Find an appeal by its ID.
     *
     * @param int $id Appeal DB id.
     * @return array<string,mixed>|null
     */
    public function findById(int $id): ?array
    {
        $row = $this->db->query(
            'SELECT * FROM `appeals` WHERE `id` = ? LIMIT 1',
            [$id]
        )->fetch();

        return $row ?: null;
    }

    /**
     * Retrieve all resolved (approved/rejected) appeals, newest first.
     *
     * @return array<int, array<string,mixed>>
     */
    public function getAllResolved(): array
    {
        return $this->db->query(
            "SELECT ap.*, u.username, u.discord_id, r.username AS reviewer_name
             FROM `appeals` ap
             JOIN `users` u ON u.id = ap.user_id
             LEFT JOIN `users` r ON r.id = ap.reviewed_by
             WHERE ap.status IN ('approved', 'rejected')
             ORDER BY ap.reviewed_at DESC"
        )->fetchAll();
    }
}
