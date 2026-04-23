<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Model for the `team_cache` table.
 * Caches Discord guild members per category for 24 hours.
 */
class TeamCacheModel extends BaseModel
{
    /**
     * Return all cached team members.
     */
    public function getAll(): array
    {
        return $this->db->query(
            'SELECT * FROM `team_cache` ORDER BY `category_id` ASC, `username` ASC'
        )->fetchAll();
    }

    /**
     * Return cached members for a specific category.
     */
    public function getByCategory(int $categoryId): array
    {
        return $this->db->query(
            'SELECT * FROM `team_cache` WHERE `category_id` = ? ORDER BY `username` ASC',
            [$categoryId]
        )->fetchAll();
    }

    /**
     * Check whether the team cache is still fresh (younger than 24 hours).
     */
    public function isFresh(): bool
    {
        $row = $this->db->query(
            'SELECT `cached_at` FROM `team_cache` ORDER BY `cached_at` ASC LIMIT 1'
        )->fetch();

        if (!$row) {
            return false;
        }

        $cachedAt = new \DateTimeImmutable($row['cached_at']);
        $diff     = (new \DateTimeImmutable())->getTimestamp() - $cachedAt->getTimestamp();

        return $diff < 86400;
    }

    /**
     * Clear the full cache and insert fresh member data per category.
     *
     * @param array<int, array{category_id: int, members: array}> $categoryMembers
     */
    public function refreshAll(array $categoryMembers): void
    {
        $this->db->query('DELETE FROM `team_cache`');

        foreach ($categoryMembers as $entry) {
            $catId   = $entry['category_id'];
            $members = $entry['members'];

            foreach ($members as $member) {
                $this->db->query(
                    'INSERT INTO `team_cache` (`category_id`, `discord_id`, `username`, `avatar_url`, `roles_json`)
                     VALUES (?, ?, ?, ?, ?)',
                    [
                        $catId,
                        $member['discord_id'],
                        $member['username'],
                        $member['avatar_url'] ?? null,
                        json_encode($member['roles'] ?? []),
                    ]
                );
            }
        }
    }

    /**
     * Refresh cache for a single category only.
     */
    public function refreshCategory(int $categoryId, array $members): void
    {
        $this->db->query('DELETE FROM `team_cache` WHERE `category_id` = ?', [$categoryId]);

        foreach ($members as $member) {
            $this->db->query(
                'INSERT INTO `team_cache` (`category_id`, `discord_id`, `username`, `avatar_url`, `roles_json`)
                 VALUES (?, ?, ?, ?, ?)',
                [
                    $categoryId,
                    $member['discord_id'],
                    $member['username'],
                    $member['avatar_url'] ?? null,
                    json_encode($member['roles'] ?? []),
                ]
            );
        }
    }
}
