<?php

declare(strict_types=1);

namespace App\Models;

/**
 * User model — CRUD for the `users` table.
 */
class UserModel extends BaseModel
{
    /**
     * Find a user record by Discord snowflake ID.
     *
     * @param string $discordId Discord user snowflake.
     * @return array<string,mixed>|null
     */
    public function findByDiscordId(string $discordId): ?array
    {
        $row = $this->db->query(
            'SELECT * FROM `users` WHERE `discord_id` = ? LIMIT 1',
            [$discordId]
        )->fetch();

        return $row ?: null;
    }

    /**
     * Find a user record by internal DB id.
     *
     * @param int $id Internal DB id.
     * @return array<string,mixed>|null
     */
    public function findById(int $id): ?array
    {
        $row = $this->db->query(
            'SELECT * FROM `users` WHERE `id` = ? LIMIT 1',
            [$id]
        )->fetch();

        return $row ?: null;
    }

    /**
     * Insert a new user or update if the Discord ID already exists.
     *
     * @param string      $discordId Discord snowflake.
     * @param string      $username  Discord username.
     * @param string|null $avatar    Avatar hash.
     * @return int Internal DB id.
     */
    public function upsert(string $discordId, string $username, ?string $avatar): int
    {
        $existing = $this->findByDiscordId($discordId);

        if ($existing) {
            $this->db->query(
                'UPDATE `users` SET `username` = ?, `avatar` = ? WHERE `discord_id` = ?',
                [$username, $avatar, $discordId]
            );
            return (int)$existing['id'];
        }

        $this->db->query(
            'INSERT INTO `users` (`discord_id`, `username`, `avatar`) VALUES (?, ?, ?)',
            [$discordId, $username, $avatar]
        );

        return (int)$this->db->getConnection()->lastInsertId();
    }

    /**
     * Update the stored Discord roles and refresh timestamp for a user.
     *
     * @param int           $userId    Internal DB id.
     * @param array<string> $roleNames Resolved role name array.
     * @param array<string> $roleIds   Raw Discord role IDs.
     */
    public function updateRoles(int $userId, array $roleNames, array $roleIds = []): void
    {
        $this->db->query(
            'UPDATE `users` SET `roles_json` = ?, `role_ids_json` = ?, `roles_cached_at` = NOW() WHERE `id` = ?',
            [json_encode($roleNames), json_encode(array_values(array_map('strval', $roleIds))), $userId]
        );
    }

    /**
     * Check whether the roles cache is still fresh (updated within 24 hours).
     *
     * @param array<string,mixed> $user User row from DB.
     * @return bool True when the cache is still valid.
     */
    public function isRolesCacheFresh(array $user): bool
    {
        if (empty($user['roles_cached_at'])) {
            return false;
        }

        $cachedAt = new \DateTimeImmutable($user['roles_cached_at']);
        $now      = new \DateTimeImmutable();
        $diff     = $now->getTimestamp() - $cachedAt->getTimestamp();

        return $diff < 86400; // 24 hours
    }

    /**
     * Decode the roles_json column to an array of role name strings.
     *
     * @param array<string,mixed> $user User row from DB.
     * @return array<string>
     */
    public function getRoleNames(array $user): array
    {
        if (empty($user['roles_json'])) {
            return [];
        }

        $decoded = json_decode($user['roles_json'], true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Decode the role_ids_json column to an array of role ID strings.
     *
     * @param array<string,mixed> $user User row from DB.
     * @return array<string>
     */
    public function getRoleIds(array $user): array
    {
        if (empty($user['role_ids_json'])) {
            return [];
        }

        $decoded = json_decode((string)$user['role_ids_json'], true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $decoded), static fn(string $v): bool => $v !== ''));
    }

    /**
     * Set server access flag for a user.
     *
     * @param int    $userId Internal DB user id.
     * @param string $type   'dev' or 'maps'.
     * @param bool   $value  True to grant, false to revoke.
     */
    public function setAccess(int $userId, string $type, bool $value): void
    {
        $col = match ($type) {
            'dev'  => 'access_dev',
            'maps' => 'access_maps',
            default => throw new \InvalidArgumentException("Unknown access type: {$type}"),
        };
        $this->db->query(
            "UPDATE users SET `{$col}` = ? WHERE id = ?",
            [(int)$value, $userId]
        );
    }

    /**
     * Get all users that have had their Discord roles cached (staff/members who logged in).
     * Used for the witness picker in admin actions.
     *
     * @return array<int, array<string,mixed>>
     */
    public function getStaffUsers(): array
    {
        return $this->db->query(
            "SELECT id, username, discord_id
             FROM users
             WHERE roles_json IS NOT NULL AND roles_json != 'null' AND roles_json != '[]'
             ORDER BY username ASC"
        )->fetchAll();
    }

    /**
     * Search users by username or Discord ID (partial match).
     *
     * @param string $query Search string.
     * @return array<int, array<string,mixed>>
     */
    public function search(string $query): array
    {
        $like = '%' . $query . '%';
        return $this->db->query(
            "SELECT u.*,
                    (SELECT COUNT(*) FROM allowlist_applications a WHERE a.user_id = u.id) AS app_count,
                    (SELECT a2.status FROM allowlist_applications a2 WHERE a2.user_id = u.id ORDER BY a2.submitted_at DESC LIMIT 1) AS latest_status,
                    (SELECT a3.interview_status FROM allowlist_applications a3 WHERE a3.user_id = u.id ORDER BY a3.submitted_at DESC LIMIT 1) AS latest_interview_status,
                    (SELECT COUNT(*) FROM blacklist b WHERE b.discord_id = u.discord_id) AS is_blacklisted
             FROM users u
             WHERE u.username LIKE ? OR u.discord_id LIKE ?
             ORDER BY u.username ASC
             LIMIT 50",
            [$like, $like]
        )->fetchAll();
    }

    /**
     * Get all users with their allowlist summary (for listing).
     *
     * @return array<int, array<string,mixed>>
     */
    public function getAllWithStatus(): array
    {
        return $this->db->query(
            "SELECT u.*,
                    (SELECT COUNT(*) FROM allowlist_applications a WHERE a.user_id = u.id) AS app_count,
                    (SELECT a2.status FROM allowlist_applications a2 WHERE a2.user_id = u.id ORDER BY a2.submitted_at DESC LIMIT 1) AS latest_status,
                    (SELECT a3.interview_status FROM allowlist_applications a3 WHERE a3.user_id = u.id ORDER BY a3.submitted_at DESC LIMIT 1) AS latest_interview_status,
                    (SELECT COUNT(*) FROM blacklist b WHERE b.discord_id = u.discord_id) AS is_blacklisted
             FROM users u
             ORDER BY u.username ASC
             LIMIT 200"
        )->fetchAll();
    }

    /**
     * Get users for the management permission picker.
     *
     * @return array<int, array<string,mixed>>
     */
    public function getUsersForPermissionPicker(): array
    {
        return $this->db->query(
            "SELECT id, username, discord_id
             FROM users
             ORDER BY username ASC
             LIMIT 500"
        )->fetchAll();
    }

    /**
     * Build a deduplicated list of Discord role names known from cached users.
     *
     * @return array<int, string>
     */
    public function getKnownRoleNames(): array
    {
        $roles = $this->getKnownRolesForPermissionPicker();
        $names = [];

        foreach ($roles as $role) {
            $name = trim((string)($role['name'] ?? ''));
            if ($name !== '') {
                $names[$name] = true;
            }
        }

        $list = array_keys($names);
        sort($list, SORT_NATURAL | SORT_FLAG_CASE);
        return $list;
    }

    /**
     * Build a deduplicated list of known Discord roles with best-effort ID mapping.
     *
     * @return array<int,array{id:string,name:string,label:string}>
     */
    public function getKnownRolesForPermissionPicker(): array
    {
        $rows = $this->db->query(
            "SELECT roles_json, role_ids_json
             FROM users
             WHERE roles_json IS NOT NULL AND roles_json != 'null' AND roles_json != '[]'"
        )->fetchAll();

        $rolesByKey = [];

        foreach ($rows as $row) {
            $roleNames = json_decode((string)$row['roles_json'], true);
            if (!is_array($roleNames)) {
                continue;
            }

            $roleIds = [];
            if (!empty($row['role_ids_json'])) {
                $decodedIds = json_decode((string)$row['role_ids_json'], true);
                if (is_array($decodedIds)) {
                    $roleIds = array_values(array_map('strval', $decodedIds));
                }
            }

            foreach ($roleNames as $idx => $roleName) {
                $name = trim((string)$roleName);
                if ($name === '') {
                    continue;
                }

                $id = trim((string)($roleIds[$idx] ?? ''));
                if ($id !== '') {
                    $key = 'id:' . $id;
                    $rolesByKey[$key] = [
                        'id'    => $id,
                        'name'  => $name,
                        'label' => $name . ' (' . $id . ')',
                    ];
                } else {
                    $key = 'name:' . $name;
                    if (!isset($rolesByKey[$key])) {
                        $rolesByKey[$key] = [
                            'id'    => $name,
                            'name'  => $name,
                            'label' => $name,
                        ];
                    }
                }
            }
        }

        $list = array_values($rolesByKey);
        usort($list, static function (array $a, array $b): int {
            return strcasecmp($a['name'], $b['name']);
        });

        return $list;
    }
}
