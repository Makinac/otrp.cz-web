<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Logger;

/**
 * Discord OAuth2 client.
 * Handles the authorization code flow and Guild member API calls.
 */
class DiscordOAuth
{
    private const DISCORD_API   = 'https://discord.com/api/v10';
    private const TIMEOUT       = 5;

    /**
     * Build the Discord OAuth2 authorisation URL.
     *
     * @return string Full redirect URL.
     */
    public static function getAuthUrl(): string
    {
        $params = http_build_query([
            'client_id'     => env('DISCORD_CLIENT_ID'),
            'redirect_uri'  => env('DISCORD_REDIRECT_URI'),
            'response_type' => 'code',
            'scope'         => 'identify guilds guilds.members.read',
        ]);

        return self::DISCORD_API . '/oauth2/authorize?' . $params;
    }

    /**
     * Exchange an authorisation code for an access token.
     *
     * @param string $code The code received from Discord's callback.
     * @return array<string,mixed>|null Token data, or null on failure.
     */
    public static function exchangeCode(string $code): ?array
    {
        $data = [
            'client_id'     => env('DISCORD_CLIENT_ID'),
            'client_secret' => env('DISCORD_CLIENT_SECRET'),
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => env('DISCORD_REDIRECT_URI'),
        ];

        $response = self::post(self::DISCORD_API . '/oauth2/token', $data);

        if ($response === null || isset($response['error'])) {
            Logger::error('Discord OAuth token exchange failed: ' . json_encode($response));
            return null;
        }

        return $response;
    }

    /**
     * Fetch the authenticated user's profile from Discord.
     *
     * @param string $accessToken OAuth2 access token.
     * @return array<string,mixed>|null User data, or null on failure.
     */
    public static function getUser(string $accessToken): ?array
    {
        $response = self::get(self::DISCORD_API . '/users/@me', $accessToken);

        if ($response === null || !isset($response['id'])) {
            Logger::error('Discord getUser failed: ' . json_encode($response));
            return null;
        }

        return $response;
    }

    /**
     * Fetch the user's Guild member data (roles, nickname, etc.) via bot token.
     *
     * @param string $discordId Discord snowflake user ID.
     * @return array<string,mixed>|null Member data, or null on failure.
     */
    public static function getGuildMember(string $discordId): ?array
    {
        $guildId = env('DISCORD_GUILD_ID');
        $url     = self::DISCORD_API . "/guilds/{$guildId}/members/{$discordId}";

        $response = self::getBotAuth($url);

        if ($response === null || isset($response['code'])) {
            Logger::error("Discord getGuildMember failed for {$discordId}: " . json_encode($response));
            return null;
        }

        return $response;
    }

    /**
     * Fetch all Guild members that have specified roles — used for team cache.
     * Uses the LIST members endpoint with limit and pagination.
     *
     * @param array<string> $roleNames Discord role names to filter by.
     * @return array<int, array<string,mixed>> Filtered member list.
     */
    public static function getGuildMembersWithRoles(array $roleNames): array
    {
        $guildId = env('DISCORD_GUILD_ID');
        // First, fetch all guild roles to build name→id mapping.
        $rolesUrl    = self::DISCORD_API . "/guilds/{$guildId}/roles";
        $guildRoles  = self::getBotAuth($rolesUrl);

        if (!is_array($guildRoles)) {
            Logger::error('Discord: failed to fetch guild roles.');
            return [];
        }

        // Build name→id map.
        $roleIdMap = [];
        foreach ($guildRoles as $role) {
            if (isset($role['name'], $role['id'])) {
                $roleIdMap[$role['name']] = $role['id'];
            }
        }

        $targetIds = [];
        foreach ($roleNames as $name) {
            if (isset($roleIdMap[$name])) {
                $targetIds[] = $roleIdMap[$name];
            }
        }

        if (empty($targetIds)) {
            return [];
        }

        // Paginate through members.
        $result = [];
        $after  = 0;
        do {
            $url = self::DISCORD_API . "/guilds/{$guildId}/members?limit=1000&after={$after}";
            $members = self::getBotAuth($url);

            if (!is_array($members) || empty($members)) {
                break;
            }

            foreach ($members as $member) {
                $memberRoles = $member['roles'] ?? [];
                if (array_intersect($targetIds, $memberRoles)) {
                    // Resolve role names for storage.
                    $memberRoleNames = [];
                    $roleNameById    = array_flip($roleIdMap);
                    foreach ($memberRoles as $rid) {
                        if (isset($roleNameById[$rid])) {
                            $memberRoleNames[] = $roleNameById[$rid];
                        }
                    }
                    $member['_role_names'] = $memberRoleNames;
                    $result[] = $member;
                }
            }

            $lastMember = end($members);
            $after      = $lastMember['user']['id'] ?? 0;
        } while (count($members) === 1000);

        return $result;
    }

    /**
     * Resolve a list of role IDs to role names for a given guild.
     *
     * @param array<string> $roleIds  Discord role snowflake IDs.
     * @return array<string>          Role names.
     */
    public static function resolveRoleNames(array $roleIds): array
    {
        if (empty($roleIds)) {
            return [];
        }

        $guildId   = env('DISCORD_GUILD_ID');
        $url       = self::DISCORD_API . "/guilds/{$guildId}/roles";
        $guildRoles = self::getBotAuth($url);

        if (!is_array($guildRoles)) {
            return [];
        }

        $nameMap = [];
        foreach ($guildRoles as $role) {
            $nameMap[$role['id']] = $role['name'];
        }

        $names = [];
        foreach ($roleIds as $id) {
            if (isset($nameMap[$id])) {
                $names[] = $nameMap[$id];
            }
        }

        return $names;
    }

    /**
     * Fetch all guild roles (id, name, color).
     *
     * Cached statically per request and in session for 30 minutes —
     * guild roles change rarely and are fetched on every page load for
     * QP/Char breakdown display.
     *
     * @return array<int, array{id: string, name: string, color: string}>
     */
    public static function getGuildRoles(): array
    {
        // 1. Static per-request cache
        static $reqCache = null;
        if ($reqCache !== null) {
            return $reqCache;
        }

        // 2. Session TTL cache (30 min)
        $sessionKey = '_discord_guild_roles';
        $cached     = $_SESSION[$sessionKey] ?? null;
        if (
            is_array($cached)
            && isset($cached['expires'], $cached['roles'])
            && $cached['expires'] > time()
        ) {
            $reqCache = $cached['roles'];
            return $reqCache;
        }

        // 3. Live Discord API call
        $guildId    = env('DISCORD_GUILD_ID');
        $url        = self::DISCORD_API . "/guilds/{$guildId}/roles";
        $guildRoles = self::getBotAuth($url);

        if (!is_array($guildRoles)) {
            $reqCache = [];
            return $reqCache;
        }

        $result = [];
        foreach ($guildRoles as $role) {
            if (($role['name'] ?? '') === '@everyone') {
                continue;
            }
            $colorInt = (int)($role['color'] ?? 0);
            $result[] = [
                'id'       => (string)$role['id'],
                'name'     => (string)$role['name'],
                'color'    => $colorInt > 0 ? '#' . str_pad(dechex($colorInt), 6, '0', STR_PAD_LEFT) : '',
                'position' => (int)($role['position'] ?? 0),
            ];
        }

        usort($result, fn($a, $b) => $b['position'] <=> $a['position']);

        // Store in session (30 min TTL)
        $_SESSION[$sessionKey] = ['roles' => $result, 'expires' => time() + 1800];

        $reqCache = $result;
        return $reqCache;
    }

    /**
     * Fetch all text channels from the configured guild.
     *
     * @return array<int, array{id: string, name: string}>
     */
    public static function getGuildChannels(): array
    {
        $guildId  = env('DISCORD_GUILD_ID');
        $url      = self::DISCORD_API . "/guilds/{$guildId}/channels";
        $channels = self::getBotAuth($url);

        if (!is_array($channels)) {
            return [];
        }

        $result = [];
        foreach ($channels as $ch) {
            // type 0 = text channel
            if (((int)($ch['type'] ?? -1)) !== 0) {
                continue;
            }
            $result[] = [
                'id'       => (string)$ch['id'],
                'name'     => (string)($ch['name'] ?? ''),
                'position' => (int)($ch['position'] ?? 0),
            ];
        }

        usort($result, fn($a, $b) => $a['position'] <=> $b['position']);

        return $result;
    }

    /**
     * Return voice channels (type 2) from the guild, sorted by position.
     *
     * @return array<int, array{id: string, name: string, position: int}>
     */
    public static function getGuildVoiceChannels(): array
    {
        $guildId  = env('DISCORD_GUILD_ID');
        $url      = self::DISCORD_API . "/guilds/{$guildId}/channels";
        $channels = self::getBotAuth($url);

        if (!is_array($channels)) {
            return [];
        }

        $result = [];
        foreach ($channels as $ch) {
            // type 2 = voice channel
            if (((int)($ch['type'] ?? -1)) !== 2) {
                continue;
            }
            $result[] = [
                'id'       => (string)$ch['id'],
                'name'     => (string)($ch['name'] ?? ''),
                'position' => (int)($ch['position'] ?? 0),
            ];
        }

        usort($result, fn($a, $b) => $a['position'] <=> $b['position']);

        return $result;
    }

    /**
     * Return Discord category channels (type 4) from the guild, sorted by position.
     * These are the parent containers used for grouping ticket channels.
     *
     * @return array<int, array{id: string, name: string, position: int}>
     */
    public static function getGuildCategories(): array
    {
        $guildId  = env('DISCORD_GUILD_ID');
        $url      = self::DISCORD_API . "/guilds/{$guildId}/channels";
        $channels = self::getBotAuth($url);

        if (!is_array($channels)) {
            return [];
        }

        $result = [];
        foreach ($channels as $ch) {
            // type 4 = category channel
            if (((int)($ch['type'] ?? -1)) !== 4) {
                continue;
            }
            $result[] = [
                'id'       => (string)$ch['id'],
                'name'     => (string)($ch['name'] ?? ''),
                'position' => (int)($ch['position'] ?? 0),
            ];
        }

        usort($result, fn($a, $b) => $a['position'] <=> $b['position']);

        return $result;
    }

    /**
     * Fetch guild members that have at least one of the specified role IDs.
     *
     * @param array<string> $roleIds Discord role snowflake IDs.
     * @return array<int, array<string,mixed>> Member list with resolved _role_names.
     */
    public static function getGuildMembersWithRoleIds(array $roleIds): array
    {
        if (empty($roleIds)) {
            return [];
        }

        $guildId = env('DISCORD_GUILD_ID');

        // Fetch role name mapping for reference.
        $rolesUrl   = self::DISCORD_API . "/guilds/{$guildId}/roles";
        $guildRoles = self::getBotAuth($rolesUrl);
        $roleNameById = [];
        if (is_array($guildRoles)) {
            foreach ($guildRoles as $r) {
                $roleNameById[(string)$r['id']] = (string)$r['name'];
            }
        }

        $result = [];
        $after  = 0;
        do {
            $url     = self::DISCORD_API . "/guilds/{$guildId}/members?limit=1000&after={$after}";
            $members = self::getBotAuth($url);

            if (!is_array($members) || empty($members)) {
                break;
            }

            foreach ($members as $member) {
                $memberRoles = array_map('strval', $member['roles'] ?? []);
                if (array_intersect($roleIds, $memberRoles)) {
                    $memberRoleNames = [];
                    foreach ($memberRoles as $rid) {
                        if (isset($roleNameById[$rid])) {
                            $memberRoleNames[] = $roleNameById[$rid];
                        }
                    }
                    $member['_role_names'] = $memberRoleNames;
                    $result[] = $member;
                }
            }

            $lastMember = end($members);
            $after      = $lastMember['user']['id'] ?? 0;
        } while (count($members) === 1000);

        return $result;
    }

    // -------------------------------------------------------------------------
    // Internal HTTP helpers
    // -------------------------------------------------------------------------

    /**
     * Perform a POST request to Discord (application/x-www-form-urlencoded).
     *
     * @param string               $url  Target URL.
     * @param array<string,string> $data POST fields.
     * @return array<string,mixed>|null Decoded JSON response or null.
     */
    private static function post(string $url, array $data): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);

        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno || $body === false) {
            Logger::error("Discord POST {$url} curl error #{$errno}");
            return null;
        }

        return json_decode((string)$body, true);
    }

    /**
     * Perform a GET request authenticated with a Bearer (user) access token.
     *
     * @param string $url         Target URL.
     * @param string $accessToken OAuth2 access token.
     * @return array<string,mixed>|null Decoded JSON response or null.
     */
    private static function get(string $url, string $accessToken): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$accessToken}"],
        ]);

        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno || $body === false) {
            Logger::error("Discord GET {$url} curl error #{$errno}");
            return null;
        }

        return json_decode((string)$body, true);
    }

    /**
     * Perform a GET request authenticated with the Bot token.
     *
     * @param string $url Target URL.
     * @return array<string,mixed>|null Decoded JSON response or null.
     */
    private static function getBotAuth(string $url): ?array
    {
        $botToken = env('DISCORD_BOT_TOKEN');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_HTTPHEADER     => ["Authorization: Bot {$botToken}"],
        ]);

        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno || $body === false) {
            Logger::error("Discord BotGET {$url} curl error #{$errno}");
            return null;
        }

        return json_decode((string)$body, true);
    }

    /**
     * PATCH request with JSON body using bot token.
     *
     * @param  string $url
     * @param  array  $payload  PHP array, JSON-encoded before sending.
     * @return array|null       Decoded response, or null on error.
     */
    private static function patchBotAuth(string $url, array $payload): ?array
    {
        $botToken = env('DISCORD_BOT_TOKEN');
        $body     = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CUSTOMREQUEST  => 'PATCH',
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bot {$botToken}",
                'Content-Type: application/json',
            ],
        ]);

        $resp  = curl_exec($ch);
        $errno = curl_errno($ch);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno || $resp === false) {
            Logger::error("Discord BotPATCH {$url} curl error #{$errno}");
            return null;
        }

        if ($code >= 400) {
            Logger::error("Discord BotPATCH {$url} HTTP {$code}: {$resp}");
            return null;
        }

        return json_decode((string)$resp, true);
    }

    /**
     * Edit an existing Discord message (PATCH /channels/{id}/messages/{id}).
     * Used to refresh the ticket panel embed after config changes.
     *
     * @param  string $channelId
     * @param  string $messageId
     * @param  array  $payload   Discord message payload (embeds, components, …)
     * @return bool
     */
    public static function editGuildMessage(string $channelId, string $messageId, array $payload): bool
    {
        $url    = self::DISCORD_API . "/channels/{$channelId}/messages/{$messageId}";
        $result = self::patchBotAuth($url, $payload);
        return $result !== null;
    }

    /**
     * Add a role to a guild member.
     *
     * @param  string $discordId  User's Discord snowflake ID.
     * @param  string $roleId     Role snowflake ID.
     * @return bool
     */
    public static function addRoleToMember(string $discordId, string $roleId): bool
    {
        $guildId  = env('DISCORD_GUILD_ID');
        $botToken = env('DISCORD_BOT_TOKEN');
        $url      = self::DISCORD_API . "/guilds/{$guildId}/members/{$discordId}/roles/{$roleId}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => '',
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bot {$botToken}",
                'Content-Length: 0',
            ],
        ]);
        $resp  = curl_exec($ch);
        $errno = curl_errno($ch);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno || ($code >= 400 && $code !== 204)) {
            Logger::error("addRoleToMember {$discordId}/{$roleId} HTTP {$code}");
            return false;
        }
        return true;
    }

    /**
     * Remove a role from a guild member.
     *
     * @param  string $discordId  User's Discord snowflake ID.
     * @param  string $roleId     Role snowflake ID.
     * @return bool
     */
    public static function removeRoleFromMember(string $discordId, string $roleId): bool
    {
        $guildId  = env('DISCORD_GUILD_ID');
        $botToken = env('DISCORD_BOT_TOKEN');
        $url      = self::DISCORD_API . "/guilds/{$guildId}/members/{$discordId}/roles/{$roleId}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_HTTPHEADER     => ["Authorization: Bot {$botToken}"],
        ]);
        $resp  = curl_exec($ch);
        $errno = curl_errno($ch);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno || ($code >= 400 && $code !== 204)) {
            Logger::error("removeRoleFromMember {$discordId}/{$roleId} HTTP {$code}");
            return false;
        }
        return true;
    }
}
