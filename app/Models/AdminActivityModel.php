<?php

declare(strict_types=1);

namespace App\Models;

class AdminActivityModel extends BaseModel
{
    /**
     * Get activities for a user within a date range.
     *
     * @return array<string,array<string,mixed>> Keyed by activity_date (Y-m-d).
     */
    public function getForUserInRange(int $userId, string $dateFrom, string $dateTo): array
    {
        $sql = "SELECT * FROM admin_activities
                WHERE user_id = ? AND activity_date BETWEEN ? AND ?
                ORDER BY activity_date ASC";
        $rows = $this->db->query($sql, [$userId, $dateFrom, $dateTo])->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['activity_date']] = $row;
        }
        return $result;
    }

    /**
     * Save (insert or update) an activity for a specific day.
     */
    public function save(int $userId, string $date, bool $wasActive, ?string $description): void
    {
        $sql = "INSERT INTO admin_activities (user_id, activity_date, was_active, description)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE was_active = VALUES(was_active), description = VALUES(description)";
        $this->db->query($sql, [$userId, $date, $wasActive ? 1 : 0, $description]);
    }

    /**
     * Get all activities for all admins in a date range (for vedení overview).
     *
     * @return array<int,array{user_id:int, username:string, avatar:?string, discord_id:string, days:array<string,array>}>
     */
    public function getAllInRange(string $dateFrom, string $dateTo): array
    {
        $sql = "SELECT a.*, u.username, u.avatar, u.discord_id
                FROM admin_activities a
                JOIN users u ON u.id = a.user_id
                WHERE a.activity_date BETWEEN ? AND ?
                ORDER BY u.username ASC, a.activity_date ASC";
        $rows = $this->db->query($sql, [$dateFrom, $dateTo])->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $uid = (int)$row['user_id'];
            if (!isset($grouped[$uid])) {
                $grouped[$uid] = [
                    'user_id'    => $uid,
                    'username'   => $row['username'],
                    'avatar'     => $row['avatar'],
                    'discord_id' => $row['discord_id'],
                    'days'       => [],
                ];
            }
            $grouped[$uid]['days'][$row['activity_date']] = $row;
        }
        return array_values($grouped);
    }

    /**
     * Get users who have any activity records in a date range.
     *
     * @return array<int,array{user_id:int, username:string, discord_id:string, avatar:?string}>
     */
    public function getUsersWithActivity(string $dateFrom, string $dateTo): array
    {
        $sql = "SELECT DISTINCT a.user_id, u.username, u.discord_id, u.avatar
                FROM admin_activities a
                JOIN users u ON u.id = a.user_id
                WHERE a.activity_date BETWEEN ? AND ?
                ORDER BY u.username ASC";
        return $this->db->query($sql, [$dateFrom, $dateTo])->fetchAll();
    }
}
