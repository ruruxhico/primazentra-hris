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
    $projectId        = $projectId        ?? ($project['id'] ?? '');

    $hasMilestones = !empty($milestones);
    $hasCurve      = $curveData !== null && $plannedToDate !== null;

    $undatedCount     = $curveData['undatedCount']    ?? 0;
    $totalMilestones  = $curveData['totalMilestones'] ?? count($milestones);
    $curveWarning     = $curveData['warning']         ?? null;

    // Project fields
    $pName      = esc(strtoupper($project['project_name'] ?? 'Project'));
    $pType      = esc($project['project_type'] ?? '');
    $pStatus    = $project['status'] ?? '';
    $progress   = (float)($project['overall_progress'] ?? $project['completion_percent'] ?? 0);
    $client     = esc($project['client_name'] ?? '—');
    $address    = esc($project['location_label'] ?? ($project['location']['address'] ?? '—'));
    $lat        = $project['location_lat_value'] ?? '';
    $lng        = $project['location_lng_value'] ?? '';
    $region     = esc($project['region'] ?? '—');
    $startFmt   = esc($project['start_date_value'] ?? '—');
    $endFmt     = esc($project['end_date_value']   ?? '—');
    $budget     = $project['budget'] ?? null;
    $budgetDoc  = $project['budget_document_url'] ?? null;
    $pm         = esc($project['project_manager_name'] ?? 'N/A');
    $se         = esc($project['site_engineer_name']   ?? 'N/A');

    $coordStr   = ($lat !== '' && $lng !== '') ? esc($lat . ', ' . $lng) : '—';
    $budgetFmt  = $budget !== null ? 'PHP ' . number_format((float)$budget, 2) : '—';

    // Status badge
    $stLower    = strtolower($pStatus);
    $stColors   = [
        'on-going'     => ['bg' => '#e8f5e9', 'color' => '#2e7d32', 'border' => '#a5d6a7'],
        'ongoing'      => ['bg' => '#e8f5e9', 'color' => '#2e7d32', 'border' => '#a5d6a7'],
        'completed'    => ['bg' => '#e3f2fd', 'color' => '#1565c0', 'border' => '#90caf9'],
        'planned'      => ['bg' => '#fff3e0', 'color' => '#e65100', 'border' => '#ffcc80'],
        'past deadline'=> ['bg' => '#fce4ec', 'color' => '#b71c1c', 'border' => '#ef9a9a'],
        'archived'     => ['bg' => '#f5f5f5', 'color' => '#757575', 'border' => '#e0e0e0'],
    ];
    $stStyle    = $stColors[$stLower] ?? ['bg' => '#f5f5f5', 'color' => '#555', 'border' => '#ccc'];
?>

<style>
/* ── Page shell ───────────────────────────────────────────────────── */
.pd-shell { max-width: 680px; margin: 0 auto; padding-bottom: 2rem; }

/* Back + Edit row */
.pd-topbar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 1.2rem;
}
.pd-back {
    display: inline-flex; align-items: center; gap: 4px;
    color: #8C701C; font-weight: 600; text-decoration: none; font-size: 0.92rem;
}
.pd-back:hover { color: #5c3d11; text-decoration: none; }
.pd-back .material-symbols-outlined { font-size: 20px; }
.pd-edit-btn {
    display: inline-flex; align-items: center; gap: 4px;
    background: none; border: 1.5px solid #C47A0F; color: #8C701C;
    border-radius: 6px; padding: 5px 14px; font-size: 0.85rem; font-weight: 600;
    text-decoration: none; cursor: pointer;
    font-variation-settings: 'FILL' 0, 'wght' 400;
}
.pd-edit-btn:hover { background: #fdf8ee; color: #5c3d11; text-decoration: none; }
.pd-edit-btn .material-symbols-outlined { font-size: 16px; }

/* Project card at top */
.pd-project-card {
    background: #fff; border: 1px solid #e5e0d0; border-radius: 14px;
    padding: 1.4rem 1.5rem; margin-bottom: 1rem;
}
.pd-project-card-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 0.75rem; margin-bottom: 4px;
}
.pd-project-name { font-size: 1.2rem; font-weight: 800; color: #2c1f07; line-height: 1.2; }
.pd-status-badge {
    flex-shrink: 0; border-radius: 20px; padding: 3px 12px;
    font-size: 0.75rem; font-weight: 700; border: 1.5px solid;
    white-space: nowrap;
}
.pd-project-type { font-size: 0.78rem; color: #8C701C; font-weight: 600; margin-bottom: 0.9rem; }
.pd-progress-row { display: flex; align-items: center; gap: 10px; }
.pd-progress-bar-wrap { flex: 1; background: #e8e0cc; border-radius: 6px; height: 10px; overflow: hidden; }
.pd-progress-fill { height: 100%; background: #8C701C; border-radius: 6px; }
.pd-progress-pct { font-size: 0.82rem; font-weight: 700; color: #5c3d11; min-width: 36px; text-align: right; }

/* Section blocks */
.pd-section { background: #fff; border: 1px solid #e5e0d0; border-radius: 14px; margin-bottom: 1rem; overflow: hidden; }
.pd-section-hdr {
    background: #8C701C; color: #fff;
    padding: 8px 16px; font-size: 0.72rem; font-weight: 700; letter-spacing: 0.07em;
    text-transform: uppercase;
}
.pd-section-body { padding: 0; }
.pd-row {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 1rem; padding: 11px 16px; border-bottom: 1px solid #f0ebe0;
}
.pd-row:last-child { border-bottom: none; }
.pd-row-label { font-size: 0.82rem; color: #888; font-weight: 500; flex-shrink: 0; min-width: 130px; }
.pd-row-value { font-size: 0.88rem; color: #2c1f07; font-weight: 500; text-align: right; word-break: break-word; }
.pd-row-value a { color: #8C701C; font-weight: 600; text-decoration: none; }
.pd-row-value a:hover { text-decoration: underline; }

/* Action buttons grid */
.pd-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 1.5rem; }
.pd-action-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 13px 10px; border-radius: 10px; border: none; cursor: pointer;
    font-size: 0.82rem; font-weight: 700; letter-spacing: 0.06em;
    text-decoration: none; text-transform: uppercase;
    font-variation-settings: 'FILL' 1, 'wght' 600;
}
.pd-action-btn .material-symbols-outlined { font-size: 18px; }
.pd-btn-resources  { background: #3a2a0a; color: #fff; }
.pd-btn-schedule   { background: #1565c0; color: #fff; }
.pd-btn-reports    { background: #2e7d32; color: #fff; }
.pd-btn-emergency  { background: #b71c1c; color: #fff; }
.pd-btn-resources:hover  { background: #5c3d11; text-decoration: none; color: #fff; }
.pd-btn-schedule:hover   { background: #0d47a1; text-decoration: none; color: #fff; }
.pd-btn-reports:hover    { background: #1b5e20; text-decoration: none; color: #fff; }
.pd-btn-emergency:hover  { background: #7f0000; text-decoration: none; color: #fff; }

/* ── Milestone / progress cards (unchanged) ───────────────────────── */
.ms-card { background:#fff; border:1px solid #e8d9a8; border-radius:12px; padding:1.5rem; margin-bottom:1.5rem; }
.ms-card-title { font-size:1rem; font-weight:700; color:#5c3d11; margin:0 0 1rem; display:flex; align-items:center; gap:.5rem; }
.ms-card-title .material-symbols-outlined { font-size:1.15rem; }
.ms-metrics { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:1rem; }
.ms-metric { background:#fdf8ee; border-radius:8px; padding:.9rem 1rem; text-align:center; }
.ms-metric-label { font-size:.72rem; font-weight:600; color:#8c701c; text-transform:uppercase; letter-spacing:.04em; margin-bottom:.35rem; }
.ms-metric-value { font-size:1.5rem; font-weight:800; color:#3a2a0a; }
.ms-metric-value.positive { color:#2e7d32; }
.ms-metric-value.negative { color:#c62828; }
.ms-metric-value.neutral  { color:#5c3d11; }
.ms-undated-badge { display:inline-flex; align-items:center; gap:.3rem; background:#fff3cd; color:#856404; border:1px solid #ffc107; border-radius:20px; font-size:.78rem; font-weight:600; padding:.25rem .75rem; margin-bottom:1rem; }
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
.ms-status { display:inline-block; padding:.15rem .5rem; border-radius:4px; font-size:.75rem; font-weight:600; }
.ms-status-pending     { background:#f5f5f5; color:#666; }
.ms-status-inprogress  { background:#e3f2fd; color:#1565c0; }
.ms-status-done        { background:#e8f5e9; color:#2e7d32; }
.ms-warning { background:#fff8e1; border:1px solid #ffe082; border-radius:8px; padding:.7rem 1rem; font-size:.85rem; color:#795548; display:flex; align-items:flex-start; gap:.5rem; margin-bottom:1rem; }
.ms-warning .material-symbols-outlined { font-size:1.05rem; flex-shrink:0; color:#f9a825; margin-top:1px; }
.ms-empty { text-align:center; padding:2rem 1rem; color:#999; font-size:.9rem; }
.ms-section-title { font-size:0.85rem; font-weight:700; color:#8C701C; text-transform:uppercase; letter-spacing:0.05em; margin:1.5rem 0 0.5rem; }

/* ── Report modal (emergency + daily) ────────────────────────────── */
.rpt-overlay {
    display:none; position:fixed; inset:0; background:rgba(0,0,0,.45);
    z-index:1200; align-items:center; justify-content:center;
}
.rpt-overlay.open { display:flex; }
.rpt-modal {
    background:#fff; border-radius:14px; width:min(420px,95vw);
    max-height:90vh; display:flex; flex-direction:column;
    box-shadow:0 8px 32px rgba(0,0,0,.28); overflow:hidden;
    transition: width .2s ease;
}
.rpt-modal.wide { width:min(720px,96vw); }
.rpt-sum-row-label { font-weight:600; font-size:.82rem; white-space:nowrap; }
.rpt-modal-hdr {
    display:flex; align-items:center; gap:.6rem;
    background:#c0392b; color:#fff; padding:.85rem 1rem;
}
.rpt-modal-hdr .rpt-hdr-back {
    background:none; border:none; color:#fff; cursor:pointer; padding:2px;
    display:flex; align-items:center; border-radius:50%;
}
.rpt-modal-hdr .rpt-hdr-back:hover { background:rgba(255,255,255,.2); }
.rpt-modal-hdr .rpt-hdr-back .material-symbols-outlined { font-size:20px; }
.rpt-modal-title { flex:1; font-weight:700; font-size:.95rem; }
.rpt-modal-hdr .rpt-hdr-close {
    background:none; border:none; color:#fff; cursor:pointer; padding:2px;
    display:flex; align-items:center; border-radius:50%;
}
.rpt-modal-hdr .rpt-hdr-close:hover { background:rgba(255,255,255,.2); }
.rpt-modal-hdr .rpt-hdr-close .material-symbols-outlined { font-size:20px; }
.rpt-overlay[data-type="daily"] .rpt-modal-hdr { background:#8C701C; }
.rpt-modal-body { flex:1; overflow-y:auto; padding:1rem 1.1rem; }
.rpt-cal-nav { display:flex; align-items:center; justify-content:space-between; margin-bottom:.7rem; }
.rpt-cal-nav button { background:none; border:1px solid #ddd; border-radius:6px; padding:3px 7px; cursor:pointer; display:flex; align-items:center; }
.rpt-cal-nav button:hover { background:#f5f0e4; }
.rpt-cal-nav button .material-symbols-outlined { font-size:18px; }
.rpt-cal-month { font-size:.92rem; font-weight:700; color:#3a2a0a; }
.rpt-cal-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:2px; text-align:center; }
.rpt-dow { font-size:.72rem; font-weight:700; color:#888; padding:4px 0; }
.rpt-cal-day { font-size:.84rem; padding:7px 3px; border-radius:50%; cursor:default; line-height:1; aspect-ratio:1/1; display:flex; align-items:center; justify-content:center; }
.rpt-cal-day.has-report { background:#c0392b; color:#fff; cursor:pointer; font-weight:700; }
.rpt-cal-day.has-report:hover { background:#a93226; }
.rpt-cal-day.is-today { border:2px solid #c0392b; color:#c0392b; font-weight:700; }
.rpt-cal-day.has-report.is-today { border-color:#fff; }
.rpt-overlay[data-type="daily"] .rpt-cal-day.has-report { background:#8C701C; }
.rpt-overlay[data-type="daily"] .rpt-cal-day.has-report:hover { background:#6e570f; }
.rpt-overlay[data-type="daily"] .rpt-cal-day.is-today { border-color:#8C701C; color:#8C701C; }
.rpt-legend { display:flex; gap:1rem; margin-top:.8rem; justify-content:center; font-size:.76rem; color:#666; }
.rpt-legend-item { display:flex; align-items:center; gap:.35rem; }
.rpt-dot { width:14px; height:14px; border-radius:50%; flex-shrink:0; }
.rpt-dot.filled { background:#c0392b; }
.rpt-dot.outline { border:2px solid #c0392b; }
.rpt-overlay[data-type="daily"] .rpt-dot.filled { background:#8C701C; }
.rpt-overlay[data-type="daily"] .rpt-dot.outline { border-color:#8C701C; }
.rpt-detail-badge { display:inline-flex; align-items:center; gap:.4rem; background:#fdecea; color:#c0392b; border:1px solid #f5c6c6; border-radius:6px; padding:.4rem .75rem; font-size:.82rem; font-weight:700; margin-bottom:.8rem; }
.rpt-detail-badge .material-symbols-outlined { font-size:16px; }
.rpt-detail-date { display:flex; align-items:center; gap:.4rem; font-size:.84rem; color:#555; margin-bottom:.8rem; }
.rpt-detail-date .material-symbols-outlined { font-size:16px; color:#888; }
.rpt-detail-section { margin-bottom:.8rem; }
.rpt-detail-section strong { display:block; font-size:.78rem; text-transform:uppercase; color:#666; letter-spacing:.4px; margin-bottom:.3rem; }
.rpt-detail-section ul { margin:.3rem 0 0 1.2rem; padding:0; font-size:.88rem; }
.rpt-detail-section p { font-size:.88rem; margin:0; }
.rpt-detail-meta { font-size:.78rem; color:#888; margin-top:.6rem; }
.rpt-overlay[data-type="daily"] .rpt-detail-badge { background:#fffbee; color:#8C701C; border-color:#d4c185; }
.rpt-form-title { font-weight:700; color:#c0392b; margin-bottom:1rem; font-size:.95rem; }
.rpt-overlay[data-type="daily"] .rpt-form-title { color:#8C701C; }
.rpt-form-group { margin-bottom:.9rem; }
.rpt-form-group label { display:block; font-size:.78rem; font-weight:700; text-transform:uppercase; color:#666; letter-spacing:.4px; margin-bottom:4px; }
.rpt-form-group input[type="date"], .rpt-form-group input[type="number"], .rpt-form-group textarea { width:100%; padding:7px 10px; border:1px solid #ccc; border-radius:6px; font-size:.9rem; font-family:inherit; }
.rpt-form-group textarea { resize:vertical; }
.rpt-causes { border:1px solid #e0e0e0; border-radius:6px; overflow:hidden; }
.rpt-cause-item { display:flex; align-items:center; justify-content:space-between; padding:.6rem .85rem; font-size:.88rem; cursor:pointer; border-bottom:1px solid #f0f0f0; }
.rpt-cause-item:last-child { border-bottom:none; }
.rpt-cause-item:hover { background:#fafafa; }
.rpt-cause-item input[type="checkbox"] { accent-color:#c0392b; width:16px; height:16px; cursor:pointer; }
.rpt-overlay[data-type="daily"] .rpt-cause-item input[type="checkbox"] { accent-color:#8C701C; }
.rpt-modal-footer { padding:.75rem 1rem; border-top:1px solid #ebebeb; display:flex; gap:.6rem; align-items:center; justify-content:flex-end; }
.rpt-btn-back { background:#f5f5f5; border:1px solid #ddd; color:#444; border-radius:6px; padding:7px 14px; font-size:.85rem; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:.3rem; font-family:inherit; }
.rpt-btn-back:hover { background:#ebebeb; }
.rpt-btn-back .material-symbols-outlined { font-size:16px; }
.rpt-btn-new { background:#c0392b; color:#fff; border:none; border-radius:6px; padding:7px 16px; font-size:.85rem; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:.4rem; font-family:inherit; }
.rpt-btn-new:hover { background:#a93226; }
.rpt-btn-new .material-symbols-outlined { font-size:16px; }
.rpt-overlay[data-type="daily"] .rpt-btn-new { background:#8C701C; }
.rpt-overlay[data-type="daily"] .rpt-btn-new:hover { background:#6e570f; }
.rpt-btn-submit { background:#c0392b; color:#fff; border:none; border-radius:6px; padding:7px 18px; font-size:.85rem; font-weight:700; cursor:pointer; font-family:inherit; }
.rpt-btn-submit:hover { background:#a93226; }
.rpt-overlay[data-type="daily"] .rpt-btn-submit { background:#8C701C; }
.rpt-overlay[data-type="daily"] .rpt-btn-submit:hover { background:#6e570f; }

/* ── Daily report detail layout ───────────────────────── */
.rpt-daily-date { font-size:1.15rem; font-weight:800; color:#2c1f07; margin-bottom:.5rem; }
.rpt-daily-row  { font-size:.88rem; color:#555; margin-bottom:.6rem; }
.rpt-daily-section-title { font-weight:700; font-size:.88rem; color:#2c1f07; margin:.7rem 0 .3rem; }
.rpt-daily-crew { margin:.2rem 0 .5rem 1.1rem; padding:0; font-size:.88rem; color:#333; }
.rpt-daily-crew li { margin-bottom:.2rem; }
.rpt-daily-ext  { font-style:italic; color:#999; font-size:.82rem; }
.rpt-daily-completion { font-size:.9rem; color:#2c1f07; font-weight:600; margin:.6rem 0; }
.rpt-daily-divider { border:none; border-top:1px solid #e0e0e0; margin:.8rem 0; }
.rpt-daily-milestone { margin-bottom:.65rem; }
.rpt-daily-ms-name { font-size:.9rem; color:#2c1f07; font-weight:500; }
.rpt-daily-ms-meta { font-size:.82rem; color:#777; margin-top:.1rem; }
/* ── Summary buttons ───────────────────────────────────────── */
.rpt-summary-btn {
    width:100%; display:flex; align-items:center; justify-content:center; gap:.5rem;
    padding:.7rem 1rem; border:1.5px solid #c8a951; border-radius:50px;
    background:#fff; color:#5c3d11; font-size:.9rem; font-weight:700;
    cursor:pointer; font-family:inherit; letter-spacing:.01em;
}
.rpt-summary-btn:hover { background:#fdf8ee; border-color:#8C701C; }
.rpt-summary-btn .material-symbols-outlined { font-size:18px; }
/* ── Summary tables ────────────────────────────────────────── */
.rpt-sum-title { font-size:.95rem; font-weight:700; color:#3a2a0a; margin-bottom:.8rem; display:flex; align-items:center; gap:.4rem; }
.rpt-sum-table { width:100%; border-collapse:collapse; font-size:.82rem; }
.rpt-sum-table th { background:#b8d4e8; color:#1a3a4a; font-weight:700; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; padding:.5rem .6rem; border-bottom:2px solid #9bbfd4; text-align:left; }
.rpt-sum-table td { padding:.5rem .6rem; border-bottom:1px solid #f0e6c8; color:#3a2a0a; }
.rpt-sum-table tr:last-child td { border-bottom:none; }
.rpt-sum-table .num { text-align:right; font-variant-numeric:tabular-nums; }
.rpt-sum-table .pos { color:#2e7d32; font-weight:600; }
.rpt-sum-table .neg { color:#c62828; font-weight:600; }
.rpt-sum-table .muted { color:#aaa; }
.rpt-sum-status { display:inline-block; padding:.1rem .45rem; border-radius:4px; font-size:.72rem; font-weight:600; }
.rpt-sum-status-pending    { background:#f5f5f5; color:#666; }
.rpt-sum-status-inprogress { background:#e3f2fd; color:#1565c0; }
.rpt-sum-status-done       { background:#e8f5e9; color:#2e7d32; }
.rpt-sum-empty { text-align:center; color:#aaa; padding:1.5rem; font-size:.88rem; }
.rpt-sum-overall-hdr { background:#8C701C; color:#fff; border-radius:8px 8px 0 0; padding:.9rem 1rem; text-align:center; margin-bottom:0; }
.rpt-sum-overall-title { font-size:1rem; font-weight:700; }
.rpt-sum-overall-sub { font-size:.82rem; opacity:.85; margin-top:.2rem; }
.rpt-sum-overall-badge { display:inline-block; background:rgba(255,255,255,.2); border-radius:50px; font-size:.78rem; padding:.25rem .75rem; margin-top:.4rem; }
.rpt-sum-ms-heading { font-size:.82rem; font-weight:800; color:#8C701C; text-transform:uppercase; letter-spacing:.06em; margin:.9rem 0 .4rem; }
</style>

<!-- ── Report modal (shared between Emergency + Daily buttons) ──────── -->
<div id="reportModal" class="rpt-overlay" role="dialog" aria-modal="true">
    <div class="rpt-modal">
        <div class="rpt-modal-hdr">
            <button class="rpt-hdr-back" id="rptHdrBack" title="Back" onclick="rptNavBack()">
                <span class="material-symbols-outlined">arrow_back</span>
            </button>
            <span class="rpt-modal-title" id="rptModalTitle">Report</span>
            <button class="rpt-hdr-close" onclick="closeReportModal()" title="Close">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="rpt-modal-body">
            <div id="rptViewCal">
                <div id="rptCalEl"></div>
                <div class="rpt-legend">
                    <span class="rpt-legend-item"><span class="rpt-dot filled"></span>Has report</span>
                    <span class="rpt-legend-item"><span class="rpt-dot outline"></span>Today</span>
                </div>
                <div id="rptSummaryBtns" style="display:none;margin-top:1rem;flex-direction:column;gap:.5rem;">
                    <button class="rpt-summary-btn" onclick="showProgressSummary('weekly')">
                        <span class="material-symbols-outlined">bar_chart</span> Weekly Summary
                    </button>
                    <button class="rpt-summary-btn" onclick="showProgressSummary('overall')">
                        <span class="material-symbols-outlined">table_chart</span> Overall Progress Summary
                    </button>
                </div>
            </div>
            <div id="rptViewDetail" style="display:none"><div id="rptDetailEl"></div></div>
            <div id="rptViewForm"   style="display:none"><div id="rptFormEl"></div></div>
            <div id="rptViewSummary" style="display:none"><div id="rptSummaryEl"></div></div>
        </div>
        <div class="rpt-modal-footer" id="rptFooter"></div>
    </div>
</div>

<div class="pd-shell">

    <!-- Back + Edit bar -->
    <div class="pd-topbar">
        <a href="<?= base_url('projects') ?>" class="pd-back">
            <span class="material-symbols-outlined">arrow_back</span>
            Projects
        </a>
        <?php if (pz_can('edit_project')): ?>
        <a href="<?= base_url('projects/update/' . $projectId) ?>" class="pd-edit-btn">
            <span class="material-symbols-outlined">edit</span> Edit
        </a>
        <?php endif; ?>
    </div>

    <!-- ── Project card ─────────────────────────────────────────── -->
    <div class="pd-project-card">
        <div class="pd-project-card-top">
            <div class="pd-project-name"><?= $pName ?></div>
            <span class="pd-status-badge"
                  style="background:<?= $stStyle['bg'] ?>;color:<?= $stStyle['color'] ?>;border-color:<?= $stStyle['border'] ?>">
                <?= esc($pStatus) ?>
            </span>
        </div>
        <?php if ($pType): ?>
        <div class="pd-project-type"><?= $pType ?></div>
        <?php endif; ?>
        <div class="pd-progress-row">
            <div class="pd-progress-bar-wrap">
                <div class="pd-progress-fill" style="width:<?= min(100, $progress) ?>%"></div>
            </div>
            <div class="pd-progress-pct"><?= number_format($progress, 0) ?>%</div>
        </div>
    </div>

    <!-- ── PROJECT INFO ─────────────────────────────────────────── -->
    <div class="pd-section">
        <div class="pd-section-hdr">Project Info</div>
        <div class="pd-section-body">
            <div class="pd-row">
                <span class="pd-row-label">Client</span>
                <span class="pd-row-value"><?= $client ?></span>
            </div>
            <div class="pd-row">
                <span class="pd-row-label">Location</span>
                <span class="pd-row-value"><?= $address ?></span>
            </div>
            <?php if ($coordStr !== '—'): ?>
            <div class="pd-row">
                <span class="pd-row-label">Coordinates</span>
                <span class="pd-row-value"><?= $coordStr ?></span>
            </div>
            <?php endif; ?>
            <?php if ($region && $region !== '—'): ?>
            <div class="pd-row">
                <span class="pd-row-label">Region</span>
                <span class="pd-row-value"><?= $region ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── SCHEDULE ─────────────────────────────────────────────── -->
    <div class="pd-section">
        <div class="pd-section-hdr">Schedule</div>
        <div class="pd-section-body">
            <div class="pd-row">
                <span class="pd-row-label">Start Date</span>
                <span class="pd-row-value"><?= $startFmt ?></span>
            </div>
            <div class="pd-row">
                <span class="pd-row-label">Projected End Date</span>
                <span class="pd-row-value"><?= $endFmt ?></span>
            </div>
        </div>
    </div>

    <!-- ── BUDGET ───────────────────────────────────────────────── -->
    <div class="pd-section">
        <div class="pd-section-hdr">Budget</div>
        <div class="pd-section-body">
            <div class="pd-row">
                <span class="pd-row-label">Amount</span>
                <span class="pd-row-value"><?= $budgetFmt ?></span>
            </div>
            <?php if ($budgetDoc): ?>
            <div class="pd-row">
                <span class="pd-row-label">Document</span>
                <span class="pd-row-value">
                    <a href="<?= esc($budgetDoc) ?>" target="_blank" rel="noopener">
                        View Budget Document
                    </a>
                </span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── PERSONNEL ────────────────────────────────────────────── -->
    <div class="pd-section">
        <div class="pd-section-hdr">Personnel</div>
        <div class="pd-section-body">
            <div class="pd-row">
                <span class="pd-row-label">Project Manager</span>
                <span class="pd-row-value"><?= $pm ?></span>
            </div>
            <div class="pd-row">
                <span class="pd-row-label">Site Engineer</span>
                <span class="pd-row-value"><?= $se ?></span>
            </div>
        </div>
    </div>

    <!-- ── Action buttons ───────────────────────────────────────── -->
    <div class="pd-actions">
        <a href="<?= base_url('projects/' . $projectId . '/resources') ?>" class="pd-action-btn pd-btn-resources">
            <span class="material-symbols-outlined">inventory_2</span>
            Resources
        </a>
        <a href="<?= base_url('projects/' . $projectId . '/schedule') ?>" class="pd-action-btn pd-btn-schedule">
            <span class="material-symbols-outlined">calendar_month</span>
            Schedule
        </a>
        <button class="pd-action-btn pd-btn-reports"
                onclick="openStandaloneReport('daily','<?= esc($projectId) ?>','<?= $pName ?>')">
            <span class="material-symbols-outlined">description</span>
            Reports
        </button>
        <button class="pd-action-btn pd-btn-emergency"
                onclick="openStandaloneReport('emergency','<?= esc($projectId) ?>','<?= $pName ?>')">
            <span class="material-symbols-outlined">warning</span>
            Emergency
        </button>
    </div>

    <!-- ── Progress tracking (milestones / S-curve) ─────────────── -->
    <?php if (!$hasMilestones): ?>
    <div class="ms-card">
        <div class="ms-empty">
            <span class="material-symbols-outlined" style="font-size:2rem;color:#e8d9a8;display:block;margin-bottom:.5rem">flag</span>
            No milestones defined. Edit the project and add a schedule to enable progress tracking.
        </div>
    </div>

    <?php else: ?>

    <!-- Overall Progress -->
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
    <!-- Weekly Progress Summary -->
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
                <?php $displayWeeks = array_slice($weeklyTargets, -26); ?>
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

    <!-- Milestone Summary -->
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

</div><!-- /.pd-shell -->

<!-- Report modal (reuse dashboard modal JS via inline open calls) -->
<script>
// ── Report modal logic ────────────────────────────────────────────
(function () {
    var _type = '', _pid = '', _pname = '', _state = 'cal';
    var _dates = [], _idMap = {};
    var _calMo = new Date();
    var _summaryType = '', _summaryData = null;

    var _base = '<?= rtrim(base_url(), '/') ?>';
    var CAN_CREATE_EMERGENCY = <?= pz_can('create_emergency_report') ? 'true' : 'false' ?>;
    var CAN_EDIT_PROGRESS    = <?= pz_can('edit_progress_report')    ? 'true' : 'false' ?>;

    window.openReportModal = function (type, pid, pname) {
        _type = type; _pid = pid; _pname = pname;
        _summaryData = null;
        var overlay = document.getElementById('reportModal');
        overlay.setAttribute('data-type', type);
        document.getElementById('rptModalTitle').textContent =
            (type === 'emergency' ? 'Emergency Reports' : 'Progress Reports') + ' — ' + pname;
        _calMo = new Date(); _calMo.setDate(1);
        _dates = []; _idMap = {};
        setState('cal');
        overlay.classList.add('open');
        fetchDates();
    };

    window.openStandaloneReport = function (type, pid, pname) {
        window.openReportModal(type, pid, pname);
    };

    window.closeReportModal = function () {
        document.getElementById('reportModal').classList.remove('open');
    };

    window.rptNavBack = function () {
        if (_state === 'cal') { closeReportModal(); }
        else {
            document.getElementById('rptModalTitle').textContent =
                (_type === 'emergency' ? 'Emergency Reports' : 'Progress Reports') + ' — ' + _pname;
            setState('cal');
        }
    };

    function setState(s) {
        _state = s;
        document.getElementById('rptViewCal').style.display     = (s === 'cal')     ? '' : 'none';
        document.getElementById('rptViewDetail').style.display  = (s === 'detail')  ? '' : 'none';
        document.getElementById('rptViewForm').style.display    = (s === 'form')    ? '' : 'none';
        document.getElementById('rptViewSummary').style.display = (s === 'summary') ? '' : 'none';
        // Widen modal for summary tables
        var modal = document.querySelector('#reportModal .rpt-modal');
        if (modal) modal.classList.toggle('wide', s === 'summary');
        var sb = document.getElementById('rptSummaryBtns');
        if (sb) sb.style.display = (s === 'cal' && _type === 'daily') ? 'flex' : 'none';
        renderFooter();
    }

    function renderFooter() {
        var f = document.getElementById('rptFooter');
        var html = '';
        if (_state !== 'cal') {
            html += '<button class="rpt-btn-back" onclick="rptNavBack()"><span class="material-symbols-outlined">arrow_back</span>Back</button>';
        }
        if (_state === 'cal') {
            var canCreate = (_type === 'emergency' && CAN_CREATE_EMERGENCY) ||
                            (_type === 'daily'     && CAN_EDIT_PROGRESS);
            if (canCreate) {
                html += '<button class="rpt-btn-new" onclick="showNewReportForm()"><span class="material-symbols-outlined">add</span>New Report</button>';
            }
        }
        if (_state === 'form') {
            html += '<button class="rpt-btn-submit" onclick="submitReport()">Submit</button>';
        }
        f.innerHTML = html;
    }

    function fetchDates() {
        var url = _base + '/projects/' + _pid +
                  (_type === 'emergency' ? '/emergency-reports/json' : '/daily-reports/json');
        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                _dates = []; _idMap = {};
                if (_type === 'emergency') {
                    (d.reports || []).forEach(function (r) {
                        if (r.date) { _dates.push(r.date); _idMap[r.date] = r.id; }
                    });
                } else {
                    _dates = d.dates || [];
                }
                renderCal();
            })
            .catch(function () { _dates = []; renderCal(); });
    }

    function pad(n) { return String(n).padStart(2, '0'); }

    function renderCal() {
        var year  = _calMo.getFullYear();
        var month = _calMo.getMonth();
        var today = new Date(); today.setHours(0, 0, 0, 0);
        var monthLabel = _calMo.toLocaleString('default', { month: 'long', year: 'numeric' });

        var html = '<div class="rpt-cal-nav">';
        html += '<button onclick="rptPrevMo()"><span class="material-symbols-outlined">chevron_left</span></button>';
        html += '<span class="rpt-cal-month">' + monthLabel + '</span>';
        html += '<button onclick="rptNextMo()"><span class="material-symbols-outlined">chevron_right</span></button>';
        html += '</div><div class="rpt-cal-grid">';
        ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(function (d) {
            html += '<div class="rpt-dow">' + d + '</div>';
        });
        var firstDay = new Date(year, month, 1).getDay();
        for (var i = 0; i < firstDay; i++) html += '<div></div>';
        var days = new Date(year, month + 1, 0).getDate();
        for (var d = 1; d <= days; d++) {
            var ds     = year + '-' + pad(month + 1) + '-' + pad(d);
            var hasRpt = _dates.indexOf(ds) !== -1;
            var isTd   = new Date(year, month, d).getTime() === today.getTime();
            var cls    = 'rpt-cal-day' + (hasRpt ? ' has-report' : '') + (isTd ? ' is-today' : '');
            if (hasRpt) {
                html += '<div class="' + cls + '" onclick="viewReport(\'' + ds + '\')">' + d + '</div>';
            } else {
                html += '<div class="' + cls + '">' + d + '</div>';
            }
        }
        html += '</div>';
        document.getElementById('rptCalEl').innerHTML = html;
    }

    window.rptPrevMo = function () { _calMo.setMonth(_calMo.getMonth() - 1); renderCal(); };
    window.rptNextMo = function () { _calMo.setMonth(_calMo.getMonth() + 1); renderCal(); };

    window.viewReport = function (dateStr) {
        var segment = (_type === 'emergency' && _idMap[dateStr]) ? _idMap[dateStr] : dateStr;
        var url = _base + '/projects/' + _pid +
                  (_type === 'emergency' ? '/emergency-reports/' : '/daily-reports/') + segment;
        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) { renderDetail(d, dateStr); setState('detail'); })
            .catch(function () { alert('Failed to load report.'); });
    };

    function h(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str || '')));
        return div.innerHTML;
    }

    function renderDetail(d, dateStr) {
        var fmtDate = new Date(dateStr + 'T00:00:00').toLocaleDateString('en-US',
            { year: 'numeric', month: 'long', day: 'numeric' });
        var html = '';
        if (_type === 'emergency') {
            html += '<div class="rpt-detail-badge"><span class="material-symbols-outlined">warning</span>Emergency — ' + h(_pname) + '</div>';
            html += '<div class="rpt-detail-date"><span class="material-symbols-outlined">calendar_today</span>Date: ' + fmtDate + '</div>';
            var causes = d.causes || [];
            if (causes.length) {
                html += '<div class="rpt-detail-section"><strong>Cause(s)</strong><ul>';
                causes.forEach(function (c) {
                    var label = h(c);
                    if (c === 'Other' && d.other_cause) label += ': ' + h(d.other_cause);
                    html += '<li>' + label + '</li>';
                });
                html += '</ul></div>';
            }
            if (d.notes)    html += '<div class="rpt-detail-section"><strong>Notes</strong><p>' + h(d.notes) + '</p></div>';
            if (d.filed_by) html += '<div class="rpt-detail-meta">Filed by: ' + h(d.filed_by) + '</div>';
        } else {
            // Date header
            html += '<div class="rpt-daily-date">' + fmtDate + '</div>';

            // Weather
            if (d.weather) {
                html += '<div class="rpt-daily-row">Weather: ' + h(d.weather) + '</div>';
            }

            // Manpower
            var crew = d.manpower || [];
            if (crew.length) {
                html += '<div class="rpt-daily-section-title">Manpower</div><ul class="rpt-daily-crew">';
                crew.forEach(function (m) {
                    html += '<li>' + h(m.name);
                    if (m.external) html += ' <span class="rpt-daily-ext">(not part of assignment)</span>';
                    html += '</li>';
                });
                html += '</ul>';
            } else if (d.manpower_display) {
                html += '<div class="rpt-daily-section-title">Manpower</div>';
                html += '<div class="rpt-daily-row">' + h(d.manpower_display) + '</div>';
            }

            // Total Completion
            if (d.progress_percent != null) {
                html += '<div class="rpt-daily-completion">Total Completion: ' + d.progress_percent + '%</div>';
            }

            // Milestone Progress
            var ms = d.milestone_progress || [];
            if (ms.length) {
                html += '<hr class="rpt-daily-divider">';
                html += '<div class="rpt-daily-section-title">Milestone Progress</div>';
                ms.forEach(function (m) {
                    var label = h(m.name) + (m.unit ? ' (' + h(m.unit) + ')' : '');
                    var todayVal = (m.today >= 0 ? '+' : '') + m.today;
                    html += '<div class="rpt-daily-milestone">';
                    html += '<div class="rpt-daily-ms-name">' + label + '</div>';
                    html += '<div class="rpt-daily-ms-meta">Today: ' + todayVal +
                            ' &bull; Total: ' + m.total +
                            ' &bull; ' + m.percent + '%</div>';
                    html += '</div>';
                });
            }

            // Notes / description
            if (d.description) {
                html += '<div class="rpt-daily-section-title" style="margin-top:.8rem">Notes</div>';
                html += '<div class="rpt-daily-row">' + h(d.description) + '</div>';
            }

            if (d.filed_by) html += '<div class="rpt-detail-meta" style="margin-top:.6rem">Filed by: ' + h(d.filed_by) + '</div>';
        }
        document.getElementById('rptDetailEl').innerHTML = html;
    }

    window.showNewReportForm = function () {
        var today = new Date().toISOString().slice(0, 10);
        var html  = '<div class="rpt-form-title">' +
                    (_type === 'emergency' ? 'Emergency' : 'Progress Report') +
                    ' for ' + h(_pname) + '</div>';
        html += '<form id="rptNewForm">';
        html += '<div class="rpt-form-group"><label>Date *</label>' +
                '<input type="date" name="date" value="' + today + '" required></div>';
        if (_type === 'emergency') {
            var causes = ['Adverse Weather','Material Shortage','Equipment/Tool Breakdown',
                          'Labor Shortage','Onsite Accidents','Other'];
            html += '<div class="rpt-form-group"><label>Cause *</label><div class="rpt-causes">';
            causes.forEach(function (c) {
                html += '<label class="rpt-cause-item">' + h(c) +
                        '<input type="checkbox" name="causes[]" value="' + h(c) + '"></label>';
            });
            html += '</div></div>';
            html += '<div class="rpt-form-group"><label>Notes</label>' +
                    '<textarea name="notes" rows="4" placeholder="Describe the emergency specifics..."></textarea></div>';
        } else {
            html += '<div class="rpt-form-group"><label>Progress %</label>' +
                    '<input type="number" name="progress_percent" min="0" max="100" step="1" placeholder="0–100"></div>';
            html += '<div class="rpt-form-group"><label>Description</label>' +
                    '<textarea name="description" rows="4" placeholder="Describe the day\'s progress..."></textarea></div>';
        }
        html += '</form>';
        document.getElementById('rptFormEl').innerHTML = html;
        setState('form');
    };

    window.submitReport = function () {
        var form = document.getElementById('rptNewForm');
        if (!form) return;
        var fd = new FormData(form);
        var csrfName = (document.querySelector('meta[name="csrf-name"]') || {}).content;
        var csrfHash = (document.querySelector('meta[name="csrf-hash"]') || {}).content;
        if (csrfName && csrfHash) fd.append(csrfName, csrfHash);
        var url = _base + '/projects/' + _pid +
                  (_type === 'emergency' ? '/emergency-reports' : '/daily-reports');
        fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.success) { fetchDates(); setState('cal'); }
                else alert(d.error || 'Failed to save report.');
            })
            .catch(function () { alert('Failed to save report.'); });
    };

    // ── Summary (Weekly / Overall) ────────────────────────────────────────
    window.showProgressSummary = function (type) {
        _summaryType = type;
        if (_summaryData) { _renderSummary(); return; }
        fetch(_base + '/projects/' + _pid + '/progress-summary', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) { _summaryData = d; _renderSummary(); })
            .catch(function () {
                document.getElementById('rptSummaryEl').innerHTML =
                    '<div class="rpt-sum-empty">Unable to load summary data.</div>';
                setState('summary');
            });
    };

    // ── Calculation helpers ──────────────────────────────────────────────
    function _dateDiffDays(a, b) { // b - a in days
        return Math.round((new Date(b + 'T00:00:00') - new Date(a + 'T00:00:00')) / 86400000);
    }
    function _computePlannedPct(milestones, startDate, endDate, reportDate) {
        var points = [{ date: startDate, pct: 0 }];
        var cumW = 0;
        milestones.forEach(function(m) {
            cumW += m.weight_percent;
            if (m.target_date) points.push({ date: m.target_date, pct: Math.min(100, cumW) });
        });
        if (points[points.length-1].pct < 100 && endDate) {
            points.push({ date: endDate, pct: 100 });
        }
        if (!reportDate || reportDate <= points[0].date) return 0;
        if (reportDate >= points[points.length-1].date) return points[points.length-1].pct;
        for (var i = 0; i < points.length - 1; i++) {
            if (points[i].date <= reportDate && reportDate < points[i+1].date) {
                var span = _dateDiffDays(points[i].date, points[i+1].date);
                var t    = span > 0 ? _dateDiffDays(points[i].date, reportDate) / span : 0;
                return points[i].pct + t * (points[i+1].pct - points[i].pct);
            }
        }
        return 0;
    }
    function _fmtPct(v, dec) { return (dec === undefined ? 2 : dec); } // unused, inlined below
    function _pct(v, dec) { return typeof v === 'number' ? v.toFixed(dec !== undefined ? dec : 2) + ' %' : '—'; }
    function _vari(v, dec) {
        if (typeof v !== 'number') return '<span class="muted">—</span>';
        var cls = v < -0.005 ? 'neg' : (v > 0.005 ? 'pos' : '');
        return '<span class="' + cls + '">' + (v >= 0 ? '+' : '') + v.toFixed(dec !== undefined ? dec : 2) + ' %</span>';
    }
    function _fmtDate(ds) {
        if (!ds) return '—';
        return new Date(ds + 'T00:00:00').toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' });
    }
    function _fmtWeekDate(ds) {
        if (!ds) return '';
        var dt = new Date(ds + 'T00:00:00');
        var day = String(dt.getDate()).padStart(2, '0');
        var mon = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][dt.getMonth()];
        var yr  = String(dt.getFullYear()).slice(2);
        return day + '-' + mon + '-' + yr;
    }

    function _renderSummary() {
        var d = _summaryData || {};
        var html = '';
        var reportDate    = d.reportDate    || new Date().toISOString().slice(0,10);
        var overallProgress = typeof d.overallProgress === 'number' ? d.overallProgress : 0;
        var todayFmt = new Date().toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' });

        if (_summaryType === 'weekly') {
            // ── Weekly Summary ────────────────────────────────────────────
            var targets = d.weeklyTargets || [];
            if (!targets.length) {
                html += '<div class="rpt-sum-empty">No weekly schedule data available. Open this project\'s Schedule screen on mobile and save to enable this view.</div>';
            } else {
                html += '<div class="rpt-sum-title">Cumulative Weekly Progress Target Summary</div>';
                html += '<table class="rpt-sum-table">';
                html += '<thead><tr>' +
                        '<th>Week No.</th>' +
                        '<th class="num">Running Cumulative %</th>' +
                        '<th class="num">Cumulative Target %</th>' +
                        '<th class="num">Variance (+/–)</th>' +
                        '</tr></thead><tbody>';

                targets.forEach(function(wt, i) {
                    var weekNum  = (wt.week !== undefined && wt.week !== null) ? wt.week : (i + 1);
                    var weekDate = wt.week_start ? _fmtWeekDate(wt.week_start) : '';
                    var weekLabel = 'Week ' + weekNum + (weekDate ? ' (' + weekDate + ')' : '');

                    var hasActual = wt.actual_percent !== null && wt.actual_percent !== undefined;
                    var actualPct = hasActual ? Number(wt.actual_percent) : null;
                    var targetPct = Number(wt.target_percent || 0);
                    var variance  = hasActual ? (actualPct - targetPct) : null;

                    html += '<tr>';
                    html += '<td>' + h(weekLabel) + '</td>';
                    html += '<td class="num">' + (actualPct !== null ? _pct(actualPct, 2) : '') + '</td>';
                    html += '<td class="num">' + _pct(targetPct, 2) + '</td>';
                    html += '<td class="num">' + (variance !== null ? _vari(variance, 2) : '') + '</td>';
                    html += '</tr>';
                });

                html += '</tbody></table>';
            }

        } else {
            // ── Overall Progress / Milestone Summary ──────────────────────
            var milestones  = d.milestones || [];
            var totalMs     = milestones.length;
            var scheduledMs = milestones.filter(function(m){ return m.target_date; }).length;

            var plannedPct  = (d.startDate)
                ? _computePlannedPct(milestones, d.startDate, d.endDate, reportDate)
                : null;
            var variance    = (plannedPct !== null) ? overallProgress - plannedPct : null;

            html += '<div class="rpt-sum-overall-hdr">';
            html += '<div class="rpt-sum-overall-title">General Progress Accomplishment %</div>';
            html += '<div class="rpt-sum-overall-sub">As of ' + todayFmt + '</div>';
            if (totalMs > 0) {
                html += '<div class="rpt-sum-overall-badge">' + scheduledMs + ' of ' + totalMs + ' milestone' + (totalMs !== 1 ? 's' : '') + ' scheduled</div>';
            }
            html += '</div>';

            // Progress to date summary row
            html += '<table class="rpt-sum-table" style="margin-bottom:.8rem">';
            html += '<thead><tr style="background:#b8d4e8">' +
                    '<th>ITEM</th>' +
                    '<th class="num">PLANNED %<br><small style="font-weight:400;text-transform:none">(scheduled scope)</small></th>' +
                    '<th class="num">ACTUAL %</th>' +
                    '<th class="num">VARIANCE (+/–)</th>' +
                    '</tr></thead><tbody>';
            html += '<tr><td style="font-weight:700">PROGRESS TO DATE</td>' +
                    '<td class="num">' + (plannedPct !== null ? _pct(plannedPct, 1) : '—') + '</td>' +
                    '<td class="num">' + _pct(overallProgress, 2) + '</td>' +
                    '<td class="num">' + (variance !== null ? _vari(variance, 2) : '—') + '</td>' +
                    '</tr></tbody></table>';

            // Milestone sub-table
            html += '<div class="rpt-sum-ms-heading">MILESTONE SUMMARY</div>';
            if (!milestones.length) {
                html += '<div class="rpt-sum-empty">No milestones defined.</div>';
            } else {
                html += '<table class="rpt-sum-table">';
                html += '<thead><tr style="background:#b8d4e8">' +
                        '<th>MILESTONE</th><th>TARGET DATE</th>' +
                        '<th class="num">PLANNED %</th><th class="num">ACTUAL %</th>' +
                        '<th class="num">VARIANCE</th><th>STATUS</th>' +
                        '</tr></thead><tbody>';
                milestones.forEach(function (m) {
                    var stLow  = (m.status || '').toLowerCase();
                    var isDone = stLow === 'completed';
                    var isIP   = stLow === 'in progress';
                    var stLbl  = isDone ? 'Completed' : (isIP ? 'In Progress' : 'Not Started');
                    var stCls  = isDone ? 'rpt-sum-status-done' : (isIP ? 'rpt-sum-status-inprogress' : 'rpt-sum-status-pending');

                    var hasTD      = !!m.target_date;
                    var tdPassed   = hasTD && m.target_date <= reportDate;
                    var actualPct  = m.completion_percent * m.weight_percent / 100;
                    var plannedRow = hasTD ? m.weight_percent : null;
                    var varRow     = (tdPassed && plannedRow !== null) ? actualPct - plannedRow : null;

                    html += '<tr>';
                    html += '<td>' + h(m.milestone_name) + '</td>';
                    html += '<td>' + _fmtDate(m.target_date) + '</td>';
                    html += '<td class="num">' + (plannedRow !== null ? _pct(plannedRow, 1) : '—') + '</td>';
                    html += '<td class="num">' + _pct(actualPct, 1) + '</td>';
                    html += '<td class="num">' + (varRow !== null ? _vari(varRow, 2) : '<span class="muted">—</span>') + '</td>';
                    html += '<td><span class="rpt-sum-status ' + stCls + '">' + stLbl + '</span></td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
            }
        }

        document.getElementById('rptSummaryEl').innerHTML = html;
        setState('summary');
        document.getElementById('rptModalTitle').textContent =
            (_summaryType === 'weekly' ? 'Weekly Summary' : 'Overall Progress') + ' — ' + _pname;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var overlay = document.getElementById('reportModal');
        if (overlay) overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeReportModal();
        });
    });
}());
</script>

<?= $this->endSection() ?>
