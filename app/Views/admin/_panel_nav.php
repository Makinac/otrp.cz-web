<?php
$active     = $adminActive ?? '';
$perms      = $adminPerms  ?? [];
$pendingCnt = ($counts['pending'] ?? 0) + ($counts['interview'] ?? 0);
$ckOpenCnt  = $openCount ?? 0;
?>
<aside class="panel-sidebar">
    <div class="panel-sidebar-header">
        <span class="panel-sidebar-sup">Panel</span>
        <span class="panel-sidebar-title">Admin</span>
    </div>
    <nav class="panel-nav" role="navigation" aria-label="Admin navigace">

        <!-- ── Hráči ─────────────────────────────────────────────────── -->
        <div class="panel-nav-group">
            <div class="panel-nav-group-header">
                <span class="panel-nav-group-hdr-inner">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                    Hráči
                </span>
            </div>
            <?php if (!empty($perms['admin.allowlist'])): ?>
            <a href="/admin" class="panel-nav-item<?= $active === 'allowlist' ? ' panel-nav-active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                Allowlisty
                <?php if ($pendingCnt > 0): ?>
                    <span class="panel-nav-badge"><?= $pendingCnt ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>
            <?php if (!empty($perms['admin.players'])): ?>
            <a href="/admin/players" class="panel-nav-item<?= $active === 'players' ? ' panel-nav-active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                Hráči
            </a>
            <?php endif; ?>
        </div>

        <!-- ── Administrace ───────────────────────────────────────────── -->
        <?php if (!empty($perms['admin.ck']) || !empty($perms['admin.vacation']) || !empty($perms['admin.activity'])): ?>
        <div class="panel-nav-group">
            <div class="panel-nav-group-header">
                <span class="panel-nav-group-hdr-inner">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                    Administrace
                </span>
            </div>
            <?php if (!empty($perms['admin.ck'])): ?>
            <a href="/admin/ck" class="panel-nav-item<?= $active === 'ck' ? ' panel-nav-active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 11.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l2-2a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd"/></svg>
                CK Hlasování
                <?php if ($ckOpenCnt > 0): ?>
                    <span class="panel-nav-badge"><?= $ckOpenCnt ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>
            <?php if (!empty($perms['admin.vacation'])): ?>
            <a href="/admin/vacation" class="panel-nav-item<?= $active === 'vacation' ? ' panel-nav-active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 3a1 1 0 011-1h.01a1 1 0 010 2H7a1 1 0 01-1-1zm3 0a1 1 0 011-1h4a1 1 0 110 2h-4a1 1 0 01-1-1zM4 6a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V8a2 2 0 00-2-2H4zm1 3a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1zm0 3a1 1 0 011-1h4a1 1 0 110 2H6a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                Dovolená
            </a>
            <?php endif; ?>
            <?php if (!empty($perms['admin.activity'])): ?>
            <a href="/admin/activity" class="panel-nav-item<?= $active === 'activity' ? ' panel-nav-active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11 4a1 1 0 10-2 0v4a1 1 0 102 0V7zm-3 1a1 1 0 10-2 0v3a1 1 0 102 0V8zM8 9a1 1 0 00-2 0v2a1 1 0 102 0V9z" clip-rule="evenodd"/></svg>
                Aktivita
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ── Systém ─────────────────────────────────────────────────── -->
        <div class="panel-nav-group">
            <div class="panel-nav-group-header">
                <span class="panel-nav-group-hdr-inner">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
                    Systém
                </span>
            </div>
            <?php if (!empty($perms['admin.security'])): ?>
            <a href="/admin/security" class="panel-nav-item<?= $active === 'security' ? ' panel-nav-active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                Security
            </a>
            <?php endif; ?>
            <a href="/admin/settings" class="panel-nav-item<?= $active === 'settings' ? ' panel-nav-active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
                Nastavení
            </a>
        </div>

    </nav>
</aside>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var sb = document.querySelector('.panel-sidebar');
    if (!sb) return;

    // Inject chevron SVG into each group header
    sb.querySelectorAll('.panel-nav-group-header').forEach(function (hdr) {
        var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 20 20');
        svg.setAttribute('fill', 'currentColor');
        svg.classList.add('panel-nav-group-chevron');
        var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('fill-rule', 'evenodd');
        path.setAttribute('d', 'M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z');
        path.setAttribute('clip-rule', 'evenodd');
        svg.appendChild(path);
        hdr.appendChild(svg);
        hdr.addEventListener('click', function () {
            hdr.closest('.panel-nav-group').classList.toggle('is-open');
        });
    });

    // Auto-open the group containing the active item
    var activeItem = sb.querySelector('.panel-nav-active');
    if (activeItem) {
        var grp = activeItem.closest('.panel-nav-group');
        if (grp) grp.classList.add('is-open');
    } else {
        // Fallback: open first group
        var first = sb.querySelector('.panel-nav-group');
        if (first) first.classList.add('is-open');
    }

    // Layout: move siblings after sidebar into .panel-main
    var container = sb.parentNode;
    var layout = document.createElement('div');
    layout.className = 'panel-layout';
    var main = document.createElement('div');
    main.className = 'panel-main';
    var nodes = [], n = sb.nextSibling;
    while (n) { nodes.push(n); n = n.nextSibling; }
    nodes.forEach(function (el) { main.appendChild(el); });
    layout.appendChild(sb);
    layout.appendChild(main);
    container.appendChild(layout);
});
</script>
