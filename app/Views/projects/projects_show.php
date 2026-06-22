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
            .catch(function () { showToast('Failed to load report.', 'error'); });
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
            html += '<div class="rpt-detail-section"><strong>Cause(s)</strong>';
            if (causes.length) {
                html += '<ul>';
                causes.forEach(function (c) {
                    var label = h(c);
                    if (c === 'Other' && d.other_cause) label += ': ' + h(d.other_cause);
                    html += '<li>' + label + '</li>';
                });
                html += '</ul>';
            } else {
                html += '<p style="color:#888;font-style:italic">No cause recorded.</p>';
            }
            html += '</div>';
            html += '<div class="rpt-detail-section"><strong>Notes</strong>';
            html += d.notes ? '<p>' + h(d.notes) + '</p>' : '<p style="color:#888;font-style:italic">No notes recorded.</p>';
            html += '</div>';
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
        if (_type === 'emergency') {
            var checked = form.querySelectorAll('input[name="causes[]"]:checked');
            if (!checked.length) {
                var causesBox = form.querySelector('.rpt-causes');
                if (causesBox) {
                    causesBox.style.outline = '2px solid #c0392b';
                    causesBox.style.borderRadius = '6px';
                    setTimeout(function () { causesBox.style.outline = ''; }, 2000);
                }
                return;
            }
        }
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
                else showToast(d.error || 'Failed to save report.', 'error');
            })
            .catch(function () { showToast('Failed to save report.', 'error'); });
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

            // No saved weekly_targets? Generate S-curve from start/end dates (same as mobile)
            var scurveFallback = false;
            if (!targets.length && d.startDate && d.endDate) {
                var _s = new Date(d.startDate + 'T00:00:00');
                var _e = new Date(d.endDate   + 'T00:00:00');
                var _n = Math.max(1, Math.ceil((_e - _s) / (7 * 24 * 3600 * 1000)));
                for (var _i = 0; _i < _n; _i++) {
                    var _ws = new Date(_s.getTime() + _i * 7 * 24 * 3600 * 1000);
                    targets.push({
                        week:           _i + 1,
                        week_start:     _ws.toISOString().slice(0, 10),
                        target_percent: Math.round((1 - Math.cos(Math.PI * (_i + 1) / _n)) / 2 * 10000) / 100,
                        actual_percent: null,
                    });
                }
                scurveFallback = true;
            }

            // Show only weeks that have already started (mirror mobile behaviour)
            var _today = new Date(); _today.setHours(0, 0, 0, 0);
            targets = targets.filter(function(wt) {
                if (!wt.week_start) return true;
                return new Date(wt.week_start + 'T00:00:00') <= _today;
            });

            if (!targets.length) {
                html += '<div class="rpt-sum-empty">No project dates set. Add a start and end date to the project to generate the weekly target table.</div>';
            } else {
                html += '<div class="rpt-sum-title">Cumulative Weekly Progress Target Summary</div>';
                if (scurveFallback) {
                    html += '<div style="font-size:.76rem;color:#8C701C;background:#fffbee;border:1px solid #e8d9a8;border-radius:6px;padding:.4rem .75rem;margin-bottom:.75rem;">Targets computed via S-curve (no saved schedule). Save a milestone schedule to use custom targets.</div>';
                }
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
