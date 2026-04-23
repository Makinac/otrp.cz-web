<?php
$tabs = [
    'pending'   => ['label' => 'Čeká na posouzení', 'cls' => 'status-pending'],
    'interview' => ['label' => 'Pohovor',            'cls' => 'status-approved'],
    'rejected'  => ['label' => 'Zamítnuto',          'cls' => 'status-rejected'],
    'all'       => ['label' => 'Vše',                'cls' => ''],
];
if (!empty($isVedeni)) {
    // Vedení can also see the "Aktivní" tab
    $tabs = [
        'pending'   => ['label' => 'Čeká na posouzení', 'cls' => 'status-pending'],
        'interview' => ['label' => 'Pohovor',            'cls' => 'status-approved'],
        'active'    => ['label' => 'Aktivní',            'cls' => 'status-active'],
        'rejected'  => ['label' => 'Zamítnuto',          'cls' => 'status-rejected'],
        'all'       => ['label' => 'Vše',                'cls' => ''],
    ];
}

function appRowBadge(array $app): array {
    $s  = $app['status'];
    $iv = $app['interview_status'] ?? null;
    if ($s === 'approved' && $iv === 'passed')  return ['cls' => 'status-active',   'label' => 'Aktivní'];
    if ($s === 'approved')                      return ['cls' => 'status-approved',  'label' => 'Čeká na pohovor'];
    if ($s === 'rejected')                      return ['cls' => 'status-rejected',  'label' => 'Zamítnuto'];
    if ($s === 'blocked' && $iv === 'failed')   return ['cls' => 'status-blocked',  'label' => 'Pohovor nesplněn'];
    if ($s === 'blocked')                       return ['cls' => 'status-blocked',   'label' => 'Zablokováno'];
    return ['cls' => 'status-pending', 'label' => 'Čeká na posouzení'];
}
?>
<section class="section">
    <div class="container">
        <h1 class="page-title">Admin Panel</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>

        <?php require __DIR__ . '/_panel_nav.php'; ?>

        <!-- Filter tabs -->
        <div class="app-filter-tabs">
            <?php foreach ($tabs as $key => $tab): ?>
                <a href="/admin?filter=<?= $key ?><?= $search !== '' ? '&q=' . urlencode($search) : '' ?>"
                   class="app-filter-tab <?= $filter === $key ? 'app-filter-tab-active' : '' ?>">
                    <?= $tab['label'] ?>
                    <?php if ($counts[$key] > 0): ?>
                        <span class="app-filter-count"><?= $counts[$key] ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Search bar -->
        <form method="GET" action="/admin" class="player-search-form" style="margin-bottom:1.25rem;">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <div class="player-search-wrap">
                <svg class="player-search-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                <input
                    type="text"
                    name="q"
                    class="player-search-input"
                    placeholder="Hledat podle jména nebo Discord ID…"
                    value="<?= htmlspecialchars($search) ?>"
                >
                <?php if ($search !== ''): ?>
                    <a href="/admin?filter=<?= htmlspecialchars($filter) ?>" class="player-search-clear">&#10005;</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Applications list -->
        <?php if (empty($applications)): ?>
            <p class="empty-notice">Žádné žádosti v této kategorii.</p>
        <?php else: ?>
            <div class="app-list">
                <?php foreach ($applications as $app): ?>
                    <?php $badge = appRowBadge($app); ?>
                    <a href="/admin/<?= (int)$app['id'] ?>" class="app-row">
                        <img
                            src="<?= $app['avatar'] ? 'https://cdn.discordapp.com/avatars/' . htmlspecialchars($app['discord_id']) . '/' . htmlspecialchars($app['avatar']) . '.png' : 'https://cdn.discordapp.com/embed/avatars/0.png' ?>"
                            alt=""
                            class="app-row-avatar"
                            width="36" height="36"
                        >
                        <div class="app-row-info">
                            <span class="app-row-name"><?= htmlspecialchars($app['username']) ?></span>
                            <span class="app-row-meta">Pokus #<?= (int)$app['attempt_number'] ?> · <?= date('j. n. Y H:i', strtotime($app['submitted_at'])) ?></span>
                        </div>
                        <span class="status-badge <?= $badge['cls'] ?>"><?= $badge['label'] ?></span>
                        <span class="app-row-arrow">›</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
