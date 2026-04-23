<section class="hero hero--short">
    <div class="hero-inner">
        <h1 class="hero-title" style="font-size:4rem;">Pravidla serveru</h1>
    </div>
</section>

<section class="section rules-page">
    <div class="container">
        <?php if (empty($sections)): ?>
            <p class="empty-notice">Pravidla nebyla zatím nastavena.</p>
        <?php else: ?>
            <div class="rules-layout">
                <aside class="rules-sidebar">
                    <nav class="rules-toc rdr-panel">
                        <div class="rules-toc-title">Obsah</div>
                        <?php foreach ($sections as $idx => $sec): ?>
                            <a href="#rule-<?= (int)$sec['id'] ?>" class="rules-toc-item<?= $idx === 0 ? ' rules-toc-active' : '' ?>">
                                <span class="rules-toc-num"><?= $idx + 1 ?></span>
                                <span class="rules-toc-text"><?= htmlspecialchars($sec['title']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </aside>

                <div class="rules-content">
                    <?php foreach ($sections as $idx => $sec): ?>
                        <div class="rules-section" id="rule-<?= (int)$sec['id'] ?>">
                            <div class="rules-section-head">
                                <span class="rules-section-num"><?= $idx + 1 ?></span>
                                <h2 class="rules-section-title"><?= htmlspecialchars($sec['title']) ?></h2>
                            </div>
                            <div class="rules-body content-body">
                                <?= $sec['body_html'] ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <p class="rules-updated">
                        Naposledy aktualizováno:
                        <?= htmlspecialchars(date('j. n. Y H:i', strtotime($sections[count($sections)-1]['updated_at']))) ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
(function () {
    var tocItems = document.querySelectorAll('.rules-toc-item');
    var sections = document.querySelectorAll('.rules-section');
    if (!tocItems.length || !sections.length) return;

    function updateActive() {
        var scrollY = window.scrollY + 120;
        var current = 0;
        sections.forEach(function (sec, i) {
            if (sec.offsetTop <= scrollY) current = i;
        });
        tocItems.forEach(function (item, i) {
            item.classList.toggle('rules-toc-active', i === current);
        });
    }

    window.addEventListener('scroll', updateActive, { passive: true });
    updateActive();
})();
</script>
