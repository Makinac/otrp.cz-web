<section class="section">
    <div class="container">
        <h1 class="page-title">Management</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>
        <?php require __DIR__ . '/_panel_nav.php'; ?>
        <h2 class="section-heading">Denylist</h2>

        <!-- Add form -->
        <div class="card">
            <h2 class="card-title">Přidat na denylist</h2>
            <form method="POST" action="/management/denylist/add">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="fields-grid">
                    <div class="form-group">
                        <label class="form-label" for="bl_discord_id">Discord ID <span class="req">*</span></label>
                        <input type="text" id="bl_discord_id" name="discord_id" class="form-control" required placeholder="123456789012345678">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="bl_name">Jméno</label>
                        <input type="text" id="bl_name" name="name" class="form-control" maxlength="100" placeholder="Jméno hráče">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="bl_reason">Důvod</label>
                        <input type="text" id="bl_reason" name="reason" class="form-control" maxlength="500" placeholder="Důvod přidání na denylist">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Přidat</button>
            </form>
        </div>

        <!-- Denylist table -->
        <h2 class="section-heading mt-4">Aktuální denylist</h2>
        <?php if (empty($entries)): ?>
            <p class="empty-notice">Denylist je prázdný.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Discord ID</th>
                        <th>Jméno</th>
                        <th>Důvod</th>
                        <th>Přidal</th>
                        <th>Datum</th>
                        <th>Akce</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td><?= htmlspecialchars($entry['discord_id']) ?></td>
                            <td><?= htmlspecialchars($entry['name'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($entry['reason'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($entry['added_by_username'] ?? '—') ?></td>
                            <td><?= htmlspecialchars(date('j. n. Y', strtotime($entry['added_at']))) ?></td>
                            <td>
                                <form method="POST" action="/management/denylist/remove/<?= (int)$entry['id'] ?>"
                                      onsubmit="return confirm('Odebrat z denylistu?')">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <button class="btn btn-ghost btn-sm">Odebrat</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
