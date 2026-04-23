<?php
// ── Prepare data ──
$activeRoles = [];
$inactiveRoles = [];
$totalQp = 0;
if (!empty($discordRoles)) {
    foreach ($discordRoles as $role) {
        $rid = (string)$role['id'];
        $val = (int)($roleMap[$rid] ?? 0);
        $role['_qp'] = $val;
        $role['_color'] = isset($role['color']) && $role['color'] > 0 ? sprintf('#%06X', $role['color']) : null;
        if ($val > 0) {
            $activeRoles[] = $role;
            $totalQp += $val;
        } else {
            $inactiveRoles[] = $role;
        }
    }
    usort($activeRoles, fn($a, $b) => $b['_qp'] <=> $a['_qp']);
}
?>
<section class="section">
    <div class="container">
        <h1 class="page-title">Management</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>

        <?php require __DIR__ . '/_panel_nav.php'; ?>

        <?php if (!empty($flash['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($flash['success']) ?></div>
        <?php endif; ?>
        <?php if (!empty($flash['error'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($flash['error']) ?></div>
        <?php endif; ?>

        <?php if (empty($discordRoles)): ?>
            <div class="card">
                <p class="empty-notice">Nepodařilo se načíst Discord role. Zkontroluj konfiguraci bota.</p>
            </div>
        <?php else: ?>
        <form method="POST" action="/management/qp/save">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <!-- ── Summary header ──────────────────────────────────────────── -->
            <div class="rc-summary">
                <div class="rc-stat-card">
                    <span class="rc-stat-icon">⭐</span>
                    <div class="rc-stat-body">
                        <span class="rc-stat-value"><?= count($activeRoles) ?></span>
                        <span class="rc-stat-label">Aktivních rolí</span>
                    </div>
                </div>
                <div class="rc-stat-card">
                    <span class="rc-stat-icon">🎯</span>
                    <div class="rc-stat-body">
                        <span class="rc-stat-value"><?= number_format($totalQp, 0, ',', ' ') ?></span>
                        <span class="rc-stat-label">Celkem QP v rolích</span>
                    </div>
                </div>
                <div class="rc-stat-card">
                    <span class="rc-stat-icon">🏷️</span>
                    <div class="rc-stat-body">
                        <span class="rc-stat-value"><?= count($discordRoles) ?></span>
                        <span class="rc-stat-label">Všech rolí na serveru</span>
                    </div>
                </div>
            </div>

            <!-- ── Aktivní role (s QP > 0) ─────────────────────────────────── -->
            <?php if (!empty($activeRoles)): ?>
            <div class="card" style="border-left: 3px solid var(--red);">
                <h2 class="card-title">⭐ Aktivní QP role</h2>
                <p style="color:var(--grey);margin-bottom:1.25rem;">Role, které aktuálně přidávají QP. Seřazeno od nejvyšší hodnoty.</p>

                <div class="rc-active-grid">
                    <?php foreach ($activeRoles as $role): ?>
                    <?php $rid = (string)$role['id']; $rname = (string)($role['name'] ?? $rid); ?>
                    <div class="rc-active-card" <?php if ($role['_color']): ?>style="--rc-accent:<?= htmlspecialchars($role['_color']) ?>;"<?php endif; ?>>
                        <div class="rc-active-header">
                            <?php if ($role['_color']): ?>
                                <span class="rc-dot" style="background:<?= htmlspecialchars($role['_color']) ?>;"></span>
                            <?php endif; ?>
                            <span class="rc-active-name"><?= htmlspecialchars($rname) ?></span>
                        </div>
                        <div class="rc-active-value">
                            <input type="number" name="qp[<?= htmlspecialchars($rid) ?>]" value="<?= $role['_qp'] ?>"
                                   min="0" max="100000" class="form-control rc-num-input">
                            <span class="rc-unit">QP</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Všechny role ────────────────────────────────────────────── -->
            <div class="card">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;margin-bottom:1.25rem;">
                    <div>
                        <h2 class="card-title" style="margin-bottom:0.25rem;">🏷️ Všechny role</h2>
                        <p style="color:var(--grey);font-size:0.85rem;margin:0;">Nastav QP hodnotu libovolné roli. Role s hodnotou 0 nejsou aktivní.</p>
                    </div>
                    <div style="position:relative;">
                        <input type="text" id="rcFilterQp" class="form-control" placeholder="🔍 Hledat roli…" style="width:220px;font-size:0.85rem;">
                    </div>
                </div>

                <div class="rc-table-wrap">
                    <table class="data-table rc-table" id="rcTableQp">
                        <thead>
                            <tr>
                                <th style="width:40px;"></th>
                                <th>Role</th>
                                <th style="width:140px;text-align:right;">QP hodnota</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($discordRoles as $role): ?>
                            <?php
                                $rid     = (string)$role['id'];
                                $rname   = (string)($role['name'] ?? $rid);
                                $current = (int)($roleMap[$rid] ?? 0);
                                $color   = isset($role['color']) && $role['color'] > 0 ? sprintf('#%06X', $role['color']) : null;
                                // Skip roles already shown in active grid (they have hidden inputs above)
                                if ($current > 0) continue;
                            ?>
                            <tr class="rc-row" data-name="<?= htmlspecialchars(mb_strtolower($rname)) ?>">
                                <td>
                                    <?php if ($color): ?>
                                        <span class="rc-dot" style="background:<?= htmlspecialchars($color) ?>;"></span>
                                    <?php else: ?>
                                        <span class="rc-dot rc-dot-empty"></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="rc-role-name"><?= htmlspecialchars($rname) ?></span>
                                    <span class="rc-role-id"><?= htmlspecialchars($rid) ?></span>
                                </td>
                                <td style="text-align:right;">
                                    <input type="number" name="qp[<?= htmlspecialchars($rid) ?>]" value="<?= $current ?>"
                                           min="0" max="100000" class="form-control rc-num-input">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ── Submit ──────────────────────────────────────────────────── -->
            <div style="display:flex;justify-content:flex-end;margin-top:1rem;">
                <button type="submit" class="btn btn-primary" style="padding:.6rem 2.5rem;">Uložit konfiguraci</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var filter = document.getElementById('rcFilterQp');
    if (!filter) return;
    filter.addEventListener('input', function() {
        var q = this.value.toLowerCase();
        document.querySelectorAll('#rcTableQp tbody .rc-row').forEach(function(row) {
            row.style.display = row.dataset.name.indexOf(q) !== -1 ? '' : 'none';
        });
    });
});
</script>
