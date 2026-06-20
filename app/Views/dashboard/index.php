<?= $this->extend('layouts/main') ?>

<?= $this->section('head') ?>
<?php if (!empty($googleMapsBrowserKey)): ?>
<script>
window.__monitorMapKey = '<?= esc($googleMapsBrowserKey ?? '') ?>';
</script>
<?php endif; ?>
<meta name="csrf-name" content="<?= csrf_token() ?>">
<meta name="csrf-hash" content="<?= csrf_hash() ?>">
<style>
/* ── Report modal overlay ─────────────────────────────────────────────── */
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

/* progress report modal header is golden */
.rpt-overlay[data-type="daily"] .rpt-modal-hdr { background:#8C701C; }

.rpt-modal-body { flex:1; overflow-y:auto; padding:1rem 1.1rem; }

/* Calendar */
.rpt-cal-nav {
    display:flex; align-items:center; justify-content:space-between;
    margin-bottom:.7rem;
}
.rpt-cal-nav button {
    background:none; border:1px solid #ddd; border-radius:6px; padding:3px 7px;
    cursor:pointer; display:flex; align-items:center;
}
.rpt-cal-nav button:hover { background:#f5f0e4; }
.rpt-cal-nav button .material-symbols-outlined { font-size:18px; }
.rpt-cal-month { font-size:.92rem; font-weight:700; color:#3a2a0a; }
.rpt-cal-grid {
    display:grid; grid-template-columns:repeat(7,1fr); gap:2px;
    text-align:center;
}
.rpt-dow { font-size:.72rem; font-weight:700; color:#888; padding:4px 0; }
.rpt-cal-day {
    font-size:.84rem; padding:7px 3px; border-radius:50%; cursor:default;
    line-height:1; aspect-ratio:1/1; display:flex; align-items:center; justify-content:center;
}
.rpt-cal-day.has-report {
    background:#c0392b; color:#fff; cursor:pointer; font-weight:700;
}
.rpt-cal-day.has-report:hover { background:#a93226; }
.rpt-cal-day.is-today {
    border:2px solid #c0392b; color:#c0392b; font-weight:700;
}
.rpt-cal-day.has-report.is-today { border-color:#fff; }

/* golden for progress */
.rpt-overlay[data-type="daily"] .rpt-cal-day.has-report { background:#8C701C; }
.rpt-overlay[data-type="daily"] .rpt-cal-day.has-report:hover { background:#6e570f; }
.rpt-overlay[data-type="daily"] .rpt-cal-day.is-today { border-color:#8C701C; color:#8C701C; }

.rpt-legend {
    display:flex; gap:1rem; margin-top:.8rem; justify-content:center;
    font-size:.76rem; color:#666;
}
.rpt-legend-item { display:flex; align-items:center; gap:.35rem; }
.rpt-dot {
    width:14px; height:14px; border-radius:50%; flex-shrink:0;
}
.rpt-dot.filled { background:#c0392b; }
.rpt-dot.outline { border:2px solid #c0392b; }
.rpt-overlay[data-type="daily"] .rpt-dot.filled { background:#8C701C; }
.rpt-overlay[data-type="daily"] .rpt-dot.outline { border-color:#8C701C; }

/* Detail view */
.rpt-detail-badge {
    display:inline-flex; align-items:center; gap:.4rem;
    background:#fdecea; color:#c0392b; border:1px solid #f5c6c6;
    border-radius:6px; padding:.4rem .75rem; font-size:.82rem; font-weight:700; margin-bottom:.8rem;
}
.rpt-detail-badge .material-symbols-outlined { font-size:16px; }
.rpt-detail-date {
    display:flex; align-items:center; gap:.4rem;
    font-size:.84rem; color:#555; margin-bottom:.8rem;
}
.rpt-detail-date .material-symbols-outlined { font-size:16px; color:#888; }
.rpt-detail-section { margin-bottom:.8rem; }
.rpt-detail-section strong { display:block; font-size:.78rem; text-transform:uppercase; color:#666; letter-spacing:.4px; margin-bottom:.3rem; }
.rpt-detail-section ul { margin:.3rem 0 0 1.2rem; padding:0; font-size:.88rem; }
.rpt-detail-section p { font-size:.88rem; margin:0; }
.rpt-detail-meta { font-size:.78rem; color:#888; margin-top:.6rem; }
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
.rpt-sum-title .material-symbols-outlined { font-size:1.1rem; }
.rpt-sum-metrics { display:grid; grid-template-columns:repeat(2,1fr); gap:.6rem; margin-bottom:1rem; }
.rpt-sum-metric { background:#fdf8ee; border-radius:8px; padding:.6rem .8rem; text-align:center; }
.rpt-sum-metric-label { font-size:.68rem; font-weight:700; text-transform:uppercase; color:#8c701c; letter-spacing:.04em; margin-bottom:.2rem; }
.rpt-sum-metric-value { font-size:1.2rem; font-weight:800; color:#3a2a0a; }
.rpt-sum-metric-value.pos { color:#2e7d32; }
.rpt-sum-metric-value.neg { color:#c62828; }
.rpt-sum-table { width:100%; border-collapse:collapse; font-size:.82rem; }
.rpt-sum-table th { background:#fdf8ee; color:#8c701c; font-weight:700; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; padding:.5rem .6rem; border-bottom:2px solid #e8d9a8; text-align:left; }
.rpt-sum-table td { padding:.5rem .6rem; border-bottom:1px solid #f0e6c8; color:#3a2a0a; }
.rpt-sum-table tr:last-child td { border-bottom:none; }
.rpt-sum-table .num { text-align:right; font-variant-numeric:tabular-nums; }
.rpt-sum-table .pos { color:#2e7d32; font-weight:600; }
.rpt-sum-table .neg { color:#c62828; font-weight:600; }
.rpt-sum-table .muted { color:#aaa; }
.rpt-sum-ms-dot { display:inline-block; width:6px; height:6px; border-radius:50%; background:#c8a951; margin-right:3px; vertical-align:middle; }
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

/* Progress detail badge is golden */
.rpt-overlay[data-type="daily"] .rpt-detail-badge { background:#fffbee; color:#8C701C; border-color:#d4c185; }

/* New report form */
.rpt-form-title { font-weight:700; color:#c0392b; margin-bottom:1rem; font-size:.95rem; }
.rpt-overlay[data-type="daily"] .rpt-form-title { color:#8C701C; }
.rpt-form-group { margin-bottom:.9rem; }
.rpt-form-group label { display:block; font-size:.78rem; font-weight:700; text-transform:uppercase; color:#666; letter-spacing:.4px; margin-bottom:4px; }
.rpt-form-group input[type="date"],
.rpt-form-group input[type="number"],
.rpt-form-group textarea {
    width:100%; padding:7px 10px; border:1px solid #ccc; border-radius:6px;
    font-size:.9rem; font-family:inherit;
}
.rpt-form-group textarea { resize:vertical; }
.rpt-causes { border:1px solid #e0e0e0; border-radius:6px; overflow:hidden; }
.rpt-cause-item {
    display:flex; align-items:center; justify-content:space-between;
    padding:.6rem .85rem; font-size:.88rem; cursor:pointer;
    border-bottom:1px solid #f0f0f0;
}
.rpt-cause-item:last-child { border-bottom:none; }
.rpt-cause-item:hover { background:#fafafa; }
.rpt-cause-item input[type="checkbox"] { accent-color:#c0392b; width:16px; height:16px; cursor:pointer; }
.rpt-overlay[data-type="daily"] .rpt-cause-item input[type="checkbox"] { accent-color:#8C701C; }

/* Modal footer */
.rpt-modal-footer {
    padding:.75rem 1rem; border-top:1px solid #ebebeb;
    display:flex; gap:.6rem; align-items:center; justify-content:flex-end;
}
.rpt-btn-back {
    background:#f5f5f5; border:1px solid #ddd; color:#444;
    border-radius:6px; padding:7px 14px; font-size:.85rem; font-weight:600;
    cursor:pointer; display:flex; align-items:center; gap:.3rem; font-family:inherit;
}
.rpt-btn-back:hover { background:#ebebeb; }
.rpt-btn-back .material-symbols-outlined { font-size:16px; }
.rpt-btn-new {
    background:#c0392b; color:#fff; border:none; border-radius:6px;
    padding:7px 16px; font-size:.85rem; font-weight:700; cursor:pointer;
    display:flex; align-items:center; gap:.4rem; font-family:inherit;
}
.rpt-btn-new:hover { background:#a93226; }
.rpt-btn-new .material-symbols-outlined { font-size:16px; }
.rpt-overlay[data-type="daily"] .rpt-btn-new { background:#8C701C; }
.rpt-overlay[data-type="daily"] .rpt-btn-new:hover { background:#6e570f; }
.rpt-btn-submit {
    background:#c0392b; color:#fff; border:none; border-radius:6px;
    padding:7px 18px; font-size:.85rem; font-weight:700; cursor:pointer; font-family:inherit;
}
.rpt-btn-submit:hover { background:#a93226; }
.rpt-overlay[data-type="daily"] .rpt-btn-submit { background:#8C701C; }
.rpt-overlay[data-type="daily"] .rpt-btn-submit:hover { background:#6e570f; }

/* Dashboard card footer icon buttons */
.db-icon-btn {
    background:none; border:none; cursor:pointer; padding:2px; color:var(--db-orange);
    display:inline-flex; align-items:center; border-radius:4px; font-family:inherit;
}
.db-icon-btn:hover { color:#3a2a0a; background:#f0ebe0; }
.db-icon-btn .material-symbols-outlined {
    font-size:20px;
    font-variation-settings:'FILL' 1,'wght' 600;
}
.db-icon-btn.db-icon-emergency { color:#c0392b; }
.db-icon-btn.db-icon-emergency .material-symbols-outlined {
    font-variation-settings:'FILL' 0,'wght' 400;
}
.db-icon-btn.db-icon-emergency:hover { color:#922b21; background:#fdecea; }
.db-icon-btn.db-icon-report:hover { color:#6e570f; }
.db-icon-chat {
    font-variation-settings:'FILL' 1,'wght' 600 !important;
}
/* View project pill link (monitor cards only) */
.db-view-project-link {
    display:block; width:100%;
    border:1.5px solid #C47A0F; border-radius:20px;
    padding:5px 12px; margin-bottom:8px;
    text-align:center; text-decoration:none;
    color:#3a2a0a; font-size:0.72rem; font-weight:600; letter-spacing:0.04em;
    display:flex; align-items:center; justify-content:center; gap:4px;
    transition:background .15s, color .15s;
}
.db-view-project-link:hover { background:#f5edda; color:#6e570f; text-decoration:none; }
.db-view-project-link .material-symbols-outlined {
    font-size:13px; font-variation-settings:'FILL' 0,'wght' 400;
    opacity:0.7;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div id="dndToast"></div>

<!-- Transfer confirm modal -->
<div id="dndConfirmOverlay">
    <div id="dndConfirmBox">
        <div class="dnd-modal-title">
            <span class="material-symbols-outlined">sync_alt</span>
            Transfer Resource
        </div>
        <div class="dnd-modal-body">
            Create a resource request to transfer <strong id="dndResLabel"></strong>
            <div class="dnd-modal-label">from</div>
            <div class="dnd-modal-project" id="dndSrcLabel"></div>
            <div class="dnd-modal-label">to</div>
            <div class="dnd-modal-project" id="dndDestLabel"></div>
        </div>
        <div class="dnd-modal-actions">
            <button class="dnd-modal-cancel" id="dndCancelBtn">Cancel</button>
            <button class="dnd-modal-confirm" id="dndConfirmBtn">Confirm Request</button>
        </div>
    </div>
</div>

<?php if (!empty($errorMessage)): ?>
<div class="alert alert-warning mb-3"><?= esc((string)$errorMessage) ?></div>
<?php endif; ?>

<div class="db-root">

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     SUMMARY SECTION
     â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<section id="summary" class="db-section">

    <p class="db-today">Today,</p>
    <p class="db-today-date"><?= date('F j, Y') ?></p>

    <div class="db-info-row">

        <!-- BLOCKERS --------------------------------------- -->
        <div class="db-info-col">
            <div class="db-info-card">
                <div class="db-info-title">Blockers</div>
                <?php if (empty($blockers)): ?>
                    <p class="db-empty">No blockers â€” all projects on track.</p>
                <?php else: ?>
                    <div class="db-blockers-list">
                    <?php foreach (array_slice($blockers, 0, 5) as $bl):
                        $endTs     = is_numeric($bl['end_date'] ?? '') ? (int)$bl['end_date'] : @strtotime((string)($bl['end_date'] ?? ''));
                        $delayDays = ($endTs && $endTs < time()) ? (int)floor((time() - $endTs) / 86400) : 0;
                        $reason    = $bl['reason'] ?? $bl['blocker_reason'] ?? (($bl['status'] ?? '') === 'Halted' ? 'Project Halted' : 'Low Progress');
                    ?>
                    <div class="db-blocker-box">
                        <span class="db-blocker-name"><?= esc(strtoupper($bl['project_name'] ?? 'Project')) ?></span>
                        <?php if ($delayDays > 0): ?>
                        <span class="db-blocker-sep">Â·</span>
                        <span class="db-blocker-line"><?= $delayDays ?>d delay</span>
                        <?php endif; ?>
                        <span class="db-blocker-sep">Â·</span>
                        <span class="db-blocker-line"><?= esc(ucwords((string)$reason)) ?></span>
                    </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- AVAILABLE TOOLS -------------------------------- -->
        <div class="db-info-col">
            <div class="db-info-card">
                <div class="db-info-title">Available Tools</div>
                <?php if (empty($availableTools)): ?>
                    <p class="db-empty">No tools available.</p>
                <?php else: ?>
                    <table class="db-info-tbl">
                        <thead><tr><th>Name</th><th>Qty</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($availableTools, 0, 8) as $t): ?>
                            <tr>
                                <td><?= esc(strtoupper($t['name'] ?? 'N/A')) ?></td>
                                <td><?= esc((string)($t['asset_details']['available_quantity'] ?? $t['available_units_display'] ?? 'â€”')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- AVAILABLE TO DEPLOY ---------------------------- -->
        <div class="db-info-col">
            <div class="db-info-card">
                <div class="db-info-title">Available to Deploy</div>
                <?php if (empty($availableManpower)): ?>
                    <p class="db-empty">No manpower available.</p>
                <?php else: ?>
                    <table class="db-info-tbl">
                        <thead><tr><th>Name</th><th>Role</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($availableManpower, 0, 8) as $mp): ?>
                            <tr>
                                <td><?= esc(strtoupper($mp['name'] ?? 'N/A')) ?></td>
                                <td><?= esc(strtoupper($mp['manpower_details']['role'] ?? $mp['role'] ?? 'Field Worker')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /db-info-row -->

</section><!-- #summary -->


<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     MONITOR SECTION  â€”  cards (left) + sticky map (right)
     â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<section id="monitor" class="db-section">

    <?php if (empty($projectsByRegion)): ?>
        <p class="db-empty">No active projects to monitor.</p>
    <?php else: ?>

    <?php
    // Build sorted unique client list for the filter dropdown
    $monitorClients = [];
    foreach ($activeProjects as $_mp) {
        $c = $_mp['_client'] ?? '';
        if ($c !== '') $monitorClients[$c] = true;
    }
    ksort($monitorClients);
    $monitorClients = array_keys($monitorClients);
    ?>

    <!-- -- Filter toggle + dropdown -- -->
    <div class="db-filter-wrap">
        <button type="button" class="db-filter-toggle-btn" id="dbFilterToggle">
            <span class="db-filter-arrow">▼</span> Filter
        </button>
        <div class="db-filter-dropdown" id="dbFilterDropdown">
            <div class="db-filter-bar">
                <div class="db-filter-row">
                    <span class="db-filter-label">Region</span>
                    <div class="db-filter-pills" id="dbRegionPills">
                        <button type="button" class="db-filter-pill active" data-region="ALL">All</button>
                        <button type="button" class="db-filter-pill" data-region="LUZON">Luzon</button>
                        <button type="button" class="db-filter-pill" data-region="VISAYAS">Visayas</button>
                        <button type="button" class="db-filter-pill" data-region="MINDANAO">Mindanao</button>
                    </div>
                </div>
                <div class="db-filter-row">
                    <span class="db-filter-label">Category</span>
                    <div class="db-filter-pills" id="dbTypePills">
                        <button type="button" class="db-filter-pill active" data-type="ALL">All</button>
                        <button type="button" class="db-filter-pill" data-type="NEW BUILD">New Build</button>
                        <button type="button" class="db-filter-pill" data-type="RETROFITTING">Retrofitting</button>
                        <button type="button" class="db-filter-pill" data-type="COLOCATION">Colocation</button>
                        <button type="button" class="db-filter-pill" data-type="CONVERSION">Conversion</button>
                    </div>
                </div>
                <div class="db-filter-row">
                    <span class="db-filter-label">Status</span>
                    <div class="db-filter-pills" id="dbStatusPills">
                        <button type="button" class="db-filter-pill active" data-status="ALL">All</button>
                        <button type="button" class="db-filter-pill" data-status="ONGOING">Ongoing</button>
                        <button type="button" class="db-filter-pill" data-status="DONE">Done</button>
                    </div>
                </div>
                <?php if (!empty($monitorClients)): ?>
                <div class="db-filter-row">
                    <span class="db-filter-label">Client</span>
                    <select class="db-filter-client-sel" id="dbClientFilter">
                        <option value="ALL">All Clients</option>
                        <?php foreach ($monitorClients as $cl): ?>
                        <option value="<?= esc($cl) ?>"><?= esc($cl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div><!-- .db-filter-bar -->
        </div><!-- .db-filter-dropdown -->
    </div><!-- .db-filter-wrap -->

    <!-- -- Project card groups -- -->
    <?php foreach ($projectsByRegion as $region => $regionProjects): ?>
    <div class="db-monitor-region" data-region-group="<?= esc($region) ?>">
        <h3 class="db-region-title"><?= esc($region) ?></h3>
        <div class="row g-3 mb-2">
            <?php foreach ($regionProjects as $proj): ?>
            <div class="col-xl-6 col-12 db-monitor-col"
                 data-region="<?= esc($proj['_region']) ?>"
                 data-client="<?= esc($proj['_client'] ?? '') ?>"
                 data-type="<?= esc($proj['_project_type'] ?? '') ?>"
                 data-status="<?= esc($proj['_status'] ?? 'ONGOING') ?>">
                <?php echo db_render_card($proj, 'monitor'); ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="db-monitor-no-results" id="dbNoResults">
        <div style="font-size:1.5rem;margin-bottom:0.5rem;">📋</div>
        No projects match the selected filters.
    </div>

    <?php endif; ?>

</section><!-- #monitor -->

</div><!-- .db-root -->

<!-- ── Report Modal (shared for Emergency + Daily Progress) ──────────── -->
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
            <!-- Calendar state -->
            <div id="rptViewCal">
                <div id="rptCalEl"></div>
                <div class="rpt-legend">
                    <span class="rpt-legend-item"><span class="rpt-dot filled"></span>Has report</span>
                    <span class="rpt-legend-item"><span class="rpt-dot outline"></span>Today</span>
                </div>
                <div id="rptSummaryBtns" style="display:none;margin-top:1rem;display:flex;flex-direction:column;gap:.5rem;">
                    <button class="rpt-summary-btn" onclick="showProgressSummary('weekly')">
                        <span class="material-symbols-outlined">bar_chart</span> Weekly Summary
                    </button>
                    <button class="rpt-summary-btn" onclick="showProgressSummary('overall')">
                        <span class="material-symbols-outlined">table_chart</span> Overall Progress Summary
                    </button>
                </div>
            </div>
            <!-- Detail state -->
            <div id="rptViewDetail" style="display:none">
                <div id="rptDetailEl"></div>
            </div>
            <!-- New report form state -->
            <div id="rptViewForm" style="display:none">
                <div id="rptFormEl"></div>
            </div>
            <!-- Summary state -->
            <div id="rptViewSummary" style="display:none">
                <div id="rptSummaryEl"></div>
            </div>
        </div>
        <div class="rpt-modal-footer" id="rptFooter"></div>
    </div>
</div>

<!-- -- Right map sidebar panel ----------------------------- -->
<div id="mapSidePanel">
    <div class="map-panel-hdr" onclick="toggleMapPanel()">
        <button class="map-panel-toggle" onclick="event.stopPropagation(); toggleMapPanel()" title="Toggle map panel">
            <span class="material-symbols-outlined">chevron_right</span>
        </button>
        <span class="map-panel-hdr-label">
            <span class="material-symbols-outlined">map</span>
            Project Sites
        </span>
        <span class="map-panel-hdr-spacer"></span>
    </div>
    <div id="monitorMap"></div>
    <div class="map-legend">
        <span class="map-legend-item"><span class="map-legend-dot" style="background:#E53935"></span>New Build</span>
        <span class="map-legend-item"><span class="map-legend-dot" style="background:#1E88E5"></span>Retrofitting</span>
        <span class="map-legend-item"><span class="map-legend-dot" style="background:#FDD835"></span>Colocation</span>
        <span class="map-legend-item"><span class="map-legend-dot" style="background:#7B1FA2"></span>Conversion</span>
    </div>
</div>

<?php
/**
 * Render a single project card (shared by Summary and Monitor sections).
 */
function db_render_card(array $p, string $context = 'summary'): string
{
    $name      = esc(strtoupper($p['project_name'] ?? 'Project'));
    $progress  = min(100, max(0, (float)$p['_progress']));
    $startFmt  = esc($p['_start_fmt']);
    $endFmt    = esc($p['_end_fmt']);
    $equipment = (array)($p['_equipment'] ?? []);
    $tools     = (array)($p['_tools']     ?? []);
    $manpower  = (array)($p['_manpower']  ?? []);
    $mapsUrl   = esc($p['_maps_url']);
    $cardId    = 'card-' . preg_replace('/[^a-zA-Z0-9]/', '', (string)($p['id'] ?? uniqid()));
    $lat       = $p['_lat'] ?? null;
    $lng       = $p['_lng'] ?? null;

    ob_start();
    ?>
    <div class="db-project-card" id="<?= $cardId ?>" data-pid="<?= esc($p['id'] ?? '') ?>" data-pname="<?= esc(strtoupper($p['project_name'] ?? '')) ?>">

        <!-- Project name -->
        <div class="db-project-name-box"><?= $name ?></div>

        <?php if ($context === 'monitor'): ?>
        <!-- View project link -->
        <a href="<?= base_url('projects/show/' . esc($p['id'] ?? '')) ?>" class="db-view-project-link">
            <?= $name ?>
            <span class="material-symbols-outlined">open_in_new</span>
        </a>
        <?php endif; ?>

        <!-- Progress bar -->
        <div class="db-progress">
            <div class="db-progress-fill" style="width:<?= $progress ?>%"></div>
            <span class="db-progress-pct"><?= number_format($progress, 0) ?>%</span>
        </div>

        <!-- Dates -->
        <div class="db-dates">
            <div class="db-date-row">
                <span class="db-date-label">Start Date:</span>
                <span class="db-date-val"><?= $startFmt ?></span>
            </div>
            <div class="db-date-row">
                <span class="db-date-label">End Date:</span>
                <span class="db-date-val"><?= $endFmt ?></span>
            </div>
        </div>

        <!-- Resource accordion â€” Equipment / Tools / Manpower -->
        <div class="db-res-accordion">
        <?php
        $resGroups = [
            'equipment' => ['label' => 'Equipment', 'icon' => 'construction', 'items' => $equipment],
            'tools'     => ['label' => 'Tools',     'icon' => 'handyman',     'items' => $tools],
            'manpower'  => ['label' => 'Manpower',  'icon' => 'engineering',  'items' => $manpower],
        ];
        foreach ($resGroups as $cat => $grp):
        ?>
            <div class="db-res-group open" data-cat="<?= $cat ?>">
                <div class="db-res-group-hdr" onclick="dbToggleGroup(this.parentElement)">
                    <span class="material-symbols-outlined db-res-chevron">chevron_right</span>
                    <span class="material-symbols-outlined"><?= $grp['icon'] ?></span>
                    <?= $grp['label'] ?>
                    <span class="db-res-group-count"><?= count($grp['items']) ?></span>
                </div>
                <div class="db-res-group-body">
                    <div class="db-res-drop-zone" data-cat="<?= $cat ?>" data-proj="<?= $cardId ?>" data-pid="<?= esc($p['id'] ?? '') ?>">
                        <?php foreach ($grp['items'] as $res):
                            $resName   = is_array($res) ? ($res['name']    ?? '') : $res;
                            $resId     = is_array($res) ? ($res['id']      ?? '') : '';
                            $resUnitId = is_array($res) ? ($res['unit_id'] ?? '') : '';
                            $isPending = is_array($res) && !empty($res['pending']);
                            $reqId     = is_array($res) ? (string)($res['req_id'] ?? '') : '';
                        ?>
                        <div class="db-pill<?= $isPending ? ' db-pill--pending' : '' ?>"
                             data-res-name="<?= esc($resName) ?>"
                             data-res-id="<?= esc($resId) ?>"
                             data-unit-id="<?= esc($resUnitId) ?>"
                             <?= $isPending ? 'data-pending="1" data-req-id="' . esc($reqId) . '"' : '' ?>>
                            <span class="db-pill-icons">
                                <span class="material-symbols-outlined">drag_indicator</span>
                                <span class="material-symbols-outlined"><?= $grp['icon'] ?></span>
                            </span>
                            <span class="db-pill-name"><?= esc($resName) ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($grp['items'])): ?>
                        <p class="db-empty" style="font-size:0.8rem; padding:0.25rem 0; margin:0;">None assigned</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>

        <!-- Footer actions -->
        <div class="db-card-footer">
            <a href="<?= base_url('messages/project/' . esc($p['id'] ?? '')) ?>"
               class="db-icon-btn" title="Messages" style="text-decoration:none">
                <span class="material-symbols-outlined db-icon-chat">chat_bubble</span>
            </a>
            <?php if ($context === 'monitor'): ?>
            <button class="db-icon-btn db-icon-emergency"
                    data-pid="<?= esc($p['id'] ?? '') ?>"
                    data-pname="<?= $name ?>"
                    onclick="openReportModal('emergency',this.dataset.pid,this.dataset.pname)"
                    title="Emergency Reports">
                <span class="material-symbols-outlined">warning</span>
            </button>
            <button class="db-icon-btn db-icon-report"
                    data-pid="<?= esc($p['id'] ?? '') ?>"
                    data-pname="<?= $name ?>"
                    onclick="openReportModal('daily',this.dataset.pid,this.dataset.pname)"
                    title="Progress Reports">
                <span class="material-symbols-outlined">description</span>
            </button>
            <button class="db-btn-locate"
                    data-lat="<?= $lat !== null ? (float)$lat : '' ?>"
                    data-lng="<?= $lng !== null ? (float)$lng : '' ?>"
                    data-name="<?= esc(strtoupper($p['project_name'] ?? '')) ?>"
                    title="Focus on map">Locate Site</button>
            <?php else: ?>
            <span class="material-symbols-outlined db-icon-warn" title="Warnings">warning</span>
            <a href="<?= $mapsUrl ?>" class="db-btn-locate" target="_blank" rel="noopener">Locate Site</a>
            <?php endif; ?>
        </div>

    </div><!-- .db-project-card -->
    <?php
    return ob_get_clean();
}
?>

<script>
function dbCardToggle(cardId, btn) {
    const card = document.getElementById(cardId);
    if (card) card.classList.toggle('db-card-expanded');
}

function dbToggleGroup(groupEl) {
    groupEl.classList.toggle('open');
}

// -- Filter dropdown toggle ---------------------------------
document.addEventListener('DOMContentLoaded', function () {
    var toggleBtn  = document.getElementById('dbFilterToggle');
    var dropdown   = document.getElementById('dbFilterDropdown');
    if (!toggleBtn || !dropdown) return;

    toggleBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        var isOpen = dropdown.classList.toggle('open');
        toggleBtn.classList.toggle('open', isOpen);
    });

    // Close when clicking outside
    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target) && e.target !== toggleBtn) {
            dropdown.classList.remove('open');
            toggleBtn.classList.remove('open');
        }
    });
});

// -- Monitor filters ----------------------------------------
(function () {
    var activeRegion = 'ALL';
    var activeType   = 'ALL';
    var activeStatus = 'ALL';
    var activeClient = 'ALL';

    function closeDropdown() {
        var dd  = document.getElementById('dbFilterDropdown');
        var btn = document.getElementById('dbFilterToggle');
        if (dd)  dd.classList.remove('open');
        if (btn) btn.classList.remove('open');
    }

    function applyFilters() {
        var anyVisible = false;
        document.querySelectorAll('.db-monitor-region').forEach(function (group) {
            var visibleCols = [];
            group.querySelectorAll('.db-monitor-col').forEach(function (col) {
                var show = (activeRegion === 'ALL' || col.dataset.region === activeRegion) &&
                           (activeType   === 'ALL' || col.dataset.type   === activeType)   &&
                           (activeStatus === 'ALL' || col.dataset.status === activeStatus) &&
                           (activeClient === 'ALL' || col.dataset.client === activeClient);
                col.style.display = show ? '' : 'none';
                if (show) visibleCols.push(col);
            });

            group.style.display = visibleCols.length > 0 ? '' : 'none';
            if (visibleCols.length > 0) anyVisible = true;
        });
        var noResults = document.getElementById('dbNoResults');
        if (noResults) noResults.style.display = anyVisible ? 'none' : 'block';
    }

    function bindPills(containerId, getValue, setter) {
        var container = document.getElementById(containerId);
        if (!container) return;
        container.addEventListener('click', function (e) {
            var pill = e.target.closest('.db-filter-pill');
            if (!pill) return;
            container.querySelectorAll('.db-filter-pill').forEach(function (p) { p.classList.remove('active'); });
            pill.classList.add('active');
            setter(getValue(pill));
            applyFilters();
            closeDropdown();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindPills('dbRegionPills', function (p) { return p.dataset.region; }, function (v) { activeRegion = v; });
        bindPills('dbTypePills',   function (p) { return p.dataset.type;   }, function (v) { activeType   = v; });
        bindPills('dbStatusPills', function (p) { return p.dataset.status; }, function (v) { activeStatus = v; });

        var clientSel = document.getElementById('dbClientFilter');
        if (clientSel) {
            clientSel.addEventListener('change', function () {
                activeClient = this.value;
                applyFilters();
                closeDropdown();
            });
        }
    });
}());

// ── Report Modal ─────────────────────────────────────────────────────────
(function () {
    // Permissions injected from PHP
    var _base = '<?= rtrim(base_url(), '/') ?>';
    var CAN_CREATE_EMERGENCY = <?= pz_can('create_emergency_report') ? 'true' : 'false' ?>;
    var CAN_EDIT_PROGRESS    = <?= pz_can('edit_progress_report')    ? 'true' : 'false' ?>;

    var _type  = ''; // 'emergency' | 'daily'
    var _pid   = '';
    var _pname = '';
    var _dates = [];  // YYYY-MM-DD strings visible on calendar
    var _idMap = {};  // date → docId (emergency reports only; auto-generated IDs)
    var _calMo = new Date(); // currently displayed calendar month

    var _state = 'cal'; // 'cal' | 'detail' | 'form' | 'summary'
    var _summaryType = ''; // 'weekly' | 'overall'
    var _summaryData = null; // cached per modal-open

    window.openReportModal = function (type, pid, pname) {
        _type  = type;
        _pid   = pid;
        _pname = pname;
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
        var modal = document.querySelector('#reportModal .rpt-modal');
        if (modal) modal.classList.toggle('wide', s === 'summary');
        var sb = document.getElementById('rptSummaryBtns');
        if (sb) sb.style.display = (s === 'cal' && _type === 'daily') ? 'flex' : 'none';
        renderFooter();
    }

    function renderFooter() {
        var f   = document.getElementById('rptFooter');
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
                    // Mobile uses auto-generated doc IDs; list returns [{id, date}]
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

    function renderCal() {
        var year  = _calMo.getFullYear();
        var month = _calMo.getMonth();
        var today = new Date(); today.setHours(0,0,0,0);
        var monthLabel = _calMo.toLocaleString('default', { month: 'long', year: 'numeric' });

        var html = '<div class="rpt-cal-nav">';
        html += '<button onclick="rptPrevMo()"><span class="material-symbols-outlined">chevron_left</span></button>';
        html += '<span class="rpt-cal-month">' + monthLabel + '</span>';
        html += '<button onclick="rptNextMo()"><span class="material-symbols-outlined">chevron_right</span></button>';
        html += '</div>';

        html += '<div class="rpt-cal-grid">';
        ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(function (d) {
            html += '<div class="rpt-dow">' + d + '</div>';
        });
        var firstDay = new Date(year, month, 1).getDay();
        for (var i = 0; i < firstDay; i++) html += '<div></div>';
        var days = new Date(year, month + 1, 0).getDate();
        for (var d = 1; d <= days; d++) {
            var ds = year + '-' + pad(month + 1) + '-' + pad(d);
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
        // Emergency reports use auto-generated Firestore IDs; daily reports use YYYY-MM-DD as doc ID
        var segment = (_type === 'emergency' && _idMap[dateStr]) ? _idMap[dateStr] : dateStr;
        var url = _base + '/projects/' + _pid +
                  (_type === 'emergency' ? '/emergency-reports/' : '/daily-reports/') + segment;
        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) { renderDetail(d, dateStr); setState('detail'); })
            .catch(function () { alert('Failed to load report.'); });
    };

    function renderDetail(d, dateStr) {
        var fmtDate = new Date(dateStr + 'T00:00:00').toLocaleDateString('en-US',
            { year:'numeric', month:'long', day:'numeric' });
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
            if (d.notes) html += '<div class="rpt-detail-section"><strong>Notes</strong><p>' + h(d.notes) + '</p></div>';
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
            if (d.progress_percent !== null && d.progress_percent !== undefined) {
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

            // Notes
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
        } else {
            html += '<div class="rpt-form-group"><label>Progress %</label>' +
                    '<input type="number" name="progress_percent" min="0" max="100" step="1" placeholder="0–100"></div>';
            html += '<div class="rpt-form-group"><label>Description</label>' +
                    '<textarea name="description" rows="4" placeholder="Describe the day\'s progress..."></textarea></div>';
        }

        if (_type === 'emergency') {
            html += '<div class="rpt-form-group"><label>Notes</label>' +
                    '<textarea name="notes" rows="4" placeholder="Describe the emergency specifics..."></textarea></div>';
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

    // Helper: HTML-escape a string
    function h(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str || '')));
        return div.innerHTML;
    }

    function pad(n) { return String(n).padStart(2, '0'); }

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

    function _dateDiffDays(a, b) {
        return Math.round((new Date(b + 'T00:00:00') - new Date(a + 'T00:00:00')) / 86400000);
    }
    function _computePlannedPct(milestones, startDate, endDate, reportDate) {
        var points = [{ date: startDate, pct: 0 }];
        var cumW = 0;
        milestones.forEach(function(m) {
            cumW += m.weight_percent;
            if (m.target_date) points.push({ date: m.target_date, pct: Math.min(100, cumW) });
        });
        if (points[points.length-1].pct < 100 && endDate) points.push({ date: endDate, pct: 100 });
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
    function _pct(v, dec)  { return typeof v === 'number' ? v.toFixed(dec !== undefined ? dec : 2) + ' %' : '—'; }
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
        var reportDate      = d.reportDate      || new Date().toISOString().slice(0,10);
        var overallProgress = typeof d.overallProgress === 'number' ? d.overallProgress : 0;
        var todayFmt = new Date().toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' });

        if (_summaryType === 'weekly') {
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
                    var weekNum   = (wt.week !== undefined && wt.week !== null) ? wt.week : (i + 1);
                    var weekDate  = wt.week_start ? _fmtWeekDate(wt.week_start) : '';
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
            var milestones  = d.milestones || [];
            var totalMs     = milestones.length;
            var scheduledMs = milestones.filter(function(m){ return m.target_date; }).length;
            var plannedPct  = d.startDate ? _computePlannedPct(milestones, d.startDate, d.endDate, reportDate) : null;
            var variance    = plannedPct !== null ? overallProgress - plannedPct : null;

            html += '<div class="rpt-sum-overall-hdr">';
            html += '<div class="rpt-sum-overall-title">General Progress Accomplishment %</div>';
            html += '<div class="rpt-sum-overall-sub">As of ' + todayFmt + '</div>';
            if (totalMs > 0) html += '<div class="rpt-sum-overall-badge">' + scheduledMs + ' of ' + totalMs + ' milestone' + (totalMs !== 1 ? 's' : '') + ' scheduled</div>';
            html += '</div>';

            html += '<table class="rpt-sum-table" style="margin-bottom:.8rem"><thead><tr style="background:#b8d4e8">' +
                    '<th>ITEM</th>' +
                    '<th class="num">PLANNED %<br><small style="font-weight:400;text-transform:none">(scheduled scope)</small></th>' +
                    '<th class="num">ACTUAL %</th><th class="num">VARIANCE (+/–)</th>' +
                    '</tr></thead><tbody><tr>' +
                    '<td style="font-weight:700">PROGRESS TO DATE</td>' +
                    '<td class="num">' + (plannedPct !== null ? _pct(plannedPct, 1) : '—') + '</td>' +
                    '<td class="num">' + _pct(overallProgress, 2) + '</td>' +
                    '<td class="num">' + (variance !== null ? _vari(variance, 2) : '—') + '</td>' +
                    '</tr></tbody></table>';

            html += '<div class="rpt-sum-ms-heading">MILESTONE SUMMARY</div>';
            if (!milestones.length) {
                html += '<div class="rpt-sum-empty">No milestones defined.</div>';
            } else {
                html += '<table class="rpt-sum-table"><thead><tr style="background:#b8d4e8">' +
                        '<th>MILESTONE</th><th>TARGET DATE</th>' +
                        '<th class="num">PLANNED %</th><th class="num">ACTUAL %</th>' +
                        '<th class="num">VARIANCE</th><th>STATUS</th>' +
                        '</tr></thead><tbody>';
                milestones.forEach(function(m) {
                    var stLow  = (m.status || '').toLowerCase();
                    var isDone = stLow === 'completed';
                    var isIP   = stLow === 'in progress';
                    var stLbl  = isDone ? 'Completed' : (isIP ? 'In Progress' : 'Not Started');
                    var stCls  = isDone ? 'rpt-sum-status-done' : (isIP ? 'rpt-sum-status-inprogress' : 'rpt-sum-status-pending');
                    var hasTD     = !!m.target_date;
                    var tdPassed  = hasTD && m.target_date <= reportDate;
                    var actualPct = m.completion_percent * m.weight_percent / 100;
                    var plannedR  = hasTD ? m.weight_percent : null;
                    var varRow    = (tdPassed && plannedR !== null) ? actualPct - plannedR : null;
                    html += '<tr><td>' + h(m.milestone_name) + '</td><td>' + _fmtDate(m.target_date) + '</td>' +
                            '<td class="num">' + (plannedR !== null ? _pct(plannedR, 1) : '—') + '</td>' +
                            '<td class="num">' + _pct(actualPct, 1) + '</td>' +
                            '<td class="num">' + (varRow !== null ? _vari(varRow, 2) : '<span class="muted">—</span>') + '</td>' +
                            '<td><span class="rpt-sum-status ' + stCls + '">' + stLbl + '</span></td></tr>';
                });
                html += '</tbody></table>';
            }
        }

        document.getElementById('rptSummaryEl').innerHTML = html;
        setState('summary');
        document.getElementById('rptModalTitle').textContent =
            (_summaryType === 'weekly' ? 'Weekly Summary' : 'Overall Progress') + ' — ' + _pname;
    }

    // Close on overlay click
    document.addEventListener('DOMContentLoaded', function () {
        var overlay = document.getElementById('reportModal');
        if (overlay) overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeReportModal();
        });
    });
}());

// Smooth-scroll to #summary / #monitor from sidebar links
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('a[href*="#summary"], a[href*="#monitor"]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            const hash = this.getAttribute('href').split('#')[1];
            const target = document.getElementById(hash);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                history.replaceState(null, '', '#' + hash);
            }
        });
    });

    // Auto-scroll to hash on page load
    if (window.location.hash) {
        const el = document.querySelector(window.location.hash);
        if (el) setTimeout(() => el.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
    }
});
</script>

<?php
// Build JS-safe project locations for the monitor map
$jsLocations = json_encode($projectLocations ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
$mapsKey     = esc($googleMapsBrowserKey ?? '');
?>
<?php if ($mapsKey): ?>
<script>
(function () {
    var PROJECT_LOCATIONS = <?= $jsLocations ?>;
    var monitorMap = null;
    var openInfo   = null;

    window.initMonitorMap = function () {
        var mapEl = document.getElementById('monitorMap');
        if (!mapEl) return;

        monitorMap = new google.maps.Map(mapEl, {
            center: { lat: 12.8797, lng: 121.7740 },   // PH centre
            zoom: 6,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
        });
        window.pzMonitorMap = monitorMap;

        var bounds = new google.maps.LatLngBounds();
        var hasMarkers = false;

        function pinIcon(color, stroke) {
            var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="36" viewBox="0 0 28 36">' +
                '<path d="M14 0C6.268 0 0 6.268 0 14c0 9.941 14 22 14 22S28 23.941 28 14C28 6.268 21.732 0 14 0z" ' +
                'fill="' + color + '" stroke="' + (stroke || '#fff') + '" stroke-width="1.5"/>' +
                '<circle cx="14" cy="13" r="5" fill="rgba(255,255,255,0.85)"/>' +
                '</svg>';
            return {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
                scaledSize: new google.maps.Size(28, 36),
                anchor: new google.maps.Point(14, 36),
            };
        }

        function typeColor(type) {
            var t = (type || '').toUpperCase();
            if (t.indexOf('NEW')   !== -1 || t.indexOf('BUILD')  !== -1) return '#E53935'; // red
            if (t.indexOf('RETRO') !== -1)                                return '#1E88E5'; // blue
            if (t.indexOf('COLO')  !== -1)                                return '#FDD835'; // yellow
            if (t.indexOf('CONV')  !== -1)                                return '#7B1FA2'; // violet
            return '#E53935'; // default red
        }

        PROJECT_LOCATIONS.forEach(function (proj) {
            if (proj.lat == null || proj.lng == null) return;

            var pos = { lat: parseFloat(proj.lat), lng: parseFloat(proj.lng) };
            var marker = new google.maps.Marker({
                position: pos,
                map: monitorMap,
                title: proj.name,
                animation: google.maps.Animation.DROP,
                icon: pinIcon(typeColor(proj.type)),
            });

            var info = new google.maps.InfoWindow({
                content: '<div style="max-width:220px;font-family:inherit;padding:2px 0">' +
                         '<div style="font-weight:700;font-size:0.85rem;color:#5a3e00;letter-spacing:0.5px;margin-bottom:4px">' + proj.name + '</div>' +
                         (proj.addr ? '<div style="font-size:0.78rem;color:#666;line-height:1.4">' + proj.addr + '</div>' : '') +
                         '</div>',
            });

            marker.addListener('click', function () {
                if (openInfo) openInfo.close();
                info.open(monitorMap, marker);
                openInfo = info;
            });

            // Store marker reference on the project for fly-to
            proj._marker = marker;
            proj._info   = info;

            bounds.extend(pos);
            hasMarkers = true;
        });

        if (hasMarkers) monitorMap.fitBounds(bounds);
    };

    // Delegated listener — avoids inline-onclick quote-escaping issues
    document.addEventListener('click', function (e) {
        var btn = e.target.closest && e.target.closest('.db-btn-locate[data-name]');
        if (!btn) return;
        var rawLat = btn.dataset.lat;
        var rawLng = btn.dataset.lng;
        var lat  = rawLat !== '' ? parseFloat(rawLat) : null;
        var lng  = rawLng !== '' ? parseFloat(rawLng) : null;
        monitorFlyTo(lat, lng, btn.dataset.name || '');
    });

    // Called by the delegated listener above
    function monitorFlyTo(lat, lng, name) {
        var sidePanel    = document.getElementById('mapSidePanel');
        var wasCollapsed = sidePanel && sidePanel.classList.contains('map-panel-collapsed');

        // Open the panel if it was collapsed
        if (wasCollapsed) toggleMapPanel();

        // Flash the panel header to draw the user's eye toward the map
        var hdr = document.querySelector('#mapSidePanel .map-panel-hdr');
        if (hdr) {
            hdr.classList.add('map-panel-flash');
            setTimeout(function () { hdr.classList.remove('map-panel-flash'); }, 900);
        }

        // No coordinates saved for this project — just open the panel
        if (lat === null || lng === null || !monitorMap) return;

        // Wait for panel open + map resize before panning
        setTimeout(function () {
            monitorMap.panTo({ lat: lat, lng: lng });
            monitorMap.setZoom(15);

            PROJECT_LOCATIONS.forEach(function (proj) {
                if (proj._marker && Math.abs(proj.lat - lat) < 0.0001 && Math.abs(proj.lng - lng) < 0.0001) {
                    if (openInfo) openInfo.close();

                    proj._marker.setAnimation(google.maps.Animation.BOUNCE);
                    setTimeout(function () { proj._marker.setAnimation(null); }, 1500);

                    setTimeout(function () {
                        proj._info.open(monitorMap, proj._marker);
                        openInfo = proj._info;
                    }, 400);
                }
            });
        }, wasCollapsed ? 320 : 0);
    }
}());
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= $mapsKey ?>&callback=initMonitorMap" async defer></script>
<?php endif; ?>

<!-- -- SortableJS â€” drag-to-reorder/reassign resource pills -- -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var dragging        = false;
    var dragY           = 0;
    var rAfId           = null;
    var scrollEl        = document.scrollingElement || document.documentElement;
    var pendingTransfer = null;

    // ── Toast helper ─────────────────────────────────────────────────
    function showToast(msg) {
        var toast = document.getElementById('dndToast');
        if (!toast) return;
        toast.textContent = msg;
        toast.classList.add('show');
        clearTimeout(toast._hideTimer);
        toast._hideTimer = setTimeout(function () { toast.classList.remove('show'); }, 3500);
    }

    // ── Transfer confirm modal handlers ──────────────────────────────
    var overlay    = document.getElementById('dndConfirmOverlay');
    var cancelBtn  = document.getElementById('dndCancelBtn');
    var confirmBtn = document.getElementById('dndConfirmBtn');

    function closeModal() { overlay.classList.remove('open'); }

    cancelBtn && cancelBtn.addEventListener('click', function () {
        if (pendingTransfer) {
            var t = pendingTransfer;
            t.fromZone.insertBefore(t.pill, t.fromZone.children[t.oldIndex] || null);
        }
        pendingTransfer = null;
        closeModal();
    });

    overlay && overlay.addEventListener('click', function (e) {
        if (e.target === overlay) cancelBtn.click();
    });

    confirmBtn && confirmBtn.addEventListener('click', function () {
        if (!pendingTransfer) return;
        var t = pendingTransfer;
        pendingTransfer = null;

        // 1. Close modal immediately — no waiting for the network call
        closeModal();

        // 2. Revert pill back to source zone
        t.fromZone.insertBefore(t.pill, t.fromZone.children[t.oldIndex] || null);

        // 3. Optimistically mark as pending (gray, locked from drag)
        t.pill.classList.add('db-pill--pending');
        t.pill.dataset.pending = '1';

        // 4. Submit transfer request
        confirmBtn.disabled = true;
        fetch('<?= base_url('requests/store') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>',
            },
            body: JSON.stringify({
                type:                     'drag_drop',
                category:                 t.cat,
                resource_name:            t.resName,
                resource_id:              t.resId,
                unit_id:                  t.unitId,
                source_project_id:        t.srcPid,
                source_project_name:      t.srcPname,
                destination_project_id:   t.destPid,
                destination_project_name: t.destPname,
                quantity:                 1,
            }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                if (data.id) t.pill.dataset.reqId = data.id;
                showToast('Transfer request submitted — awaiting approval.');
            } else {
                // Server rejected — roll back pending state
                t.pill.classList.remove('db-pill--pending');
                delete t.pill.dataset.pending;
                showToast('Could not submit transfer request.');
            }
        })
        .catch(function () {
            t.pill.classList.remove('db-pill--pending');
            delete t.pill.dataset.pending;
            showToast('Network error — transfer request not submitted.');
        })
        .finally(function () { confirmBtn.disabled = false; });
    });

    function updateY(e) { if (dragging) dragY = e.clientY; }
    document.addEventListener('mousemove',   updateY, true);
    document.addEventListener('pointermove', updateY, true);

    function edgeScrollLoop() {
        if (!dragging) return;
        var ZONE = 80, MAX = 20;
        var dTop = dragY;
        var dBot = window.innerHeight - dragY;
        if (dTop < ZONE)
            scrollEl.scrollTop += dTop > 0 ? -Math.round((1 - dTop / ZONE) * MAX) : -MAX;
        else if (dBot < ZONE)
            scrollEl.scrollTop += dBot > 0 ?  Math.round((1 - dBot / ZONE) * MAX) :  MAX;
        rAfId = requestAnimationFrame(edgeScrollLoop);
    }

    // ── Poll for resolved pending transfers ──────────────────────────
    var PENDING_JSON_URL = '<?= base_url('requests/pending-json') ?>';

    function pollPendingTransfers() {
        var pendingPills = document.querySelectorAll('.db-pill--pending[data-req-id]');
        if (!pendingPills.length) return;

        var knownIds = new Set();
        pendingPills.forEach(function (p) { if (p.dataset.reqId) knownIds.add(p.dataset.reqId); });
        if (!knownIds.size) return;

        fetch(PENDING_JSON_URL)
            .then(function (r) { return r.json(); })
            .then(function (reqs) {
                var activeIds = new Set(reqs.map(function (r) { return r.id; }));
                var resolved  = false;
                knownIds.forEach(function (id) { if (!activeIds.has(id)) resolved = true; });
                if (resolved) {
                    showToast('A transfer request was resolved — refreshing…');
                    setTimeout(function () { location.reload(); }, 2000);
                }
            })
            .catch(function () {});
    }

    // Start polling every 30 s (only meaningful when pending pills exist)
    setInterval(pollPendingTransfers, 30000);

    ['equipment', 'tools', 'manpower'].forEach(function (cat) {
        var zones = document.querySelectorAll('.db-res-drop-zone[data-cat="' + cat + '"]');

        zones.forEach(function (zone) {
            new Sortable(zone, {
                group:           { name: 'res-' + cat, pull: true, put: ['res-' + cat] },
                handle:          '.db-pill-icons',
                draggable:       '.db-pill',
                filter:          '.db-empty, .db-pill--pending',
                animation:       150,
                ghostClass:      'db-pill-ghost',
                dragClass:       'db-pill-drag',
                scroll:          false,
                // forceFallback disables HTML5 native DnD and uses pointer/mouse events
                // instead â€” this is required for mousemove/pointermove to fire during drag,
                // which is what our edge-scroll and wheel-scroll tracking depends on.
                forceFallback:   true,
                fallbackTolerance: 3,

                onStart: function (evt) {
                    dragging = true;
                    dragY    = evt.originalEvent ? evt.originalEvent.clientY : window.innerHeight / 2;
                    document.documentElement.style.scrollBehavior = 'auto';
                    document.querySelectorAll('.db-res-group[data-cat="' + cat + '"]').forEach(function (g) {
                        g.classList.add('open');
                    });
                    zones.forEach(function (z) {
                        z.classList.add(z === zone ? 'dnd-active' : 'dnd-drop-zone');
                    });
                    rAfId = requestAnimationFrame(edgeScrollLoop);
                },

                onEnd: function () {
                    dragging = false;
                    if (rAfId) { cancelAnimationFrame(rAfId); rAfId = null; }
                    document.documentElement.style.scrollBehavior = '';
                    zones.forEach(function (z) {
                        z.classList.remove('dnd-active', 'dnd-drop-zone');
                    });
                },

                onAdd: function (evt) {
                    var destCard = evt.to.closest('.db-project-card');
                    var srcCard  = evt.from.closest('.db-project-card');

                    var pill      = evt.item;
                    var resName   = pill.dataset.resName  || (pill.querySelector('.db-pill-name') || {}).textContent || '';
                    var resId     = pill.dataset.resId    || '';
                    var unitId    = pill.dataset.unitId   || '';
                    var destPid   = (evt.to.dataset.pid)  || '';
                    var destPname = destCard ? (destCard.dataset.pname || '') : '';
                    var srcPid    = srcCard  ? (srcCard.dataset.pid   || '') : '';
                    var srcPname  = srcCard  ? (srcCard.dataset.pname || '') : '';

                    if (!destPid || destPid === srcPid) return;

                    // Store context for the confirm/cancel handlers
                    pendingTransfer = {
                        cat: cat, pill: pill,
                        resName: resName.trim(), resId: resId, unitId: unitId,
                        srcPid: srcPid, srcPname: srcPname,
                        destPid: destPid, destPname: destPname,
                        fromZone: evt.from, oldIndex: evt.oldIndex,
                    };

                    // Populate and show the modal
                    document.getElementById('dndResLabel').textContent  = resName.trim();
                    document.getElementById('dndSrcLabel').textContent  = srcPname  || '—';
                    document.getElementById('dndDestLabel').textContent = destPname || '—';
                    overlay.classList.add('open');
                },
            });
        });
    });
});

// Restore right map panel state (like sidebar)
(function () {
    var panel = document.getElementById('mapSidePanel');
    if (!panel) return;
    var collapsed = localStorage.getItem('pz_map_collapsed') === 'true';
    if (collapsed) {
        panel.classList.add('map-panel-collapsed');
        document.body.classList.add('map-panel-collapsed');
    } else {
        document.body.classList.add('map-panel-open');
    }
}());

function toggleMapPanel() {
    var panel = document.getElementById('mapSidePanel');
    if (!panel) return;
    var isNowCollapsed = panel.classList.toggle('map-panel-collapsed');
    document.body.classList.toggle('map-panel-open',     !isNowCollapsed);
    document.body.classList.toggle('map-panel-collapsed',  isNowCollapsed);
    localStorage.setItem('pz_map_collapsed', isNowCollapsed);
    // Trigger map resize after the slide animation
    if (!isNowCollapsed) {
        setTimeout(function () {
            if (window.pzMonitorMap) google.maps.event.trigger(window.pzMonitorMap, 'resize');
        }, 280);
    }
}
</script>

<?= $this->endSection() ?>
