<section class="section">
    <div class="container">
        <h1 class="page-title">Management</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>

        <?php require __DIR__ . '/_panel_nav.php'; ?>

        <!-- Generate new code -->
        <div class="card" style="margin-bottom:2rem;">
            <h2 class="card-title">Vygenerovat nový kód</h2>
            <form method="POST" action="/management/codes/create" class="codes-create-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="codeType">Typ <span class="req">*</span></label>
                        <select id="codeType" name="type" class="form-control" required>
                            <option value="qp">QP (QuePoints)</option>
                            <option value="chars">Sloty pro postavy</option>
                            <option value="ped">Ped Menu (přístup)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="codeAmount">Hodnota <span class="req">*</span></label>
                        <input type="number" id="codeAmount" name="amount" class="form-control" min="1" max="100000" placeholder="např. 500" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="codeMaxUses">Počet použití</label>
                        <input type="number" id="codeMaxUses" name="max_uses" class="form-control" min="1" value="1">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="codeNote">Poznámka</label>
                        <input type="text" id="codeNote" name="note" class="form-control" placeholder="např. Tebex objednávka, odměna…" maxlength="255">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="codeExpires">Expirace výhody <span style="color:var(--text-muted);font-weight:400;">(volitelné)</span></label>
                        <input type="datetime-local" id="codeExpires" name="expires_at" class="form-control">
                    </div>
                </div>

                <div style="margin-top:.5rem;">
                    <button type="submit" class="btn btn-primary">Vygenerovat kód</button>
                </div>
            </form>
        </div>

        <!-- Code list -->
        <div class="card">
            <h2 class="card-title">Všechny kódy</h2>

            <?php if (empty($codes)): ?>
                <p class="empty-notice">Žádné kódy.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kód</th>
                                <th>Typ</th>
                                <th style="width:80px;">Hodnota</th>
                                <th style="width:100px;">Použití</th>
                                <th>Expirace výhody</th>
                                <th>Poznámka</th>
                                <th>Vytvořil</th>
                                <th style="width:80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($codes as $c): ?>
                                <?php
                                    $full    = (int)$c['used_count'] >= (int)$c['max_uses'];
                                    $expired = $c['expires_at'] !== null && strtotime($c['expires_at']) <= time();
                                    $rowCls  = ($full || $expired) ? ' style="opacity:.55;"' : '';
                                ?>
                                <tr<?= $rowCls ?>>
                                    <td>
                                        <code class="codes-code-display"><?= htmlspecialchars($c['code']) ?></code>
                                        <?php if ($full): ?>
                                            <span class="status-badge status-rejected" style="font-size:.7rem;margin-left:.4rem;">Vyčerpán</span>
                                        <?php elseif ($expired): ?>
                                            <span class="status-badge status-rejected" style="font-size:.7rem;margin-left:.4rem;">Expirován</span>
                                        <?php else: ?>
                                            <span class="status-badge status-active" style="font-size:.7rem;margin-left:.4rem;">Aktivní</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($c['type'] === 'qp'): ?>
                                            <span class="status-badge" style="background:rgba(212,175,55,.15);color:#d4af37;font-size:.75rem;">QP</span>
                                        <?php elseif ($c['type'] === 'ped'): ?>
                                            <span class="status-badge" style="background:rgba(168,85,247,.15);color:#a855f7;font-size:.75rem;">Ped</span>
                                        <?php else: ?>
                                            <span class="status-badge" style="background:rgba(99,179,237,.15);color:#63b3ed;font-size:.75rem;">Sloty</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= $c['type'] === 'ped' ? '—' : '+' . number_format((int)$c['amount'], 0, ',', ' ') ?></strong></td>
                                    <td><?= (int)$c['used_count'] ?> / <?= (int)$c['max_uses'] ?></td>
                                    <td><?= $c['expires_at'] ? date('j. n. Y H:i', strtotime($c['expires_at'])) : '<span style="color:var(--text-muted);">trvalé</span>' ?></td>
                                    <td><?= htmlspecialchars($c['note'] ?: '—') ?></td>
                                    <td><?= htmlspecialchars($c['created_by_name'] ?? '—') ?></td>
                                    <td>
                                        <form method="POST" action="/management/codes/<?= (int)$c['id'] ?>/delete" onsubmit="return confirm('Opravdu smazat kód <?= htmlspecialchars($c['code']) ?>?')">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Smazat</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
