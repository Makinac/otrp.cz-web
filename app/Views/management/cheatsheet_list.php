<section class="section">
    <div class="container">
        <h1 class="page-title">Management</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>
        <?php require __DIR__ . '/_panel_nav.php'; ?>
        <h2 class="section-heading">Tahák — otázky k pohovoru</h2>

        <div class="admin-toolbar">
            <a href="/management/cheatsheet/new" class="btn btn-primary">&#43; Nová otázka</a>
        </div>

        <?php if (empty($sections)): ?>
            <p class="empty-notice">Žádné otázky. Přidejte první kliknutím na tlačítko výše.</p>
        <?php else: ?>
            <table class="data-table rules-admin-table">
                <thead>
                    <tr>
                        <th style="width:90px">Pořadí</th>
                        <th>Otázka</th>
                        <th>Naposledy upraven</th>
                        <th>Akce</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sections as $i => $sec): ?>
                        <tr>
                            <td class="sort-cell">
                                <span class="sort-pos"><?= $i + 1 ?></span>
                                <?php if ($i > 0): ?>
                                    <form method="POST" action="/management/cheatsheet/<?= (int)$sec['id'] ?>/move">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="direction" value="up">
                                        <button class="sort-btn" title="Posunout nahoru">&#9650;</button>
                                    </form>
                                <?php else: ?>
                                    <span class="sort-btn sort-btn-disabled">&#9650;</span>
                                <?php endif; ?>
                                <?php if ($i < count($sections) - 1): ?>
                                    <form method="POST" action="/management/cheatsheet/<?= (int)$sec['id'] ?>/move">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="direction" value="down">
                                        <button class="sort-btn" title="Posunout dolů">&#9660;</button>
                                    </form>
                                <?php else: ?>
                                    <span class="sort-btn sort-btn-disabled">&#9660;</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($sec['title']) ?></td>
                            <td><?= htmlspecialchars(date('j. n. Y H:i', strtotime($sec['updated_at']))) ?></td>
                            <td class="actions-cell">
                                <a href="/management/cheatsheet/<?= (int)$sec['id'] ?>/edit" class="btn btn-ghost btn-sm">Upravit</a>
                                <form method="POST" action="/management/cheatsheet/<?= (int)$sec['id'] ?>/delete"
                                      style="display:inline"
                                      onsubmit="return confirm('Smazat otázku &quot;<?= htmlspecialchars(addslashes($sec['title'])) ?>&quot;?')">
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
