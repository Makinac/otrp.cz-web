<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Model for the `content_pages` table.
 * Used for editable static pages such as Rules.
 */
class ContentPageModel extends BaseModel
{
    /**
     * Find a content page by its slug.
     *
     * @param string $slug URL slug (e.g. 'rules').
     * @return array<string,mixed>|null
     */
    public function findBySlug(string $slug): ?array
    {
        $row = $this->db->query(
            'SELECT * FROM `content_pages` WHERE `slug` = ? LIMIT 1',
            [$slug]
        )->fetch();

        return $row ?: null;
    }

    /**
     * Update content page body by slug.
     * Creates the page if it does not exist.
     *
     * @param string $slug      Page slug.
     * @param string $title     Page title.
     * @param string $bodyHtml  HTML content.
     * @param int    $updatedBy Editor's internal user id.
     */
    public function upsert(string $slug, string $title, string $bodyHtml, int $updatedBy): void
    {
        $this->db->query(
            'INSERT INTO `content_pages` (`slug`, `title`, `body_html`, `updated_by`)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               `title`      = VALUES(`title`),
               `body_html`  = VALUES(`body_html`),
               `updated_by` = VALUES(`updated_by`),
               `updated_at` = NOW()',
            [$slug, $title, $bodyHtml, $updatedBy]
        );
    }
}
