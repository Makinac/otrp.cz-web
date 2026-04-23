<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Wrapper for the Discord bot's `config` key-value table.
 *
 * Uses a separate PDO connection to the bot's database (BOT_DB_* env vars),
 * because the bot and web application live in different MySQL databases.
 * The bot reads from this table on every operation, so changes are picked up
 * immediately without a bot restart.
 */
class DiscordBotConfigModel
{
    private PDO $pdo;

    public function __construct()
    {
        $host   = env('BOT_DB_HOST', 'localhost');
        $port   = env('BOT_DB_PORT', '3306');
        $dbName = env('BOT_DB_NAME', 'discord_bot');
        $user   = env('BOT_DB_USER', '');
        $pass   = env('BOT_DB_PASS', '');

        if ($user === '' || $dbName === '') {
            throw new RuntimeException('Bot database credentials (BOT_DB_*) are not configured in .env');
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";

        try {
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('Bot database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Get a single bot config value.
     */
    public function get(string $key, ?string $default = null): ?string
    {
        $stmt = $this->pdo->prepare('SELECT `value` FROM `config` WHERE `key` = ? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? (string)$row['value'] : $default;
    }

    /**
     * Set a single bot config value. Passing null or empty string removes the key.
     */
    public function set(string $key, ?string $value): void
    {
        if ($value === null || $value === '') {
            $stmt = $this->pdo->prepare('DELETE FROM `config` WHERE `key` = ?');
            $stmt->execute([$key]);
        } else {
            $stmt = $this->pdo->prepare('REPLACE INTO `config` (`key`, `value`) VALUES (?, ?)');
            $stmt->execute([$key, $value]);
        }
    }

    /**
     * Get multiple bot config values indexed by key name.
     *
     * @param  string[]             $keys
     * @return array<string, string|null>
     */
    public function getMultiple(array $keys): array
    {
        if (empty($keys)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $this->pdo->prepare("SELECT `key`, `value` FROM `config` WHERE `key` IN ($placeholders)");
        $stmt->execute($keys);
        $rows = $stmt->fetchAll();

        $result = array_fill_keys($keys, null);
        foreach ($rows as $row) {
            $result[$row['key']] = $row['value'];
        }

        return $result;
    }

    /**
     * Get a JSON-encoded config value and decode it to an array.
     * Returns $default if the key is missing or value is not valid JSON.
     *
     * @param  string  $key
     * @param  array   $default
     * @return array
     */
    public function getJson(string $key, array $default = []): array
    {
        $raw = $this->get($key);
        if ($raw !== null) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return $default;
    }

    /**
     * JSON-encode an array and store it as a config value.
     *
     * @param  string $key
     * @param  array  $value
     */
    public function setJson(string $key, array $value): void
    {
        $this->set($key, json_encode($value, JSON_UNESCAPED_UNICODE));
    }

    // ── Blacklisted links ────────────────────────────────────────────────────

    /** Return all banned domains ordered by date descending. */
    public function getBannedLinks(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM `blacklisted_links` ORDER BY `added_at` DESC');
        return $stmt->fetchAll();
    }

    /** Add a domain to the blacklist. Returns true on insert, false if duplicate. */
    public function addBannedLink(string $domain, string $addedBy): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO `blacklisted_links` (`domain`, `added_by`, `added_at`) VALUES (?, ?, NOW())'
        );
        $stmt->execute([$domain, $addedBy]);
        return $stmt->rowCount() > 0;
    }

    /** Remove a domain from the blacklist. */
    public function removeBannedLink(string $domain): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM `blacklisted_links` WHERE `domain` = ?');
        $stmt->execute([$domain]);
    }
}
