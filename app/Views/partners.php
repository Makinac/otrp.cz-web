<section class="hero hero--short">
    <div class="hero-inner">
        <h1 class="hero-title" style="font-size:4rem;">Partneři</h1>
    </div>
</section>

<section class="section partners-page">
    <div class="container">
        <?php if (empty($partners)): ?>
            <p class="empty-notice">Momentálně nemáme žádné partnery.</p>
        <?php else: ?>
            <div class="partners-showcase">
                <?php foreach ($partners as $i => $partner): ?>
                    <?php
                        $hasUrl  = !empty($partner['url']);
                        $tag     = $hasUrl ? 'a' : 'div';
                        $attrs   = $hasUrl
                            ? ' href="' . htmlspecialchars($partner['url']) . '" target="_blank" rel="noopener noreferrer"'
                            : '';
                        $initial = mb_strtoupper(mb_substr($partner['name'], 0, 1));
                        $domain  = $hasUrl ? preg_replace('#^https?://(www\.)?#', '', rtrim($partner['url'], '/')) : '';
                        $isEven  = ($i % 2 === 0);
                    ?>
                    <<?= $tag ?> class="partner-row<?= $hasUrl ? ' partner-row--link' : '' ?>"<?= $attrs ?>>
                        <div class="partner-row-accent"></div>
                        <div class="partner-row-inner<?= $isEven ? '' : ' partner-row-inner--flip' ?>">
                            <div class="partner-row-logo">
                                <?php if (!empty($partner['logo_url'])): ?>
                                    <img src="<?= htmlspecialchars($partner['logo_url']) ?>"
                                         alt="<?= htmlspecialchars($partner['name']) ?>"
                                         class="partner-row-img" loading="lazy">
                                <?php else: ?>
                                    <span class="partner-row-fallback"><?= $initial ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="partner-row-content">
                                <h3 class="partner-row-name"><?= htmlspecialchars($partner['name']) ?></h3>
                                <?php if (!empty($partner['description'])): ?>
                                    <p class="partner-row-desc"><?= htmlspecialchars($partner['description']) ?></p>
                                <?php endif; ?>
                                <?php if ($hasUrl): ?>
                                    <span class="partner-row-url"><?= htmlspecialchars($domain) ?> <span>↗</span></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </<?= $tag ?>>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
