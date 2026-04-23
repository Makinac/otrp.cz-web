<section class="section">
    <div class="container">
        <h1 class="page-title">Management</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>
        <?php require __DIR__ . '/_panel_nav.php'; ?>
        <div class="cards-row admin-nav-cards">
            <?php if (!empty($managementPerms['management.homepage'])): ?>
            <a href="/management/homepage" class="card admin-nav-item">
                <span class="admin-nav-icon">&#127968;</span>
                <strong>Domovská stránka</strong>
                <span>Správa sekcí hlavní stránky</span>
            </a>
            <?php endif; ?>
            <?php if (!empty($managementPerms['management.form'])): ?>
            <a href="/management/form" class="card admin-nav-item">
                <span class="admin-nav-icon">&#9998;</span>
                <strong>Formuláře</strong>
                <span>Správa žádostí o allowlist</span>
            </a>
            <?php endif; ?>
            <?php if (!empty($managementPerms['management.content'])): ?>
            <a href="/management/content" class="card admin-nav-item">
                <span class="admin-nav-icon">&#128240;</span>
                <strong>Novinky</strong>
                <span>Příspěvky a aktuality</span>
            </a>
            <?php endif; ?>
            <?php if (!empty($managementPerms['management.rules'])): ?>
            <a href="/management/rules" class="card admin-nav-item">
                <span class="admin-nav-icon">&#128210;</span>
                <strong>Pravidla</strong>
                <span>Správa pravidel serveru</span>
            </a>
            <?php endif; ?>
            <?php if (!empty($managementPerms['management.blacklist'])): ?>
            <a href="/management/denylist" class="card admin-nav-item">
                <span class="admin-nav-icon">&#128683;</span>
                <strong>Denylist</strong>
                <span>Správa zakázaných uživatelů</span>
            </a>
            <?php endif; ?>
            <?php if (!empty($managementPerms['management.appeals'])): ?>
            <a href="/management/appeals" class="card admin-nav-item">
                <span class="admin-nav-icon">&#128172;</span>
                <strong>Odvolání</strong>
                <span>Fronta nevyřízených odvolání</span>
            </a>
            <?php endif; ?>
            <?php if (!empty($managementPerms['management.team'])): ?>
            <a href="/management/team" class="card admin-nav-item">
                <span class="admin-nav-icon">&#128101;</span>
                <strong>Tým</strong>
                <span>Kategorie a členové na /tym</span>
            </a>
            <?php endif; ?>
            <?php if (!empty($managementPerms['management.partners'])): ?>
            <a href="/management/partners" class="card admin-nav-item">
                <span class="admin-nav-icon">&#9989;</span>
                <strong>Partneři</strong>
                <span>Správa partnerských serverů</span>
            </a>
            <?php endif; ?>
            <?php if (!empty($managementPerms['management.cheatsheet'])): ?>
            <a href="/management/cheatsheet" class="card admin-nav-item">
                <span class="admin-nav-icon">&#128214;</span>
                <strong>Tahák</strong>
                <span>Otázky k pohovoru</span>
            </a>
            <?php endif; ?>
            <?php if (!empty($managementPerms['management.allowlist_stats'])): ?>
            <a href="/management/allowlist-stats" class="card admin-nav-item">
                <span class="admin-nav-icon">&#128202;</span>
                <strong>Allowlist statistiky</strong>
                <span>Přehled aktivity testerů</span>
            </a>
            <?php endif; ?>
            <?php if (!empty($managementPerms['management.settings'])): ?>
            <a href="/management/settings" class="card admin-nav-item">
                <span class="admin-nav-icon">&#9881;</span>
                <strong>Nastavení práv</strong>
                <span>Role a konkrétní uživatelé</span>
            </a>
            <?php endif; ?>
        </div>
    </div>
</section>
