<?php
$dayNames = ['Mon' => 'Po', 'Tue' => 'Út', 'Wed' => 'St', 'Thu' => 'Čt', 'Fri' => 'Pá', 'Sat' => 'So', 'Sun' => 'Ne'];
$dayHeaders = ['Po', 'Út', 'St', 'Čt', 'Pá', 'So', 'Ne'];
$todayStr = date('Y-m-d');
$isOwnView = ((int)$viewUserId === (int)($currentUserId ?? 0));
$currentMonth = $monthStart ?? '';
?>
<section class="section">
    <div class="container">
        <h1 class="page-title">Admin Panel</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>

        <?php require __DIR__ . '/_panel_nav.php'; ?>

        <?php if ($isVedeni): ?>
            <!-- Vedení controls: user switcher + month navigation -->
            <div class="act-controls">
                <div class="act-switcher">
                    <?php $userParam = (int)$viewUserId !== (int)$currentUserId ? '&user=' . (int)$viewUserId : ''; ?>
                    <select id="act-user-select" class="form-control act-switcher-select"
                            onchange="window.location.href='/admin/activity?month=<?= urlencode($currentMonth) ?>'+(this.value ? '&user='+this.value : '')">
                        <option value="" <?= $isOwnView ? 'selected' : '' ?>>Moje aktivita</option>
                        <?php
                            $memberMap = [];
                            foreach ($activeUsers as $au) {
                                if ((int)$au['user_id'] !== (int)$currentUserId) {
                                    $memberMap[(int)$au['user_id']] = $au['username'];
                                }
                            }
                            asort($memberMap);
                        ?>
                        <?php foreach ($memberMap as $uid => $uname): ?>
                            <option value="<?= $uid ?>" <?= (int)$viewUserId === $uid ? 'selected' : '' ?>>
                                <?= htmlspecialchars($uname) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="act-month-nav">
                    <a href="/admin/activity?month=<?= urlencode($prevMonth) ?><?= $userParam ?>" class="btn btn-secondary btn-sm">&larr;</a>
                    <span class="act-month-label"><?= htmlspecialchars($monthLabel) ?></span>
                    <?php
                        $currentYM = date('Y-m');
                        $canGoNext = ($currentMonth < $currentYM);
                    ?>
                    <?php if ($canGoNext): ?>
                        <a href="/admin/activity?month=<?= urlencode($nextMonth) ?><?= $userParam ?>" class="btn btn-secondary btn-sm">&rarr;</a>
                    <?php else: ?>
                        <span class="btn btn-secondary btn-sm" style="opacity:0.3;pointer-events:none;">&rarr;</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Full month calendar grid -->
            <div class="act-calendar act-calendar-month">
                <div class="act-cal-header-row">
                    <?php foreach ($dayHeaders as $dh): ?>
                        <div class="act-cal-hdr"><?= $dh ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="act-cal-month-grid">
                    <?php foreach ($dates as $dateStr): ?>
                        <?php
                            $dt       = new DateTimeImmutable($dateStr);
                            $existing = $activities[$dateStr] ?? null;
                            $isToday  = ($dateStr === $todayStr);
                            $inMonth  = $dt->format('Y-m') === $currentMonth;
                            $filled   = $existing !== null;
                            $active   = $filled && $existing['was_active'];
                            $isFuture = $dateStr > $todayStr;

                            if (!$inMonth)       $cellCls = 'act-cell-outside';
                            elseif ($isFuture)   $cellCls = 'act-cell-future';
                            elseif ($active)     $cellCls = 'act-cell-active';
                            elseif ($filled)     $cellCls = 'act-cell-inactive';
                            else                 $cellCls = 'act-cell-empty';

                            $clickable = $inMonth && !$isFuture;
                        ?>
                        <div class="act-month-cell <?= $cellCls ?> <?= $isToday ? 'act-cell-today' : '' ?> <?= $clickable ? 'act-clickable' : '' ?>"
                             <?php if ($clickable): ?>
                                 data-date="<?= $dateStr ?>" onclick="actOpenDetail(this)" role="button" tabindex="0"
                             <?php endif; ?>
                             <?php if ($filled && !empty($existing['description'])): ?>
                                 title="<?= htmlspecialchars($existing['description']) ?>"
                             <?php endif; ?>>
                            <span class="act-month-day"><?= $dt->format('j') ?></span>
                            <?php if ($inMonth && !$isFuture): ?>
                                <span class="act-month-icon">
                                    <?php if ($active): ?>✓<?php elseif ($filled): ?>✗<?php else: ?>—<?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Day detail modal (vedení) -->
            <?php
                $weekAgoStr = (new DateTimeImmutable('-6 days'))->format('Y-m-d');
            ?>
            <div id="act-detail-modal" class="act-modal" style="display:none;" onclick="if(event.target===this)actCloseDetail()">
                <div class="act-modal-content act-detail-wide">
                    <div class="act-modal-header">
                        <h3 id="act-detail-title"></h3>
                        <button type="button" class="act-modal-close" onclick="actCloseDetail()">&times;</button>
                    </div>
                    <div class="act-modal-body">
                        <!-- All users' entries for this day -->
                        <div id="act-detail-list" class="act-detail-list"></div>

                        <!-- Own edit form (shown only for last 7 days) -->
                        <div id="act-detail-edit" class="act-detail-edit" style="display:none;">
                            <h4>Tvoje aktivita</h4>
                            <form method="POST" action="/admin/activity/save" id="act-detail-form">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="date" id="act-detail-date" value="">
                                <label class="activity-checkbox-label">
                                    <input type="checkbox" name="was_active" value="1" id="act-detail-active">
                                    Byl/a jsem aktivní
                                </label>
                                <div class="form-group" style="margin-top:0.75rem;">
                                    <label for="act-detail-desc" class="form-label">Co jsi dělal/a?</label>
                                    <textarea id="act-detail-desc" name="description" class="form-control" rows="3" placeholder="Stručný popis…"></textarea>
                                </div>
                                <div class="act-modal-footer" style="border-top:none;padding:0.75rem 0 0;">
                                    <button type="submit" class="btn btn-primary">Uložit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            const actAllByDate = <?= json_encode($allByDate, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
            const actOwnData   = <?= json_encode($ownActivities, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
            const actWeekAgo   = '<?= $weekAgoStr ?>';
            const actToday     = '<?= $todayStr ?>';
            const actDayNames  = ['Neděle','Pondělí','Úterý','Středa','Čtvrtek','Pátek','Sobota'];

            function actOpenDetail(cell) {
                const date = cell.dataset.date;
                const d = new Date(date + 'T00:00:00');
                const label = actDayNames[d.getDay()] + ' ' + d.getDate() + '. ' + (d.getMonth()+1) + '. ' + d.getFullYear();

                document.getElementById('act-detail-title').textContent = label;

                // Build user entries list
                const list = document.getElementById('act-detail-list');
                const entries = actAllByDate[date] || [];
                if (entries.length === 0) {
                    list.innerHTML = '<p class="text-muted">Žádné záznamy pro tento den.</p>';
                } else {
                    let html = '';
                    entries.forEach(function(e) {
                        const icon = e.was_active
                            ? '<span class="act-d-icon act-d-ok">✓</span>'
                            : '<span class="act-d-icon act-d-no">✗</span>';
                        const desc = e.description
                            ? '<span class="act-d-desc">' + escHtml(e.description) + '</span>'
                            : '<span class="act-d-desc text-muted">—</span>';
                        html += '<div class="act-d-row">'
                            + icon
                            + '<span class="act-d-user">' + escHtml(e.username) + '</span>'
                            + desc
                            + '</div>';
                    });
                    list.innerHTML = html;
                }

                // Show edit form if date is within last 7 days
                const editBlock = document.getElementById('act-detail-edit');
                if (date >= actWeekAgo && date <= actToday) {
                    editBlock.style.display = 'block';
                    document.getElementById('act-detail-date').value = date;
                    const own = actOwnData[date] || null;
                    document.getElementById('act-detail-active').checked = own ? !!parseInt(own.was_active) : false;
                    document.getElementById('act-detail-desc').value = own ? (own.description || '') : '';
                } else {
                    editBlock.style.display = 'none';
                }

                document.getElementById('act-detail-modal').style.display = 'flex';
            }

            function actCloseDetail() {
                document.getElementById('act-detail-modal').style.display = 'none';
            }

            function escHtml(str) {
                const d = document.createElement('div');
                d.textContent = str;
                return d.innerHTML;
            }

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') actCloseDetail();
            });
            </script>

        <?php else: ?>
            <!-- Admin: 7-day strip -->
            <h2 style="margin-bottom:1rem;">Moje aktivita — posledních 7 dní</h2>
            <div class="act-calendar">
                <div class="act-cal-grid">
                    <?php foreach ($dates as $dateStr): ?>
                        <?php
                            $dt       = new DateTimeImmutable($dateStr);
                            $dayN     = $dayNames[$dt->format('D')] ?? $dt->format('D');
                            $existing = $activities[$dateStr] ?? null;
                            $isToday  = ($dateStr === $todayStr);
                            $filled   = $existing !== null;
                            $active   = $filled && $existing['was_active'];

                            if ($active)       $cellCls = 'act-cell-active';
                            elseif ($filled)   $cellCls = 'act-cell-inactive';
                            else               $cellCls = 'act-cell-empty';
                        ?>
                        <div class="act-cal-cell <?= $cellCls ?> <?= $isToday ? 'act-cell-today' : '' ?>"
                             data-date="<?= $dateStr ?>"
                             onclick="actOpenDay(this)"
                             role="button" tabindex="0">
                            <div class="act-cell-head">
                                <span class="act-cell-dayname"><?= $dayN ?></span>
                                <span class="act-cell-num"><?= $dt->format('j. n.') ?></span>
                                <?php if ($isToday): ?>
                                    <span class="act-badge-today">Dnes</span>
                                <?php endif; ?>
                            </div>
                            <div class="act-cell-icon">
                                <?php if ($active): ?>
                                    <svg viewBox="0 0 20 20" fill="currentColor" width="28" height="28" class="act-icon-ok"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                <?php elseif ($filled): ?>
                                    <svg viewBox="0 0 20 20" fill="currentColor" width="28" height="28" class="act-icon-no"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                <?php else: ?>
                                    <span class="act-icon-missing">—</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($filled && !empty($existing['description'])): ?>
                                <div class="act-cell-desc" title="<?= htmlspecialchars($existing['description']) ?>"><?= htmlspecialchars(mb_strimwidth($existing['description'], 0, 50, '…')) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Edit modal for admin -->
            <div id="act-modal" class="act-modal" style="display:none;" onclick="if(event.target===this)actCloseModal()">
                <div class="act-modal-content">
                    <div class="act-modal-header">
                        <h3 id="act-modal-title"></h3>
                        <button type="button" class="act-modal-close" onclick="actCloseModal()">&times;</button>
                    </div>
                    <form method="POST" action="/admin/activity/save" id="act-modal-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="date" id="act-modal-date" value="">
                        <div class="act-modal-body">
                            <label class="activity-checkbox-label">
                                <input type="checkbox" name="was_active" value="1" id="act-modal-active">
                                Byl/a jsem aktivní
                            </label>
                            <div class="form-group" style="margin-top:0.75rem;">
                                <label for="act-modal-desc" class="form-label">Co jsi dělal/a?</label>
                                <textarea id="act-modal-desc" name="description" class="form-control" rows="3" placeholder="Stručný popis…"></textarea>
                            </div>
                        </div>
                        <div class="act-modal-footer">
                            <button type="submit" class="btn btn-primary">Uložit</button>
                            <button type="button" class="btn btn-secondary" onclick="actCloseModal()">Zavřít</button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
            const actData = <?= json_encode($activities, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
            const actDayNames = ['Neděle','Pondělí','Úterý','Středa','Čtvrtek','Pátek','Sobota'];

            function actOpenDay(cell) {
                const date = cell.dataset.date;
                const d = new Date(date + 'T00:00:00');
                const label = actDayNames[d.getDay()] + ' ' + d.getDate() + '. ' + (d.getMonth()+1) + '. ' + d.getFullYear();

                document.getElementById('act-modal-title').textContent = label;
                document.getElementById('act-modal-date').value = date;

                const existing = actData[date] || null;
                document.getElementById('act-modal-active').checked = existing ? !!parseInt(existing.was_active) : false;
                document.getElementById('act-modal-desc').value = existing ? (existing.description || '') : '';

                document.getElementById('act-modal').style.display = 'flex';
                setTimeout(() => document.getElementById('act-modal-desc').focus(), 50);
            }

            function actCloseModal() {
                document.getElementById('act-modal').style.display = 'none';
            }

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') actCloseModal();
            });
            </script>
        <?php endif; ?>
    </div>
</section>
