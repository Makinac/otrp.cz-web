<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Model for the `news` table.
 */
class NewsModel extends BaseModel
{
    /**
     * Retrieve paginated news items.
     *
     * @param int $page    1-based page number.
     * @param int $perPage Number of items per page.
     * @return array<int, array<string,mixed>>
     */
    public function getPaginated(int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        return $this->db->query(
            'SELECT n.*, u.username AS author_name
             FROM `news` n
             LEFT JOIN `users` u ON u.id = n.author_id
             ORDER BY n.published_at DESC
             LIMIT ? OFFSET ?',
            [$perPage, $offset]
        )->fetchAll();
    }

    /**
     * Count total news items (for pagination).
     *
     * @return int
     */
    public function countAll(): int
    {
        return (int)$this->db->query(
            'SELECT COUNT(*) FROM `news`'
        )->fetchColumn();
    }

    /**
     * Find a news item by its URL slug.
     *
     * @param string $slug URL slug.
     * @return array<string,mixed>|null
     */
    public function findBySlug(string $slug): ?array
    {
        $row = $this->db->query(
            'SELECT n.*, u.username AS author_name
             FROM `news` n
             LEFT JOIN `users` u ON u.id = n.author_id
             WHERE n.slug = ?
             LIMIT 1',
            [$slug]
        )->fetch();

        return $row ?: null;
    }

    /**
     * Find a news item by internal ID.
     *
     * @param int $id News DB id.
     * @return array<string,mixed>|null
     */
    public function findById(int $id): ?array
    {
        $row = $this->db->query(
            'SELECT * FROM `news` WHERE `id` = ? LIMIT 1',
            [$id]
        )->fetch();

        return $row ?: null;
    }

    /**
     * Create a new news entry.
     *
     * @param string $title         Post title.
     * @param string $slug          URL slug.
     * @param string $bodyHtml      Body HTML content.
     * @param int    $authorId      Author's internal user id.
     * @param string $category      Category name.
     * @param string $categoryColor Category hex color.
     * @return int New news item ID.
     */
    public function create(string $title, string $slug, string $bodyHtml, int $authorId, string $category, string $categoryColor): int
    {
        $this->db->query(
            'INSERT INTO `news` (`title`, `slug`, `body_html`, `author_id`, `category`, `category_color`) VALUES (?, ?, ?, ?, ?, ?)',
            [$title, $slug, $bodyHtml, $authorId, $category, $categoryColor]
        );

        return (int)$this->db->getConnection()->lastInsertId();
    }

    /**
     * Update an existing news entry.
     *
     * @param int    $id            News DB id.
     * @param string $title         Post title.
     * @param string $slug          URL slug.
     * @param string $bodyHtml      Body HTML.
     * @param string $category      Category name.
     * @param string $categoryColor Category hex color.
     */
    public function update(int $id, string $title, string $slug, string $bodyHtml, string $category, string $categoryColor): void
    {
        $this->db->query(
            'UPDATE `news` SET `title` = ?, `slug` = ?, `body_html` = ?, `category` = ?, `category_color` = ? WHERE `id` = ?',
            [$title, $slug, $bodyHtml, $category, $categoryColor, $id]
        );
    }

    /**
     * Get distinct category names used so far.
     *
     * @return array<string>
     */
    public function getCategories(): array
    {
        return $this->db->query(
            'SELECT DISTINCT category FROM `news` ORDER BY category ASC'
        )->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Delete a news item.
     *
     * @param int $id News DB id.
     */
    public function delete(int $id): void
    {
        $this->db->query('DELETE FROM `news` WHERE `id` = ?', [$id]);
    }

    /**
     * Generate a unique URL slug from a title.
     *
     * @param string $title Post title.
     * @return string Unique URL-safe slug.
     */
    public function generateSlug(string $title): string
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        // Ensure uniqueness.
        $base    = $slug;
        $counter = 1;
        while ($this->slugExists($slug)) {
            $slug = $base . '-' . $counter++;
        }

        return $slug;
    }

    /**
     * Check whether a given slug already exists in the DB.
     *
     * @param string $slug Candidate slug.
     * @return bool
     */
    private function slugExists(string $slug): bool
    {
        return (bool)$this->db->query(
            'SELECT 1 FROM `news` WHERE `slug` = ? LIMIT 1',
            [$slug]
        )->fetchColumn();
    }
}
