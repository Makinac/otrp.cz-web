<section class="section">
    <div class="container">
        <h1 class="page-title">Management</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>
        <?php require __DIR__ . '/_panel_nav.php'; ?>
        <h2 class="section-heading">Formuláře žádostí</h2>
        <div class="admin-toolbar">
            <a href="/management/form/new" class="btn btn-primary">&#43; Nový formulář</a>
        </div>

        <?php if (empty($schemas)): ?>
            <p class="empty-notice">Žádná schémata formulářů.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Název</th>
                        <th>Aktivní</th>
                        <th>Vytvořeno</th>
                        <th>Akce</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schemas as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['name']) ?></td>
                            <td><?= $s['active'] ? '<span class="status-badge status-active">Ano</span>' : '—' ?></td>
                            <td><?= htmlspecialchars(date('j. n. Y', strtotime($s['created_at']))) ?></td>
                            <td class="actions-cell">
                                <a href="/management/form/<?= (int)$s['id'] ?>/edit" class="btn btn-ghost btn-sm">Upravit</a>
                                <?php if (!$s['active']): ?>
                                    <form method="POST" action="/management/form/<?= (int)$s['id'] ?>/activate" style="display:inline">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <button class="btn btn-secondary btn-sm">Aktivovat</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" action="/management/form/<?= (int)$s['id'] ?>/delete" style="display:inline"
                                      onsubmit="return confirm('Opravdu smazat tento formulář?')">
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
