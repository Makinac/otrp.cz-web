<style>
.tox-tinymce { border: 1px solid rgba(255,255,255,0.12) !important; border-radius: 0 !important; }
.tox .tox-edit-area__iframe { background: #111 !important; }
.tox .tox-toolbar__primary { background: #1a1a1a !important; border-bottom: 1px solid rgba(255,255,255,0.08) !important; }
.tox .tox-toolbar__overflow { background: #1a1a1a !important; }
.tox .tox-tbtn { color: #bbb !important; }
.tox .tox-tbtn:hover { background: rgba(255,255,255,0.08) !important; color: #fff !important; }
.tox .tox-tbtn--enabled, .tox .tox-tbtn--enabled:hover { background: rgba(204,0,0,0.2) !important; color: #cc0000 !important; }
.tox .tox-tbtn svg { fill: #bbb !important; }
.tox .tox-tbtn:hover svg { fill: #fff !important; }
.tox .tox-tbtn--enabled svg { fill: #cc0000 !important; }
.tox .tox-split-button:hover { box-shadow: none !important; }
.tox .tox-tbtn--select { color: #bbb !important; }
.tox .tox-menubar { background: #1a1a1a !important; border-bottom: 1px solid rgba(255,255,255,0.08) !important; }
.tox .tox-mbtn { color: #bbb !important; }
.tox .tox-mbtn:hover { background: rgba(255,255,255,0.08) !important; color: #fff !important; }
.tox .tox-menu { background: #1a1a1a !important; border: 1px solid rgba(255,255,255,0.12) !important; }
.tox .tox-collection__item { color: #ccc !important; }
.tox .tox-collection__item--active { background: rgba(255,255,255,0.08) !important; }
.tox .tox-collection__item-label { color: #ccc !important; }
.tox .tox-dialog { background: #1a1a1a !important; border: 1px solid rgba(255,255,255,0.15) !important; }
.tox .tox-dialog__header { background: #151515 !important; color: #eee !important; }
.tox .tox-dialog__body { color: #ccc !important; }
.tox .tox-dialog__footer { background: #151515 !important; }
.tox .tox-label { color: #bbb !important; }
.tox .tox-textfield, .tox .tox-textarea, .tox .tox-selectfield select { background: #111 !important; color: #eee !important; border-color: rgba(255,255,255,0.12) !important; }
.tox .tox-button { background: #cc0000 !important; color: #fff !important; border: none !important; }
.tox .tox-button--secondary { background: #333 !important; color: #ccc !important; }
.tox .tox-statusbar { background: #151515 !important; border-top: 1px solid rgba(255,255,255,0.08) !important; color: #666 !important; }
.tox .tox-statusbar__text-container { color: #666 !important; }
.tox .tox-statusbar a { color: #888 !important; }
.tox .tox-toolbar-overlord { background: #1a1a1a !important; }
.tox .tox-toolbar__group { border-right-color: rgba(255,255,255,0.08) !important; }
.tox .tox-pop__dialog { background: #1a1a1a !important; border: 1px solid rgba(255,255,255,0.12) !important; }
.tox .tox-pop__dialog .tox-toolbar { background: #1a1a1a !important; }
#html-editor {
    display: none;
    width: 100%;
    height: 520px;
    background: #0d0d0d;
    color: #ccc;
    border: 1px solid rgba(255,255,255,0.12);
    font-family: 'Courier New', monospace;
    font-size: 0.82rem;
    line-height: 1.7;
    padding: 1rem;
    resize: vertical;
    box-sizing: border-box;
}
</style>

<section class="section">
    <div class="container">
        <h1 class="page-title">Management</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>
        <?php require __DIR__ . '/_panel_nav.php'; ?>
        <a href="/management/content" class="back-link">&larr; Novinky</a>

        <div class="editor-page-header">
            <h2 class="section-heading"><?= $item ? 'Upravit příspěvek' : 'Nový příspěvek' ?></h2>
        </div>

        <?php $action = $item ? "/management/content/{$item['id']}/save" : '/management/content/save'; ?>
        <form method="POST" action="<?= $action ?>" id="contentForm" class="content-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="editor-layout">
                <!-- Main editor column -->
                <div class="editor-main">
                    <div class="editor-panel">
                        <div class="editor-panel-header">
                            <span class="editor-panel-title">Titulek</span>
                        </div>
                        <div class="editor-panel-body">
                            <input type="text" id="item_title" name="title" class="form-control form-control--lg"
                                   value="<?= htmlspecialchars($item['title'] ?? '') ?>" required
                                   placeholder="Zadejte titulek článku…">
                        </div>
                    </div>

                    <div class="editor-panel">
                        <div class="editor-panel-header">
                            <span class="editor-panel-title">Obsah</span>
                            <div class="editor-mode-btns">
                                <button type="button" id="btnWysiwyg" class="editor-mode-btn editor-mode-btn-active">
                                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                                    WYSIWYG
                                </button>
                                <button type="button" id="btnHtml" class="editor-mode-btn">
                                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px"><path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                    HTML
                                </button>
                            </div>
                        </div>
                        <div class="editor-panel-body editor-panel-body--flush">
                            <div id="tinymce-wrap">
                                <textarea id="tinymce-editor"><?= htmlspecialchars($item['body_html'] ?? '') ?></textarea>
                            </div>
                            <textarea id="html-editor"></textarea>
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
                                <a href="/management/content" class="btn btn-ghost">Zrušit</a>
                            </div>
                            <?php if ($item): ?>
                                <div class="editor-meta-info">
                                    <div class="editor-meta-row">
                                        <span class="editor-meta-label">Vytvořeno</span>
                                        <span class="editor-meta-value"><?= htmlspecialchars(date('j. n. Y H:i', strtotime($item['published_at']))) ?></span>
                                    </div>
                                    <div class="editor-meta-row">
                                        <span class="editor-meta-label">Slug</span>
                                        <span class="editor-meta-value" style="font-family:monospace;font-size:0.75rem;"><?= htmlspecialchars($item['slug']) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="editor-panel">
                        <div class="editor-panel-header">
                            <span class="editor-panel-title">Kategorie</span>
                        </div>
                        <div class="editor-panel-body">
                            <div class="form-group" style="margin-bottom:1rem;">
                                <label class="form-label" for="item_category">Název <span class="req">*</span></label>
                                <input type="text" id="item_category" name="category" class="form-control"
                                       value="<?= htmlspecialchars($item['category'] ?? 'Novinka') ?>" required
                                       list="category-suggestions" placeholder="např. Novinka, Devlog…">
                                <datalist id="category-suggestions">
                                    <?php foreach ($categories ?? [] as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat) ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Barva</label>
                                <div class="color-picker-row">
                                    <input type="color" id="cat_color_picker"
                                           value="<?= htmlspecialchars($item['category_color'] ?? '#cc0000') ?>"
                                           class="color-picker-input">
                                    <input type="text" id="cat_color_hex" name="category_color" class="form-control"
                                           value="<?= htmlspecialchars($item['category_color'] ?? '#cc0000') ?>"
                                           style="flex:1;font-family:monospace;font-size:.85rem;"
                                           placeholder="#cc0000" maxlength="7" pattern="#[0-9a-fA-F]{6}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="editor-panel">
                        <div class="editor-panel-header">
                            <span class="editor-panel-title">Callouts</span>
                        </div>
                        <div class="editor-panel-body">
                            <p class="editor-panel-hint">Kliknutím vložíte blok do editoru</p>
                            <div class="callout-btn-stack">
                                <button type="button" id="btnCalloutExample" class="callout-insert-btn callout-insert-btn--green">
                                    💡 Příklad
                                </button>
                                <button type="button" id="btnCalloutImportant" class="callout-insert-btn callout-insert-btn--red">
                                    ⚠️ Důležité
                                </button>
                                <button type="button" id="btnCalloutNote" class="callout-insert-btn callout-insert-btn--teal">
                                    ℹ️ Poznámka
                                </button>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </form>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function () {
    const htmlArea    = document.getElementById('html-editor');
    const tmceWrap    = document.getElementById('tinymce-wrap');
    const btnW        = document.getElementById('btnWysiwyg');
    const btnH        = document.getElementById('btnHtml');
    const form        = document.getElementById('contentForm');
    let editor        = null;
    let mode          = 'wysiwyg';

    const calloutCSS = `
        .callout { border-left: 3px solid; border-radius: 4px; padding: 12px 14px; margin: 14px 0; font-size: 14px; }
        .callout-title { font-weight: 700; margin-bottom: 6px; font-size: 14px; }
        .callout-body { color: #ccc; line-height: 1.7; }
        .callout-body p:last-child { margin-bottom: 0; }
        .callout-example { border-color: #27ae60; background: rgba(39,174,96,0.12); }
        .callout-example .callout-title { color: #2ecc71; }
        .callout-important { border-color: #c0392b; background: rgba(192,57,43,0.12); }
        .callout-important .callout-title { color: #e74c3c; }
        .callout-note { border-color: #16a085; background: rgba(22,160,133,0.12); }
        .callout-note .callout-title { color: #1abc9c; }
    `;

    function calloutHtml(type, icon, title) {
        return '<div class="callout callout-' + type + '">' +
               '<div class="callout-title">' + icon + ' ' + title + '</div>' +
               '<div class="callout-body"><p>Text…</p></div></div><p>&nbsp;</p>';
    }

    tinymce.init({
        selector: '#tinymce-editor',
        height: 520,
        skin: 'oxide-dark',
        content_css: 'dark',
        content_style: 'body { font-family: Barlow, sans-serif; font-size: 15px; color: #eee; line-height: 1.7; background: #111; } a { color: #cc0000; } table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid rgba(255,255,255,0.15); padding: 8px; } ' + calloutCSS,
        menubar: 'edit insert format table',
        plugins: 'lists advlist link autolink table hr charmap code wordcount searchreplace visualblocks fullscreen',
        toolbar: [
            'undo redo | blocks fontsize | bold italic underline strikethrough | forecolor backcolor',
            'alignleft aligncenter alignright alignjustify | numlist bullist outdent indent | table link hr charmap | searchreplace code fullscreen removeformat'
        ],
        block_formats: 'Odstavec=p; Nadpis 1=h1; Nadpis 2=h2; Nadpis 3=h3; Nadpis 4=h4; Předformátovaný=pre',
        font_size_formats: '10px 12px 14px 15px 16px 18px 20px 24px 28px 36px',
        advlist_bullet_styles: 'disc,circle,square',
        advlist_number_styles: 'default,lower-alpha,upper-alpha,lower-roman,upper-roman',
        link_default_target: '_blank',
        table_default_attributes: { border: '1' },
        table_default_styles: { width: '100%', 'border-collapse': 'collapse' },
        branding: false,
        promotion: false,
        resize: true,
        paste_as_text: false,
        setup: function(ed) {
            editor = ed;
        }
    });

    // Sidebar callout buttons
    document.getElementById('btnCalloutExample').addEventListener('click', function() {
        if (editor) editor.insertContent(calloutHtml('example', '💡', 'Příklad'));
    });
    document.getElementById('btnCalloutImportant').addEventListener('click', function() {
        if (editor) editor.insertContent(calloutHtml('important', '⚠️', 'Důležité'));
    });
    document.getElementById('btnCalloutNote').addEventListener('click', function() {
        if (editor) editor.insertContent(calloutHtml('note', 'ℹ️', 'Poznámka'));
    });

    btnH.addEventListener('click', function () {
        if (mode === 'html') return;
        htmlArea.value = editor.getContent();
        tmceWrap.style.display = 'none';
        htmlArea.style.display = 'block';
        btnH.classList.add('editor-mode-btn-active');
        btnW.classList.remove('editor-mode-btn-active');
        mode = 'html';
    });

    btnW.addEventListener('click', function () {
        if (mode === 'wysiwyg') return;
        editor.setContent(htmlArea.value);
        htmlArea.style.display  = 'none';
        tmceWrap.style.display  = 'block';
        btnW.classList.add('editor-mode-btn-active');
        btnH.classList.remove('editor-mode-btn-active');
        mode = 'wysiwyg';
    });

    form.addEventListener('submit', function (e) {
        var body = (mode === 'html') ? htmlArea.value : editor.getContent();
        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'body_html';
        hidden.value = body;
        form.appendChild(hidden);
    });
}());

// Color picker sync
(function() {
    var picker = document.getElementById('cat_color_picker');
    var hex = document.getElementById('cat_color_hex');
    if (!picker || !hex) return;
    picker.addEventListener('input', function() { hex.value = this.value; });
    hex.addEventListener('input', function() {
        var v = this.value.trim();
        if (/^#[0-9a-fA-F]{6}$/.test(v)) picker.value = v;
    });
})();
</script>
