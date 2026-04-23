<?php
$active = $managementActive ?? '';
$perms  = $managementPerms ?? [];
?>
<aside class="panel-sidebar">
    <div class="panel-sidebar-header">
        <span class="panel-sidebar-sup">Panel</span>
        <span class="panel-sidebar-title">Management</span>
    </div>
    <nav class="panel-nav" role="navigation" aria-label="Management navigace">

        <!-- ── Web a obsah ───────────────────────────────────────────── -->
        <?php if (!empty($perms['management.homepage']) || !empty($perms['management.content']) || !empty($perms['management.rules']) || !empty($perms['management.team']) || !empty($perms['management.partners'])): ?>
        <div class="panel-nav-group">
            <div class="panel-nav-group-header">
                <span class="panel-nav-group-hdr-inner">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>
                    Web a obsah
                </span>
            </div>
            <?php if (!empty($perms['management.homepage'])): ?>
            <a href="/management/homepage" class="panel-nav-item<?= $active === 'homepage' ? ' panel-nav-active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                Domovská stránka
            </a>
            <?php endif; ?>
            <?php if (!empty($perms['management.content'])): ?>
            <a href="/management/content" class="panel-nav-item<?= $active === 'content' ? ' panel-nav-active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V8l-5-5H4zm8 1.5L16.5 9H13a1 1 0 01-1-1V4.5z"/></svg>
                Novinky
            </a>
            <?php endif; ?>
            <?php if (!empty($perms['management.rules'])): ?>
            <a href="/management/rules" class="panel-nav-item<?= $active === 'rules' ? ' panel-nav-active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M4 3h12a1 1 0 011 1v10a1 1 0 01-1 1H9l-4 3v-3H4a1 1 0 01-1-1V4a1 1 0 011-1z"/></svg>
                Pravidla
            </a>
            <?php endif; ?>
            <?php if (!empty($perms['management.team'])): ?>
            <a href="/management/team" class="panel-nav-item<?= $active === 'team' ? ' panel-nav-active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                Tým
            </a>
            <?php endif; ?>
            <?php if (!empty($perms['management.partners'])): ?>
            <a href="/management/partners" class="panel-nav-item<?= $active === 'partners' ? ' panel-nav-active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                Partneři
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ── Allowlist ──────────────────────────────────────────────── -->
        <?php if (!empty($perms['management.form']) || !empty($perms['management.allowlist_stats']) || !empty($perms['management.cheatsheet'])): ?>
        <div class="panel-nav-group">
            <div class="panel-nav-group-header">
                <span class="panel-nav-group-hdr-inner">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                    Allowlist
                </span>
            </div>
            <?php if (!empty($perms['management.form'])): ?>
            <a href="/management/form" class="panel-nav-item<?= $active === 'form' ? ' panel-nav-active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M17.414 2.586a2 2 0 010 2.828l-9.5 9.5a1 1 0 01-.39.242l-4 1.333a1 1 0 01-1.265-1.265l1.333-4a1 1 0 01.242-.39l9.5-9.5a2 2 0 012.828 0z"/></svg>
                Formuláře
            </a>
            <?php endif; ?>
            <?php if (!empty($perms['management.allowlist_stats'])): ?>
            <a href="/management/allowlist-stats" class="panel-nav-item<?= $active === 'allowlist_stats' ? ' panel-nav-active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
                Statistiky
            </a>
            <?php endif; ?>
            <?php if (!empty($perms['management.cheatsheet'])): ?>
            <a href="/management/cheatsheet" class="panel-nav-item<?= $active === 'cheatsheet' ? ' panel-nav-active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.968 7.968 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.953 7.953 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V14a1 1 0 11-2 0V4.804z"/></svg>
                Tahák
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ── Moderace ───────────────────────────────────────────────── -->
        <?php if (!empty($perms['management.blacklist']) || !empty($perms['management.appeals'])): ?>
        <div class="panel-nav-group">
            <div class="panel-nav-group-header">
                <span class="panel-nav-group-hdr-inner">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0117.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    Moderace
                </span>
            </div>
            <?php if (!empty($perms['management.blacklist'])): ?>
            <a href="/management/denylist" class="panel-nav-item<?= $active === 'blacklist' ? ' panel-nav-active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-10.293a1 1 0 00-1.414-1.414L10 8.586 7.707 6.293a1 1 0 10-1.414 1.414L8.586 10l-2.293 2.293a1 1 0 101.414 1.414L10 11.414l2.293 2.293a1 1 0 001.414-1.414L11.414 10l2.293-2.293z" clip-rule="evenodd"/></svg>
                Deny List
            </a>
            <?php endif; ?>
            <?php if (!empty($perms['management.appeals'])): ?>
            <a href="/management/appeals" class="panel-nav-item<?= $active === 'appeals' ? ' panel-nav-active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M18 10c0 4.418-3.582 8-8 8a8.001 8.001 0 01-7.75-6h3.293A5.5 5.5 0 1010 4.5c-1.61 0-3.06.69-4.07 1.79L8 8H2V2l1.96 1.96A7.965 7.965 0 0110 2c4.418 0 8 3.582 8 8z"/></svg>
                Odvolání
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ── Systém ─────────────────────────────────────────────────── -->
        <?php if (!empty($perms['management.qp']) || !empty($perms['management.chars']) || !empty($perms['management.codes']) || !empty($perms['management.api_keys']) || !empty($perms['management.discord']) || !empty($perms['management.settings'])): ?>
        <div class="panel-nav-group">
            <div class="panel-nav-group-header">
                <span class="panel-nav-group-hdr-inner">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
                    Systém
                </span>
            </div>
            <?php if (!empty($perms['management.codes'])): ?>
            <a href="/management/codes" class="panel-nav-item<?= $active === 'codes' ? ' panel-nav-active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                Kódy
            </a>
            <?php endif; ?>
            <?php if (!empty($perms['management.api_keys'])): ?>
            <a href="/management/api-keys" class="panel-nav-item<?= $active === 'api_keys' ? ' panel-nav-active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v2H2v-4l4.257-4.257A6 6 0 1118 8zm-6-4a1 1 0 100 2 2 2 0 012 2 1 1 0 102 0 4 4 0 00-4-4z" clip-rule="evenodd"/></svg>
                API
            </a>
            <?php endif; ?>
            <?php if (!empty($perms['management.discord'])): ?>
            <a href="/management/discord" class="panel-nav-item<?= $active === 'discord' ? ' panel-nav-active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M16.942 3.614A16.07 16.07 0 0012.893 2.5a.06.06 0 00-.064.03c-.175.31-.37.714-.505 1.032a14.83 14.83 0 00-4.447 0A10.605 10.605 0 007.37 2.53a.062.062 0 00-.064-.03 16.028 16.028 0 00-4.05 1.114.056.056 0 00-.026.022C.533 6.866-.32 10.02.1 13.13a.066.066 0 00.025.045 16.163 16.163 0 004.868 2.46.063.063 0 00.068-.022c.375-.512.709-1.05.995-1.614a.062.062 0 00-.034-.086 10.636 10.636 0 01-1.52-.724.063.063 0 01-.006-.104c.102-.077.204-.157.302-.237a.06.06 0 01.063-.009c3.188 1.456 6.638 1.456 9.79 0a.06.06 0 01.064.008c.098.08.2.161.303.238a.063.063 0 01-.005.104 9.99 9.99 0 01-1.521.723.062.062 0 00-.033.087c.29.564.624 1.101.994 1.613a.062.062 0 00.068.023 16.116 16.116 0 004.876-2.46.063.063 0 00.026-.044c.5-3.775-.838-7.052-3.546-9.494a.05.05 0 00-.026-.022zM6.684 11.152c-.958 0-1.748-.88-1.748-1.96 0-1.082.774-1.961 1.748-1.961 1.981 0 1.763 2.895 0 3.921zm6.64 0c-.959 0-1.748-.88-1.748-1.96 0-1.082.774-1.961 1.748-1.961 1.98 0 1.762 2.895 0 3.921z"/></svg>
                Discord
            </a>
            <?php endif; ?>
            <?php if (!empty($perms['management.settings'])): ?>
            <a href="/management/settings" class="panel-nav-item<?= $active === 'settings' ? ' panel-nav-active' : '' ?>">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
                Nastavení
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

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

