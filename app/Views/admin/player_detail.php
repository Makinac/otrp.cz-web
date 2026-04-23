<?php
$avatarUrl = $user['avatar']
    ? 'https://cdn.discordapp.com/avatars/' . htmlspecialchars($user['discord_id']) . '/' . htmlspecialchars($user['avatar']) . '.png'
    : 'https://cdn.discordapp.com/embed/avatars/0.png';

$csrf  = \App\Core\Session::csrfToken();
$uid   = (int)$user['id'];

function appAttemptBadge(array $app): array {
    $s  = $app['status'];
    $iv = $app['interview_status'] ?? null;
    if ($s === 'approved' && $iv === 'passed') return ['cls' => 'status-active',   'label' => 'Aktivní'];
    if ($s === 'approved')                     return ['cls' => 'status-approved',  'label' => 'Čeká na pohovor'];
    if ($s === 'rejected')                     return ['cls' => 'status-rejected',  'label' => 'Zamítnuto'];
    if ($s === 'blocked' && $iv === 'failed')  return ['cls' => 'status-blocked',   'label' => 'Pohovor nesplněn'];
    if ($s === 'blocked')                      return ['cls' => 'status-blocked',   'label' => 'Zablokováno'];
    return ['cls' => 'status-pending', 'label' => 'Čeká na posouzení'];
}

function fmtExpiry(?string $exp): string {
    if ($exp === null) return '<span class="pd-perm">Permanentní</span>';
    return htmlspecialchars(date('j. n. Y H:i', strtotime($exp)));
}

function fmtWitnesses(?string $json): string {
    if (!$json) return '';
    $arr = json_decode($json, true);
    if (!is_array($arr) || empty($arr)) return '';
    return implode(', ', array_map('htmlspecialchars', $arr));
}
?>
<section class="section">
    <div class="container">
        <h1 class="page-title">Admin Panel</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>

        <a href="/admin/players" class="btn btn-secondary btn-sm" style="margin-bottom:1rem;">
            <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14" style="vertical-align:-2px;margin-right:4px;"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/></svg>
            Zpět na seznam hráčů
        </a>

        <?php if ($msg = \App\Core\Session::getFlash('success')): ?>
            <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if ($msg = \App\Core\Session::getFlash('error')): ?>
            <div class="alert alert-error"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <!-- Player header -->
        <div class="app-detail-header">
            <img src="<?= $avatarUrl ?>" alt="<?= htmlspecialchars($user['username']) ?>" class="app-detail-avatar" width="56" height="56">
            <div class="app-detail-meta">
                <h2 class="app-detail-name"><?= htmlspecialchars($user['username']) ?></h2>
                <div class="app-detail-sub">
                    <span>Discord ID: <?= htmlspecialchars($user['discord_id']) ?></span>
                    <span class="app-detail-dot">·</span>
                    <span>Registrace: <?= date('j. n. Y', strtotime($user['created_at'])) ?></span>
                    <span class="app-detail-dot">·</span>
                    <span><?= count($applications) ?> <?= count($applications) === 1 ? 'pokus' : (count($applications) <= 4 ? 'pokusy' : 'pokusů') ?></span>
                    <?php if (!empty($bans)): ?>
                        <span class="app-detail-dot">·</span>
                        <span class="status-badge status-blocked"><?= count($bans) ?> <?= count($bans) === 1 ? 'ban' : 'bany' ?></span>
                    <?php endif; ?>
                    <?php if (!empty($warns)): ?>
                        <span class="app-detail-dot">·</span>
                        <span class="status-badge status-rejected"><?= count($warns) ?> <?= count($warns) === 1 ? 'warn' : 'warny' ?></span>
                    <?php endif; ?>
                    <?php if (!empty($mutes)): ?>
                        <span class="app-detail-dot">·</span>
                        <span class="status-badge status-warning"><?= count($mutes) ?> <?= count($mutes) === 1 ? 'mute' : 'mutes' ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── Přístup na server ──────────────────────────────────────────── -->
        <h3 class="pd-section-title" style="margin-bottom:1rem;">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L7.586 10 5.293 7.707a1 1 0 010-1.414zM11 12a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
            Přístup na server
        </h3>
        <div class="sa-grid" style="margin-bottom:2rem;">

            <!-- Main (Allowlist) — read-only, derived -->
            <div class="sa-item <?= $hasAllowlist ? 'sa-on' : 'sa-off' ?>">
                <div class="sa-icon">
                    <?php if ($hasAllowlist): ?>
                        <svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <?php else: ?>
                        <svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    <?php endif; ?>
                </div>
                <div class="sa-info">
                    <span class="sa-name">Main</span>
                    <span class="sa-status"><?= $hasAllowlist ? 'Allowlist aktivní' : 'Bez allowlistu' ?></span>
                </div>
                <span class="sa-badge <?= $hasAllowlist ? 'sa-badge-on' : 'sa-badge-off' ?>"><?= $hasAllowlist ? 'ANO' : 'NE' ?></span>
                <?php if (!$hasAllowlist && !empty($adminPerms['admin.allowlist'])): ?>
                    <form method="POST" action="/admin/players/<?= $uid ?>/grant-allowlist">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                        <button type="submit" class="btn btn-sm btn-approve" onclick="return confirm('Opravdu chceš manuálně udělit allowlist?')">Udělit</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Dev -->
            <div class="sa-item <?= $user['access_dev'] ? 'sa-on' : 'sa-off' ?>">
                <div class="sa-icon">
                    <?php if ($user['access_dev']): ?>
                        <svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <?php else: ?>
                        <svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    <?php endif; ?>
                </div>
                <div class="sa-info">
                    <span class="sa-name">Dev</span>
                    <span class="sa-status"><?= $user['access_dev'] ? 'Přístup udělen' : 'Bez přístupu' ?></span>
                </div>
                <span class="sa-badge <?= $user['access_dev'] ? 'sa-badge-on' : 'sa-badge-off' ?>"><?= $user['access_dev'] ? 'ANO' : 'NE' ?></span>
                <?php if (!empty($canToggleAccess)): ?>
                    <form method="POST" action="/admin/players/<?= $uid ?>/access/dev">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                        <button type="submit" class="btn btn-sm <?= $user['access_dev'] ? 'btn-outline-danger' : 'btn-approve' ?>">
                            <?= $user['access_dev'] ? 'Odebrat' : 'Udělit' ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Maps -->
            <div class="sa-item <?= $user['access_maps'] ? 'sa-on' : 'sa-off' ?>">
                <div class="sa-icon">
                    <?php if ($user['access_maps']): ?>
                        <svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <?php else: ?>
                        <svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    <?php endif; ?>
                </div>
                <div class="sa-info">
                    <span class="sa-name">Mapy</span>
                    <span class="sa-status"><?= $user['access_maps'] ? 'Přístup udělen' : 'Bez přístupu' ?></span>
                </div>
                <span class="sa-badge <?= $user['access_maps'] ? 'sa-badge-on' : 'sa-badge-off' ?>"><?= $user['access_maps'] ? 'ANO' : 'NE' ?></span>
                <?php if (!empty($canToggleAccess)): ?>
                    <form method="POST" action="/admin/players/<?= $uid ?>/access/maps">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                        <button type="submit" class="btn btn-sm <?= $user['access_maps'] ? 'btn-outline-danger' : 'btn-approve' ?>">
                            <?= $user['access_maps'] ? 'Odebrat' : 'Udělit' ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>

        </div>

        <!-- ── QP (QuePoints) ─────────────────────────────────────────────── -->
        <div class="pd-section-header">
            <h3 class="pd-section-title">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                QP (QuePoints)
            </h3>
            <?php if (!empty($canManageQp)): ?>
                <button type="button" class="btn btn-primary btn-sm" onclick="openQpModal(<?= $uid ?>)">+ Přidat bonus</button>
            <?php endif; ?>
        </div>

        <?php
            $qpBd     = $qpBreakdown ?? ['total' => 0, 'roles_total' => 0, 'bonuses_total' => 0, 'role_hits' => [], 'all_bonuses' => [], 'discord_error' => true];
            $qpTotal  = (int)$qpBd['total'];
        ?>

        <?php if (!empty($qpBd['discord_error'])): ?>
            <div class="alert alert-error" style="margin-bottom:1rem;">
                Nepodařilo se načíst Discord role hráče. QP hodnota nemusí být aktuální.
            </div>
        <?php endif; ?>

        <div class="qp-summary" style="margin-bottom:1.25rem;">
            <span class="qp-total-badge"><?= number_format($qpTotal, 0, ',', ',') ?> QP</span>
            <span class="qp-breakdown-meta">
                Role: <strong><?= number_format((int)$qpBd['roles_total'], 0, ',', ',') ?></strong>
                &nbsp;+&nbsp;
                Bonusy: <strong><?= number_format((int)$qpBd['bonuses_total'], 0, ',', ',') ?></strong>
            </span>
        </div>

        <?php if (!empty($qpBd['role_hits'])): ?>
            <div class="qp-role-hits" style="margin-bottom:1.25rem;">
                <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:.5rem;">Přispívající role:</p>
                <div class="qp-role-pills">
                    <?php foreach ($qpBd['role_hits'] as $hit): ?>
                        <span class="qp-role-pill">
                            <?= htmlspecialchars($hit['role_name']) ?>
                            <strong>+<?= number_format((int)$hit['qp_value'], 0, ',', ',') ?></strong>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Bonus list -->
        <?php if (!empty($qpBd['all_bonuses'])): ?>
            <table class="data-table qp-bonus-table" style="margin-bottom:1.25rem;">
                <thead>
                    <tr>
                        <th>Částka</th>
                        <th>Důvod</th>
                        <th>Přidal</th>
                        <th>Přidáno</th>
                        <th>Platí do</th>
                        <?php if (!empty($canManageQp)): ?>
                            <th style="width:70px;"></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($qpBd['all_bonuses'] as $bonus): ?>
                        <?php
                            $expired = $bonus['expires_at'] && strtotime($bonus['expires_at']) < time();
                        ?>
                        <tr class="qp-bonus-row <?= $expired ? 'qp-bonus-expired' : '' ?>">
                            <td>
                                <strong style="color:<?= $bonus['amount'] >= 0 ? 'var(--green)' : 'var(--red)' ?>;"><?= $bonus['amount'] >= 0 ? '+' : '-' ?><?= number_format(abs((int)$bonus['amount']), 0, ',', ',') ?></strong></td>
                            <td><?= htmlspecialchars($bonus['reason']) ?></td>
                            <td><?= htmlspecialchars($bonus['created_by_name'] ?? '—') ?></td>
                            <td><?= date('j. n. Y H:i', strtotime($bonus['created_at'])) ?></td>
                            <td>
                                <?php if ($expired): ?>
                                    <span class="status-badge status-rejected" style="font-size:.7rem;"><?= date('j. n. Y H:i', strtotime($bonus['expires_at'])) ?> — Expirován</span>
                                <?php elseif ($bonus['expires_at']): ?>
                                    <?= date('j. n. Y H:i', strtotime($bonus['expires_at'])) ?>
                                <?php else: ?>
                                    <span class="pd-perm">Trvalý</span>
                                <?php endif; ?>
                            </td>
                            <?php if (!empty($canManageQp)): ?>
                                <td>
                                    <form method="POST" action="/admin/players/<?= $uid ?>/qp-bonus/<?= (int)$bonus['id'] ?>/delete" onsubmit="return confirm('Odebrat tento QP bonus?')" style="margin:0;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Odebrat</button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="empty-notice" style="margin-bottom:1.25rem;">Žádné QP bonusy.</p>
        <?php endif; ?>

        <!-- ── Char Slots ────────────────────────────────────────────────── -->
        <div class="pd-section-header" style="margin-top:2rem;">
            <h3 class="pd-section-title">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/></svg>
                Sloty pro postavy
            </h3>
            <?php if (!empty($canManageChars)): ?>
                <button type="button" class="btn btn-primary btn-sm" onclick="openCharModal(<?= $uid ?>)">+ Přidat bonus</button>
            <?php endif; ?>
        </div>

        <?php
            $chBd    = $charBreakdown ?? ['total' => 1, 'roles_total' => 0, 'bonuses_total' => 0, 'role_hits' => [], 'all_bonuses' => [], 'discord_error' => true];
            $chTotal = (int)$chBd['total'];
        ?>

        <?php if (!empty($chBd['discord_error'])): ?>
            <div class="alert alert-error" style="margin-bottom:1rem;">
                Nepodařilo se načíst Discord role hráče. Hodnota nemusí být aktuální.
            </div>
        <?php endif; ?>

        <div class="qp-summary" style="margin-bottom:1.25rem;">
            <span class="qp-total-badge"><?= $chTotal ?> <?= $chTotal === 1 ? 'slot' : ($chTotal <= 4 ? 'sloty' : 'slotů') ?></span>
            <span class="qp-breakdown-meta">
                Role: <strong>+<?= (int)$chBd['roles_total'] ?></strong>
                &nbsp;+&nbsp;
                Bonusy: <strong>+<?= (int)$chBd['bonuses_total'] ?></strong>
                &nbsp;+&nbsp;
                Základní: <strong>1</strong>
            </span>
        </div>

        <?php if (!empty($chBd['role_hits'])): ?>
            <div class="qp-role-hits" style="margin-bottom:1.25rem;">
                <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:.5rem;">Přispívající role:</p>
                <div class="qp-role-pills">
                    <?php foreach ($chBd['role_hits'] as $hit): ?>
                        <span class="qp-role-pill">
                            <?= htmlspecialchars($hit['role_name']) ?>
                            <strong>+<?= (int)$hit['char_value'] ?></strong>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Char Bonus list -->
        <?php if (!empty($chBd['all_bonuses'])): ?>
            <table class="data-table qp-bonus-table" style="margin-bottom:1.25rem;">
                <thead>
                    <tr>
                        <th>Částka</th>
                        <th>Důvod</th>
                        <th>Přidal</th>
                        <th>Přidáno</th>
                        <th>Platí do</th>
                        <?php if (!empty($canManageChars)): ?>
                            <th style="width:70px;"></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($chBd['all_bonuses'] as $bonus): ?>
                        <?php
                            $expired = $bonus['expires_at'] && strtotime($bonus['expires_at']) < time();
                        ?>
                        <tr class="qp-bonus-row <?= $expired ? 'qp-bonus-expired' : '' ?>">
                            <td>
                                <strong style="color:<?= $bonus['amount'] >= 0 ? 'var(--green)' : 'var(--red)' ?>;"><?= $bonus['amount'] >= 0 ? '+' : '-' ?><?= abs((int)$bonus['amount']) ?></strong></td>
                            <td><?= htmlspecialchars($bonus['reason']) ?></td>
                            <td><?= htmlspecialchars($bonus['created_by_name'] ?? '—') ?></td>
                            <td><?= date('j. n. Y H:i', strtotime($bonus['created_at'])) ?></td>
                            <td>
                                <?php if ($expired): ?>
                                    <span class="status-badge status-rejected" style="font-size:.7rem;"><?= date('j. n. Y H:i', strtotime($bonus['expires_at'])) ?> — Expirován</span>
                                <?php elseif ($bonus['expires_at']): ?>
                                    <?= date('j. n. Y H:i', strtotime($bonus['expires_at'])) ?>
                                <?php else: ?>
                                    <span class="pd-perm">Trvalý</span>
                                <?php endif; ?>
                            </td>
                            <?php if (!empty($canManageChars)): ?>
                                <td>
                                    <form method="POST" action="/admin/players/<?= $uid ?>/char-bonus/<?= (int)$bonus['id'] ?>/delete" onsubmit="return confirm('Odebrat tento char bonus?')" style="margin:0;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Odebrat</button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="empty-notice" style="margin-bottom:1.25rem;">Žádné char bonusy.</p>
        <?php endif; ?>

        <!-- ── FiveM Identifikátory ───────────────────────────────────────── -->
        <?php if (!empty($identifiers)): ?>
        <div class="pd-identifiers" style="margin-bottom:2rem;">
            <table class="pd-id-table">
                <thead>
                    <tr>
                        <th>Typ</th>
                        <th>Hodnota</th>
                        <th>Poprvé</th>
                        <th>Naposledy</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($identifiers as $type => $entries): ?>
                        <?php $multipleValues = count($entries) > 1; ?>
                        <?php foreach ($entries as $i => $entry): ?>
                            <tr class="<?= $multipleValues ? 'pd-id-changed' : '' ?>">
                                <td><strong><?= htmlspecialchars($type) ?></strong></td>
                                <td><code style="font-size:0.85rem;word-break:break-all;"><?= htmlspecialchars($entry['identifier_value']) ?></code></td>
                                <td><?= date('j. n. Y H:i', strtotime($entry['first_seen_at'])) ?></td>
                                <td><?= date('j. n. Y H:i', strtotime($entry['last_seen_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- ── Security Log ───────────────────────────────────────────────── -->
        <?php if (!empty($canViewSecurity) && !empty($securityLogs)): ?>
        <div class="pd-section-header" style="margin-top:2rem;">
            <h3 class="pd-section-title">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                Security záznamy
                <span class="pd-count"><?= count($securityLogs) ?></span>
            </h3>
        </div>
        <div class="pd-action-list" style="margin-bottom:2rem;">
            <?php foreach ($securityLogs as $sl): ?>
                <?php
                    $slCls = 'pd-action-item' . ($sl['severity'] === 'critical' ? ' pd-ban' : ($sl['severity'] === 'warning' ? ' pd-warn' : ''));
                    if ($sl['resolved']) $slCls .= ' pd-revoked';
                    $slBadge = match($sl['severity']) {
                        'critical' => ['cls' => 'status-blocked',  'label' => 'Kritické'],
                        'warning'  => ['cls' => 'status-warning',  'label' => 'Varování'],
                        default    => ['cls' => 'status-pending',  'label' => 'Info'],
                    };
                ?>
                <div class="<?= $slCls ?>">
                    <div class="pd-action-info">
                        <span class="pd-action-reason"><?= htmlspecialchars($sl['description']) ?></span>
                        <span class="pd-action-meta">
                            <?= match($sl['event_type']) {
                                'new_identifier'      => 'Nový identifikátor',
                                'identifier_conflict' => 'Změna identifikátoru',
                                'multi_account'       => 'Sdílený účet',
                                default               => $sl['event_type'],
                            } ?>
                            &nbsp;·&nbsp;
                            <?= date('j. n. Y H:i', strtotime($sl['created_at'])) ?>
                            <?php if ($sl['resolved']): ?>
                                &nbsp;·&nbsp;
                                <span class="pd-revoked-info">Vyřešil: <?= htmlspecialchars($sl['resolver_name'] ?? 'Neznámý') ?> &middot; <?= date('j. n. Y H:i', strtotime($sl['resolved_at'])) ?></span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <span class="status-badge <?= $slBadge['cls'] ?>"><?= $slBadge['label'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- ── Allowlist attempts ──────────────────────────────────────────── -->
        <h3 class="pd-section-title">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
            Žádosti na allowlist
        </h3>
        <?php if (empty($applications)): ?>
            <p class="empty-notice" style="margin-bottom:2rem;">Hráč zatím nepodal žádnou žádost.</p>
        <?php else: ?>
            <div class="app-list" style="margin-bottom:2rem;">
                <?php foreach ($applications as $app): ?>
                    <?php $badge = appAttemptBadge($app); ?>
                    <?php if (!empty($adminPerms['admin.allowlist'])): ?>
                    <a href="/admin/<?= (int)$app['id'] ?>" class="app-row">
                        <div class="app-row-info">
                            <span class="app-row-name">Pokus #<?= (int)$app['attempt_number'] ?></span>
                            <span class="app-row-meta"><?= date('j. n. Y H:i', strtotime($app['submitted_at'])) ?><?= $app['error_count'] !== null ? ' · ' . (int)$app['error_count'] . ' chyb' : '' ?></span>
                        </div>
                        <span class="status-badge <?= $badge['cls'] ?>"><?= $badge['label'] ?></span>
                        <span class="app-row-arrow">›</span>
                    </a>
                    <?php else: ?>
                    <div class="app-row" style="cursor:default;">
                        <div class="app-row-info">
                            <span class="app-row-name">Pokus #<?= (int)$app['attempt_number'] ?></span>
                            <span class="app-row-meta"><?= date('j. n. Y H:i', strtotime($app['submitted_at'])) ?><?= $app['error_count'] !== null ? ' · ' . (int)$app['error_count'] . ' chyb' : '' ?></span>
                        </div>
                        <span class="status-badge <?= $badge['cls'] ?>"><?= $badge['label'] ?></span>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- ── Historie odvolání ─────────────────────────────────────────── -->
        <?php if (!empty($canViewAppeals)): ?>
        <div class="pd-section-header" style="margin-top:2rem;">
            <h3 class="pd-section-title">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                Historie odvolání
                <?php if (!empty($appeals)): ?><span class="pd-count"><?= count($appeals) ?></span><?php endif; ?>
            </h3>
        </div>

        <?php if (empty($appeals)): ?>
            <p class="empty-notice pd-empty">Žádná odvolání.</p>
        <?php else: ?>
            <div class="pd-action-list" style="margin-bottom:2rem;">
                <?php foreach ($appeals as $ap): ?>
                    <?php
                        [$aStatusCls, $aStatusLabel] = match($ap['status']) {
                            'approved' => ['status-active',   'Schváleno'],
                            'rejected' => ['status-rejected', 'Zamítnuto'],
                            default    => ['status-pending',  'Čeká'],
                        };
                        $aTypeCls = $ap['type'] === 'blacklist' ? 'pd-ban' : 'pd-warn';
                    ?>
                    <div class="pd-action-item <?= $aTypeCls ?>">
                        <div class="pd-action-info">
                            <span class="pd-action-reason"><?= nl2br(htmlspecialchars($ap['reason'])) ?></span>
                            <span class="pd-action-meta">
                                Typ: <?= match($ap['type']) { 'ban' => 'Ban', 'warn' => 'Warn', 'blacklist' => 'Denylist', default => 'Nepodařený Allowlist' } ?>
                                &nbsp;·&nbsp;
                                <?= date('j. n. Y H:i', strtotime($ap['created_at'])) ?>
                                <?php if ($ap['reviewed_at']): ?>
                                    &nbsp;·&nbsp;
                                    Vyřídil: <?= $ap['reviewer_name'] ? htmlspecialchars($ap['reviewer_name']) : 'Neznámý' ?>
                                    · <?= date('j. n. Y H:i', strtotime($ap['reviewed_at'])) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <span class="status-badge <?= $aStatusCls ?>"><?= $aStatusLabel ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- ── Bans ───────────────────────────────────────────────────────── -->
        <div class="pd-section-header">
            <h3 class="pd-section-title">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524L13.477 14.89zm1.414-1.414L6.524 5.11A6 6 0 0114.89 13.477zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd"/></svg>
                Bany
                <?php if (!empty($bans)): ?><span class="pd-count"><?= count($bans) ?></span><?php endif; ?>
            </h3>
            <?php if (!empty($canManagePunishments)): ?>
            <button type="button" class="btn btn-danger btn-sm" onclick="openActionModal('ban', <?= $uid ?>)">+ Udělit ban</button>
            <?php endif; ?>
        </div>

        <?php if (empty($bans)): ?>
            <p class="empty-notice pd-empty">Žádné bany.</p>
        <?php else: ?>
            <div class="pd-action-list" style="margin-bottom:1.5rem;">
                <?php foreach ($bans as $ban): ?>
                    <?php
                        $witnesses = fmtWitnesses($ban['witnesses_json'] ?? null);
                        $revoked   = !empty($ban['revoked']);
                        $expired   = !$revoked && ($ban['expires_at'] !== null) && (strtotime($ban['expires_at']) < time());
                        $itemCls   = 'pd-action-item pd-ban' . ($revoked ? ' pd-revoked' : ($expired ? ' pd-expired' : ''));
                    ?>
                    <div class="<?= $itemCls ?>">
                        <div class="pd-action-info">
                            <span class="pd-action-reason"><?= nl2br(htmlspecialchars($ban['reason'])) ?></span>
                            <span class="pd-action-meta">
                                Platnost: <?= fmtExpiry($ban['expires_at'] ?? null) ?>
                                &nbsp;&middot;&nbsp;
                                Udělil: <?= $ban['issuer_name'] ? htmlspecialchars($ban['issuer_name']) : 'Neznámý' ?>
                                &middot; <?= date('j. n. Y H:i', strtotime($ban['issued_at'])) ?>
                                <?php if ($witnesses !== ''): ?>
                                    <br>Přítomní: <?= $witnesses ?>
                                <?php endif; ?>
                                <?php if ($revoked): ?>
                                    <br><span class="pd-revoked-info">Zrušil: <?= htmlspecialchars($ban['revoker_name'] ?? 'Neznámý') ?> &middot; <?= date('j. n. Y H:i', strtotime($ban['revoked_at'])) ?> &mdash; <?= htmlspecialchars($ban['revoked_reason']) ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php if (!$revoked && !empty($canManagePunishments)): ?>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="openRevokeModal('ban', <?= (int)$ban['id'] ?>, <?= $uid ?>)">
                            Zrušit
                        </button>
                        <?php elseif ($revoked): ?>
                        <span class="status-badge status-blocked">Zrušeno</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- ── Warns ──────────────────────────────────────────────────────── -->
        <div class="pd-section-header" style="margin-top:2rem;">
            <h3 class="pd-section-title">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                Warny
                <?php if (!empty($warns)): ?><span class="pd-count"><?= count($warns) ?></span><?php endif; ?>
            </h3>
            <?php if (!empty($canManagePunishments)): ?>
            <button type="button" class="btn btn-warning btn-sm" onclick="openActionModal('warn', <?= $uid ?>)">+ Udělit warn</button>
            <?php endif; ?>
        </div>

        <?php if (empty($warns)): ?>
            <p class="empty-notice pd-empty">Žádné warny.</p>
        <?php else: ?>
            <div class="pd-action-list" style="margin-bottom:1.5rem;">
                <?php foreach ($warns as $warn): ?>
                    <?php
                        $witnesses = fmtWitnesses($warn['witnesses_json'] ?? null);
                        $revoked   = !empty($warn['revoked']);
                        $expired   = !$revoked && ($warn['expires_at'] !== null) && (strtotime($warn['expires_at']) < time());
                        $itemCls   = 'pd-action-item pd-warn' . ($revoked ? ' pd-revoked' : ($expired ? ' pd-expired' : ''));
                    ?>
                    <div class="<?= $itemCls ?>">
                        <div class="pd-action-info">
                            <span class="pd-action-reason"><?= nl2br(htmlspecialchars($warn['reason'])) ?></span>
                            <span class="pd-action-meta">
                                Platnost: <?= fmtExpiry($warn['expires_at'] ?? null) ?>
                                &nbsp;&middot;&nbsp;
                                Udělil: <?= $warn['issuer_name'] ? htmlspecialchars($warn['issuer_name']) : 'Neznámý' ?>
                                &middot; <?= date('j. n. Y H:i', strtotime($warn['issued_at'])) ?>
                                <?php if ($witnesses !== ''): ?>
                                    <br>Přítomní: <?= $witnesses ?>
                                <?php endif; ?>
                                <?php if ($revoked): ?>
                                    <br><span class="pd-revoked-info">Zrušil: <?= htmlspecialchars($warn['revoker_name'] ?? 'Neznámý') ?> &middot; <?= date('j. n. Y H:i', strtotime($warn['revoked_at'])) ?> &mdash; <?= htmlspecialchars($warn['revoked_reason']) ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php if (!$revoked && !empty($canManagePunishments)): ?>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="openRevokeModal('warn', <?= (int)$warn['id'] ?>, <?= $uid ?>)">
                            Zrušit
                        </button>
                        <?php elseif ($revoked): ?>
                        <span class="status-badge status-blocked">Zrušeno</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- ── Mutes ──────────────────────────────────────────────────────── -->
        <div class="pd-section-header" style="margin-top:2rem;">
            <h3 class="pd-section-title">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M4 7a2 2 0 012-2h3l3-3v16l-3-3H6a2 2 0 01-2-2V7z"/><path d="M14.5 7.5a1 1 0 011.414 0A4.5 4.5 0 0117.25 10a4.5 4.5 0 01-1.336 2.5 1 1 0 11-1.414-1.414A2.5 2.5 0 0015.25 10c0-.69-.28-1.315-.75-1.786a1 1 0 010-1.414z"/></svg>
                Mute
                <?php if (!empty($mutes)): ?><span class="pd-count"><?= count($mutes) ?></span><?php endif; ?>
            </h3>
            <?php if (!empty($canManagePunishments)): ?>
            <button type="button" class="btn btn-sm" style="background:#5865F2;color:#fff;" onclick="openActionModal('mute', <?= $uid ?>)">+ Udělit mute</button>
            <?php endif; ?>
        </div>

        <?php if (empty($mutes)): ?>
            <p class="empty-notice pd-empty">Žádné mutes.</p>
        <?php else: ?>
            <div class="pd-action-list" style="margin-bottom:1.5rem;">
                <?php foreach ($mutes as $mute): ?>
                    <?php
                        $revoked = !empty($mute['revoked']);
                        $expired = !$revoked && ($mute['expires_at'] !== null) && (strtotime($mute['expires_at']) < time());
                        $itemCls = 'pd-action-item pd-warn' . ($revoked ? ' pd-revoked' : ($expired ? ' pd-expired' : ''));
                        $issuedVia = ($mute['issued_via'] ?? 'web') === 'discord' ? 'Discord' : 'Web';
                    ?>
                    <div class="<?= $itemCls ?>">
                        <div class="pd-action-info">
                            <span class="pd-action-reason"><?= nl2br(htmlspecialchars($mute['reason'])) ?></span>
                            <span class="pd-action-meta">
                                Platnost: <?= fmtExpiry($mute['expires_at'] ?? null) ?>
                                &nbsp;&middot;&nbsp;
                                Udělil: <?= $mute['issuer_name'] ? htmlspecialchars($mute['issuer_name']) : 'Neznámý' ?>
                                &middot; <?= date('j. n. Y H:i', strtotime($mute['issued_at'])) ?>
                                &middot; Zdroj: <?= htmlspecialchars($issuedVia) ?>
                                <?php if ($revoked): ?>
                                    <br><span class="pd-revoked-info">Zrušil: <?= htmlspecialchars($mute['revoker_name'] ?? 'Neznámý') ?> &middot; <?= date('j. n. Y H:i', strtotime($mute['revoked_at'])) ?> &mdash; <?= htmlspecialchars($mute['revoked_reason']) ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php if (!$revoked && !empty($canManagePunishments)): ?>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="openRevokeModal('mute', <?= (int)$mute['id'] ?>, <?= $uid ?>)">
                            Zrušit
                        </button>
                        <?php elseif ($revoked): ?>
                        <span class="status-badge status-blocked">Zrušeno</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- ── Poznámky adminů ────────────────────────────────────────────── -->
        <div class="pd-section-header" style="margin-top:2rem;">
            <h3 class="pd-section-title">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M18 13V5a2 2 0 00-2-2H4a2 2 0 00-2 2v8a2 2 0 002 2h3l3 3 3-3h3a2 2 0 002-2zM5 7a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1zm1 3a1 1 0 000 2h3a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                Poznámky adminů
                <?php if (!empty($notes)): ?><span class="pd-count"><?= count($notes) ?></span><?php endif; ?>
            </h3>
            <button type="button" class="btn btn-sm btn-approve" onclick="openNoteModal(<?= $uid ?>)">+ Přidat poznámku</button>
        </div>

        <?php if (empty($notes)): ?>
            <p class="empty-notice pd-empty">Zatím žádné poznámky.</p>
        <?php else: ?>
            <div class="pn-list" style="margin-top:0.75rem;margin-bottom:1.5rem;">
                <?php foreach ($notes as $note): ?>
                    <?php
                        $noteAuthorAvatar = $note['author_avatar']
                            ? 'https://cdn.discordapp.com/avatars/' . htmlspecialchars($note['author_discord_id']) . '/' . htmlspecialchars($note['author_avatar']) . '.png'
                            : 'https://cdn.discordapp.com/embed/avatars/0.png';
                        $canDeleteNote = (int)$note['author_id'] === (int)\App\Auth\Permission::userId() || \App\Auth\Permission::isVedeni();
                    ?>
                    <div class="pn-item">
                        <img src="<?= $noteAuthorAvatar ?>" alt="" class="pn-avatar" width="32" height="32">
                        <div class="pn-body">
                            <div class="pn-meta">
                                <span class="pn-author"><?= htmlspecialchars($note['author_name'] ?? 'Neznámý') ?></span>
                                <span class="pn-date"><?= date('j. n. Y H:i', strtotime($note['created_at'])) ?></span>
                            </div>
                            <div class="pn-text"><?= nl2br(htmlspecialchars($note['note'])) ?></div>
                        </div>
                        <?php if ($canDeleteNote): ?>
                            <form method="POST" action="/admin/players/<?= $uid ?>/note/<?= (int)$note['id'] ?>/delete" class="pn-del-form">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                <button type="submit" class="pn-del-btn" title="Smazat poznámku"
                                    onclick="return confirm('Opravdu smazat tuto poznámku?')">
                                    <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div style="margin-top:2.5rem;">
            <a href="/admin/players" class="btn btn-ghost btn-sm">← Zpět na seznam hráčů</a>
        </div>
    </div>
</section>

<?php if (!empty($canManagePunishments)): ?>
<!-- ── Action Modal (ban / warn / mute) ───────────────────────────────────── -->
<div id="actionModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="actionModalTitle" hidden>
    <div class="modal-box modal-box-lg">
        <h2 id="actionModalTitle" class="modal-title">Udělit ban</h2>

        <form method="POST" id="actionModalForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

            <!-- Reason -->
            <div class="form-group">
                <label class="form-label" for="amReason">Důvod <span class="req">*</span></label>
                <textarea id="amReason" name="reason" class="form-control" rows="3" required maxlength="2000" placeholder="Popiš důvod…"></textarea>
            </div>

            <!-- Permanent / expiry -->
            <div class="form-group">
                <label class="am-checkbox-label">
                    <input type="checkbox" name="permanent" id="amPermanent" value="1" checked>
                    <span>Permanentní</span>
                </label>
            </div>
            <div class="form-group" id="amExpiryGroup" style="display:none;">
                <label class="form-label" for="amExpires">Platnost do</label>
                <input type="datetime-local" id="amExpires" name="expires_at" class="form-control">
            </div>

            <!-- Witnesses -->
            <?php if (!empty($staffUsers)): ?>
                <div class="form-group" id="amWitnessGroup">
                    <label class="form-label">Kdo byl přítomen</label>
                    <div class="staff-checkbox-grid">
                        <?php foreach ($staffUsers as $su): ?>
                            <label class="staff-checkbox">
                                <input type="checkbox" name="witnesses[]" value="<?= htmlspecialchars($su['username']) ?>">
                                <?= htmlspecialchars($su['username']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" id="amConfirm" class="btn btn-danger">Udělit ban</button>
                <button type="button" id="amCancel" class="btn btn-ghost">Zrušit</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var modal     = document.getElementById('actionModal');
    var form      = document.getElementById('actionModalForm');
    var titleEl   = document.getElementById('actionModalTitle');
    var confirmEl = document.getElementById('amConfirm');
    var cancelEl  = document.getElementById('amCancel');
    var permanent = document.getElementById('amPermanent');
    var expiryGrp = document.getElementById('amExpiryGroup');
    var expiryIn  = document.getElementById('amExpires');

    window.openActionModal = function (type, userId) {
        var isBan  = type === 'ban';
        var isMute = type === 'mute';
        var label  = isBan ? 'ban' : isMute ? 'mute' : 'warn';
        var titles = { ban: 'Udělit ban', warn: 'Udělit warn', mute: 'Udělit mute' };
        var btnCls = { ban: 'btn btn-danger', warn: 'btn btn-warning', mute: 'btn' };

        titleEl.textContent   = titles[type] || titles.warn;
        confirmEl.textContent = titles[type] || titles.warn;
        confirmEl.className   = btnCls[type] || btnCls.warn;
        if (isMute) confirmEl.style.cssText = 'background:#5865F2;color:#fff;border:none;';
        else confirmEl.style.cssText = '';

        // Hide witnesses for mute
        var witnessGrp = document.getElementById('amWitnessGroup');
        if (witnessGrp) witnessGrp.style.display = isMute ? 'none' : '';

        form.action = '/admin/players/' + userId + '/' + label;

        // Reset form
        form.reset();
        permanent.checked   = true;
        expiryGrp.style.display = 'none';
        expiryIn.required   = false;

        // Uncheck all witnesses
        form.querySelectorAll('input[name="witnesses[]"]').forEach(function (cb) { cb.checked = false; });

        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        document.getElementById('amReason').focus();
    };

    // Toggle expiry field
    permanent.addEventListener('change', function () {
        var show = !this.checked;
        expiryGrp.style.display = show ? '' : 'none';
        expiryIn.required       = show;
        if (!show) expiryIn.value = '';
    });

    // Close handlers
    cancelEl.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) closeModal();
    });

    function closeModal() {
        modal.hidden = true;
        document.body.style.overflow = '';
    }
}());
</script>

<!-- ── Revoke Modal (ban / warn) ─────────────────────────────────────────── -->
<div id="revokeModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="revokeModalTitle" hidden>
    <div class="modal-box">
        <h2 id="revokeModalTitle" class="modal-title">Zrušit ban</h2>
        <form method="POST" id="revokeForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <div class="form-group">
                <label class="form-label" for="revokeReason">Důvod zrušení <span class="req">*</span></label>
                <textarea id="revokeReason" name="revoke_reason" class="form-control" rows="3" required maxlength="1000" placeholder="Proč se ban/warn ruší…"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-reject" id="revokeConfirm">Potvrdit zrušení</button>
                <button type="button" id="revokeCancel" class="btn btn-ghost">Zrušit</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var modal    = document.getElementById('revokeModal');
    var form     = document.getElementById('revokeForm');
    var titleEl  = document.getElementById('revokeModalTitle');
    var textarea = document.getElementById('revokeReason');
    var cancel   = document.getElementById('revokeCancel');

    window.openRevokeModal = function (type, actionId, userId) {
        var label = 'warn';
        if (type === 'ban') label = 'ban';
        if (type === 'mute') label = 'mute';
        titleEl.textContent = 'Zrušit ' + label;
        form.action = '/admin/players/' + userId + '/' + label + '/' + actionId + '/delete';
        textarea.value = '';
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        textarea.focus();
    };

    cancel.addEventListener('click', closeRevoke);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeRevoke(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) closeRevoke(); });

    function closeRevoke() {
        modal.hidden = true;
        document.body.style.overflow = '';
    }
}());
</script>

<!-- ── Note Modal ─────────────────────────────────────────────────────────── -->
<div id="noteModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="noteModalTitle" hidden>
    <div class="modal-box">
        <h2 id="noteModalTitle" class="modal-title">Přidat poznámku</h2>
        <form method="POST" id="noteModalForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <div class="form-group">
                <label class="form-label" for="noteText">Poznámka <span class="req">*</span></label>
                <textarea id="noteText" name="note" class="form-control" rows="4" required maxlength="2000" placeholder="Napiš poznámku k tomuto hráči…"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-approve">Přidat poznámku</button>
                <button type="button" id="noteCancel" class="btn btn-ghost">Zrušit</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var modal    = document.getElementById('noteModal');
    var form     = document.getElementById('noteModalForm');
    var textarea = document.getElementById('noteText');
    var cancel   = document.getElementById('noteCancel');

    window.openNoteModal = function (userId) {
        form.action    = '/admin/players/' + userId + '/note';
        textarea.value = '';
        modal.hidden   = false;
        document.body.style.overflow = 'hidden';
        textarea.focus();
    };

    cancel.addEventListener('click', closeNote);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeNote(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) closeNote(); });

    function closeNote() {
        modal.hidden = true;
        document.body.style.overflow = '';
    }
}());
</script>

<!-- ── QP Bonus Modal ─────────────────────────────────────────────────────── -->
<?php if (!empty($canManageQp)): ?>
<div id="qpModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="qpModalTitle" hidden>
    <div class="modal-box">
        <h2 id="qpModalTitle" class="modal-title">Přidat QP bonus</h2>
        <form method="POST" id="qpModalForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <div class="form-group">
                <label class="form-label" for="qpModalAmount">Částka <span class="req">*</span></label>
                <input type="number" id="qpModalAmount" name="amount" class="form-control" placeholder="např. 50 nebo -10" min="-100000" max="100000" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="qpModalReason">Důvod <span class="req">*</span></label>
                <input type="text" id="qpModalReason" name="reason" class="form-control" placeholder="Důvod bonusu…" required maxlength="255">
            </div>
            <div class="form-group">
                <label class="form-label" for="qpModalExpires">Platí do <span style="color:var(--text-muted);font-weight:400;">(volitelné)</span></label>
                <input type="datetime-local" id="qpModalExpires" name="expires_at" class="form-control">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Přidat bonus</button>
                <button type="button" id="qpModalCancel" class="btn btn-ghost">Zrušit</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var modal  = document.getElementById('qpModal');
    var form   = document.getElementById('qpModalForm');
    var cancel = document.getElementById('qpModalCancel');

    window.openQpModal = function (userId) {
        form.action = '/admin/players/' + userId + '/qp-bonus';
        form.reset();
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        document.getElementById('qpModalAmount').focus();
    };

    cancel.addEventListener('click', closeQp);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeQp(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) closeQp(); });

    function closeQp() {
        modal.hidden = true;
        document.body.style.overflow = '';
    }
}());
</script>
<?php endif; ?>

<!-- ── Char Bonus Modal ───────────────────────────────────────────────────── -->
<?php if (!empty($canManageChars)): ?>
<div id="charModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="charModalTitle" hidden>
    <div class="modal-box">
        <h2 id="charModalTitle" class="modal-title">Přidat char slot bonus</h2>
        <form method="POST" id="charModalForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <div class="form-group">
                <label class="form-label" for="charModalAmount">Počet slotů <span class="req">*</span></label>
                <input type="number" id="charModalAmount" name="amount" class="form-control" placeholder="např. 1 nebo -1" min="-15" max="15" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="charModalReason">Důvod <span class="req">*</span></label>
                <input type="text" id="charModalReason" name="reason" class="form-control" placeholder="Důvod bonusu…" required maxlength="255">
            </div>
            <div class="form-group">
                <label class="form-label" for="charModalExpires">Platí do <span style="color:var(--text-muted);font-weight:400;">(volitelné)</span></label>
                <input type="datetime-local" id="charModalExpires" name="expires_at" class="form-control">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Přidat bonus</button>
                <button type="button" id="charModalCancel" class="btn btn-ghost">Zrušit</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var modal  = document.getElementById('charModal');
    var form   = document.getElementById('charModalForm');
    var cancel = document.getElementById('charModalCancel');

    window.openCharModal = function (userId) {
        form.action = '/admin/players/' + userId + '/char-bonus';
        form.reset();
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        document.getElementById('charModalAmount').focus();
    };

    cancel.addEventListener('click', closeChar);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeChar(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) closeChar(); });

    function closeChar() {
        modal.hidden = true;
        document.body.style.overflow = '';
    }
}());
</script>
<?php endif; ?>

<?php endif; ?>
