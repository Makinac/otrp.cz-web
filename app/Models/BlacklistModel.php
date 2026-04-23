<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Model for the `blacklist` table.
 */
class BlacklistModel extends BaseModel
{
    /**
     * Check whether a Discord user is currently blacklisted.
     *
     * @param string $discordId Discord snowflake.
     * @return array<string,mixed>|null Blacklist row or null.
     */
    public function findByDiscordId(string $discordId): ?array
    {
        $row = $this->db->query(
            'SELECT b.*, u.username AS added_by_username
             FROM `blacklist` b
             LEFT JOIN `users` u ON u.id = b.added_by
             WHERE b.discord_id = ?
             LIMIT 1',
            [$discordId]
        )->fetch();

        return $row ?: null;
    }

    /**
     * Add a Discord user to the blacklist.
     *
     * @param string      $discordId Discord snowflake of the target.
     * @param int         $addedBy   Internal user DB id of the admin.
     * @param string|null $name      Display name (visible to management).
     * @param string|null $reason    Reason for blacklisting (visible to management).
     */
    public function add(string $discordId, int $addedBy, ?string $name = null, ?string $reason = null): void
    {
        $this->db->query(
            'INSERT INTO `blacklist` (`discord_id`, `name`, `reason`, `added_by`) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `reason` = VALUES(`reason`), `added_by` = VALUES(`added_by`), `added_at` = NOW()',
            [$discordId, $name, $reason, $addedBy]
        );
    }

    /**
     * Remove an entry from the blacklist.
     *
     * @param int $id Blacklist row id.
     */
    public function remove(int $id): void
    {
        $this->db->query(
            'DELETE FROM `blacklist` WHERE `id` = ?',
            [$id]
        );
    }

    /**
     * Retrieve all blacklisted users.
     *
     * @return array<int, array<string,mixed>>
     */
    public function getAll(): array
    {
        return $this->db->query(
            'SELECT b.*,
                    a.username AS added_by_username
             FROM `blacklist` b
             LEFT JOIN `users` a ON a.id = b.added_by
             ORDER BY b.added_at DESC'
        )->fetchAll();
    }
}
