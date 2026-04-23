<?php
/**
 * Role Config — QP, Char, Ped Menu per Discord role.
 * Design follows the perm-subject-card pattern from settings.php.
 */

// Build role name map for the dropdown
$roleOptions = [];
$rolePositions = [];
if (!empty($discordRoles)) {
    foreach ($discordRoles as $r) {
        $rid   = (string)$r['id'];
        $name  = trim(preg_replace('/[\x{1F000}-\x{1FFFF}\x{2600}-\x{27BF}\x{FE00}-\x{FE0F}\x{200D}\x{20E3}\x{E0020}-\x{E007F}\x{E0100}-\x{E01EF}]/u', '', (string)($r['name'] ?? $rid)));
        $name  = $name ?: (string)($r['name'] ?? $rid);
        $color = isset($r['color']) && $r['color'] > 0 ? sprintf('#%06X', $r['color']) : null;
        if (is_string($r['color'] ?? null) && str_starts_with($r['color'], '#')) {
            $color = $r['color'];
        }
        $roleOptions[$rid] = ['name' => $name, 'color' => $color];
        $rolePositions[$rid] = (int)($r['position'] ?? 0);
    }
}

// Collect all role IDs that have at least one value
$configuredRoleIds = [];
if ($hasQpPerm && !empty($qpRoleMap)) {
    foreach ($qpRoleMap as $rid => $val) { if ($val > 0) $configuredRoleIds[$rid] = true; }
}
if ($hasCharsPerm && !empty($charRoleMap)) {
    foreach ($charRoleMap as $rid => $val) { if ($val > 0) $configuredRoleIds[$rid] = true; }
}
if ($hasPedPerm && !empty($pedRoleMap)) {
    foreach ($pedRoleMap as $rid => $v) { $configuredRoleIds[$rid] = true; }
}

// Sort: configured roles by Discord position (highest first)
$configuredList = [];
foreach ($configuredRoleIds as $rid => $_) {
    $configuredList[] = [
        'id'    => (string)$rid,
        'name'  => $roleOptions[$rid]['name'] ?? $rid,
        'color' => $roleOptions[$rid]['color'] ?? null,
        'pos'   => $rolePositions[$rid] ?? 0,
        'qp'    => $hasQpPerm ? (int)($qpRoleMap[$rid] ?? 0) : 0,
        'ch'    => $hasCharsPerm ? (int)($charRoleMap[$rid] ?? 0) : 0,
        'ped'   => $hasPedPerm ? !empty($pedRoleMap[$rid]) : false,
    ];
}
usort($configuredList, fn($a, $b) => $b['pos'] <=> $a['pos']);

// Unconfigured roles for dropdown (highest position first)
$unconfiguredRoles = [];
foreach ($roleOptions as $rid => $info) {
    if (!isset($configuredRoleIds[$rid])) {
        $unconfiguredRoles[$rid] = ['name' => $info['name'], 'pos' => $rolePositions[$rid] ?? 0];
    }
}
uasort($unconfiguredRoles, fn($a, $b) => $b['pos'] <=> $a['pos']);
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

        <!-- ── Add Role Card ──────────────────────────────────────────── -->
        <div class="card" style="margin-bottom:1.5rem;">
            <h2 class="card-title">Přidat roli</h2>
            <p class="tester-meta" style="margin:0 0 .75rem;line-height:1.5;">
                Vyber Discord roli a nastav jí QP, Char sloty nebo Ped Menu přístup.
                Role, které už mají konfiguraci, se zobrazují níže.
            </p>
            <div style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap;">
                <div class="form-group" style="margin:0;flex:1;min-width:200px;">
                    <label class="form-label" for="rcAddRole">Role</label>
                    <select id="rcAddRole" class="form-control">
                        <?php foreach ($unconfiguredRoles as $rid => $rinfo): ?>
                            <option value="<?= htmlspecialchars($rid) ?>"><?= htmlspecialchars($rinfo['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" id="rcAddBtn" class="btn btn-primary">Přidat</button>
            </div>
        </div>

        <!-- ── Single unified form ──────────────────────────────────── -->
        <form id="rcForm" method="POST" action="/management/role-config/save">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div id="rcHiddenInputs"></div>
        </form>

        <!-- ── Section heading + toolbar ──────────────────────────────── -->
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem;">
            <h2 class="section-heading" style="margin:0;">Konfigurace rolí</h2>
            <button type="submit" form="rcForm" class="btn btn-primary btn-sm">Uložit</button>
        </div>

        <?php if (empty($configuredList)): ?>
            <p class="empty-notice">Zatím žádná role s konfigurací. Přidej roli výše.</p>
        <?php else: ?>

        <!-- ── Role Cards ─────────────────────────────────────────────── -->
        <div id="rcCards">
            <?php foreach ($configuredList as $role): ?>
            <div class="perm-subject-card" data-role-id="<?= htmlspecialchars($role['id']) ?>">
                <div class="perm-subject-header">
                    <div>
                        <?php if ($role['color']): ?>
                            <span class="rc-dot" style="background:<?= htmlspecialchars($role['color']) ?>;margin-right:.4rem;"></span>
                        <?php endif; ?>
                        <span class="perm-subject-label"><?= htmlspecialchars($role['name']) ?></span>
                        <span class="perm-subject-id"><?= htmlspecialchars($role['id']) ?></span>
                    </div>
                    <button type="button" class="btn btn-reject btn-sm rc-remove-btn" data-role-id="<?= htmlspecialchars($role['id']) ?>">Odebrat</button>
                </div>

                <div class="perm-sections-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem;">
                    <?php if ($hasQpPerm): ?>
                    <div class="perm-section">
                        <div class="perm-section-heading">QP (QuePoints)</div>
                        <div class="fields-grid" style="grid-template-columns:1fr;">
                            <div class="form-group" style="margin:0;">
                                <label class="form-label">Hodnota QP</label>
                                <input type="number" class="form-control rc-qp-val" value="<?= $role['qp'] ?>"
                                       min="0" max="100000" data-role="<?= htmlspecialchars($role['id']) ?>"
                                       style="max-width:160px;">
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($hasCharsPerm): ?>
                    <div class="perm-section">
                        <div class="perm-section-heading">Character sloty</div>
                        <div class="fields-grid" style="grid-template-columns:1fr;">
                            <div class="form-group" style="margin:0;">
                                <label class="form-label">Počet slotů</label>
                                <input type="number" class="form-control rc-char-val" value="<?= $role['ch'] ?>"
                                       min="0" max="15" data-role="<?= htmlspecialchars($role['id']) ?>"
                                       style="max-width:160px;">
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($hasPedPerm): ?>
                    <div class="perm-section">
                        <div class="perm-section-heading">Ped Menu</div>
                        <div class="perm-checkboxes">
                            <label class="perm-checkbox-label">
                                <input type="checkbox" class="rc-ped-val" data-role="<?= htmlspecialchars($role['id']) ?>"
                                       <?= $role['ped'] ? 'checked' : '' ?>>
                                <span class="perm-checkbox-text">Přístup k Ped Menu</span>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>

        <!-- ── Bottom save button ──────────────────────────────────── -->
        <?php if (!empty($configuredList)): ?>
        <div style="margin-top:1rem;">
            <button type="submit" form="rcForm" class="btn btn-primary btn-sm">Uložit</button>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</section>

<script>
(function() {
    // ── Sync inputs into the single hidden form before submit ──
    function syncForm() {
        var container = document.getElementById('rcHiddenInputs');
        if (!container) return;
        container.innerHTML = '';

        document.querySelectorAll('.perm-subject-card').forEach(function(card) {
            var rid = card.dataset.roleId;
            if (!rid) return;

            var qpInput = card.querySelector('.rc-qp-val');
            if (qpInput) {
                var h = document.createElement('input');
                h.type = 'hidden'; h.name = 'qp[' + rid + ']'; h.value = qpInput.value;
                container.appendChild(h);
            }

            var charInput = card.querySelector('.rc-char-val');
            if (charInput) {
                var h2 = document.createElement('input');
                h2.type = 'hidden'; h2.name = 'chars[' + rid + ']'; h2.value = charInput.value;
                container.appendChild(h2);
            }

            var pedCheck = card.querySelector('.rc-ped-val');
            if (pedCheck && pedCheck.checked) {
                var h3 = document.createElement('input');
                h3.type = 'hidden'; h3.name = 'ped[' + rid + ']'; h3.value = '1';
                container.appendChild(h3);
            }
        });
    }

    var form = document.getElementById('rcForm');
    if (form) form.addEventListener('submit', function() { syncForm(); });

    // ── Add role button ──
    var addBtn  = document.getElementById('rcAddBtn');
    var addSel  = document.getElementById('rcAddRole');
    var cardsEl = document.getElementById('rcCards');

    if (addBtn && addSel && cardsEl) {
        addBtn.addEventListener('click', function() {
            var rid  = addSel.value;
            var name = addSel.options[addSel.selectedIndex] ? addSel.options[addSel.selectedIndex].text : rid;
            if (!rid) return;

            // Remove from dropdown
            addSel.querySelector('option[value="' + rid + '"]')?.remove();

            var card = document.createElement('div');
            card.className = 'perm-subject-card';
            card.dataset.roleId = rid;

            var sections = '';
            <?php if ($hasQpPerm): ?>
            sections += '<div class="perm-section"><div class="perm-section-heading">QP (QuePoints)</div>'
                + '<div class="fields-grid" style="grid-template-columns:1fr"><div class="form-group" style="margin:0">'
                + '<label class="form-label">Hodnota QP</label>'
                + '<input type="number" class="form-control rc-qp-val" value="0" min="0" max="100000" data-role="' + rid + '" style="max-width:160px">'
                + '</div></div></div>';
            <?php endif; ?>
            <?php if ($hasCharsPerm): ?>
            sections += '<div class="perm-section"><div class="perm-section-heading">Character sloty</div>'
                + '<div class="fields-grid" style="grid-template-columns:1fr"><div class="form-group" style="margin:0">'
                + '<label class="form-label">Počet slotů</label>'
                + '<input type="number" class="form-control rc-char-val" value="0" min="0" max="15" data-role="' + rid + '" style="max-width:160px">'
                + '</div></div></div>';
            <?php endif; ?>
            <?php if ($hasPedPerm): ?>
            sections += '<div class="perm-section"><div class="perm-section-heading">Ped Menu</div>'
                + '<div class="perm-checkboxes"><label class="perm-checkbox-label">'
                + '<input type="checkbox" class="rc-ped-val" data-role="' + rid + '">'
                + '<span class="perm-checkbox-text">Přístup k Ped Menu</span></label></div></div>';
            <?php endif; ?>

            card.innerHTML = '<div class="perm-subject-header"><div>'
                + '<span class="perm-subject-label">' + name + '</span>'
                + '<span class="perm-subject-id">' + rid + '</span>'
                + '</div><button type="button" class="btn btn-reject btn-sm rc-remove-btn" data-role-id="' + rid + '">Odebrat</button></div>'
                + '<div class="perm-sections-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem">' + sections + '</div>';

            cardsEl.appendChild(card);
            bindRemoveBtn(card.querySelector('.rc-remove-btn'));
        });
    }

    // ── Remove role button ──
    function bindRemoveBtn(btn) {
        if (!btn) return;
        btn.addEventListener('click', function() {
            var card = this.closest('.perm-subject-card');
            var rid  = this.dataset.roleId;
            if (!card) return;
            var name = card.querySelector('.perm-subject-label');
            var label = name ? name.textContent : rid;
            card.remove();

            // Add back to dropdown
            if (addSel) {
                var opt = document.createElement('option');
                opt.value = rid;
                opt.textContent = label;
                addSel.appendChild(opt);
            }
        });
    }

    document.querySelectorAll('.rc-remove-btn').forEach(bindRemoveBtn);
})();
</script>
