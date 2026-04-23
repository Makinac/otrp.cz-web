<?php

declare(strict_types=1);

namespace App\Models;

class VacationModel extends BaseModel
{
    /**
     * Get all vacations that overlap with a month range.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getForRange(string $dateFrom, string $dateTo): array
    {
        $sql = "SELECT v.*, u.username, u.discord_id, u.avatar
                FROM admin_vacations v
                JOIN users u ON u.id = v.user_id
                WHERE v.date_from <= ? AND v.date_to >= ?
                ORDER BY v.date_from ASC, u.username ASC";
        return $this->db->query($sql, [$dateTo, $dateFrom])->fetchAll();
    }

    /**
     * Get vacations for a specific user within a range.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getForUser(int $userId, string $dateFrom, string $dateTo): array
    {
        $sql = "SELECT * FROM admin_vacations
                WHERE user_id = ? AND date_from <= ? AND date_to >= ?
                ORDER BY date_from ASC";
        return $this->db->query($sql, [$userId, $dateTo, $dateFrom])->fetchAll();
    }

    /**
     * Create a new vacation entry.
     */
    public function create(int $userId, string $dateFrom, string $dateTo, ?string $note): void
    {
        $sql = "INSERT INTO admin_vacations (user_id, date_from, date_to, note) VALUES (?, ?, ?, ?)";
        $this->db->query($sql, [$userId, $dateFrom, $dateTo, $note]);
    }

    /**
     * Delete a vacation entry (only own).
     */
    public function delete(int $id, int $userId): bool
    {
        $sql = "DELETE FROM admin_vacations WHERE id = ? AND user_id = ?";
        $stmt = $this->db->query($sql, [$id, $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Delete any vacation entry by ID (Vedení override).
     */
    public function deleteAny(int $id): bool
    {
        $sql = "DELETE FROM admin_vacations WHERE id = ?";
        $stmt = $this->db->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Find vacation by ID.
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM admin_vacations WHERE id = ?";
        $row = $this->db->query($sql, [$id])->fetch();
        return $row ?: null;
    }
}
