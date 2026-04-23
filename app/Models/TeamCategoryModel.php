<?php

declare(strict_types=1);

namespace App\Models;

class TeamCategoryModel extends BaseModel
{
    public function getAll(): array
    {
        return $this->db->query(
            'SELECT * FROM `team_categories` ORDER BY `sort_order` ASC, `id` ASC'
        )->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $row = $this->db->query(
            'SELECT * FROM `team_categories` WHERE `id` = ?',
            [$id]
        )->fetch();

        return $row ?: null;
    }

    public function create(string $name, array $roleIds, int $sortOrder = 0, ?string $color = null): void
    {
        $this->db->query(
            'INSERT INTO `team_categories` (`name`, `color`, `role_ids_json`, `sort_order`) VALUES (?, ?, ?, ?)',
            [$name, $color, json_encode(array_values($roleIds)), $sortOrder]
        );
    }

    public function update(int $id, string $name, array $roleIds, ?string $color = null): void
    {
        $this->db->query(
            'UPDATE `team_categories` SET `name` = ?, `color` = ?, `role_ids_json` = ? WHERE `id` = ?',
            [$name, $color, json_encode(array_values($roleIds)), $id]
        );
    }

    public function delete(int $id): void
    {
        $this->db->query('DELETE FROM `team_cache` WHERE `category_id` = ?', [$id]);
        $this->db->query('DELETE FROM `team_categories` WHERE `id` = ?', [$id]);
    }

    public function moveUp(int $id): void
    {
        $current = $this->findById($id);
        if (!$current) return;

        $prev = $this->db->query(
            'SELECT * FROM `team_categories` WHERE `sort_order` < ? ORDER BY `sort_order` DESC LIMIT 1',
            [$current['sort_order']]
        )->fetch();

        if ($prev) {
            $this->swapOrder((int)$current['id'], (int)$current['sort_order'], (int)$prev['id'], (int)$prev['sort_order']);
        }
    }

    public function moveDown(int $id): void
    {
        $current = $this->findById($id);
        if (!$current) return;

        $next = $this->db->query(
            'SELECT * FROM `team_categories` WHERE `sort_order` > ? ORDER BY `sort_order` ASC LIMIT 1',
            [$current['sort_order']]
        )->fetch();

        if ($next) {
            $this->swapOrder((int)$current['id'], (int)$current['sort_order'], (int)$next['id'], (int)$next['sort_order']);
        }
    }

    public function renumber(): void
    {
        $rows = $this->getAll();
        foreach ($rows as $i => $row) {
            $this->db->query(
                'UPDATE `team_categories` SET `sort_order` = ? WHERE `id` = ?',
                [$i + 1, $row['id']]
            );
        }
    }

    private function swapOrder(int $idA, int $orderA, int $idB, int $orderB): void
    {
        $this->db->query('UPDATE `team_categories` SET `sort_order` = ? WHERE `id` = ?', [$orderB, $idA]);
        $this->db->query('UPDATE `team_categories` SET `sort_order` = ? WHERE `id` = ?', [$orderA, $idB]);
    }
}
