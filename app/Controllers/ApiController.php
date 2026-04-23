<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Logger;
use App\Models\AllowlistModel;
use App\Models\BlacklistModel;
use App\Models\PlayerActionModel;
use App\Models\PlayerIdentifierModel;
use App\Models\SecurityLogModel;
use App\Models\UserModel;
use App\Models\ManagementPermissionModel;
use App\Models\RedeemCodeModel;
use App\Models\ApiKeyModel;
use App\Models\AdminSettingModel;
use App\Models\PedBonusModel;
use App\Services\QpService;

/**
 * REST API endpoint for allowlist status queries.
 * Authentication via X-API-Key header (DB-backed with IP restriction).
 */
class ApiController extends BaseController
{
    /**
     * Authenticate the API request using the X-API-Key header.
     * Validates the key exists in DB, is active, and the caller IP is allowed.
     * Sends a JSON error response and exits on failure.
     */
    private function authenticateApiKey(): void
    {
        // getallheaders() keys can vary in case depending on SAPI/proxy.
        $apiKey = '';
        foreach (getallheaders() as $name => $value) {
            if (strcasecmp($name, 'X-API-Key') === 0) {
                $apiKey = $value;
                break;
            }
        }
        // Fallback: Nginx converts headers to HTTP_X_API_KEY in $_SERVER.
        if ($apiKey === '') {
            $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        }

        // Determine caller IP: only trust proxy headers if behind a known proxy.
        // REMOTE_ADDR is always trustworthy; proxy headers can be spoofed.
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
        $callerIp   = $remoteAddr;

        // Detect Cloudflare via Cdn-Loop header (Cloudflare strips this from
        // incoming requests and re-adds it, so it cannot be forged by clients).
        $cdnLoop = $_SERVER['HTTP_CDN_LOOP'] ?? '';
        $isBehindCloudflare = str_contains($cdnLoop, 'cloudflare');

        if ($isBehindCloudflare && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $callerIp = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (env('TRUSTED_PROXY', '') !== '' && $remoteAddr === env('TRUSTED_PROXY', '')) {
            $callerIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $remoteAddr;
            if (str_contains($callerIp, ',')) {
                $callerIp = trim(explode(',', $callerIp)[0]);
            }
        }

        // Also support legacy env-based key for backward compatibility.
        $legacyKey = env('API_SECRET_KEY', '');
        if (!empty($legacyKey) && !empty($apiKey) && hash_equals($legacyKey, $apiKey)) {
            return; // Legacy key valid — no IP restriction.
        }

        $result = (new ApiKeyModel())->validate($apiKey, $callerIp);

        if (!$result['valid']) {
            $headerList = [];
            foreach (getallheaders() as $h => $v) {
                $headerList[] = "$h: " . (stripos($h, 'key') !== false || stripos($h, 'auth') !== false ? substr($v, 0, 12) . '***' : $v);
            }
            Logger::warning("API auth failed: {$result['error']} | IP: {$callerIp} | Key: " . substr($apiKey, 0, 8) . '*** | Headers: ' . implode('; ', $headerList));
            $this->json(['error' => $result['error'] ?? 'Unauthorized'], $result['error'] === 'IP not allowed' ? 403 : 401);
        }
    }

    /**
     * GET|POST /api/access/:server/:discord_id
     * Check if a player has access to a specific server.
     *
     * :server = main | dev | maps
     *
     * On POST, also accepts JSON body with identifiers to sync:
     * { "identifiers": { "license": "xxx", "steam": "yyy", ... } }
     *
     * @param array<string,string> $params Route parameters.
     */
    public function accessCheck(array $params = []): void
    {
        $this->authenticateApiKey();

        $server    = $params['server'] ?? '';
        $discordId = $params['discord_id'] ?? '';

        if (!in_array($server, ['main', 'dev', 'maps'], true)) {
            $this->json(['error' => 'Invalid server. Use: main, dev, maps'], 400);
        }

        if (empty($discordId)) {
            $this->json(['error' => 'Missing discord_id'], 400);
        }

        // On POST, parse and sync identifiers
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $body = json_decode(file_get_contents('php://input') ?: '{}', true);
            $identifiers = [];
            if (is_array($body['identifiers'] ?? null)) {
                $identifiers = $body['identifiers'];
            }
        }

        $userModel = new UserModel();
        $user      = $userModel->findByDiscordId($discordId);

        if (!$user) {
            $this->json([
                'discord_id' => $discordId,
                'server'     => $server,
                'allowed'    => false,
                'reason'     => 'not_found',
                'qp'         => 0,
                'chars'      => 0,
                'ped'        => false,
            ]);
        }

        $userId = (int)$user['id'];
        $qp     = QpService::calculate($discordId, $userId);
        $chars  = \App\Services\CharService::calculate($discordId, $userId);
        $ped    = (new PedBonusModel())->hasAccess($userId);

        // Sync identifiers on POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($identifiers)) {
            $this->processIdentifiers($userId, $identifiers);
        }

        if ($server === 'dev') {
            $this->json([
                'discord_id' => $discordId,
                'server'     => $server,
                'allowed'    => (bool)$user['access_dev'],
                'qp'         => $qp,
                'chars'      => $chars,
                'ped'        => $ped,
            ]);
        }

        if ($server === 'maps') {
            $this->json([
                'discord_id' => $discordId,
                'server'     => $server,
                'allowed'    => (bool)$user['access_maps'],
                'qp'         => $qp,
                'chars'      => $chars,
                'ped'        => $ped,
            ]);
        }

        // server = main
        $blModel = new BlacklistModel();
        if ($blModel->findByDiscordId($discordId)) {
            $this->json([
                'discord_id' => $discordId,
                'server'     => $server,
                'allowed'    => false,
                'reason'     => 'blacklisted',
                'qp'         => $qp,
                'chars'      => $chars,
                'ped'        => $ped,
            ]);
        }

        $actionModel = new PlayerActionModel();
        $activeBan   = $actionModel->getActiveBan($userId);
        if ($activeBan) {
            $this->json([
                'discord_id' => $discordId,
                'server'     => $server,
                'allowed'    => false,
                'reason'     => 'banned',
                'expires_at' => $activeBan['expires_at'],
                'qp'         => $qp,
                'chars'      => $chars,
                'ped'        => $ped,
            ]);
        }

        $alModel   = new AllowlistModel();
        $latestApp = $alModel->findLatestByUserId($userId);

        $hasAllowlist = $latestApp
            && $latestApp['status'] === 'approved'
            && ($latestApp['interview_status'] ?? null) === 'passed';

        $this->json([
            'discord_id' => $discordId,
            'server'     => $server,
            'allowed'    => $hasAllowlist,
            'reason'     => $hasAllowlist ? null : 'no_allowlist',
            'qp'         => $qp,
            'chars'      => $chars,
            'ped'        => $ped,
        ]);
    }

    /**
     * GET /api/benefits/:discord_id
     * Returns all benefit values (QP, chars, ped) for a Discord user.
     *
     * @param array<string,string> $params Route parameters — expects :discord_id.
     */
    public function benefits(array $params = []): void
    {
        $this->authenticateApiKey();

        $discordId = $params['discord_id'] ?? '';

        if (empty($discordId)) {
            $this->json(['error' => 'Missing discord_id'], 400);
        }

        $userModel = new UserModel();
        $user      = $userModel->findByDiscordId($discordId);

        if (!$user) {
            $this->json([
                'discord_id' => $discordId,
                'qp'         => 0,
                'chars'      => 0,
                'ped'        => false,
            ]);
        }

        $userId = (int)$user['id'];

        $this->json([
            'discord_id' => $discordId,
            'qp'         => QpService::calculate($discordId, $userId),
            'chars'      => \App\Services\CharService::calculate($discordId, $userId),
            'ped'        => (new PedBonusModel())->hasAccess($userId),
        ]);
    }

    /**
     * GET /api/permissions/:discord_id
     * Returns admin and management permission flags for a Discord user.
     *
     * @param array<string,string> $params Route parameters — expects :discord_id.
     */
    public function permissions(array $params = []): void
    {
        $this->authenticateApiKey();

        $discordId = $params['discord_id'] ?? '';

        if (empty($discordId)) {
            $this->json(['error' => 'Missing discord_id'], 400);
        }

        $userModel = new UserModel();
        $user      = $userModel->findByDiscordId($discordId);

        if (!$user) {
            $this->json([
                'discord_id'             => $discordId,
                'admin_permissions'      => [],
                'management_permissions' => [],
                'rsg_permissions'        => [],
                'lib_permissions'        => [],
                'admin_settings'         => [
                    'admin_prefix_chat'    => true,
                    'report_notifications' => true,
                ],
            ]);
        }

        $userId    = (int)$user['id'];
        $roleNames = $userModel->getRoleNames($user);
        $roleIds   = $userModel->getRoleIds($user);

        // Vedení has full access to everything.
        $isVedeni = !empty(array_intersect(\App\Auth\Permission::VEDENI_ROLE_IDS, $roleIds));

        // All admin permission keys.
        $adminKeys = [
            'admin.allowlist',
            'admin.allowlist.reinterview',
            'admin.players',
            'admin.players.punishments',
            'admin.players.access',
            'admin.players.appeals',
            'admin.ck',
            'admin.activity',
            'admin.vacation',
            'admin.security',
            'admin.qp_bonus',
            'admin.char_bonus',
        ];

        // All management permission keys.
        $managementKeys = [
            'management.form',
            'management.content',
            'management.rules',
            'management.blacklist',
            'management.appeals',
            'management.team',
            'management.cheatsheet',
            'management.partners',
            'management.homepage',
            'management.allowlist_stats',
            'management.qp',
            'management.chars',
            'management.codes',
            'management.api_keys',
            'management.settings',
        ];

        // RSG (in-game) permission keys.
        $rsgKeys = [
            'ingame.admin',
            'ingame.management',
        ];

        // LIB permission keys grouped by subcategory.
        $libDevKeys   = ['lib.jobscreator', 'lib.blipscreator', 'lib.shopscreator'];
        $libAdminKeys = [];

        $adminPerms      = array_fill_keys($adminKeys, false);
        $managementPerms = array_fill_keys($managementKeys, false);
        $rsgPerms        = array_fill_keys($rsgKeys, false);
        $libDevPerms     = array_fill_keys($libDevKeys, false);
        $libAdminPerms   = array_fill_keys($libAdminKeys, false);

        if ($isVedeni) {
            $adminPerms      = array_fill_keys($adminKeys, true);
            $managementPerms = array_fill_keys($managementKeys, true);
            $rsgPerms        = array_fill_keys($rsgKeys, true);
            $libDevPerms     = array_fill_keys($libDevKeys, true);
            $libAdminPerms   = array_fill_keys($libAdminKeys, true);
        } else {
            $granted = (new ManagementPermissionModel())->getPermissionKeysForUser($userId, $roleNames, $roleIds);
            foreach ($granted as $key) {
                if (array_key_exists($key, $adminPerms))      { $adminPerms[$key]      = true; }
                if (array_key_exists($key, $managementPerms)) { $managementPerms[$key] = true; }
                if (array_key_exists($key, $rsgPerms))        { $rsgPerms[$key]        = true; }
                if (array_key_exists($key, $libDevPerms))     { $libDevPerms[$key]     = true; }
                if (array_key_exists($key, $libAdminPerms))   { $libAdminPerms[$key]   = true; }
            }
        }

        $adminSettings = (new AdminSettingModel())->getForUser($userId);

        $this->json([
            'discord_id'             => $discordId,
            'admin_permissions'      => $adminPerms,
            'management_permissions' => $managementPerms,
            'rsg_permissions'        => $rsgPerms,
            'lib_permissions'        => [
                'dev'   => $libDevPerms,
                'admin' => $libAdminPerms,
            ],
            'admin_settings'         => $adminSettings,
        ]);
    }

    /**
     * Process FiveM identifiers: store new ones, detect changes & multi-accounts.
     *
     * @param int                    $userId
     * @param array<string,string>   $identifiers
     */
    private function processIdentifiers(int $userId, array $identifiers): void
    {
        $idModel  = new PlayerIdentifierModel();
        $secModel = new SecurityLogModel();

        try {
            $result = $idModel->syncIdentifiers($userId, $identifiers);

            foreach ($result['new'] as $type => $value) {
                // Check if user already had a different value for this type
                $previous = $idModel->getPreviousForType($userId, $type, $value);

                if (!empty($previous)) {
                    // Identifier changed — could be VPN, new hardware, etc.
                    $oldValues = array_column($previous, 'identifier_value');
                    $severity  = in_array($type, ['license', 'license2', 'steam', 'fivem'], true) ? 'warning' : 'info';

                    $secModel->create(
                        $userId,
                        'new_identifier',
                        $severity,
                        "Nový {$type} identifikátor: {$value} (předchozí: " . implode(', ', $oldValues) . ')',
                        [
                            'type'       => $type,
                            'new_value'  => $value,
                            'old_values' => $oldValues,
                        ]
                    );
                }

                // Check if another user has the same identifier (multi-account)
                $others = $idModel->findOtherUsersWithIdentifier($userId, $type, $value);
                if (!empty($others)) {
                    $otherNames = array_map(
                        fn(array $o): string => $o['username'] . ' (' . $o['discord_id'] . ')',
                        $others
                    );

                    $secModel->create(
                        $userId,
                        'multi_account',
                        'critical',
                        "Sdílený {$type} identifikátor s: " . implode(', ', $otherNames),
                        [
                            'type'        => $type,
                            'value'       => $value,
                            'other_users' => array_map(fn(array $o): array => [
                                'user_id'    => (int)$o['user_id'],
                                'username'   => $o['username'],
                                'discord_id' => $o['discord_id'],
                            ], $others),
                        ]
                    );

                    // Also log on the other users' profiles
                    $userModel = new UserModel();
                    $thisUser  = $userModel->findById($userId);
                    $thisName  = $thisUser ? $thisUser['username'] : "user#{$userId}";
                    $thisDiscord = $thisUser ? $thisUser['discord_id'] : '';

                    foreach ($others as $other) {
                        $secModel->create(
                            (int)$other['user_id'],
                            'multi_account',
                            'critical',
                            "Sdílený {$type} identifikátor s: {$thisName} ({$thisDiscord})",
                            [
                                'type'        => $type,
                                'value'       => $value,
                                'other_users' => [[
                                    'user_id'    => $userId,
                                    'username'   => $thisName,
                                    'discord_id' => $thisDiscord,
                                ]],
                            ]
                        );
                    }
                }
            }
        } catch (\Throwable $e) {
            Logger::error("Identifier sync failed for user #{$userId}: " . $e->getMessage());
        }
    }

    /**
     * POST /api/codes
     * Generate a redemption code (for Tebex or other external integrations).
     *
     * Authentication: X-API-Key header.
     *
     * Request body (JSON):
     * {
     *   "type":       "qp"|"chars"|"ped",
     *   "amount":     500,
     *   "max_uses":   1,
     *   "note":       "Tebex order #123",
     *   "expires_at": null | "2025-12-31 23:59:59"
     * }
     *
     * Response:
     * { "code": "XXXX-XXXX-XXXX" }
     *
     * @param array<string,string> $params
     */
    public function createCode(array $params = []): void
    {
        $this->authenticateApiKey();

        $raw  = (string)file_get_contents('php://input');
        $body = json_decode($raw, true);

        if (!is_array($body)) {
            $this->json(['error' => 'Invalid JSON body'], 400);
        }

        $type      = (string)($body['type'] ?? '');
        $amount    = (int)($body['amount'] ?? 0);
        $maxUses   = max(1, (int)($body['max_uses'] ?? 1));
        $note      = substr(trim((string)($body['note'] ?? '')), 0, 255);
        $expiresAt = null;

        if (!in_array($type, ['qp', 'chars', 'ped'], true)) {
            $this->json(['error' => 'type must be "qp", "chars" or "ped"'], 400);
        }

        if ($amount <= 0) {
            $this->json(['error' => 'amount must be a positive integer'], 400);
        }

        $rawExpiry = $body['expires_at'] ?? null;
        if ($rawExpiry !== null && $rawExpiry !== '') {
            try {
                $expiresAt = (new \DateTimeImmutable((string)$rawExpiry))->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                $this->json(['error' => 'Invalid expires_at format'], 400);
            }
        }

        try {
            $code = (new RedeemCodeModel())->generate($type, $amount, $maxUses, $expiresAt, $note, null);
            $this->json(['code' => $code], 201);
        } catch (\Throwable $e) {
            Logger::error('API createCode: ' . $e->getMessage());
            $this->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * PUT /api/settings/:discord_id
     * Update admin settings for a user.
     *
     * Request body (JSON):
     * {
     *   "admin_prefix_chat":    true|false,
     *   "report_notifications": true|false
     * }
     *
     * @param array<string,string> $params Route parameters — expects :discord_id.
     */
    public function updateSettings(array $params = []): void
    {
        $this->authenticateApiKey();

        $discordId = $params['discord_id'] ?? '';

        if (empty($discordId)) {
            $this->json(['error' => 'Missing discord_id'], 400);
        }

        $raw  = (string)file_get_contents('php://input');
        $body = json_decode($raw, true);

        if (!is_array($body)) {
            $this->json(['error' => 'Invalid JSON body'], 400);
        }

        $userModel = new UserModel();
        $user      = $userModel->findByDiscordId($discordId);

        if (!$user) {
            $this->json(['error' => 'User not found'], 404);
        }

        $userId  = (int)$user['id'];
        $model   = new AdminSettingModel();
        $current = $model->getForUser($userId);

        $adminPrefixChat    = array_key_exists('admin_prefix_chat', $body)
            ? (bool)$body['admin_prefix_chat']
            : $current['admin_prefix_chat'];
        $reportNotifications = array_key_exists('report_notifications', $body)
            ? (bool)$body['report_notifications']
            : $current['report_notifications'];

        try {
            $model->save($userId, $adminPrefixChat, $reportNotifications);
            $this->json([
                'discord_id'           => $discordId,
                'admin_prefix_chat'    => $adminPrefixChat,
                'report_notifications' => $reportNotifications,
            ]);
        } catch (\Throwable $e) {
            Logger::error('API updateSettings: ' . $e->getMessage());
            $this->json(['error' => 'Internal server error'], 500);
        }
    }
}
