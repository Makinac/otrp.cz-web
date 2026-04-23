<?php
$isOpen     = $vote['status'] === 'open';
$hasVoted   = $userVote !== null;
$contextUrls = [];
if (!empty($vote['context_urls'])) {
    $contextUrls = json_decode($vote['context_urls'], true) ?: [];
}

$approveCount = 0;
$rejectCount  = 0;
$abstainCount = 0;
foreach ($entries as $e) {
    if ($e['decision'] === 'approve') $approveCount++;
    elseif ($e['decision'] === 'reject') $rejectCount++;
    else $abstainCount++;
}

$resultLabels = [
    'approved' => 'Schváleno',
    'rejected' => 'Zamítnuto',
    'tie'      => 'Nerozhodně',
];
?>
<section class="section">
    <div class="container">
        <h1 class="page-title">CK Hlasování #<?= (int)$vote['id'] ?></h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>

        <a href="/admin/ck" class="btn btn-secondary" style="margin-bottom:1.25rem;display:inline-block;">← Zpět na seznam</a>

        <!-- Vote info card -->
        <div class="ck-detail-card">
            <div class="ck-detail-header">
                <div class="ck-detail-parties">
                    <div class="ck-party">
                        <span class="ck-party-label">Žadatel</span>
                        <span class="ck-party-name"><?= htmlspecialchars($vote['applicant']) ?></span>
                    </div>
                    <div class="ck-vs">VS</div>
                    <div class="ck-party">
                        <span class="ck-party-label">Oběť</span>
                        <span class="ck-party-name"><?= htmlspecialchars($vote['victim']) ?></span>
                    </div>
                </div>
                <div class="ck-detail-meta">
                    Vytvořil <strong><?= htmlspecialchars($vote['creator_username']) ?></strong>
                    · <?= date('j. n. Y H:i', strtotime($vote['created_at'])) ?>
                    <?php if (!$isOpen): ?>
                        · Uzavřeno <?= date('j. n. Y H:i', strtotime($vote['closed_at'])) ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="ck-detail-description">
                <h3>Popis situace</h3>
                <p><?= nl2br(htmlspecialchars($vote['description'])) ?></p>
            </div>

            <?php if (!empty($contextUrls)): ?>
            <div class="ck-detail-context">
                <h3>Discord kontext</h3>
                <ul>
                    <?php foreach ($contextUrls as $url): ?>
                        <li><a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($url) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Status badge -->
            <?php if (!$isOpen): ?>
                <?php
                    $resCls = match($vote['result']) {
                        'approved' => 'status-active',
                        'rejected' => 'status-rejected',
                        default    => 'status-blocked',
                    };
                ?>
                <div class="ck-result-banner ck-result-<?= htmlspecialchars($vote['result']) ?>">
                    Výsledek: <strong><?= $resultLabels[$vote['result']] ?? $vote['result'] ?></strong>
                    (<?= $approveCount ?> pro / <?= $rejectCount ?> proti / <?= $abstainCount ?> zdržel se)
                </div>
            <?php endif; ?>
        </div>

        <!-- Voting section -->
        <?php if ($isOpen): ?>
            <div class="ck-vote-section">
                <?php if (!$hasVoted): ?>
                    <h3>Tvůj hlas</h3>
                    <form method="POST" action="/admin/ck/<?= (int)$vote['id'] ?>/vote" class="ck-vote-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <div class="form-group">
                            <label for="reason" class="form-label">Odůvodnění — proč tak hlasuješ? Co by se muselo stát, abys hlasoval jinak? *</label>
                            <textarea id="reason" name="reason" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="ck-vote-buttons">
                            <button type="submit" name="decision" value="approve" class="btn ck-btn-approve">
                                <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                Schválit CK
                            </button>
                            <button type="submit" name="decision" value="reject" class="btn ck-btn-reject">
                                <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                Zamítnout CK
                            </button>
                            <button type="submit" name="decision" value="abstain" class="btn ck-btn-abstain">
                                Zdržet se
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <h3>Hlasoval jsi: <strong class="ck-voted-<?= htmlspecialchars($userVote['decision']) ?>"><?= match($userVote['decision']) { 'approve' => 'Schválit', 'reject' => 'Zamítnout', 'abstain' => 'Zdržet se' } ?></strong></h3>
                    <p class="ck-vote-hint">Tvůj hlas byl zaznamenán a nelze ho změnit.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Vote entries table (visible after user voted, when closed, or for vedení) -->
        <?php if ($hasVoted || !$isOpen || $hasRoleVedeni): ?>
            <div class="ck-entries-section">
                <h3>Přehled hlasů (<?= count($entries) ?>)</h3>
                <?php if (empty($entries)): ?>
                    <p class="empty-notice">Zatím nikdo nehlasoval.</p>
                <?php else: ?>
                    <div class="ck-entries-summary">
                        <span class="ck-count-approve"><?= $approveCount ?> pro</span>
                        <span class="ck-count-reject"><?= $rejectCount ?> proti</span>
                        <span class="ck-count-abstain"><?= $abstainCount ?> zdržel se</span>
                    </div>
                    <div class="ck-entries-list">
                        <?php foreach ($entries as $e): ?>
                            <?php
                                $decLabel = match($e['decision']) {
                                    'approve' => 'Schválit',
                                    'reject'  => 'Zamítnout',
                                    'abstain' => 'Zdržet se',
                                    default   => $e['decision'],
                                };
                                $decCls = 'ck-decision-' . $e['decision'];
                            ?>
                            <div class="ck-entry-card ck-entry-<?= htmlspecialchars($e['decision']) ?>">
                                <div class="ck-entry-header">
                                    <div style="display:flex;align-items:center;gap:0.5rem;">
                                        <img
                                            src="<?= $e['avatar'] ? 'https://cdn.discordapp.com/avatars/' . htmlspecialchars($e['discord_id']) . '/' . htmlspecialchars($e['avatar']) . '.png?size=32' : 'https://cdn.discordapp.com/embed/avatars/0.png' ?>"
                                            alt="" width="28" height="28" style="border-radius:50%;"
                                        >
                                        <strong><?= htmlspecialchars($e['username']) ?></strong>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:0.75rem;">
                                        <span class="<?= $decCls ?>"><?= $decLabel ?></span>
                                        <span class="ck-entry-time"><?= date('j. n. Y H:i', strtotime($e['created_at'])) ?></span>
                                        <?php if ($hasRoleVedeni && $isOpen): ?>
                                            <form method="POST" action="/admin/ck/<?= (int)$vote['id'] ?>/delete-entry" style="margin:0;">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                <input type="hidden" name="entry_id" value="<?= (int)$e['id'] ?>">
                                                <button type="submit" class="btn btn-sm ck-btn-delete-entry" onclick="return confirm('Opravdu smazat hlas uživatele <?= htmlspecialchars($e['username']) ?>?')" title="Smazat hlas">
                                                    <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (!empty($e['reason'])): ?>
                                    <div class="ck-entry-reason"><?= nl2br(htmlspecialchars($e['reason'])) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Comments section (visible to all) -->
        <div class="ck-comments-section">
            <h3>Poznámky (<?= count($comments) ?>)</h3>
            <?php if (!empty($comments)): ?>
                <div class="ck-comments-list">
                    <?php foreach ($comments as $c): ?>
                        <div class="ck-comment">
                            <div class="ck-comment-header">
                                <div style="display:flex;align-items:center;gap:0.5rem;">
                                    <img
                                        src="<?= $c['avatar'] ? 'https://cdn.discordapp.com/avatars/' . htmlspecialchars($c['discord_id']) . '/' . htmlspecialchars($c['avatar']) . '.png?size=32' : 'https://cdn.discordapp.com/embed/avatars/0.png' ?>"
                                        alt="" width="24" height="24" style="border-radius:50%;"
                                    >
                                    <strong><?= htmlspecialchars($c['username']) ?></strong>
                                </div>
                                <span class="ck-entry-time"><?= date('j. n. Y H:i', strtotime($c['created_at'])) ?></span>
                            </div>
                            <div class="ck-comment-body"><?= nl2br(htmlspecialchars($c['body'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-notice">Zatím žádné poznámky.</p>
            <?php endif; ?>

            <form method="POST" action="/admin/ck/<?= (int)$vote['id'] ?>/comment" class="ck-comment-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="form-group" style="margin-bottom:0.75rem;">
                    <label for="comment_body" class="form-label">Přidat poznámku</label>
                    <textarea id="comment_body" name="body" class="form-control" rows="2" required placeholder="Napiš poznámku…"></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Odeslat</button>
            </form>
        </div>

        <!-- Close button (only for open votes, vedení) -->
        <?php $canClose = $isOpen && ($hasRoleVedeni || (int)$vote['created_by'] === (int)($currentUserId ?? 0)); ?>
        <?php if ($canClose): ?>
            <div class="ck-close-section">
                <form method="POST" action="/admin/ck/<?= (int)$vote['id'] ?>/close" onsubmit="return confirm('Opravdu chceš uzavřít toto hlasování? Výsledek bude vyhodnocen automaticky.')">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <button type="submit" class="btn btn-danger">Uzavřít hlasování</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</section>
