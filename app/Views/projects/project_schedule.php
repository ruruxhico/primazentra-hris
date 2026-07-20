<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $project       = $project       ?? [];
    $projectId     = $projectId     ?? '';
    $milestones    = $milestones    ?? [];
    $weeklyTargets = $weeklyTargets ?? [];
    $startDate     = $startDate     ?? '';
    $endDate       = $endDate       ?? '';

    // If we're re-rendering after a rejected submission (weight ≠ 100%, missing
    // target date, PM add/remove violation), show what the user just typed
    // instead of silently reverting to the last saved state.
    $oldMilestones = old('milestones');
    if (is_array($oldMilestones) && !empty($oldMilestones)) {
        $milestones = array_values($oldMilestones);
    }

    $pName  = esc(strtoupper($project['project_name'] ?? 'PROJECT'));
    $canEdit      = pz_can('manage_milestones');
    $canAddRemove = pz_can('add_remove_milestones');

    $units = ['pcs', 'lot', 'm', 'm²', 'm³', 'kg', 'ton', 'bag', 'set', 'unit', 'lm', 'days', 'weeks'];
?>

<style>
.ps-mode-row { display:flex; align-items:center; gap:0.5rem; padding:0.1rem 0 0.15rem 1.75rem; }
.ps-mode-label { font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#8C701C; }
.ps-mode-select { padding:3px 6px; border:1px solid #e0dbd0; border-radius:6px; font-size:0.78rem; background:#fff; font-family:inherit; }
.ps-mode-select:focus { outline:none; border-color:#C47A0F; }
.ps-ms-qty:disabled, .ps-ms-select:disabled { opacity:0.45; background:#f5f3ee; cursor:not-allowed; }
/* Activity sub-list reuses the .pf-act-* component styles from Create Project
   (public/css/styles.css) so both screens share one look and behavior. */
.ps-act-panel { padding:0.35rem 0 0.15rem; }
</style>

<div class="ps-shell">

    <!-- Page header -->
    <div class="ps-page-hdr">
        <button class="ps-back-btn" onclick="history.back()" title="Back">
            <span class="material-symbols-outlined">arrow_back</span>
        </button>
        <div>
            <p class="ps-page-title">Project Schedule</p>
            <p class="ps-page-sub"><?= $pName ?></p>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
    <div class="ps-alert ps-alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
    <div class="ps-alert ps-alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('projects/' . esc($projectId) . '/schedule') ?>" id="scheduleForm">
        <?= csrf_field() ?>

        <!-- Milestone Schedule -->
        <div class="ps-card">
            <div class="ps-card-hdr">Milestone Schedule</div>

            <div class="ps-ms-col-hdr">
                <span></span>
                <span>Milestone</span>
                <span>Weight %</span>
                <span>Qty</span>
                <span>Unit</span>
                <span>Target Start</span>
                <span>Target End</span>
                <span></span>
            </div>

            <div class="ps-ms-list" id="msList">
                <?php foreach ($milestones as $i => $m): ?>
                <div class="ps-ms-row" draggable="true">
                    <span class="material-symbols-outlined ps-ms-drag">drag_indicator</span>
                    <input type="text" class="ps-ms-name"
                           name="milestones[<?= $i ?>][name]"
                           value="<?= esc($m['milestone_name'] ?? $m['name'] ?? '') ?>"
                           placeholder="Milestone name" required>
                    <input type="number" class="ps-ms-num weight-input"
                           name="milestones[<?= $i ?>][weight]"
                           value="<?= esc($m['weight_percent'] ?? $m['weight'] ?? 0) ?>"
                           step="0.1" min="0" max="100" placeholder="0">
                    <input type="number" class="ps-ms-num ps-ms-qty"
                           name="milestones[<?= $i ?>][qty]"
                           value="<?= esc($m['target_quantity'] ?? $m['qty'] ?? 1) ?>"
                           step="1" min="1" placeholder="1">
                    <select class="ps-ms-select" name="milestones[<?= $i ?>][unit]">
                        <?php foreach ($units as $u): ?>
                        <option value="<?= esc($u) ?>" <?= ($m['unit'] ?? 'pcs') === $u ? 'selected' : '' ?>><?= esc($u) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php
                        $tsVal  = $m['target_start'] ?? $m['targetStart'] ?? '';
                        $tsDisp = ($tsVal !== '' && ($tsTs = strtotime($tsVal)) !== false) ? date('d/m/Y', $tsTs) : '';
                    ?>
                    <div class="date-picker ps-ms-date-picker">
                        <input type="text" class="date-display" placeholder="DD/MM/YYYY" value="<?= esc($tsDisp) ?>" readonly>
                        <input type="hidden" class="ps-ms-date-start" name="milestones[<?= $i ?>][target_start]" value="<?= esc($tsVal) ?>">
                        <button type="button" class="date-toggle"><span class="material-symbols-outlined">calendar_month</span></button>
                        <div class="calendar-popup" aria-hidden="true"></div>
                    </div>
                    <?php
                        $tdVal  = $m['target_date'] ?? $m['targetDate'] ?? '';
                        $tdDisp = ($tdVal !== '' && ($tdTs = strtotime($tdVal)) !== false) ? date('d/m/Y', $tdTs) : '';
                    ?>
                    <div class="date-picker ps-ms-date-picker">
                        <input type="text" class="date-display" placeholder="DD/MM/YYYY" value="<?= esc($tdDisp) ?>" readonly>
                        <input type="hidden" class="ps-ms-date" name="milestones[<?= $i ?>][target_date]" value="<?= esc($tdVal) ?>">
                        <button type="button" class="date-toggle"><span class="material-symbols-outlined">calendar_month</span></button>
                        <div class="calendar-popup" aria-hidden="true"></div>
                    </div>
                    <?php if ($canAddRemove): ?>
                    <button type="button" class="ps-ms-del" onclick="removeRow(this)" title="Remove">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                    <?php else: ?>
                    <span></span>
                    <?php endif; ?>

                    <div class="ps-mode-row" style="grid-column:1 / -1">
                        <label class="ps-mode-label">Track by</label>
                        <select class="ps-mode-select" name="milestones[<?= $i ?>][tracking_mode]">
                            <option value="quantity"    <?= ($m['tracking_mode'] ?? 'quantity') === 'quantity'    ? 'selected' : '' ?>>Quantity</option>
                            <option value="status_only" <?= ($m['tracking_mode'] ?? 'quantity') === 'status_only' ? 'selected' : '' ?>>Status Only</option>
                        </select>
                    </div>

                    <div class="ps-act-panel" style="grid-column:1 / -1">
                        <button type="button" class="pf-act-toggle-btn">
                            <span class="material-symbols-outlined pf-act-toggle-icon">expand_more</span>
                            Activities (<span class="pf-act-count"><?= count($m['activities'] ?? []) ?></span>)
                        </button>
                        <div class="pf-act-body" hidden>
                            <div class="pf-act-list">
                                <?php foreach (($m['activities'] ?? []) as $ai => $a): ?>
                                <?php
                                    $aMode  = ($a['tracking_mode'] ?? 'status_only') === 'quantity' ? 'quantity' : 'status_only';
                                    $aIsQty = $aMode === 'quantity';
                                ?>
                                <div class="pf-act-row">
                                    <div class="phase-field">
                                        <span class="phase-field-lbl">Activity Name</span>
                                        <input type="text" class="pf-act-name"
                                               name="milestones[<?= $i ?>][activities][<?= $ai ?>][name]"
                                               value="<?= esc($a['activity_name'] ?? $a['name'] ?? '') ?>"
                                               placeholder="Activity name">
                                    </div>
                                    <div class="pf-act-row2">
                                        <div class="phase-field">
                                            <span class="phase-field-lbl">Weight %</span>
                                            <div class="pf-act-weight-group">
                                                <input type="number" class="pf-act-weight"
                                                       name="milestones[<?= $i ?>][activities][<?= $ai ?>][weight]"
                                                       value="<?= esc($a['weight_percent'] ?? $a['weight'] ?? 0) ?>"
                                                       step="0.1" min="0" placeholder="0">
                                                <span class="pf-act-weight-unit">%</span>
                                            </div>
                                        </div>
                                        <div class="phase-field">
                                            <span class="phase-field-lbl">Track By</span>
                                            <select class="pf-act-mode" name="milestones[<?= $i ?>][activities][<?= $ai ?>][tracking_mode]">
                                                <option value="status_only" <?= !$aIsQty ? 'selected' : '' ?>>Status</option>
                                                <option value="quantity"    <?= $aIsQty  ? 'selected' : '' ?>>Quantity</option>
                                            </select>
                                        </div>
                                        <div class="phase-field">
                                            <span class="phase-field-lbl pf-act-value-lbl"><?= $aIsQty ? 'Qty' : 'Status' ?></span>
                                            <span class="pf-act-status-badge" <?= $aIsQty ? 'hidden' : '' ?>><?= esc($a['status'] ?? 'Not Started') ?></span>
                                            <input type="number" class="pf-act-qty"
                                                   name="milestones[<?= $i ?>][activities][<?= $ai ?>][qty]"
                                                   value="<?= esc($a['target_quantity'] ?? 1) ?>" min="1" step="1" placeholder="Qty"
                                                   <?= !$aIsQty ? 'hidden disabled' : '' ?>>
                                        </div>
                                        <div class="phase-field pf-act-unit-field" <?= !$aIsQty ? 'hidden' : '' ?>>
                                            <span class="phase-field-lbl">Unit</span>
                                            <select class="pf-act-unit" name="milestones[<?= $i ?>][activities][<?= $ai ?>][unit]" <?= !$aIsQty ? 'disabled' : '' ?>>
                                                <?php foreach ($units as $u): ?>
                                                <option value="<?= esc($u) ?>" <?= ($a['unit'] ?? 'pcs') === $u ? 'selected' : '' ?>><?= esc($u) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="button" class="pf-act-del-btn" title="Remove activity">
                                            <span class="material-symbols-outlined">close</span>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="pf-act-add-row">
                                <button type="button" class="pf-act-add-btn">+ Add Activity</button>
                                <span class="pf-act-total">Activities total <span class="pf-act-total-val">0.0</span>% / <span class="pf-act-total-target">0.0</span>%</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="ps-add-row">
                <?php if ($canAddRemove): ?>
                <button type="button" class="ps-add-btn" onclick="addRow()">
                    <span class="material-symbols-outlined">add</span> Add Milestone
                </button>
                <?php else: ?>
                <p class="ps-add-note">Only Administrators and Operations Managers can add or remove milestones. You may edit the details of existing ones below.</p>
                <?php endif; ?>
                <div class="ps-total-weight" id="totalWeightDisplay">
                    TOTAL WEIGHT <span id="totalWeightVal">0.0</span>%
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="ps-footer">
            <button type="submit" class="ps-btn ps-btn-save">SAVE</button>
            <button type="button" class="ps-btn ps-btn-cancel" onclick="history.back()">CANCEL</button>
        </div>

    </form>
</div>

<!-- Row template (hidden) -->
<template id="rowTpl">
    <div class="ps-ms-row" draggable="true">
        <span class="material-symbols-outlined ps-ms-drag">drag_indicator</span>
        <input type="text" class="ps-ms-name" placeholder="Milestone name" required>
        <input type="number" class="ps-ms-num weight-input" value="0" step="0.1" min="0" max="100" placeholder="0">
        <input type="number" class="ps-ms-num ps-ms-qty" value="1" step="1" min="1" placeholder="1">
        <select class="ps-ms-select">
            <?php foreach ($units as $u): ?>
            <option value="<?= esc($u) ?>"><?= esc($u) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="date-picker ps-ms-date-picker">
            <input type="text" class="date-display" placeholder="DD/MM/YYYY" readonly>
            <input type="hidden" class="ps-ms-date-start">
            <button type="button" class="date-toggle"><span class="material-symbols-outlined">calendar_month</span></button>
            <div class="calendar-popup" aria-hidden="true"></div>
        </div>
        <div class="date-picker ps-ms-date-picker">
            <input type="text" class="date-display" placeholder="DD/MM/YYYY" readonly>
            <input type="hidden" class="ps-ms-date">
            <button type="button" class="date-toggle"><span class="material-symbols-outlined">calendar_month</span></button>
            <div class="calendar-popup" aria-hidden="true"></div>
        </div>
        <button type="button" class="ps-ms-del" onclick="removeRow(this)" title="Remove">
            <span class="material-symbols-outlined">delete</span>
        </button>

        <div class="ps-mode-row" style="grid-column:1 / -1">
            <label class="ps-mode-label">Track by</label>
            <select class="ps-mode-select">
                <option value="quantity" selected>Quantity</option>
                <option value="status_only">Status Only</option>
            </select>
        </div>

        <div class="ps-act-panel" style="grid-column:1 / -1">
            <button type="button" class="pf-act-toggle-btn">
                <span class="material-symbols-outlined pf-act-toggle-icon">expand_more</span>
                Activities (<span class="pf-act-count">0</span>)
            </button>
            <div class="pf-act-body" hidden>
                <div class="pf-act-list"></div>
                <div class="pf-act-add-row">
                    <button type="button" class="pf-act-add-btn">+ Add Activity</button>
                    <span class="pf-act-total">Activities total <span class="pf-act-total-val">0.0</span>% / <span class="pf-act-total-target">0.0</span>%</span>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- Activity row template (hidden) -->
<template id="actRowTpl">
    <div class="pf-act-row">
        <div class="phase-field">
            <span class="phase-field-lbl">Activity Name</span>
            <input type="text" class="pf-act-name" placeholder="Activity name">
        </div>
        <div class="pf-act-row2">
            <div class="phase-field">
                <span class="phase-field-lbl">Weight %</span>
                <div class="pf-act-weight-group">
                    <input type="number" class="pf-act-weight" value="0" step="0.1" min="0" max="100" placeholder="0">
                    <span class="pf-act-weight-unit">%</span>
                </div>
            </div>
            <div class="phase-field">
                <span class="phase-field-lbl">Track By</span>
                <select class="pf-act-mode">
                    <option value="status_only" selected>Status</option>
                    <option value="quantity">Quantity</option>
                </select>
            </div>
            <div class="phase-field">
                <span class="phase-field-lbl pf-act-value-lbl">Status</span>
                <span class="pf-act-status-badge">Not Started</span>
                <input type="number" class="pf-act-qty" value="1" min="1" step="1" placeholder="Qty" hidden disabled>
            </div>
            <div class="phase-field pf-act-unit-field" hidden>
                <span class="phase-field-lbl">Unit</span>
                <select class="pf-act-unit" disabled>
                    <?php foreach ($units as $u): ?>
                    <option value="<?= esc($u) ?>" <?= $u === 'pcs' ? 'selected' : '' ?>><?= esc($u) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="button" class="pf-act-del-btn" title="Remove activity">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
    </div>
</template>

<script>
(function () {
    var list   = document.getElementById('msList');
    var tpl    = document.getElementById('rowTpl');
    var totEl  = document.getElementById('totalWeightDisplay');
    var totVal = document.getElementById('totalWeightVal');

    // Project's own start/end date — every milestone's target start/end must
    // fall within this window.
    var projStart = <?= json_encode($startDate) ?>;
    var projEnd   = <?= json_encode($endDate) ?>;

    /* ── Total weight ── */
    function recomputeTotal() {
        var total = 0;
        list.querySelectorAll('.weight-input').forEach(function (inp) {
            total += parseFloat(inp.value || 0);
        });
        total = Math.round(total * 10) / 10;
        totVal.textContent = total.toFixed(1);
        totEl.classList.toggle('exact', Math.abs(total - 100) < 0.5);
        totEl.classList.toggle('over',  total > 100.5);
    }

    list.addEventListener('input', function (e) {
        if (e.target.classList.contains('weight-input')) {
            recomputeTotal();
            // The activities target is the milestone's own weight, so it must
            // refresh when that weight changes too, not just the activities.
            recomputeActTotal(e.target.closest('.ps-ms-row'));
        }
    });

    /* ── Target Start / Target End date pickers (same rule as the project's own
       start/end date: the target end can't be before the target start) ── */
    function initMsDatePickers(row) {
        if (!window.pzInitDatePicker) return;
        var startHidden = row.querySelector('.ps-ms-date-start');
        var endHidden    = row.querySelector('.ps-ms-date');
        var startDp      = startHidden ? startHidden.closest('.ps-ms-date-picker') : null;
        var endDp        = endHidden   ? endHidden.closest('.ps-ms-date-picker')   : null;
        var endDisplay   = endDp ? endDp.querySelector('.date-display') : null;

        if (startDp) {
            window.pzInitDatePicker(startDp, {
                getMin: function () { return projStart || ''; },
                getMax: function () {
                    var vals = [];
                    if (endHidden && endHidden.value) vals.push(endHidden.value);
                    if (projEnd) vals.push(projEnd);
                    return vals.length ? vals.reduce(function (a, b) { return a < b ? a : b; }) : '';
                },
                onSelect: function (val) {
                    if (endHidden && endHidden.value && val > endHidden.value) {
                        endHidden.value = '';
                        if (endDisplay) endDisplay.value = '';
                    }
                }
            });
        }
        if (endDp) {
            window.pzInitDatePicker(endDp, {
                getMin: function () {
                    var vals = [];
                    if (startHidden && startHidden.value) vals.push(startHidden.value);
                    if (projStart) vals.push(projStart);
                    return vals.length ? vals.reduce(function (a, b) { return a > b ? a : b; }) : '';
                },
                getMax: function () { return projEnd || ''; }
            });
        }
    }

    function showScheduleError(msg) {
        var existing = document.querySelector('.ps-alert-js-error');
        if (existing) existing.remove();
        var div = document.createElement('div');
        div.className = 'ps-alert ps-alert-error ps-alert-js-error';
        div.textContent = msg;
        var form = document.getElementById('scheduleForm');
        form.parentNode.insertBefore(div, form);
        div.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    /* ── Add / remove rows ── */
    window.addRow = function () {
        var clone = tpl.content.cloneNode(true);
        list.appendChild(clone);
        reindex();
        recomputeTotal();
        var rows = list.querySelectorAll('.ps-ms-row');
        var newRow = rows[rows.length - 1];
        newRow.querySelector('.ps-ms-name').focus();
        initMsDatePickers(newRow);
        applyMode(newRow);
    };

    window.removeRow = function (btn) {
        var row = btn.closest('.ps-ms-row');
        if (list.querySelectorAll('.ps-ms-row').length <= 1) {
            row.querySelector('.ps-ms-name').value = '';
            row.querySelector('.weight-input').value = 0;
            var msDateStart = row.querySelector('.ps-ms-date-start');
            if (msDateStart) msDateStart.value = '';
            var msDate = row.querySelector('.ps-ms-date');
            if (msDate) msDate.value = '';
            row.querySelectorAll('.ps-ms-date-picker .date-display').forEach(function (disp) { disp.value = ''; });
            recomputeTotal();
            return;
        }
        row.remove();
        reindex();
        recomputeTotal();
    };

    /* ── Re-index name attributes ── */
    function reindex() {
        var keys = ['name', 'weight', 'qty', 'unit', 'target_start', 'target_date'];
        list.querySelectorAll('.ps-ms-row').forEach(function (row, i) {
            row.querySelectorAll('input.ps-ms-name, input.weight-input, input.ps-ms-qty, select.ps-ms-select, input.ps-ms-date-start, input.ps-ms-date')
               .forEach(function (el, j) { el.name = 'milestones[' + i + '][' + keys[j] + ']'; });
            var modeSel = row.querySelector('.ps-mode-select');
            if (modeSel) modeSel.name = 'milestones[' + i + '][tracking_mode]';
            reindexActivities(row, i);
        });
    }

    /* ── Quantity / Status-only mode ── */
    function applyMode(row) {
        var modeSel = row.querySelector('.ps-mode-select');
        var qtyInp  = row.querySelector('.ps-ms-qty');
        var unitSel = row.querySelector('.ps-ms-select');
        if (!modeSel) return;
        var statusOnly  = modeSel.value === 'status_only';
        // Any activity on the milestone — status-based or quantity-based — means
        // progress is now driven by the activities, so the milestone-level qty/unit
        // no longer apply, regardless of the milestone's own tracking mode.
        var hasActivity = row.querySelectorAll('.pf-act-row').length > 0;
        var hideQty = statusOnly || hasActivity;
        if (qtyInp) {
            qtyInp.disabled = hideQty;
            qtyInp.style.visibility = hideQty ? 'hidden' : '';
            if (hideQty) qtyInp.dataset.prevValue = qtyInp.dataset.prevValue || qtyInp.value;
            qtyInp.value = hideQty ? 1 : (qtyInp.dataset.prevValue || qtyInp.value);
        }
        if (unitSel) unitSel.disabled = hideQty;
    }

    /* ── Activity-level status-only / quantity mode ── */
    function applyActivityMode(actRow) {
        var modeSel     = actRow.querySelector('.pf-act-mode');
        var statusBadge = actRow.querySelector('.pf-act-status-badge');
        var qtyInp      = actRow.querySelector('.pf-act-qty');
        var unitField   = actRow.querySelector('.pf-act-unit-field');
        var valueLbl    = actRow.querySelector('.pf-act-value-lbl');
        if (!modeSel) return;
        var isQty = modeSel.value === 'quantity';
        if (statusBadge) statusBadge.hidden = isQty;
        if (qtyInp) { qtyInp.hidden = !isQty; qtyInp.disabled = !isQty; }
        if (unitField) {
            unitField.hidden = !isQty;
            var unitSel = unitField.querySelector('.pf-act-unit');
            if (unitSel) unitSel.disabled = !isQty;
        }
        if (valueLbl) valueLbl.textContent = isQty ? 'Qty' : 'Status';
    }

    list.addEventListener('change', function (e) {
        if (e.target.classList.contains('ps-mode-select')) {
            applyMode(e.target.closest('.ps-ms-row'));
        }
        if (e.target.classList.contains('pf-act-mode')) {
            applyActivityMode(e.target.closest('.pf-act-row'));
            applyMode(e.target.closest('.ps-ms-row'));
        }
    });

    /* ── Activities (per-milestone sub-list) ── */
    var actTpl = document.getElementById('actRowTpl');

    function reindexActivities(msRow, msIndex) {
        // Status is not a form field (server derives/preserves it) — not reindexed here.
        var actKeys = ['name', 'weight', 'tracking_mode', 'qty', 'unit'];
        msRow.querySelectorAll('.pf-act-row').forEach(function (row, j) {
            row.querySelectorAll('.pf-act-name, .pf-act-weight, .pf-act-mode, .pf-act-qty, .pf-act-unit')
               .forEach(function (el, k) { el.name = 'milestones[' + msIndex + '][activities][' + j + '][' + actKeys[k] + ']'; });
        });
        var count = msRow.querySelectorAll('.pf-act-row').length;
        var countEl = msRow.querySelector('.pf-act-count');
        if (countEl) countEl.textContent = count;
    }

    // Each milestone's activity weights must sum to that milestone's own
    // Weight % (not a fixed 100) — activity weight is on the same scale as
    // milestone weight, just a sub-allocation of it.
    function recomputeActTotal(msRow) {
        var total = 0;
        msRow.querySelectorAll('.pf-act-weight').forEach(function (inp) {
            total += parseFloat(inp.value || 0);
        });
        total = Math.round(total * 10) / 10;
        var msWeightInp = msRow.querySelector('.weight-input');
        var target = msWeightInp ? (parseFloat(msWeightInp.value) || 0) : 0;
        var totValEl    = msRow.querySelector('.pf-act-total-val');
        var totTargetEl = msRow.querySelector('.pf-act-total-target');
        var totEl2      = msRow.querySelector('.pf-act-total');
        if (totValEl) totValEl.textContent = total.toFixed(1);
        if (totTargetEl) totTargetEl.textContent = target.toFixed(1);
        if (totEl2) {
            totEl2.classList.toggle('exact', Math.abs(total - target) < 0.5);
            totEl2.classList.toggle('over',  total > target + 0.5);
        }
    }

    list.addEventListener('click', function (e) {
        var toggleBtn = e.target.closest('.pf-act-toggle-btn');
        if (toggleBtn) {
            var body = toggleBtn.parentElement.querySelector('.pf-act-body');
            var icon = toggleBtn.querySelector('.pf-act-toggle-icon');
            var open = body.hidden;
            body.hidden = !open;
            if (icon) icon.textContent = open ? 'expand_less' : 'expand_more';
            return;
        }

        var addBtn = e.target.closest('.pf-act-add-btn');
        if (addBtn) {
            var msRow = addBtn.closest('.ps-ms-row');
            var actList = msRow.querySelector('.pf-act-list');
            actList.appendChild(actTpl.content.cloneNode(true));
            var msIndex = Array.prototype.indexOf.call(list.querySelectorAll('.ps-ms-row'), msRow);
            reindexActivities(msRow, msIndex);
            recomputeActTotal(msRow);
            applyMode(msRow);
            return;
        }

        var delBtn = e.target.closest('.pf-act-del-btn');
        if (delBtn) {
            var msRow2 = delBtn.closest('.ps-ms-row');
            delBtn.closest('.pf-act-row').remove();
            var msIndex2 = Array.prototype.indexOf.call(list.querySelectorAll('.ps-ms-row'), msRow2);
            reindexActivities(msRow2, msIndex2);
            recomputeActTotal(msRow2);
            applyMode(msRow2);
            return;
        }
    });

    list.addEventListener('input', function (e) {
        if (e.target.classList.contains('pf-act-weight')) {
            recomputeActTotal(e.target.closest('.ps-ms-row'));
        }
    });

    /* ── Drag-to-reorder ── */
    var dragged = null;

    list.addEventListener('dragstart', function (e) {
        dragged = e.target.closest('.ps-ms-row');
        if (dragged) setTimeout(function () { dragged.classList.add('dragging'); }, 0);
    });
    list.addEventListener('dragend', function () {
        if (dragged) dragged.classList.remove('dragging');
        list.querySelectorAll('.ps-ms-row').forEach(function (r) { r.classList.remove('drag-over'); });
        dragged = null;
        reindex();
    });
    list.addEventListener('dragover', function (e) {
        e.preventDefault();
        var target = e.target.closest('.ps-ms-row');
        if (target && target !== dragged) {
            list.querySelectorAll('.ps-ms-row').forEach(function (r) { r.classList.remove('drag-over'); });
            target.classList.add('drag-over');
        }
    });
    list.addEventListener('drop', function (e) {
        e.preventDefault();
        var target = e.target.closest('.ps-ms-row');
        if (target && target !== dragged && dragged) {
            var rows    = Array.from(list.querySelectorAll('.ps-ms-row'));
            var fromIdx = rows.indexOf(dragged);
            var toIdx   = rows.indexOf(target);
            if (fromIdx < toIdx) {
                list.insertBefore(dragged, target.nextSibling);
            } else {
                list.insertBefore(dragged, target);
            }
        }
    });

    /* ── Submit-time guard (catches anything the calendar constraints miss) ── */
    document.getElementById('scheduleForm').addEventListener('submit', function (e) {
        var invalid    = [];
        var outOfRange = [];
        list.querySelectorAll('.ps-ms-row').forEach(function (row, i) {
            var s = row.querySelector('.ps-ms-date-start');
            var d = row.querySelector('.ps-ms-date');
            var nameInp = row.querySelector('.ps-ms-name');
            var label = nameInp && nameInp.value.trim() ? nameInp.value.trim() : ('Milestone ' + (i + 1));
            var sv = s ? s.value : '';
            var dv = d ? d.value : '';
            if (sv && dv && sv > dv) {
                invalid.push(label);
            }
            var beforeStart = (projStart && ((sv && sv < projStart) || (dv && dv < projStart)));
            var afterEnd    = (projEnd && ((sv && sv > projEnd) || (dv && dv > projEnd)));
            if (beforeStart || afterEnd) {
                outOfRange.push(label);
            }
        });
        var msgs = [];
        if (invalid.length > 0) {
            msgs.push('Target end date must be on or after the target start date for: ' + invalid.join(', ') + '.');
        }
        if (outOfRange.length > 0) {
            msgs.push('Milestone target dates must fall within the project\'s start and end dates for: ' + outOfRange.join(', ') + '.');
        }
        if (msgs.length > 0) {
            e.preventDefault();
            e.stopImmediatePropagation();
            showScheduleError(msgs.join(' '));
            return false;
        }
    });

    /* ── Init ── */
    recomputeTotal();
    list.querySelectorAll('.ps-ms-row').forEach(function (row) { initMsDatePickers(row); recomputeActTotal(row); applyMode(row); });
}());
</script>

<?= $this->endSection() ?>
