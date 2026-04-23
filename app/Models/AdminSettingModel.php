<?php

declare(strict_types=1);

namespace App\Models;

class AdminSettingModel extends BaseModel
{
    private const DEFAULTS = [
        'admin_prefix_chat'    => 1,
        'report_notifications' => 1,
    ];

    /**
     * Get settings for a user. Returns defaults if no row exists.
     *
     * @return array{admin_prefix_chat: bool, report_notifications: bool}
     */
    public function getForUser(int $userId): array
    {
        $row = $this->db->query(
            'SELECT `admin_prefix_chat`, `report_notifications` FROM `admin_settings` WHERE `user_id` = ? LIMIT 1',
            [$userId]
        )->fetch();

        return [
            'admin_prefix_chat'    => (bool)($row['admin_prefix_chat'] ?? self::DEFAULTS['admin_prefix_chat']),
            'report_notifications' => (bool)($row['report_notifications'] ?? self::DEFAULTS['report_notifications']),
        ];
    }

    /**
     * Upsert settings for a user.
     */
    public function save(int $userId, bool $adminPrefixChat, bool $reportNotifications): void
    {
        $this->db->query(
            'INSERT INTO `admin_settings` (`user_id`, `admin_prefix_chat`, `report_notifications`)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE
                `admin_prefix_chat`    = VALUES(`admin_prefix_chat`),
                `report_notifications` = VALUES(`report_notifications`)',
            [$userId, (int)$adminPrefixChat, (int)$reportNotifications]
        );
    }

    /**
     * Get settings by Discord ID. Returns defaults if no row exists.
     *
     * @return array{admin_prefix_chat: bool, report_notifications: bool}
     */
    public function getByDiscordId(string $discordId): array
    {
        $row = $this->db->query(
            'SELECT s.`admin_prefix_chat`, s.`report_notifications`
             FROM `admin_settings` s
             JOIN `users` u ON u.`id` = s.`user_id`
             WHERE u.`discord_id` = ?
             LIMIT 1',
            [$discordId]
        )->fetch();

        return [
            'admin_prefix_chat'    => (bool)($row['admin_prefix_chat'] ?? self::DEFAULTS['admin_prefix_chat']),
            'report_notifications' => (bool)($row['report_notifications'] ?? self::DEFAULTS['report_notifications']),
        ];
    }
}
