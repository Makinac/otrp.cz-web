<section class="section">
    <div class="container">
        <h1 class="page-title">Management</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>
        <?php require __DIR__ . '/_panel_nav.php'; ?>
        <a href="/management/partners" class="back-link">&larr; Partneři</a>

        <div class="editor-page-header">
            <h2 class="section-heading"><?= $partner ? 'Upravit partnera' : 'Nový partner' ?></h2>
        </div>

        <?php $action = $partner ? "/management/partners/{$partner['id']}/save" : '/management/partners/save'; ?>

        <form method="POST" action="<?= $action ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="editor-layout">
                <!-- Main column -->
                <div class="editor-main">
                    <div class="editor-panel">
                        <div class="editor-panel-header">
                            <span class="editor-panel-title">Základní informace</span>
                        </div>
                        <div class="editor-panel-body">
                            <div class="form-group">
                                <label class="form-label" for="p_name">Název partnera <span class="req">*</span></label>
                                <input type="text" id="p_name" name="name" class="form-control form-control--lg"
                                       value="<?= htmlspecialchars($partner['name'] ?? '') ?>" required
                                       placeholder="např. FiveM Community CZ">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="p_desc">Popis</label>
                                <textarea id="p_desc" name="description" class="form-control" rows="3"
                                          placeholder="Krátký popis partnera…"><?= htmlspecialchars($partner['description'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" for="p_url">Odkaz</label>
                                <p class="form-hint">Webová stránka, Discord pozvánka nebo jiný odkaz partnera.</p>
                                <input type="url" id="p_url" name="url" class="form-control"
                                       value="<?= htmlspecialchars($partner['url'] ?? '') ?>"
                                       placeholder="https://example.com">
                            </div>
                        </div>
                    </div>

                    <div class="editor-panel">
                        <div class="editor-panel-header">
                            <span class="editor-panel-title">Logo</span>
                        </div>
                        <div class="editor-panel-body">
                            <p class="editor-panel-hint">Pokud necháte prázdné, logo se automaticky stáhne z odkazu partnera (og:image, favicon).</p>
                            <div class="form-group" style="margin-bottom:0;">
                                <input type="url" id="p_logo" name="logo_url" class="form-control"
                                       value="<?= htmlspecialchars($partner['logo_url'] ?? '') ?>"
                                       placeholder="Automaticky z odkazu nebo vlastní URL">
                            </div>
                            <?php if (!empty($partner['logo_url'])): ?>
                                <div class="partner-edit-logo-preview">
                                    <img src="<?= htmlspecialchars($partner['logo_url']) ?>" alt="Náhled">
                                    <span>Aktuální logo</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <aside class="editor-sidebar">
                    <div class="editor-panel">
                        <div class="editor-panel-header">
                            <span class="editor-panel-title">Publikace</span>
                        </div>
                        <div class="editor-panel-body">
                            <div class="form-actions" style="margin-top:0;">
                                <button type="submit" class="btn btn-primary" style="flex:1;">Uložit</button>
                                <a href="/management/partners" class="btn btn-ghost">Zrušit</a>
                            </div>
                        </div>
                    </div>

                    <div class="editor-panel">
                        <div class="editor-panel-header">
                            <span class="editor-panel-title">Nastavení</span>
                        </div>
                        <div class="editor-panel-body">
                            <div class="form-group" style="margin-bottom:1rem;">
                                <label class="form-label" for="p_sort">Pořadí</label>
                                <input type="number" id="p_sort" name="sort_order" class="form-control"
                                       value="<?= (int)($partner['sort_order'] ?? 0) ?>" min="0">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="option-label">
                                    <input type="checkbox" name="active" value="1"
                                           <?= ($partner === null || $partner['active']) ? 'checked' : '' ?>>
                                    Aktivní (zobrazit na veřejné stránce)
                                </label>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </form>
    </div>
</section>
