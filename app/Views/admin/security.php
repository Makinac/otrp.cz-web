<?php
$tabs = [
    'all'        => ['label' => 'Vše',         'count' => 0],
    'critical'   => ['label' => 'Kritické',    'count' => $counts['critical']],
    'warning'    => ['label' => 'Varování',    'count' => $counts['warning']],
    'info'       => ['label' => 'Info',         'count' => $counts['info']],
];

function severityBadge(string $severity): array {
    return match ($severity) {
        'critical' => ['cls' => 'status-blocked',  'label' => 'Kritické'],
        'warning'  => ['cls' => 'status-warning',  'label' => 'Varování'],
        default    => ['cls' => 'status-pending',  'label' => 'Info'],
    };
}

function eventTypeLabel(string $type): string {
    return match ($type) {
        'new_identifier'      => 'Nový identifikátor',
        'identifier_conflict' => 'Změna identifikátoru',
        'multi_account'       => 'Sdílený účet',
        default               => $type,
    };
}
?>
<section class="section">
    <div class="container">
        <h1 class="page-title">Admin Panel</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>

        <?php require __DIR__ . '/_panel_nav.php'; ?>

        <?php if ($msg = \App\Core\Session::getFlash('success')): ?>
            <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if ($msg = \App\Core\Session::getFlash('error')): ?>
            <div class="alert alert-error"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <!-- Filter tabs -->
        <div class="app-filter-tabs">
            <?php foreach ($tabs as $key => $tab): ?>
                <a href="/admin/security?filter=<?= $key ?>"
                   class="app-filter-tab <?= $filter === $key ? 'app-filter-tab-active' : '' ?>">
                    <?= $tab['label'] ?>
                    <?php if ($tab['count'] > 0): ?>
                        <span class="app-filter-count"><?= $tab['count'] ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($logs)): ?>
            <p class="empty-notice" style="margin-top:2rem;">Žádné bezpečnostní záznamy.</p>
        <?php else: ?>
            <div class="pd-action-list" style="margin-top:1rem;">
                <?php foreach ($logs as $log): ?>
                    <?php
                        $badge = severityBadge($log['severity']);
                        $avatarUrl = $log['avatar']
                            ? 'https://cdn.discordapp.com/avatars/' . htmlspecialchars($log['discord_id']) . '/' . htmlspecialchars($log['avatar']) . '.png'
                            : 'https://cdn.discordapp.com/embed/avatars/0.png';
                        $itemCls = 'pd-action-item' . ($log['severity'] === 'critical' ? ' pd-ban' : ($log['severity'] === 'warning' ? ' pd-warn' : ''));
                    ?>
                    <div class="<?= $itemCls ?>">
                        <div class="pd-action-info">
                            <span class="pd-action-reason" style="display:flex;align-items:center;gap:0.5rem;">
                                <img src="<?= $avatarUrl ?>" alt="" width="24" height="24" style="border-radius:50%;flex-shrink:0;">
                                <a href="/admin/players/<?= (int)$log['user_id'] ?>" style="color:var(--gold);text-decoration:none;"><?= htmlspecialchars($log['username']) ?></a>
                                <span class="status-badge <?= $badge['cls'] ?>" style="font-size:0.7rem;"><?= $badge['label'] ?></span>
                            </span>
                            <span class="pd-action-reason"><?= htmlspecialchars($log['description']) ?></span>
                            <span class="pd-action-meta">
                                <?= eventTypeLabel($log['event_type']) ?>
                                &nbsp;·&nbsp;
                                <?= date('j. n. Y H:i', strtotime($log['created_at'])) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>
