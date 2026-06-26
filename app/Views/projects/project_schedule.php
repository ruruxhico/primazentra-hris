<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $project       = $project       ?? [];
    $projectId     = $projectId     ?? '';
    $milestones    = $milestones    ?? [];
    $weeklyTargets = $weeklyTargets ?? [];
    $startDate     = $startDate     ?? '';
    $endDate       = $endDate       ?? '';

    $pName  = esc(strtoupper($project['project_name'] ?? 'PROJECT'));
    $canEdit = pz_can('manage_milestones');

    $units = ['pcs', 'lot', 'm', 'm²', 'm³', 'kg', 'ton', 'bag', 'set', 'unit', 'lm'];
?>


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
                <span>Target Date</span>
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
                        $tdVal  = $m['target_date'] ?? $m['targetDate'] ?? '';
                        $tdDisp = ($tdVal !== '' && ($tdTs = strtotime($tdVal)) !== false) ? date('d/m/Y', $tdTs) : '';
                    ?>
                    <div class="date-picker ps-ms-date-picker">
                        <input type="text" class="date-display" placeholder="DD/MM/YYYY" value="<?= esc($tdDisp) ?>" readonly>
                        <input type="hidden" class="ps-ms-date" name="milestones[<?= $i ?>][target_date]" value="<?= esc($tdVal) ?>">
                        <button type="button" class="date-toggle"><span class="material-symbols-outlined">calendar_month</span></button>
                        <div class="calendar-popup" aria-hidden="true"></div>
                    </div>
                    <button type="button" class="ps-ms-del" onclick="removeRow(this)" title="Remove">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="ps-add-row">
                <button type="button" class="ps-add-btn" onclick="addRow()">
                    <span class="material-symbols-outlined">add</span> Add Milestone
                </button>
                <div class="ps-total-weight" id="totalWeightDisplay">
                    TOTAL WEIGHT <span id="totalWeightVal">0.0</span>%
                </div>
            </div>
        </div>

        <!-- Weekly Progress Targets -->
        <div style="margin-bottom:1.2rem;">
            <div class="ps-weekly-toggle" onclick="toggleWeekly(this)">
                <span class="material-symbols-outlined">calendar_month</span>
                Weekly Progress Targets
                <span class="material-symbols-outlined" style="margin-left:auto;font-size:18px;transition:transform .2s" id="weeklyChevron">expand_more</span>
            </div>
            <div id="weeklySection">
                <div class="ps-card" style="margin-bottom:0">
                    <table class="ps-weekly-table">
                        <thead>
                            <tr>
                                <th>Week No.</th>
                                <th class="num">Running Cumulative %</th>
                                <th class="num">Cumulative Target %</th>
                                <th class="num">Variance (+/−)</th>
                            </tr>
                        </thead>
                        <tbody id="weeklyTbody">
                            <?php if (empty($weeklyTargets)): ?>
                            <tr><td colspan="4" class="ps-weekly-empty">
                                <?php if ($startDate === ''): ?>
                                    No start date set on this project. Set a start date to see weekly targets.
                                <?php else: ?>
                                    No milestones with target dates yet. Add target dates to see weekly targets.
                                <?php endif; ?>
                            </td></tr>
                            <?php else: ?>
                            <?php $weekNo = 1; foreach ($weeklyTargets as $wt): ?>
                            <?php
                                $weekLabel = 'Week ' . $weekNo . ' (' . date('d-M-y', strtotime($wt['weekEnd'])) . ')';
                                $target    = number_format((float)$wt['target'], 1);
                            ?>
                            <tr>
                                <td><?= esc($weekLabel) ?></td>
                                <td class="num">—</td>
                                <td class="num"><?= $target ?></td>
                                <td class="num">—</td>
                            </tr>
                            <?php $weekNo++; endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
            <input type="hidden" class="ps-ms-date">
            <button type="button" class="date-toggle"><span class="material-symbols-outlined">calendar_month</span></button>
            <div class="calendar-popup" aria-hidden="true"></div>
        </div>
        <button type="button" class="ps-ms-del" onclick="removeRow(this)" title="Remove">
            <span class="material-symbols-outlined">delete</span>
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
        var dp = newRow.querySelector('.ps-ms-date-picker');
        if (dp && window.pzInitDatePicker) window.pzInitDatePicker(dp, {});
    };

    window.removeRow = function (btn) {
        var row = btn.closest('.ps-ms-row');
        if (list.querySelectorAll('.ps-ms-row').length <= 1) {
            row.querySelector('.ps-ms-name').value = '';
            row.querySelector('.weight-input').value = 0;
            var msDate = row.querySelector('.ps-ms-date');
            if (msDate) msDate.value = '';
            var msDisp = row.querySelector('.ps-ms-date-picker .date-display');
            if (msDisp) msDisp.value = '';
            recomputeTotal();
            return;
        }
        row.remove();
        reindex();
        recomputeTotal();
    };

    /* ── Re-index name attributes ── */
    function reindex() {
        list.querySelectorAll('.ps-ms-row').forEach(function (row, i) {
            var fields = row.querySelectorAll('input, select');
            var names  = ['name', 'weight', 'qty', 'unit', 'target_date'];
            // inputs: name, weight, qty, date / select: unit
            var fieldEls = row.querySelectorAll('input.ps-ms-name, input.weight-input, input.ps-ms-qty, select.ps-ms-select, input.ps-ms-date');
            var keys = ['name', 'weight', 'qty', 'unit', 'target_date'];
            fieldEls.forEach(function (el, j) {
                el.name = 'milestones[' + i + '][' + keys[j] + ']';
            });
        });
    }

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

    /* ── Weekly toggle ── */
    window.toggleWeekly = function (el) {
        var sec = document.getElementById('weeklySection');
        var chv = document.getElementById('weeklyChevron');
        var hidden = sec.style.display === 'none';
        sec.style.display = hidden ? '' : 'none';
        chv.textContent = hidden ? 'expand_less' : 'expand_more';
    };

    /* ── Init ── */
    recomputeTotal();
    list.querySelectorAll('.ps-ms-date-picker').forEach(function (dp) {
        if (window.pzInitDatePicker) window.pzInitDatePicker(dp, {});
    });
}());
</script>

<?= $this->endSection() ?>
