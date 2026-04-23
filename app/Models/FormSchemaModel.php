<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Model for the `form_schema` table.
 */
class FormSchemaModel extends BaseModel
{
    /**
     * Return the currently active form schema, or null if none.
     *
     * @return array<string,mixed>|null
     */
    public function getActive(): ?array
    {
        $row = $this->db->query(
            "SELECT * FROM `form_schema` WHERE `active` = 1 LIMIT 1"
        )->fetch();

        return $row ?: null;
    }

    /**
     * Return all form schemas.
     *
     * @return array<int, array<string,mixed>>
     */
    public function getAll(): array
    {
        return $this->db->query(
            'SELECT * FROM `form_schema` ORDER BY `created_at` DESC'
        )->fetchAll();
    }

    /**
     * Find a schema by ID.
     *
     * @param int $id Schema DB id.
     * @return array<string,mixed>|null
     */
    public function findById(int $id): ?array
    {
        $row = $this->db->query(
            'SELECT * FROM `form_schema` WHERE `id` = ? LIMIT 1',
            [$id]
        )->fetch();

        return $row ?: null;
    }

    /**
     * Create a new form schema.
     *
     * @param string              $name      Human-readable schema name.
     * @param array<mixed>        $fields    Decoded fields array.
     * @param bool                $active    Whether to immediately activate.
     * @return int New schema ID.
     */
    public function create(string $name, array $fields, bool $active = false): int
    {
        if ($active) {
            $this->deactivateAll();
        }

        $this->db->query(
            'INSERT INTO `form_schema` (`name`, `fields_json`, `active`) VALUES (?, ?, ?)',
            [$name, json_encode($fields), (int)$active]
        );

        return (int)$this->db->getConnection()->lastInsertId();
    }

    /**
     * Update an existing form schema.
     *
     * @param int          $id     Schema DB id.
     * @param string       $name   Human-readable name.
     * @param array<mixed> $fields Decoded fields array.
     * @param bool         $active Whether this schema should be active.
     */
    public function update(int $id, string $name, array $fields, bool $active): void
    {
        if ($active) {
            $this->deactivateAll();
        }

        $this->db->query(
            'UPDATE `form_schema` SET `name` = ?, `fields_json` = ?, `active` = ? WHERE `id` = ?',
            [$name, json_encode($fields), (int)$active, $id]
        );
    }

    /**
     * Delete a form schema by ID.
     *
     * @param int $id Schema DB id.
     */
    public function delete(int $id): void
    {
        $this->db->query('DELETE FROM `form_schema` WHERE `id` = ?', [$id]);
    }

    /**
     * Set all schemas to inactive.
     */
    public function deactivateAll(): void
    {
        $this->db->query('UPDATE `form_schema` SET `active` = 0');
    }

    /**
     * Activate a single schema by ID.
     *
     * @param int $id Schema DB id.
     */
    public function activate(int $id): void
    {
        $this->deactivateAll();
        $this->db->query('UPDATE `form_schema` SET `active` = 1 WHERE `id` = ?', [$id]);
    }
}
