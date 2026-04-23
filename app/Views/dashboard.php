<section class="section">
    <div class="container container-narrow">
        <h1 class="page-title">Allowlist</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>

        <?php
        $MAX_ATTEMPTS  = 3;
        $latestApp     = $applications[0] ?? null;
        // Počítáme spálené pokusy: zamítnuté žádosti + neúspěšné pohovory
        $failedCount = count(array_filter($applications, fn($a) =>
            $a['status'] === 'rejected'
            || ($a['status'] === 'blocked' && ($a['interview_status'] ?? null) === 'failed')
        ));

        $state = 'none';
        if ($blacklisted) {
            $state = 'blacklisted';
        } elseif ($latestApp) {
            $s  = $latestApp['status'];
            $iv = $latestApp['interview_status'] ?? null;
            if ($s === 'approved' && $iv === 'passed')       $state = 'active';
            elseif ($s === 'approved' && $iv === 'failed')   $state = 'interview_failed';
            elseif ($s === 'approved')                       $state = 'interview_pending';
            elseif ($s === 'blocked' && $iv === 'failed')    $state = 'interview_failed';
            elseif ($s === 'blocked')                        $state = 'blocked';
            elseif ($s === 'pending')                        $state = 'pending';
            elseif ($s === 'rejected' && $failedCount >= $MAX_ATTEMPTS) $state = 'rejected_final';
            elseif ($s === 'rejected')                       $state = 'rejected';
        }

        // Může podat novou žádost: bez žádosti, zamítnutý nebo neuspěl u pohovoru — pokud má zbývající pokusy
        $canApply = in_array($state, ['none', 'rejected'])
            || ($state === 'interview_failed' && $failedCount < $MAX_ATTEMPTS);

        function appBadge(array $app): array {
            $s  = $app['status'];
            $iv = $app['interview_status'] ?? null;
            if ($s === 'approved' && $iv === 'passed')  return ['cls' => 'status-active',   'icon' => '&#10003;', 'label' => 'Aktivní'];
            if ($s === 'approved' && $iv === 'failed')  return ['cls' => 'status-blocked',  'icon' => '&#10007;', 'label' => 'Pohovor nesplněn'];
            if ($s === 'approved')                      return ['cls' => 'status-approved',  'icon' => '&#9670;',  'label' => 'Čeká na pohovor'];
            if ($s === 'rejected')                      return ['cls' => 'status-rejected',  'icon' => '&#10007;', 'label' => 'Formulář zamítnut'];
            if ($s === 'blocked' && $iv === 'failed')   return ['cls' => 'status-blocked',  'icon' => '&#10007;', 'label' => 'Pohovor nesplněn'];
            if ($s === 'blocked')                       return ['cls' => 'status-blocked',   'icon' => '&#10007;', 'label' => 'Zablokováno'];
            return ['cls' => 'status-pending', 'icon' => '&#9670;', 'label' => 'Čeká na posouzení'];
        }
        ?>

        <?php if ($state === 'active'): ?>
            <div class="al-active-banner">
                <span class="al-active-icon">&#10003;</span>
                <div class="al-active-text">
                    <div class="al-active-title">Aktivní allowlist</div>
                    <?php if (!empty($latestApp['reviewed_at'])): ?>
                        <div class="al-active-since">Aktivní od <?= date('j. n. Y', strtotime($latestApp['reviewed_at'])) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($state === 'blacklisted'): ?>
            <div class="al-status-card al-status-danger">
                <div class="status-badge status-blocked">&#10007; Na denylistu</div>
                <p>Jsi na denylistu.</p>
                <?php if (!$activeAppeal): ?>
                    <a href="/appeal" class="btn btn-secondary mt-1">Podat odvolání</a>
                <?php else: ?>
                    <p class="appeal-status">Odvolání čeká na vyřízení.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Seznam žádostí -->
        <div class="al-app-list">
            <h2 class="al-list-title">Žádosti</h2>
            <?php if (empty($applications)): ?>
                <p class="al-empty">Zatím žádná žádost.</p>
            <?php else: ?>
                <?php foreach ($applications as $app): ?>
                    <?php $badge = appBadge($app); ?>
                    <div class="al-app-item">
                        <span class="al-app-num">Pokus <?= (int)$app['attempt_number'] ?></span>
                        <span class="al-app-date"><?= date('j. n. Y', strtotime($app['submitted_at'])) ?></span>
                        <span class="status-badge <?= $badge['cls'] ?>"><?= $badge['icon'] ?> <?= $badge['label'] ?></span>
                        <?php if ($app['status'] === 'rejected' && !empty($app['error_count'])): ?>
                            <span class="al-app-errors"><?= (int)$app['error_count'] ?> <?= $app['error_count'] === 1 ? 'chyba' : ($app['error_count'] <= 4 ? 'chyby' : 'chyb') ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- CTA -->
        <div class="al-cta">
            <?php if ($canApply): ?>
                <?php $remaining = $MAX_ATTEMPTS - $failedCount; ?>
                <?php if ($state === 'interview_failed'): ?>
                    <p class="al-cta-info">Nesplnil jsi pohovor — pokus se počítá jako spálený. Zbývá ti <strong><?= $remaining ?></strong> <?= $remaining === 1 ? 'pokus' : ($remaining <= 4 ? 'pokusy' : 'pokusů') ?>.</p>
                <?php else: ?>
                    <p class="al-cta-info">Zbývá ti <strong><?= $remaining ?></strong> <?= $remaining === 1 ? 'pokus' : ($remaining <= 4 ? 'pokusy' : 'pokusů') ?> ze <?= $MAX_ATTEMPTS ?>.</p>
                <?php endif; ?>
                <a href="/apply" class="btn btn-primary">Požádat o allowlist</a>
            <?php elseif ($state === 'interview_pending'): ?>
                <p class="al-cta-info">Žádost schválena — čeká tě pohovor na Discordu.</p>
            <?php elseif ($state === 'rejected_final' || ($state === 'interview_failed' && $failedCount >= $MAX_ATTEMPTS)): ?>
                <p class="al-cta-blocked">Vyčerpal jsi všechny <?= $MAX_ATTEMPTS ?> pokusy. Žádost je uzavřena.</p>
            <?php elseif ($state === 'blocked'): ?>
                <p class="al-cta-blocked">Nesplnil jsi podmínky — žádost zablokována.</p>
            <?php endif; ?>
        </div>

        <?php if ($state !== 'blacklisted' && $activeAppeal): ?>
            <?php
                $appealTypeLabel = match ($activeAppeal['type'] ?? '') {
                    'ban' => 'Ban',
                    'warn' => 'Warn',
                    'blacklist' => 'Denylist',
                    'allowlist' => 'Nepodařený Allowlist',
                    default => (string)($activeAppeal['type'] ?? ''),
                };
            ?>
            <div class="al-status-card mt-2">
                <div class="status-badge status-pending">&#9670; Probíhá odvolání</div>
                <p>Typ: <strong><?= htmlspecialchars($appealTypeLabel) ?></strong> — čeká na vyřízení.</p>
            </div>
        <?php endif; ?>
    </div>
</section>
