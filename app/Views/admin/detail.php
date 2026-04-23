<?php
$s  = $app['status'];
$iv = $app['interview_status'] ?? null;

if ($s === 'approved' && $iv === 'passed')      { $badgeCls = 'status-active';   $badgeLabel = 'Aktivní'; }
elseif ($s === 'approved')                      { $badgeCls = 'status-approved';  $badgeLabel = 'Čeká na pohovor'; }
elseif ($s === 'rejected')                      { $badgeCls = 'status-rejected';  $badgeLabel = 'Zamítnuto'; }
elseif ($s === 'blocked' && $iv === 'failed')   { $badgeCls = 'status-blocked';  $badgeLabel = 'Pohovor nesplněn'; }
elseif ($s === 'blocked')                       { $badgeCls = 'status-blocked';   $badgeLabel = 'Zablokováno'; }
else                                            { $badgeCls = 'status-pending';   $badgeLabel = 'Čeká na posouzení'; }

$avatarUrl = $app['avatar']
    ? 'https://cdn.discordapp.com/avatars/' . htmlspecialchars($app['discord_id']) . '/' . htmlspecialchars($app['avatar']) . '.png'
    : 'https://cdn.discordapp.com/embed/avatars/0.png';
?>
<section class="section">
    <div class="container container-narrow">
        <a href="/admin" class="back-link">&larr; Zpět na seznam</a>

        <!-- Header -->
        <div class="app-detail-header">
            <img src="<?= $avatarUrl ?>" alt="<?= htmlspecialchars($app['username']) ?>" class="app-detail-avatar" width="56" height="56">
            <div class="app-detail-meta">
                <h1 class="app-detail-name"><?= htmlspecialchars($app['username']) ?></h1>
                <div class="app-detail-sub">
                    <span class="app-detail-id"><?= htmlspecialchars($app['discord_id']) ?></span>
                    <span class="app-detail-dot">·</span>
                    <span>Pokus #<?= (int)$app['attempt_number'] ?></span>
                    <span class="app-detail-dot">·</span>
                    <span><?= date('j. n. Y H:i', strtotime($app['submitted_at'])) ?></span>
                </div>
            </div>
            <span class="status-badge <?= $badgeCls ?>"><?= $badgeLabel ?></span>
        </div>

        <!-- Form answers -->
        <div class="app-detail-answers">
            <h2 class="app-detail-section-title">Odpovědi formuláře</h2>
            <?php if (empty($formData)): ?>
                <p class="empty-notice">Žádné odpovědi.</p>
            <?php else: ?>
                <div class="app-answers-grid">
                    <?php $i = 0; foreach ($formData as $key => $val): $i++; ?>
                        <div class="app-answer-card">
                            <div class="app-answer-num"><?= $i ?></div>
                            <div class="app-answer-label"><?= htmlspecialchars($labelMap[$key] ?? $key) ?></div>
                            <div class="app-answer-value"><?= is_array($val) ? htmlspecialchars(implode(', ', $val)) : nl2br(htmlspecialchars((string)$val)) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Reviewer info -->
        <?php if ($app['reviewer_id'] || $app['interview_reviewer_id']): ?>
        <div class="app-detail-answers" style="margin-top:1rem;">
            <h2 class="app-detail-section-title">Vyhodnocení</h2>
            <?php if ($app['reviewer_id']): ?>
            <div class="app-answer-row">
                <span class="app-answer-key">
                    <?= ($s === 'rejected') ? 'Zamítnul' : 'Schválil (Fáze 1)' ?>
                </span>
                <span class="app-answer-val">
                    <?= htmlspecialchars($app['reviewer_name'] ?? 'Neznámý') ?>
                    &nbsp;&middot;&nbsp;
                    <?= date('j. n. Y H:i', strtotime($app['reviewed_at'])) ?>
                    <?php if ($s === 'rejected' && $app['error_count'] !== null): ?>
                        &nbsp;&middot;&nbsp;<span style="color:#ff6060;"><?= (int)$app['error_count'] ?> chyb</span>
                    <?php endif; ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Interview History -->
        <?php if (!empty($interviewHistory)): ?>
        <div class="app-detail-answers" style="margin-top:1rem;">
            <h2 class="app-detail-section-title">Historie pohovorů</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Výsledek</th>
                        <th>Chyby</th>
                        <th>Hodnotil</th>
                        <th>Datum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($interviewHistory as $i => $ih): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <?php if ($ih['result'] === 'passed'): ?>
                                    <span class="status-badge status-active">Splněn</span>
                                <?php else: ?>
                                    <span class="status-badge status-rejected">Nesplněn</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $ih['error_count'] !== null ? (int)$ih['error_count'] : '—' ?></td>
                            <td><?= htmlspecialchars($ih['reviewer_name'] ?? 'Neznámý') ?></td>
                            <td><?= date('j. n. Y H:i', strtotime($ih['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <?php if ($s === 'pending'): ?>
            <div class="app-detail-actions">
                <h2 class="app-detail-section-title">Akce</h2>
                <div class="app-detail-action-row">
                    <form method="POST" action="/admin/<?= (int)$app['id'] ?>/approve">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <button type="submit" class="btn btn-approve">
                            &#10003; Schválit — přejít na pohovor
                        </button>
                    </form>
                    <form method="POST" action="/admin/<?= (int)$app['id'] ?>/reject" class="reject-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="error_count" class="reject-error-count" value="0">
                        <button type="button" class="btn btn-reject reject-trigger">
                            &#10007; Zamítnout
                        </button>
                    </form>
                </div>
            </div>

        <?php elseif ($s === 'approved' && $iv === 'passed'): ?>
            <?php if (!empty($canReinterview)): ?>
            <div class="app-detail-actions">
                <h2 class="app-detail-section-title">Allowlist aktivní</h2>
                <div class="app-detail-action-row">
                    <button type="button" class="btn btn-ghost" id="reinterviewTrigger">&#8635; Resetovat pokusy — znovu na pohovor</button>
                </div>
            </div>
            <?php endif; ?>

        <?php elseif ($s === 'approved' && ($iv === null || $iv === 'pending')): ?>
            <div class="app-detail-actions">
                <h2 class="app-detail-section-title">Fáze 2 — Pohovor (<?= (int)$app['interview_attempts'] ?>/3 pokusů)</h2>
                <div class="app-detail-action-row">
                    <form method="POST" action="/admin/<?= (int)$app['id'] ?>/interview/pass">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <button type="submit" class="btn btn-approve">&#10003; Pohovor splněn</button>
                    </form>
                    <form method="POST" action="/admin/<?= (int)$app['id'] ?>/interview/fail" id="interviewFailForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="error_count" id="interviewFailErrorCount" value="0">
                        <button type="button" class="btn btn-reject" id="interviewFailTrigger">&#10007; Pohovor nesplněn</button>
                    </form>
                    <?php if (!empty($canReinterview) && (int)$app['interview_attempts'] > 0): ?>
                    <button type="button" class="btn btn-ghost" id="reinterviewTrigger">&#8635; Resetovat pokusy</button>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($s === 'blocked' && $iv === 'failed'): ?>
            <div class="app-detail-actions">
                <h2 class="app-detail-section-title">Pohovor nesplněn (<?= (int)$app['interview_attempts'] ?>/3 pokusů vyčerpáno)</h2>
                <?php if (!empty($canReinterview)): ?>
                <div class="app-detail-action-row">
                    <button type="button" class="btn btn-ghost" id="reinterviewTrigger">&#8635; Resetovat pokusy — povolit nový pohovor</button>
                </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>

        <!-- Cheatsheet / Tahák -->
        <?php if (!empty($cheatsheetQuestions) && $s === 'approved' && ($iv === null || $iv === 'pending')): ?>
        <div class="cheatsheet-box">
            <h2 class="app-detail-section-title">📋 Tahák — náhodné otázky (<?= count($cheatsheetQuestions) ?>)</h2>
            <div class="cheatsheet-questions">
                <?php foreach ($cheatsheetQuestions as $qi => $q): ?>
                    <details class="cheatsheet-item">
                        <summary class="cheatsheet-question">
                            <span class="cheatsheet-num"><?= $qi + 1 ?></span>
                            <?= htmlspecialchars($q['title']) ?>
                        </summary>
                        <div class="cheatsheet-answer content-body">
                            <?= $q['body_html'] ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
            <form method="GET" action="/admin/<?= (int)$app['id'] ?>" style="margin-top:0.75rem;">
                <button type="submit" class="btn btn-ghost btn-sm">&#8635; Nové otázky</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Reject Modal -->
<div id="rejectModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="rejectModalTitle" hidden>
    <div class="modal-box">
        <h2 id="rejectModalTitle" class="modal-title">Zamítnout žádost</h2>
        <div class="form-group">
            <label class="form-label" for="modalErrorCount">Počet chyb <span class="req">*</span></label>
            <input type="number" id="modalErrorCount" class="form-control" min="0" value="0">
        </div>
        <div class="form-actions">
            <button id="confirmReject" class="btn btn-reject">Potvrdit zamítnutí</button>
            <button id="cancelReject"  class="btn btn-ghost">Zrušit</button>
        </div>
    </div>
</div>

<!-- Interview Fail Modal -->
<div id="interviewFailModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="interviewFailModalTitle" hidden>
    <div class="modal-box">
        <h2 id="interviewFailModalTitle" class="modal-title">Pohovor nesplněn</h2>
        <div class="form-group">
            <label class="form-label" for="interviewFailCount">Počet chyb <span class="req">*</span></label>
            <input type="number" id="interviewFailCount" class="form-control" min="0" value="0">
        </div>
        <div class="form-actions">
            <button id="confirmInterviewFail" class="btn btn-reject">Potvrdit nesplnění</button>
            <button id="cancelInterviewFail" class="btn btn-ghost">Zrušit</button>
        </div>
    </div>
</div>

<?php if (!empty($canReinterview)): ?>
<!-- Reinterview Confirm Modal -->
<div id="reinterviewModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="reinterviewModalTitle" hidden>
    <div class="modal-box">
        <h2 id="reinterviewModalTitle" class="modal-title">Resetovat pokusy o pohovor</h2>
        <p style="font-family:var(--font-body);font-size:0.92rem;color:var(--grey-light);margin-bottom:1.5rem;line-height:1.6;">
            Tato akce <strong>resetuje všechny pokusy o pohovor</strong>.<br>
            Hráč bude mít znovu 3 pokusy na absolvování pohovoru.
        </p>
        <form method="POST" action="/admin/<?= (int)$app['id'] ?>/reinterview">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="form-actions">
                <button type="submit" class="btn btn-reject">&#8635; Potvrdit reset</button>
                <button type="button" id="cancelReinterview" class="btn btn-ghost">Zrušit</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var trigger = document.getElementById('reinterviewTrigger');
    var modal   = document.getElementById('reinterviewModal');
    var cancel  = document.getElementById('cancelReinterview');
    if (!trigger || !modal) return;
    trigger.addEventListener('click', function () {
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
    });
    cancel.addEventListener('click', close);
    modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) close(); });
    function close() { modal.hidden = true; document.body.style.overflow = ''; }
}());
</script>
<?php endif; ?>

<script>
(function () {
    // ── Interview Fail Modal ───────────────────────────────────────────────
    var failTrigger = document.getElementById('interviewFailTrigger');
    var failModal   = document.getElementById('interviewFailModal');
    if (!failTrigger || !failModal) return;

    var failCountIn  = document.getElementById('interviewFailCount');
    var failHidden   = document.getElementById('interviewFailErrorCount');
    var failForm     = document.getElementById('interviewFailForm');
    var confirmBtn   = document.getElementById('confirmInterviewFail');
    var cancelBtn    = document.getElementById('cancelInterviewFail');

    failTrigger.addEventListener('click', function () {
        failCountIn.value = '0';
        failModal.hidden  = false;
        document.body.style.overflow = 'hidden';
        failCountIn.focus();
    });

    confirmBtn.addEventListener('click', function () {
        var n = parseInt(failCountIn.value, 10);
        if (isNaN(n) || n < 0) { failCountIn.focus(); return; }
        failHidden.value = n;
        failModal.hidden = true;
        document.body.style.overflow = '';
        failForm.submit();
    });

    cancelBtn.addEventListener('click', closeFailModal);
    failModal.addEventListener('click', function (e) { if (e.target === failModal) closeFailModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !failModal.hidden) closeFailModal(); });

    function closeFailModal() { failModal.hidden = true; document.body.style.overflow = ''; }
}());
</script>
