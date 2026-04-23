<?php

declare(strict_types=1);

namespace App\Models;

/**
 * PlayerNoteModel — CRUD for player_notes table.
 */
class PlayerNoteModel extends BaseModel
{
    /**
     * Get all notes for a player, newest first.
     * Joins author username.
     *
     * @return array<int, array<string,mixed>>
     */
    public function getByUserId(int $userId): array
    {
        return $this->db->query(
            "SELECT pn.`id`, pn.`note`, pn.`created_at`,
                    pn.`author_id`,
                    u.`username` AS author_name,
                    u.`avatar`   AS author_avatar,
                    u.`discord_id` AS author_discord_id
             FROM `player_notes` pn
             LEFT JOIN `users` u ON u.`id` = pn.`author_id`
             WHERE pn.`user_id` = ?
             ORDER BY pn.`created_at` DESC",
            [$userId]
        )->fetchAll();
    }

    /**
     * Add a new note.
     */
    public function add(int $userId, int $authorId, string $note): int
    {
        $this->db->query(
            "INSERT INTO `player_notes` (`user_id`, `author_id`, `note`) VALUES (?, ?, ?)",
            [$userId, $authorId, $note]
        );
        return (int)$this->db->lastInsertId();
    }

    /**
     * Delete a note. Returns true if deleted.
     */
    public function delete(int $noteId): bool
    {
        $stmt = $this->db->query(
            "DELETE FROM `player_notes` WHERE `id` = ?",
            [$noteId]
        );
        return $stmt->rowCount() > 0;
    }

    /**
     * Find a note by id (for ownership check).
     *
     * @return array<string,mixed>|null
     */
    public function findById(int $noteId): ?array
    {
        $row = $this->db->query(
            "SELECT * FROM `player_notes` WHERE `id` = ?",
            [$noteId]
        )->fetch();
        return $row ?: null;
    }
}
