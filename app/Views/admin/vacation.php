<?php
$dayHeaders = ['Po', 'Út', 'St', 'Čt', 'Pá', 'So', 'Ne'];
$todayStr = date('Y-m-d');
$currentMonth = $monthStart ?? '';

// Color palette (8 rotating colors)
$vacColorPalette = [
    'rgb(124,58,237)', 'rgb(59,130,246)', 'rgb(16,185,129)', 'rgb(245,158,11)',
    'rgb(239,68,68)', 'rgb(236,72,153)', 'rgb(6,182,212)', 'rgb(132,204,22)',
];
$userColors = [];
foreach ($users as $idx => $user) {
    $userColors[(int)$user['user_id']] = $idx % 8;
}

// Who's on vacation today?
$todayVacUsers = [];
foreach ($users as $user) {
    $uid = (int)$user['user_id'];
    if (isset($vacByUserDate[$uid][$todayStr])) {
        $todayVacUsers[] = [
            'user' => $user,
            'vac'  => $vacByUserDate[$uid][$todayStr],
        ];
    }
}

// Avatar URL helper
function vacAvatar(array $user): string {
    if (!empty($user['avatar']) && !empty($user['discord_id'])) {
        return 'https://cdn.discordapp.com/avatars/' . htmlspecialchars($user['discord_id']) . '/' . htmlspecialchars($user['avatar']) . '.png?size=64';
    }
    return 'https://cdn.discordapp.com/embed/avatars/0.png';
}
?>
<section class="section">
    <div class="container">
        <h1 class="page-title">Admin Panel</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>

        <?php require __DIR__ . '/_panel_nav.php'; ?>

        <!-- ── Page header ─────────────────────────────────────────────── -->
        <div class="vac-page-header">
            <div class="vac-page-header-left">
                <div class="vac-page-supertitle">Přehled dovolených</div>
                <h2 class="vac-page-monthtitle"><?= htmlspecialchars($monthLabel) ?></h2>
            </div>
            <div class="vac-page-header-right">
                <nav class="vac-month-nav" aria-label="Navigace měsíce">
                    <a href="/admin/vacation?month=<?= urlencode($prevMonth) ?>" class="vac-nav-btn" title="Předchozí měsíc">
                        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    </a>
                    <a href="/admin/vacation?month=<?= urlencode($nextMonth) ?>" class="vac-nav-btn" title="Následující měsíc">
                        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                    </a>
                </nav>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('vac-add-modal').style.display='flex'">
                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;margin-right:0.4rem;vertical-align:-2px"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                    Přidat dovolenou
                </button>
            </div>
        </div>

        <?php if (!empty($todayVacUsers)): ?>
        <!-- ── Currently away ──────────────────────────────────────────── -->
        <div class="vac-away-panel">
            <div class="vac-away-panel-header">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z"/></svg>
                Právě na dovolené
                <span class="vac-away-count"><?= count($todayVacUsers) ?></span>
            </div>
            <div class="vac-away-cards">
                <?php foreach ($todayVacUsers as $tv):
                    $tvUser  = $tv['user'];
                    $tvVac   = $tv['vac'];
                    $tvFrom  = new DateTimeImmutable($tvVac['date_from']);
                    $tvTo    = new DateTimeImmutable($tvVac['date_to']);
                    $tvDays  = $tvFrom->diff($tvTo)->days + 1;
                    $tvColor = $vacColorPalette[$userColors[(int)$tvUser['user_id']] ?? 0];
                    $tvToday = new DateTimeImmutable($todayStr);
                    $tvLeft  = $tvToday <= $tvTo ? $tvToday->diff($tvTo)->days + 1 : 0;
                ?>
                <div class="vac-away-card" style="--vc:<?= $tvColor ?>">
                    <div class="vac-away-card-stripe"></div>
                    <img src="<?= vacAvatar($tvUser) ?>" alt="" class="vac-away-card-avatar" loading="lazy">
                    <div class="vac-away-card-body">
                        <div class="vac-away-card-name"><?= htmlspecialchars($tvUser['username']) ?></div>
                        <div class="vac-away-card-dates">
                            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                            <?= $tvFrom->format('j. n.') ?> – <?= $tvTo->format('j. n. Y') ?>
                        </div>
                        <?php if (!empty($tvVac['note'])): ?>
                        <div class="vac-away-card-note"><?= htmlspecialchars($tvVac['note']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="vac-away-card-badge">
                        <span class="vac-away-badge-num"><?= $tvLeft ?></span>
                        <span class="vac-away-badge-label">dní</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Calendar ───────────────────────────────────────────────── -->
        <div class="vac-calendar-wrap">
            <table class="vac-table">
                <thead>
                    <!-- Week-group header row -->
                    <tr class="vac-wg-row">
                        <th class="vac-th-user vac-th-corner"></th>
                        <?php
                        $wgBuckets = [];
                        foreach ($dates as $dateStr) {
                            $dt = new DateTimeImmutable($dateStr);
                            $key = $dt->format('o-W');
                            if (!isset($wgBuckets[$key])) {
                                $wgBuckets[$key] = ['span' => 0, 'n' => (int)$dt->format('W'), 'first' => $dt];
                            }
                            $wgBuckets[$key]['span']++;
                            $wgBuckets[$key]['last'] = $dt;
                        }
                        foreach ($wgBuckets as $wg): ?>
                        <th class="vac-th-wg" colspan="<?= $wg['span'] ?>">
                            <span class="vac-wg-label">Týden <?= $wg['n'] ?></span>
                            <span class="vac-wg-dates"><?= $wg['first']->format('j.n') ?>–<?= $wg['last']->format('j.n') ?></span>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <th class="vac-th-user">Člen</th>
                        <?php foreach ($dates as $i => $dateStr):
                            $dt        = new DateTimeImmutable($dateStr);
                            $inMonth   = $dt->format('Y-m') === $currentMonth;
                            $isToday   = ($dateStr === $todayStr);
                            $isWeekend = ((int)$dt->format('N') >= 6);
                            $isWeekSep = ($i > 0 && (int)$dt->format('N') === 1);
                            $dayIdx    = (int)$dt->format('N') - 1;
                        ?>
                        <th class="vac-th-day<?= !$inMonth ? ' vac-outside' : '' ?><?= $isToday ? ' vac-today-hd' : '' ?><?= $isWeekend ? ' vac-weekend' : '' ?><?= $isWeekSep ? ' vac-week-sep' : '' ?>"
                            title="<?= $dt->format('j. n. Y') ?>">
                            <span class="vac-dayname"><?= $dayHeaders[$dayIdx] ?></span>
                            <span class="vac-daynum"><?= $dt->format('j') ?></span>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $uIdx => $user):
                        $uid          = (int)$user['user_id'];
                        $avatarUrl    = vacAvatar($user);
                        $userVacDates = $vacByUserDate[$uid] ?? [];
                        $colorHex     = $vacColorPalette[$userColors[$uid] ?? 0];
                    ?>
                    <tr class="vac-row<?= ($uIdx % 2 === 0) ? ' vac-row-even' : '' ?>">
                        <td class="vac-td-user">
                            <div class="vac-user-color-bar" style="background:<?= $colorHex ?>"></div>
                            <img src="<?= $avatarUrl ?>" alt="" class="vac-user-avatar" loading="lazy">
                            <span class="vac-user-name"><?= htmlspecialchars($user['username']) ?></span>
                        </td>
                        <?php foreach ($dates as $di => $dateStr):
                            $dt        = new DateTimeImmutable($dateStr);
                            $inMonth   = $dt->format('Y-m') === $currentMonth;
                            $isToday   = ($dateStr === $todayStr);
                            $isWeekend = ((int)$dt->format('N') >= 6);
                            $isWeekSep = ($di > 0 && (int)$dt->format('N') === 1);
                            $vacDay    = $userVacDates[$dateStr] ?? null;
                            $hasVac    = $vacDay !== null;
                            $isStart   = $hasVac && $vacDay['is_start'];
                            $isEnd     = $hasVac && $vacDay['is_end'];
                            $canDelete = $hasVac && ((int)$uid === (int)$currentUserId || \App\Auth\Permission::isVedeni());
                            $colorCls  = $hasVac ? ' vac-color-' . ($userColors[$uid] ?? 0) : '';
                            $cls = 'vac-td-day';
                            if (!$inMonth) $cls .= ' vac-outside';
                            if ($isToday)  $cls .= ' vac-today-col';
                            if ($isWeekend) $cls .= ' vac-weekend';
                            if ($isWeekSep) $cls .= ' vac-week-sep';
                            if ($hasVac)   $cls .= ' vac-has' . $colorCls;
                            if ($isStart)  $cls .= ' vac-start';
                            if ($isEnd)    $cls .= ' vac-end';
                        ?>
                        <td class="<?= $cls ?>"
                            <?php if ($hasVac): ?>
                                title="<?= htmlspecialchars($user['username']) ?>: <?= htmlspecialchars($vacDay['date_from']) ?> – <?= htmlspecialchars($vacDay['date_to']) ?><?= !empty($vacDay['note']) ? ' · ' . htmlspecialchars($vacDay['note']) : '' ?>"
                                <?php if ($canDelete): ?>
                                    data-vac-id="<?= $vacDay['id'] ?>"
                                    data-vac-user="<?= htmlspecialchars($user['username']) ?>"
                                    data-vac-from="<?= htmlspecialchars($vacDay['date_from']) ?>"
                                    data-vac-to="<?= htmlspecialchars($vacDay['date_to']) ?>"
                                    data-vac-note="<?= htmlspecialchars($vacDay['note'] ?? '') ?>"
                                    onclick="vacShowDetail(this)"
                                    role="button"
                                <?php endif; ?>
                            <?php endif; ?>
                        >
                            <?php if ($hasVac && $isStart):
                                $vFrom     = new DateTimeImmutable($vacDay['date_from']);
                                $vTo       = new DateTimeImmutable($vacDay['date_to']);
                                $spanLabel = $vFrom->format('j.n') . '–' . $vTo->format('j.n');
                            ?>
                                <div class="vac-bar">
                                    <img src="<?= $avatarUrl ?>" alt="" class="vac-bar-avatar">
                                    <span class="vac-bar-label"><?= $spanLabel ?></span>
                                </div>
                            <?php elseif ($hasVac): ?>
                                <div class="vac-bar vac-bar-cont"></div>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- ── Add vacation modal ─────────────────────────────────────── -->
        <div id="vac-add-modal" class="act-modal" style="display:none;" onclick="if(event.target===this)this.style.display='none'">
            <div class="act-modal-content">
                <div class="act-modal-header">
                    <h3>Přidat dovolenou</h3>
                    <button type="button" class="act-modal-close" onclick="document.getElementById('vac-add-modal').style.display='none'">&times;</button>
                </div>
                <form method="POST" action="/admin/vacation/save">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <div class="act-modal-body">
                        <div class="form-group">
                            <label for="vac-from" class="form-label">Od</label>
                            <input type="date" id="vac-from" name="date_from" class="form-control" required value="<?= $todayStr ?>">
                        </div>
                        <div class="form-group" style="margin-top:0.75rem;">
                            <label for="vac-to" class="form-label">Do</label>
                            <input type="date" id="vac-to" name="date_to" class="form-control" required value="<?= $todayStr ?>">
                        </div>
                        <div class="form-group" style="margin-top:0.75rem;">
                            <label for="vac-note" class="form-label">Poznámka (volitelné)</label>
                            <input type="text" id="vac-note" name="note" class="form-control" maxlength="255" placeholder="Důvod, místo…">
                        </div>
                    </div>
                    <div class="act-modal-footer">
                        <button type="submit" class="btn btn-primary">Uložit</button>
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('vac-add-modal').style.display='none'">Zavřít</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ── Vacation detail / delete modal ────────────────────────── -->
        <div id="vac-detail-modal" class="act-modal" style="display:none;" onclick="if(event.target===this)this.style.display='none'">
            <div class="act-modal-content">
                <div class="act-modal-header">
                    <h3 id="vac-detail-title">Detail dovolené</h3>
                    <button type="button" class="act-modal-close" onclick="document.getElementById('vac-detail-modal').style.display='none'">&times;</button>
                </div>
                <div class="act-modal-body vac-detail-body">
                    <div class="vac-detail-row">
                        <span class="vac-detail-key">Člen</span>
                        <span class="vac-detail-val" id="vac-detail-user"></span>
                    </div>
                    <div class="vac-detail-row">
                        <span class="vac-detail-key">Od</span>
                        <span class="vac-detail-val" id="vac-detail-from"></span>
                    </div>
                    <div class="vac-detail-row">
                        <span class="vac-detail-key">Do</span>
                        <span class="vac-detail-val" id="vac-detail-to"></span>
                    </div>
                    <div class="vac-detail-row" id="vac-detail-note-row">
                        <span class="vac-detail-key">Poznámka</span>
                        <span class="vac-detail-val" id="vac-detail-note"></span>
                    </div>
                </div>
                <div class="act-modal-footer">
                    <form method="POST" action="/admin/vacation/delete" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="vacation_id" id="vac-detail-id" value="">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Opravdu smazat tuto dovolenou?')">Smazat</button>
                    </form>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('vac-detail-modal').style.display='none'">Zavřít</button>
                </div>
            </div>
        </div>

        <script>
        function vacShowDetail(td) {
            document.getElementById('vac-detail-user').textContent = td.dataset.vacUser;
            document.getElementById('vac-detail-from').textContent = td.dataset.vacFrom;
            document.getElementById('vac-detail-to').textContent = td.dataset.vacTo;
            document.getElementById('vac-detail-id').value = td.dataset.vacId;
            const note = td.dataset.vacNote || '';
            const noteRow = document.getElementById('vac-detail-note-row');
            if (note) {
                document.getElementById('vac-detail-note').textContent = note;
                noteRow.style.display = '';
            } else {
                noteRow.style.display = 'none';
            }
            document.getElementById('vac-detail-modal').style.display = 'flex';
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('vac-add-modal').style.display = 'none';
                document.getElementById('vac-detail-modal').style.display = 'none';
            }
        });
        </script>
    </div>
</section>

