<section class="section">
    <div class="container">
        <h1 class="page-title">Management</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>
        <?php require __DIR__ . '/_panel_nav.php'; ?>
        <h2 class="section-heading">Fronta odvolání</h2>

        <?php if (empty($appeals)): ?>
            <p class="empty-notice">Žádná nevyřízená odvolání.</p>
        <?php else: ?>
            <div class="tester-list">
                <?php foreach ($appeals as $appeal): ?>
                    <?php
                        $typeLabel = match ($appeal['type']) {
                            'ban' => 'Ban',
                            'warn' => 'Warn',
                            'blacklist' => 'Denylist',
                            'allowlist' => 'Nepodařený Allowlist',
                            default => (string)$appeal['type'],
                        };
                    ?>
                    <div class="tester-card card">
                        <div class="tester-card-header">
                            <div>
                                <strong><?= htmlspecialchars($appeal['username']) ?></strong>
                                <span class="tester-meta">
                                    <?= htmlspecialchars($appeal['discord_id']) ?>
                                    &middot; Typ: <em><?= htmlspecialchars($typeLabel) ?></em>
                                    &middot; <?= htmlspecialchars(date('j. n. Y', strtotime($appeal['created_at']))) ?>
                                </span>
                            </div>
                        </div>
                        <blockquote class="appeal-reason"><?= htmlspecialchars($appeal['reason']) ?></blockquote>
                        <?php if (!empty($appeal['staff_present'])): ?>
                            <p class="tester-meta" style="margin:0.3rem 0 0.5rem;">Přítomní: <em><?= htmlspecialchars($appeal['staff_present']) ?></em></p>
                        <?php endif; ?>
                        <div class="tester-actions">
                            <form method="POST" action="/management/appeals/<?= (int)$appeal['id'] ?>/approve" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <button class="btn btn-approve">&#10003; Schválit</button>
                            </form>
                            <form method="POST" action="/management/appeals/<?= (int)$appeal['id'] ?>/reject" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <button class="btn btn-reject">&#10007; Zamítnout</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h2 class="section-heading" style="margin-top:2.5rem;">Historie odvolání</h2>

        <?php if (empty($history)): ?>
            <p class="empty-notice">Žádná vyřízená odvolání.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Hráč</th>
                        <th>Typ</th>
                        <th>Důvod</th>
                        <th>Stav</th>
                        <th>Vyřídil</th>
                        <th>Datum vyřízení</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                        <?php
                            $typeLabel = match ($h['type']) {
                                'ban' => 'Ban',
                                'warn' => 'Warn',
                                'blacklist' => 'Denylist',
                                'allowlist' => 'Nepodařený Allowlist',
                                default => (string)$h['type'],
                            };
                            $statusLabel = $h['status'] === 'approved' ? 'Schváleno' : 'Zamítnuto';
                            $statusClass = $h['status'] === 'approved' ? 'status-approved' : 'status-rejected';
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($h['username']) ?></strong>
                                <br><small><?= htmlspecialchars($h['discord_id']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($typeLabel) ?></td>
                            <td class="appeal-reason-cell"><?= htmlspecialchars($h['reason']) ?></td>
                            <td><span class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                            <td><?= htmlspecialchars($h['reviewer_name'] ?? '—') ?></td>
                            <td><?= $h['reviewed_at'] ? htmlspecialchars(date('j. n. Y H:i', strtotime($h['reviewed_at']))) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
