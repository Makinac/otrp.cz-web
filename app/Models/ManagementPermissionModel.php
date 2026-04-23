<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Stores delegated Management permissions for Discord roles and specific users.
 */
class ManagementPermissionModel extends BaseModel
{
    /**
     * @param int $userId
     * @param array<int,string> $roleNames
     * @param array<int,string> $roleIds
     * @return array<int,string>
     */
    public function getPermissionKeysForUser(int $userId, array $roleNames, array $roleIds = []): array
    {
        $params = [(string)$userId];
        $sql = "SELECT DISTINCT permission_key
                FROM management_permissions
                WHERE (subject_type = 'user' AND subject_value = ?)";

        $roleSubjects = array_values(array_unique(array_filter(array_merge(
            array_map('strval', $roleNames),
            array_map('strval', $roleIds)
        ), static fn(string $v): bool => $v !== '')));

        if (!empty($roleSubjects)) {
            $placeholders = implode(', ', array_fill(0, count($roleSubjects), '?'));
            $sql .= " OR (subject_type = 'role' AND subject_value IN ({$placeholders}))";
            foreach ($roleSubjects as $role) {
                $params[] = $role;
            }
        }

        $rows = $this->db->query($sql, $params)->fetchAll();
        return array_values(array_unique(array_map(static fn(array $r): string => (string)$r['permission_key'], $rows)));
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getAllWithLabels(): array
    {
        return $this->db->query(
            "SELECT mp.*, u.username AS user_name, u.discord_id AS user_discord_id
             FROM management_permissions mp
             LEFT JOIN users u ON (mp.subject_type = 'user' AND u.id = CAST(mp.subject_value AS UNSIGNED))
             ORDER BY mp.permission_key ASC, mp.subject_type ASC, mp.subject_value ASC"
        )->fetchAll();
    }

    /**
     * Get all permissions grouped by subject (type+value).
     *
     * @return array<string,array{subject_type:string,subject_value:string,label:string,keys:array<string,int>}>
     */
    public function getGroupedBySubject(): array
    {
        $rows = $this->db->query(
            "SELECT mp.id, mp.permission_key, mp.subject_type, mp.subject_value,
                    u.username AS user_name, u.discord_id AS user_discord_id
             FROM management_permissions mp
             LEFT JOIN users u ON (mp.subject_type = 'user' AND u.id = CAST(mp.subject_value AS UNSIGNED))
             ORDER BY mp.subject_type ASC, mp.subject_value ASC"
        )->fetchAll();

        $groups = [];
        foreach ($rows as $row) {
            $key = $row['subject_type'] . ':' . $row['subject_value'];
            if (!isset($groups[$key])) {
                $label = $row['subject_value'];
                if ($row['subject_type'] === 'user') {
                    $label = ($row['user_name'] ?? ('ID ' . $row['subject_value']));
                    if (!empty($row['user_discord_id'])) {
                        $label .= ' (' . $row['user_discord_id'] . ')';
                    }
                }
                $groups[$key] = [
                    'subject_type'  => $row['subject_type'],
                    'subject_value' => $row['subject_value'],
                    'label'         => $label,
                    'keys'          => [],
                ];
            }
            $groups[$key]['keys'][$row['permission_key']] = (int)$row['id'];
        }
        return $groups;
    }

    /**
     * Sync (bulk set) permission keys for a specific subject.
     *
     * @param string        $subjectType  'role' or 'user'
     * @param string        $subjectValue Role ID or user ID
     * @param array<string> $newKeys      Permission keys that should be active
     * @param array<string> $allKeys      All valid permission keys
     * @param int           $createdBy    Acting user ID
     */
    public function syncPermissions(string $subjectType, string $subjectValue, array $newKeys, array $allKeys, int $createdBy): void
    {
        $current = $this->db->query(
            "SELECT id, permission_key FROM management_permissions WHERE subject_type = ? AND subject_value = ?",
            [$subjectType, $subjectValue]
        )->fetchAll();

        $currentMap = [];
        foreach ($current as $row) {
            $currentMap[$row['permission_key']] = (int)$row['id'];
        }

        // Grant new
        foreach ($newKeys as $key) {
            if (!in_array($key, $allKeys, true)) continue;
            if (!isset($currentMap[$key])) {
                $this->grant($key, $subjectType, $subjectValue, $createdBy);
            }
        }

        // Revoke removed
        foreach ($currentMap as $key => $id) {
            if (!in_array($key, $newKeys, true)) {
                $this->revoke($id);
            }
        }
    }

    /**
     * Remove ALL permissions for a subject.
     */
    public function revokeAllForSubject(string $subjectType, string $subjectValue): void
    {
        $this->db->query(
            "DELETE FROM management_permissions WHERE subject_type = ? AND subject_value = ?",
            [$subjectType, $subjectValue]
        );
    }

    public function grant(string $permissionKey, string $subjectType, string $subjectValue, int $createdBy): void
    {
        $exists = $this->db->query(
            "SELECT id FROM management_permissions WHERE permission_key = ? AND subject_type = ? AND subject_value = ? LIMIT 1",
            [$permissionKey, $subjectType, $subjectValue]
        )->fetch();

        if ($exists) {
            return;
        }

        $this->db->query(
            "INSERT INTO management_permissions (permission_key, subject_type, subject_value, created_by)
             VALUES (?, ?, ?, ?)",
            [$permissionKey, $subjectType, $subjectValue, $createdBy]
        );
    }

    public function revoke(int $id): void
    {
        $this->db->query("DELETE FROM management_permissions WHERE id = ?", [$id]);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findById(int $id): ?array
    {
        $row = $this->db->query(
            "SELECT * FROM management_permissions WHERE id = ? LIMIT 1",
            [$id]
        )->fetch();

        return $row ?: null;
    }
}
