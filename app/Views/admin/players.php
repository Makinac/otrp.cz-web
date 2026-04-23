<section class="section">
    <div class="container">
        <h1 class="page-title">Admin Panel</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>

        <?php require __DIR__ . '/_panel_nav.php'; ?>

        <!-- Search -->
        <form method="GET" action="/admin/players" class="player-search-form">
            <div class="player-search-wrap">
                <svg class="player-search-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                <input
                    type="text"
                    name="q"
                    class="player-search-input"
                    placeholder="Hledat podle jména nebo Discord ID…"
                    value="<?= htmlspecialchars($query) ?>"
                    autofocus
                >
                <?php if ($query !== ''): ?>
                    <a href="/admin/players" class="player-search-clear">&#10005;</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Results -->
        <div class="player-list">
            <?php if (empty($users)): ?>
                <p class="empty-notice">
                    <?= $query !== '' ? 'Žádní hráči nenalezeni pro „' . htmlspecialchars($query) . '".' : 'Zatím žádní registrovaní hráči.' ?>
                </p>
            <?php else: ?>
                <?php if ($query !== ''): ?>
                    <p class="player-result-count"><?= count($users) ?> <?= count($users) === 1 ? 'výsledek' : (count($users) <= 4 ? 'výsledky' : 'výsledků') ?></p>
                <?php endif; ?>
                <?php foreach ($users as $user): ?>
                    <?php
                        $ls  = $user['latest_status'] ?? null;
                        $liv = $user['latest_interview_status'] ?? null;
                        if ($user['is_blacklisted']) {
                            $statusCls = 'status-blocked'; $statusLabel = 'Denylist';
                        } elseif ($ls === 'approved' && $liv === 'passed') {
                            $statusCls = 'status-active';   $statusLabel = 'Aktivní';
                        } elseif ($ls === 'approved') {
                            $statusCls = 'status-approved'; $statusLabel = 'Pohovor';
                        } elseif ($ls === 'pending') {
                            $statusCls = 'status-pending';  $statusLabel = 'Čeká';
                        } elseif ($ls === 'rejected' || ($ls === 'blocked' && $liv === 'failed')) {
                            $statusCls = 'status-rejected'; $statusLabel = 'Zamítnuto';
                        } elseif ($ls === 'blocked') {
                            $statusCls = 'status-blocked';  $statusLabel = 'Blokován';
                        } else {
                            $statusCls = 'status-none';     $statusLabel = 'Bez žádosti';
                        }
                        $avatarUrl = $user['avatar']
                            ? 'https://cdn.discordapp.com/avatars/' . htmlspecialchars($user['discord_id']) . '/' . htmlspecialchars($user['avatar']) . '.png'
                            : 'https://cdn.discordapp.com/embed/avatars/0.png';
                    ?>
                    <a href="/admin/players/<?= (int)$user['id'] ?>" class="player-card">
                        <img src="<?= $avatarUrl ?>" alt="<?= htmlspecialchars($user['username']) ?>" class="player-avatar" width="36" height="36">
                        <div class="player-info">
                            <span class="player-name"><?= htmlspecialchars($user['username']) ?></span>
                            <span class="player-discord-id"><?= htmlspecialchars($user['discord_id']) ?></span>
                        </div>
                        <span class="player-attempts"><?= (int)$user['app_count'] ?> <?= (int)$user['app_count'] === 1 ? 'žádost' : ((int)$user['app_count'] <= 4 ? 'žádosti' : 'žádostí') ?></span>
                        <span class="status-badge <?= $statusCls ?>"><?= $statusLabel ?></span>
                        <span class="app-row-arrow">›</span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
