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
.ps-act-panel { padding:0.35rem 0 0.15rem; }
.ps-act-toggle-btn { display:inline-flex; align-items:center; gap:2px; background:none; border:none; cursor:pointer; color:#8C701C; font-size:0.76rem; font-weight:700; padding:2px 4px; border-radius:5px; }
.ps-act-toggle-btn:hover { background:#faf6ee; }
.ps-act-toggle-icon { font-size:1rem; }
.ps-act-body { margin:0.4rem 0 0.2rem 1.75rem; border-left:2px solid #f0ebe0; padding-left:0.75rem; }
.ps-act-col-hdr { display:grid; grid-template-columns:1fr 90px 140px 32px; gap:8px; padding:0 0 0.3rem; }
.ps-act-col-hdr span { font-size:0.62rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#8C701C; }
.ps-act-row { display:grid; grid-template-columns:1fr 90px 140px 32px; gap:8px; align-items:center; margin-bottom:0.4rem; }
.ps-act-name { font-size:0.82rem; padding:5px 8px; border:1px solid #e0dbd0; border-radius:6px; font-family:inherit; }
.ps-act-weight-wrap { position:relative; }
.ps-act-weight { width:100%; padding:5px 22px 5px 8px; border:1px solid #e0dbd0; border-radius:6px; font-size:0.82rem; text-align:right; font-family:inherit; }
.ps-act-weight-suffix { position:absolute; right:8px; top:50%; transform:translateY(-50%); font-size:0.76rem; color:#999; pointer-events:none; }
.ps-act-status { padding:5px 6px; border:1px solid #e0dbd0; border-radius:6px; font-size:0.78rem; background:#fff; font-family:inherit; }
.ps-act-name:focus, .ps-act-weight:focus, .ps-act-status:focus { outline:none; border-color:#C47A0F; }
.ps-act-del { background:none; border:none; cursor:pointer; color:#e53e3e; padding:4px; border-radius:6px; display:flex; align-items:center; justify-content:center; }
.ps-act-del:hover { background:#fff0f0; }
.ps-act-del .material-symbols-outlined { font-size:16px; }
.ps-act-add-row { display:flex; align-items:center; justify-content:space-between; gap:0.75rem; margin-top:0.15rem; }
.ps-act-add-btn { background:none; border:1px dashed #C47A0F; color:#C47A0F; font-size:0.76rem; font-weight:700; padding:4px 10px; border-radius:6px; cursor:pointer; }
.ps-act-add-btn:hover { background:#fff8ec; }
.ps-act-total { font-size:0.72rem; font-weight:700; color:#999; }
.ps-act-total.exact { color:#2f7d4f; }
.ps-act-total.over  { color:#e53e3e; }
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
                        <button type="button" class="ps-act-toggle-btn">
                            <span class="material-symbols-outlined ps-act-toggle-icon">expand_more</span>
                            Activities (<span class="ps-act-count"><?= count($m['activities'] ?? []) ?></span>)
                        </button>
                        <div class="ps-act-body" hidden>
                            <div class="ps-act-col-hdr">
                                <span>Activity Name</span>
                                <span>Weight %</span>
                                <span>Status</span>
                                <span></span>
                            </div>
                            <div class="ps-act-list">
                                <?php foreach (($m['activities'] ?? []) as $ai => $a): ?>
                                <div class="ps-act-row">
                                    <input type="text" class="ps-act-name"
                                           name="milestones[<?= $i ?>][activities][<?= $ai ?>][name]"
                                           value="<?= esc($a['activity_name'] ?? $a['name'] ?? '') ?>"
                                           placeholder="Activity name">
                                    <div class="ps-act-weight-wrap">
                                        <input type="number" class="ps-act-weight"
                                               name="milestones[<?= $i ?>][activities][<?= $ai ?>][weight]"
                                               value="<?= esc($a['weight_percent'] ?? $a['weight'] ?? 0) ?>"
                                               step="0.1" min="0" max="100" placeholder="0">
                                        <span class="ps-act-weight-suffix">%</span>
                                    </div>
                                    <select class="ps-act-status" name="milestones[<?= $i ?>][activities][<?= $ai ?>][status]">
                                        <?php foreach (['Not Started', 'In Progress', 'Completed'] as $st): ?>
                                        <option value="<?= esc($st) ?>" <?= ($a['status'] ?? 'Not Started') === $st ? 'selected' : '' ?>><?= esc($st) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="ps-act-del" title="Remove activity">
                                        <span class="material-symbols-outlined">close</span>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="ps-act-add-row">
                                <button type="button" class="ps-act-add-btn">+ Add Activity</button>
                                <span class="ps-act-total">Activities total <span class="ps-act-total-val">0.0</span>%</span>
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
            <button type="button" class="ps-act-toggle-btn">
                <span class="material-symbols-outlined ps-act-toggle-icon">expand_more</span>
                Activities (<span class="ps-act-count">0</span>)
            </button>
            <div class="ps-act-body" hidden>
                <div class="ps-act-col-hdr">
                    <span>Activity Name</span>
                    <span>Weight %</span>
                    <span>Status</span>
                    <span></span>
                </div>
                <div class="ps-act-list"></div>
                <div class="ps-act-add-row">
                    <button type="button" class="ps-act-add-btn">+ Add Activity</button>
                    <span class="ps-act-total">Activities total <span class="ps-act-total-val">0.0</span>%</span>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- Activity row template (hidden) -->
<template id="actRowTpl">
    <div class="ps-act-row">
        <input type="text" class="ps-act-name" placeholder="Activity name">
        <div class="ps-act-weight-wrap">
            <input type="number" class="ps-act-weight" value="0" step="0.1" min="0" max="100" placeholder="0">
            <span class="ps-act-weight-suffix">%</span>
        </div>
        <select class="ps-act-status">
            <option value="Not Started">Not Started</option>
            <option value="In Progress">In Progress</option>
            <option value="Completed">Completed</option>
        </select>
        <button type="button" class="ps-act-del" title="Remove activity">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>
</template>

<script>
(function () {
    var list   = document.getElementById('msList');
    var tpl    = document.getElementById('rowTpl');
    var totEl  = document.getElementById('totalWeightDisplay');
    var totVal = document.getElementById('totalWeightVal');

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
        if (e.target.classList.contains('weight-input')) recomputeTotal();
    });

    /* ── Add / remove rows ── */
    window.addRow = function () {
        var clone = tpl.content.cloneNode(true);
        list.appendChild(clone);
        reindex();
        recomputeTotal();
        var rows = list.querySelectorAll('.ps-ms-row');
        var newRow = rows[rows.length - 1];
        newRow.querySelector('.ps-ms-name').focus();
        newRow.querySelectorAll('.ps-ms-date-picker').forEach(function (dp) {
            if (window.pzInitDatePicker) window.pzInitDatePicker(dp, {});
        });
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
        var statusOnly = modeSel.value === 'status_only';
        if (qtyInp) {
            qtyInp.disabled = statusOnly;
            if (statusOnly) qtyInp.dataset.prevValue = qtyInp.dataset.prevValue || qtyInp.value;
            qtyInp.value = statusOnly ? 1 : (qtyInp.dataset.prevValue || qtyInp.value);
        }
        if (unitSel) unitSel.disabled = statusOnly;
    }

    list.addEventListener('change', function (e) {
        if (e.target.classList.contains('ps-mode-select')) {
            applyMode(e.target.closest('.ps-ms-row'));
        }
    });

    /* ── Activities (per-milestone sub-list) ── */
    var actTpl = document.getElementById('actRowTpl');

    function reindexActivities(msRow, msIndex) {
        var actKeys = ['name', 'weight', 'status'];
        msRow.querySelectorAll('.ps-act-row').forEach(function (row, j) {
            row.querySelectorAll('.ps-act-name, .ps-act-weight, .ps-act-status')
               .forEach(function (el, k) { el.name = 'milestones[' + msIndex + '][activities][' + j + '][' + actKeys[k] + ']'; });
        });
        var count = msRow.querySelectorAll('.ps-act-row').length;
        var countEl = msRow.querySelector('.ps-act-count');
        if (countEl) countEl.textContent = count;
    }

    function recomputeActTotal(msRow) {
        var total = 0;
        msRow.querySelectorAll('.ps-act-weight').forEach(function (inp) {
            total += parseFloat(inp.value || 0);
        });
        total = Math.round(total * 10) / 10;
        var totValEl = msRow.querySelector('.ps-act-total-val');
        var totEl2   = msRow.querySelector('.ps-act-total');
        if (totValEl) totValEl.textContent = total.toFixed(1);
        if (totEl2) {
            totEl2.classList.toggle('exact', Math.abs(total - 100) < 0.5);
            totEl2.classList.toggle('over',  total > 100.5);
        }
    }

    list.addEventListener('click', function (e) {
        var toggleBtn = e.target.closest('.ps-act-toggle-btn');
        if (toggleBtn) {
            var body = toggleBtn.parentElement.querySelector('.ps-act-body');
            var icon = toggleBtn.querySelector('.ps-act-toggle-icon');
            var open = body.hidden;
            body.hidden = !open;
            if (icon) icon.textContent = open ? 'expand_less' : 'expand_more';
            return;
        }

        var addBtn = e.target.closest('.ps-act-add-btn');
        if (addBtn) {
            var msRow = addBtn.closest('.ps-ms-row');
            var actList = msRow.querySelector('.ps-act-list');
            actList.appendChild(actTpl.content.cloneNode(true));
            var msIndex = Array.prototype.indexOf.call(list.querySelectorAll('.ps-ms-row'), msRow);
            reindexActivities(msRow, msIndex);
            recomputeActTotal(msRow);
            return;
        }

        var delBtn = e.target.closest('.ps-act-del');
        if (delBtn) {
            var msRow2 = delBtn.closest('.ps-ms-row');
            delBtn.closest('.ps-act-row').remove();
            var msIndex2 = Array.prototype.indexOf.call(list.querySelectorAll('.ps-ms-row'), msRow2);
            reindexActivities(msRow2, msIndex2);
            recomputeActTotal(msRow2);
            return;
        }
    });

    list.addEventListener('input', function (e) {
        if (e.target.classList.contains('ps-act-weight')) {
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

    /* ── Init ── */
    recomputeTotal();
    list.querySelectorAll('.ps-ms-date-picker').forEach(function (dp) {
        if (window.pzInitDatePicker) window.pzInitDatePicker(dp, {});
    });
    list.querySelectorAll('.ps-ms-row').forEach(function (row) { recomputeActTotal(row); applyMode(row); });
}());
</script>

<?= $this->endSection() ?>
