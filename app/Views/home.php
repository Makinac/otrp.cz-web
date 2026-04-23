<?php
    $blocks = $blocks ?? [];
    $esc    = fn(?string $s): string => htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');

    // ── Separate hero blocks from body blocks ──
    $heroBlock   = null;
    $heroButtons = null;
    $bodyBlocks  = [];
    $inHero      = true;

    foreach ($blocks as $b) {
        if ($inHero && $b['type'] === 'hero') {
            $heroBlock = $b;
        } elseif ($inHero && $b['type'] === 'buttons' && $heroBlock !== null) {
            $heroButtons = $b;
        } else {
            $inHero = false;
            $bodyBlocks[] = $b;
        }
    }
?>

<!-- ────────────────────────── HERO ────────────────────────────────── -->
<section class="hero hp-hero">
    <div class="hp-hero-inner">

        <img src="/assets/images/d07410ff-41c5-44c7-ad94-9925be2847c7.webp"
             alt="Old Times RP"
             class="hp-logo"
             width="340" height="340">

        <?php if (!empty($heroBlock['data']['desc'])): ?>
        <div class="hp-hero-sep"><span>✦</span></div>
        <p class="hp-hero-desc"><?= $esc($heroBlock['data']['desc']) ?></p>
        <?php endif; ?>

        <?php if (!empty($heroButtons['data']['items'])): ?>
        <div class="hp-hero-actions">
            <?php foreach ($heroButtons['data']['items'] as $btn): ?>
                <?php if (!empty($btn['text']) && !empty($btn['url'])): ?>
                    <?php
                        // Swap login button for Allowlist when logged in
                        if ($isLoggedIn && ($btn['url'] ?? '') === '/auth/redirect') {
                            echo '<a href="/allowlist" class="btn btn-primary">Allowlist</a>';
                            continue;
                        }
                    ?>
                    <a href="<?= $esc($btn['url']) ?>"
                       class="btn btn-<?= $esc($btn['style'] ?? 'primary') ?>"
                       <?= str_starts_with($btn['url'] ?? '', 'http') ? 'target="_blank" rel="noopener noreferrer"' : '' ?>><?= $esc($btn['text']) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</section>

<?php if (!empty($latestNews)): ?>
<?php
    $newsEsc  = fn(?string $s): string => htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
    $newsLink = '/novinky/' . $newsEsc($latestNews['slug'] ?? '');
    $newsDate = !empty($latestNews['published_at']) ? date('j. n. Y', strtotime($latestNews['published_at'])) : '';
    $newsExcerpt = mb_substr(strip_tags($latestNews['body_html'] ?? ''), 0, 280);
?>
<!-- ─────────────────────── LATEST NEWS ────────────────────────────── -->
<section class="hp-latest-news">
    <div class="hp-latest-news-inner container">
        <div class="hp-latest-news-label">Nejnovější novinka</div>
        <div class="hp-latest-news-meta">
            <?php if (!empty($latestNews['category'])): ?>
                <span class="news-cat" style="--cat-bg:<?= $newsEsc($latestNews['category_color'] ?? '#8b1a1a') ?>;">
                    <?= $newsEsc($latestNews['category']) ?>
                </span>
            <?php endif; ?>
            <?php if ($newsDate): ?>
                <time class="news-date"><?= $newsEsc($newsDate) ?></time>
            <?php endif; ?>
        </div>
        <h2 class="hp-latest-news-title">
            <a href="<?= $newsLink ?>"><?= $newsEsc($latestNews['title'] ?? '') ?></a>
        </h2>
        <?php if ($newsExcerpt): ?>
            <p class="hp-latest-news-excerpt"><?= $newsEsc($newsExcerpt) ?>…</p>
        <?php endif; ?>
        <div class="hp-latest-news-actions">
            <a href="<?= $newsLink ?>" class="btn btn-primary">Číst celý článek →</a>
            <a href="/novinky" class="btn btn-ghost">Všechny novinky</a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($bodyBlocks)): ?>
<!-- ─────────────────────────── BODY BLOCKS ─────────────────────────── -->
<div class="hp-body">
    <?php
    // Group consecutive card/steps blocks with their preceding heading into sections
    $i     = 0;
    $total = count($bodyBlocks);
    while ($i < $total):
        $b = $bodyBlocks[$i];
        $d = $b['data'] ?? [];
    ?>
    <?php switch ($b['type']):
        case 'heading': ?>
            <?php $lvl = max(2, min(4, (int)($d['level'] ?? 2))); ?>
            <div class="section hp-section">
                <div class="container">
                    <<?= $lvl ?> class="hp-section-title"><?= $esc($d['text'] ?? '') ?></<?= $lvl ?>>
                    <div class="hp-section-ornament"><span>✦ ✦ ✦</span></div>
            <?php
            // Peek at next blocks — render them inside this section
            $j = $i + 1;
            while ($j < $total && in_array($bodyBlocks[$j]['type'], ['cards', 'steps', 'text', 'buttons', 'divider', 'spacer', 'html'], true)):
                $nb = $bodyBlocks[$j];
                $nd = $nb['data'] ?? [];
                if ($nb['type'] === 'divider') break;
                if ($nb['type'] === 'spacer') break;
            ?>
                    <?php if ($nb['type'] === 'cards'): ?>
                        <div class="cards-row">
                            <?php foreach (($nd['items'] ?? []) as $card): ?>
                                <div class="hp-feature-card">
                                    <div class="hp-feature-icon">
                                        <svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
                                    </div>
                                    <?php if (!empty($card['title'])): ?>
                                        <div class="hp-feature-title"><?= $esc($card['title']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($card['text'])): ?>
                                        <p class="hp-feature-text"><?= $esc($card['text']) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($nb['type'] === 'steps'): ?>
                        <div class="hp-steps">
                            <?php foreach (($nd['items'] ?? []) as $si => $step): ?>
                                <div class="hp-step">
                                    <span class="hp-step-num"><?= str_pad((string)($si + 1), 2, '0', STR_PAD_LEFT) ?></span>
                                    <div>
                                        <?php if (!empty($step['title'])): ?><div class="hp-step-title"><?= $esc($step['title']) ?></div><?php endif; ?>
                                        <?php if (!empty($step['text'])): ?><p class="hp-step-text"><?= $esc($step['text']) ?></p><?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($nb['type'] === 'text'): ?>
                        <div class="home-text-block"><?= $nd['content'] ?? '' ?></div>
                    <?php elseif ($nb['type'] === 'buttons'): ?>
                        <div class="hero-actions" style="justify-content:flex-start;margin-top:1rem;">
                            <?php foreach (($nd['items'] ?? []) as $btn): ?>
                                <?php if (!empty($btn['text']) && !empty($btn['url'])): ?>
                                    <a href="<?= $esc($btn['url']) ?>" class="btn btn-<?= $esc($btn['style'] ?? 'primary') ?>"><?= $esc($btn['text']) ?></a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($nb['type'] === 'html'): ?>
                        <?= $nd['code'] ?? '' ?>
                    <?php endif; ?>
            <?php $j++; endwhile; ?>
                </div><!-- .container -->
            </div><!-- .section -->
            <?php $i = $j; ?>
            <?php break;

        case 'divider': ?>
            <div class="divider-double" style="margin:0;"></div>
            <?php $i++; break;

        case 'spacer': ?>
            <?php $sizes = ['small' => '1rem', 'medium' => '2rem', 'large' => '4rem']; ?>
            <div style="height:<?= $sizes[$d['size'] ?? 'medium'] ?? '2rem' ?>;"></div>
            <?php $i++; break;

        case 'cards': ?>
            <section class="section hp-features">
                <div class="container">
                    <div class="cards-row">
                        <?php foreach (($d['items'] ?? []) as $card): ?>
                            <div class="hp-feature-card">
                                <div class="hp-feature-icon">
                                    <svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
                                </div>
                                <?php if (!empty($card['title'])): ?><div class="hp-feature-title"><?= $esc($card['title']) ?></div><?php endif; ?>
                                <?php if (!empty($card['text'])): ?><p class="hp-feature-text"><?= $esc($card['text']) ?></p><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php $i++; break;

        case 'steps': ?>
            <section class="section">
                <div class="container">
                    <div class="hp-steps">
                        <?php foreach (($d['items'] ?? []) as $si => $step): ?>
                            <div class="hp-step">
                                <span class="hp-step-num"><?= str_pad((string)($si + 1), 2, '0', STR_PAD_LEFT) ?></span>
                                <div>
                                    <?php if (!empty($step['title'])): ?><div class="hp-step-title"><?= $esc($step['title']) ?></div><?php endif; ?>
                                    <?php if (!empty($step['text'])): ?><p class="hp-step-text"><?= $esc($step['text']) ?></p><?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php $i++; break;

        case 'text': ?>
            <section class="section">
                <div class="container">
                    <div class="home-text-block"><?= $d['content'] ?? '' ?></div>
                </div>
            </section>
            <?php $i++; break;

        case 'html': ?>
            <section class="section">
                <div class="container"><?= $d['code'] ?? '' ?></div>
            </section>
            <?php $i++; break;

        default: ?>
            <?php $i++; break;
    endswitch; ?>
    <?php endwhile; ?>
</div>
<?php endif; ?>

<!-- ─────────────────────── DISCORD CTA (always) ─────────────────── -->
<section class="hp-discord-cta">
    <div class="hp-discord-inner">
        <h2 class="hp-discord-title">Připoj se k nám</h2>
        <p class="hp-discord-sub">
            Celá komunita Old Times RP žije na Discordu — od herních oznámení přes eventové koordinace
            až po technickou podporu. Přidej se a buď první, kdo se dozví novinky.
        </p>
        <a href="https://discord.gg/BmERSfVH9M"
           class="btn btn-discord"
           target="_blank"
           rel="noopener noreferrer">
            <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M20.317 4.37a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 00.031.057 19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 00-.041-.106 13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128 10.2 10.2 0 00.372-.292.074.074 0 01.077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 01.078.01c.12.098.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.892.077.077 0 00-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.095 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.095 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>
            Připojit se na Discord
        </a>
    </div>
</section>


<?php if (!empty($heroBlocks)): ?>
<section class="hero">
    <div class="hero-inner">
        <?php foreach ($heroBlocks as $b): $d = $b['data'] ?? []; ?>
            <?php if ($b['type'] === 'hero'): ?>
                <?php if (!empty($d['title'])): ?>
                    <h1 class="hero-title"><?= $esc($d['title']) ?></h1>
                <?php endif; ?>
                <?php if (!empty($d['desc'])): ?>
                    <p class="hero-desc"><?= $esc($d['desc']) ?></p>
                <?php endif; ?>
            <?php elseif ($b['type'] === 'buttons'): ?>
                <div class="hero-actions">
                    <?php foreach (($d['items'] ?? []) as $btn): ?>
                        <?php if (!empty($btn['text']) && !empty($btn['url'])): ?>
                            <a href="<?= $esc($btn['url']) ?>" class="btn btn-<?= $esc($btn['style'] ?? 'primary') ?>"><?= $esc($btn['text']) ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($bodyBlocks)): ?>
<section class="section home-info">
    <div class="container">
        <?php foreach ($bodyBlocks as $b): $d = $b['data'] ?? []; ?>
            <?php switch ($b['type']):
                case 'heading': ?>
                    <?php $lvl = max(1, min(6, (int)($d['level'] ?? 2))); ?>
                    <h<?= $lvl ?> class="section-heading"><?= $esc($d['text'] ?? '') ?></h<?= $lvl ?>>
                    <?php if (!empty($d['ornament'])): ?>
                        <div class="ornament">✦✦✦</div>
                    <?php endif; ?>
                <?php break; case 'text': ?>
                    <div class="home-text-block"><?= $d['content'] ?? '' ?></div>
                <?php break; case 'buttons': ?>
                    <div class="hero-actions" style="justify-content:flex-start;">
                        <?php foreach (($d['items'] ?? []) as $btn): ?>
                            <?php if (!empty($btn['text']) && !empty($btn['url'])): ?>
                                <a href="<?= $esc($btn['url']) ?>" class="btn btn-<?= $esc($btn['style'] ?? 'primary') ?>"><?= $esc($btn['text']) ?></a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php break; case 'cards': ?>
                    <div class="cards-row">
                        <?php foreach (($d['items'] ?? []) as $card): ?>
                            <div class="card rdr-panel">
                                <?php if (!empty($card['title'])): ?>
                                    <h2 class="card-title"><?= $esc($card['title']) ?></h2>
                                <?php endif; ?>
                                <?php if (!empty($card['text'])): ?>
                                    <p><?= $esc($card['text']) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php break; case 'divider': ?>
                    <div class="ornament"><?= $esc($d['chars'] ?? '✦✦✦') ?></div>
                <?php break; case 'spacer': ?>
                    <?php
                        $sizes = ['small' => '1rem', 'medium' => '2rem', 'large' => '4rem'];
                        $sz = $sizes[$d['size'] ?? 'medium'] ?? '2rem';
                    ?>
                    <div style="height:<?= $sz ?>;"></div>
                <?php break; case 'html': ?>
                    <?= $d['code'] ?? '' ?>
                <?php break; case 'hero': ?>
                    <div class="hero" style="padding:3rem 2rem;">
                        <div class="hero-inner">
                            <?php if (!empty($d['title'])): ?>
                                <h2 class="hero-title" style="font-size:3rem;"><?= $esc($d['title']) ?></h2>
                            <?php endif; ?>
                            <?php if (!empty($d['desc'])): ?>
                                <p class="hero-desc"><?= $esc($d['desc']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php break; endswitch; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
