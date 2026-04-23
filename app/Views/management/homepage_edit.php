<section class="section">
    <div class="container">
        <h1 class="page-title">Management</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>
        <?php require __DIR__ . '/_panel_nav.php'; ?>
        <h2 class="section-heading">Domovská stránka</h2>
        <p class="form-hint" style="margin-bottom:1.5rem;">Sestavte domovskou stránku z&nbsp;bloků. Přidávejte, odebírejte a přesouvejte elementy dle potřeby.</p>

        <form method="POST" action="/management/homepage/save" id="hpBlockForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="blocks_json" id="blocksJson" value="">

            <div id="blockList" class="block-list"></div>

            <div class="block-add-bar">
                <span class="block-add-label">Přidat blok:</span>
                <?php foreach ($blockTypes as $type => $label): ?>
                    <button type="button" class="btn btn-ghost btn-sm block-add-btn" data-type="<?= $type ?>">+ <?= htmlspecialchars($label) ?></button>
                <?php endforeach; ?>
            </div>

            <div class="form-actions" style="margin-top:1.5rem;">
                <button type="submit" class="btn btn-primary">Uložit změny</button>
                <a href="/management" class="btn btn-ghost">Zrušit</a>
            </div>
        </form>
    </div>
</section>

<script>
(function () {
    'use strict';

    var blockTypes = <?= json_encode($blockTypes, JSON_UNESCAPED_UNICODE) ?>;
    var blocks = <?= json_encode($blocks, JSON_UNESCAPED_UNICODE) ?>;
    var listEl = document.getElementById('blockList');
    var jsonEl = document.getElementById('blocksJson');

    // ── Block templates ─────────────────────────────────────────────────
    function escHtml(str) {
        var d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    function renderBlockFields(type, data) {
        data = data || {};
        switch (type) {
            case 'hero':
                return '' +
                    '<div class="form-group"><label class="form-label">Nadpis</label>' +
                    '<input type="text" class="form-control" data-field="title" value="' + escHtml(data.title || '') + '" placeholder="Vítejte v divočině Západu"></div>' +
                    '<div class="form-group"><label class="form-label">Popis</label>' +
                    '<textarea class="form-control" data-field="desc" rows="3" placeholder="Popis serveru…">' + escHtml(data.desc || '') + '</textarea></div>';

            case 'heading':
                return '' +
                    '<div class="block-row">' +
                    '<div class="form-group" style="flex:1"><label class="form-label">Text nadpisu</label>' +
                    '<input type="text" class="form-control" data-field="text" value="' + escHtml(data.text || '') + '"></div>' +
                    '<div class="form-group" style="width:100px"><label class="form-label">Úroveň</label>' +
                    '<select class="form-control" data-field="level">' +
                    '<option value="2"' + (data.level == 2 ? ' selected' : '') + '>H2</option>' +
                    '<option value="3"' + (data.level == 3 ? ' selected' : '') + '>H3</option>' +
                    '<option value="1"' + (data.level == 1 ? ' selected' : '') + '>H1</option>' +
                    '</select></div>' +
                    '</div>' +
                    '<div class="form-group"><label class="form-label"><input type="checkbox" data-field="ornament"' + (data.ornament ? ' checked' : '') + '> Zobrazit ornament pod nadpisem</label></div>';

            case 'text':
                return '' +
                    '<div class="form-group"><label class="form-label">Text (HTML povoleno)</label>' +
                    '<textarea class="form-control" data-field="content" rows="4">' + escHtml(data.content || '') + '</textarea></div>';

            case 'buttons':
                var items = data.items || [{ text: '', url: '', style: 'primary' }];
                var html = '<div class="block-buttons-list" data-field-array="items">';
                for (var i = 0; i < items.length; i++) {
                    html += renderButtonItem(items[i], i);
                }
                html += '</div>';
                html += '<button type="button" class="btn btn-ghost btn-sm block-add-subitem" data-target="items">+ Přidat tlačítko</button>';
                return html;

            case 'cards':
                var citems = data.items || [{ title: '', text: '' }];
                var chtml = '<div class="block-cards-list" data-field-array="items">';
                for (var j = 0; j < citems.length; j++) {
                    chtml += renderCardItem(citems[j], j);
                }
                chtml += '</div>';
                chtml += '<button type="button" class="btn btn-ghost btn-sm block-add-subitem" data-target="items">+ Přidat kartu</button>';
                return chtml;

            case 'divider':
                return '' +
                    '<div class="form-group"><label class="form-label">Znaky oddělovače</label>' +
                    '<input type="text" class="form-control" data-field="chars" value="' + escHtml(data.chars || '✦✦✦') + '" placeholder="✦✦✦"></div>';

            case 'spacer':
                return '' +
                    '<div class="form-group"><label class="form-label">Velikost</label>' +
                    '<select class="form-control" data-field="size">' +
                    '<option value="small"' + (data.size === 'small' ? ' selected' : '') + '>Malá (1rem)</option>' +
                    '<option value="medium"' + ((data.size === 'medium' || !data.size) ? ' selected' : '') + '>Střední (2rem)</option>' +
                    '<option value="large"' + (data.size === 'large' ? ' selected' : '') + '>Velká (4rem)</option>' +
                    '</select></div>';

            case 'html':
                return '' +
                    '<div class="form-group"><label class="form-label">HTML kód</label>' +
                    '<textarea class="form-control block-html-code" data-field="code" rows="6" spellcheck="false">' + escHtml(data.code || '') + '</textarea></div>';

            default:
                return '<p>Neznámý typ bloku.</p>';
        }
    }

    function renderButtonItem(item, idx) {
        return '<div class="block-subitem" data-index="' + idx + '">' +
            '<div class="block-row">' +
            '<div class="form-group" style="flex:1"><label class="form-label">Text</label>' +
            '<input type="text" class="form-control" data-subfield="text" value="' + escHtml(item.text || '') + '"></div>' +
            '<div class="form-group" style="flex:1"><label class="form-label">Odkaz</label>' +
            '<input type="text" class="form-control" data-subfield="url" value="' + escHtml(item.url || '') + '"></div>' +
            '<div class="form-group" style="width:130px"><label class="form-label">Styl</label>' +
            '<select class="form-control" data-subfield="style">' +
            '<option value="primary"' + (item.style === 'primary' ? ' selected' : '') + '>Primární</option>' +
            '<option value="secondary"' + (item.style === 'secondary' ? ' selected' : '') + '>Sekundární</option>' +
            '<option value="ghost"' + (item.style === 'ghost' ? ' selected' : '') + '>Ghost</option>' +
            '</select></div>' +
            '<button type="button" class="btn btn-ghost btn-sm block-remove-subitem" title="Odebrat">✕</button>' +
            '</div></div>';
    }

    function renderCardItem(item, idx) {
        return '<div class="block-subitem" data-index="' + idx + '">' +
            '<div class="block-row">' +
            '<div class="form-group" style="flex:1"><label class="form-label">Titulek</label>' +
            '<input type="text" class="form-control" data-subfield="title" value="' + escHtml(item.title || '') + '"></div>' +
            '<button type="button" class="btn btn-ghost btn-sm block-remove-subitem" title="Odebrat">✕</button>' +
            '</div>' +
            '<div class="form-group"><label class="form-label">Text</label>' +
            '<textarea class="form-control" data-subfield="text" rows="2">' + escHtml(item.text || '') + '</textarea></div>' +
            '</div>';
    }

    // ── Render full block ───────────────────────────────────────────────
    function renderBlock(block, index) {
        var el = document.createElement('div');
        el.className = 'block-item';
        el.setAttribute('data-index', index);
        el.setAttribute('data-type', block.type);

        el.innerHTML = '' +
            '<div class="block-header">' +
            '<span class="block-drag-handle" title="Přetáhnout">☰</span>' +
            '<span class="block-type-label">' + escHtml(blockTypes[block.type] || block.type) + '</span>' +
            '<span class="block-actions">' +
            '<button type="button" class="btn-icon block-collapse-btn" title="Sbalit/Rozbalit">▼</button>' +
            '<button type="button" class="btn-icon block-move-up" title="Nahoru">▲</button>' +
            '<button type="button" class="btn-icon block-move-down" title="Dolů">▼</button>' +
            '<button type="button" class="btn-icon block-delete" title="Smazat">✕</button>' +
            '</span>' +
            '</div>' +
            '<div class="block-body">' + renderBlockFields(block.type, block.data) + '</div>';

        return el;
    }

    // ── Collect data from DOM ───────────────────────────────────────────
    function collectBlockData(blockEl) {
        var type = blockEl.getAttribute('data-type');
        var data = {};
        var body = blockEl.querySelector('.block-body');

        // Simple fields
        body.querySelectorAll('[data-field]').forEach(function (el) {
            var key = el.getAttribute('data-field');
            if (el.type === 'checkbox') {
                data[key] = el.checked;
            } else {
                data[key] = el.value;
            }
        });

        // Array fields (buttons, cards)
        body.querySelectorAll('[data-field-array]').forEach(function (arrEl) {
            var arrKey = arrEl.getAttribute('data-field-array');
            var items = [];
            arrEl.querySelectorAll('.block-subitem').forEach(function (si) {
                var item = {};
                si.querySelectorAll('[data-subfield]').forEach(function (f) {
                    item[f.getAttribute('data-subfield')] = f.value;
                });
                items.push(item);
            });
            data[arrKey] = items;
        });

        return { type: type, data: data };
    }

    function collectAllBlocks() {
        var result = [];
        listEl.querySelectorAll('.block-item').forEach(function (el) {
            result.push(collectBlockData(el));
        });
        return result;
    }

    // ── Render all blocks ───────────────────────────────────────────────
    function renderAll() {
        listEl.innerHTML = '';
        blocks.forEach(function (block, i) {
            listEl.appendChild(renderBlock(block, i));
        });
    }

    // ── Sync blocks from DOM before any structural change ───────────────
    function syncFromDom() {
        blocks = collectAllBlocks();
    }

    // ── Event handlers ──────────────────────────────────────────────────
    listEl.addEventListener('click', function (e) {
        var btn = e.target.closest('button');
        if (!btn) return;
        var blockEl = btn.closest('.block-item');

        if (btn.classList.contains('block-delete')) {
            syncFromDom();
            var idx = Array.from(listEl.children).indexOf(blockEl);
            blocks.splice(idx, 1);
            renderAll();
        } else if (btn.classList.contains('block-move-up')) {
            syncFromDom();
            var idx2 = Array.from(listEl.children).indexOf(blockEl);
            if (idx2 > 0) {
                var tmp = blocks[idx2];
                blocks[idx2] = blocks[idx2 - 1];
                blocks[idx2 - 1] = tmp;
                renderAll();
            }
        } else if (btn.classList.contains('block-move-down')) {
            syncFromDom();
            var idx3 = Array.from(listEl.children).indexOf(blockEl);
            if (idx3 < blocks.length - 1) {
                var tmp2 = blocks[idx3];
                blocks[idx3] = blocks[idx3 + 1];
                blocks[idx3 + 1] = tmp2;
                renderAll();
            }
        } else if (btn.classList.contains('block-collapse-btn')) {
            blockEl.classList.toggle('block-collapsed');
            btn.textContent = blockEl.classList.contains('block-collapsed') ? '▶' : '▼';
        } else if (btn.classList.contains('block-add-subitem')) {
            syncFromDom();
            var bi = Array.from(listEl.children).indexOf(blockEl);
            var btype = blocks[bi].type;
            var target = btn.getAttribute('data-target');
            if (!blocks[bi].data[target]) blocks[bi].data[target] = [];
            if (btype === 'buttons') {
                blocks[bi].data[target].push({ text: '', url: '', style: 'primary' });
            } else if (btype === 'cards') {
                blocks[bi].data[target].push({ title: '', text: '' });
            }
            renderAll();
        } else if (btn.classList.contains('block-remove-subitem')) {
            var subitem = btn.closest('.block-subitem');
            var arrList = subitem.parentNode;
            subitem.remove();
            arrList.querySelectorAll('.block-subitem').forEach(function (si, i) {
                si.setAttribute('data-index', i);
            });
        }
    });

    // Add block buttons
    document.querySelectorAll('.block-add-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            syncFromDom();
            var type = btn.getAttribute('data-type');
            var newBlock = { type: type, data: {} };

            if (type === 'divider') newBlock.data.chars = '✦✦✦';
            if (type === 'spacer') newBlock.data.size = 'medium';
            if (type === 'buttons') newBlock.data.items = [{ text: '', url: '', style: 'primary' }];
            if (type === 'cards') newBlock.data.items = [{ title: '', text: '' }];
            if (type === 'heading') { newBlock.data.level = '2'; newBlock.data.ornament = false; }

            blocks.push(newBlock);
            renderAll();
            listEl.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });

    // ── Drag and drop reorder ───────────────────────────────────────────
    var dragSrc = null;

    listEl.addEventListener('dragstart', function (e) {
        var handle = e.target.closest('.block-drag-handle');
        if (!handle) { e.preventDefault(); return; }
        dragSrc = handle.closest('.block-item');
        dragSrc.classList.add('block-dragging');
        e.dataTransfer.effectAllowed = 'move';
    });

    listEl.addEventListener('dragover', function (e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        var target = e.target.closest('.block-item');
        if (!target || target === dragSrc) return;

        var rect = target.getBoundingClientRect();
        var mid = rect.top + rect.height / 2;
        if (e.clientY < mid) {
            listEl.insertBefore(dragSrc, target);
        } else {
            listEl.insertBefore(dragSrc, target.nextSibling);
        }
    });

    listEl.addEventListener('dragend', function () {
        if (dragSrc) {
            dragSrc.classList.remove('block-dragging');
            dragSrc = null;
            syncFromDom();
        }
    });

    listEl.addEventListener('mousedown', function (e) {
        if (e.target.closest('.block-drag-handle')) {
            e.target.closest('.block-item').setAttribute('draggable', 'true');
        }
    });
    listEl.addEventListener('mouseup', function () {
        listEl.querySelectorAll('.block-item[draggable]').forEach(function (el) {
            el.removeAttribute('draggable');
        });
    });

    // ── Form submit ─────────────────────────────────────────────────────
    document.getElementById('hpBlockForm').addEventListener('submit', function () {
        syncFromDom();
        jsonEl.value = JSON.stringify(blocks);
    });

    // ── Initial render ──────────────────────────────────────────────────
    renderAll();
})();
</script>
