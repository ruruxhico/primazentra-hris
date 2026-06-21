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

<style>
/* ── Shell ──────────────────────────────────────────────── */
.ps-shell {
    padding: 0 2rem 3rem;
}

/* ── Page header ────────────────────────────────────────── */
.ps-page-hdr {
    display: flex; align-items: center; gap: 1rem;
    padding: 1.2rem 0 1rem;
}
.ps-back-btn {
    background: none; border: none; cursor: pointer;
    color: #8C701C; display: flex; align-items: center;
    padding: 6px; border-radius: 8px; transition: background .12s;
    font-variation-settings: 'FILL' 0, 'wght' 400;
}
.ps-back-btn:hover { background: #f0ead8; }
.ps-back-btn .material-symbols-outlined { font-size: 22px; }
.ps-page-title { font-size: 1.3rem; font-weight: 800; color: #3b2a0e; margin: 0; }
.ps-page-sub   { font-size: 0.8rem; color: #8C701C; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.06em; margin: 0; }

/* ── Section card ───────────────────────────────────────── */
.ps-card {
    background: #fff; border: 1px solid #e0d4b0; border-radius: 14px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 1.2rem;
    overflow: hidden;
}
.ps-card-hdr {
    background: #7a5c1e; color: #fff;
    padding: 0.65rem 1.1rem; font-size: 0.78rem;
    font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase;
}

/* ── Milestone rows ─────────────────────────────────────── */
.ps-ms-list { padding: 0.4rem 0; }

.ps-ms-row {
    display: grid;
    grid-template-columns: 28px 1fr 100px 80px 110px 130px 36px;
    align-items: center; gap: 8px;
    padding: 0.55rem 1rem; border-bottom: 1px solid #f0ebe0;
}
.ps-ms-row:last-child { border-bottom: none; }

.ps-ms-drag {
    color: #ccc; cursor: grab; font-size: 20px;
    font-variation-settings: 'FILL' 0, 'wght' 300;
    user-select: none; touch-action: none;
}
.ps-ms-drag:active { cursor: grabbing; }

.ps-ms-name {
    width: 100%; padding: 6px 10px; border: 1.5px solid #e0d4b0;
    border-radius: 7px; font-size: 0.88rem; font-family: inherit;
    outline: none; background: #fff;
}
.ps-ms-name:focus { border-color: #C47A0F; }

.ps-field-label {
    font-size: 0.65rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.05em; color: #8C701C; margin-bottom: 2px;
}
.ps-ms-num {
    width: 100%; padding: 6px 8px; border: 1.5px solid #e0d4b0;
    border-radius: 7px; font-size: 0.88rem; font-family: inherit;
    outline: none; text-align: center;
}
.ps-ms-num:focus { border-color: #C47A0F; }
.ps-ms-select {
    width: 100%; padding: 6px 4px; border: 1.5px solid #e0d4b0;
    border-radius: 7px; font-size: 0.82rem; font-family: inherit;
    outline: none; background: #fff;
}
.ps-ms-select:focus { border-color: #C47A0F; }
.ps-ms-date {
    width: 100%; padding: 6px 6px; border: 1.5px solid #e0d4b0;
    border-radius: 7px; font-size: 0.82rem; font-family: inherit;
    outline: none;
}
.ps-ms-date:focus { border-color: #C47A0F; }
.ps-ms-del {
    background: none; border: none; cursor: pointer; color: #e53e3e;
    padding: 4px; border-radius: 6px; display: flex; align-items: center;
    transition: background .12s; font-variation-settings: 'FILL' 1;
}
.ps-ms-del:hover { background: #fff0f0; }
.ps-ms-del .material-symbols-outlined { font-size: 18px; }

/* Column header row */
.ps-ms-col-hdr {
    display: grid;
    grid-template-columns: 28px 1fr 100px 80px 110px 130px 36px;
    gap: 8px; padding: 0.3rem 1rem 0.1rem;
    background: #faf6ee; border-bottom: 1px solid #f0ebe0;
}
.ps-ms-col-hdr span {
    font-size: 0.62rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.05em; color: #8C701C;
}

/* ── Add milestone button ───────────────────────────────── */
.ps-add-row {
    padding: 0.65rem 1rem; border-top: 1px solid #f0ebe0;
    display: flex; justify-content: space-between; align-items: center;
}
.ps-add-btn {
    background: none; border: none; cursor: pointer; color: #8C701C;
    font-size: 0.85rem; font-weight: 700; padding: 5px 10px;
    border-radius: 7px; display: flex; align-items: center; gap: 5px;
    font-family: inherit; transition: background .12s;
}
.ps-add-btn:hover { background: #f0ead8; }
.ps-add-btn .material-symbols-outlined { font-size: 18px; }

.ps-total-weight {
    font-size: 0.82rem; font-weight: 800; letter-spacing: 0.04em;
    color: #3b2a0e;
}
.ps-total-weight span { color: #8C701C; margin-left: 6px; }
.ps-total-weight.over  span { color: #c53030; }
.ps-total-weight.exact span { color: #2e7d32; }

/* ── Weekly targets ─────────────────────────────────────── */
.ps-weekly-toggle {
    display: flex; align-items: center; gap: 0.5rem;
    font-size: 1rem; font-weight: 700; color: #3b2a0e;
    cursor: pointer; user-select: none; margin: 0.5rem 0;
}
.ps-weekly-toggle .material-symbols-outlined { font-size: 20px; color: #8C701C; }

.ps-weekly-table {
    width: 100%; border-collapse: collapse; font-size: 0.84rem;
}
.ps-weekly-table th {
    background: #7a5c1e; color: #fff;
    padding: 0.55rem 0.8rem; text-align: left;
    font-size: 0.75rem; letter-spacing: 0.04em;
}
.ps-weekly-table th.num { text-align: right; }
.ps-weekly-table td {
    padding: 0.45rem 0.8rem; border-bottom: 1px solid #f0ebe0;
    color: #3b2a0e;
}
.ps-weekly-table td.num { text-align: right; font-weight: 600; }
.ps-weekly-table tbody tr:hover { background: #fdf8ed; }
.ps-weekly-empty {
    text-align: center; padding: 1rem; color: #888;
    font-style: italic; font-size: 0.85rem;
}

/* ── Footer actions ─────────────────────────────────────── */
.ps-footer {
    display: flex; gap: 0.75rem; padding: 0.5rem 0;
}
.ps-btn {
    padding: 0.65rem 1.6rem; border-radius: 8px;
    font-size: 0.88rem; font-weight: 700; cursor: pointer;
    border: none; font-family: inherit; letter-spacing: 0.04em;
}
.ps-btn-save   { background: #3a7d34; color: #fff; }
.ps-btn-save:hover { background: #2d6228; }
.ps-btn-cancel { background: #e53e3e; color: #fff; }
.ps-btn-cancel:hover { background: #c53030; }

/* ── Alert ──────────────────────────────────────────────── */
.ps-alert {
    padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem;
    font-size: 0.88rem; font-weight: 600;
}
.ps-alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
.ps-alert-error   { background: #fce4ec; color: #b71c1c; border: 1px solid #ef9a9a; }

/* ── Dragging state ─────────────────────────────────────── */
.ps-ms-row.dragging { opacity: .4; }
.ps-ms-row.drag-over { border-top: 2px solid #C47A0F; }
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
                <span>Target Date</span>
                <span></span>
            </div>

            <div class="ps-ms-list" id="msList">
                <?php foreach ($milestones as $i => $m): ?>
                <div class="ps-ms-row" draggable="true">
                    <span class="material-symbols-outlined ps-ms-drag">drag_indicator</span>
                    <input type="text" class="ps-ms-name"
                           name="milestones[<?= $i ?>][name]"
                           value="<?= esc($m['name'] ?? '') ?>"
                           placeholder="Milestone name" required>
                    <input type="number" class="ps-ms-num weight-input"
                           name="milestones[<?= $i ?>][weight]"
                           value="<?= esc($m['weight'] ?? 0) ?>"
                           step="0.1" min="0" max="100" placeholder="0">
                    <input type="number" class="ps-ms-num"
                           name="milestones[<?= $i ?>][qty]"
                           value="<?= esc($m['qty'] ?? 1) ?>"
                           step="1" min="1" placeholder="1">
                    <select class="ps-ms-select" name="milestones[<?= $i ?>][unit]">
                        <?php foreach ($units as $u): ?>
                        <option value="<?= esc($u) ?>" <?= ($m['unit'] ?? 'pcs') === $u ? 'selected' : '' ?>><?= esc($u) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" class="ps-ms-date"
                           name="milestones[<?= $i ?>][target_date]"
                           value="<?= esc($m['targetDate'] ?? '') ?>">
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
        <input type="number" class="ps-ms-num" value="1" step="1" min="1" placeholder="1">
        <select class="ps-ms-select">
            <?php foreach ($units as $u): ?>
            <option value="<?= esc($u) ?>"><?= esc($u) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" class="ps-ms-date">
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
        rows[rows.length - 1].querySelector('.ps-ms-name').focus();
    };

    window.removeRow = function (btn) {
        var row = btn.closest('.ps-ms-row');
        if (list.querySelectorAll('.ps-ms-row').length <= 1) {
            row.querySelector('.ps-ms-name').value = '';
            row.querySelector('.weight-input').value = 0;
            row.querySelector('.ps-ms-date').value = '';
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
            var fieldEls = row.querySelectorAll('input.ps-ms-name, input.weight-input, input[placeholder="1"], select.ps-ms-select, input.ps-ms-date');
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
}());
</script>

<?= $this->endSection() ?>
