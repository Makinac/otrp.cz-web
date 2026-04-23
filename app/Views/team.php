<section class="hero hero--short">
    <div class="hero-inner">
        <h1 class="hero-title" style="font-size:4rem;">Náš Tým</h1>
    </div>
</section>

<section class="section team-page">
    <div class="container">
        <?php if (empty($teamSections)): ?>
            <p class="empty-notice">Data týmu jsou momentálně nedostupná.</p>
        <?php else: ?>

            <?php
                // First section = lead / vedení – featured layout
                $first   = true;
                $sIndex  = 0;
            ?>

            <?php foreach ($teamSections as $section): ?>
                <?php if (empty($section['members'])) continue; ?>
                <?php
                    $color = htmlspecialchars($section['color'] ?? '#cc0000');
                    $count = count($section['members']);
                    $isFeatured = $first;
                    $first = false;
                    $sIndex++;
                ?>

                <div class="team-section <?= $isFeatured ? 'team-section--featured' : '' ?>" style="--cat-color: <?= $color ?>;">
                    <div class="team-section-head">
                        <div class="team-section-line"></div>
                        <h2 class="team-section-title"><?= htmlspecialchars($section['name']) ?></h2>
                        <div class="team-section-line"></div>
                    </div>

                    <div class="team-grid <?= $isFeatured ? 'team-grid--featured' : '' ?>">
                        <?php foreach ($section['members'] as $mi => $member): ?>
                            <?php
                                $avatar = htmlspecialchars($member['avatar_url'] ?? 'https://cdn.discordapp.com/embed/avatars/0.png');
                                $name   = htmlspecialchars($member['username']);
                            ?>
                            <div class="team-card <?= $isFeatured ? 'team-card--featured' : '' ?>"
                                 style="animation-delay: <?= ($mi * 0.06) ?>s;">
                                <div class="team-card-glow"></div>
                                <div class="team-avatar-ring">
                                    <img src="<?= $avatar ?>" alt="<?= $name ?>" class="team-avatar"
                                         loading="lazy" width="<?= $isFeatured ? 140 : 100 ?>" height="<?= $isFeatured ? 140 : 100 ?>">
                                </div>
                                <div class="team-info">
                                    <strong class="team-name"><?= $name ?></strong>
                                    <span class="team-role-badge"><?= htmlspecialchars($section['name']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php endforeach; ?>

        <?php endif; ?>
    </div>
</section>
