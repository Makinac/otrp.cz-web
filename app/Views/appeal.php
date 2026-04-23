<section class="section">
    <div class="container container-narrow">
        <h1 class="page-title">Odvolání</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>

        <?php if ($msg = \App\Core\Session::getFlash('success')): ?>
            <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if ($msg = \App\Core\Session::getFlash('error')): ?>
            <div class="alert alert-error"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <!-- Historie odvolání -->
        <div class="al-app-list">
            <h2 class="al-list-title">Historie odvolání</h2>
            <?php if (empty($allAppeals)): ?>
                <p class="al-empty">Zatím žádné odvolání.</p>
            <?php else: ?>
                <?php foreach ($allAppeals as $ap): ?>
                    <?php
                        [$badgeCls, $badgeLabel] = match($ap['status']) {
                            'approved' => ['status-active',   '&#10003; Schváleno'],
                            'rejected' => ['status-rejected', '&#10007; Zamítnuto'],
                            default    => ['status-pending',  '&#9670; Čeká na vyřízení'],
                        };
                        $typeLabel = match($ap['type']) {
                            'ban'       => 'Ban',
                            'warn'      => 'Warn',
                            'blacklist' => 'Denylist',
                            default     => 'Nepodařený Allowlist',
                        };
                    ?>
                    <div class="al-app-item al-appeal-item">
                        <span class="al-app-num"><?= $typeLabel ?></span>
                        <span class="al-app-date"><?= date('j. n. Y', strtotime($ap['created_at'])) ?></span>
                        <span class="al-appeal-reason"><?= htmlspecialchars(mb_strimwidth($ap['reason'], 0, 80, '…')) ?></span>
                        <span class="status-badge <?= $badgeCls ?>"><?= $badgeLabel ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Formulář nového odvolání -->
        <div class="al-cta">
            <?php if ($activeAppeal): ?>
                <p class="al-cta-info">&#9670; Máš aktivní odvolání čekající na vyřízení. Nové odvolání půjde podat až po vyřízení.</p>
            <?php else: ?>
                <h2 class="al-list-title" style="margin-top:1.5rem;">Podat nové odvolání</h2>
                <form method="POST" action="/appeal" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="form-group">
                        <label class="form-label" for="appeal_type">Na co se odvoláváš <span class="req">*</span></label>
                        <select name="type" id="appeal_type" class="form-control" required>
                            <option value="">— Vyber typ —</option>
                            <option value="ban">Ban</option>
                            <option value="warn">Warn</option>
                            <option value="blacklist"<?= $blacklisted ? ' selected' : '' ?>>Denylist</option>
                            <option value="allowlist">Nepodařený Allowlist</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="appeal_reason">Důvod odvolání <span class="req">*</span></label>
                        <textarea name="reason" id="appeal_reason" class="form-control" rows="6" required maxlength="2000" placeholder="Popiš proč si myslíš, že rozhodnutí bylo nesprávné…"></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Podat odvolání</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
