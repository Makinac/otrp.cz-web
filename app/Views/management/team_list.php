<section class="section">
    <div class="container">
        <h1 class="page-title">Management</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>
        <?php require __DIR__ . '/_panel_nav.php'; ?>
        <h2 class="section-heading">Správa týmu</h2>

        <div class="admin-toolbar" style="display:flex;gap:.75rem;align-items:center;">
            <a href="/management/team/new" class="btn btn-primary">&#43; Nová kategorie</a>
            <form method="POST" action="/management/team/refresh" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <button class="btn btn-ghost" onclick="return confirm('Vynutit obnovení dat z Discordu?')">&#8635; Obnovit data z Discordu</button>
            </form>
            <?php if (!empty($cacheAge)): ?>
                <span class="tester-meta">Poslední aktualizace: <?= htmlspecialchars($cacheAge) ?></span>
            <?php endif; ?>
        </div>

        <?php if (empty($categories)): ?>
            <p class="empty-notice">Žádné kategorie. Přidejte první kliknutím na tlačítko výše.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:90px">Pořadí</th>
                        <th>Název</th>
                        <th>Role</th>
                        <th>Členů</th>
                        <th>Akce</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $i => $cat): ?>
                        <?php
                            $roleIds = json_decode($cat['role_ids_json'] ?? '[]', true) ?? [];
                            $memberCount = 0;
                            foreach ($cachedMembers as $m) {
                                if (((int)($m['category_id'] ?? 0)) === (int)$cat['id']) $memberCount++;
                            }
                        ?>
                        <tr>
                            <td class="sort-cell">
                                <span class="sort-pos"><?= $i + 1 ?></span>
                                <?php if ($i > 0): ?>
                                    <form method="POST" action="/management/team/<?= (int)$cat['id'] ?>/move">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="direction" value="up">
                                        <button class="sort-btn" title="Posunout nahoru">&#9650;</button>
                                    </form>
                                <?php else: ?>
                                    <span class="sort-btn sort-btn-disabled">&#9650;</span>
                                <?php endif; ?>
                                <?php if ($i < count($categories) - 1): ?>
                                    <form method="POST" action="/management/team/<?= (int)$cat['id'] ?>/move">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="direction" value="down">
                                        <button class="sort-btn" title="Posunout dolů">&#9660;</button>
                                    </form>
                                <?php else: ?>
                                    <span class="sort-btn sort-btn-disabled">&#9660;</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($cat['color'])): ?>
                                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?= htmlspecialchars($cat['color']) ?>;vertical-align:middle;margin-right:.4rem;"></span>
                                <?php endif; ?>
                                <?= htmlspecialchars($cat['name']) ?>
                            </td>
                            <td>
                                <?php foreach ($roleIds as $rid): ?>
                                    <span class="role-badge"><?= htmlspecialchars($knownRoleNames[$rid] ?? $rid) ?></span>
                                <?php endforeach; ?>
                            </td>
                            <td><?= $memberCount ?></td>
                            <td class="actions-cell">
                                <a href="/management/team/<?= (int)$cat['id'] ?>/edit" class="btn btn-ghost btn-sm">Upravit</a>
                                <form method="POST" action="/management/team/<?= (int)$cat['id'] ?>/delete"
                                      style="display:inline"
                                      onsubmit="return confirm('Smazat kategorii &quot;<?= htmlspecialchars(addslashes($cat['name'])) ?>&quot;?')">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <button class="btn btn-reject btn-sm">Smazat</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
