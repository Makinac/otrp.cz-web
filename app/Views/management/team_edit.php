<section class="section">
    <div class="container">
        <h1 class="page-title">Management</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>
        <?php require __DIR__ . '/_panel_nav.php'; ?>
        <a href="/management/team" class="back-link">&larr; Správa týmu</a>
        <h2 class="section-heading"><?= $category ? 'Upravit kategorii' : 'Nová kategorie' ?></h2>

        <?php
            $action  = $category ? "/management/team/{$category['id']}/save" : '/management/team/save';
            $roleIds = $category ? (json_decode($category['role_ids_json'] ?? '[]', true) ?? []) : [];
        ?>

        <form method="POST" action="<?= $action ?>" class="card" style="padding:1.5rem;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="form-group">
                <label class="form-label" for="cat_name">Název kategorie <span class="req">*</span></label>
                <input type="text" id="cat_name" name="name" class="form-control"
                       value="<?= htmlspecialchars($category['name'] ?? '') ?>" required
                       placeholder="např. Vedení, Vývojáři, Testeři…">
            </div>

            <div class="form-group">
                <label class="form-label" for="cat_color">Barva kategorie</label>
                <p class="form-hint">Barva se zobrazí na veřejné stránce /tym jako akcentní barva kategorie.</p>
                <div style="display:flex;align-items:center;gap:.75rem;">
                    <input type="color" id="cat_color" name="color"
                           value="<?= htmlspecialchars($category['color'] ?? '#cc0000') ?>"
                           style="width:48px;height:36px;border:1px solid rgba(255,255,255,.15);background:transparent;cursor:pointer;padding:0;">
                    <input type="text" id="cat_color_hex" class="form-control"
                           value="<?= htmlspecialchars($category['color'] ?? '#cc0000') ?>"
                           style="width:120px;font-family:monospace;font-size:.85rem;"
                           placeholder="#cc0000" maxlength="7" pattern="#[0-9a-fA-F]{6}">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Discord role</label>
                <p class="form-hint">Vyberte role, jejichž členové se zobrazí v této kategorii na stránce /tym.</p>
                <div class="role-checkbox-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:.5rem;">
                    <?php foreach ($allRoles as $role): ?>
                        <label class="role-check-item" style="display:flex;align-items:center;gap:.5rem;padding:.4rem .6rem;border:1px solid rgba(255,255,255,.1);border-radius:6px;cursor:pointer;">
                            <input type="checkbox" name="role_ids[]" value="<?= htmlspecialchars((string)$role['id']) ?>"
                                   <?= in_array((string)$role['id'], $roleIds, true) ? 'checked' : '' ?>>
                            <?php if (!empty($role['color'])): ?>
                                <span style="width:12px;height:12px;border-radius:50%;background:<?= htmlspecialchars($role['color']) ?>;flex-shrink:0;"></span>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($role['name']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Uložit</button>
                <a href="/management/team" class="btn btn-ghost">Zrušit</a>
            </div>
        </form>
    </div>
</section>
<script>
(function() {
    var picker = document.getElementById('cat_color');
    var hex = document.getElementById('cat_color_hex');
    if (!picker || !hex) return;
    picker.addEventListener('input', function() {
        hex.value = this.value;
    });
    hex.addEventListener('input', function() {
        var v = this.value.trim();
        if (/^#[0-9a-fA-F]{6}$/.test(v)) {
            picker.value = v;
        }
    });
})();
</script>
