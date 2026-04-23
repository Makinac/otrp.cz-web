<?php
$filterTabs = [
    'open'   => 'Otevřené',
    'closed' => 'Uzavřené',
    'all'    => 'Vše',
];
?>
<section class="section">
    <div class="container">
        <h1 class="page-title">Admin Panel</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>

        <?php require __DIR__ . '/_panel_nav.php'; ?>
        <div class="app-filter-tabs">
            <?php foreach ($filterTabs as $key => $label): ?>
                <a href="/admin/ck?filter=<?= $key ?>"
                   class="app-filter-tab <?= $filter === $key ? 'app-filter-tab-active' : '' ?>">
                    <?= $label ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Action bar -->
        <div style="margin-bottom:1.25rem;">
            <a href="/admin/ck/new" class="btn btn-primary">+ Nové hlasování</a>
        </div>

        <!-- Votes list -->
        <?php if (empty($votes)): ?>
            <p class="empty-notice">Žádná hlasování v této kategorii.</p>
        <?php else: ?>
            <div class="app-list">
                <?php foreach ($votes as $v): ?>
                    <?php
                        if ($v['status'] === 'open') {
                            $badgeCls = 'status-pending';
                            $badgeLabel = 'Otevřené';
                        } elseif ($v['result'] === 'approved') {
                            $badgeCls = 'status-active';
                            $badgeLabel = 'Schváleno';
                        } elseif ($v['result'] === 'rejected') {
                            $badgeCls = 'status-rejected';
                            $badgeLabel = 'Zamítnuto';
                        } else {
                            $badgeCls = 'status-blocked';
                            $badgeLabel = 'Nerozhodně';
                        }
                    ?>
                    <a href="/admin/ck/<?= (int)$v['id'] ?>" class="app-row">
                        <div class="ck-row-icon">
                            <svg viewBox="0 0 20 20" fill="currentColor" width="24" height="24"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        </div>
                        <div class="app-row-info">
                            <span class="app-row-name"><?= htmlspecialchars($v['applicant']) ?> → <?= htmlspecialchars($v['victim']) ?></span>
                            <span class="app-row-meta">
                                Vytvořil <?= htmlspecialchars($v['creator_username']) ?> · <?= date('j. n. Y H:i', strtotime($v['created_at'])) ?>
                                · Hlasů: <?= (int)$v['total_votes'] ?>
                            </span>
                        </div>
                        <span class="status-badge <?= $badgeCls ?>"><?= $badgeLabel ?></span>
                        <span class="app-row-arrow">›</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
