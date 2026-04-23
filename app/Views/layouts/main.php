<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Old Times RP') ?> | Old Times RP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Barlow:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css?v=77">
    <link rel="icon" href="/assets/images/d07410ff-41c5-44c7-ad94-9925be2847c7.ico" sizes="any">
    <link rel="apple-touch-icon" href="/assets/images/d07410ff-41c5-44c7-ad94-9925be2847c7.png">
</head>
<body>

<header class="site-header">
    <div class="header-inner">
        <a href="/" class="site-logo">
            <img src="/assets/images/d07410ff-41c5-44c7-ad94-9925be2847c7.webp" alt="Old Times RP" class="logo-img">
        </a>
        <nav class="site-nav" role="navigation" aria-label="Hlavní navigace">
            <a href="/"><svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg> Domů</a>
            <a href="/novinky"><svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M2 5a2 2 0 012-2h8a2 2 0 012 2v10a2 2 0 002 2H4a2 2 0 01-2-2V5zm3 1h6v4H5V6zm6 6H5v2h6v-2z" clip-rule="evenodd"/><path d="M15 7h1a2 2 0 012 2v5.5a1.5 1.5 0 01-3 0V7z"/></svg> Novinky</a>
            <a href="/tym"><svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg> Tým</a>
            <a href="/pravidla"><svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg> Pravidla</a>
            <a href="/partneri"><svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> Partneři</a>
            <a href="https://discord.gg/BmERSfVH9M" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M20.317 4.37a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 00.031.057 19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 00-.041-.106 13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128 10.2 10.2 0 00.372-.292.074.074 0 01.077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 01.078.01c.12.098.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.892.077.077 0 00-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.095 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.095 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg> Discord</a>
            <a href="https://old-times-rp.tebex.io/" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/></svg> Tebex</a>
            <?php if ($isLoggedIn): ?>
                <?php
                    $avatarId  = \App\Core\Session::get('avatar');
                    $discordId = \App\Core\Session::get('discord_id');
                    $username  = \App\Core\Session::get('username', 'Uživatel');
                    $avatarUrl = ($avatarId && $discordId)
                        ? 'https://cdn.discordapp.com/avatars/' . htmlspecialchars($discordId) . '/' . htmlspecialchars($avatarId) . '.webp?size=128'
                        : null;
                ?>
                <div class="user-dropdown" id="userDropdown">
                    <button class="user-dropdown-trigger" id="userDropdownTrigger" type="button" aria-haspopup="true" aria-expanded="false">
                        <span class="ud-avatar-wrap">
                            <?php if ($avatarUrl): ?>
                                <img src="<?= $avatarUrl ?>" alt="" class="user-avatar">
                            <?php else: ?>
                                <span class="user-avatar ud-avatar-fallback">
                                    <?= mb_strtoupper(mb_substr($username, 0, 1)) ?>
                                </span>
                            <?php endif; ?>
                            <span class="ud-online-dot"></span>
                        </span>
                    </button>
                    <div class="user-dropdown-menu" id="userDropdownMenu">
                        <div class="ud-profile">
                            <?php if ($avatarUrl): ?>
                                <img src="<?= $avatarUrl ?>" alt="" class="ud-profile-avatar">
                            <?php else: ?>
                                <span class="ud-profile-avatar ud-avatar-fallback">
                                    <?= mb_strtoupper(mb_substr($username, 0, 1)) ?>
                                </span>
                            <?php endif; ?>
                            <div class="ud-profile-info">
                                <span class="ud-profile-name"><?= htmlspecialchars($username) ?></span>
                            </div>
                        </div>
                        <div class="ud-menu">
                            <a href="/allowlist" class="ud-item">
                                <svg class="ud-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>
                                Allowlist
                            </a>
                            <a href="/appeal" class="ud-item">
                                <svg class="ud-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 13V5a2 2 0 00-2-2H4a2 2 0 00-2 2v8a2 2 0 002 2h3l3 3 3-3h1a2 2 0 002-2zM5 7a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1zm1 3a1 1 0 100 2h3a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                                Odvolání
                            </a>
                            <a href="/vyhody" class="ud-item">
                                <svg class="ud-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5 5a3 3 0 015-2.236A3 3 0 0114.83 6H16a2 2 0 110 4h-5V9a1 1 0 10-2 0v1H4a2 2 0 110-4h1.17C5.06 5.687 5 5.35 5 5zm4 1V5a1 1 0 10-1 1h1zm2 0a1 1 0 10-1-1v1h1z" clip-rule="evenodd"/><path d="M9 11H3v5a2 2 0 002 2h4v-7zm2 7h4a2 2 0 002-2v-5h-6v7z"/></svg>
                                Výhody
                            </a>
                            <?php if ($hasAdminAccess): ?>
                            <a href="/admin" class="ud-item">
                                <svg class="ud-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.69.056-1.36.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                Admin panel
                            </a>
                            <?php endif; ?>
                            <?php if ($hasManagementAccess): ?>
                            <a href="/management" class="ud-item">
                                <svg class="ud-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M5 4a1 1 0 00-2 0v7.268a2 2 0 000 3.464V16a1 1 0 102 0v-1.268a2 2 0 000-3.464V4zM11 4a1 1 0 10-2 0v1.268a2 2 0 000 3.464V16a1 1 0 102 0V8.732a2 2 0 000-3.464V4zM16 3a1 1 0 011 1v7.268a2 2 0 010 3.464V16a1 1 0 11-2 0v-1.268a2 2 0 010-3.464V4a1 1 0 011-1z"/></svg>
                                Management
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="ud-footer">
                            <?php if ($hasIngameAdmin): ?>
                            <a href="/admin/settings" class="ud-item">
                                <svg class="ud-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
                                Nastavení
                            </a>
                            <?php endif; ?>
                            <form method="POST" action="/logout">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <button type="submit" class="ud-item ud-item-logout">
                                    <svg class="ud-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/></svg>
                                    Odhlásit se
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <a href="/auth/redirect" class="btn-nav-login">Přihlásit se</a>
            <?php endif; ?>
        </nav>
        <button class="nav-toggle" aria-label="Otevřít menu" aria-expanded="false">&#9776;</button>
    </div>
</header>

<main class="site-main">
    <?php if (!empty($flash)): ?>
        <?php foreach ($flash as $type => $msg): ?>
            <div class="flash flash-<?= htmlspecialchars($type) ?>" role="alert">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="footer-inner">
        <a href="/" class="footer-logo-link">
            <img src="/assets/images/d07410ff-41c5-44c7-ad94-9925be2847c7.webp" alt="Old Times RP" class="footer-logo-img">
        </a>
        <p class="footer-text">&copy; <?= date('Y') ?> Old Times RP &mdash; Všechna práva vyhrazena.</p>
        <p class="footer-text" style="margin-top:.25rem;font-size:.65rem;opacity:.45;letter-spacing:.12em;">Tento projekt není spojen s Rockstar Games ani Take-Two Interactive.</p>
    </div>
</footer>

<script src="/assets/js/main.js?v=7"></script>
</body>
</html>
