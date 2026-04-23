<style>
.tox-tinymce { border: 1px solid rgba(255,255,255,0.12) !important; border-radius: 4px !important; }
#html-editor {
    display: none;
    width: 100%;
    height: 520px;
    background: #111;
    color: #eee;
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 3px;
    font-family: monospace;
    font-size: 0.82rem;
    line-height: 1.6;
    padding: 0.75rem;
    resize: vertical;
    box-sizing: border-box;
}
</style>

<section class="section">
    <div class="container">
        <h1 class="page-title">Management</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>
        <?php require __DIR__ . '/_panel_nav.php'; ?>
        <a href="/management/cheatsheet" class="back-link">&larr; Tahák</a>
        <h2 class="section-heading"><?= $section ? 'Upravit otázku' : 'Nová otázka' ?></h2>

        <?php $action = $section ? "/management/cheatsheet/{$section['id']}/save" : '/management/cheatsheet/save'; ?>
        <form method="POST" action="<?= $action ?>" id="cheatsheetForm" class="content-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="sort_order" value="<?= (int)($section['sort_order'] ?? 0) ?>">
            <textarea id="sec_body" name="body_html" style="display:none"><?= htmlspecialchars($section['body_html'] ?? '') ?></textarea>

            <div class="form-group">
                <label class="form-label" for="sec_title">Otázka <span class="req">*</span></label>
                <input type="text" id="sec_title" name="title" class="form-control"
                       value="<?= htmlspecialchars($section['title'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <div class="editor-header">
                    <label class="form-label">Odpověď / nápověda</label>
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
                <div id="tinymce-wrap">
                    <textarea id="tinymce-editor"><?= htmlspecialchars($section['body_html'] ?? '') ?></textarea>
                </div>
                <textarea id="html-editor"></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Uložit</button>
                <a href="/management/cheatsheet" class="btn btn-ghost">Zrušit</a>
            </div>
        </form>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function () {
    const hiddenTa  = document.getElementById('sec_body');
    const htmlArea  = document.getElementById('html-editor');
    const tmceWrap  = document.getElementById('tinymce-wrap');
    const btnW      = document.getElementById('btnWysiwyg');
    const btnH      = document.getElementById('btnHtml');
    const form      = document.getElementById('cheatsheetForm');
    let editor      = null;
    let mode        = 'wysiwyg';

    tinymce.init({
        selector: '#tinymce-editor',
        height: 420,
        skin: 'oxide-dark',
        content_css: 'dark',
        content_style: 'body { font-family: Barlow, sans-serif; font-size: 15px; color: #eee; line-height: 1.7; background: #111; } a { color: #cc0000; }',
        menubar: 'edit insert format',
        plugins: 'lists advlist link autolink hr charmap code wordcount searchreplace visualblocks fullscreen',
        toolbar: [
            'undo redo | blocks fontsize | bold italic underline strikethrough | forecolor backcolor',
            'alignleft aligncenter alignright | numlist bullist outdent indent | link hr charmap | code fullscreen removeformat'
        ],
        block_formats: 'Odstavec=p; Nadpis 2=h2; Nadpis 3=h3; Předformátovaný=pre',
        font_size_formats: '12px 14px 15px 16px 18px 20px 24px',
        link_default_target: '_blank',
        branding: false,
        promotion: false,
        resize: true,
        paste_as_text: false,
        setup: function(ed) {
            editor = ed;
        }
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

    form.addEventListener('submit', function () {
        hiddenTa.value = (mode === 'html') ? htmlArea.value : editor.getContent();
    });
}());
</script>
