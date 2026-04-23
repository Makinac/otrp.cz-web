<?php

declare(strict_types=1);

namespace App\Models;

class CheatsheetModel extends BaseModel
{
    public function getAll(): array
    {
        return $this->db->query(
            'SELECT * FROM `cheatsheet_sections` ORDER BY `sort_order` ASC, `id` ASC'
        )->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $row = $this->db->query(
            'SELECT * FROM `cheatsheet_sections` WHERE `id` = ? LIMIT 1',
            [$id]
        )->fetch();

        return $row ?: null;
    }

    public function create(string $title, string $bodyHtml, int $sortOrder, int $userId): int
    {
        $this->db->query(
            'INSERT INTO `cheatsheet_sections` (`title`, `body_html`, `sort_order`, `updated_by`) VALUES (?, ?, ?, ?)',
            [$title, $bodyHtml, $sortOrder, $userId]
        );

        return (int)$this->db->getConnection()->lastInsertId();
    }

    public function update(int $id, string $title, string $bodyHtml, int $sortOrder, int $userId): void
    {
        $this->db->query(
            'UPDATE `cheatsheet_sections`
             SET `title` = ?, `body_html` = ?, `sort_order` = ?, `updated_by` = ?
             WHERE `id` = ?',
            [$title, $bodyHtml, $sortOrder, $userId, $id]
        );
    }

    public function delete(int $id): void
    {
        $this->db->query('DELETE FROM `cheatsheet_sections` WHERE `id` = ?', [$id]);
    }

    public function getMaxSortOrder(): int
    {
        $row = $this->db->query('SELECT MAX(`sort_order`) AS m FROM `cheatsheet_sections`')->fetch();
        return (int)($row['m'] ?? 0);
    }

    public function renumber(): void
    {
        $rows = $this->db->query(
            'SELECT `id` FROM `cheatsheet_sections` ORDER BY `sort_order` ASC, `id` ASC'
        )->fetchAll();
        $i = 1;
        foreach ($rows as $row) {
            $this->db->query('UPDATE `cheatsheet_sections` SET `sort_order` = ? WHERE `id` = ?', [$i, $row['id']]);
            $i++;
        }
    }

    public function moveUp(int $id): void
    {
        $current = $this->findById($id);
        if (!$current) return;

        $prev = $this->db->query(
            'SELECT * FROM `cheatsheet_sections` WHERE `sort_order` < ? ORDER BY `sort_order` DESC LIMIT 1',
            [$current['sort_order']]
        )->fetch();

        if (!$prev) return;

        $this->db->query('UPDATE `cheatsheet_sections` SET `sort_order` = ? WHERE `id` = ?', [$prev['sort_order'], $id]);
        $this->db->query('UPDATE `cheatsheet_sections` SET `sort_order` = ? WHERE `id` = ?', [$current['sort_order'], $prev['id']]);
    }

    public function moveDown(int $id): void
    {
        $current = $this->findById($id);
        if (!$current) return;

        $next = $this->db->query(
            'SELECT * FROM `cheatsheet_sections` WHERE `sort_order` > ? ORDER BY `sort_order` ASC LIMIT 1',
            [$current['sort_order']]
        )->fetch();

        if (!$next) return;

        $this->db->query('UPDATE `cheatsheet_sections` SET `sort_order` = ? WHERE `id` = ?', [$next['sort_order'], $id]);
        $this->db->query('UPDATE `cheatsheet_sections` SET `sort_order` = ? WHERE `id` = ?', [$current['sort_order'], $next['id']]);
    }

    /**
     * Return N random sections (for cheat-sheet quiz on admin detail).
     */
    public function getRandom(int $count): array
    {
        return $this->db->query(
            'SELECT `id`, `title`, `body_html` FROM `cheatsheet_sections` ORDER BY RAND() LIMIT ?',
            [$count]
        )->fetchAll();
    }
}
