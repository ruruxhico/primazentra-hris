<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
    $milestones       = $milestones       ?? [];
    $curveData        = $curveData        ?? null;
    $plannedToDate    = $plannedToDate    ?? null;
    $actualToDate     = $actualToDate     ?? 0.0;
    $variance         = $variance         ?? null;
    $ewd              = $ewd              ?? null;
    $weeklyTargets    = $weeklyTargets    ?? [];
    $milestoneSummary = $milestoneSummary ?? [];
    $reportDate       = $reportDate       ?? date('Y-m-d');

    $hasMilestones = !empty($milestones);
    $hasCurve      = $curveData !== null && $plannedToDate !== null;

    $undatedCount     = $curveData['undatedCount']    ?? 0;
    $totalMilestones  = $curveData['totalMilestones'] ?? count($milestones);
    $curveWarning     = $curveData['warning']         ?? null;
?>

<style>
/* ── Progress blocks ──────────────────────────────────────────── */
.ms-card { background:#fff; border:1px solid #e8d9a8; border-radius:12px; padding:1.5rem; margin-bottom:1.5rem; }
.ms-card-title { font-size:1rem; font-weight:700; color:#5c3d11; margin:0 0 1rem; display:flex; align-items:center; gap:.5rem; }
.ms-card-title .material-symbols-outlined { font-size:1.15rem; }

/* Overall Progress metrics */
.ms-metrics { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:1rem; }
.ms-metric { background:#fdf8ee; border-radius:8px; padding:.9rem 1rem; text-align:center; }
.ms-metric-label { font-size:.72rem; font-weight:600; color:#8c701c; text-transform:uppercase; letter-spacing:.04em; margin-bottom:.35rem; }
.ms-metric-value { font-size:1.5rem; font-weight:800; color:#3a2a0a; }
.ms-metric-value.positive { color:#2e7d32; }
.ms-metric-value.negative { color:#c62828; }
.ms-metric-value.neutral  { color:#5c3d11; }
.ms-undated-badge { display:inline-flex; align-items:center; gap:.3rem; background:#fff3cd; color:#856404; border:1px solid #ffc107; border-radius:20px; font-size:.78rem; font-weight:600; padding:.25rem .75rem; margin-bottom:1rem; }

/* Tables */
.ms-table-wrap { overflow-x:auto; }
.ms-table { width:100%; border-collapse:collapse; font-size:.88rem; }
.ms-table th { background:#fdf8ee; color:#8c701c; font-weight:700; font-size:.75rem; text-transform:uppercase; letter-spacing:.04em; padding:.6rem .8rem; border-bottom:2px solid #e8d9a8; text-align:left; }
.ms-table td { padding:.6rem .8rem; border-bottom:1px solid #f0e6c8; color:#3a2a0a; }
.ms-table tr:last-child td { border-bottom:none; }
.ms-table tr:hover td { background:#fdf8ee; }
.ms-table .num { text-align:right; font-variant-numeric:tabular-nums; }
.ms-table .pos { color:#2e7d32; font-weight:600; }
.ms-table .neg { color:#c62828; font-weight:600; }
.ms-table .muted { color:#999; }
.ms-milestone-marker { display:inline-block; width:7px; height:7px; border-radius:50%; background:#c8a951; margin-right:4px; vertical-align:middle; }

/* Status badges */
.ms-status { display:inline-block; padding:.15rem .5rem; border-radius:4px; font-size:.75rem; font-weight:600; }
.ms-status-pending     { background:#f5f5f5; color:#666; }
.ms-status-inprogress  { background:#e3f2fd; color:#1565c0; }
.ms-status-done        { background:#e8f5e9; color:#2e7d32; }

/* Warning banner */
.ms-warning { background:#fff8e1; border:1px solid #ffe082; border-radius:8px; padding:.7rem 1rem; font-size:.85rem; color:#795548; display:flex; align-items:flex-start; gap:.5rem; margin-bottom:1rem; }
.ms-warning .material-symbols-outlined { font-size:1.05rem; flex-shrink:0; color:#f9a825; margin-top:1px; }

/* Empty state */
.ms-empty { text-align:center; padding:2rem 1rem; color:#999; font-size:.9rem; }
</style>

<a href="<?= base_url('projects') ?>" class="pz-back-link">
    <span class="material-symbols-outlined">arrow_back</span> Projects
</a>

<div class="pz-page-header">
    <h1><?= esc(view_text($project['project_name'] ?? 'Project Detail')) ?></h1>
    <p><?= esc(view_text($project['project_type'] ?? '')) ?></p>
</div>

<!-- ── Project Overview ─────────────────────────────────────── -->
<div class="pz-card">
    <p class="pz-card-section">Overview</p>
    <div class="pz-info-grid">
        <span class="pz-info-label">Status</span>
        <span class="pz-info-value">
            <?php
                $st = strtolower($project['status'] ?? '');
                $badgeMap = ['completed'=>'pz-badge-completed','done'=>'pz-badge-done','halted'=>'pz-badge-halted','planned'=>'pz-badge-pending','on-going'=>'pz-badge-ongoing','ongoing'=>'pz-badge-ongoing'];
                $bc = $badgeMap[$st] ?? 'pz-badge-active';
            ?>
            <span class="pz-badge <?= $bc ?>"><?= esc(strtoupper($project['status'] ?? '—')) ?></span>
        </span>

        <span class="pz-info-label">Progress</span>
        <span class="pz-info-value">
            <span class="pz-badge pz-badge-progress"><?= esc(view_text($project['progress_label'] ?? ($project['overall_progress'] ?? 0))) ?>%</span>
        </span>

        <div class="pz-info-divider"></div>

        <span class="pz-info-label">Location</span>
        <span class="pz-info-value"><?= esc(view_text($project['location_label'] ?? ($project['location']['address'] ?? '—'))) ?></span>

        <span class="pz-info-label">Start Date</span>
        <span class="pz-info-value"><?= esc(view_text($project['start_date_value'] ?? '—')) ?></span>

        <span class="pz-info-label">End Date</span>
        <span class="pz-info-value"><?= esc(view_text($project['end_date_value'] ?? '—')) ?></span>

        <span class="pz-info-label">Budget</span>
        <span class="pz-info-value"><?= esc(view_text($project['budget'] ?? '—')) ?></span>

        <div class="pz-info-divider"></div>

        <span class="pz-info-label">Project Manager</span>
        <span class="pz-info-value"><?= esc(view_text($project['project_manager_name'] ?? 'N/A', 'N/A')) ?></span>

        <span class="pz-info-label">Site Engineer</span>
        <span class="pz-info-value"><?= esc(view_text($project['site_engineer_name'] ?? 'N/A', 'N/A')) ?></span>
    </div>

    <div class="pz-card-actions">
        <a href="<?= base_url('projects/' . ($project['id'] ?? '') . '/resources') ?>" class="pz-btn pz-btn-outline">
            <span class="material-symbols-outlined">inventory_2</span> Resources
        </a>
        <a href="<?= base_url('projects/update/' . ($project['id'] ?? '')) ?>" class="pz-btn pz-btn-primary">
            <span class="material-symbols-outlined">edit</span> Edit
        </a>
    </div>
</div>

<?php if (!$hasMilestones): ?>
<!-- No milestones yet -->
<div class="ms-card">
    <div class="ms-empty">
        <span class="material-symbols-outlined" style="font-size:2rem;color:#e8d9a8;display:block;margin-bottom:.5rem">flag</span>
        No milestones defined. Edit the project and add a schedule to enable progress tracking.
    </div>
</div>

<?php else: ?>

<!-- ── a. Overall Progress ─────────────────────────────────── -->
<div class="ms-card">
    <p class="ms-card-title">
        <span class="material-symbols-outlined">trending_up</span>
        Overall Progress
        <span style="margin-left:auto;font-size:.75rem;font-weight:400;color:#999">as of <?= esc($reportDate) ?></span>
    </p>

    <?php if ($undatedCount > 0): ?>
    <div class="ms-undated-badge">
        <span class="material-symbols-outlined" style="font-size:.9rem">info</span>
        <?= $undatedCount ?> of <?= $totalMilestones ?> milestone<?= $totalMilestones !== 1 ? 's' : '' ?> scheduled
        — planned figure reflects scheduled scope only
    </div>
    <?php endif; ?>

    <?php if ($curveWarning): ?>
    <div class="ms-warning">
        <span class="material-symbols-outlined">warning</span>
        <?= esc($curveWarning) ?>
    </div>
    <?php endif; ?>

    <div class="ms-metrics">
        <?php if ($hasCurve): ?>
        <div class="ms-metric">
            <div class="ms-metric-label">Planned to Date</div>
            <div class="ms-metric-value neutral"><?= number_format($plannedToDate, 1) ?>%</div>
        </div>
        <?php endif; ?>

        <div class="ms-metric">
            <div class="ms-metric-label">Actual to Date</div>
            <div class="ms-metric-value neutral"><?= number_format($actualToDate, 1) ?>%</div>
        </div>

        <?php if ($variance !== null): ?>
        <div class="ms-metric">
            <div class="ms-metric-label">Variance</div>
            <div class="ms-metric-value <?= $variance >= 0 ? 'positive' : 'negative' ?>">
                <?= ($variance >= 0 ? '+' : '') . number_format($variance, 1) ?>%
            </div>
        </div>
        <?php endif; ?>

        <?php if ($ewd !== null): ?>
        <div class="ms-metric">
            <div class="ms-metric-label">Equiv. Work Days</div>
            <div class="ms-metric-value <?= $ewd >= 0 ? 'positive' : 'negative' ?>">
                <?= ($ewd >= 0 ? '+' : '') . number_format($ewd, 1) ?> d
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($hasCurve && !empty($weeklyTargets)): ?>
<!-- ── b. Weekly Progress Summary ─────────────────────────── -->
<div class="ms-card">
    <p class="ms-card-title">
        <span class="material-symbols-outlined">calendar_view_week</span>
        Weekly Progress Summary
    </p>

    <div class="ms-table-wrap">
        <table class="ms-table">
            <thead>
                <tr>
                    <th>Week Ending</th>
                    <th class="num">Target %</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php
                // Show the most recent 26 weeks (6 months) to keep the table manageable
                $displayWeeks = array_slice($weeklyTargets, -26);
            ?>
            <?php foreach ($displayWeeks as $row): ?>
                <tr>
                    <td>
                        <?php if ($row['hasMilestone']): ?>
                            <span class="ms-milestone-marker" title="Milestone due this week"></span>
                        <?php endif; ?>
                        <?= esc(date('d M Y', strtotime($row['weekEnd']))) ?>
                    </td>
                    <td class="num"><?= number_format($row['target'], 1) ?>%</td>
                    <td>
                        <?php if ($row['hasMilestone']): ?>
                            <span style="font-size:.75rem;color:#8c701c;font-weight:600">milestone due</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($weeklyTargets) > 26): ?>
                <tr>
                    <td colspan="3" class="muted" style="font-size:.8rem;text-align:center">
                        Showing most recent 26 weeks of <?= count($weeklyTargets) ?> total
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ── c. Milestone Summary ──────────────────────────────── -->
<div class="ms-card">
    <p class="ms-card-title">
        <span class="material-symbols-outlined">flag</span>
        Milestone Summary
    </p>

    <?php if (empty($milestoneSummary)): ?>
    <div class="ms-empty">No milestone data available.</div>
    <?php else: ?>
    <div class="ms-table-wrap">
        <table class="ms-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Milestone</th>
                    <th>Target Date</th>
                    <th class="num">Planned %</th>
                    <th class="num">Actual %</th>
                    <th class="num">Variance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($milestoneSummary as $i => $row): ?>
                <?php
                    $statusClass = 'ms-status-pending';
                    $statusLower = strtolower($row['status']);
                    if (in_array($statusLower, ['done', 'completed'], true)) {
                        $statusClass = 'ms-status-done';
                    } elseif ($statusLower === 'in progress') {
                        $statusClass = 'ms-status-inprogress';
                    }
                ?>
                <tr>
                    <td class="muted"><?= $i + 1 ?></td>
                    <td><?= esc($row['name']) ?></td>
                    <td>
                        <?= $row['targetDate']
                            ? esc(date('d M Y', strtotime($row['targetDate'])))
                            : '<span class="muted">—</span>' ?>
                    </td>
                    <td class="num">
                        <?= $row['plannedPct'] !== null
                            ? number_format($row['plannedPct'], 1) . '%'
                            : '<span class="muted">—</span>' ?>
                    </td>
                    <td class="num"><?= number_format($row['actualPct'], 1) ?>%</td>
                    <td class="num">
                        <?php if ($row['variance'] !== null): ?>
                            <span class="<?= $row['variance'] >= 0 ? 'pos' : 'neg' ?>">
                                <?= ($row['variance'] >= 0 ? '+' : '') . number_format($row['variance'], 1) ?>%
                            </span>
                        <?php else: ?>
                            <span class="muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="ms-status <?= $statusClass ?>">
                            <?= esc(ucwords(strtolower($row['status']))) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php endif; /* hasMilestones */ ?>

<?= $this->endSection() ?>
