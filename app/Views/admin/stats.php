<?php
function fmtMinutes(?float $minutes): string {
    if ($minutes === null) return '—';
    if ($minutes < 60)   return round($minutes) . ' min';
    if ($minutes < 1440) return round($minutes / 60, 1) . ' h';
    return round($minutes / 1440, 1) . ' d';
}
function fmtDate(?string $dt): string {
    if (!$dt) return '—';
    return date('d.m.y H:i', strtotime($dt));
}
?>
<section class="section">
    <div class="container">
        <h1 class="page-title">Admin Panel</h1>
        <div class="ornament">&#10070;&#10070;&#10070;</div>

        <?php $adminActive = 'stats'; require __DIR__ . '/_panel_nav.php'; ?>

        <!-- Period tabs -->
        <div class="stats-period-tabs">
            <?php foreach ($periodOptions as $val => $label): ?>
                <a href="/admin/stats?period=<?= htmlspecialchars((string)$val) ?>"
                   class="stats-period-tab <?= $period === (string)$val ? 'stats-period-tab-active' : '' ?>">
                    <?= htmlspecialchars($label) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Summary cards -->
        <div class="stats-summary">
            <div class="stats-card stats-card-forms">
                <div class="stats-card-value"><?= $totals['total_forms'] ?></div>
                <div class="stats-card-label">Formulářů celkem</div>
            </div>
            <div class="stats-card stats-card-ok">
                <div class="stats-card-value"><?= $totals['forms_approved'] ?></div>
                <div class="stats-card-label">Schváleno</div>
            </div>
            <div class="stats-card stats-card-rejected">
                <div class="stats-card-value"><?= $totals['forms_rejected'] ?></div>
                <div class="stats-card-label">Zamítnuto</div>
            </div>
            <div class="stats-card stats-card-interviews">
                <div class="stats-card-value"><?= $totals['total_interviews'] ?></div>
                <div class="stats-card-label">Pohovorů celkem</div>
            </div>
            <div class="stats-card stats-card-passed">
                <div class="stats-card-value"><?= $totals['interviews_passed'] ?></div>
                <div class="stats-card-label">Pohovorů prošlo</div>
            </div>
            <div class="stats-card stats-card-failed">
                <div class="stats-card-value"><?= $totals['interviews_failed'] ?></div>
                <div class="stats-card-label">Pohovorů neprošlo</div>
            </div>
        </div>

        <!-- Tester table -->
        <?php if (empty($testerStats)): ?>
            <div class="stats-empty">Žádná data za toto období.</div>
        <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tester</th>
                        <th title="Formuláře schváleny / zamítnuty / celkem">Formuláře</th>
                        <th title="Pohovory prošlo / neprošlo / celkem">Pohovory</th>
                        <th title="Průměrná doba zpracování formuláře">Ø čas kontroly</th>
                        <th>První akce</th>
                        <th>Poslední akce</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($testerStats as $i => $t): ?>
                        <?php
                        // Determine last action date
                        $dates = array_filter([$t['last_form'], $t['last_interview']]);
                        $lastAction = $dates ? max($dates) : null;
                        $firstDates = array_filter([$t['first_form'], $t['first_interview']]);
                        $firstAction = $firstDates ? min($firstDates) : null;
                        ?>
                        <tr>
                            <td class="stats-num" style="color:var(--grey-dim);width:30px;"><?= $i + 1 ?></td>
                            <td><span class="stats-tester-name"><?= htmlspecialchars($t['username']) ?></span></td>
                            <td>
                                <?php if ($t['total_forms'] > 0): ?>
                                    <span class="stats-num-ok"><?= $t['forms_approved'] ?></span>
                                    <span style="color:var(--grey-dim);margin:0 2px;">/</span>
                                    <span class="stats-num-bad"><?= $t['forms_rejected'] ?></span>
                                    <span style="color:var(--grey-dim);margin:0 2px;">/</span>
                                    <span class="stats-num-main"><?= $t['total_forms'] ?></span>
                                <?php else: ?>
                                    <span style="color:var(--grey-dim);">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($t['total_interviews'] > 0): ?>
                                    <span class="stats-num-ok"><?= $t['interviews_passed'] ?></span>
                                    <span style="color:var(--grey-dim);margin:0 2px;">/</span>
                                    <span class="stats-num-bad"><?= $t['interviews_failed'] ?></span>
                                    <span style="color:var(--grey-dim);margin:0 2px;">/</span>
                                    <span class="stats-num-main"><?= $t['total_interviews'] ?></span>
                                <?php else: ?>
                                    <span style="color:var(--grey-dim);">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="stats-avg-time"><?= fmtMinutes($t['avg_review_minutes']) ?></td>
                            <td class="stats-time"><?= fmtDate($firstAction) ?></td>
                            <td class="stats-time"><?= fmtDate($lastAction) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2">Celkem</td>
                        <td>
                            <span class="stats-num-ok"><?= $totals['forms_approved'] ?></span>
                            <span style="color:var(--grey-dim);margin:0 2px;">/</span>
                            <span class="stats-num-bad"><?= $totals['forms_rejected'] ?></span>
                            <span style="color:var(--grey-dim);margin:0 2px;">/</span>
                            <span class="stats-num-main"><?= $totals['total_forms'] ?></span>
                        </td>
                        <td>
                            <span class="stats-num-ok"><?= $totals['interviews_passed'] ?></span>
                            <span style="color:var(--grey-dim);margin:0 2px;">/</span>
                            <span class="stats-num-bad"><?= $totals['interviews_failed'] ?></span>
                            <span style="color:var(--grey-dim);margin:0 2px;">/</span>
                            <span class="stats-num-main"><?= $totals['total_interviews'] ?></span>
                        </td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>

        <p style="margin-top:1rem;font-size:0.75rem;color:var(--grey-dim);">
            Sloupce Formuláře a Pohovory: <strong style="color:var(--grey)">schváleno / zamítnuto / celkem</strong>.
            Hodnoty Ø čas kontroly jsou průměrné časy od podání formuláře po jeho schválení/zamítnutí.
        </p>
    </div>
</section>
