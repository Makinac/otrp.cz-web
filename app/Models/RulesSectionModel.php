<?php

declare(strict_types=1);

namespace App\Models;

class RulesSectionModel extends BaseModel
{
    /** Return all sections ordered by sort_order. */
    public function getAll(): array
    {
        return $this->db->query(
            'SELECT * FROM `rules_sections` ORDER BY `sort_order` ASC, `id` ASC'
        )->fetchAll();
    }

    /** Find a single section by id. */
    public function findById(int $id): ?array
    {
        $row = $this->db->query(
            'SELECT * FROM `rules_sections` WHERE `id` = ? LIMIT 1',
            [$id]
        )->fetch();

        return $row ?: null;
    }

    /** Create a new section and return its id. */
    public function create(string $title, string $bodyHtml, int $sortOrder, int $userId): int
    {
        $this->db->query(
            'INSERT INTO `rules_sections` (`title`, `body_html`, `sort_order`, `updated_by`) VALUES (?, ?, ?, ?)',
            [$title, $bodyHtml, $sortOrder, $userId]
        );

        return (int)$this->db->getConnection()->lastInsertId();
    }

    /** Update an existing section. */
    public function update(int $id, string $title, string $bodyHtml, int $sortOrder, int $userId): void
    {
        $this->db->query(
            'UPDATE `rules_sections`
             SET `title` = ?, `body_html` = ?, `sort_order` = ?, `updated_by` = ?
             WHERE `id` = ?',
            [$title, $bodyHtml, $sortOrder, $userId, $id]
        );
    }

    /** Delete a section. */
    public function delete(int $id): void
    {
        $this->db->query('DELETE FROM `rules_sections` WHERE `id` = ?', [$id]);
    }

    /** Return the current highest sort_order value. */
    public function getMaxSortOrder(): int
    {
        $row = $this->db->query('SELECT MAX(`sort_order`) AS m FROM `rules_sections`')->fetch();
        return (int)($row['m'] ?? 0);
    }

    /** Reassign sort_order as 1, 2, 3, … in current order. */
    public function renumber(): void
    {
        $rows = $this->db->query(
            'SELECT `id` FROM `rules_sections` ORDER BY `sort_order` ASC, `id` ASC'
        )->fetchAll();
        $i = 1;
        foreach ($rows as $row) {
            $this->db->query('UPDATE `rules_sections` SET `sort_order` = ? WHERE `id` = ?', [$i, $row['id']]);
            $i++;
        }
    }

    /** Swap sort_order with the section that comes immediately before. */
    public function moveUp(int $id): void
    {
        $current = $this->findById($id);
        if (!$current) {
            return;
        }

        $prev = $this->db->query(
            'SELECT * FROM `rules_sections` WHERE `sort_order` < ? ORDER BY `sort_order` DESC LIMIT 1',
            [$current['sort_order']]
        )->fetch();

        if (!$prev) {
            return;
        }

        $this->db->query('UPDATE `rules_sections` SET `sort_order` = ? WHERE `id` = ?', [$prev['sort_order'], $id]);
        $this->db->query('UPDATE `rules_sections` SET `sort_order` = ? WHERE `id` = ?', [$current['sort_order'], $prev['id']]);
    }

    /** Swap sort_order with the section that comes immediately after. */
    public function moveDown(int $id): void
    {
        $current = $this->findById($id);
        if (!$current) {
            return;
        }

        $next = $this->db->query(
            'SELECT * FROM `rules_sections` WHERE `sort_order` > ? ORDER BY `sort_order` ASC LIMIT 1',
            [$current['sort_order']]
        )->fetch();

        if (!$next) {
            return;
        }

        $this->db->query('UPDATE `rules_sections` SET `sort_order` = ? WHERE `id` = ?', [$next['sort_order'], $id]);
        $this->db->query('UPDATE `rules_sections` SET `sort_order` = ? WHERE `id` = ?', [$current['sort_order'], $next['id']]);
    }
}
