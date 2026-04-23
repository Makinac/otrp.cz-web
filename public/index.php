<?php

declare(strict_types=1);

// ── Bootstrap ─────────────────────────────────────────────────────────────
define('APP_ROOT', dirname(__DIR__));

// Autoloader (PSR-4 compatible, no Composer needed)
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $base   = APP_ROOT . '/app/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file     = $base . str_replace('\\', '/', $relative) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Load config / env
require APP_ROOT . '/config/config.php';

// Init logger
\App\Core\Logger::init(APP_ROOT . '/logs/app.log');

// Start secure session
\App\Core\Session::start();

// ── Security headers ───────────────────────────────────────────────────────
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net blob: 'unsafe-inline'; style-src 'self' https://fonts.googleapis.com https://cdn.jsdelivr.net 'unsafe-inline'; font-src https://fonts.gstatic.com https://cdn.jsdelivr.net data:; img-src 'self' https://cdn.discordapp.com https://cdn.jsdelivr.net data: blob:; connect-src 'self'; worker-src blob:; frame-ancestors 'self';");

// ── Router ─────────────────────────────────────────────────────────────────
$router = new \App\Core\Router();

use App\Controllers\AdminController;
use App\Controllers\ApiController;
use App\Controllers\AppealController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Controllers\ManagementController;
use App\Controllers\BenefitsController;

// ── Middleware stacks ──────────────────────────────────────────────────────
$auth       = new \App\Middleware\AuthMiddleware();

// ── Public routes ──────────────────────────────────────────────────────────
$router->get('/',               [HomeController::class, 'index']);
$router->get('/novinky',        [HomeController::class, 'news']);
$router->get('/novinky/:slug',  [HomeController::class, 'newsDetail']);
$router->get('/tym',            [HomeController::class, 'team']);
$router->get('/pravidla',       [HomeController::class, 'rules']);
$router->get('/partneri',       [HomeController::class, 'partners']);
$router->get('/login',          [HomeController::class, 'login']);

// ── Auth routes ────────────────────────────────────────────────────────────
$router->get('/auth/redirect',  [AuthController::class, 'oauthRedirect']);
$router->get('/auth/callback',  [AuthController::class, 'callback']);
$router->post('/logout',        [AuthController::class, 'logout']);

// ── Allowlist (authenticated) ──────────────────────────────────────────────
$router->get('/allowlist', [DashboardController::class, 'index'],             [$auth]);
$router->get('/apply',     [DashboardController::class, 'applicationForm'],   [$auth]);
$router->post('/apply',    [DashboardController::class, 'submitApplication'], [$auth]);

// ── Appeals (authenticated) ────────────────────────────────────────────────
$router->get('/appeal',  [AppealController::class, 'index'],  [$auth]);
$router->post('/appeal', [AppealController::class, 'submit'], [$auth]);

// ── Admin panel ─────────────────────────────────────────────────────────
$router->get('/admin',                     [AdminController::class, 'index'],         [$auth]);
$router->get('/admin/players',             [AdminController::class, 'players'],       [$auth]);
$router->get('/admin/players/:id',         [AdminController::class, 'playerDetail'],  [$auth]);
$router->post('/admin/players/:id/ban',    [AdminController::class, 'addBan'],        [$auth]);
$router->post('/admin/players/:id/ban/:banId/delete',  [AdminController::class, 'deleteBan'],   [$auth]);
$router->post('/admin/players/:id/warn',   [AdminController::class, 'addWarn'],       [$auth]);
$router->post('/admin/players/:id/warn/:warnId/delete', [AdminController::class, 'deleteWarn'],  [$auth]);
$router->post('/admin/players/:id/mute',                [AdminController::class, 'addMute'],       [$auth]);
$router->post('/admin/players/:id/mute/:muteId/delete', [AdminController::class, 'deleteMute'],  [$auth]);
$router->post('/admin/players/:id/access/:type',        [AdminController::class, 'toggleAccess'], [$auth]);
$router->post('/admin/players/:id/grant-allowlist', [AdminController::class, 'grantAllowlist'], [$auth]);
$router->post('/admin/players/:id/note',                        [AdminController::class, 'addNote'],      [$auth]);
$router->post('/admin/players/:id/note/:noteId/delete',         [AdminController::class, 'deleteNote'],   [$auth]);

// CK Voting (must be before /admin/:id catch-all)
$router->get('/admin/ck',              [AdminController::class, 'ckIndex'],   [$auth]);
$router->get('/admin/ck/new',          [AdminController::class, 'ckCreate'],  [$auth]);
$router->post('/admin/ck/save',        [AdminController::class, 'ckStore'],   [$auth]);
$router->get('/admin/ck/:id',          [AdminController::class, 'ckDetail'],  [$auth]);
$router->post('/admin/ck/:id/vote',    [AdminController::class, 'ckVote'],    [$auth]);
$router->post('/admin/ck/:id/comment',      [AdminController::class, 'ckComment'],     [$auth]);
$router->post('/admin/ck/:id/delete-entry', [AdminController::class, 'ckDeleteEntry'], [$auth]);
$router->post('/admin/ck/:id/close',        [AdminController::class, 'ckClose'],       [$auth]);

// Admin Activity (must be before /admin/:id catch-all)
$router->get('/admin/activity',       [AdminController::class, 'activityIndex'], [$auth]);
$router->post('/admin/activity/save', [AdminController::class, 'activitySave'],  [$auth]);

// Admin Vacation (must be before /admin/:id catch-all)
$router->get('/admin/vacation',          [AdminController::class, 'vacationIndex'],  [$auth]);
$router->post('/admin/vacation/save',    [AdminController::class, 'vacationSave'],   [$auth]);
$router->post('/admin/vacation/delete',  [AdminController::class, 'vacationDelete'], [$auth]);

// Admin Security (must be before /admin/:id catch-all)
$router->get('/admin/security',               [AdminController::class, 'securityIndex'],   [$auth]);
$router->post('/admin/security/:id/resolve',  [AdminController::class, 'securityResolve'], [$auth]);

// Admin Settings (own settings for the logged-in admin)
$router->get('/admin/settings',       [AdminController::class, 'settingsIndex'], [$auth]);
$router->post('/admin/settings/save', [AdminController::class, 'settingsSave'],  [$auth]);

// QP Bonuses (must be before /admin/players/:id catch-all)
$router->post('/admin/players/:id/qp-bonus',                     [AdminController::class, 'addQpBonus'],    [$auth]);
$router->post('/admin/players/:id/qp-bonus/:bonusId/delete',     [AdminController::class, 'deleteQpBonus'], [$auth]);

// Char Slot Bonuses (must be before /admin/players/:id catch-all)
$router->post('/admin/players/:id/char-bonus',                   [AdminController::class, 'addCharBonus'],    [$auth]);
$router->post('/admin/players/:id/char-bonus/:bonusId/delete',   [AdminController::class, 'deleteCharBonus'], [$auth]);

$router->get('/admin/:id',                 [AdminController::class, 'detail'],        [$auth]);
$router->post('/admin/:id/approve',        [AdminController::class, 'approve'],       [$auth]);
$router->post('/admin/:id/reject',         [AdminController::class, 'reject'],        [$auth]);
$router->post('/admin/:id/interview/pass', [AdminController::class, 'interviewPass'], [$auth]);
$router->post('/admin/:id/interview/fail', [AdminController::class, 'interviewFail'], [$auth]);
$router->post('/admin/:id/reinterview',    [AdminController::class, 'reinterview'],   [$auth]);

// ── Management panel (delegated access checks in controller) ───────────────
$router->get('/management', [ManagementController::class, 'index'], [$auth]);

// Form schema
$router->get('/management/form',               [ManagementController::class, 'formList'],     [$auth]);
$router->get('/management/form/new',           [ManagementController::class, 'formEdit'],     [$auth]);
$router->post('/management/form/save',         [ManagementController::class, 'formSave'],     [$auth]);
$router->get('/management/form/:id/edit',      [ManagementController::class, 'formEdit'],     [$auth]);
$router->post('/management/form/:id/save',     [ManagementController::class, 'formSave'],     [$auth]);
$router->post('/management/form/:id/delete',   [ManagementController::class, 'formDelete'],   [$auth]);
$router->post('/management/form/:id/activate', [ManagementController::class, 'formActivate'], [$auth]);

// Homepage
$router->get('/management/homepage',       [ManagementController::class, 'homepageEdit'], [$auth]);
$router->post('/management/homepage/save', [ManagementController::class, 'homepageSave'], [$auth]);

// Allowlist Statistics
$router->get('/management/allowlist-stats', [ManagementController::class, 'allowlistStats'], [$auth]);

// Content
$router->get('/management/content',            [ManagementController::class, 'contentList'],   [$auth]);
$router->get('/management/content/new',        [ManagementController::class, 'contentEdit'],   [$auth]);
$router->post('/management/content/save',      [ManagementController::class, 'contentSave'],   [$auth]);
$router->get('/management/content/:id/edit',   [ManagementController::class, 'contentEdit'],   [$auth]);
$router->post('/management/content/:id/save',  [ManagementController::class, 'contentSave'],   [$auth]);
$router->post('/management/content/:id/delete',[ManagementController::class, 'contentDelete'], [$auth]);

// Rules (category-based editor)
$router->get( '/management/rules',             [ManagementController::class, 'rulesIndex'],         [$auth]);
$router->get( '/management/rules/new',         [ManagementController::class, 'rulesEdit'],           [$auth]);
$router->post('/management/rules/save',        [ManagementController::class, 'rulesSectionSave'],    [$auth]);
$router->get( '/management/rules/:id/edit',    [ManagementController::class, 'rulesEdit'],           [$auth]);
$router->post('/management/rules/:id/save',    [ManagementController::class, 'rulesSectionSave'],    [$auth]);
$router->post('/management/rules/:id/delete',  [ManagementController::class, 'rulesSectionDelete'],  [$auth]);
$router->post('/management/rules/:id/move',    [ManagementController::class, 'rulesSectionMove'],    [$auth]);
$router->post('/management/rules/rp-blocks',   [ManagementController::class, 'rpBlocksSave'],        [$auth]);

// Cheatsheet (Tahák)
$router->get( '/management/cheatsheet',             [ManagementController::class, 'cheatsheetIndex'],   [$auth]);
$router->get( '/management/cheatsheet/new',          [ManagementController::class, 'cheatsheetEdit'],    [$auth]);
$router->post('/management/cheatsheet/save',         [ManagementController::class, 'cheatsheetSave'],    [$auth]);
$router->get( '/management/cheatsheet/:id/edit',     [ManagementController::class, 'cheatsheetEdit'],    [$auth]);
$router->post('/management/cheatsheet/:id/save',     [ManagementController::class, 'cheatsheetSave'],    [$auth]);
$router->post('/management/cheatsheet/:id/delete',   [ManagementController::class, 'cheatsheetDelete'],  [$auth]);
$router->post('/management/cheatsheet/:id/move',     [ManagementController::class, 'cheatsheetMove'],    [$auth]);

// Denylist
$router->get('/management/denylist',               [ManagementController::class, 'blacklistIndex'],  [$auth]);
$router->post('/management/denylist/add',          [ManagementController::class, 'blacklistAdd'],    [$auth]);
$router->post('/management/denylist/remove/:id',    [ManagementController::class, 'blacklistRemove'], [$auth]);

// Team
$router->get('/management/team',               [ManagementController::class, 'teamList'],    [$auth]);
$router->get('/management/team/new',           [ManagementController::class, 'teamEdit'],    [$auth]);
$router->post('/management/team/save',         [ManagementController::class, 'teamSave'],    [$auth]);
$router->get('/management/team/:id/edit',      [ManagementController::class, 'teamEdit'],    [$auth]);
$router->post('/management/team/:id/save',     [ManagementController::class, 'teamSave'],    [$auth]);
$router->post('/management/team/:id/delete',   [ManagementController::class, 'teamDelete'],  [$auth]);
$router->post('/management/team/:id/move',     [ManagementController::class, 'teamMove'],    [$auth]);
$router->post('/management/team/refresh',      [ManagementController::class, 'teamRefresh'], [$auth]);

// Partners
$router->get('/management/partners',               [ManagementController::class, 'partnerList'],    [$auth]);
$router->get('/management/partners/new',           [ManagementController::class, 'partnerEdit'],    [$auth]);
$router->post('/management/partners/save',         [ManagementController::class, 'partnerSave'],    [$auth]);
$router->get('/management/partners/:id/edit',      [ManagementController::class, 'partnerEdit'],    [$auth]);
$router->post('/management/partners/:id/save',     [ManagementController::class, 'partnerSave'],    [$auth]);
$router->post('/management/partners/:id/delete',   [ManagementController::class, 'partnerDelete'],  [$auth]);
$router->post('/management/partners/:id/move',     [ManagementController::class, 'partnerMove'],    [$auth]);

// Appeals
$router->get('/management/appeals',              [ManagementController::class, 'appealsIndex'],  [$auth]);
$router->post('/management/appeals/:id/approve', [ManagementController::class, 'appealApprove'], [$auth]);
$router->post('/management/appeals/:id/reject',  [ManagementController::class, 'appealReject'],  [$auth]);

// Discord settings
$router->get('/management/discord',                  [ManagementController::class, 'discordIndex'],          [$auth]);
$router->post('/management/discord/automation/save', [ManagementController::class, 'discordAutomationSave'], [$auth]);
$router->post('/management/discord/bot/save',           [ManagementController::class, 'discordBotConfigSave'],    [$auth]);
$router->post('/management/discord/category/save',      [ManagementController::class, 'discordCategorySave'],     [$auth]);
$router->post('/management/discord/categories/update',  [ManagementController::class, 'discordCategoriesUpdate'], [$auth]);
$router->post('/management/discord/embed/save',         [ManagementController::class, 'discordEmbedSave'],        [$auth]);
$router->post('/management/discord/embeds/save',        [ManagementController::class, 'discordAllEmbedsSave'],   [$auth]);
$router->post('/management/discord/panel/refresh',      [ManagementController::class, 'discordPanelRefresh'],     [$auth]);
$router->post('/management/discord/bannedlinks/add',    [ManagementController::class, 'discordBannedLinksAdd'],   [$auth]);
$router->post('/management/discord/bannedlinks/delete', [ManagementController::class, 'discordBannedLinksDelete'],[$auth]);

// Settings (delegated via management.settings; Vedeni always has access)
$router->get('/management/settings',            [ManagementController::class, 'settingsIndex'],         [$auth]);
$router->post('/management/settings/grant',     [ManagementController::class, 'settingsGrant'],         [$auth]);
$router->post('/management/settings/sync-perms',[ManagementController::class, 'settingsSyncPerms'],     [$auth]);
$router->post('/management/settings/remove-subject', [ManagementController::class, 'settingsRemoveSubject'], [$auth]);
$router->post('/management/settings/clear-cache',    [ManagementController::class, 'clearDiscordCache'],     [$auth]);
$router->post('/management/settings/bot',       [ManagementController::class, 'settingsBotSave'],       [$auth]);
$router->post('/management/settings/:id/revoke',[ManagementController::class, 'settingsRevoke'],        [$auth]);

// QP + Char combined configuration
$router->get('/management/role-config', [ManagementController::class, 'roleConfig'], [$auth]);

// QP configuration (legacy redirects + POST save)
$router->get('/management/qp',       [ManagementController::class, 'qpConfig'],     [$auth]);
$router->post('/management/qp/save', [ManagementController::class, 'qpConfigSave'], [$auth]);

// Char slot configuration (legacy redirect + POST save)
$router->get('/management/chars',       [ManagementController::class, 'charConfig'],     [$auth]);
$router->post('/management/chars/save', [ManagementController::class, 'charConfigSave'], [$auth]);

// Ped Menu role configuration
$router->post('/management/ped/save', [ManagementController::class, 'pedConfigSave'], [$auth]);

// Unified role config save (QP + Char + Ped in one)
$router->post('/management/role-config/save', [ManagementController::class, 'roleConfigSave'], [$auth]);

// Redemption codes
$router->get('/management/codes',              [ManagementController::class, 'codesIndex'],  [$auth]);
$router->post('/management/codes/create',      [ManagementController::class, 'codesCreate'], [$auth]);
$router->post('/management/codes/:id/delete',  [ManagementController::class, 'codesDelete'], [$auth]);

// API keys management
$router->get('/management/api-keys',                     [ManagementController::class, 'apiKeysIndex'],     [$auth]);
$router->post('/management/api-keys/create',             [ManagementController::class, 'apiKeysCreate'],    [$auth]);
$router->post('/management/api-keys/:id/toggle',         [ManagementController::class, 'apiKeysToggle'],    [$auth]);
$router->post('/management/api-keys/:id/update-ips',     [ManagementController::class, 'apiKeysUpdateIps'], [$auth]);
$router->post('/management/api-keys/:id/delete',         [ManagementController::class, 'apiKeysDelete'],    [$auth]);

// ── Benefits (Výhody) ──────────────────────────────────────────────────────
$router->get('/vyhody',         [BenefitsController::class, 'index'],  [$auth]);
$router->post('/vyhody/redeem', [BenefitsController::class, 'redeem'], [$auth]);

// ── REST API ───────────────────────────────────────────────────────────────
$router->get('/api/access/:server/:discord_id',      [ApiController::class, 'accessCheck']);
$router->post('/api/access/:server/:discord_id',     [ApiController::class, 'accessCheck']);
$router->get('/api/benefits/:discord_id',             [ApiController::class, 'benefits']);
$router->get('/api/permissions/:discord_id',          [ApiController::class, 'permissions']);
$router->post('/api/settings/:discord_id',            [ApiController::class, 'updateSettings']);
$router->post('/api/codes',                          [ApiController::class, 'createCode']);

// ── Dispatch ───────────────────────────────────────────────────────────────
try {
    $router->dispatch();
} catch (\Throwable $e) {
    \App\Core\Logger::error(
        'Unhandled exception: ' . $e->getMessage()
        . ' in ' . $e->getFile() . ':' . $e->getLine()
    );
    http_response_code(500);
    if (env('APP_ENV', 'production') !== 'production') {
        echo '<pre>' . htmlspecialchars((string)$e) . '</pre>';
    } else {
        require APP_ROOT . '/app/Views/errors/500.php';
    }
}
