<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

/**
 * Discord Bot API client — role management and direct messages.
 */
class DiscordBot
{
    private const API     = 'https://discord.com/api/v10';
    private const TIMEOUT = 5;

    /**
     * Add a role to a guild member.
     */
    public static function addRole(string $discordId, string $roleId): bool
    {
        $guildId = env('DISCORD_GUILD_ID');
        $url     = self::API . "/guilds/{$guildId}/members/{$discordId}/roles/{$roleId}";

        return self::request('PUT', $url, null) !== false;
    }

    /**
     * Remove a role from a guild member.
     */
    public static function removeRole(string $discordId, string $roleId): bool
    {
        $guildId = env('DISCORD_GUILD_ID');
        $url     = self::API . "/guilds/{$guildId}/members/{$discordId}/roles/{$roleId}";

        return self::request('DELETE', $url, null) !== false;
    }

    /**
     * Send a direct message to a user.
     * First opens a DM channel, then sends the message.
     *
     * @param string $discordId User's Discord ID.
     * @param string $message   Message content (plain text, supports Discord markdown).
     * @return bool
     */
    public static function sendDM(string $discordId, string $message): bool
    {
        // 1. Open DM channel
        $channelData = self::request('POST', self::API . '/users/@me/channels', [
            'recipient_id' => $discordId,
        ]);

        if ($channelData === false || !isset($channelData['id'])) {
            Logger::error("DiscordBot: failed to open DM channel for {$discordId}");
            return false;
        }

        $channelId = $channelData['id'];

        // 2. Send message
        $result = self::request('POST', self::API . "/channels/{$channelId}/messages", [
            'content' => $message,
        ]);

        if ($result === false) {
            Logger::error("DiscordBot: failed to send DM to {$discordId}");
            return false;
        }

        return true;
    }

    /**
     * Send a message to a specific guild channel.
     */
    public static function sendChannelMessage(string $channelId, string $message): bool
    {
        $result = self::request('POST', self::API . "/channels/{$channelId}/messages", [
            'content' => $message,
        ]);

        if ($result === false) {
            Logger::error("DiscordBot: failed to send message to channel {$channelId}");
            return false;
        }

        return true;
    }

    /**
     * Replace placeholders in a message template.
     *
     * Supported placeholders:
     *   {username}   — Discord username
     *   {discord_id} — Discord user ID
     *   {app_url}    — Link to the application detail
     *   {tester}     — Name of the tester/staff who performed the action
     */
    public static function formatMessage(string $template, array $vars): string
    {
        $replacements = [
            '{username}'   => $vars['username'] ?? '',
            '{discord_id}' => $vars['discord_id'] ?? '',
            '{app_url}'    => $vars['app_url'] ?? '',
            '{tester}'     => $vars['tester'] ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Fetch the role IDs currently assigned to a guild member.
     * Returns an array of role ID strings, or false on API error.
     *
     * Cached in session for 5 minutes to avoid repeated Discord API calls
     * on every page request. Also cached statically per PHP process request
     * to deduplicate calls within the same request (e.g. QpService + CharService).
     *
     * @return list<string>|false
     */
    public static function getMemberRoles(string $discordId): array|false
    {
        // 1. Static per-request cache (free dedup within one page load)
        static $reqCache = [];
        if (array_key_exists($discordId, $reqCache)) {
            return $reqCache[$discordId];
        }

        // 2. Session TTL cache (5 min)
        $sessionKey = '_discord_roles_' . $discordId;
        $cached     = $_SESSION[$sessionKey] ?? null;
        if (
            is_array($cached)
            && isset($cached['expires'], $cached['roles'])
            && $cached['expires'] > time()
        ) {
            $reqCache[$discordId] = $cached['roles'];
            return $cached['roles'];
        }

        // 3. Live Discord API call
        $guildId = env('DISCORD_GUILD_ID');
        $url     = self::API . "/guilds/{$guildId}/members/{$discordId}";

        $result = self::request('GET', $url, null);
        if ($result === false) {
            $reqCache[$discordId] = false;
            return false;
        }

        // 404 / unknown member returns empty roles
        $roles = isset($result['roles'])
            ? array_values(array_map('strval', $result['roles']))
            : [];

        // Store in session (5 min TTL)
        $_SESSION[$sessionKey] = ['roles' => $roles, 'expires' => time() + 300];

        $reqCache[$discordId] = $roles;
        return $roles;
    }

    /**
     * Perform a Discord API request with Bot authentication.
     *
     * @return array|false Decoded JSON or false on error. For 204 No Content returns [].
     */
    private static function request(string $method, string $url, ?array $jsonBody): array|false
    {
        $botToken = env('DISCORD_BOT_TOKEN');
        if (empty($botToken)) {
            Logger::error('DiscordBot: DISCORD_BOT_TOKEN not configured');
            return false;
        }

        $headers = [
            "Authorization: Bot {$botToken}",
            'Content-Type: application/json',
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        if ($jsonBody !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsonBody));
        }

        $body     = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno    = curl_errno($ch);
        curl_close($ch);

        if ($errno || $body === false) {
            Logger::error("DiscordBot: {$method} {$url} curl error #{$errno}");
            return false;
        }

        // 204 No Content is success (e.g. role add/remove)
        if ($httpCode === 204) {
            return [];
        }

        if ($httpCode >= 400) {
            Logger::error("DiscordBot: {$method} {$url} HTTP {$httpCode}: {$body}");
            return false;
        }

        return json_decode((string)$body, true) ?? [];
    }

}
