<?php

declare(strict_types=1);

namespace App\Models;

class SiteSettingModel extends BaseModel
{
    public function get(string $key, ?string $default = null): ?string
    {
        $row = $this->db->query(
            'SELECT `setting_value` FROM `site_settings` WHERE `setting_key` = ? LIMIT 1',
            [$key]
        )->fetch();

        return $row ? $row['setting_value'] : $default;
    }

    public function set(string $key, ?string $value): void
    {
        $this->db->query(
            'INSERT INTO `site_settings` (`setting_key`, `setting_value`)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)',
            [$key, $value]
        );
    }

    public function getMultiple(array $keys): array
    {
        if (empty($keys)) return [];

        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $rows = $this->db->query(
            "SELECT `setting_key`, `setting_value` FROM `site_settings` WHERE `setting_key` IN ({$placeholders})",
            $keys
        )->fetchAll();

        $result = [];
        foreach ($keys as $k) {
            $result[$k] = null;
        }
        foreach ($rows as $row) {
            $result[$row['setting_key']] = $row['setting_value'];
        }
        return $result;
    }
}
