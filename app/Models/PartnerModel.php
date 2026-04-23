<?php

declare(strict_types=1);

namespace App\Models;

class PartnerModel extends BaseModel
{
    public function getActive(): array
    {
        return $this->db->query(
            'SELECT * FROM `partners` WHERE `active` = 1 ORDER BY `sort_order` ASC, `id` ASC'
        )->fetchAll();
    }

    public function getAll(): array
    {
        return $this->db->query(
            'SELECT * FROM `partners` ORDER BY `sort_order` ASC, `id` ASC'
        )->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $row = $this->db->query(
            'SELECT * FROM `partners` WHERE `id` = ?',
            [$id]
        )->fetch();

        return $row ?: null;
    }

    public function create(string $name, ?string $logoUrl, ?string $description, ?string $url, int $sortOrder, bool $active): void
    {
        $this->db->query(
            'INSERT INTO `partners` (`name`, `logo_url`, `description`, `url`, `sort_order`, `active`) VALUES (?, ?, ?, ?, ?, ?)',
            [$name, $logoUrl, $description, $url, $sortOrder, $active ? 1 : 0]
        );
    }

    public function update(int $id, string $name, ?string $logoUrl, ?string $description, ?string $url, int $sortOrder, bool $active): void
    {
        $this->db->query(
            'UPDATE `partners` SET `name` = ?, `logo_url` = ?, `description` = ?, `url` = ?, `sort_order` = ?, `active` = ? WHERE `id` = ?',
            [$name, $logoUrl, $description, $url, $sortOrder, $active ? 1 : 0, $id]
        );
    }

    public function delete(int $id): void
    {
        $this->db->query('DELETE FROM `partners` WHERE `id` = ?', [$id]);
    }

    public function moveUp(int $id): void
    {
        $current = $this->findById($id);
        if (!$current) return;

        $prev = $this->db->query(
            'SELECT * FROM `partners` WHERE `sort_order` < ? ORDER BY `sort_order` DESC LIMIT 1',
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
            'SELECT * FROM `partners` WHERE `sort_order` > ? ORDER BY `sort_order` ASC LIMIT 1',
            [$current['sort_order']]
        )->fetch();

        if ($next) {
            $this->swapOrder((int)$current['id'], (int)$current['sort_order'], (int)$next['id'], (int)$next['sort_order']);
        }
    }

    private function swapOrder(int $idA, int $orderA, int $idB, int $orderB): void
    {
        $this->db->query('UPDATE `partners` SET `sort_order` = ? WHERE `id` = ?', [$orderB, $idA]);
        $this->db->query('UPDATE `partners` SET `sort_order` = ? WHERE `id` = ?', [$orderA, $idB]);
    }

    /**
     * Fetch a site logo (og:image, apple-touch-icon, or favicon) from a URL.
     */
    public static function fetchLogoFromUrl(string $siteUrl): ?string
    {
        $ctx = stream_context_create([
            'http' => [
                'timeout'         => 5,
                'follow_location' => 1,
                'max_redirects'   => 3,
                'user_agent'      => 'Mozilla/5.0 (compatible; OldTimesRP/1.0)',
                'ignore_errors'   => true,
            ],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);

        $html = @file_get_contents($siteUrl, false, $ctx);
        if ($html === false) {
            return null;
        }

        $parsed = parse_url($siteUrl);
        $base   = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');

        // 1) og:image
        if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
            return self::resolveUrl($m[1], $base);
        }
        // alternate order: content before property
        if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/i', $html, $m)) {
            return self::resolveUrl($m[1], $base);
        }

        // 2) apple-touch-icon (high quality square icon)
        if (preg_match('/<link[^>]+rel=["\']apple-touch-icon[^"\']*["\'][^>]+href=["\']([^"\']+)["\']/i', $html, $m)) {
            return self::resolveUrl($m[1], $base);
        }

        // 3) <link rel="icon"> with sizes (prefer largest)
        if (preg_match_all('/<link[^>]+rel=["\'](?:shortcut )?icon["\'][^>]+href=["\']([^"\']+)["\']/i', $html, $all)) {
            return self::resolveUrl(end($all[1]), $base);
        }

        // 4) fallback: /favicon.ico
        $fallback = $base . '/favicon.ico';
        $headers  = @get_headers($fallback, true, $ctx);
        if ($headers && str_contains($headers[0] ?? '', '200')) {
            return $fallback;
        }

        return null;
    }

    private static function resolveUrl(string $href, string $base): string
    {
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }
        if (str_starts_with($href, '//')) {
            return 'https:' . $href;
        }
        return rtrim($base, '/') . '/' . ltrim($href, '/');
    }
}
