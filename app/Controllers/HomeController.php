<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ContentPageModel;
use App\Models\NewsModel;
use App\Models\PartnerModel;
use App\Models\RulesSectionModel;
use App\Models\SiteSettingModel;
use App\Models\TeamCacheModel;
use App\Models\TeamCategoryModel;
use App\Auth\DiscordOAuth;
use App\Core\Logger;

/**
 * Handles all public-facing pages.
 */
class HomeController extends BaseController
{
    /**
     * Display the home page.
     *
     * @param array<string,string> $params Route parameters (unused).
     */
    public function index(array $params = []): void
    {
        $model = new SiteSettingModel();
        $raw   = $model->get('home.blocks');
        $blocks = $raw ? json_decode($raw, true) : null;

        // Fallback to old key-value settings if blocks not saved yet
        if ($blocks === null) {
            $blocks = [
                ['type' => 'hero', 'data' => [
                    'title' => '',
                    'desc'  => 'Old Times RP je česko-slovenský roleplay server pro Red Dead Redemption 2. Ponořte se do světa divokého Západu — kde zákon sahá jen tak daleko, jak daleko dohlédnete.',
                ]],
                ['type' => 'buttons', 'data' => ['items' => [
                    ['text' => 'Přihlásit se',     'url' => '/auth/redirect',                   'style' => 'primary'],
                    ['text' => 'Číst pravidla',     'url' => '/pravidla',                        'style' => 'secondary'],
                    ['text' => 'Připojit na Discord','url' => 'https://discord.gg/BmERSfVH9M',  'style' => 'discord'],
                ]]],
                ['type' => 'heading', 'data' => ['text' => 'Proč Old Times RP?', 'ornament' => true, 'level' => 2]],
                ['type' => 'cards', 'data' => ['items' => [
                    [
                        'title' => 'Autentický Západ',
                        'text'  => 'Detailně propracovaný svět s důrazem na realismus a hlubokou atmosféru divokého Západu. Každý charakter má svůj příběh.',
                    ],
                    [
                        'title' => 'Allowlist systém',
                        'text'  => 'Dvoustupňový allowlist (formulář + herní pohovor) zajišťuje kvalitu komunity a seriózní přístup ke hře.',
                    ],
                    [
                        'title' => 'Aktivní komunita',
                        'text'  => 'Stovky hráčů, pravidelné eventy a zkušený tým, který pečuje o fair-play a rozvoj serveru každý den.',
                    ],
                    [
                        'title' => 'Pravidelné eventy',
                        'text'  => 'Organizované hráčské i GM eventy — hrdinové, zločinci, lovci odměn. Vždycky je co dělat.',
                    ],
                    [
                        'title' => 'Vlastní skript',
                        'text'  => 'Unikátní herní mechaniky vyvíjené přímo pro naši komunitu. Zrání, ekonomika, pozice — vše na míru.',
                    ],
                    [
                        'title' => 'Bezpečné prostředí',
                        'text'  => 'Jasná pravidla, aktivní moderace a nulová tolerance hackerů a toxicity. Hraješ v klidu.',
                    ],
                ]]],
                ['type' => 'heading', 'data' => ['text' => 'Jak se připojit?', 'ornament' => false, 'level' => 2]],
                ['type' => 'steps', 'data' => ['items' => [
                    [
                        'title' => 'Přihlaš se přes Discord',
                        'text'  => 'Klikni na tlačítko "Přihlásit se" a autorizuj se svým Discord účtem. Je to rychlé, bezpečné a nevyžaduje registraci.',
                    ],
                    [
                        'title' => 'Vyplň žádost o allowlist',
                        'text'  => 'Pečlivě vyplň allowlist formulář. Odpovídej upřímně — hodnotíme tvůj přístup ke hře, ne perfektní znalosti lore.',
                    ],
                    [
                        'title' => 'Absolvuj herní pohovor',
                        'text'  => 'Po schválení žádosti tě tester pozve na krátký herní pohovor. Úspěšné absolvování otevírá dveře na server — vítej na Západě!',
                    ],
                ]]],
            ];
        }

        $newsModel  = new NewsModel();
        $latestNews = $newsModel->getPaginated(1, 1)[0] ?? null;

        $this->render('home', [
            'pageTitle'  => 'Domů',
            'blocks'     => $blocks,
            'latestNews' => $latestNews,
        ]);
    }

    /**
     * Display paginated news list.
     *
     * @param array<string,string> $params Route parameters.
     */
    public function news(array $params = []): void
    {
        $model   = new NewsModel();
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $total   = $model->countAll();
        $items   = $model->getPaginated($page, $perPage);

        $this->render('news', [
            'pageTitle' => 'Novinky',
            'items'     => $items,
            'page'      => $page,
            'perPage'   => $perPage,
            'total'     => $total,
        ]);
    }

    /**
     * Display a single news article.
     *
     * @param array<string,string> $params Route parameters — expects :slug.
     */
    public function newsDetail(array $params = []): void
    {
        $model = new NewsModel();
        $item  = $model->findBySlug($params['slug'] ?? '');

        if (!$item) {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => '404']);
            return;
        }

        $this->render('news_detail', ['pageTitle' => htmlspecialchars($item['title']), 'item' => $item]);
    }

    /**
     * Display the team page, refreshing from Discord API if cache is stale.
     *
     * @param array<string,string> $params Route parameters (unused).
     */
    public function team(array $params = []): void
    {
        $cacheModel = new TeamCacheModel();
        $catModel   = new TeamCategoryModel();

        $categories = $catModel->getAll();

        if (!$cacheModel->isFresh() && !empty($categories)) {
            // Refresh from Discord API.
            try {
                $allRoleIds = [];
                foreach ($categories as $cat) {
                    $catRoles   = json_decode($cat['role_ids_json'] ?? '[]', true) ?? [];
                    $allRoleIds = array_merge($allRoleIds, $catRoles);
                }
                $allRoleIds = array_unique(array_map('strval', $allRoleIds));

                if (!empty($allRoleIds)) {
                    $rawMembers = DiscordOAuth::getGuildMembersWithRoleIds($allRoleIds);

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
            } catch (\Throwable $e) {
                Logger::error('Team cache refresh failed: ' . $e->getMessage());
            }
        }

        // Build category → members structure for the view.
        // Deduplicate: a member appears only in the first (highest-priority) category.
        $allCached = $cacheModel->getAll();
        $membersByCategory = [];
        foreach ($allCached as $m) {
            $cid = (int)($m['category_id'] ?? 0);
            $membersByCategory[$cid][] = $m;
        }

        $seenDiscordIds = [];
        $teamSections = [];
        foreach ($categories as $cat) {
            $catId   = (int)$cat['id'];
            $raw     = $membersByCategory[$catId] ?? [];
            $unique  = [];
            foreach ($raw as $m) {
                $did = $m['discord_id'] ?? '';
                if ($did !== '' && isset($seenDiscordIds[$did])) {
                    continue;
                }
                $seenDiscordIds[$did] = true;
                $unique[] = $m;
            }
            $teamSections[] = [
                'name'    => $cat['name'],
                'color'   => $cat['color'] ?? null,
                'members' => $unique,
            ];
        }

        $this->render('team', [
            'pageTitle'    => 'Tým',
            'teamSections' => $teamSections,
        ]);
    }

    /**
     * Display the rules page.
     *
     * @param array<string,string> $params Route parameters (unused).
     */
    public function rules(array $params = []): void
    {
        $model    = new RulesSectionModel();
        $sections = $model->getAll();

        $this->render('rules', [
            'pageTitle' => 'Pravidla',
            'sections'  => $sections,
        ]);
    }

    /**
     * Display the login page.
     *
     * @param array<string,string> $params Route parameters (unused).
     */
    public function login(array $params = []): void
    {
        $this->render('login', [
            'pageTitle'  => 'Přihlášení',
            'discordUrl' => DiscordOAuth::getAuthUrl(),
        ]);
    }

    /**
     * Display the partners page.
     *
     * @param array<string,string> $params Route parameters (unused).
     */
    public function partners(array $params = []): void
    {
        $model    = new PartnerModel();
        $partners = $model->getActive();

        $this->render('partners', [
            'pageTitle' => 'Partneři',
            'partners'  => $partners,
        ]);
    }
}
