<section class="hero hero--short">
    <div class="hero-inner">
        <h1 class="hero-title" style="font-size:4rem;">Novinky</h1>
    </div>
</section>

<section class="section news-page">
    <div class="container">
        <?php if (empty($items)): ?>
            <p class="empty-notice">Žádné příspěvky zatím nebyly přidány.</p>
        <?php else: ?>

            <?php
                $first = array_shift($items);
                $firstLink = '/novinky/' . htmlspecialchars($first['slug']);
            ?>
            <article class="news-hero-card rdr-panel">
                <div class="news-hero-card-glow"></div>
                <div class="news-hero-card-inner">
                    <div class="news-hero-card-label">Nejnovější</div>
                    <div class="news-hero-card-meta">
                        <span class="news-cat" style="--cat-bg:<?= htmlspecialchars($first['category_color']) ?>;">
                            <?= htmlspecialchars($first['category']) ?>
                        </span>
                        <time class="news-date"><?= htmlspecialchars(date('j. n. Y', strtotime($first['published_at']))) ?></time>
                        <?php if (!empty($first['author_name'])): ?>
                            <span class="news-author"><?= htmlspecialchars($first['author_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <h2 class="news-hero-card-title"><a href="<?= $firstLink ?>"><?= htmlspecialchars($first['title']) ?></a></h2>
                    <p class="news-hero-card-excerpt"><?= mb_substr(strip_tags($first['body_html']), 0, 360) ?>…</p>
                    <a href="<?= $firstLink ?>" class="btn btn-primary">Číst článek →</a>
                </div>
            </article>

            <?php if (!empty($items)): ?>
                <div class="news-timeline">
                    <div class="news-timeline-line"></div>
                    <?php
                        $prevMonth = '';
                        $months = ['','Leden','Únor','Březen','Duben','Květen','Červen','Červenec','Srpen','Září','Říjen','Listopad','Prosinec'];
                        foreach ($items as $i => $item):
                            $link = '/novinky/' . htmlspecialchars($item['slug']);
                            $ts = strtotime($item['published_at']);
                            $monthLabel = $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
                            $showMonth = ($monthLabel !== $prevMonth);
                            $prevMonth = $monthLabel;
                            $side = ($i % 2 === 0) ? 'left' : 'right';
                    ?>
                        <?php if ($showMonth): ?>
                            <div class="news-timeline-month">
                                <span><?= htmlspecialchars(ucfirst($monthLabel)) ?></span>
                            </div>
                        <?php endif; ?>
                        <article class="news-tl-item news-tl-item--<?= $side ?>">
                            <div class="news-tl-dot"></div>
                            <div class="news-tl-card">
                                <div class="news-tl-card-top" style="--cat-bg:<?= htmlspecialchars($item['category_color']) ?>;"></div>
                                <div class="news-tl-card-body">
                                    <div class="news-tl-meta">
                                        <span class="news-cat" style="--cat-bg:<?= htmlspecialchars($item['category_color']) ?>;">
                                            <?= htmlspecialchars($item['category']) ?>
                                        </span>
                                        <time class="news-date"><?= htmlspecialchars(date('j. n. Y', strtotime($item['published_at']))) ?></time>
                                    </div>
                                    <h3 class="news-tl-title"><a href="<?= $link ?>"><?= htmlspecialchars($item['title']) ?></a></h3>
                                    <p class="news-tl-excerpt"><?= mb_substr(strip_tags($item['body_html']), 0, 180) ?>…</p>
                                    <a href="<?= $link ?>" class="news-tl-link">Číst více <span>→</span></a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php $totalPages = (int)ceil($total / $perPage); ?>
            <?php if ($totalPages > 1): ?>
                <nav class="pagination" aria-label="Stránkování">
                    <?php if ($page > 1): ?>
                        <a href="/novinky?page=<?= $page - 1 ?>" class="btn btn-ghost btn-sm">« Předchozí</a>
                    <?php endif; ?>
                    <span class="pagination-info">Strana <?= $page ?> z <?= $totalPages ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a href="/novinky?page=<?= $page + 1 ?>" class="btn btn-ghost btn-sm">Následující »</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
