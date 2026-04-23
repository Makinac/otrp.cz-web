<?php
$qp  = $qpBreakdown  ?? ['total' => 0, 'roles_total' => 0, 'bonuses_total' => 0, 'role_hits' => [], 'discord_error' => true];
$ch  = $charBreakdown ?? ['total' => 1, 'roles_total' => 0, 'bonuses_total' => 0, 'role_hits' => [], 'discord_error' => true];
$ped = $pedAccess     ?? false;
?>
<section class="section">
    <div class="container">
        <h1 class="page-title">Výhody</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>

        <!-- ── Code Redemption ────────────────────────────────────────────── -->
        <div class="card benefits-redeem-card" style="margin-bottom:2rem;">
            <h2 class="card-title">Uplatit kód</h2>
            <p style="color:var(--text-muted);margin-bottom:1.25rem;">
                Máš kód od vedení nebo z Tebex? Zadej ho níže a získej QP, sloty pro postavu nebo přístup k ped menu.
            </p>
            <form method="POST" action="/vyhody/redeem" class="benefits-redeem-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="benefits-redeem-row">
                    <input
                        type="text"
                        name="code"
                        class="form-control benefits-code-input"
                        placeholder="XXXX-XXXX-XXXX"
                        maxlength="14"
                        pattern="[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}"
                        title="Formát: XXXX-XXXX-XXXX"
                        autocomplete="off"
                        spellcheck="false"
                        required
                    >
                    <button type="submit" class="btn btn-primary">Uplatnit</button>
                </div>
            </form>
        </div>

        <!-- ── Redemption history ────────────────────────────────────────── -->
        <?php if (!empty($redeemHistory)): ?>
        <div class="card rh-card" style="margin-bottom:2rem;">
            <h2 class="card-title">Historie uplatněných kódů</h2>
            <div class="rh-list">
                <?php foreach ($redeemHistory as $row): ?>
                <?php
                    $isQp     = $row['type'] === 'qp';
                    $isPed    = $row['type'] === 'ped';
                    $valLabel = $isQp
                        ? '+' . number_format((int)$row['amount'], 0, ',', ' ') . ' QP'
                        : ($isPed
                            ? 'Ped menu'
                            : '+' . (int)$row['amount'] . ' ' . ((int)$row['amount'] === 1 ? 'slot' : 'sloty'));
                    $active   = (bool)$row['is_active'];
                ?>
                <div class="rh-row <?= $active ? 'rh-row-active' : 'rh-row-inactive' ?>">
                    <!-- icon -->
                    <div class="rh-icon">
                        <?php if ($isQp): ?>
                            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <?php elseif ($isPed): ?>
                            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/></svg>
                        <?php else: ?>
                            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/></svg>
                        <?php endif; ?>
                    </div>
                    <!-- main info -->
                    <div class="rh-main">
                        <span class="rh-code"><?= htmlspecialchars($row['code']) ?></span>
                        <?php if (!empty($row['note'])): ?>
                            <span class="rh-note"><?= htmlspecialchars($row['note']) ?></span>
                        <?php endif; ?>
                    </div>
                    <!-- value -->
                    <div class="rh-value"><?= $valLabel ?></div>
                    <!-- meta -->
                    <div class="rh-meta">
                        <span class="rh-date"><?= htmlspecialchars(substr($row['redeemed_at'], 0, 16)) ?></span>
                        <span class="rh-expiry">
                            <?php if ($row['expires_at']): ?>
                                <?php if (!$active && strtotime($row['expires_at']) <= time()): ?>
                                    <span style="color:var(--red);">expirováno <?= htmlspecialchars(substr($row['expires_at'], 0, 10)) ?></span>
                                <?php else: ?>
                                    do <?= htmlspecialchars(substr($row['expires_at'], 0, 10)) ?>
                                <?php endif; ?>
                            <?php else: ?>
                                bez expirace
                            <?php endif; ?>
                        </span>
                    </div>
                    <!-- badge -->
                    <div class="rh-status">
                        <span class="badge <?= $active ? 'badge-active' : 'badge-inactive' ?>">
                            <?= $active ? 'aktivní' : 'neaktivní' ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="benefits-grid">

            <!-- ── QP (QuePoints) ───────────────────────────────────────── -->
            <div class="card benefits-card">
                <h2 class="card-title">
                    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18" style="vertical-align:-3px;margin-right:.4rem;"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    QP (QuePoints)
                </h2>

                <?php if (!empty($qp['discord_error'])): ?>
                    <div class="alert alert-error" style="margin-bottom:1rem;">
                        Nepodařilo se načíst Discord role. Hodnota nemusí být aktuální.
                    </div>
                <?php endif; ?>

                <div class="benefits-stat-row">
                    <span class="benefits-stat-value"><?= number_format((int)$qp['total'], 0, ',', ' ') ?></span>
                    <span class="benefits-stat-label">QP celkem</span>
                </div>

                <div class="benefits-breakdown">
                    <span>Role: <strong><?= number_format((int)$qp['roles_total'], 0, ',', ' ') ?></strong></span>
                    <span>Bonusy: <strong><?= number_format((int)$qp['bonuses_total'], 0, ',', ' ') ?></strong></span>
                </div>

                <?php if (!empty($qp['role_hits'])): ?>
                    <div class="qp-role-hits" style="margin-top:1rem;">
                        <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:.5rem;">Přispívající role:</p>
                        <div class="qp-role-pills">
                            <?php foreach ($qp['role_hits'] as $hit): ?>
                                <span class="qp-role-pill">
                                    <?= htmlspecialchars($hit['role_name']) ?>
                                    <strong>+<?= number_format((int)$hit['qp_value'], 0, ',', ' ') ?></strong>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ── Character Slots ──────────────────────────────────────── -->
            <div class="card benefits-card">
                <h2 class="card-title">
                    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18" style="vertical-align:-3px;margin-right:.4rem;"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/></svg>
                    Sloty pro postavy
                </h2>

                <?php if (!empty($ch['discord_error'])): ?>
                    <div class="alert alert-error" style="margin-bottom:1rem;">
                        Nepodařilo se načíst Discord role. Hodnota nemusí být aktuální.
                    </div>
                <?php endif; ?>

                <div class="benefits-stat-row">
                    <span class="benefits-stat-value"><?= (int)$ch['total'] ?></span>
                    <span class="benefits-stat-label"><?= (int)$ch['total'] === 1 ? 'slot' : ((int)$ch['total'] <= 4 ? 'sloty' : 'slotů') ?></span>
                </div>

                <div class="benefits-breakdown">
                    <span>Role: <strong>+<?= (int)$ch['roles_total'] ?></strong></span>
                    <span>Bonusy: <strong>+<?= (int)$ch['bonuses_total'] ?></strong></span>
                    <span>Základní: <strong>1</strong></span>
                </div>

                <?php if (!empty($ch['role_hits'])): ?>
                    <div class="qp-role-hits" style="margin-top:1rem;">
                        <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:.5rem;">Přispívající role:</p>
                        <div class="qp-role-pills">
                            <?php foreach ($ch['role_hits'] as $hit): ?>
                                <span class="qp-role-pill">
                                    <?= htmlspecialchars($hit['role_name']) ?>
                                    <strong>+<?= (int)$hit['char_value'] ?></strong>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Visual slot display -->
                <div class="char-slots-visual" style="margin-top:1.25rem;">
                    <?php for ($i = 1; $i <= max(15, (int)$ch['total']); $i++): ?>
                        <div class="char-slot <?= $i <= (int)$ch['total'] ? 'char-slot-active' : 'char-slot-locked' ?>">
                            <?php if ($i <= (int)$ch['total']): ?>
                                <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/></svg>
                            <?php else: ?>
                                <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- ── Ped Menu ──────────────────────────────────────────────── -->
            <div class="card benefits-card">
                <h2 class="card-title">
                    <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18" style="vertical-align:-3px;margin-right:.4rem;"><path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/></svg>
                    Ped Menu
                </h2>

                <div class="benefits-stat-row">
                    <span class="benefits-stat-value" style="color: <?= $ped ? 'var(--accent)' : 'var(--text-muted)' ?>">
                        <?= $ped ? '✓' : '✗' ?>
                    </span>
                    <span class="benefits-stat-label"><?= $ped ? 'Aktivní' : 'Neaktivní' ?></span>
                </div>

                <p style="color:var(--text-muted);font-size:.85rem;margin-top:.75rem;">
                    Přístup k ped menu získáš uplatněním kódu.
                </p>
            </div>

        </div>
    </div>
</section>
