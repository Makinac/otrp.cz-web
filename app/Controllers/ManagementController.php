<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Permission;
use App\Core\Logger;
use App\Core\Session;
use App\Models\AllowlistModel;
use App\Models\AppealModel;
use App\Models\BlacklistModel;
use App\Models\ContentPageModel;
use App\Models\FormSchemaModel;
use App\Models\ManagementPermissionModel;
use App\Models\NewsModel;
use App\Models\CheatsheetModel;
use App\Models\RulesSectionModel;
use App\Models\SiteSettingModel;
use App\Models\TeamCacheModel;
use App\Models\TeamCategoryModel;
use App\Models\UserModel;
use App\Models\PartnerModel;
use App\Auth\DiscordOAuth;
use App\Models\QpRoleConfigModel;
use App\Models\CharRoleConfigModel;
use App\Models\PedRoleConfigModel;
use App\Models\RedeemCodeModel;
use App\Models\ApiKeyModel;
use App\Models\DiscordBotConfigModel;

/**
 * Management panel — Vedeni or delegated users (via management settings).
 */
class ManagementController extends BaseController
{
    private const ADMIN_PERM_ALLOWLIST             = 'admin.allowlist';
    private const ADMIN_PERM_ALLOWLIST_REINTERVIEW = 'admin.allowlist.reinterview';
    private const ADMIN_PERM_PLAYERS               = 'admin.players';
    private const ADMIN_PERM_PLAYERS_PUNISHMENTS   = 'admin.players.punishments';
    private const ADMIN_PERM_PLAYERS_ACCESS        = 'admin.players.access';
    private const ADMIN_PERM_PLAYERS_APPEALS       = 'admin.players.appeals';

    private const PERM_FORM      = 'management.form';
    private const PERM_CONTENT   = 'management.content';
    private const PERM_RULES     = 'management.rules';
    private const PERM_BLACKLIST = 'management.blacklist';
    private const PERM_APPEALS   = 'management.appeals';
    private const PERM_TEAM      = 'management.team';
    private const PERM_CHEATSHEET = 'management.cheatsheet';
    private const PERM_SETTINGS  = 'management.settings';
    private const PERM_PARTNERS        = 'management.partners';
    private const PERM_HOMEPAGE        = 'management.homepage';
    private const PERM_ALLOWLIST_STATS = 'management.allowlist_stats';
    private const PERM_QP              = 'management.qp';
    private const PERM_CHARS           = 'management.chars';
    private const PERM_PED             = 'management.ped';
    private const PERM_CODES           = 'management.codes';
    private const PERM_API_KEYS          = 'management.api_keys';
    private const PERM_DISCORD           = 'management.discord';
    private const PERM_INGAME_ADMIN      = 'ingame.admin';
    private const PERM_INGAME_MANAGEMENT = 'ingame.management';
    private const PERM_LIB_JOBSCREATOR   = 'lib.jobscreator';
    private const PERM_LIB_BLIPSCREATOR  = 'lib.blipscreator';
    private const PERM_LIB_SHOPSCREATOR  = 'lib.shopscreator';

    /**
     * @return array<string,string>
     */
    private function permissionLabels(): array
    {
        return [
            self::ADMIN_PERM_ALLOWLIST             => 'Admin: Allowlist',
            self::ADMIN_PERM_ALLOWLIST_REINTERVIEW => 'Admin: Repohovor',
            self::ADMIN_PERM_PLAYERS               => 'Admin: Hráči',
            self::ADMIN_PERM_PLAYERS_PUNISHMENTS   => 'Admin: Bany a warny',
            self::ADMIN_PERM_PLAYERS_ACCESS        => 'Admin: Dev/Maps přístup',
            self::ADMIN_PERM_PLAYERS_APPEALS       => 'Admin: Historie odvolání hráče',
            'admin.ck'                             => 'Admin: CK Hlasování',
            'admin.qp_bonus'                       => 'Admin: QP bonusy',
            self::PERM_FORM      => 'Formuláře',
            self::PERM_CONTENT   => 'Novinky',
            self::PERM_RULES     => 'Pravidla',
            self::PERM_BLACKLIST => 'Denylist',
            self::PERM_APPEALS   => 'Odvolání',
            self::PERM_TEAM        => 'Tým',
            self::PERM_CHEATSHEET  => 'Tahák',
            self::PERM_PARTNERS    => 'Partneři',
            self::PERM_HOMEPAGE         => 'Domovská stránka',
            self::PERM_ALLOWLIST_STATS  => 'Allowlist statistiky',
            self::PERM_QP               => 'QP konfigurace',
            self::PERM_CHARS            => 'Char sloty konfigurace',
            self::PERM_PED              => 'Ped Menu konfigurace',
            self::PERM_CODES            => 'Kódy (generovat/mazat)',
            'admin.char_bonus'          => 'Admin: Char slot bonusy',
            self::PERM_API_KEYS          => 'API Klíče',
            self::PERM_DISCORD           => 'Discord',
            self::PERM_INGAME_ADMIN      => 'In Game: Admin',
            self::PERM_INGAME_MANAGEMENT => 'In Game: Management',
            self::PERM_LIB_JOBSCREATOR   => 'Lib: Jobscreator',
            self::PERM_LIB_BLIPSCREATOR  => 'Lib: Blipscreator',
            self::PERM_LIB_SHOPSCREATOR  => 'Lib: Shopscreator',
            self::PERM_SETTINGS         => 'Nastavení práv',
        ];
    }

    /**
     * Returns permissions grouped into display sections for the settings view.
     *
     * @return array<string, array{label: string, perms: array<string, string>}>
     */
    private function permissionGroupedLabels(): array
    {
        return [
            'web' => [
                'label' => 'Webový',
                'perms' => [
                    self::PERM_FORM             => 'Formuláře',
                    self::PERM_CONTENT          => 'Novinky',
                    self::PERM_RULES            => 'Pravidla',
                    self::PERM_BLACKLIST        => 'Denylist',
                    self::PERM_APPEALS          => 'Odvolání',
                    self::PERM_TEAM             => 'Tým',
                    self::PERM_CHEATSHEET       => 'Tahák',
                    self::PERM_PARTNERS         => 'Partneři',
                    self::PERM_HOMEPAGE         => 'Domovská stránka',
                    self::PERM_ALLOWLIST_STATS  => 'Allowlist statistiky',
                    self::PERM_QP               => 'QP konfigurace',
                    self::PERM_CHARS            => 'Char sloty konfigurace',
                    self::PERM_PED              => 'Ped Menu konfigurace',
                    self::PERM_CODES            => 'Kódy (generovat/mazat)',
                    self::PERM_API_KEYS         => 'API Klíče',
                    self::PERM_DISCORD          => 'Discord',
                    self::PERM_SETTINGS         => 'Nastavení práv',
                    self::ADMIN_PERM_ALLOWLIST             => 'Admin: Allowlist',
                    self::ADMIN_PERM_ALLOWLIST_REINTERVIEW => 'Admin: Repohovor',
                    self::ADMIN_PERM_PLAYERS               => 'Admin: Hráči',
                    self::ADMIN_PERM_PLAYERS_PUNISHMENTS   => 'Admin: Bany a warny',
                    self::ADMIN_PERM_PLAYERS_ACCESS        => 'Admin: Dev/Maps přístup',
                    self::ADMIN_PERM_PLAYERS_APPEALS       => 'Admin: Historie odvolání hráče',
                    'admin.ck'                             => 'Admin: CK Hlasování',
                    'admin.qp_bonus'                       => 'Admin: QP bonusy',
                    'admin.char_bonus'                     => 'Admin: Char slot bonusy',
                ],
            ],
            'rsg' => [
                'label' => 'RSG',
                'perms' => [
                    self::PERM_INGAME_ADMIN      => 'Admin Pravomoce',
                    self::PERM_INGAME_MANAGEMENT => 'Management Pravomoce',
                ],
            ],
            'lib' => [
                'label' => 'LIB',
                'perms' => [],
                'subcategories' => [
                    'dev' => [
                        'label' => 'Dev',
                        'perms' => [
                            self::PERM_LIB_JOBSCREATOR  => 'Jobs',
                            self::PERM_LIB_BLIPSCREATOR => 'Blips',
                            self::PERM_LIB_SHOPSCREATOR => 'Shops',
                        ],
                    ],
                    'admin' => [
                        'label' => 'Admin',
                        'perms' => [],
                    ],
                ],
            ],
            'benefits' => [
                'label' => 'Výhody',
                'perms' => [],
                'custom' => 'benefits',
            ],
        ];
    }

    // =========================================================================
    // Dashboard
    // =========================================================================

    /**
    * Management panel home.
     *
     * @param array<string,string> $params Route parameters (unused).
     */
    public function index(array $params = []): void
    {
        $perms = $this->getManagementPermissions();
        if (!in_array(true, $perms, true)) {
            http_response_code(403);
            $this->render('errors/403', ['pageTitle' => '403']);
            return;
        }

        $this->render('management/index', [
            'pageTitle'        => 'Management',
            'managementActive' => 'home',
            'managementPerms'  => $perms,
        ]);
    }

    // =========================================================================
    // Form Schema Editor
    // =========================================================================

    /**
     * List all form schemas.
     *
     * @param array<string,string> $params Route parameters (unused).
     */
    public function formList(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_FORM);
        $model   = new FormSchemaModel();
        $schemas = $model->getAll();

        $this->render('management/form_list', [
            'pageTitle'        => 'Management',
            'schemas'          => $schemas,
            'managementActive' => 'form',
            'managementPerms'  => $this->getManagementPermissions(),
        ]);
    }

    /**
     * Show form schema editor (GET — create new or edit existing).
     *
     * @param array<string,string> $params Route parameters — optional :id.
     */
    public function formEdit(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_FORM);
        $model  = new FormSchemaModel();
        $id     = isset($params['id']) ? (int)$params['id'] : null;
        $schema = $id ? $model->findById($id) : null;
        $fields = $schema ? (json_decode($schema['fields_json'], true) ?? []) : [];

        $this->render('management/form_edit', [
            'pageTitle'        => 'Management',
            'schema'           => $schema,
            'fields'           => $fields,
            'managementActive' => 'form',
            'managementPerms'  => $this->getManagementPermissions(),
        ]);
    }

    /**
     * Save a form schema (POST — create or update).
     *
     * @param array<string,string> $params Route parameters — optional :id.
     */
    public function formSave(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_FORM);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id     = isset($params['id']) ? (int)$params['id'] : null;
        $name   = trim($_POST['name'] ?? '');
        $active = isset($_POST['active']);

        if (empty($name)) {
            Session::flash('error', 'Název schématu je povinný.');
            $this->redirect($id ? "/management/form/{$id}/edit" : '/management/form/new');
        }

        // Build fields array from POST.
        $fieldNames    = $_POST['field_name']     ?? [];
        $fieldLabels   = $_POST['field_label']    ?? [];
        $fieldTypes    = $_POST['field_type']     ?? [];
        $fieldRequired = $_POST['field_required'] ?? [];
        $fieldOptions  = $_POST['field_options']  ?? [];

        $fields = [];
        foreach ($fieldNames as $idx => $fname) {
            $fname = trim($fname);
            if (empty($fname)) {
                continue;
            }
            $fields[] = [
                'name'     => $fname,
                'label'    => trim($fieldLabels[$idx] ?? $fname),
                'type'     => $fieldTypes[$idx] ?? 'text',
                'required' => isset($fieldRequired[$idx]),
                'options'  => trim($fieldOptions[$idx] ?? ''),
            ];
        }

        $model = new FormSchemaModel();

        try {
            if ($id) {
                $model->update($id, $name, $fields, $active);
                Session::flash('success', 'Formulář byl aktualizován.');
            } else {
                $model->create($name, $fields, $active);
                Session::flash('success', 'Formulář byl vytvořen.');
            }
        } catch (\Throwable $e) {
            Logger::error("Form schema save failed: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se uložit formulář.');
        }

        $this->redirect('/management/form');
    }

    /**
     * Delete a form schema (POST).
     *
     * @param array<string,string> $params Route parameters — expects :id.
     */
    public function formDelete(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_FORM);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id    = (int)($params['id'] ?? 0);
        $model = new FormSchemaModel();

        try {
            $model->delete($id);
            Session::flash('success', 'Formulář byl smazán.');
        } catch (\Throwable $e) {
            Logger::error("Form schema delete failed for #{$id}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se smazat formulář.');
        }

        $this->redirect('/management/form');
    }

    /**
     * Activate a form schema (POST).
     *
     * @param array<string,string> $params Route parameters — expects :id.
     */
    public function formActivate(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_FORM);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id    = (int)($params['id'] ?? 0);
        $model = new FormSchemaModel();

        try {
            $model->activate($id);
            Session::flash('success', 'Formulář byl aktivován.');
        } catch (\Throwable $e) {
            Logger::error("Form schema activate failed for #{$id}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se aktivovat formulář.');
        }

        $this->redirect('/management/form');
    }

    // =========================================================================
    // Content Editor (News, Dev Log, Rules)
    // =========================================================================

    /**
     * List all news/devlog items and show rules editor.
     *
     * @param array<string,string> $params Route parameters (unused).
     */
    public function contentList(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_CONTENT);
        $newsModel = new NewsModel();

        $this->render('management/content_list', [
            'pageTitle'        => 'Management',
            'items'            => $newsModel->getPaginated(1, 50),
            'managementActive' => 'content',
            'managementPerms'  => $this->getManagementPermissions(),
        ]);
    }

    /**
     * Show news/devlog item editor (GET).
     *
     * @param array<string,string> $params Optional :id.
     */
    public function contentEdit(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_CONTENT);
        $id   = isset($params['id']) ? (int)$params['id'] : null;
        $item = null;

        if ($id) {
            $model = new NewsModel();
            $item  = $model->findById($id);
        }

        $newsModel  = new NewsModel();
        $categories = $newsModel->getCategories();

        $this->render('management/content_edit', [
            'pageTitle'        => 'Management',
            'item'             => $item,
            'categories'       => $categories,
            'managementActive' => 'content',
            'managementPerms'  => $this->getManagementPermissions(),
        ]);
    }

    /**
     * Save a news/devlog item (POST).
     *
     * @param array<string,string> $params Optional :id.
     */
    public function contentSave(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_CONTENT);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id            = isset($params['id']) ? (int)$params['id'] : null;
        $title         = trim($_POST['title']          ?? '');
        $bodyHtml      = $_POST['body_html']           ?? '';
        $category      = trim($_POST['category']       ?? 'Novinka');
        $categoryColor = trim($_POST['category_color'] ?? '#cc0000');
        $authorId      = Permission::userId();

        if (empty($title)) {
            Session::flash('error', 'Titulek je povinný.');
            $this->redirect($id ? "/management/content/{$id}/edit" : '/management/content/new');
        }

        if (empty($category)) {
            $category = 'Novinka';
        }

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $categoryColor)) {
            $categoryColor = '#cc0000';
        }

        $model = new NewsModel();

        try {
            if ($id) {
                $item = $model->findById($id);
                $slug = $item ? $item['slug'] : $model->generateSlug($title);
                $model->update($id, $title, $slug, $bodyHtml, $category, $categoryColor);
                Session::flash('success', 'Příspěvek byl aktualizován.');
            } else {
                $slug = $model->generateSlug($title);
                $model->create($title, $slug, $bodyHtml, $authorId, $category, $categoryColor);
                Session::flash('success', 'Příspěvek byl vytvořen.');
            }
        } catch (\Throwable $e) {
            Logger::error("Content save failed: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se uložit příspěvek.');
        }

        $this->redirect('/management/content');
    }

    /**
     * Delete a news/devlog item (POST).
     *
     * @param array<string,string> $params Route parameters — expects :id.
     */
    public function contentDelete(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_CONTENT);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id    = (int)($params['id'] ?? 0);
        $model = new NewsModel();

        try {
            $model->delete($id);
            Session::flash('success', 'Příspěvek byl smazán.');
        } catch (\Throwable $e) {
            Logger::error("Content delete failed for #{$id}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se smazat příspěvek.');
        }

        $this->redirect('/management/content');
    }

    /**
     * List all rules sections (admin).
     *
     * @param array<string,string> $params Route parameters (unused).
     */
    public function rulesIndex(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_RULES);
        $model    = new RulesSectionModel();
        $sections = $model->getAll();

        $settings = new SiteSettingModel();
        $rpBlocks = json_decode($settings->get('rp_blocks', '[]'), true) ?: [];

        $this->render('management/rules_list', [
            'pageTitle'        => 'Management',
            'sections'         => $sections,
            'rpBlocks'         => $rpBlocks,
            'managementActive' => 'rules',
            'managementPerms'  => $this->getManagementPermissions(),
        ]);
    }

    /**
     * Show the rules section editor (GET — create new or edit existing).
     *
     * @param array<string,string> $params Optional :id.
     */
    public function rulesEdit(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_RULES);
        $model   = new RulesSectionModel();
        $id      = isset($params['id']) ? (int)$params['id'] : null;
        $section = $id ? $model->findById($id) : null;

        $settings = new SiteSettingModel();
        $rpBlocks = json_decode($settings->get('rp_blocks', '[]'), true) ?: [];

        $this->render('management/rules_edit', [
            'pageTitle'        => 'Management',
            'section'          => $section,
            'rpBlocks'         => $rpBlocks,
            'managementActive' => 'rules',
            'managementPerms'  => $this->getManagementPermissions(),
        ]);
    }

    /**
     * Save a rules section (POST — create or update).
     *
     * @param array<string,string> $params Optional :id.
     */
    public function rulesSectionSave(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_RULES);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id        = isset($params['id']) ? (int)$params['id'] : null;
        $title     = trim($_POST['title']     ?? '');
        $bodyHtml  = $_POST['body_html']      ?? '';
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $userId    = Permission::userId();

        if (empty($title)) {
            Session::flash('error', 'Název kategorie je povinný.');
            $this->redirect($id ? "/management/rules/{$id}/edit" : '/management/rules/new');
        }

        $model = new RulesSectionModel();

        try {
            if ($id) {
                $model->update($id, $title, $bodyHtml, $sortOrder, $userId);
                Session::flash('success', 'Kategorie byla aktualizována.');
            } else {
                $auto = $model->getMaxSortOrder() + 1;
                $model->create($title, $bodyHtml, $sortOrder ?: $auto, $userId);
                Session::flash('success', 'Kategorie byla vytvořena.');
            }
        } catch (\Throwable $e) {
            Logger::error('Rules section save failed: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se uložit kategorii.');
        }

        $this->redirect('/management/rules');
    }

    /**
     * Delete a rules section (POST).
     *
     * @param array<string,string> $params Expects :id.
     */
    public function rulesSectionDelete(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_RULES);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id    = (int)($params['id'] ?? 0);
        $model = new RulesSectionModel();

        try {
            $model->delete($id);
            Session::flash('success', 'Kategorie byla smazána.');
        } catch (\Throwable $e) {
            Logger::error("Rules section delete failed for #{$id}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se smazat kategorii.');
        }

        $this->redirect('/management/rules');
    }

    /**
     * Move a rules section up or down in sort order (POST).
     *
     * @param array<string,string> $params Expects :id; POST field `direction` = up|down.
     */
    public function rulesSectionMove(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_RULES);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id        = (int)($params['id'] ?? 0);
        $direction = $_POST['direction'] ?? 'up';
        $model     = new RulesSectionModel();

        try {
            if ($direction === 'up') {
                $model->moveUp($id);
            } else {
                $model->moveDown($id);
            }
            $model->renumber();
        } catch (\Throwable $e) {
            Logger::error("Rules section move failed for #{$id}: " . $e->getMessage());
        }

        $this->redirect('/management/rules');
    }

    /**
     * Save custom RP blocks (POST — JSON stored in site_settings).
     */
    public function rpBlocksSave(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_RULES);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $raw = $_POST['rp_blocks'] ?? '[]';
        $blocks = json_decode($raw, true);

        if (!is_array($blocks)) {
            $blocks = [];
        }

        // Sanitise each block
        $clean = [];
        $validRadii = ['0', '3', '4', '6', '8', '12', '999'];
        foreach ($blocks as $b) {
            $name = trim($b['name'] ?? '');
            $color = trim($b['color'] ?? '#7c3aed');
            $desc = trim($b['description'] ?? '');
            $hasBg = !empty($b['hasBg']);
            $bgColor = trim($b['bgColor'] ?? $color);
            $badgeRadius = (string)($b['badgeRadius'] ?? '3');
            $bgRadius = (string)($b['bgRadius'] ?? '4');

            if ($name === '') continue;
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                $color = '#7c3aed';
            }
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $bgColor)) {
                $bgColor = $color;
            }
            if (!in_array($badgeRadius, $validRadii, true)) {
                $badgeRadius = '3';
            }
            if (!in_array($bgRadius, $validRadii, true)) {
                $bgRadius = '4';
            }

            $clean[] = [
                'name'        => mb_substr($name, 0, 30),
                'color'       => $color,
                'description' => mb_substr($desc, 0, 200),
                'hasBg'       => $hasBg,
                'bgColor'     => $bgColor,
                'badgeRadius' => $badgeRadius,
                'bgRadius'    => $bgRadius,
            ];
        }

        $settings = new SiteSettingModel();
        $settings->set('rp_blocks', json_encode($clean, JSON_UNESCAPED_UNICODE));

        Session::flash('success', 'RP bloky uloženy.');
        $this->redirect('/management/rules');
    }

    // =========================================================================
    // Cheatsheet (Tahák) Management
    // =========================================================================

    public function cheatsheetIndex(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_CHEATSHEET);
        $model    = new CheatsheetModel();
        $sections = $model->getAll();

        $this->render('management/cheatsheet_list', [
            'pageTitle'        => 'Management',
            'sections'         => $sections,
            'managementActive' => 'cheatsheet',
            'managementPerms'  => $this->getManagementPermissions(),
        ]);
    }

    public function cheatsheetEdit(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_CHEATSHEET);
        $model   = new CheatsheetModel();
        $id      = isset($params['id']) ? (int)$params['id'] : null;
        $section = $id ? $model->findById($id) : null;

        $this->render('management/cheatsheet_edit', [
            'pageTitle'        => 'Management',
            'section'          => $section,
            'managementActive' => 'cheatsheet',
            'managementPerms'  => $this->getManagementPermissions(),
        ]);
    }

    public function cheatsheetSave(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_CHEATSHEET);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id        = isset($params['id']) ? (int)$params['id'] : null;
        $title     = trim($_POST['title']     ?? '');
        $bodyHtml  = $_POST['body_html']      ?? '';
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $userId    = Permission::userId();

        if (empty($title)) {
            Session::flash('error', 'Název otázky je povinný.');
            $this->redirect($id ? "/management/cheatsheet/{$id}/edit" : '/management/cheatsheet/new');
        }

        $model = new CheatsheetModel();

        try {
            if ($id) {
                $model->update($id, $title, $bodyHtml, $sortOrder, $userId);
                Session::flash('success', 'Otázka byla aktualizována.');
            } else {
                $auto = $model->getMaxSortOrder() + 1;
                $model->create($title, $bodyHtml, $sortOrder ?: $auto, $userId);
                Session::flash('success', 'Otázka byla vytvořena.');
            }
        } catch (\Throwable $e) {
            Logger::error('Cheatsheet save failed: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se uložit otázku.');
        }

        $this->redirect('/management/cheatsheet');
    }

    public function cheatsheetDelete(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_CHEATSHEET);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id    = (int)($params['id'] ?? 0);
        $model = new CheatsheetModel();

        try {
            $model->delete($id);
            Session::flash('success', 'Otázka byla smazána.');
        } catch (\Throwable $e) {
            Logger::error("Cheatsheet delete failed for #{$id}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se smazat otázku.');
        }

        $this->redirect('/management/cheatsheet');
    }

    public function cheatsheetMove(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_CHEATSHEET);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id        = (int)($params['id'] ?? 0);
        $direction = $_POST['direction'] ?? 'up';
        $model     = new CheatsheetModel();

        try {
            if ($direction === 'up') {
                $model->moveUp($id);
            } else {
                $model->moveDown($id);
            }
            $model->renumber();
        } catch (\Throwable $e) {
            Logger::error("Cheatsheet move failed for #{$id}: " . $e->getMessage());
        }

        $this->redirect('/management/cheatsheet');
    }

    // =========================================================================
    // Blacklist Management
    // =========================================================================

    /**
     * Show the blacklist management page.
     *
     * @param array<string,string> $params Route parameters (unused).
     */
    public function blacklistIndex(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_BLACKLIST);
        $model = new BlacklistModel();

        $this->render('management/blacklist', [
            'pageTitle'        => 'Management',
            'entries'          => $model->getAll(),
            'managementActive' => 'blacklist',
            'managementPerms'  => $this->getManagementPermissions(),
        ]);
    }

    /**
     * Add a Discord user to the blacklist (POST).
     *
     * @param array<string,string> $params Route parameters (unused).
     */
    public function blacklistAdd(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_BLACKLIST);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $discordId = trim($_POST['discord_id'] ?? '');
        $name      = trim($_POST['name'] ?? '') ?: null;
        $reason    = trim($_POST['reason'] ?? '') ?: null;
        $adminId   = Permission::userId();

        if (empty($discordId)) {
            Session::flash('error', 'Discord ID je povinné.');
            $this->redirect('/management/denylist');
        }

        $model = new BlacklistModel();

        try {
            $model->add($discordId, $adminId, $name, $reason);
            Session::flash('success', "Discord ID {$discordId} bylo přidáno na denylist.");
        } catch (\Throwable $e) {
            Logger::error("Blacklist add failed: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se přidat na denylist.');
        }

        $this->redirect('/management/denylist');
    }

    /**
     * Remove a user from the blacklist (POST).
     *
     * @param array<string,string> $params Route parameters — expects :userId.
     */
    public function blacklistRemove(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_BLACKLIST);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id    = (int)($params['id'] ?? 0);
        $model = new BlacklistModel();

        try {
            $model->remove($id);
            Session::flash('success', 'Záznam byl odebrán z denylistu.');
        } catch (\Throwable $e) {
            Logger::error("Blacklist remove failed for entry #{$id}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se odebrat z denylistu.');
        }

        $this->redirect('/management/denylist');
    }

    // =========================================================================
    // Appeals Queue
    // =========================================================================

    /**
     * Show all pending appeals.
     *
     * @param array<string,string> $params Route parameters (unused).
     */
    public function appealsIndex(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_APPEALS);
        $model = new AppealModel();

        $this->render('management/appeals', [
            'pageTitle'        => 'Management',
            'appeals'          => $model->getAllPending(),
            'history'          => $model->getAllResolved(),
            'managementActive' => 'appeals',
            'managementPerms'  => $this->getManagementPermissions(),
        ]);
    }

    /**
     * Approve an appeal (POST).
     *
     * @param array<string,string> $params Route parameters — expects :id.
     */
    public function appealApprove(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_APPEALS);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id          = (int)($params['id'] ?? 0);
        $reviewerId  = Permission::userId();
        $appealModel = new AppealModel();
        $appeal      = $appealModel->findById($id);

        if (!$appeal) {
            Session::flash('error', 'Odvolání nenalezeno.');
            $this->redirect('/management/appeals');
        }

        try {
            $appealModel->approve($id, $reviewerId);

            // If it's a blacklist appeal — remove from blacklist.
            if ($appeal['type'] === 'blacklist') {
                $blModel = new BlacklistModel();
                $blModel->remove((int)$appeal['user_id']);
            }

            Session::flash('success', 'Odvolání bylo schváleno.');
        } catch (\Throwable $e) {
            Logger::error("Appeal approve failed for #{$id}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se schválit odvolání.');
        }

        $this->redirect('/management/appeals');
    }

    /**
     * Reject an appeal (POST).
     *
     * @param array<string,string> $params Route parameters — expects :id.
     */
    public function appealReject(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_APPEALS);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id         = (int)($params['id'] ?? 0);
        $reviewerId = Permission::userId();
        $model      = new AppealModel();

        try {
            $model->reject($id, $reviewerId);
            Session::flash('success', 'Odvolání bylo zamítnuto.');
        } catch (\Throwable $e) {
            Logger::error("Appeal reject failed for #{$id}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se zamítnout odvolání.');
        }

        $this->redirect('/management/appeals');
    }

    // =========================================================================
    // Team Management
    // =========================================================================

    public function teamList(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_TEAM);

        $catModel   = new TeamCategoryModel();
        $cacheModel = new TeamCacheModel();

        // Resolve role names for display.
        $knownRoleNames = [];
        try {
            $guildRoles = DiscordOAuth::getGuildRoles();
            foreach ($guildRoles as $r) {
                $knownRoleNames[$r['id']] = $r['name'];
            }
        } catch (\Throwable $e) {
            Logger::error('Team: failed to fetch guild roles: ' . $e->getMessage());
        }

        // Cache age.
        $cacheAge = null;
        $members  = $cacheModel->getAll();
        if (!empty($members)) {
            $oldest   = min(array_column($members, 'cached_at'));
            $diff     = time() - strtotime($oldest);
            $hours    = (int)($diff / 3600);
            $minutes  = (int)(($diff % 3600) / 60);
            $cacheAge = $hours > 0 ? "{$hours}h {$minutes}m zpět" : "{$minutes}m zpět";
        }

        $this->render('management/team_list', [
            'pageTitle'        => 'Management',
            'managementActive' => 'team',
            'managementPerms'  => $this->getManagementPermissions(),
            'categories'       => $catModel->getAll(),
            'cachedMembers'    => $members,
            'knownRoleNames'   => $knownRoleNames,
            'cacheAge'         => $cacheAge,
        ]);
    }

    public function teamEdit(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_TEAM);

        $id       = isset($params['id']) ? (int)$params['id'] : null;
        $model    = new TeamCategoryModel();
        $category = $id ? $model->findById($id) : null;

        // Fetch all guild roles for the picker.
        $allRoles = [];
        try {
            $allRoles = DiscordOAuth::getGuildRoles();
        } catch (\Throwable $e) {
            Logger::error('Team edit: failed to fetch guild roles: ' . $e->getMessage());
        }

        $this->render('management/team_edit', [
            'pageTitle'        => 'Management',
            'managementActive' => 'team',
            'managementPerms'  => $this->getManagementPermissions(),
            'category'         => $category,
            'allRoles'         => $allRoles,
        ]);
    }

    public function teamSave(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_TEAM);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id      = isset($params['id']) ? (int)$params['id'] : null;
        $name    = trim($_POST['name'] ?? '');
        $roleIds = array_map('strval', (array)($_POST['role_ids'] ?? []));
        $color   = trim($_POST['color'] ?? '');
        $color   = preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : null;

        if (empty($name)) {
            Session::flash('error', 'Název kategorie je povinný.');
            $this->redirect($id ? "/management/team/{$id}/edit" : '/management/team/new');
            return;
        }

        $model = new TeamCategoryModel();

        try {
            if ($id) {
                $model->update($id, $name, $roleIds, $color);
                Session::flash('success', 'Kategorie byla aktualizována.');
            } else {
                $allCats   = $model->getAll();
                $sortOrder = count($allCats) + 1;
                $model->create($name, $roleIds, $sortOrder, $color);
                Session::flash('success', 'Kategorie byla vytvořena.');
            }
        } catch (\Throwable $e) {
            Logger::error('Team category save failed: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se uložit kategorii.');
        }

        $this->redirect('/management/team');
    }

    public function teamDelete(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_TEAM);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id = (int)($params['id'] ?? 0);

        try {
            (new TeamCategoryModel())->delete($id);
            Session::flash('success', 'Kategorie byla smazána.');
        } catch (\Throwable $e) {
            Logger::error("Team category delete failed for #{$id}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se smazat kategorii.');
        }

        $this->redirect('/management/team');
    }

    public function teamMove(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_TEAM);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id        = (int)($params['id'] ?? 0);
        $direction = $_POST['direction'] ?? 'up';
        $model     = new TeamCategoryModel();

        try {
            if ($direction === 'up') {
                $model->moveUp($id);
            } else {
                $model->moveDown($id);
            }
            $model->renumber();
        } catch (\Throwable $e) {
            Logger::error("Team category move failed for #{$id}: " . $e->getMessage());
        }

        $this->redirect('/management/team');
    }

    /**
     * Force-refresh Discord member cache for all team categories.
     */
    public function teamRefresh(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_TEAM);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $this->refreshTeamCache();
        Session::flash('success', 'Data týmu byla obnovena z Discordu.');
        $this->redirect('/management/team');
    }

    /**
     * Fetch Discord members for all team categories and update cache.
     */
    private function refreshTeamCache(): void
    {
        $catModel   = new TeamCategoryModel();
        $cacheModel = new TeamCacheModel();
        $categories = $catModel->getAll();

        if (empty($categories)) {
            return;
        }

        // Collect all unique role IDs across categories.
        $allRoleIds = [];
        foreach ($categories as $cat) {
            $catRoles   = json_decode($cat['role_ids_json'] ?? '[]', true) ?? [];
            $allRoleIds = array_merge($allRoleIds, $catRoles);
        }
        $allRoleIds = array_unique(array_map('strval', $allRoleIds));

        if (empty($allRoleIds)) {
            return;
        }

        try {
            $rawMembers = DiscordOAuth::getGuildMembersWithRoleIds($allRoleIds);
        } catch (\Throwable $e) {
            Logger::error('Team cache refresh failed: ' . $e->getMessage());
            return;
        }

        // Sort members into categories.
        $categoryMembers = [];
        foreach ($categories as $cat) {
            $catRoles = array_map('strval', json_decode($cat['role_ids_json'] ?? '[]', true) ?? []);
            $members  = [];

            foreach ($rawMembers as $m) {
                $memberRoles = array_map('strval', $m['roles'] ?? []);
                if (array_intersect($catRoles, $memberRoles)) {
                    $user      = $m['user'] ?? [];
                    $avatarHash = $user['avatar'] ?? null;
                    $avatarUrl  = $avatarHash
                        ? "https://cdn.discordapp.com/avatars/{$user['id']}/{$avatarHash}.png"
                        : 'https://cdn.discordapp.com/embed/avatars/0.png';

                    $members[] = [
                        'discord_id' => (string)($user['id'] ?? ''),
                        'username'   => (string)($user['global_name'] ?? $user['username'] ?? ''),
                        'avatar_url' => $avatarUrl,
                        'roles'      => $m['_role_names'] ?? [],
                    ];
                }
            }

            $categoryMembers[] = [
                'category_id' => (int)$cat['id'],
                'members'     => $members,
            ];
        }

        $cacheModel->refreshAll($categoryMembers);
    }

    // =========================================================================
    // Allowlist Statistics
    // =========================================================================

    /**
     * Per-tester allowlist statistics.
     */
    public function allowlistStats(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_ALLOWLIST_STATS);

        $allowedPeriods = ['7' => '7 dní', '30' => '30 dní', '90' => '90 dní', '0' => 'Vše'];
        $period = (string)($_GET['period'] ?? '30');
        if (!isset($allowedPeriods[$period])) {
            $period = '30';
        }
        $days = (int)$period;

        $model       = new AllowlistModel();
        $testerStats = $model->getTesterStats($days);

        $totals = [
            'total_forms'       => 0,
            'forms_approved'    => 0,
            'forms_rejected'    => 0,
            'total_interviews'  => 0,
            'interviews_passed' => 0,
            'interviews_failed' => 0,
        ];
        foreach ($testerStats as $t) {
            foreach (array_keys($totals) as $k) {
                $totals[$k] += ($t[$k] ?? 0);
            }
        }

        $this->render('management/allowlist_stats', [
            'pageTitle'      => 'Management',
            'managementActive' => 'allowlist_stats',
            'managementPerms'  => $this->getManagementPermissions(),
            'testerStats'    => $testerStats,
            'totals'         => $totals,
            'period'         => $period,
            'periodLabel'    => $allowedPeriods[$period],
            'periodOptions'  => $allowedPeriods,
        ]);
    }

    // =========================================================================
    // Discord Settings
    // =========================================================================

    /**
     * Discord settings page — automation (site_settings) + bot runtime config.
     */
    public function discordIndex(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_DISCORD);

        $settingModel   = new SiteSettingModel();
        $botConfigModel = new DiscordBotConfigModel();

        $automationKeys = [
            'bot_role_approved',
            'bot_role_allowlisted',
            'bot_dm_approved',
            'bot_dm_allowlisted',
            'bot_dm_rejected',
            'bot_dm_interview_failed',
            'bot_log_channel',
            'bot_log_approved',
            'bot_log_rejected',
            'bot_log_interview_passed',
            'bot_log_interview_failed',
        ];

        $botConfigKeys = [
            // Obecné
            'clen_role_id',
            'mod_log_channel',
            'mute_role_id',
            // Ticket systém
            'ticket_log_channel',
            'transcript_channel',
            'ticket_category_admin',
            'ticket_category_dev',
            'ticket_category_faction',
            'ticket_category_vedeni',
            'ticket_category_premium',
            'ticket_category_closed',
            'staff_roles_admin',
            'staff_roles_dev',
            'staff_roles_faction',
            'staff_roles_vedeni',
            'staff_roles_premium',
            'premium_member_roles',
            // Server statistiky
            'stats_channel_total',
            'stats_channel_al',
            'stats_channel_interview',
            'stats_channel_redm',
            'stats_format_total',
            'stats_format_al',
            'stats_format_interview',
            'stats_format_redm',
            'stats_role_al',
            'stats_role_interview',
            'stats_redm_url',
        ];

        $automationSettings = $settingModel->getMultiple($automationKeys);

        // Načti kategorie ticketů nejdříve, abychom mohli sestavit dynamický seznam klíčů
        $defaultCategories = [
            ['slug' => 'admin',   'label' => 'Admin Ticket',   'emoji' => '🔵', 'color' => '3498DB'],
            ['slug' => 'dev',     'label' => 'Dev Ticket',     'emoji' => '🟠', 'color' => 'E67E22'],
            ['slug' => 'faction', 'label' => 'Faction Ticket', 'emoji' => '🟣', 'color' => '9B59B6'],
            ['slug' => 'vedeni',  'label' => 'Vedení Ticket',  'emoji' => '🔴', 'color' => 'E74C3C'],
            ['slug' => 'premium', 'label' => 'Premium Ticket', 'emoji' => '⭐', 'color' => 'F1C40F'],
        ];
        $ticketCategories = $botConfigModel->getJson('ticket_categories', $defaultCategories);
        if (empty($ticketCategories)) {
            $ticketCategories = $defaultCategories;
        }

        // Přidej dynamické klíče pro kategorie ticketů (+ nové per-category klíče)
        $dynamicTicketKeys = [];
        foreach ($ticketCategories as $cat) {
            $slug = $cat['slug'] ?? '';
            if ($slug !== '') {
                $dynamicTicketKeys[] = 'ticket_category_' . $slug;
                $dynamicTicketKeys[] = 'ticket_closed_category_' . $slug;
                $dynamicTicketKeys[] = 'staff_roles_' . $slug;
                $dynamicTicketKeys[] = 'creator_roles_' . $slug;
                $dynamicTicketKeys[] = 'ticket_close_action_' . $slug;
                $dynamicTicketKeys[] = 'ticket_embed_title_' . $slug;
                $dynamicTicketKeys[] = 'ticket_embed_description_' . $slug;
                $dynamicTicketKeys[] = 'ticket_embed_footer_' . $slug;
            }
        }

        $botConfigKeys = array_merge($botConfigKeys, $dynamicTicketKeys);
        $botConfig = $botConfigModel->getMultiple($botConfigKeys);

        $discordRoles         = DiscordOAuth::getGuildRoles();
        $discordChannels      = DiscordOAuth::getGuildChannels();
        $discordVoiceChannels = DiscordOAuth::getGuildVoiceChannels();
        $discordCategories    = DiscordOAuth::getGuildCategories();
        $bannedLinks          = $botConfigModel->getBannedLinks();

        // Embed editor config
        $embedConfigKeys = [
            'panel_embed_title',
            'panel_embed_description',
            'panel_embed_color',
            'ticket_embed_title',
            'ticket_embed_description',
            'ticket_embed_footer',
            'ticket_panel_channel_id',
            'ticket_panel_message_id',
            // Log embeds
            'embed_log_ticket_open_title', 'embed_log_ticket_open_color',
            'embed_log_ticket_claim_title', 'embed_log_ticket_claim_color',
            'embed_log_ticket_close_title', 'embed_log_ticket_close_color',
            'embed_log_blacklist_add_title', 'embed_log_blacklist_add_color',
            'embed_log_blacklist_remove_title', 'embed_log_blacklist_remove_color',
            'embed_log_link_blocked_title', 'embed_log_link_blocked_color',
            'embed_log_autoRole_title', 'embed_log_autoRole_color',
            'embed_log_mute_add_title', 'embed_log_mute_add_color',
            'embed_log_mute_remove_title', 'embed_log_mute_remove_color',
            // Mute embeds
            'embed_mute_response_title', 'embed_mute_response_color', 'embed_mute_response_footer',
            'embed_mute_modlog_title', 'embed_mute_modlog_color', 'embed_mute_modlog_footer',
            'embed_mute_dm_title', 'embed_mute_dm_color', 'embed_mute_dm_description', 'embed_mute_dm_footer',
            'embed_mute_unmute_title', 'embed_mute_unmute_color', 'embed_mute_unmute_footer',
            // System embeds
            'embed_error_title', 'embed_error_color',
            'embed_success_title', 'embed_success_color',
            // Other embeds
            'embed_stats_title', 'embed_stats_color',
            'embed_blacklist_title', 'embed_blacklist_color',
        ];
        $embedConfig = $botConfigModel->getMultiple($embedConfigKeys);

        $this->render('management/discord', [
            'pageTitle'            => 'Management — Discord',
            'managementActive'     => 'discord',
            'managementPerms'      => $this->getManagementPermissions(),
            'automationSettings'   => $automationSettings,
            'botConfig'            => $botConfig,
            'discordRoles'         => $discordRoles,
            'discordChannels'      => $discordChannels,
            'discordVoiceChannels' => $discordVoiceChannels,
            'discordCategories'    => $discordCategories,
            'ticketCategories'     => $ticketCategories,
            'embedConfig'          => $embedConfig,
            'bannedLinks'          => $bannedLinks,
        ]);
    }

    /**
     * Add a domain to the banned links list (bot's blacklisted_links table).
     */
    public function discordBannedLinksAdd(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_DISCORD);
        $this->requirePost('/management/discord');
        $this->verifyCsrf('/management/discord');

        $domain = strtolower(trim((string)($_POST['domain'] ?? '')));
        // Strip scheme/www/path — keep only the root domain
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#^www\.#', '', $domain);
        $domain = explode('/', $domain)[0];
        $domain = preg_replace('/[^a-z0-9.\-]/', '', $domain);

        if ($domain === '' || !str_contains($domain, '.')) {
            Session::flash('error', 'Zadej platnou doménu (např. pornhub.com).');
            $this->redirect('/management/discord#general');
            return;
        }

        try {
            $botConfigModel = new DiscordBotConfigModel();
            $added = $botConfigModel->addBannedLink($domain, Session::get('username') ?? 'WEB');
            if ($added) {
                Session::flash('success', "Doména {$domain} byla přidána do blacklistu.");
            } else {
                Session::flash('error', "Doména {$domain} je již v blacklistu.");
            }
        } catch (\Throwable $e) {
            Logger::error('Banned link add failed: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se přidat doménu.');
        }

        $this->redirect('/management/discord#general');
    }

    /**
     * Remove a domain from the banned links list.
     */
    public function discordBannedLinksDelete(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_DISCORD);
        $this->requirePost('/management/discord');
        $this->verifyCsrf('/management/discord');

        $domain = strtolower(trim((string)($_POST['domain'] ?? '')));

        if ($domain === '') {
            Session::flash('error', 'Chybí doména.');
            $this->redirect('/management/discord#general');
            return;
        }

        try {
            $botConfigModel = new DiscordBotConfigModel();
            $botConfigModel->removeBannedLink($domain);
            Session::flash('success', "Doména {$domain} byla odebrána z blacklistu.");
        } catch (\Throwable $e) {
            Logger::error('Banned link delete failed: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se odebrat doménu.');
        }

        $this->redirect('/management/discord#general');
    }

    /**
     * Save automation settings (bot DMs / roles / log messages) to site_settings.
     */
    public function discordAutomationSave(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_DISCORD);
        $this->requirePost('/management/discord');
        $this->verifyCsrf('/management/discord');

        $keys = [
            'bot_role_approved',
            'bot_role_allowlisted',
            'bot_dm_approved',
            'bot_dm_allowlisted',
            'bot_dm_rejected',
            'bot_dm_interview_failed',
            'bot_log_channel',
            'bot_log_approved',
            'bot_log_rejected',
            'bot_log_interview_passed',
            'bot_log_interview_failed',
        ];

        $settingModel = new SiteSettingModel();

        try {
            foreach ($keys as $key) {
                $value = trim((string)($_POST[$key] ?? ''));
                $settingModel->set($key, $value !== '' ? $value : null);
            }
            Session::flash('success', 'Nastavení automatizace bylo uloženo.');
        } catch (\Throwable $e) {
            Logger::error('Discord automation save failed: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se uložit nastavení automatizace.');
        }

        $this->redirect('/management/discord');
    }

    /**
     * Save Discord bot runtime config keys to the bot's config table.
     */
    public function discordBotConfigSave(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_DISCORD);
        $this->requirePost('/management/discord');
        $this->verifyCsrf('/management/discord');

        $section = trim((string)($_POST['section'] ?? ''));

        $sectionKeys = [
            'general' => [
                'clen_role_id',
                'mod_log_channel',
                'mute_role_id',
            ],
            'tickets' => [
                'ticket_log_channel',
                'transcript_channel',
                'ticket_category_closed',
                'premium_member_roles',
            ],
            'stats' => [
                'stats_channel_total',
                'stats_channel_al',
                'stats_channel_interview',
                'stats_channel_redm',
                'stats_format_total',
                'stats_format_al',
                'stats_format_interview',
                'stats_format_redm',
                'stats_role_al',
                'stats_role_interview',
                'stats_redm_url',
            ],
        ];

        if (!isset($sectionKeys[$section])) {
            Session::flash('error', 'Neplatná sekce.');
            $this->redirect('/management/discord');
            return;
        }

        $keys           = $sectionKeys[$section];
        $botConfigModel = new DiscordBotConfigModel();

        try {
            foreach ($keys as $key) {
                $value = trim((string)($_POST[$key] ?? ''));
                $botConfigModel->set($key, $value !== '' ? $value : null);
            }

            // For tickets section: also save dynamic category IDs and staff roles
            if ($section === 'tickets') {
                $defaultCategories = [
                    ['slug' => 'admin'], ['slug' => 'dev'], ['slug' => 'faction'],
                    ['slug' => 'vedeni'], ['slug' => 'premium'],
                ];
                $categories = $botConfigModel->getJson('ticket_categories', $defaultCategories);
                foreach ($categories as $cat) {
                    $slug = $cat['slug'] ?? '';
                    if ($slug === '') continue;

                    $catKey   = 'ticket_category_' . $slug;
                    $staffKey = 'staff_roles_' . $slug;

                    if (isset($_POST[$catKey])) {
                        $v = trim((string)$_POST[$catKey]);
                        $botConfigModel->set($catKey, $v !== '' ? $v : null);
                    }
                    if (isset($_POST[$staffKey])) {
                        $v = trim((string)$_POST[$staffKey]);
                        $botConfigModel->set($staffKey, $v !== '' ? $v : null);
                    }
                }
            }
            Session::flash('success', 'Nastavení bota bylo uloženo.');
        } catch (\Throwable $e) {
            Logger::error('Discord bot config save failed: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se uložit nastavení bota.');
        }

        $this->redirect('/management/discord');
    }

    /**
     * Save per-category ticket bot config (Discord category ID, staff roles,
     * creator roles, close action, closed category ID).
     *
     * POST params: slug + ticket_category_{slug}, ticket_closed_category_{slug},
     *              staff_roles_{slug}, creator_roles_{slug}, ticket_close_action_{slug}
     */
    public function discordCategorySave(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_DISCORD);
        $this->requirePost('/management/discord');
        $this->verifyCsrf('/management/discord');

        $slug = preg_replace('/[^a-z0-9_]/', '', strtolower(trim((string)($_POST['slug'] ?? ''))));

        if ($slug === '') {
            Session::flash('error', 'Neplatný slug kategorie.');
            $this->redirect('/management/discord#tickets');
            return;
        }

        $botConfigModel = new DiscordBotConfigModel();

        $keys = [
            "ticket_category_{$slug}",
            "ticket_closed_category_{$slug}",
            "staff_roles_{$slug}",
            "creator_roles_{$slug}",
            "ticket_close_action_{$slug}",
            "ticket_embed_title_{$slug}",
            "ticket_embed_description_{$slug}",
            "ticket_embed_footer_{$slug}",
        ];

        try {
            foreach ($keys as $key) {
                $value = trim((string)($_POST[$key] ?? ''));
                // close_action must be 'move' or 'delete', default is 'move'
                if ($key === "ticket_close_action_{$slug}") {
                    $value = $value === 'delete' ? 'delete' : 'move';
                }
                $botConfigModel->set($key, $value !== '' ? $value : null);
            }
            Session::flash('success', 'Nastavení kategorie bylo uloženo.');
        } catch (\Throwable $e) {
            Logger::error('Discord category save failed: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se uložit nastavení.');
        }

        $this->redirect('/management/discord#tickets');
    }

    /**
     * Add or delete a ticket category stored in the bot's `config` table
     * under the JSON key `ticket_categories`.
     *
     * POST params:
     *   action  = add | delete
     *   For add:    slug, label, emoji, color (hex without #)
     *   For delete: slug
     */
    public function discordCategoriesUpdate(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_DISCORD);
        $this->requirePost('/management/discord');
        $this->verifyCsrf('/management/discord');

        $action = trim((string)($_POST['action'] ?? ''));

        $defaultCategories = [
            ['slug' => 'admin',   'label' => 'Admin Ticket',   'emoji' => '🔵', 'color' => '3498DB'],
            ['slug' => 'dev',     'label' => 'Dev Ticket',     'emoji' => '🟠', 'color' => 'E67E22'],
            ['slug' => 'faction', 'label' => 'Faction Ticket', 'emoji' => '🟣', 'color' => '9B59B6'],
            ['slug' => 'vedeni',  'label' => 'Vedení Ticket',  'emoji' => '🔴', 'color' => 'E74C3C'],
            ['slug' => 'premium', 'label' => 'Premium Ticket', 'emoji' => '⭐', 'color' => 'F1C40F'],
        ];

        $botConfigModel = new DiscordBotConfigModel();
        $categories     = $botConfigModel->getJson('ticket_categories', $defaultCategories);
        if (empty($categories)) {
            $categories = $defaultCategories;
        }

        if ($action === 'add') {
            $slug  = preg_replace('/[^a-z0-9_]/', '', strtolower(trim((string)($_POST['slug'] ?? ''))));
            $label = trim((string)($_POST['label'] ?? ''));
            $emoji = trim((string)($_POST['emoji'] ?? '📂'));
            $color = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', ltrim(trim((string)($_POST['color'] ?? 'AAAAAA')), '#')));

            if ($slug === '' || $label === '') {
                Session::flash('error', 'Slug a název kategorie jsou povinné.');
                $this->redirect('/management/discord#tickets');
                return;
            }

            foreach ($categories as $cat) {
                if ($cat['slug'] === $slug) {
                    Session::flash('error', "Kategorie se slugem {$slug} již existuje.");
                    $this->redirect('/management/discord#tickets');
                    return;
                }
            }

            $categories[] = [
                'slug'  => $slug,
                'label' => $label,
                'emoji' => $emoji !== '' ? $emoji : '📂',
                'color' => $color !== '' ? $color : 'AAAAAA',
            ];

        } elseif ($action === 'delete') {
            $slug       = trim((string)($_POST['slug'] ?? ''));
            $categories = array_values(array_filter($categories, fn($c) => $c['slug'] !== $slug));

        } else {
            Session::flash('error', 'Neplatná akce.');
            $this->redirect('/management/discord#tickets');
            return;
        }

        try {
            $botConfigModel->setJson('ticket_categories', $categories);
            Session::flash('success', 'Kategorie ticketů byly aktualizovány.');
        } catch (\Throwable $e) {
            Logger::error('Discord categories update failed: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se uložit kategorie.');
        }

        $this->redirect('/management/discord#tickets');
    }

    /**
     * Save panel embed and ticket welcome embed content.
     *
     * POST params:
     *   panel_embed_title, panel_embed_description, panel_embed_color
     *   ticket_embed_title, ticket_embed_description, ticket_embed_footer
     */
    public function discordEmbedSave(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_DISCORD);
        $this->requirePost('/management/discord');
        $this->verifyCsrf('/management/discord');

        $type = trim((string)($_POST['embed_type'] ?? 'panel'));

        $botConfigModel = new DiscordBotConfigModel();

        if ($type === 'welcome') {
            $fields = ['ticket_embed_title', 'ticket_embed_description', 'ticket_embed_footer'];
        } else {
            $fields = ['panel_embed_title', 'panel_embed_description', 'panel_embed_color'];
        }

        try {
            foreach ($fields as $key) {
                $value = trim((string)($_POST[$key] ?? ''));
                if ($key === 'panel_embed_color') {
                    $value = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', ltrim($value, '#')));
                }
                $botConfigModel->set($key, $value !== '' ? $value : null);
            }
            Session::flash('success', 'Nastavení embedu bylo uloženo.');
        } catch (\Throwable $e) {
            Logger::error('Discord embed save failed: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se uložit nastavení embedu.');
        }

        $this->redirect('/management/discord#tickets');
    }

    /**
     * Save all embed configuration (log, mute, system, other embeds).
     */
    public function discordAllEmbedsSave(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_DISCORD);
        $this->requirePost('/management/discord');
        $this->verifyCsrf('/management/discord');

        $allowed = [
            // Log embeds
            'embed_log_ticket_open_title', 'embed_log_ticket_open_color',
            'embed_log_ticket_claim_title', 'embed_log_ticket_claim_color',
            'embed_log_ticket_close_title', 'embed_log_ticket_close_color',
            'embed_log_blacklist_add_title', 'embed_log_blacklist_add_color',
            'embed_log_blacklist_remove_title', 'embed_log_blacklist_remove_color',
            'embed_log_link_blocked_title', 'embed_log_link_blocked_color',
            'embed_log_autoRole_title', 'embed_log_autoRole_color',
            'embed_log_mute_add_title', 'embed_log_mute_add_color',
            'embed_log_mute_remove_title', 'embed_log_mute_remove_color',
            // Mute embeds
            'embed_mute_response_title', 'embed_mute_response_color', 'embed_mute_response_footer',
            'embed_mute_modlog_title', 'embed_mute_modlog_color', 'embed_mute_modlog_footer',
            'embed_mute_dm_title', 'embed_mute_dm_color', 'embed_mute_dm_description', 'embed_mute_dm_footer',
            'embed_mute_unmute_title', 'embed_mute_unmute_color', 'embed_mute_unmute_footer',
            // System embeds
            'embed_error_title', 'embed_error_color',
            'embed_success_title', 'embed_success_color',
            // Other embeds
            'embed_stats_title', 'embed_stats_color',
            'embed_blacklist_title', 'embed_blacklist_color',
        ];

        $botConfigModel = new DiscordBotConfigModel();

        try {
            foreach ($allowed as $key) {
                $value = trim((string)($_POST[$key] ?? ''));
                // Normalize color values (strip # and non-hex chars)
                if (str_ends_with($key, '_color') && $value !== '') {
                    $value = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', ltrim($value, '#')));
                }
                $botConfigModel->set($key, $value !== '' ? $value : null);
            }
            Session::flash('success', 'Nastavení embedů bylo uloženo.');
        } catch (\Throwable $e) {
            Logger::error('Discord all embeds save failed: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se uložit nastavení embedů.');
        }

        $this->redirect('/management/discord#embeds');
    }

    /**
     * Refresh (PATCH) the ticket panel Discord message with up-to-date
     * embed content and category dropdown.
     */
    public function discordPanelRefresh(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_DISCORD);
        $this->requirePost('/management/discord');
        $this->verifyCsrf('/management/discord');

        $botConfigModel = new DiscordBotConfigModel();

        $channelId = $botConfigModel->get('ticket_panel_channel_id');
        $messageId = $botConfigModel->get('ticket_panel_message_id');

        if (!$channelId || !$messageId) {
            Session::flash('error', 'Panel ještě nebyl nastaven. Nejprve použij příkaz /ticket setup v Discordu.');
            $this->redirect('/management/discord#tickets');
            return;
        }

        $defaultCategories = [
            ['slug' => 'admin',   'label' => 'Admin Ticket',   'emoji' => '🔵', 'color' => '3498DB'],
            ['slug' => 'dev',     'label' => 'Dev Ticket',     'emoji' => '🟠', 'color' => 'E67E22'],
            ['slug' => 'faction', 'label' => 'Faction Ticket', 'emoji' => '🟣', 'color' => '9B59B6'],
            ['slug' => 'vedeni',  'label' => 'Vedení Ticket',  'emoji' => '🔴', 'color' => 'E74C3C'],
            ['slug' => 'premium', 'label' => 'Premium Ticket', 'emoji' => '⭐', 'color' => 'F1C40F'],
        ];

        $categories = $botConfigModel->getJson('ticket_categories', $defaultCategories);
        if (empty($categories)) {
            $categories = $defaultCategories;
        }

        // Build embed
        $title       = $botConfigModel->get('panel_embed_title')       ?: '🎫  OTRP  •  Tickets';
        $description = $botConfigModel->get('panel_embed_description') ?:
            "> Potřebuješ pomoc nebo chceš nahlásit problém?\n" .
            "> Vyber níže kategorii a otevři ticket.\n\n" .
            "🔵 **Admin** — Nahlášení Hráčů, Všeobecné problémy a dotazy, Žádosti o CK\n" .
            "🟠 **Dev** — Bugy, návrhy\n" .
            "🟣 **Faction** — Žádosti o frakce, Stížnosti na frakce\n" .
            "🔴 **Vedení** — Závažné věci přímo pro vedení\n" .
            "⭐ **Premium** — Pouze pro subscribery\n\n";
        $colorHex    = $botConfigModel->get('panel_embed_color') ?: '2B2D31';
        $colorInt    = (int)hexdec($colorHex);

        // Build select-menu options
        $selectOptions = [];
        foreach ($categories as $cat) {
            $option = [
                'label' => $cat['label'],
                'value' => $cat['slug'],
            ];
            if (!empty($cat['emoji'])) {
                $option['emoji'] = ['name' => $cat['emoji']];
            }
            $selectOptions[] = $option;
        }

        $payload = [
            'embeds' => [[
                'title'       => $title,
                'description' => $description,
                'color'       => $colorInt,
            ]],
            'components' => [[
                'type'       => 1,
                'components' => [[
                    'type'        => 3,
                    'custom_id'   => 'ticket:select',
                    'placeholder' => '🤠 Vyber typ ticketu...',
                    'options'     => $selectOptions,
                ]],
            ]],
        ];

        $ok = DiscordOAuth::editGuildMessage($channelId, $messageId, $payload);

        if ($ok) {
            Session::flash('success', 'Ticket panel byl úspěšně aktualizován v Discordu.');
        } else {
            Session::flash('error', 'Nepodařilo se aktualizovat panel v Discordu. Zkontroluj logy.');
        }

        $this->redirect('/management/discord#tickets');
    }

    // =========================================================================
    // Management Settings
    // =========================================================================

    /**
     * Settings for delegated admin/management access.
     */
    public function settingsIndex(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_SETTINGS);

        $permModel    = new ManagementPermissionModel();
        $userModel    = new UserModel();

        $this->render('management/settings', [
            'pageTitle'              => 'Management',
            'managementActive'       => 'settings',
            'managementPerms'        => $this->getManagementPermissions(),
            'permissionLabels'       => $this->permissionLabels(),
            'permissionGroupedLabels'=> $this->permissionGroupedLabels(),
            'permissionGroups'       => $permModel->getGroupedBySubject(),
            'knownRoles'             => $userModel->getKnownRolesForPermissionPicker(),
            'users'                  => $userModel->getUsersForPermissionPicker(),
            'vedeniRoleIds'          => Permission::VEDENI_ROLE_IDS,
            'qpRoleMap'              => (new QpRoleConfigModel())->getRoleMap(),
            'charRoleMap'            => (new CharRoleConfigModel())->getRoleMap(),
            'pedRoleMap'             => (new PedRoleConfigModel())->getRoleMap(),
        ]);
    }

    /**
     * Add a new subject (role or user) to the permission system.
     */
    public function settingsGrant(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_SETTINGS);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $subjectType   = trim((string)($_POST['subject_type'] ?? ''));
        $subjectValue  = trim((string)($_POST['subject_value'] ?? ''));

        if (!in_array($subjectType, ['role', 'user'], true)) {
            Session::flash('error', 'Neplatný typ subjektu.');
            $this->redirect('/management/settings');
            return;
        }

        if ($subjectValue === '') {
            Session::flash('error', 'Musíš vyplnit roli nebo uživatele.');
            $this->redirect('/management/settings');
            return;
        }

        if ($subjectType === 'user' && !(new UserModel())->findById((int)$subjectValue)) {
            Session::flash('error', 'Vybraný uživatel neexistuje.');
            $this->redirect('/management/settings');
            return;
        }

        // Check if subject already has any permissions
        $permModel = new ManagementPermissionModel();
        $groups = $permModel->getGroupedBySubject();
        $key = $subjectType . ':' . $subjectValue;
        if (isset($groups[$key])) {
            Session::flash('error', 'Tento subjekt již existuje v seznamu.');
            $this->redirect('/management/settings');
            return;
        }

        // Grant the first available permission so the subject appears in the list
        $labels = $this->permissionLabels();
        $firstKey = array_key_first($labels);
        try {
            $permModel->grant($firstKey, $subjectType, $subjectValue, (int)Permission::userId());
            Session::flash('success', 'Subjekt byl přidán. Nyní zaškrtni požadovaná oprávnění.');
        } catch (\Throwable $e) {
            Logger::error('Management settings grant failed: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se přidat subjekt.');
        }

        $this->redirect('/management/settings');
    }

    /**
     * Sync (bulk update) permission checkboxes for a subject.
     */
    public function settingsSyncPerms(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_SETTINGS);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $subjectType  = trim((string)($_POST['subject_type'] ?? ''));
        $subjectValue = trim((string)($_POST['subject_value'] ?? ''));
        $newKeys      = (array)($_POST['perms'] ?? []);

        if (!in_array($subjectType, ['role', 'user'], true) || $subjectValue === '') {
            Session::flash('error', 'Neplatný subjekt.');
            $this->redirect('/management/settings');
            return;
        }

        // Vedení roles cannot be modified
        if ($subjectType === 'role' && in_array($subjectValue, Permission::VEDENI_ROLE_IDS, true)) {
            Session::flash('error', 'Systémové vedení role nelze upravovat.');
            $this->redirect('/management/settings');
            return;
        }

        $allKeys = array_keys($this->permissionLabels());
        // Filter only valid keys
        $newKeys = array_values(array_intersect($newKeys, $allKeys));

        try {
            (new ManagementPermissionModel())->syncPermissions(
                $subjectType,
                $subjectValue,
                $newKeys,
                $allKeys,
                (int)Permission::userId()
            );

            // Save benefits (QP / Char / Ped) for role-type subjects
            if ($subjectType === 'role') {
                $benefitQp   = (int)($_POST['benefit_qp'] ?? 0);
                $benefitChar = (int)($_POST['benefit_chars'] ?? 0);
                $benefitPed  = !empty($_POST['benefit_ped']);

                $qpModel   = new QpRoleConfigModel();
                $charModel = new CharRoleConfigModel();
                $pedModel  = new PedRoleConfigModel();

                // Load current maps, update this role, save back
                $qpMap = $qpModel->getRoleMap();
                if ($benefitQp > 0) {
                    $qpMap[$subjectValue] = $benefitQp;
                } else {
                    unset($qpMap[$subjectValue]);
                }
                $qpModel->save($qpMap);

                $charMap = $charModel->getRoleMap();
                if ($benefitChar > 0) {
                    $charMap[$subjectValue] = $benefitChar;
                } else {
                    unset($charMap[$subjectValue]);
                }
                $charModel->save($charMap);

                $pedIds = $pedModel->getRoleIds();
                $pedIds = array_filter($pedIds, fn($id) => $id !== $subjectValue);
                if ($benefitPed) {
                    $pedIds[] = $subjectValue;
                }
                $pedModel->save(array_values($pedIds));
            }

            Session::flash('success', 'Oprávnění byla aktualizována.');
        } catch (\Throwable $e) {
            Logger::error('Management settings sync failed: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se aktualizovat oprávnění.');
        }

        $this->redirect('/management/settings');
    }

    /**
     * Remove a subject (and all its permissions) from the system.
     */
    public function settingsRemoveSubject(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_SETTINGS);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $subjectType  = trim((string)($_POST['subject_type'] ?? ''));
        $subjectValue = trim((string)($_POST['subject_value'] ?? ''));

        if (!in_array($subjectType, ['role', 'user'], true) || $subjectValue === '') {
            Session::flash('error', 'Neplatný subjekt.');
            $this->redirect('/management/settings');
            return;
        }

        if ($subjectType === 'role' && in_array($subjectValue, Permission::VEDENI_ROLE_IDS, true)) {
            Session::flash('error', 'Systémové vedení role nelze odebrat.');
            $this->redirect('/management/settings');
            return;
        }

        try {
            (new ManagementPermissionModel())->revokeAllForSubject($subjectType, $subjectValue);
            Session::flash('success', 'Subjekt a všechna jeho oprávnění byla odebrána.');
        } catch (\Throwable $e) {
            Logger::error('Management settings removeSubject failed: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se odebrat subjekt.');
        }

        $this->redirect('/management/settings');
    }

    /**
     * Remove delegated access rule.
     */
    public function settingsRevoke(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_SETTINGS);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id = (int)($params['id'] ?? 0);

        $permModel = new ManagementPermissionModel();
        $entry = $permModel->findById($id);

        if (
            $entry
            && ($entry['subject_type'] ?? '') === 'role'
            && in_array((string)($entry['subject_value'] ?? ''), Permission::VEDENI_ROLE_IDS, true)
        ) {
            Session::flash('error', 'Systémové vedení role nejde odebrat.');
            $this->redirect('/management/settings');
            return;
        }

        try {
            $permModel->revoke($id);
            Session::flash('success', 'Pravidlo oprávnění bylo odebráno.');
        } catch (\Throwable $e) {
            Logger::error("Management settings revoke failed for #{$id}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se odebrat oprávnění.');
        }

        $this->redirect('/management/settings');
    }

    /**
     * Save Discord bot settings (roles + DM templates).
     */
    public function settingsBotSave(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_SETTINGS);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $keys = [
            'bot_role_approved',
            'bot_role_allowlisted',
            'bot_dm_approved',
            'bot_dm_allowlisted',
            'bot_dm_rejected',
            'bot_dm_interview_failed',
            'bot_log_channel',
            'bot_log_approved',
            'bot_log_rejected',
            'bot_log_interview_passed',
            'bot_log_interview_failed',
        ];

        $settingModel = new SiteSettingModel();

        try {
            foreach ($keys as $key) {
                $value = trim((string)($_POST[$key] ?? ''));
                $settingModel->set($key, $value !== '' ? $value : null);
            }
            Session::flash('success', 'Nastavení bota bylo uloženo.');
        } catch (\Throwable $e) {
            Logger::error('Bot settings save failed: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se uložit nastavení.');
        }

        $this->redirect('/management/settings');
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /** @var array<string,bool>|null Per-request permission cache. */
    private ?array $mgmtPermsCache = null;

    /**
     * @return array<string,bool>
     */
    private function getManagementPermissions(): array
    {
        if ($this->mgmtPermsCache !== null) {
            return $this->mgmtPermsCache;
        }

        $all = [
            self::PERM_FORM      => false,
            self::PERM_CONTENT   => false,
            self::PERM_RULES     => false,
            self::PERM_BLACKLIST => false,
            self::PERM_APPEALS   => false,
            self::PERM_TEAM        => false,
            self::PERM_CHEATSHEET  => false,
            self::PERM_PARTNERS        => false,
            self::PERM_HOMEPAGE        => false,
            self::PERM_ALLOWLIST_STATS => false,
            self::PERM_QP              => false,
            self::PERM_CHARS           => false,
            self::PERM_PED             => false,
            self::PERM_CODES           => false,
            self::PERM_API_KEYS          => false,
            self::PERM_DISCORD           => false,
            self::PERM_INGAME_ADMIN      => false,
            self::PERM_INGAME_MANAGEMENT => false,
            self::PERM_SETTINGS        => false,
        ];

        if (Permission::isVedeni()) {
            foreach ($all as $k => $_) {
                $all[$k] = true;
            }
            $this->mgmtPermsCache = $all;
            return $all;
        }

        $userId = (int)Permission::userId();
        $roles  = Session::get('roles', []);
        $roles  = is_array($roles) ? array_values(array_map('strval', $roles)) : [];
        $roleIds = Permission::roleIds();

        $keys = (new ManagementPermissionModel())->getPermissionKeysForUser($userId, $roles, $roleIds);
        foreach ($keys as $key) {
            if (array_key_exists($key, $all)) {
                $all[$key] = true;
            }
        }

        $this->mgmtPermsCache = $all;
        return $all;
    }

    // =========================================================================
    // Homepage Editor
    // =========================================================================

    /** Available homepage block types with labels. */
    private const BLOCK_TYPES = [
        'hero'    => 'Hero sekce',
        'heading' => 'Nadpis',
        'text'    => 'Text',
        'buttons' => 'Tlačítka',
        'cards'   => 'Karty',
        'divider' => 'Oddělovač',
        'spacer'  => 'Mezera',
        'html'    => 'HTML kód',
    ];

    public function homepageEdit(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_HOMEPAGE);
        $model  = new SiteSettingModel();
        $raw    = $model->get('home.blocks');
        $blocks = $raw ? json_decode($raw, true) : null;

        // Fallback: build default blocks from old key-value settings
        if ($blocks === null) {
            $blocks = $this->buildDefaultBlocks($model);
        }

        $this->render('management/homepage_edit', [
            'pageTitle'        => 'Management',
            'blocks'           => $blocks,
            'blockTypes'       => self::BLOCK_TYPES,
            'managementActive' => 'homepage',
            'managementPerms'  => $this->getManagementPermissions(),
        ]);
    }

    public function homepageSave(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_HOMEPAGE);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $model = new SiteSettingModel();

        try {
            $blocksJson = $_POST['blocks_json'] ?? '[]';
            $blocks     = json_decode($blocksJson, true);

            if (!is_array($blocks)) {
                throw new \RuntimeException('Invalid blocks data');
            }

            // Sanitize each block
            $clean = [];
            foreach ($blocks as $block) {
                if (!is_array($block) || !isset($block['type']) || !isset(self::BLOCK_TYPES[$block['type']])) {
                    continue;
                }
                $data = is_array($block['data'] ?? null) ? $block['data'] : [];
                $clean[] = [
                    'type' => $block['type'],
                    'data' => $data,
                ];
            }

            $model->set('home.blocks', json_encode($clean, JSON_UNESCAPED_UNICODE));
            Session::flash('success', 'Domovská stránka byla aktualizována.');
        } catch (\Throwable $e) {
            Logger::error("Homepage save failed: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se uložit změny.');
        }

        $this->redirect('/management/homepage');
    }

    /**
     * Build default blocks array from legacy key-value settings.
     */
    private function buildDefaultBlocks(SiteSettingModel $model): array
    {
        $keys = [
            'home.hero_badge', 'home.hero_title', 'home.hero_desc',
            'home.btn_primary_text', 'home.btn_primary_url',
            'home.btn_secondary_text', 'home.btn_secondary_url',
            'home.card1_title', 'home.card1_text',
            'home.card2_title', 'home.card2_text',
            'home.card3_title', 'home.card3_text',
        ];
        $s = $model->getMultiple($keys);

        $blocks = [];

        // Hero
        $blocks[] = [
            'type' => 'hero',
            'data' => [
                'badge' => $s['home.hero_badge'] ?? '✦ Roleplay Server ✦',
                'title' => $s['home.hero_title'] ?? 'Vítejte v divočině Západu',
                'desc'  => $s['home.hero_desc'] ?? 'Old Times RP je předním česko-slovenským roleplay serverem pro Red Dead Redemption 2. Ponořte se do světa starého Západu — kde zákon sahá jen tak daleko, jak daleko dohlédnete.',
            ],
        ];

        // Buttons
        $blocks[] = [
            'type' => 'buttons',
            'data' => [
                'items' => [
                    [
                        'text'  => $s['home.btn_primary_text'] ?? 'Přihlásit se',
                        'url'   => $s['home.btn_primary_url'] ?? '/login',
                        'style' => 'primary',
                    ],
                    [
                        'text'  => $s['home.btn_secondary_text'] ?? 'Číst Pravidla',
                        'url'   => $s['home.btn_secondary_url'] ?? '/pravidla',
                        'style' => 'secondary',
                    ],
                ],
            ],
        ];

        // Cards
        $cards = [];
        for ($i = 1; $i <= 3; $i++) {
            $cards[] = [
                'title' => $s["home.card{$i}_title"] ?? '',
                'text'  => $s["home.card{$i}_text"] ?? '',
            ];
        }
        $blocks[] = [
            'type' => 'cards',
            'data' => ['items' => $cards],
        ];

        return $blocks;
    }

    // =========================================================================
    // Partners
    // =========================================================================

    public function partnerList(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_PARTNERS);
        $model = new PartnerModel();

        $this->render('management/partner_list', [
            'pageTitle'        => 'Management',
            'partners'         => $model->getAll(),
            'managementActive' => 'partners',
            'managementPerms'  => $this->getManagementPermissions(),
        ]);
    }

    public function partnerEdit(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_PARTNERS);
        $id      = isset($params['id']) ? (int)$params['id'] : null;
        $partner = null;

        if ($id) {
            $model   = new PartnerModel();
            $partner = $model->findById($id);
        }

        $this->render('management/partner_edit', [
            'pageTitle'        => 'Management',
            'partner'          => $partner,
            'managementActive' => 'partners',
            'managementPerms'  => $this->getManagementPermissions(),
        ]);
    }

    public function partnerSave(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_PARTNERS);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id          = isset($params['id']) ? (int)$params['id'] : null;
        $name        = trim($_POST['name'] ?? '');
        $logoUrl     = trim($_POST['logo_url'] ?? '') ?: null;
        $description = trim($_POST['description'] ?? '') ?: null;
        $url         = trim($_POST['url'] ?? '') ?: null;
        $sortOrder   = (int)($_POST['sort_order'] ?? 0);
        $active      = isset($_POST['active']);

        if (empty($name)) {
            Session::flash('error', 'Název partnera je povinný.');
            $this->redirect($id ? "/management/partners/{$id}/edit" : '/management/partners/new');
            return;
        }

        // Auto-fetch logo from partner URL if not provided manually
        if (!$logoUrl && $url) {
            try {
                $fetched = PartnerModel::fetchLogoFromUrl($url);
                if ($fetched) {
                    $logoUrl = $fetched;
                }
            } catch (\Throwable $e) {
                Logger::error("Partner logo fetch failed for {$url}: " . $e->getMessage());
            }
        }

        $model = new PartnerModel();

        try {
            if ($id) {
                $model->update($id, $name, $logoUrl, $description, $url, $sortOrder, $active);
                Session::flash('success', 'Partner byl aktualizován.');
            } else {
                $model->create($name, $logoUrl, $description, $url, $sortOrder, $active);
                Session::flash('success', 'Partner byl vytvořen.');
            }
        } catch (\Throwable $e) {
            Logger::error("Partner save failed: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se uložit partnera.');
        }

        $this->redirect('/management/partners');
    }

    public function partnerDelete(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_PARTNERS);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id    = (int)($params['id'] ?? 0);
        $model = new PartnerModel();

        try {
            $model->delete($id);
            Session::flash('success', 'Partner byl smazán.');
        } catch (\Throwable $e) {
            Logger::error("Partner delete failed for #{$id}: " . $e->getMessage());
            Session::flash('error', 'Nepodařilo se smazat partnera.');
        }

        $this->redirect('/management/partners');
    }

    public function partnerMove(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_PARTNERS);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id        = (int)($params['id'] ?? 0);
        $direction = $_POST['direction'] ?? '';
        $model     = new PartnerModel();

        if ($direction === 'up') {
            $model->moveUp($id);
        } elseif ($direction === 'down') {
            $model->moveDown($id);
        }

        $this->redirect('/management/partners');
    }

    private function requireManagementPermission(string $permissionKey): void
    {
        $permissions = $this->getManagementPermissions();
        if (!($permissions[$permissionKey] ?? false)) {
            http_response_code(403);
            $this->render('errors/403', ['pageTitle' => '403']);
            exit;
        }
    }

    // =========================================================================
    // QP + Char Combined Configuration
    // =========================================================================

    /**
     * GET /management/role-config — combined QP & char-slot configuration page.
     */
    public function roleConfig(array $params = []): void
    {
        $this->redirect('/management/settings');
    }

    // =========================================================================
    // QP Configuration
    // =========================================================================

    /**
     * GET /management/qp — QP role configuration page.
     */
    public function qpConfig(array $params = []): void
    {
        $this->redirect('/management/role-config#qp');
        return;
    }

    public function charConfig(array $params = []): void
    {
        $this->redirect('/management/role-config#chars');
        return;
    }

    /**
     * POST /management/qp/save — persist QP values per role.
     */
    public function qpConfigSave(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_QP);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $raw    = $_POST['qp'] ?? [];
        $config = [];

        if (is_array($raw)) {
            foreach ($raw as $roleId => $value) {
                $roleId = preg_replace('/[^0-9]/', '', (string)$roleId);
                $value  = (int)$value;
                if ($roleId !== '' && $value > 0) {
                    $config[$roleId] = $value;
                }
            }
        }

        try {
            (new QpRoleConfigModel())->save($config);
            Session::flash('success', 'QP konfigurace byla uložena.');
        } catch (\Throwable $e) {
            Logger::error('QP config save failed: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se uložit QP konfiguraci.');
        }

        $this->redirect('/management/role-config#qp');
    }

    // =========================================================================
    // Char Slot Configuration
    // =========================================================================

    /**
     * POST /management/chars/save — persist char-slot values per role.
     */
    public function charConfigSave(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_CHARS);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $raw    = $_POST['chars'] ?? [];
        $config = [];

        if (is_array($raw)) {
            foreach ($raw as $roleId => $value) {
                $roleId = preg_replace('/[^0-9]/', '', (string)$roleId);
                $value  = (int)$value;
                if ($roleId !== '' && $value > 0) {
                    $config[$roleId] = $value;
                }
            }
        }

        try {
            (new CharRoleConfigModel())->save($config);
            Session::flash('success', 'Konfigurace slotů pro postavy byla uložena.');
        } catch (\Throwable $e) {
            Logger::error('Char config save failed: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se uložit konfiguraci.');
        }

        $this->redirect('/management/role-config#chars');
    }

    // =========================================================================
    // Ped Menu Role Configuration
    // =========================================================================

    /**
     * POST /management/ped/save — persist ped-menu role list.
     */
    public function pedConfigSave(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_PED);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $raw     = $_POST['ped'] ?? [];
        $roleIds = [];

        if (is_array($raw)) {
            foreach ($raw as $roleId => $on) {
                $roleId = preg_replace('/[^0-9]/', '', (string)$roleId);
                if ($roleId !== '') {
                    $roleIds[] = $roleId;
                }
            }
        }

        try {
            (new PedRoleConfigModel())->save($roleIds);
            Session::flash('success', 'Konfigurace Ped Menu byla uložena.');
        } catch (\Throwable $e) {
            Logger::error('Ped config save failed: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se uložit konfiguraci Ped Menu.');
        }

        $this->redirect('/management/role-config#ped');
    }

    /**
     * POST /management/role-config/save — unified save for QP, Char and Ped.
     * Saves only the sections the user has permission for.
     */
    public function roleConfigSave(array $params = []): void
    {
        $perms = $this->getManagementPermissions();
        $hasAny = !empty($perms[self::PERM_QP]) || !empty($perms[self::PERM_CHARS]) || !empty($perms[self::PERM_PED]);
        if (!$hasAny) {
            Session::flash('error', 'Nemáš oprávnění.');
            $this->redirect('/management');
            return;
        }
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $errors = [];

        // QP
        if (!empty($perms[self::PERM_QP])) {
            $raw = $_POST['qp'] ?? [];
            $config = [];
            if (is_array($raw)) {
                foreach ($raw as $roleId => $value) {
                    $roleId = preg_replace('/[^0-9]/', '', (string)$roleId);
                    $value  = (int)$value;
                    if ($roleId !== '' && $value > 0) {
                        $config[$roleId] = $value;
                    }
                }
            }
            try {
                (new QpRoleConfigModel())->save($config);
            } catch (\Throwable $e) {
                Logger::error('QP config save failed: ' . $e->getMessage());
                $errors[] = 'QP';
            }
        }

        // Char
        if (!empty($perms[self::PERM_CHARS])) {
            $raw = $_POST['chars'] ?? [];
            $config = [];
            if (is_array($raw)) {
                foreach ($raw as $roleId => $value) {
                    $roleId = preg_replace('/[^0-9]/', '', (string)$roleId);
                    $value  = (int)$value;
                    if ($roleId !== '' && $value > 0) {
                        $config[$roleId] = $value;
                    }
                }
            }
            try {
                (new CharRoleConfigModel())->save($config);
            } catch (\Throwable $e) {
                Logger::error('Char config save failed: ' . $e->getMessage());
                $errors[] = 'Char';
            }
        }

        // Ped
        if (!empty($perms[self::PERM_PED])) {
            $raw = $_POST['ped'] ?? [];
            $roleIds = [];
            if (is_array($raw)) {
                foreach ($raw as $roleId => $on) {
                    $roleId = preg_replace('/[^0-9]/', '', (string)$roleId);
                    if ($roleId !== '') {
                        $roleIds[] = $roleId;
                    }
                }
            }
            try {
                (new PedRoleConfigModel())->save($roleIds);
            } catch (\Throwable $e) {
                Logger::error('Ped config save failed: ' . $e->getMessage());
                $errors[] = 'Ped';
            }
        }

        if (empty($errors)) {
            Session::flash('success', 'Konfigurace rolí byla uložena.');
        } else {
            Session::flash('error', 'Nepodařilo se uložit: ' . implode(', ', $errors) . '.');
        }

        $this->redirect('/management/role-config');
    }

    // =========================================================================
    // Redemption Codes
    // =========================================================================

    /**
     * GET /management/codes — list all redemption codes.
     */
    public function codesIndex(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_CODES);

        $codes = (new RedeemCodeModel())->getAll();

        $this->render('management/codes', [
            'pageTitle'        => 'Management',
            'managementActive' => 'codes',
            'managementPerms'  => $this->getManagementPermissions(),
            'codes'            => $codes,
        ]);
    }

    /**
     * POST /management/codes/create — generate a new redemption code.
     */
    public function codesCreate(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_CODES);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $type     = $_POST['type'] ?? '';
        $amount   = (int)($_POST['amount'] ?? 0);
        $maxUses  = max(1, (int)($_POST['max_uses'] ?? 1));
        $note     = trim($_POST['note'] ?? '');
        $expiresRaw = trim($_POST['expires_at'] ?? '');
        $expiresAt  = null;

        if (!in_array($type, ['qp', 'chars', 'ped'], true)) {
            Session::flash('error', 'Neplatný typ kódu.');
            $this->redirect('/management/codes');
            return;
        }

        if ($type !== 'ped' && $amount <= 0) {
            Session::flash('error', 'Hodnota musí být kladné číslo.');
            $this->redirect('/management/codes');
            return;
        }

        if ($expiresRaw !== '') {
            try {
                $expiresAt = (new \DateTimeImmutable($expiresRaw))->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                Session::flash('error', 'Neplatný formát data.');
                $this->redirect('/management/codes');
                return;
            }
        }

        try {
            $code = (new RedeemCodeModel())->generate(
                $type,
                $amount,
                $maxUses,
                $expiresAt,
                $note,
                (int)Permission::userId()
            );
            Session::flash('success', 'Kód vygenerován: ' . $code);
        } catch (\Throwable $e) {
            Logger::error('Code generate: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se vygenerovat kód.');
        }

        $this->redirect('/management/codes');
    }

    /**
     * POST /management/codes/:id/delete — delete a redemption code.
     */
    public function codesDelete(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_CODES);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id = (int)($params['id'] ?? 0);

        try {
            (new RedeemCodeModel())->delete($id);
            Session::flash('success', 'Kód byl smazán.');
        } catch (\Throwable $e) {
            Logger::error('Code delete: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se smazat kód.');
        }

        $this->redirect('/management/codes');
    }

    // -------------------------------------------------------------------------
    // Discord cache nuke
    // -------------------------------------------------------------------------

    public function clearDiscordCache(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_SETTINGS);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $savePath = session_save_path();
        if (empty($savePath)) {
            $savePath = ini_get('session.save_path') ?: sys_get_temp_dir();
        }
        // session.save_path may include a depth prefix like "1;/path"
        if (str_contains($savePath, ';')) {
            $savePath = substr($savePath, (int)strpos($savePath, ';') + 1);
        }
        $savePath  = rtrim($savePath, '/');
        $currentId = session_id();
        $deleted   = 0;

        foreach (glob($savePath . '/sess_*') ?: [] as $file) {
            if (!is_file($file)) {
                continue;
            }
            if (basename($file) === 'sess_' . $currentId) {
                // Clear only Discord cache keys from current session so admin stays logged in
                foreach (array_keys($_SESSION) as $key) {
                    if (str_starts_with($key, '_discord_')) {
                        unset($_SESSION[$key]);
                    }
                }
                continue;
            }
            @unlink($file);
            $deleted++;
        }

        Logger::info("Discord cache nuke by user {$_SESSION['user_id']}: {$deleted} sessions deleted.");
        Session::flash('success', "Cache Discord rolí vymazána. Smazáno {$deleted} cizích sessions — ostatní uživatelé budou odhlášeni a role se načtou znovu z Discordu.");
        $this->redirect('/management/settings');
    }

    // =========================================================================
    // API Keys
    // =========================================================================

    /**
     * GET /management/api-keys — list all API keys.
     */
    public function apiKeysIndex(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_API_KEYS);

        $keys = (new ApiKeyModel())->getAll();

        $this->render('management/api_keys', [
            'pageTitle'        => 'Management',
            'managementActive' => 'api_keys',
            'managementPerms'  => $this->getManagementPermissions(),
            'apiKeys'          => $keys,
        ]);
    }

    /**
     * GET /management/api-docs — API documentation page.
     */
    public function apiDocsIndex(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_API_KEYS);

        $this->render('management/api_docs', [
            'pageTitle'        => 'Management',
            'managementActive' => 'api_docs',
            'managementPerms'  => $this->getManagementPermissions(),
        ]);
    }

    /**
     * POST /management/api-keys/create — generate a new API key.
     */
    public function apiKeysCreate(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_API_KEYS);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $label      = trim($_POST['label'] ?? '');
        $allowedIps = trim($_POST['allowed_ips'] ?? '');

        if ($label === '') {
            Session::flash('error', 'Název klíče je povinný.');
            $this->redirect('/management/api-keys');
            return;
        }

        // Sanitize IPs — keep only valid IPs.
        $cleanIps = null;
        if ($allowedIps !== '') {
            $parts = array_map('trim', explode(',', $allowedIps));
            $valid = array_filter($parts, static fn(string $ip): bool => filter_var($ip, FILTER_VALIDATE_IP) !== false);
            $cleanIps = !empty($valid) ? implode(',', $valid) : null;

            if ($cleanIps === null) {
                Session::flash('error', 'Žádná zadaná IP není validní.');
                $this->redirect('/management/api-keys');
                return;
            }
        }

        try {
            $key = (new ApiKeyModel())->generate($label, $cleanIps, (int)Permission::userId());
            Session::flash('success', 'API klíč vygenerován: ' . $key);
        } catch (\Throwable $e) {
            Logger::error('API key generate: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se vygenerovat klíč.');
        }

        $this->redirect('/management/api-keys');
    }

    /**
     * POST /management/api-keys/:id/toggle — toggle active/inactive.
     */
    public function apiKeysToggle(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_API_KEYS);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id = (int)($params['id'] ?? 0);

        try {
            (new ApiKeyModel())->toggleActive($id);
            Session::flash('success', 'Stav klíče změněn.');
        } catch (\Throwable $e) {
            Logger::error('API key toggle: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se změnit stav klíče.');
        }

        $this->redirect('/management/api-keys');
    }

    /**
     * POST /management/api-keys/:id/update-ips — update allowed IPs.
     */
    public function apiKeysUpdateIps(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_API_KEYS);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id         = (int)($params['id'] ?? 0);
        $allowedIps = trim($_POST['allowed_ips'] ?? '');

        $cleanIps = null;
        if ($allowedIps !== '') {
            $parts = array_map('trim', explode(',', $allowedIps));
            $valid = array_filter($parts, static fn(string $ip): bool => filter_var($ip, FILTER_VALIDATE_IP) !== false);
            $cleanIps = !empty($valid) ? implode(',', $valid) : null;
        }

        try {
            (new ApiKeyModel())->updateAllowedIps($id, $cleanIps);
            Session::flash('success', 'Povolené IP aktualizovány.');
        } catch (\Throwable $e) {
            Logger::error('API key update IPs: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se aktualizovat IP.');
        }

        $this->redirect('/management/api-keys');
    }

    /**
     * POST /management/api-keys/:id/delete — delete an API key.
     */
    public function apiKeysDelete(array $params = []): void
    {
        $this->requireManagementPermission(self::PERM_API_KEYS);
        $this->requirePost('/management');
        $this->verifyCsrf('/management');

        $id = (int)($params['id'] ?? 0);

        try {
            (new ApiKeyModel())->delete($id);
            Session::flash('success', 'API klíč byl smazán.');
        } catch (\Throwable $e) {
            Logger::error('API key delete: ' . $e->getMessage());
            Session::flash('error', 'Nepodařilo se smazat klíč.');
        }

        $this->redirect('/management/api-keys');
    }
}
