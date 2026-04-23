<section class="section">
    <div class="container">
        <h1 class="page-title">Management</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>
        <?php require __DIR__ . '/_panel_nav.php'; ?>

        <div class="content-list-header">
            <h2 class="section-heading">Novinky</h2>
            <a href="/management/content/new" class="btn btn-primary">&#43; Nový příspěvek</a>
        </div>

        <?php if (empty($items)): ?>
            <p class="empty-notice">Žádné příspěvky.</p>
        <?php else: ?>
            <div class="content-card-grid">
                <?php foreach ($items as $item): ?>
                    <div class="content-card">
                        <div class="content-card-accent" style="background:<?= htmlspecialchars($item['category_color']) ?>;"></div>
                        <div class="content-card-body">
                            <div class="content-card-head">
                                <span class="news-cat" style="--cat-bg:<?= htmlspecialchars($item['category_color']) ?>;">
                                    <?= htmlspecialchars($item['category']) ?>
                                </span>
                                <time class="content-card-date"><?= htmlspecialchars(date('j. n. Y', strtotime($item['published_at']))) ?></time>
                            </div>
                            <h3 class="content-card-title"><?= htmlspecialchars($item['title']) ?></h3>
                            <p class="content-card-excerpt"><?= mb_substr(strip_tags($item['body_html'] ?? ''), 0, 120) ?></p>
                        </div>
                        <div class="content-card-actions">
                            <a href="/management/content/<?= (int)$item['id'] ?>/edit" class="btn btn-ghost btn-sm">Upravit</a>
                            <form method="POST" action="/management/content/<?= (int)$item['id'] ?>/delete"
                                  onsubmit="return confirm('Smazat příspěvek?')">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <button class="btn btn-reject btn-sm">Smazat</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
