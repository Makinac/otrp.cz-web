<section class="hero hero--short">
    <div class="hero-inner">
        <div class="news-detail-meta">
            <span class="news-cat" style="--cat-bg:<?= htmlspecialchars($item['category_color']) ?>;">
                <?= htmlspecialchars($item['category']) ?>
            </span>
            <time class="news-date"><?= htmlspecialchars(date('j. n. Y', strtotime($item['published_at']))) ?></time>
            <?php if (!empty($item['author_name'])): ?>
                <span class="news-author"><?= htmlspecialchars($item['author_name']) ?></span>
            <?php endif; ?>
        </div>
        <h1 class="hero-title" style="font-size:3rem;"><?= htmlspecialchars($item['title']) ?></h1>
    </div>
</section>

<section class="section news-detail-page">
    <div class="container container--narrow">
        <div class="news-detail-card rdr-panel">
            <div class="news-detail-card-accent" style="--cat-bg:<?= htmlspecialchars($item['category_color']) ?>;"></div>
            <div class="news-body content-body">
                <?= $item['body_html'] ?>
            </div>
        </div>
        <div class="news-detail-footer">
            <a href="/novinky" class="btn btn-ghost btn-sm">← Zpět na novinky</a>
        </div>
    </div>
</section>
