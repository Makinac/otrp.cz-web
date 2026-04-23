<section class="section">
    <div class="container">
        <h1 class="page-title">Management</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>
        <?php require __DIR__ . '/_panel_nav.php'; ?>

        <div class="content-list-header">
            <h2 class="section-heading">Pravidla — kategorie</h2>
            <a href="/management/rules/new" class="btn btn-primary">&#43; Nová kategorie</a>
        </div>

        <?php if (empty($sections)): ?>
            <p class="empty-notice">Žádné kategorie. Přidejte první kliknutím na tlačítko výše.</p>
        <?php else: ?>
            <table class="data-table rules-admin-table">
                <thead>
                    <tr>
                        <th style="width:90px">Pořadí</th>
                        <th>Název kategorie</th>
                        <th>Naposledy upraven</th>
                        <th>Akce</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sections as $i => $sec): ?>
                        <tr>
                            <td class="sort-cell">
                                <span class="sort-pos"><?= $i + 1 ?></span>
                                <?php if ($i > 0): ?>
                                    <form method="POST" action="/management/rules/<?= (int)$sec['id'] ?>/move">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="direction" value="up">
                                        <button class="sort-btn" title="Posunout nahoru">&#9650;</button>
                                    </form>
                                <?php else: ?>
                                    <span class="sort-btn sort-btn-disabled">&#9650;</span>
                                <?php endif; ?>
                                <?php if ($i < count($sections) - 1): ?>
                                    <form method="POST" action="/management/rules/<?= (int)$sec['id'] ?>/move">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="direction" value="down">
                                        <button class="sort-btn" title="Posunout dolů">&#9660;</button>
                                    </form>
                                <?php else: ?>
                                    <span class="sort-btn sort-btn-disabled">&#9660;</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($sec['title']) ?></td>
                            <td><?= htmlspecialchars(date('j. n. Y H:i', strtotime($sec['updated_at']))) ?></td>
                            <td class="actions-cell">
                                <a href="/management/rules/<?= (int)$sec['id'] ?>/edit" class="btn btn-ghost btn-sm">Upravit</a>
                                <form method="POST" action="/management/rules/<?= (int)$sec['id'] ?>/delete"
                                      style="display:inline"
                                      onsubmit="return confirm('Smazat kategorii &quot;<?= htmlspecialchars(addslashes($sec['title'])) ?>&quot;?')">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <button class="btn btn-reject btn-sm">Smazat</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- RP Blocks Manager -->
        <div style="margin-top:3rem;">
            <div class="content-list-header">
                <h2 class="section-heading">RP bloky</h2>
            </div>
            <p class="editor-panel-hint" style="margin-bottom:1rem;">Vlastní bloky pro roleplay termíny (OOC, /me, PK atd.). Vkládají se do editoru pravidel.</p>

            <form method="POST" action="/management/rules/rp-blocks" id="rpBlocksForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="rp_blocks" id="rpBlocksJson" value="">

                <div id="rpBlocksList" class="rp-blocks-list">
                    <?php foreach ($rpBlocks as $i => $rpb): ?>
                        <div class="rp-block-row" data-index="<?= $i ?>">
                            <div class="rp-block-main">
                                <input type="color" class="rp-block-color color-picker-input" value="<?= htmlspecialchars($rpb['color']) ?>" title="Barva odznáčku / borderu">
                                <input type="text" class="rp-block-name form-control" value="<?= htmlspecialchars($rpb['name']) ?>" placeholder="Název (např. OOC)" maxlength="30">
                                <span class="rp-block-sep">—</span>
                                <input type="text" class="rp-block-desc form-control" value="<?= htmlspecialchars($rpb['description']) ?>" placeholder="Popis (volitelný)" maxlength="200">
                                <button type="button" class="btn btn-reject btn-sm rp-block-remove" title="Odebrat">✕</button>
                            </div>
                            <div class="rp-block-options">
                                <label class="rp-block-opt">
                                    <input type="checkbox" class="rp-block-hasbg" <?= !empty($rpb['hasBg']) ? 'checked' : '' ?>>
                                    <span>Pozadí</span>
                                </label>
                                <label class="rp-block-opt rp-block-opt-bgcolor" style="<?= empty($rpb['hasBg']) ? 'opacity:.35;pointer-events:none;' : '' ?>">
                                    <span>Barva pozadí</span>
                                    <input type="color" class="rp-block-bgcolor color-picker-input" value="<?= htmlspecialchars($rpb['bgColor'] ?? ($rpb['color'] ?? '#7c3aed')) ?>">
                                </label>
                                <label class="rp-block-opt">
                                    <span>Zaoblení badge</span>
                                    <select class="rp-block-badge-radius form-control">
                                        <option value="0" <?= ($rpb['badgeRadius'] ?? '3') === '0' ? 'selected' : '' ?>>Žádné</option>
                                        <option value="3" <?= ($rpb['badgeRadius'] ?? '3') === '3' ? 'selected' : '' ?>>Mírné (3px)</option>
                                        <option value="6" <?= ($rpb['badgeRadius'] ?? '3') === '6' ? 'selected' : '' ?>>Střední (6px)</option>
                                        <option value="12" <?= ($rpb['badgeRadius'] ?? '3') === '12' ? 'selected' : '' ?>>Velké (12px)</option>
                                        <option value="999" <?= ($rpb['badgeRadius'] ?? '3') === '999' ? 'selected' : '' ?>>Pilulka</option>
                                    </select>
                                </label>
                                <label class="rp-block-opt rp-block-opt-bgradius" style="<?= empty($rpb['hasBg']) ? 'opacity:.35;pointer-events:none;' : '' ?>">
                                    <span>Zaoblení pozadí</span>
                                    <select class="rp-block-bg-radius form-control">
                                        <option value="0" <?= ($rpb['bgRadius'] ?? '4') === '0' ? 'selected' : '' ?>>Žádné</option>
                                        <option value="4" <?= ($rpb['bgRadius'] ?? '4') === '4' ? 'selected' : '' ?>>Mírné (4px)</option>
                                        <option value="8" <?= ($rpb['bgRadius'] ?? '4') === '8' ? 'selected' : '' ?>>Střední (8px)</option>
                                        <option value="12" <?= ($rpb['bgRadius'] ?? '4') === '12' ? 'selected' : '' ?>>Velké (12px)</option>
                                        <option value="999" <?= ($rpb['bgRadius'] ?? '4') === '999' ? 'selected' : '' ?>>Pilulka</option>
                                    </select>
                                </label>
                            </div>
                            <div class="rp-block-preview">
                                <span class="rp-block-preview-label">Náhled:</span>
                                <div class="rp-block-preview-box"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="display:flex;gap:.75rem;margin-top:1rem;">
                    <button type="button" id="rpBlockAdd" class="btn btn-ghost btn-sm">&#43; Přidat blok</button>
                    <button type="submit" class="btn btn-primary btn-sm">Uložit RP bloky</button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
(function() {
    var list = document.getElementById('rpBlocksList');
    var form = document.getElementById('rpBlocksForm');
    var jsonInput = document.getElementById('rpBlocksJson');

    function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    function rPx(v) { return v === '999' ? '999px' : (v || '4') + 'px'; }

    function renderPreview(row) {
        var box = row.querySelector('.rp-block-preview-box');
        var color = row.querySelector('.rp-block-color').value;
        var name  = row.querySelector('.rp-block-name').value || 'TAG';
        var desc  = row.querySelector('.rp-block-desc').value || 'Popis…';
        var hasBg = row.querySelector('.rp-block-hasbg').checked;
        var bgColor = row.querySelector('.rp-block-bgcolor').value;
        var badgeR = row.querySelector('.rp-block-badge-radius').value;
        var bgR    = row.querySelector('.rp-block-bg-radius').value;

        var style = 'display:flex;align-items:center;gap:10px;color:#bbb;font-size:13px;';
        if (hasBg) {
            style += 'border-left:3px solid ' + color + ';border-radius:' + rPx(bgR) + ';padding:8px 12px;';
            style += 'background:' + bgColor + '22;';
        } else {
            style += 'padding:4px 0;';
        }

        box.innerHTML = '<div style="' + style + '">' +
            '<span style="display:inline-block;font-size:11px;font-weight:700;padding:2px 10px;border-radius:' + rPx(badgeR) + ';color:#fff;background:' + color + ';letter-spacing:.06em;flex-shrink:0;">' + esc(name) + '</span>' +
            '<span>' + esc(desc) + '</span></div>';
    }

    function toggleBgOptions(row) {
        var chk = row.querySelector('.rp-block-hasbg');
        var on = chk.checked;
        ['rp-block-opt-bgcolor', 'rp-block-opt-bgradius'].forEach(function(cls) {
            var el = row.querySelector('.' + cls);
            if (el) { el.style.opacity = on ? '' : '.35'; el.style.pointerEvents = on ? '' : 'none'; }
        });
    }

    function bindRow(row) {
        row.querySelectorAll('input, select').forEach(function(el) {
            el.addEventListener('input', function() {
                if (el.classList.contains('rp-block-hasbg')) toggleBgOptions(row);
                renderPreview(row);
            });
            el.addEventListener('change', function() {
                if (el.classList.contains('rp-block-hasbg')) toggleBgOptions(row);
                renderPreview(row);
            });
        });
        renderPreview(row);
    }

    function createRow(name, color, desc, hasBg, bgColor, badgeRadius, bgRadius) {
        color = color || '#7c3aed';
        bgColor = bgColor || color;
        badgeRadius = badgeRadius || '3';
        bgRadius = bgRadius || '4';
        var div = document.createElement('div');
        div.className = 'rp-block-row';
        function sel(cls, val, opts) {
            var h = '<select class="' + cls + ' form-control">';
            opts.forEach(function(o) { h += '<option value="' + o[0] + '"' + (val===o[0]?' selected':'') + '>' + o[1] + '</option>'; });
            return h + '</select>';
        }
        var radiusOpts = [['0','Žádné'],['3','Mírné (3px)'],['6','Střední (6px)'],['12','Velké (12px)'],['999','Pilulka']];
        var bgRadiusOpts = [['0','Žádné'],['4','Mírné (4px)'],['8','Střední (8px)'],['12','Velké (12px)'],['999','Pilulka']];
        div.innerHTML =
            '<div class="rp-block-main">' +
                '<input type="color" class="rp-block-color color-picker-input" value="' + color + '" title="Barva odznáčku / borderu">' +
                '<input type="text" class="rp-block-name form-control" value="' + esc(name || '') + '" placeholder="Název (např. OOC)" maxlength="30">' +
                '<span class="rp-block-sep">—</span>' +
                '<input type="text" class="rp-block-desc form-control" value="' + esc(desc || '') + '" placeholder="Popis (volitelný)" maxlength="200">' +
                '<button type="button" class="btn btn-reject btn-sm rp-block-remove" title="Odebrat">✕</button>' +
            '</div>' +
            '<div class="rp-block-options">' +
                '<label class="rp-block-opt"><input type="checkbox" class="rp-block-hasbg"' + (hasBg ? ' checked' : '') + '><span>Pozadí</span></label>' +
                '<label class="rp-block-opt rp-block-opt-bgcolor"' + (!hasBg ? ' style="opacity:.35;pointer-events:none;"' : '') + '><span>Barva pozadí</span><input type="color" class="rp-block-bgcolor color-picker-input" value="' + bgColor + '"></label>' +
                '<label class="rp-block-opt"><span>Zaoblení badge</span>' + sel('rp-block-badge-radius', badgeRadius, radiusOpts) + '</label>' +
                '<label class="rp-block-opt rp-block-opt-bgradius"' + (!hasBg ? ' style="opacity:.35;pointer-events:none;"' : '') + '><span>Zaoblení pozadí</span>' + sel('rp-block-bg-radius', bgRadius, bgRadiusOpts) + '</label>' +
            '</div>' +
            '<div class="rp-block-preview"><span class="rp-block-preview-label">Náhled:</span><div class="rp-block-preview-box"></div></div>';
        bindRow(div);
        return div;
    }

    // Bind existing rows
    list.querySelectorAll('.rp-block-row').forEach(bindRow);

    document.getElementById('rpBlockAdd').addEventListener('click', function() {
        list.appendChild(createRow('', '#7c3aed', '', false, '#7c3aed', '3', '4'));
    });

    list.addEventListener('click', function(e) {
        if (e.target.classList.contains('rp-block-remove')) {
            e.target.closest('.rp-block-row').remove();
        }
    });

    form.addEventListener('submit', function() {
        var rows = list.querySelectorAll('.rp-block-row');
        var blocks = [];
        rows.forEach(function(row) {
            var name = row.querySelector('.rp-block-name').value.trim();
            var color = row.querySelector('.rp-block-color').value;
            var desc = row.querySelector('.rp-block-desc').value.trim();
            var hasBg = row.querySelector('.rp-block-hasbg').checked;
            var bgColor = row.querySelector('.rp-block-bgcolor').value;
            var badgeRadius = row.querySelector('.rp-block-badge-radius').value;
            var bgRadius = row.querySelector('.rp-block-bg-radius').value;
            if (name) blocks.push({ name: name, color: color, description: desc, hasBg: hasBg, bgColor: bgColor, badgeRadius: badgeRadius, bgRadius: bgRadius });
        });
        jsonInput.value = JSON.stringify(blocks);
    });
})();
</script>
