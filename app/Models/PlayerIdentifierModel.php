<?php

declare(strict_types=1);

namespace App\Models;

class PlayerIdentifierModel extends BaseModel
{
    private const ALLOWED_TYPES = ['license', 'license2', 'steam', 'ip', 'xbl', 'live', 'fivem', 'discord'];

    /**
     * Get all identifiers for a user.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getByUserId(int $userId): array
    {
        return $this->db->query(
            'SELECT * FROM `player_identifiers` WHERE `user_id` = ? ORDER BY `identifier_type`, `first_seen_at`',
            [$userId]
        )->fetchAll();
    }

    /**
     * Get identifiers grouped by type for a user.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getGroupedByUserId(int $userId): array
    {
        $rows = $this->getByUserId($userId);
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['identifier_type']][] = $row;
        }
        return $grouped;
    }

    /**
     * Sync identifiers from a FiveM connect event.
     * Returns arrays of new and existing identifiers.
     *
     * @param int                $userId
     * @param array<string,string> $identifiers  ['license' => 'xxx', 'steam' => 'yyy', ...]
     * @return array{new: array<string,string>, existing: array<string,string>}
     */
    public function syncIdentifiers(int $userId, array $identifiers): array
    {
        $new = [];
        $existing = [];

        foreach ($identifiers as $type => $value) {
            $type = strtolower(trim($type));
            $value = trim($value);

            if (!in_array($type, self::ALLOWED_TYPES, true) || $value === '') {
                continue;
            }

            $row = $this->db->query(
                'SELECT `id` FROM `player_identifiers` WHERE `user_id` = ? AND `identifier_type` = ? AND `identifier_value` = ?',
                [$userId, $type, $value]
            )->fetch();

            if ($row) {
                // Update last_seen_at
                $this->db->query(
                    'UPDATE `player_identifiers` SET `last_seen_at` = NOW() WHERE `id` = ?',
                    [(int)$row['id']]
                );
                $existing[$type] = $value;
            } else {
                $this->db->query(
                    'INSERT INTO `player_identifiers` (`user_id`, `identifier_type`, `identifier_value`) VALUES (?, ?, ?)',
                    [$userId, $type, $value]
                );
                $new[$type] = $value;
            }
        }

        return ['new' => $new, 'existing' => $existing];
    }

    /**
     * Find other users that share the same identifier value (for multi-account detection).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findOtherUsersWithIdentifier(int $excludeUserId, string $type, string $value): array
    {
        return $this->db->query(
            'SELECT pi.*, u.username, u.discord_id
             FROM `player_identifiers` pi
             JOIN `users` u ON u.id = pi.user_id
             WHERE pi.identifier_type = ? AND pi.identifier_value = ? AND pi.user_id != ?',
            [$type, $value, $excludeUserId]
        )->fetchAll();
    }

    /**
     * Check if a user had a different value for this identifier type before.
     *
     * @return array<int, array<string, mixed>>  Previous identifier records for this type
     */
    public function getPreviousForType(int $userId, string $type, string $currentValue): array
    {
        return $this->db->query(
            'SELECT * FROM `player_identifiers`
             WHERE `user_id` = ? AND `identifier_type` = ? AND `identifier_value` != ?
             ORDER BY `last_seen_at` DESC',
            [$userId, $type, $currentValue]
        )->fetchAll();
    }
}
