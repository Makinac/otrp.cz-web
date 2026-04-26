<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Permission;
use App\Core\Logger;
use App\Core\Session;
use App\Models\AdminActivityModel;
use App\Models\AllowlistModel;
use App\Models\AppealModel;
use App\Models\CheatsheetModel;
use App\Models\CkVoteModel;
use App\Models\DiscordBotConfigModel;
use App\Models\FormSchemaModel;
use App\Models\ManagementPermissionModel;
use App\Models\PlayerActionModel;
use App\Models\PlayerIdentifierModel;
use App\Models\PlayerNoteModel;
use App\Models\SecurityLogModel;
use App\Models\SiteSettingModel;
use App\Models\TeamCacheModel;
use App\Models\UserModel;
use App\Models\VacationModel;
use App\Models\QpBonusModel;
use App\Models\CharBonusModel;
use App\Models\AdminSettingModel;
use App\Services\DiscordBot;
use App\Services\QpService;
use App\Services\CharService;

/**
 * Admin panel — review, approve, reject applications and conduct interviews.
 */
class AdminController extends BaseController
{
    private const PERM_ALLOWLIST             = 'admin.allowlist';
    private const PERM_ALLOWLIST_REINTERVIEW = 'admin.allowlist.reinterview';
    private const PERM_PLAYERS               = 'admin.players';
    private const PERM_PLAYERS_PUNISHMENTS   = 'admin.players.punishments';
    private const PERM_PLAYERS_ACCESS        = 'admin.players.access';
    private const PERM_PLAYERS_APPEALS       = 'admin.players.appeals';
    private const PERM_CK                    = 'admin.ck';
    private const PERM_ACTIVITY               = 'admin.activity';
    private const PERM_VACATION               = 'admin.vacation';
    private const PERM_SECURITY               = 'admin.security';
    private const PERM_QP_BONUS               = 'admin.qp_bonus';
    private const PERM_CHAR_BONUS              = 'admin.char_bonus';

    /** @var array<string,bool>|null Per-request permission cache. */
    private ?array $adminPermsCache = null;

    /**
     * @return array<string,bool>
     */
    private function getAdminPermissions(): array
    {
        if ($this->adminPermsCache !== null) {
            return $this->adminPermsCache;
        }

        $all = [
            self::PERM_ALLOWLIST             => false,
            self::PERM_ALLOWLIST_REINTERVIEW => false,
            self::PERM_PLAYERS               => false,
            self::PERM_PLAYERS_PUNISHMENTS   => false,
            self::PERM_PLAYERS_ACCESS        => false,
            self::PERM_PLAYERS_APPEALS       => false,
            self::PERM_CK                    => false,
            self::PERM_ACTIVITY               => false,
            self::PERM_VACATION               => false,
            self::PERM_SECURITY               => false,
            self::PERM_QP_BONUS               => false,
            self::PERM_CHAR_BONUS             => false,
        ];

        if (Permission::isVedeni()) {
            foreach ($all as $key => $_) {
                $all[$key] = true;
            }
            $this->adminPermsCache = $all;
            return $all;
        }

        $roles = Session::get('roles', []);
        $roles = is_array($roles) ? array_values(array_map('strval', $roles)) : [];
        $roleIds = Permission::roleIds();
        $userId = (int)Permission::userId();

        $keys = (new ManagementPermissionModel())->getPermissionKeysForUser($userId, $roles, $roleIds);
        foreach ($keys as $key) {
            if (array_key_exists($key, $all)) {
                $all[$key] = true;
            }
        }

        $this->adminPermsCache = $all;
        return $all;
    }

    private function requireAdminPermission(string $permissionKey): void
    {
        $permissions = $this->getAdminPermissions();
        if (!($permissions[$permissionKey] ?? false)) {
            http_response_code(403);
            $this->render('errors/403', ['pageTitle' => '403']);
            exit;
        }
    }

    /**
     * @param array<string,bool> $permissions
     */
    private function hasAnyAdminPermission(array $permissions): bool
    {
        return in_array(true, $permissions, true);
    }

    /**
     * Show the admin panel — filterable list of all applications.
     *
     * @param array<string,string> $params Route parameters (unused).
     */
    public function index(array $params = []): void
    {
        $adminPerms = $this->getAdminPermissions();
        if (!$this->hasAnyAdminPermission($adminPerms)) {
            http_response_code(403);
            $this->render('errors/403', ['pageTitle' => '403']);
            return;
        }

        if (empty($adminPerms[self::PERM_ALLOWLIST]) && !empty($adminPerms[self::PERM_PLAYERS])) {
            $this->redirect('/admin/players');
        }

        if (empty($adminPerms[self::PERM_ALLOWLIST]) && empty($adminPerms[self::PERM_PLAYERS]) && !empty($adminPerms[self::PERM_CK])) {
            $this->redirect('/admin/ck');
        }

        $this->requireAdminPermission(self::PERM_ALLOWLIST);

        $model  = new AllowlistModel();
        $filter = $_GET['filter'] ?? 'pending';
        $allowed = ['all', 'pending', 'interview', 'active', 'rejected'];
        if (!in_array($filter, $allowed, true)) {
            $filter = 'pending';
        }
        // "active" filter is Vedení-only
        if ($filter === 'active' && !Permission::isVedeni()) {
            $filter = 'pending';
        }

        $search = trim($_GET['q'] ?? '');
        $applications = $model->getAllApplications($filter, $search);
        $counts       = $model->getStatusCounts();

        $this->render('admin/index', [
            'pageTitle'    => 'Admin Panel',
            'applications' => $applications,
            'counts'       => $counts,
            'filter'       => $filter,
            'search'       => $search,
            'adminPerms'   => $adminPerms,
            'adminActive'  => 'allowlist',
            'isVedeni'     => Permission::isVedeni(),
        ]);
    }

    /**
     * Show a single application detail with form answers and action buttons.
     *
     * @param array<string,string> $params Route parameters — expects :id.
     */
    public function detail(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_ALLOWLIST);

        $id    = (int)($params['id'] ?? 0);
        $model = new AllowlistModel();
        $app   = $model->findByIdWithUser($id);

        if (!$app) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => '404']);
            return;
        }

        $formData = json_decode($app['form_data_json'] ?? '{}', true) ?? [];

        // Build name → label map from the active form schema
        $schemaModel = new FormSchemaModel();
        $schema = $schemaModel->getActive();
        $labelMap = [];
        if ($schema) {
            $fields = json_decode($schema['fields_json'], true) ?? [];
            foreach ($fields as $f) {
                if (isset($f['name'], $f['label'])) {
                    $labelMap[$f['name']] = $f['label'];
                }
            }
        }

        $adminPerms = $this->getAdminPermissions();
        $interviewHistory = $model->getInterviewHistory($id);

        $cheatsheetModel = new CheatsheetModel();
        $allQuestions = $cheatsheetModel->getAll();
        $totalQuestions = count($allQuestions);
        $randomCount = min(20, $totalQuestions);
        $cheatsheetQuestions = $cheatsheetModel->getRandom($randomCount);

        $this->render('admin/detail', [
            'pageTitle'            => 'Detail žádosti #' . $id,
            'app'                  => $app,
            'formData'             => $formData,
            'labelMap'             => $labelMap,
            'canReinterview'       => !empty($adminPerms[self::PERM_ALLOWLIST_REINTERVIEW]),
            'interviewHistory'     => $interviewHistory,
            'cheatsheetQuestions'  => $cheatsheetQuestions,
        ]);
    }

    /**
     * Approve an application (POST).
     *
     * @param array<string,string> $params Route parameters — expects :id.
     */
    public function approve(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_ALLOWLIST);
        $this->requirePost();
        $this->verifyCsrf();

        $id         = (int)($params['id'] ?? 0);
        $reviewerId = Permission::userId();
        $model      = new AllowlistModel();
        $app        = $model->findById($id);

        if (!$app || $app['status'] !== 'pending') {
            Session::flash('error', 'Žádost nenalezena nebo není ve stavu pending.');
            $this->redirect('/admin');
        }

        try {
            $model->approve($id, $reviewerId);
            Session::flash('success', 'Žádost schválena. Přechod do Fáze 2 — Pohovor.');

            // Discord bot: role + DM
            $this->botOnApprove($id);
        } catch (\Throwable $e) {
            Logger::error("Tester approve failed for app #{$id}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se schválit žádost.');
        }

        $this->redirect('/admin');
    }

    /**
     * Reject an application (POST).
     *
     * @param array<string,string> $params Route parameters — expects :id.
     */
    public function reject(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_ALLOWLIST);
        $this->requirePost();
        $this->verifyCsrf();

        $id         = (int)($params['id'] ?? 0);
        $reviewerId = Permission::userId();
        $errorCount = (int)($_POST['error_count'] ?? 0);
        $model      = new AllowlistModel();
        $app        = $model->findById($id);

        if (!$app || $app['status'] !== 'pending') {
            Session::flash('error', 'Žádost nenalezena nebo není ve stavu pending.');
            $this->redirect('/admin');
        }

        if ($errorCount < 0) {
            Session::flash('error', 'Počet chyb musí být nezáporné číslo.');
            $this->redirect('/admin');
        }

        try {
            $model->reject($id, $reviewerId, $errorCount);
            Session::flash('success', 'Žádost zamítnuta.');

            // Discord bot: DM
            $this->botOnReject($id);
        } catch (\Throwable $e) {
            Logger::error("Tester reject failed for app #{$id}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se zamítnout žádost.');
        }

        $this->redirect('/admin');
    }

    /**
     * Mark interview as passed (POST).
     *
     * @param array<string,string> $params Route parameters — expects :id.
     */
    public function interviewPass(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_ALLOWLIST);
        $this->requirePost();
        $this->verifyCsrf();

        $id         = (int)($params['id'] ?? 0);
        $reviewerId = Permission::userId();
        $model      = new AllowlistModel();
        $app        = $model->findById($id);

        if (!$app || $app['status'] !== 'approved') {
            Session::flash('error', 'Žádost nenalezena nebo není ve správném stavu.');
            $this->redirect('/admin');
        }

        try {
            $model->passInterview($id, $reviewerId);
            Session::flash('success', 'Pohovor označen jako splněný. Hráč získal allowlist.');

            // Discord bot: role + DM
            $this->botOnInterviewPass($id);
        } catch (\Throwable $e) {
            Logger::error("Interview pass failed for app #{$id}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se aktualizovat stav pohovoru.');
        }

        $this->redirect('/admin');
    }

    /**
     * Mark interview as failed (POST).
     *
     * @param array<string,string> $params Route parameters — expects :id.
     */
    public function interviewFail(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_ALLOWLIST);
        $this->requirePost();
        $this->verifyCsrf();

        $id         = (int)($params['id'] ?? 0);
        $reviewerId = Permission::userId();
        $errorCount = max(0, (int)($_POST['error_count'] ?? 0));
        $model      = new AllowlistModel();
        $app        = $model->findById($id);

        if (!$app || $app['status'] !== 'approved') {
            Session::flash('error', 'Žádost nenalezena nebo není ve správném stavu.');
            $this->redirect('/admin');
        }

        try {
            $attempts = $model->failInterview($id, $reviewerId, $errorCount);
            if ($attempts >= 3) {
                Session::flash('success', 'Pohovor nesplněn (pokus ' . $attempts . '/3). Žádost zablokována — hráč musí podat nový formulář.');
            } else {
                Session::flash('success', 'Pohovor nesplněn (pokus ' . $attempts . '/3). Hráč má ještě ' . (3 - $attempts) . ' pokus(y).');
            }

            // Discord bot: DM
            $this->botOnInterviewFail($id);
        } catch (\Throwable $e) {
            Logger::error("Interview fail failed for app #{$id}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se aktualizovat stav pohovoru.');
        }

        $this->redirect('/admin/' . $id);
    }

    /**
     * Reset interview attempts so the player can retry.
     *
     * @param array<string,string> $params Route parameters — expects :id.
     */
    public function reinterview(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_ALLOWLIST_REINTERVIEW);
        $this->requirePost();
        $this->verifyCsrf();

        $id    = (int)($params['id'] ?? 0);
        $model = new AllowlistModel();
        $app   = $model->findById($id);

        $eligible = $app && (
            ($app['status'] === 'approved')
            || ($app['status'] === 'blocked' && $app['interview_status'] === 'failed')
        );

        if (!$eligible) {
            Session::flash('error', 'Tuto žádost nelze resetovat pro repohovor (nesprávný stav).');
            $this->redirect('/admin/' . $id);
        }

        try {
            $model->resetInterviewAttempts($id);
            Session::flash('success', 'Pokusy o pohovor byly resetovány. Hráč může absolvovat pohovor znovu.');
        } catch (\Throwable $e) {
            Logger::error("Reinterview reset failed for app #{$id}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se resetovat pokusy o pohovor.');
            $this->redirect('/admin/' . $id);
        }

        $this->redirect('/admin/' . $id);
    }

    /**
     * Manually grant allowlist to a player (creates auto-approved + interview passed application).
     *
     * @param array<string,string> $params Route parameters — expects :id (user id).
     */
    public function grantAllowlist(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_ALLOWLIST);
        $this->requirePost();
        $this->verifyCsrf();

        $userId    = (int)($params['id'] ?? 0);
        $grantedBy = Permission::userId();
        $userModel = new UserModel();
        $user      = $userModel->findById($userId);

        if (!$user) {
            Session::flash('error', 'Uživatel nenalezen.');
            $this->redirect('/admin/players');
        }

        $alModel = new AllowlistModel();

        try {
            $alModel->grantManual($userId, $grantedBy);
            Session::flash('success', 'Allowlist byl manuálně udělen hráči ' . $user['username'] . '.');
        } catch (\Throwable $e) {
            Logger::error("Manual allowlist grant failed for user #{$userId}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se udělit allowlist.');
        }

        $this->redirect('/admin/players/' . $userId);
    }

    /**
     * Ensure request method is POST, redirect otherwise.
     */
    protected function requirePost(string $fallbackUrl = '/admin'): void
    {
        parent::requirePost($fallbackUrl);
    }

    /**
     * Verify CSRF token from POST body.
     */
    protected function verifyCsrf(string $fallbackUrl = '/admin'): void
    {
        parent::verifyCsrf($fallbackUrl);
    }

    /**
     * Player search page (GET /admin/players).
     *
     * @param array<string,string> $params Route parameters (unused).
     */
    public function players(array $params = []): void
    {
        $adminPerms = $this->getAdminPermissions();
        if (empty($adminPerms[self::PERM_PLAYERS]) && !empty($adminPerms[self::PERM_ALLOWLIST])) {
            $this->redirect('/admin');
        }
        $this->requireAdminPermission(self::PERM_PLAYERS);

        $model = new UserModel();
        $query = trim($_GET['q'] ?? '');

        if ($query !== '') {
            $users = $model->search($query);
        } else {
            $users = $model->getAllWithStatus();
        }

        $this->render('admin/players', [
            'pageTitle' => 'Hráči',
            'users'     => $users,
            'query'     => $query,
            'adminPerms' => $adminPerms,
            'adminActive' => 'players',
        ]);
    }

    /**
     * Player detail page (GET /admin/players/:id).
     *
     * @param array<string,string> $params Route parameters — expects :id.
     */
    public function playerDetail(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_PLAYERS);

        $id        = (int)($params['id'] ?? 0);
        $userModel = new UserModel();
        $user      = $userModel->findById($id);

        if (!$user) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => '404']);
            return;
        }

        $appModel    = new AllowlistModel();
        $actionModel = new PlayerActionModel();

        $applications = $appModel->findAllByUserId($id);
        $bans         = $actionModel->getBansByUserId($id);
        $warns        = $actionModel->getWarnsByUserId($id);
        $mutes        = $actionModel->getMutesByUserId($id);

        $currentUserId = Permission::userId();
        $teamModel     = new TeamCacheModel();
        $teamMembers   = $teamModel->getAll();
        // Deduplicate by username & exclude current user
        $currentUsername = \App\Core\Session::get('username', '');
        $seen = [];
        $staffUsers = [];
        foreach ($teamMembers as $m) {
            $name = $m['username'];
            if (isset($seen[$name]) || $name === $currentUsername) continue;
            $seen[$name] = true;
            $staffUsers[] = ['username' => $name];
        }

        $adminPerms = $this->getAdminPermissions();

        // Derive allowlist (Main server) access from latest application
        $hasAllowlist = false;
        foreach ($applications as $app) {
            if ($app['status'] === 'approved' && $app['interview_status'] === 'passed') {
                $hasAllowlist = true;
                break;
            }
        }

        $appealModel   = new AppealModel();
        $appeals       = $appealModel->getAllByUserId($id);
        $pendingAppeal = $appealModel->findPendingByUserId($id);

        $identifierModel = new PlayerIdentifierModel();
        $identifiers     = $identifierModel->getGroupedByUserId($id);

        $securityModel = new SecurityLogModel();
        $securityLogs  = $securityModel->getByUserId($id);

        $noteModel = new PlayerNoteModel();
        $notes     = $noteModel->getByUserId($id);

        $qpBreakdown   = QpService::getBreakdown($user['discord_id'], $id);
        $charBreakdown = CharService::getBreakdown($user['discord_id'], $id);

        $this->render('admin/player_detail', [
            'pageTitle'            => 'Hráč: ' . $user['username'],
            'user'                 => $user,
            'applications'         => $applications,
            'bans'                 => $bans,
            'warns'                => $warns,
            'mutes'                => $mutes,
            'staffUsers'           => array_values($staffUsers),
            'canManagePunishments' => !empty($adminPerms[self::PERM_PLAYERS_PUNISHMENTS]),
            'canToggleAccess'      => !empty($adminPerms[self::PERM_PLAYERS_ACCESS]),
            'canViewAppeals'       => !empty($adminPerms[self::PERM_PLAYERS_APPEALS]),
            'canViewSecurity'      => !empty($adminPerms[self::PERM_SECURITY]),
            'canManageQp'          => Permission::isVedeni(),
            'canManageChars'       => Permission::isVedeni(),
            'hasAllowlist'         => $hasAllowlist,
            'appeals'              => $appeals,
            'pendingAppeal'        => $pendingAppeal,
            'identifiers'          => $identifiers,
            'securityLogs'         => $securityLogs,
            'notes'                => $notes,
            'qpBreakdown'          => $qpBreakdown,
            'charBreakdown'        => $charBreakdown,
            'adminPerms'           => $adminPerms,
            'adminActive'          => 'players',
        ]);
    }

    /**
     * Add a note to a player (POST /admin/players/:id/note).
     */
    public function addNote(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_PLAYERS);
        $this->requirePost();
        $this->verifyCsrf();

        $userId    = (int)($params['id'] ?? 0);
        $note      = trim($_POST['note'] ?? '');
        $authorId  = (int)Permission::userId();

        if ($note === '' || mb_strlen($note) > 2000) {
            Session::flash('error', 'Poznámka musí mít 1–2000 znaků.');
            $this->redirect("/admin/players/{$userId}");
        }

        $userModel = new UserModel();
        if (!$userModel->findById($userId)) {
            Session::flash('error', 'Hráč nenalezen.');
            $this->redirect('/admin/players');
        }

        try {
            (new PlayerNoteModel())->add($userId, $authorId, $note);
        } catch (\Throwable $e) {
            Logger::error("addNote for user #{$userId}: " . $e->getMessage());
            Session::flash('error', 'Poznámku se nepodařilo uložit.');
            $this->redirect("/admin/players/{$userId}");
        }

        $this->redirect("/admin/players/{$userId}");
    }

    /**
     * Delete a note (POST /admin/players/:id/note/:noteId/delete).
     */
    public function deleteNote(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_PLAYERS);
        $this->requirePost();
        $this->verifyCsrf();

        $userId = (int)($params['id']     ?? 0);
        $noteId = (int)($params['noteId'] ?? 0);

        $noteModel = new PlayerNoteModel();
        $note      = $noteModel->findById($noteId);

        if (!$note || (int)$note['user_id'] !== $userId) {
            Session::flash('error', 'Poznámka nenalezena.');
            $this->redirect("/admin/players/{$userId}");
        }

        // Only the author or vedení can delete
        $currentUserId = (int)Permission::userId();
        if ((int)$note['author_id'] !== $currentUserId && !Permission::isVedeni()) {
            Session::flash('error', 'Nemáš oprávnění smazat tuto poznámku.');
            $this->redirect("/admin/players/{$userId}");
        }

        try {
            $noteModel->delete($noteId);
        } catch (\Throwable $e) {
            Logger::error("deleteNote #{$noteId}: " . $e->getMessage());
            Session::flash('error', 'Poznámku se nepodařilo smazat.');
            $this->redirect("/admin/players/{$userId}");
        }

        $this->redirect("/admin/players/{$userId}");
    }

    /**
     * Add a ban for a player (POST /admin/players/:id/ban).
     *
     * @param array<string,string> $params Route parameters — expects :id.
     */
    public function addBan(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_PLAYERS_PUNISHMENTS);
        $this->requirePost();
        $this->verifyCsrf();

        $id        = (int)($params['id'] ?? 0);
        $reason    = trim($_POST['reason'] ?? '');
        $permanent = !empty($_POST['permanent']);
        $expiresAt = null;

        if ($reason === '' || mb_strlen($reason) > 2000) {
            Session::flash('error', 'Důvod banu musí mít 1–2000 znaků.');
            $this->redirect("/admin/players/{$id}");
        }

        if (!$permanent) {
            $raw = trim($_POST['expires_at'] ?? '');
            if ($raw !== '') {
                $dt = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $raw);
                $expiresAt = $dt ? $dt->format('Y-m-d H:i:s') : null;
            }
        }

        $witnesses = array_map('strval', array_filter((array)($_POST['witnesses'] ?? []), fn($v) => trim($v) !== ''));

        $userModel = new UserModel();
        if (!$userModel->findById($id)) {
            Session::flash('error', 'Hráč nenalezen.');
            $this->redirect('/admin/players');
        }

        $actionModel = new PlayerActionModel();
        try {
            $actionModel->addBan($id, $reason, $expiresAt, $witnesses, Permission::userId());
            Session::flash('success', 'Ban byl udělen.');
        } catch (\Throwable $e) {
            Logger::error("addBan failed for user #{$id}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se udělit ban.');
        }

        $this->redirect("/admin/players/{$id}");
    }

    /**
     * Delete a ban (POST /admin/players/:id/ban/:banId/delete).
     *
     * @param array<string,string> $params Route parameters — expects :id, :banId.
     */
    public function deleteBan(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_PLAYERS_PUNISHMENTS);
        $this->requirePost();
        $this->verifyCsrf();

        $userId = (int)($params['id']    ?? 0);
        $banId  = (int)($params['banId'] ?? 0);
        $reason = trim($_POST['revoke_reason'] ?? '');

        if ($reason === '') {
            Session::flash('error', 'Musíš uvest důvod zrušení banu.');
            $this->redirect("/admin/players/{$userId}");
            return;
        }

        $actionModel = new PlayerActionModel();
        try {
            $actionModel->revokeBan($banId, Permission::userId(), $reason);
            Session::flash('success', 'Ban byl zrušen.');
        } catch (\Throwable $e) {
            Logger::error("revokeBan #{$banId} failed: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se zrušit ban.');
        }

        $this->redirect("/admin/players/{$userId}");
    }

    /**
     * Add a warn for a player (POST /admin/players/:id/warn).
     *
     * @param array<string,string> $params Route parameters — expects :id.
     */
    public function addWarn(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_PLAYERS_PUNISHMENTS);
        $this->requirePost();
        $this->verifyCsrf();

        $id        = (int)($params['id'] ?? 0);
        $reason    = trim($_POST['reason'] ?? '');
        $permanent = !empty($_POST['permanent']);
        $expiresAt = null;

        if ($reason === '' || mb_strlen($reason) > 2000) {
            Session::flash('error', 'Důvod warnu musí mít 1–2000 znaků.');
            $this->redirect("/admin/players/{$id}");
        }

        if (!$permanent) {
            $raw = trim($_POST['expires_at'] ?? '');
            if ($raw !== '') {
                $dt = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $raw);
                $expiresAt = $dt ? $dt->format('Y-m-d H:i:s') : null;
            }
        }

        $witnesses = array_map('strval', array_filter((array)($_POST['witnesses'] ?? []), fn($v) => trim($v) !== ''));

        $userModel = new UserModel();
        if (!$userModel->findById($id)) {
            Session::flash('error', 'Hráč nenalezen.');
            $this->redirect('/admin/players');
        }

        $actionModel = new PlayerActionModel();
        try {
            $actionModel->addWarn($id, $reason, $expiresAt, $witnesses, Permission::userId());
            Session::flash('success', 'Warn byl udělen.');
        } catch (\Throwable $e) {
            Logger::error("addWarn failed for user #{$id}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se udělit warn.');
        }

        $this->redirect("/admin/players/{$id}");
    }

    /**
     * Delete a warn (POST /admin/players/:id/warn/:warnId/delete).
     *
     * @param array<string,string> $params Route parameters — expects :id, :warnId.
     */
    public function deleteWarn(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_PLAYERS_PUNISHMENTS);
        $this->requirePost();
        $this->verifyCsrf();

        $userId = (int)($params['id']     ?? 0);
        $warnId = (int)($params['warnId'] ?? 0);
        $reason = trim($_POST['revoke_reason'] ?? '');

        if ($reason === '') {
            Session::flash('error', 'Musíš uvest důvod zrušení warnu.');
            $this->redirect("/admin/players/{$userId}");
            return;
        }

        $actionModel = new PlayerActionModel();
        try {
            $actionModel->revokeWarn($warnId, Permission::userId(), $reason);
            Session::flash('success', 'Warn byl zrušen.');
        } catch (\Throwable $e) {
            Logger::error("revokeWarn #{$warnId} failed: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se zrušit warn.');
        }

        $this->redirect("/admin/players/{$userId}");
    }

    /**
     * Add a mute for a player (POST /admin/players/:id/mute).
     *
     * Assigns the configured mute role on Discord and records the mute in the DB.
     *
     * @param array<string,string> $params Route parameters — expects :id.
     */
    public function addMute(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_PLAYERS_PUNISHMENTS);
        $this->requirePost();
        $this->verifyCsrf();

        $id        = (int)($params['id'] ?? 0);
        $reason    = trim($_POST['reason'] ?? '');
        $permanent = !empty($_POST['permanent']);
        $expiresAt = null;

        if ($reason === '' || mb_strlen($reason) > 2000) {
            Session::flash('error', 'Důvod mute musí mít 1–2000 znaků.');
            $this->redirect("/admin/players/{$id}");
            return;
        }

        if (!$permanent) {
            $raw = trim($_POST['expires_at'] ?? '');
            if ($raw !== '') {
                $dt = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $raw);
                $expiresAt = $dt ? $dt->format('Y-m-d H:i:s') : null;
            }
        }

        $userModel = new UserModel();
        $user      = $userModel->findById($id);
        if (!$user) {
            Session::flash('error', 'Hráč nenalezen.');
            $this->redirect('/admin/players');
            return;
        }

        $actionModel = new PlayerActionModel();
        try {
            $actionModel->addMute($id, $reason, $expiresAt, Permission::userId(), 'web');

            // Assign mute role on Discord
            if (!empty($user['discord_id'])) {
                $muteRoleId = (new DiscordBotConfigModel())->get('mute_role_id');
                if (!empty($muteRoleId)) {
                    DiscordBot::addRole((string)$user['discord_id'], (string)$muteRoleId);
                }
            }

            Session::flash('success', 'Mute byl udělen.');
        } catch (\Throwable $e) {
            Logger::error("addMute failed for user #{$id}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se udělit mute.');
        }

        $this->redirect("/admin/players/{$id}");
    }

    /**
     * Delete a mute (POST /admin/players/:id/mute/:muteId/delete).
     *
     * @param array<string,string> $params Route parameters — expects :id, :muteId.
     */
    public function deleteMute(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_PLAYERS_PUNISHMENTS);
        $this->requirePost();
        $this->verifyCsrf();

        $userId = (int)($params['id'] ?? 0);
        $muteId = (int)($params['muteId'] ?? 0);
        $reason = trim($_POST['revoke_reason'] ?? '');

        if ($reason === '') {
            Session::flash('error', 'Musíš uvest důvod zrušení mute.');
            $this->redirect("/admin/players/{$userId}");
            return;
        }

        $actionModel = new PlayerActionModel();
        $userModel   = new UserModel();

        try {
            $actionModel->revokeMute($muteId, Permission::userId(), $reason);

            // Best effort: remove mute role on Discord when mute is revoked from web.
            $user = $userModel->findById($userId);
            if ($user && !empty($user['discord_id'])) {
                try {
                    $muteRoleId = (new DiscordBotConfigModel())->get('mute_role_id');
                    if (!empty($muteRoleId)) {
                        $ok = DiscordBot::removeRole((string)$user['discord_id'], (string)$muteRoleId);
                        if (!$ok) {
                            Logger::error("deleteMute #{$muteId}: revoked in DB, but failed to remove Discord mute role for user #{$userId}");
                        }
                    }
                } catch (\Throwable $e) {
                    Logger::error("deleteMute #{$muteId}: role cleanup warning: " . $e->getMessage());
                }
            }

            Session::flash('success', 'Mute byl zrušen.');
        } catch (\Throwable $e) {
            Logger::error("revokeMute #{$muteId} failed: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se zrušit mute.');
        }

        $this->redirect("/admin/players/{$userId}");
    }

    /**
     * Toggle server access (dev/maps) for a player.
     * POST /admin/players/:id/access/:type
     *
     * @param array<string,string> $params Route parameters — expects :id, :type.
     */
    public function toggleAccess(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_PLAYERS_ACCESS);
        $this->requirePost();
        $this->verifyCsrf();

        $userId = (int)($params['id']   ?? 0);
        $type   = $params['type'] ?? '';

        if (!in_array($type, ['dev', 'maps'], true)) {
            Session::flash('error', 'Neznámý typ přístupu.');
            $this->redirect("/admin/players/{$userId}");
        }

        $userModel = new UserModel();
        $user      = $userModel->findById($userId);

        if (!$user) {
            Session::flash('error', 'Hráč nenalezen.');
            $this->redirect('/admin/players');
        }

        $col     = 'access_' . $type;
        $current = (bool)($user[$col] ?? false);
        $label   = $type === 'dev' ? 'Dev přístup' : 'Maps přístup';

        try {
            $userModel->setAccess($userId, $type, !$current);
            $action = !$current ? 'udělen' : 'odebrán';
            Session::flash('success', "{$label} byl {$action}.");
        } catch (\Throwable $e) {
            Logger::error("toggleAccess {$type} for user #{$userId}: " . $e->getMessage());
            Session::flash('error', "Nepodařilo se změnit {$label}.");
        }

        $this->redirect("/admin/players/{$userId}");
    }

    // =========================================================================
    // CK Voting
    // =========================================================================

    /**
     * List CK votes (GET /admin/ck).
     */
    public function ckIndex(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_CK);

        $model  = new CkVoteModel();
        $filter = $_GET['filter'] ?? 'open';
        if (!in_array($filter, ['open', 'closed', 'all'], true)) {
            $filter = 'open';
        }

        $votes    = $model->getAll($filter);
        $openCount = $model->countOpen();

        $this->render('admin/ck_list', [
            'pageTitle'   => 'CK Hlasování',
            'votes'       => $votes,
            'filter'      => $filter,
            'openCount'   => $openCount,
            'adminPerms'  => $this->getAdminPermissions(),
            'adminActive' => 'ck',
        ]);
    }

    /**
     * Show CK vote creation form (GET /admin/ck/new).
     */
    public function ckCreate(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_CK);

        $this->render('admin/ck_create', [
            'pageTitle'   => 'Nové CK hlasování',
            'adminPerms'  => $this->getAdminPermissions(),
            'adminActive' => 'ck',
        ]);
    }

    /**
     * Store a new CK vote (POST /admin/ck/save).
     */
    public function ckStore(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_CK);
        $this->requirePost();
        $this->verifyCsrf();

        $applicant   = trim($_POST['applicant'] ?? '');
        $victim      = trim($_POST['victim'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $contextRaw  = trim($_POST['context_urls'] ?? '');

        if ($applicant === '' || $victim === '' || $description === '') {
            Session::flash('error', 'Všechna povinná pole musí být vyplněna.');
            $this->redirect('/admin/ck/new');
        }

        if (mb_strlen($applicant) > 200 || mb_strlen($victim) > 200) {
            Session::flash('error', 'Jméno žadatele/oběti nesmí přesáhnout 200 znaků.');
            $this->redirect('/admin/ck/new');
        }

        if (mb_strlen($description) > 10000) {
            Session::flash('error', 'Popis nesmí přesáhnout 10 000 znaků.');
            $this->redirect('/admin/ck/new');
        }

        // Parse context URLs (one per line), keep only valid URLs
        $contextUrls = null;
        if ($contextRaw !== '') {
            $lines = array_filter(array_map('trim', explode("\n", $contextRaw)), fn($l) => $l !== '');
            $valid = [];
            foreach ($lines as $line) {
                if (filter_var($line, FILTER_VALIDATE_URL)) {
                    $valid[] = $line;
                }
            }
            if (!empty($valid)) {
                $contextUrls = json_encode($valid);
            }
        }

        $model = new CkVoteModel();
        try {
            $id = $model->create($applicant, $victim, $description, $contextUrls, (int)Permission::userId());
            Session::flash('success', 'CK hlasování bylo vytvořeno.');
            $this->redirect('/admin/ck/' . $id);
        } catch (\Throwable $e) {
            Logger::error("CK vote create failed: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se vytvořit hlasování.');
            $this->redirect('/admin/ck/new');
        }
    }

    /**
     * CK vote detail + voting (GET /admin/ck/:id).
     */
    public function ckDetail(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_CK);

        $id    = (int)($params['id'] ?? 0);
        $model = new CkVoteModel();
        $vote  = $model->findById($id);

        if (!$vote) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => '404']);
            return;
        }

        $userId   = (int)Permission::userId();
        $userVote = $model->getUserVote($id, $userId);
        $entries  = $model->getEntries($id);
        $comments = $model->getComments($id);

        $this->render('admin/ck_detail', [
            'pageTitle'   => 'CK Hlasování #' . $id,
            'vote'        => $vote,
            'userVote'    => $userVote,
            'entries'     => $entries,
            'comments'    => $comments,
            'adminPerms'  => $this->getAdminPermissions(),
            'adminActive' => 'ck',
        ]);
    }

    /**
     * Cast a vote on a CK vote (POST /admin/ck/:id/vote).
     */
    public function ckVote(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_CK);
        $this->requirePost();
        $this->verifyCsrf();

        $id       = (int)($params['id'] ?? 0);
        $decision = $_POST['decision'] ?? '';
        $reason   = trim($_POST['reason'] ?? '');
        $userId   = (int)Permission::userId();

        if (!in_array($decision, ['approve', 'reject', 'abstain'], true)) {
            Session::flash('error', 'Neplatná volba.');
            $this->redirect('/admin/ck/' . $id);
        }

        if ($reason === '') {
            Session::flash('error', 'Musíš uvést důvod svého hlasování.');
            $this->redirect('/admin/ck/' . $id);
        }

        $model = new CkVoteModel();
        $vote  = $model->findById($id);

        if (!$vote || $vote['status'] !== 'open') {
            Session::flash('error', 'Hlasování nenalezeno nebo je již uzavřené.');
            $this->redirect('/admin/ck');
            return;
        }

        // Admin cannot change their verdict once cast
        $existing = $model->getUserVote($id, $userId);
        if ($existing) {
            Session::flash('error', 'Svůj hlas už nemůžeš změnit.');
            $this->redirect('/admin/ck/' . $id);
            return;
        }

        try {
            $model->castVote($id, $userId, $decision, $reason);
            Session::flash('success', 'Tvůj hlas byl zaznamenán.');
        } catch (\Throwable $e) {
            Logger::error("CK vote cast failed for vote #{$id}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se zaznamenat hlas.');
        }

        $this->redirect('/admin/ck/' . $id);
    }

    /**
     * Delete a vote entry (POST /admin/ck/:id/delete-entry). Vedení only.
     */
    public function ckDeleteEntry(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_CK);
        $this->requirePost();
        $this->verifyCsrf();

        if (!Permission::isVedeni()) {
            Session::flash('error', 'Nemáš oprávnění mazat hlasy.');
            $this->redirect('/admin/ck');
            return;
        }

        $id      = (int)($params['id'] ?? 0);
        $entryId = (int)($_POST['entry_id'] ?? 0);

        $model = new CkVoteModel();
        $vote  = $model->findById($id);

        if (!$vote || $vote['status'] !== 'open') {
            Session::flash('error', 'Hlasování nenalezeno nebo je již uzavřené.');
            $this->redirect('/admin/ck');
            return;
        }

        try {
            $model->deleteEntry($entryId);
            Session::flash('success', 'Hlas byl smazán.');
        } catch (\Throwable $e) {
            Logger::error("CK delete entry failed for vote #{$id}, entry #{$entryId}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se smazat hlas.');
        }

        $this->redirect('/admin/ck/' . $id);
    }

    /**
     * Add a comment to a CK vote (POST /admin/ck/:id/comment).
     */
    public function ckComment(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_CK);
        $this->requirePost();
        $this->verifyCsrf();

        $id     = (int)($params['id'] ?? 0);
        $body   = trim($_POST['body'] ?? '');
        $userId = (int)Permission::userId();

        if ($body === '') {
            Session::flash('error', 'Poznámka nesmí být prázdná.');
            $this->redirect('/admin/ck/' . $id);
        }

        $model = new CkVoteModel();
        $vote  = $model->findById($id);

        if (!$vote) {
            Session::flash('error', 'Hlasování nenalezeno.');
            $this->redirect('/admin/ck');
        }

        try {
            $model->addComment($id, $userId, $body);
            Session::flash('success', 'Poznámka byla přidána.');
        } catch (\Throwable $e) {
            Logger::error("CK comment failed for vote #{$id}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se přidat poznámku.');
        }

        $this->redirect('/admin/ck/' . $id);
    }

    /**
     * Close a CK vote (POST /admin/ck/:id/close).
     */
    public function ckClose(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_CK);
        $this->requirePost();
        $this->verifyCsrf();

        $id    = (int)($params['id'] ?? 0);
        $model = new CkVoteModel();
        $vote  = $model->findById($id);

        if (!$vote || $vote['status'] !== 'open') {
            Session::flash('error', 'Hlasování nenalezeno nebo je již uzavřené.');
            $this->redirect('/admin/ck');
            return;
        }

        $userId = (int)Permission::userId();
        if (!Permission::isVedeni() && (int)$vote['created_by'] !== $userId) {
            Session::flash('error', 'Nemáš oprávnění uzavřít toto hlasování.');
            $this->redirect('/admin/ck/' . $id);
            return;
        }

        try {
            $result = $model->close($id, $userId);
            $labels = ['approved' => 'Schváleno', 'rejected' => 'Zamítnuto', 'tie' => 'Nerozhodně'];
            Session::flash('success', 'Hlasování bylo uzavřeno. Výsledek: ' . ($labels[$result] ?? $result));
        } catch (\Throwable $e) {
            Logger::error("CK vote close failed for vote #{$id}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se uzavřít hlasování.');
        }

        $this->redirect('/admin/ck/' . $id);
    }

    // =========================================================================
    // Admin Activity
    // =========================================================================

    /**
     * Activity overview (GET /admin/activity).
     */
    public function activityIndex(): void
    {
        $this->requireAdminPermission(self::PERM_ACTIVITY);

        $currentUserId = (int)Permission::userId();
        $model   = new AdminActivityModel();
        $isVedeni = Permission::isVedeni();
        $today    = new \DateTimeImmutable('today');

        $viewUserId  = $currentUserId;
        $teamMembers = [];
        $activeUsers = [];
        $monthLabel  = '';
        $prevMonth   = '';
        $nextMonth   = '';

        if ($isVedeni) {
            // Vedení: full month view with month navigation
            $monthParam = $_GET['month'] ?? '';
            if (preg_match('/^\\d{4}-\\d{2}$/', $monthParam)) {
                $monthStart = new \DateTimeImmutable($monthParam . '-01');
            } else {
                $monthStart = new \DateTimeImmutable($today->format('Y-m') . '-01');
            }

            $monthEnd = $monthStart->modify('last day of this month');
            $dateFrom = $monthStart->format('Y-m-d');
            $dateTo   = $monthEnd->format('Y-m-d');

            // Month navigation
            $prevMonth = $monthStart->modify('-1 month')->format('Y-m');
            $nextMonthDate = $monthStart->modify('+1 month');
            $nextMonth = ($nextMonthDate <= $today) ? $nextMonthDate->format('Y-m') : '';

            $czMonths = [1=>'Leden',2=>'Únor',3=>'Březen',4=>'Duben',5=>'Květen',6=>'Červen',7=>'Červenec',8=>'Srpen',9=>'Září',10=>'Říjen',11=>'Listopad',12=>'Prosinec'];
            $monthLabel = $czMonths[(int)$monthStart->format('n')] . ' ' . $monthStart->format('Y');

            // Build dates for full month (pad to start on Monday)
            $dates = [];
            $firstDow = (int)$monthStart->format('N'); // 1=Mon..7=Sun
            $padStart = $monthStart->modify('-' . ($firstDow - 1) . ' days');
            // Fill 6 weeks max (42 cells) or until we cover the month
            $d = $padStart;
            for ($i = 0; $i < 42; $i++) {
                $dates[] = $d->format('Y-m-d');
                $d = $d->modify('+1 day');
                // Stop after covering the month + completing the week
                if ($i >= 27 && (int)$d->format('N') === 1 && $d > $monthEnd) {
                    break;
                }
            }

            // User switcher
            $requestedUser = (int)($_GET['user'] ?? 0);
            if ($requestedUser > 0) {
                $viewUserId = $requestedUser;
            }

            $activeUsers = $model->getUsersWithActivity($dateFrom, $dateTo);

            // All users' activities grouped by date (for day detail)
            $allRaw = $model->getAllInRange($dateFrom, $dateTo);
            $allByDate = [];
            foreach ($allRaw as $userBlock) {
                foreach ($userBlock['days'] as $dayDate => $dayRow) {
                    $allByDate[$dayDate][] = [
                        'user_id'     => $userBlock['user_id'],
                        'username'    => $userBlock['username'],
                        'was_active'  => (int)$dayRow['was_active'],
                        'description' => $dayRow['description'] ?? '',
                    ];
                }
            }

            // Own activities for editing
            $ownActivities = $model->getForUserInRange($currentUserId, $dateFrom, $dateTo);
        } else {
            // Admin: only last 7 days
            $weekAgo  = $today->modify('-6 days');
            $dateFrom = $weekAgo->format('Y-m-d');
            $dateTo   = $today->format('Y-m-d');

            $dates = [];
            $d = $weekAgo;
            while ($d <= $today) {
                $dates[] = $d->format('Y-m-d');
                $d = $d->modify('+1 day');
            }
        }

        $activities = $model->getForUserInRange($viewUserId, $dateFrom, $dateTo);

        $this->render('admin/activity', [
            'pageTitle'      => 'Aktivita',
            'dates'          => $dates,
            'activities'     => $activities,
            'viewUserId'     => $viewUserId,
            'currentUserId'  => $currentUserId,
            'activeUsers'    => $activeUsers,
            'isVedeni'       => $isVedeni,
            'monthLabel'     => $monthLabel,
            'prevMonth'      => $prevMonth,
            'nextMonth'      => $nextMonth,
            'monthStart'     => isset($monthStart) ? $monthStart->format('Y-m') : '',
            'allByDate'      => $allByDate ?? [],
            'ownActivities'  => $ownActivities ?? [],
            'adminPerms'     => $this->getAdminPermissions(),
            'adminActive'    => 'activity',
        ]);
    }

    /**
     * Save activity for a day (POST /admin/activity/save).
     */
    public function activitySave(): void
    {
        $this->requireAdminPermission(self::PERM_ACTIVITY);
        $this->requirePost();
        $this->verifyCsrf();

        $userId = (int)Permission::userId();
        $date   = $_POST['date'] ?? '';
        $wasActive   = !empty($_POST['was_active']);
        $description = trim($_POST['description'] ?? '');

        if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $date)) {
            Session::flash('error', 'Neplatný formát data.');
            $this->redirect('/admin/activity');
            return;
        }

        $today    = new \DateTimeImmutable('today');
        $weekAgo  = $today->modify('-6 days');
        $dateObj  = new \DateTimeImmutable($date);

        if ($dateObj < $weekAgo || $dateObj > $today) {
            Session::flash('error', 'Můžeš upravovat aktivitu pouze za poslední týden.');
            $this->redirect('/admin/activity');
            return;
        }

        try {
            $model = new AdminActivityModel();
            $model->save($userId, $date, $wasActive, $description !== '' ? $description : null);
            Session::flash('success', 'Aktivita uložena.');
        } catch (\Throwable $e) {
            Logger::error('Activity save failed: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se uložit aktivitu.');
        }

        $this->redirect('/admin/activity');
    }

    // =========================================================================
    // Vacation (Dovolená)
    // =========================================================================

    /**
     * Vacation calendar (GET /admin/vacation).
     */
    public function vacationIndex(): void
    {
        $this->requireAdminPermission(self::PERM_VACATION);

        $today = new \DateTimeImmutable('today');
        $monthParam = $_GET['month'] ?? '';
        if (preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
            $monthStart = new \DateTimeImmutable($monthParam . '-01');
        } else {
            $monthStart = new \DateTimeImmutable($today->format('Y-m') . '-01');
        }

        $monthEnd = $monthStart->modify('last day of this month');
        $dateFrom = $monthStart->format('Y-m-d');
        $dateTo   = $monthEnd->format('Y-m-d');

        // Month navigation
        $prevMonth = $monthStart->modify('-1 month')->format('Y-m');
        $nextMonth = $monthStart->modify('+1 month')->format('Y-m');

        $czMonths = [1=>'Leden',2=>'Únor',3=>'Březen',4=>'Duben',5=>'Květen',6=>'Červen',7=>'Červenec',8=>'Srpen',9=>'Září',10=>'Říjen',11=>'Listopad',12=>'Prosinec'];
        $monthLabel = $czMonths[(int)$monthStart->format('n')] . ' ' . $monthStart->format('Y');

        // Build calendar grid dates (pad to Monday start)
        $dates = [];
        $firstDow = (int)$monthStart->format('N');
        $padStart = $monthStart->modify('-' . ($firstDow - 1) . ' days');
        $d = $padStart;
        for ($i = 0; $i < 42; $i++) {
            $dates[] = $d->format('Y-m-d');
            $d = $d->modify('+1 day');
            if ($i >= 27 && (int)$d->format('N') === 1 && $d > $monthEnd) {
                break;
            }
        }

        $model = new VacationModel();
        // All vacations that overlap this month (enlarged range for display)
        $firstDate = $dates[0];
        $lastDate  = end($dates);
        $vacations = $model->getForRange($firstDate, $lastDate);

        // Build user rows: each user who has admin.vacation permission or has vacations
        // We'll collect unique users from vacations + current user
        $usersMap = [];
        $currentUserId = (int)Permission::userId();

        // Current user always shown
        $userModel = new UserModel();
        $currentUser = $userModel->findById($currentUserId);
        if ($currentUser) {
            $usersMap[$currentUserId] = [
                'user_id'    => $currentUserId,
                'username'   => $currentUser['username'],
                'discord_id' => $currentUser['discord_id'],
                'avatar'     => $currentUser['avatar'],
            ];
        }

        // Users from vacations in range
        foreach ($vacations as $v) {
            $uid = (int)$v['user_id'];
            if (!isset($usersMap[$uid])) {
                $usersMap[$uid] = [
                    'user_id'    => $uid,
                    'username'   => $v['username'],
                    'discord_id' => $v['discord_id'],
                    'avatar'     => $v['avatar'],
                ];
            }
        }

        // Sort by username
        usort($usersMap, fn($a, $b) => strcasecmp($a['username'], $b['username']));

        // Build a per-user, per-date map of vacation IDs
        $vacByUserDate = [];
        foreach ($vacations as $v) {
            $uid = (int)$v['user_id'];
            $vFrom = max($v['date_from'], $firstDate);
            $vTo   = min($v['date_to'], $lastDate);
            $cur = new \DateTimeImmutable($vFrom);
            $end = new \DateTimeImmutable($vTo);
            while ($cur <= $end) {
                $ds = $cur->format('Y-m-d');
                $vacByUserDate[$uid][$ds] = [
                    'id'        => (int)$v['id'],
                    'date_from' => $v['date_from'],
                    'date_to'   => $v['date_to'],
                    'note'      => $v['note'] ?? '',
                    'is_start'  => ($ds === $v['date_from']),
                    'is_end'    => ($ds === $v['date_to']),
                ];
                $cur = $cur->modify('+1 day');
            }
        }

        $this->render('admin/vacation', [
            'pageTitle'      => 'Dovolená',
            'dates'          => $dates,
            'monthStart'     => $monthStart->format('Y-m'),
            'monthLabel'     => $monthLabel,
            'prevMonth'      => $prevMonth,
            'nextMonth'      => $nextMonth,
            'users'          => $usersMap,
            'vacByUserDate'  => $vacByUserDate,
            'currentUserId'  => $currentUserId,
            'adminPerms'     => $this->getAdminPermissions(),
            'adminActive'    => 'vacation',
        ]);
    }

    /**
     * Save a new vacation (POST /admin/vacation/save).
     */
    public function vacationSave(): void
    {
        $this->requireAdminPermission(self::PERM_VACATION);
        $this->requirePost();
        $this->verifyCsrf();

        $userId   = (int)Permission::userId();
        $dateFrom = trim($_POST['date_from'] ?? '');
        $dateTo   = trim($_POST['date_to'] ?? '');
        $note     = trim($_POST['note'] ?? '');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            Session::flash('error', 'Neplatný formát data.');
            $this->redirect('/admin/vacation');
            return;
        }

        if ($dateTo < $dateFrom) {
            Session::flash('error', 'Datum do nemůže být před datem od.');
            $this->redirect('/admin/vacation');
            return;
        }

        try {
            $model = new VacationModel();
            $model->create($userId, $dateFrom, $dateTo, $note !== '' ? $note : null);
            Session::flash('success', 'Dovolená uložena.');
        } catch (\Throwable $e) {
            Logger::error('Vacation save failed: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se uložit dovolenou.');
        }

        $month = (new \DateTimeImmutable($dateFrom))->format('Y-m');
        $this->redirect('/admin/vacation?month=' . $month);
    }

    /**
     * Delete own vacation (POST /admin/vacation/delete).
     */
    public function vacationDelete(): void
    {
        $this->requireAdminPermission(self::PERM_VACATION);
        $this->requirePost();
        $this->verifyCsrf();

        $userId = (int)Permission::userId();
        $vacId  = (int)($_POST['vacation_id'] ?? 0);

        $model = new VacationModel();
        $vac   = $model->findById($vacId);

        if (!$vac) {
            Session::flash('error', 'Dovolená nenalezena.');
            $this->redirect('/admin/vacation');
            return;
        }

        // Vedení can delete anyone's, others only own
        $isVedeni = Permission::isVedeni();
        if ((int)$vac['user_id'] !== $userId && !$isVedeni) {
            Session::flash('error', 'Nemáš oprávnění smazat tuto dovolenou.');
            $this->redirect('/admin/vacation');
            return;
        }

        // For vedení deleting someone else's, use model method
        if ($isVedeni && (int)$vac['user_id'] !== $userId) {
            $model->deleteAny($vacId);
        } else {
            $model->delete($vacId, $userId);
        }

        Session::flash('success', 'Dovolená smazána.');
        $month = (new \DateTimeImmutable($vac['date_from']))->format('Y-m');
        $this->redirect('/admin/vacation?month=' . $month);
    }

    // =========================================================================
    // Security Overview
    // =========================================================================

    /**
     * Security log overview (GET /admin/security).
     */
    public function securityIndex(): void
    {
        $this->requireAdminPermission(self::PERM_SECURITY);

        $secModel = new SecurityLogModel();
        $filter   = $_GET['filter'] ?? 'all';
        $allowed  = ['all', 'critical', 'warning', 'info'];
        if (!in_array($filter, $allowed, true)) {
            $filter = 'all';
        }

        $logs   = $secModel->getAll($filter);
        $counts = $secModel->getCounts();

        $this->render('admin/security', [
            'pageTitle'   => 'Security logy',
            'logs'        => $logs,
            'counts'      => $counts,
            'filter'      => $filter,
            'adminPerms'  => $this->getAdminPermissions(),
            'adminActive' => 'security',
        ]);
    }

    /**
     * Resolve a security log entry (POST /admin/security/:id/resolve).
     */
    public function securityResolve(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_SECURITY);
        $this->requirePost();
        $this->verifyCsrf();

        $logId = (int)($params['id'] ?? 0);
        $secModel = new SecurityLogModel();
        $log = $secModel->findById($logId);

        if (!$log) {
            Session::flash('error', 'Záznam nenalezen.');
            $this->redirect('/admin/security');
        }

        try {
            $secModel->resolve($logId, (int)Permission::userId());
            Session::flash('success', 'Bezpečnostní záznam vyřešen.');
        } catch (\Throwable $e) {
            Logger::error("Security resolve failed for #{$logId}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se vyřešit záznam.');
        }

        $this->redirect('/admin/security');
    }

    // =========================================================================
    // Discord Bot Helpers
    // =========================================================================

    private function getBotVars(int $appId): ?array
    {
        $app = (new AllowlistModel())->findByIdWithUser($appId);
        if (!$app) return null;

        return [
            'username'   => $app['username'] ?? '',
            'discord_id' => $app['discord_id'] ?? '',
            'app_url'    => env('APP_URL', 'https://otrp.cz') . '/admin/' . $appId,
            'tester'     => Session::get('username', ''),
        ];
    }

    private function botOnApprove(int $appId): void
    {
        try {
            $settings = (new SiteSettingModel())->getMultiple([
                'bot_role_approved', 'bot_dm_approved',
                'bot_log_channel', 'bot_log_approved',
            ]);
            $vars = $this->getBotVars($appId);
            if (!$vars) return;

            if (!empty($settings['bot_role_approved'])) {
                DiscordBot::addRole($vars['discord_id'], $settings['bot_role_approved']);
            }
            if (!empty($settings['bot_dm_approved'])) {
                $msg = DiscordBot::formatMessage($settings['bot_dm_approved'], $vars);
                DiscordBot::sendDM($vars['discord_id'], $msg);
            }
            if (!empty($settings['bot_log_channel']) && !empty($settings['bot_log_approved'])) {
                $logMsg = DiscordBot::formatMessage($settings['bot_log_approved'], $vars);
                DiscordBot::sendChannelMessage($settings['bot_log_channel'], $logMsg);
            }
        } catch (\Throwable $e) {
            Logger::error("Bot onApprove for app #{$appId}: " . $e->getMessage());
        }
    }

    private function botOnReject(int $appId): void
    {
        try {
            $settings = (new SiteSettingModel())->getMultiple([
                'bot_dm_rejected',
                'bot_log_channel', 'bot_log_rejected',
            ]);
            $vars = $this->getBotVars($appId);
            if (!$vars) return;

            if (!empty($settings['bot_dm_rejected'])) {
                $msg = DiscordBot::formatMessage($settings['bot_dm_rejected'], $vars);
                DiscordBot::sendDM($vars['discord_id'], $msg);
            }
            if (!empty($settings['bot_log_channel']) && !empty($settings['bot_log_rejected'])) {
                $logMsg = DiscordBot::formatMessage($settings['bot_log_rejected'], $vars);
                DiscordBot::sendChannelMessage($settings['bot_log_channel'], $logMsg);
            }
        } catch (\Throwable $e) {
            Logger::error("Bot onReject for app #{$appId}: " . $e->getMessage());
        }
    }

    private function botOnInterviewPass(int $appId): void
    {
        try {
            $settings = (new SiteSettingModel())->getMultiple([
                'bot_role_approved', 'bot_role_allowlisted', 'bot_dm_allowlisted',
                'bot_log_channel', 'bot_log_interview_passed',
            ]);
            $vars = $this->getBotVars($appId);
            if (!$vars) return;

            // Remove the "approved" role if set, add "allowlisted" role
            if (!empty($settings['bot_role_approved'])) {
                DiscordBot::removeRole($vars['discord_id'], $settings['bot_role_approved']);
            }
            if (!empty($settings['bot_role_allowlisted'])) {
                DiscordBot::addRole($vars['discord_id'], $settings['bot_role_allowlisted']);
            }
            if (!empty($settings['bot_dm_allowlisted'])) {
                $msg = DiscordBot::formatMessage($settings['bot_dm_allowlisted'], $vars);
                DiscordBot::sendDM($vars['discord_id'], $msg);
            }
            if (!empty($settings['bot_log_channel']) && !empty($settings['bot_log_interview_passed'])) {
                $logMsg = DiscordBot::formatMessage($settings['bot_log_interview_passed'], $vars);
                DiscordBot::sendChannelMessage($settings['bot_log_channel'], $logMsg);
            }
        } catch (\Throwable $e) {
            Logger::error("Bot onInterviewPass for app #{$appId}: " . $e->getMessage());
        }
    }

    private function botOnInterviewFail(int $appId): void
    {
        try {
            $settings = (new SiteSettingModel())->getMultiple([
                'bot_dm_interview_failed',
                'bot_log_channel', 'bot_log_interview_failed',
            ]);
            $vars = $this->getBotVars($appId);
            if (!$vars) return;

            if (!empty($settings['bot_dm_interview_failed'])) {
                $msg = DiscordBot::formatMessage($settings['bot_dm_interview_failed'], $vars);
                DiscordBot::sendDM($vars['discord_id'], $msg);
            }
            if (!empty($settings['bot_log_channel']) && !empty($settings['bot_log_interview_failed'])) {
                $logMsg = DiscordBot::formatMessage($settings['bot_log_interview_failed'], $vars);
                DiscordBot::sendChannelMessage($settings['bot_log_channel'], $logMsg);
            }
        } catch (\Throwable $e) {
            Logger::error("Bot onInterviewFail for app #{$appId}: " . $e->getMessage());
        }
    }

    public function addQpBonus(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_QP_BONUS);
        if (!Permission::isVedeni()) {
            Session::flash('error', 'Nemáš oprávnění přidávat QP bonusy.');
            $this->redirect('/admin/players/' . (int)($params['id'] ?? 0));
            return;
        }
        $this->requirePost();
        $this->verifyCsrf();

        $id     = (int)($params['id'] ?? 0);
        $amount = (int)($_POST['amount'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        $expiresRaw = trim($_POST['expires_at'] ?? '');
        $expiresAt = null;
        if ($expiresRaw !== '') {
            try {
                $expiresAt = (new \DateTimeImmutable($expiresRaw))->format('Y-m-d H:i:s');
            } catch (\Throwable) {}
        }

        if ($amount === 0 || $reason === '') {
            Session::flash('error', 'Částka a důvod jsou povinné.');
            $this->redirect("/admin/players/{$id}");
            return;
        }

        if ($amount > 100_000 || $amount < -100_000) {
            Session::flash('error', 'Bonus nesmí přesáhnout ±100 000 QP.');
            $this->redirect("/admin/players/{$id}");
            return;
        }

        try {
            (new QpBonusModel())->add($id, $amount, $reason, $expiresAt, Permission::userId());
            Session::flash('success', 'QP bonus byl přidán.');
        } catch (\Throwable $e) {
            Logger::error('QP bonus add: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se přidat bonus.');
        }
        $this->redirect("/admin/players/{$id}");
    }

    public function deleteQpBonus(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_QP_BONUS);
        if (!Permission::isVedeni()) {
            Session::flash('error', 'Nemáš oprávnění odebírat QP bonusy.');
            $this->redirect('/admin/players/' . (int)($params['id'] ?? 0));
            return;
        }
        $this->requirePost();
        $this->verifyCsrf();

        $id      = (int)($params['id'] ?? 0);
        $bonusId = (int)($params['bonusId'] ?? 0);

        try {
            (new QpBonusModel())->delete($bonusId);
            Session::flash('success', 'QP bonus byl odebrán.');
        } catch (\Throwable $e) {
            Logger::error('QP bonus delete: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se odebrat bonus.');
        }
        $this->redirect("/admin/players/{$id}");
    }

    public function addCharBonus(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_CHAR_BONUS);
        if (!Permission::isVedeni()) {
            Session::flash('error', 'Nemáš oprávnění přidávat char bonusy.');
            $this->redirect('/admin/players/' . (int)($params['id'] ?? 0));
            return;
        }
        $this->requirePost();
        $this->verifyCsrf();

        $id         = (int)($params['id'] ?? 0);
        $amount     = (int)($_POST['amount'] ?? 0);
        $reason     = trim($_POST['reason'] ?? '');
        $expiresRaw = trim($_POST['expires_at'] ?? '');
        $expiresAt  = null;
        if ($expiresRaw !== '') {
            try {
                $expiresAt = (new \DateTimeImmutable($expiresRaw))->format('Y-m-d H:i:s');
            } catch (\Throwable) {}
        }

        if ($amount === 0 || $reason === '') {
            Session::flash('error', 'Částka a důvod jsou povinné.');
            $this->redirect("/admin/players/{$id}");
            return;
        }

        if ($amount > 15 || $amount < -15) {
            Session::flash('error', 'Bonus nesmí přesáhnout ±15 slotů.');
            $this->redirect("/admin/players/{$id}");
            return;
        }

        try {
            (new CharBonusModel())->add($id, $amount, $reason, $expiresAt, Permission::userId());
            Session::flash('success', 'Char slot bonus byl přidán.');
        } catch (\Throwable $e) {
            Logger::error('Char bonus add: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se přidat bonus.');
        }
        $this->redirect("/admin/players/{$id}");
    }

    public function deleteCharBonus(array $params = []): void
    {
        $this->requireAdminPermission(self::PERM_CHAR_BONUS);
        if (!Permission::isVedeni()) {
            Session::flash('error', 'Nemáš oprávnění odebírat char bonusy.');
            $this->redirect('/admin/players/' . (int)($params['id'] ?? 0));
            return;
        }
        $this->requirePost();
        $this->verifyCsrf();

        $id      = (int)($params['id'] ?? 0);
        $bonusId = (int)($params['bonusId'] ?? 0);

        try {
            (new CharBonusModel())->delete($bonusId);
            Session::flash('success', 'Char slot bonus byl odebrán.');
        } catch (\Throwable $e) {
            Logger::error('Char bonus delete: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se odebrat bonus.');
        }
        $this->redirect("/admin/players/{$id}");
    }

    /**
     * Check if the current user has the ingame.admin permission.
     */
    private function hasIngameAdmin(): bool
    {
        if (Permission::isVedeni()) {
            return true;
        }

        $roles   = Session::get('roles', []);
        $roles   = is_array($roles) ? array_values(array_map('strval', $roles)) : [];
        $roleIds = Permission::roleIds();
        $userId  = (int)Permission::userId();

        $keys = (new ManagementPermissionModel())->getPermissionKeysForUser($userId, $roles, $roleIds);
        return in_array('ingame.admin', $keys, true);
    }

    /**
     * GET /admin/settings
     * Show admin settings page (own settings for the logged-in admin).
     */
    public function settingsIndex(array $params = []): void
    {
        if (!$this->hasIngameAdmin()) {
            http_response_code(403);
            $this->render('errors/403', ['pageTitle' => '403']);
            return;
        }

        $userId   = (int)Permission::userId();
        $settings = (new AdminSettingModel())->getForUser($userId);

        $this->render('admin/settings', [
            'pageTitle'   => 'Nastavení admina',
            'settings'    => $settings,
            'adminPerms'  => $this->getAdminPermissions(),
            'adminActive' => 'settings',
        ]);
    }

    /**
     * POST /admin/settings/save
     * Save admin settings for the logged-in admin.
     */
    public function settingsSave(array $params = []): void
    {
        $this->requirePost();
        $this->verifyCsrf('/admin/settings');

        if (!$this->hasIngameAdmin()) {
            http_response_code(403);
            $this->render('errors/403', ['pageTitle' => '403']);
            return;
        }

        $userId             = (int)Permission::userId();
        $adminPrefixChat    = !empty($_POST['admin_prefix_chat']);
        $reportNotifications = !empty($_POST['report_notifications']);

        try {
            (new AdminSettingModel())->save($userId, $adminPrefixChat, $reportNotifications);
            Session::flash('success', 'Nastavení bylo uloženo.');
        } catch (\Throwable $e) {
            Logger::error('Admin settings save: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se uložit nastavení.');
        }

        $this->redirect('/admin/settings');
    }

}
