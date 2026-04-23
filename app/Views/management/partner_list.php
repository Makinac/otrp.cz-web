<section class="section">
    <div class="container">
        <h1 class="page-title">Management</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>
        <?php require __DIR__ . '/_panel_nav.php'; ?>

        <div class="content-list-header">
            <h2 class="section-heading">Partneři</h2>
            <a href="/management/partners/new" class="btn btn-primary">&#43; Nový partner</a>
        </div>

        <?php if (empty($partners)): ?>
            <p class="empty-notice">Žádní partneři.</p>
        <?php else: ?>
            <div class="partner-mgmt-grid">
                <?php foreach ($partners as $i => $p): ?>
                    <div class="partner-mgmt-card<?= !$p['active'] ? ' partner-mgmt-card--hidden' : '' ?>">
                        <div class="partner-mgmt-card-top">
                            <div class="partner-mgmt-logo">
                                <?php if (!empty($p['logo_url'])): ?>
                                    <img src="<?= htmlspecialchars($p['logo_url']) ?>" alt="" class="partner-mgmt-logo-img">
                                <?php else: ?>
                                    <span class="partner-mgmt-logo-fallback"><?= mb_strtoupper(mb_substr($p['name'], 0, 1)) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="partner-mgmt-info">
                                <h3 class="partner-mgmt-name"><?= htmlspecialchars($p['name']) ?></h3>
                                <?php if (!empty($p['url'])): ?>
                                    <span class="partner-mgmt-url"><?= htmlspecialchars(mb_strimwidth($p['url'], 0, 40, '…')) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="partner-mgmt-status">
                                <?php if ($p['active']): ?>
                                    <span class="status-badge status-approved">Aktivní</span>
                                <?php else: ?>
                                    <span class="status-badge status-pending">Skrytý</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="partner-mgmt-card-bottom">
                            <div class="partner-mgmt-sort">
                                <?php if ($i > 0): ?>
                                    <form method="POST" action="/management/partners/<?= (int)$p['id'] ?>/move">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="direction" value="up">
                                        <button class="sort-btn" title="Posunout nahoru">&#9650;</button>
                                    </form>
                                <?php else: ?>
                                    <span class="sort-btn sort-btn-disabled">&#9650;</span>
                                <?php endif; ?>
                                <span class="sort-pos"><?= $i + 1 ?></span>
                                <?php if ($i < count($partners) - 1): ?>
                                    <form method="POST" action="/management/partners/<?= (int)$p['id'] ?>/move">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="direction" value="down">
                                        <button class="sort-btn" title="Posunout dolů">&#9660;</button>
                                    </form>
                                <?php else: ?>
                                    <span class="sort-btn sort-btn-disabled">&#9660;</span>
                                <?php endif; ?>
                            </div>
                            <div class="partner-mgmt-actions">
                                <a href="/management/partners/<?= (int)$p['id'] ?>/edit" class="btn btn-ghost btn-sm">Upravit</a>
                                <form method="POST" action="/management/partners/<?= (int)$p['id'] ?>/delete"
                                      onsubmit="return confirm('Smazat partnera?')">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <button class="btn btn-reject btn-sm">Smazat</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
