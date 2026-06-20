<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $projectId   = $projectId   ?? '';
    $project     = $project     ?? [];
    $projectName = strtoupper($project['project_name'] ?? 'PROJECT');

    // Collect unique roles / specializations for bulk-assign dropdowns
    $mpRoles = [];
    $mpSpecs = [];
    foreach (($allManpower ?? []) as $m) {
        if (!empty($m['role']))           $mpRoles[$m['role']] = true;
        if (!empty($m['specialization'])) $mpSpecs[$m['specialization']] = true;
    }
    ksort($mpRoles);
    ksort($mpSpecs);
?>

<style>
/* ── Shell ───────────────────────────────────────────────────────── */
.pres-shell { padding-bottom: 2rem; }

/* ── Header ──────────────────────────────────────────────────────── */
.pres-header {
    display: flex; align-items: center; gap: 10px;
    margin-bottom: 1.4rem;
}
.pres-back {
    display: inline-flex; align-items: center;
    color: #8C701C; text-decoration: none; line-height: 1;
}
.pres-back:hover { color: #5c3d11; text-decoration: none; }
.pres-back .material-symbols-outlined { font-size: 22px; }
.pres-title { font-size: 1.15rem; font-weight: 800; color: #2c1f07; margin: 0; }

/* ── Sections ────────────────────────────────────────────────────── */
.pres-section-label {
    font-size: 1rem; font-weight: 700; color: #8C701C;
    margin: 0 0 0.9rem; letter-spacing: 0.01em;
}
.pres-divider { border: none; border-top: 1px solid #e5e0d0; margin: 1.5rem 0; }
.pres-empty { font-size: 0.85rem; color: #aaa; margin: 0; }

/* ── Assigned resources list ─────────────────────────────────────── */
.pres-assigned-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 10px; margin-bottom: 4px;
}
.pres-assigned-card {
    background: #fff; border: 1px solid #e5e0d0; border-radius: 10px;
    padding: 14px 18px; margin-bottom: 0;
    display: flex; align-items: center; gap: 12px;
}
.pres-assigned-icon { color: #C47A0F; font-size: 1.2rem;
    font-variation-settings: 'FILL' 1, 'wght' 600; }
.pres-assigned-name { font-size: 0.9rem; font-weight: 700; color: #2c1f07; }
.pres-assigned-meta { font-size: 0.76rem; color: #999; margin-top: 2px; }

/* ── Tabs ─────────────────────────────────────────────────────────── */
.pres-tabs {
    display: flex; border-bottom: 2px solid #e5e0d0; margin-bottom: 1rem;
}
.pres-tab {
    background: none; border: none; padding: 8px 18px;
    font-size: 0.8rem; font-weight: 700; letter-spacing: 0.06em;
    color: #bbb; cursor: pointer; border-bottom: 2px solid transparent;
    margin-bottom: -2px; font-family: inherit;
}
.pres-tab.active { color: #8C701C; border-bottom-color: #C47A0F; }
.pres-tab:hover { color: #8C701C; }
.pres-tab-panel { display: none; }
.pres-tab-panel.active { display: block; }

/* ── Manpower tab header ─────────────────────────────────────────── */
.pres-mp-header {
    display: flex; justify-content: flex-start; margin-bottom: 1rem;
}
.pres-btn-bulk-assign {
    display: inline-flex; align-items: center; gap: 6px;
    background: #3a2a0a; color: #fff; border: none; border-radius: 8px;
    padding: 9px 18px; font-size: 0.82rem; font-weight: 700;
    letter-spacing: 0.05em; cursor: pointer; font-family: inherit;
    font-variation-settings: 'FILL' 1, 'wght' 600;
}
.pres-btn-bulk-assign:hover { background: #5c3d11; }
.pres-btn-bulk-assign .material-symbols-outlined { font-size: 17px; }

/* ── Resource card grid ──────────────────────────────────────────── */
.pres-res-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 10px;
}

/* Base card — horizontal (manpower) */
.pres-res-card {
    background: #fff; border: 1px solid #e5e0d0; border-radius: 12px;
    padding: 16px 18px; margin-bottom: 0;
    display: flex; align-items: center; gap: 14px;
    transition: border-color .15s, box-shadow .15s;
}
.pres-res-card:hover { border-color: #d4c185; box-shadow: 0 2px 8px rgba(140,112,28,.08); }
.pres-res-info { flex: 1; min-width: 0; }
.pres-res-name { font-size: 0.95rem; font-weight: 800; color: #2c1f07; }
.pres-res-sub  { font-size: 0.78rem; color: #999; margin-top: 3px; }
.pres-res-avail { color: #5a8a3c; }
.pres-res-site { font-size: 0.75rem; color: #bbb; margin-top: 3px; }
.pres-res-site-here { color: #8C701C; font-weight: 600; }
.pres-res-actions { display: flex; gap: 8px; flex-shrink: 0; }

/* Column variant — vertical (equipment / tools) */
.pres-res-card-col {
    flex-direction: column;
    align-items: stretch;
}
.pres-res-card-col .pres-res-info { flex: none; }
.pres-res-card-col .pres-res-actions {
    display: flex; gap: 8px;
    margin-top: 14px; padding-top: 12px;
    border-top: 1px solid #f0ebe0;
    justify-content: flex-end;
}

/* ── Status buttons ──────────────────────────────────────────────── */
.pres-btn {
    border: none; border-radius: 20px; padding: 6px 14px;
    font-size: 0.75rem; font-weight: 700; cursor: pointer; font-family: inherit;
    white-space: nowrap; letter-spacing: 0.04em;
}
.pres-btn:disabled { cursor: not-allowed; opacity: 0.55; }
.pres-btn-assign  { background: #8C701C; color: #fff; }
.pres-btn-assign:hover:not(:disabled)  { background: #6e570f; }
.pres-btn-remove  { background: #c62828; color: #fff; }
.pres-btn-remove:hover:not(:disabled)  { background: #7f0000; }
.pres-btn-unavail { background: #f0ede6; color: #bbb; border: 1px solid #e0dbd0; }
.pres-btn-view    { background: #3a2a0a; color: #fff; }
.pres-btn-view:hover { background: #5c3d11; }
.pres-btn-bulk    {
    background: none; color: #8C701C; border: 1.5px solid #C47A0F;
    border-radius: 20px; padding: 5px 14px;
    font-size: 0.75rem; font-weight: 700; cursor: pointer; font-family: inherit;
    white-space: nowrap; letter-spacing: 0.04em;
}
.pres-btn-bulk:hover { background: #fdf8ee; }

/* ── Toast ───────────────────────────────────────────────────────── */
.pres-toast {
    position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%) translateY(60px);
    background: #e65100; color: #fff; padding: 10px 20px; border-radius: 8px;
    font-size: 0.85rem; font-weight: 600; z-index: 9999;
    transition: transform 0.25s ease, opacity 0.25s ease; opacity: 0; pointer-events: none;
    white-space: nowrap; max-width: 90vw;
}
.pres-toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
.pres-toast.success { background: #2e7d32; }

/* ── Modals ──────────────────────────────────────────────────────── */
.pres-modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,0.45); z-index: 1050;
    align-items: center; justify-content: center;
    padding: 1rem;
}
.pres-modal-overlay.open { display: flex; }
.pres-modal {
    background: #fff; border-radius: 16px; width: 100%; max-width: 540px;
    padding: 1.8rem 2rem 2rem; max-height: 88vh; overflow-y: auto;
    box-shadow: 0 8px 40px rgba(0,0,0,0.22);
    animation: presModalIn .2s ease;
}
@keyframes presModalIn {
    from { opacity: 0; transform: scale(0.95) translateY(-12px); }
    to   { opacity: 1; transform: scale(1)    translateY(0); }
}
.pres-modal-hdr {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 0.3rem;
}
.pres-modal-title { font-size: 1rem; font-weight: 800; color: #2c1f07; margin: 0; }
.pres-modal-close {
    background: none; border: none; font-size: 1.4rem; cursor: pointer;
    color: #aaa; line-height: 1; padding: 4px;
}
.pres-modal-close:hover { color: #555; }
.pres-modal-sub { font-size: 0.8rem; color: #999; margin: 0 0 1rem; }

/* Units modal list */
.pres-unit-card {
    display: flex; align-items: center; justify-content: space-between; gap: 10px;
    background: #fdf8ee; border: 1px solid #e8d9a8; border-radius: 8px;
    padding: 10px 14px; margin-bottom: 8px;
}
.pres-unit-serial { font-size: 0.88rem; font-weight: 700; }
.pres-unit-status { font-size: 0.75rem; color: #999; margin-top: 2px; }

/* Bulk Assign Manpower modal */
.pres-bulk-field { margin-bottom: 1rem; }
.pres-bulk-field label {
    display: block; font-size: 0.78rem; font-weight: 700; color: #666;
    text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;
}
.pres-bulk-select {
    width: 100%; padding: 9px 12px; border: 1px solid #ddd; border-radius: 8px;
    font-size: 0.9rem; font-family: inherit; background: #fff;
    appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath d='M0 0l6 8 6-8z' fill='%23aaa'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 12px center;
}
.pres-bulk-input {
    width: 100%; padding: 9px 12px; border: 1px solid #ddd; border-radius: 8px;
    font-size: 0.9rem; font-family: inherit; background: #fff; box-sizing: border-box;
    outline: none;
}
.pres-bulk-input:focus { border-color: #C47A0F; }
.pres-qty-row {
    display: flex; align-items: center; justify-content: center; gap: 20px;
    margin: 1rem 0 1.4rem;
}
.pres-qty-btn {
    width: 34px; height: 34px; border-radius: 50%;
    border: 1.5px solid #e0dbd0; background: #fff; font-size: 1.2rem;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; color: #8C701C; font-weight: 700;
}
.pres-qty-btn:hover { background: #fdf8ee; }
.pres-qty-val { font-size: 1.2rem; font-weight: 700; min-width: 2ch; text-align: center; }
.pres-modal-actions { display: flex; gap: 10px; }
.pres-modal-btn {
    flex: 1; padding: 12px; border-radius: 8px; border: none;
    font-size: 0.9rem; font-weight: 700; cursor: pointer; font-family: inherit;
}
.pres-modal-btn-cancel { background: #f0ede6; color: #5c3d11; border: 1.5px solid #e0dbd0; }
.pres-modal-btn-cancel:hover { background: #e5e0d0; }
.pres-modal-btn-confirm { background: #3a2a0a; color: #fff; }
.pres-modal-btn-confirm:hover { background: #5c3d11; }
</style>

<div class="pres-shell">

    <!-- Header -->
    <div class="pres-header">
        <a href="<?= base_url('projects/show/' . $projectId) ?>" class="pres-back" title="Back">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <h1 class="pres-title"><?= esc($projectName) ?></h1>
    </div>

    <!-- ── Assigned Resources ──────────────────────────────────── -->
    <p class="pres-section-label">Assigned Resources</p>

    <?php if (empty($assignedManpower) && empty($assignedEqGroups)): ?>
        <p class="pres-empty">No resources assigned to this project yet.</p>
    <?php else: ?>
    <div class="pres-assigned-grid">
        <?php foreach ($assignedEqGroups as $g): ?>
        <div class="pres-assigned-card">
            <span class="material-symbols-outlined pres-assigned-icon">build</span>
            <div>
                <div class="pres-assigned-name"><?= esc(strtoupper($g['name'])) ?></div>
                <div class="pres-assigned-meta"><?= (int)$g['count'] ?> assigned unit(s)</div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php foreach ($assignedManpower as $a): ?>
        <div class="pres-assigned-card">
            <span class="material-symbols-outlined pres-assigned-icon">person</span>
            <div>
                <div class="pres-assigned-name"><?= esc(strtoupper($a['name'])) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <hr class="pres-divider">

    <!-- ── Add Resources ──────────────────────────────────────── -->
    <p class="pres-section-label">Add Resources</p>

    <!-- Tabs -->
    <div class="pres-tabs">
        <button class="pres-tab active" data-panel="manpower">MANPOWER</button>
        <button class="pres-tab" data-panel="equipment">EQUIPMENT</button>
        <button class="pres-tab" data-panel="tools">TOOLS</button>
    </div>

    <!-- ── MANPOWER panel ─────────────────────────────────────── -->
    <div class="pres-tab-panel active" id="panel-manpower">
        <div class="pres-mp-header">
            <button class="pres-btn-bulk-assign" id="btnBulkManpower">
                <span class="material-symbols-outlined">groups</span>
                BULK ASSIGN
            </button>
        </div>

        <?php if (empty($allManpower)): ?>
        <p class="pres-empty">No manpower records found.</p>
        <?php else: ?>
        <div class="pres-res-grid">
        <?php foreach ($allManpower as $m):
            $mpId         = $m['id'];
            $mpName       = strtoupper($m['name'] ?: 'UNNAMED');
            $mpRole       = strtoupper($m['role'] ?? '');
            $mpStatus     = $m['status'] ?? 'Available';
            $mpSite       = $m['current_site'] ?? '';
            $isHere       = isset($assignedManpowerIds[$mpId]);
            $assignmentId = $assignedManpowerIds[$mpId] ?? '';
        ?>
        <div class="pres-res-card">
            <div class="pres-res-info">
                <div class="pres-res-name"><?= esc($mpName) ?></div>
                <?php if ($mpRole): ?>
                <div class="pres-res-sub"><?= esc($mpRole) ?></div>
                <?php endif; ?>
                <?php if ($isHere): ?>
                    <div class="pres-res-site pres-res-site-here">Assigned to this project</div>
                <?php elseif ($mpSite): ?>
                    <div class="pres-res-site">Current site: <?= esc($mpSite) ?></div>
                <?php endif; ?>
            </div>
            <?php if ($isHere): ?>
            <button class="pres-btn pres-btn-remove"
                    data-action="unassign"
                    data-assignment-id="<?= esc($assignmentId) ?>">REMOVE</button>
            <?php elseif ($mpStatus === 'Available'): ?>
            <button class="pres-btn pres-btn-assign"
                    data-action="assign"
                    data-type="manpower"
                    data-ref-id="<?= esc($mpId) ?>"
                    data-name="<?= esc($mpName) ?>">ASSIGN</button>
            <?php else: ?>
            <button class="pres-btn pres-btn-unavail" disabled>UNAVAILABLE</button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── EQUIPMENT panel ────────────────────────────────────── -->
    <div class="pres-tab-panel" id="panel-equipment">
        <?php if (empty($allEquipment)): ?>
        <p class="pres-empty">No equipment found.</p>
        <?php else: ?>
        <div class="pres-res-grid">
        <?php foreach ($allEquipment as $eq):
            $eqId      = $eq['id'];
            $eqName    = strtoupper($eq['name'] ?? '');
            $eqStats   = $resourceItemStats[$eqId] ?? ['total' => 0, 'available' => 0, 'assigned' => 0];
        ?>
        <div class="pres-res-card pres-res-card-col">
            <div class="pres-res-info">
                <div class="pres-res-name"><?= esc($eqName) ?></div>
                <div class="pres-res-sub">Equipment model</div>
                <div class="pres-res-sub">&bull; <?= (int)$eqStats['assigned'] ?> assigned unit(s)</div>
                <div class="pres-res-sub pres-res-avail">&bull; <?= (int)$eqStats['available'] ?> available unit(s)</div>
            </div>
            <div class="pres-res-actions">
                <button class="pres-btn-bulk"
                        data-action="bulk-equipment"
                        data-resource-id="<?= esc($eqId) ?>"
                        data-available="<?= (int)$eqStats['available'] ?>">BULK</button>
                <button class="pres-btn pres-btn-view"
                        data-action="view-units"
                        data-resource-id="<?= esc($eqId) ?>"
                        data-resource-name="<?= esc($eqName) ?>"
                        data-resource-type="Equipment">VIEW UNITS</button>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── TOOLS panel ────────────────────────────────────────── -->
    <div class="pres-tab-panel" id="panel-tools">
        <?php if (empty($allTools)): ?>
        <p class="pres-empty">No tools found.</p>
        <?php else: ?>
        <div class="pres-res-grid">
        <?php foreach ($allTools as $tool):
            $toolId    = $tool['id'];
            $toolName  = strtoupper($tool['name'] ?? '');
            $toolStats = $resourceItemStats[$toolId] ?? ['total' => 0, 'available' => 0, 'assigned' => 0];
        ?>
        <div class="pres-res-card pres-res-card-col">
            <div class="pres-res-info">
                <div class="pres-res-name"><?= esc($toolName) ?></div>
                <div class="pres-res-sub">Tool</div>
                <div class="pres-res-sub">&bull; <?= (int)$toolStats['assigned'] ?> assigned unit(s)</div>
                <div class="pres-res-sub pres-res-avail">&bull; <?= (int)$toolStats['available'] ?> available unit(s)</div>
            </div>
            <div class="pres-res-actions">
                <button class="pres-btn-bulk"
                        data-action="bulk-equipment"
                        data-resource-id="<?= esc($toolId) ?>"
                        data-available="<?= (int)$toolStats['available'] ?>">BULK</button>
                <button class="pres-btn pres-btn-view"
                        data-action="view-units"
                        data-resource-id="<?= esc($toolId) ?>"
                        data-resource-name="<?= esc($toolName) ?>"
                        data-resource-type="Tool">VIEW UNITS</button>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /.pres-shell -->

<!-- ── Toast ──────────────────────────────────────────────────── -->
<div class="pres-toast" id="presToast"></div>

<!-- ── Bulk Assign Manpower modal ─────────────────────────────── -->
<div class="pres-modal-overlay" id="modalBulkManpower">
    <div class="pres-modal">
        <div class="pres-modal-hdr">
            <h3 class="pres-modal-title">Bulk Assign Manpower</h3>
            <button class="pres-modal-close" data-close="modalBulkManpower">&times;</button>
        </div>
        <p class="pres-modal-sub">Filter available workers by role, specialization, or skill. Matching workers are randomly selected and assigned.</p>

        <div class="pres-bulk-field">
            <label>Role (optional)</label>
            <select class="pres-bulk-select" id="bulkRole">
                <option value="">Any role</option>
                <?php foreach (array_keys($mpRoles) as $r): ?>
                <option value="<?= esc($r) ?>"><?= esc($r) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="pres-bulk-field">
            <label>Specialization (optional)</label>
            <select class="pres-bulk-select" id="bulkSpec">
                <option value="">Any specialization</option>
                <?php foreach (array_keys($mpSpecs) as $s): ?>
                <option value="<?= esc($s) ?>"><?= esc($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="pres-bulk-field">
            <label>Skill Needed (optional)</label>
            <select class="pres-bulk-select" id="bulkSkill">
                <option value="">Any skill</option>
            </select>
        </div>

        <div class="pres-bulk-field">
            <label>Quantity</label>
            <div class="pres-qty-row">
                <button class="pres-qty-btn" id="bulkQtyMinus">&#8722;</button>
                <span class="pres-qty-val" id="bulkQtyVal">1</span>
                <button class="pres-qty-btn" id="bulkQtyPlus">&#43;</button>
            </div>
        </div>

        <div class="pres-modal-actions">
            <button class="pres-modal-btn pres-modal-btn-cancel" data-close="modalBulkManpower">CANCEL</button>
            <button class="pres-modal-btn pres-modal-btn-confirm" id="bulkAssignConfirm">ASSIGN 1</button>
        </div>
    </div>
</div>

<!-- ── Bulk Assign Equipment modal ────────────────────────────── -->
<div class="pres-modal-overlay" id="modalBulkEquipment">
    <div class="pres-modal">
        <div class="pres-modal-hdr">
            <h3 class="pres-modal-title" id="bulkEqTitle"></h3>
            <button class="pres-modal-close" data-close="modalBulkEquipment">&times;</button>
        </div>
        <p class="pres-modal-sub" id="bulkEqSub"></p>

        <div class="pres-bulk-field">
            <label>Brand / Make (optional)</label>
            <input type="text" class="pres-bulk-input" id="bulkEqBrand"
                   placeholder="e.g. Makita, Caterpillar — leave blank for any">
        </div>

        <div class="pres-bulk-field">
            <label>Quantity to assign</label>
            <div class="pres-qty-row">
                <button class="pres-qty-btn" id="bulkEqMinus">&#8722;</button>
                <span class="pres-qty-val" id="bulkEqQtyVal">1</span>
                <button class="pres-qty-btn" id="bulkEqPlus">&#43;</button>
            </div>
        </div>

        <div class="pres-modal-actions">
            <button class="pres-modal-btn pres-modal-btn-cancel" data-close="modalBulkEquipment">CANCEL</button>
            <button class="pres-modal-btn pres-modal-btn-confirm" id="bulkEqConfirm">ASSIGN 1</button>
        </div>
    </div>
</div>

<!-- ── Units modal ────────────────────────────────────────────── -->
<div class="pres-modal-overlay" id="modalUnits">
    <div class="pres-modal">
        <div class="pres-modal-hdr">
            <h3 class="pres-modal-title" id="unitsModalTitle"></h3>
            <button class="pres-modal-close" data-close="modalUnits">&times;</button>
        </div>
        <p class="pres-modal-sub" id="unitsModalSub">Assign specific units from the resource list.</p>
        <div id="unitsModalBody"><div style="color:#bbb;font-size:.87rem;padding:.5rem 0">Loading…</div></div>
    </div>
</div>

<script>
(function () {
    var PID      = '<?= esc($projectId) ?>';
    var CSRF_N   = '<?= csrf_token() ?>';
    var CSRF_V   = '<?= csrf_hash() ?>';
    var BASE     = '<?= base_url() ?>';

    /* ── Tabs ── */
    document.querySelectorAll('.pres-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.pres-tab').forEach(function (t) { t.classList.remove('active'); });
            document.querySelectorAll('.pres-tab-panel').forEach(function (p) { p.classList.remove('active'); });
            tab.classList.add('active');
            var panel = document.getElementById('panel-' + tab.dataset.panel);
            if (panel) panel.classList.add('active');
        });
    });

    /* ── Toast ── */
    var toastEl  = document.getElementById('presToast');
    var toastTimer;
    function showToast(msg, type) {
        toastEl.textContent = msg;
        toastEl.className   = 'pres-toast' + (type === 'success' ? ' success' : '') + ' show';
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toastEl.classList.remove('show'); }, 3200);
    }

    /* ── Modals ── */
    function openModal(id)  { document.getElementById(id).classList.add('open'); }
    function closeModal(id) { document.getElementById(id).classList.remove('open'); }

    document.querySelectorAll('[data-close]').forEach(function (el) {
        el.addEventListener('click', function () { closeModal(el.dataset.close); });
    });
    document.querySelectorAll('.pres-modal-overlay').forEach(function (ov) {
        ov.addEventListener('click', function (e) {
            if (e.target === ov) closeModal(ov.id);
        });
    });

    /* ── Post helper ── */
    function post(url, data) {
        data[CSRF_N] = CSRF_V;
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data),
            credentials: 'same-origin',
        }).then(function (r) { return r.json(); });
    }

    /* ── Delegate clicks ── */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-action]');
        if (!btn || btn.disabled) return;
        var action = btn.dataset.action;

        if (action === 'assign')          { doAssign(btn); }
        if (action === 'unassign')        { doUnassign(btn); }
        if (action === 'view-units')      { openUnitsModal(btn); }
        if (action === 'assign-unit')     { doAssignUnit(btn); }
        if (action === 'unassign-unit')   { doUnassignUnit(btn); }
        if (action === 'bulk-equipment')  { doBulkEquipment(btn); }
    });

    /* ── Individual manpower assign / unassign ── */
    function doAssign(btn) {
        btn.disabled = true; btn.textContent = '…';
        post(BASE + 'projects/' + PID + '/resources/assign', {
            type:   btn.dataset.type,
            ref_id: btn.dataset.refId,
            name:   btn.dataset.name,
        }).then(function (d) {
            if (d.success) { location.reload(); }
            else { showToast(d.message || 'Failed to assign'); btn.disabled = false; btn.textContent = 'ASSIGN'; }
        }).catch(function () { showToast('Network error'); btn.disabled = false; btn.textContent = 'ASSIGN'; });
    }

    function doUnassign(btn) {
        if (!confirm('Remove this assignment?')) return;
        btn.disabled = true;
        post(BASE + 'projects/' + PID + '/resources/unassign', {
            assignment_id: btn.dataset.assignmentId,
        }).then(function (d) {
            if (d.success) { location.reload(); }
            else { showToast(d.message || 'Failed'); btn.disabled = false; }
        });
    }

    /* ── Equipment bulk assign modal ── */
    var bulkEqResourceId = '';
    var bulkEqAvail      = 0;
    var bulkEqQty        = 1;

    function doBulkEquipment(btn) {
        var avail = parseInt(btn.dataset.available, 10);
        if (avail === 0) {
            showToast('No units available at warehouse for this model.');
            return;
        }
        bulkEqResourceId = btn.dataset.resourceId;
        bulkEqAvail      = avail;
        bulkEqQty        = 1;

        document.getElementById('bulkEqTitle').textContent =
            btn.closest('.pres-res-card').querySelector('.pres-res-name').textContent;
        document.getElementById('bulkEqSub').textContent =
            avail + ' unit(s) available at warehouse';
        document.getElementById('bulkEqBrand').value = '';
        updateBulkEqDisplay();
        openModal('modalBulkEquipment');
    }

    function updateBulkEqDisplay() {
        document.getElementById('bulkEqQtyVal').textContent  = bulkEqQty;
        document.getElementById('bulkEqConfirm').textContent = 'ASSIGN ' + bulkEqQty;
    }

    document.getElementById('bulkEqMinus').addEventListener('click', function () {
        if (bulkEqQty > 1) { bulkEqQty--; updateBulkEqDisplay(); }
    });
    document.getElementById('bulkEqPlus').addEventListener('click', function () {
        if (bulkEqQty < bulkEqAvail) { bulkEqQty++; updateBulkEqDisplay(); }
    });

    document.getElementById('bulkEqConfirm').addEventListener('click', function () {
        var btn = this;
        btn.disabled = true; btn.textContent = 'Assigning…';
        post(BASE + 'projects/' + PID + '/resources/bulk-assign-equipment', {
            resource_id: bulkEqResourceId,
            brand:       document.getElementById('bulkEqBrand').value.trim(),
            quantity:    bulkEqQty,
        }).then(function (d) {
            btn.disabled = false;
            updateBulkEqDisplay();
            if (d.success) {
                closeModal('modalBulkEquipment');
                showToast(d.assigned + ' unit(s) assigned successfully.', 'success');
                setTimeout(function () { location.reload(); }, 900);
            } else {
                showToast(d.message || 'Failed to assign');
            }
        }).catch(function () {
            btn.disabled = false;
            updateBulkEqDisplay();
            showToast('Network error. Please try again.');
        });
    });

    /* ── Units modal ── */
    var currentResId, currentResType;

    function openUnitsModal(btn) {
        currentResId   = btn.dataset.resourceId;
        currentResType = btn.dataset.resourceType || 'Equipment';
        document.getElementById('unitsModalTitle').textContent = btn.dataset.resourceName || '';
        document.getElementById('unitsModalSub').textContent =
            'Assign specific ' + currentResType + ' units from the resource list.';
        document.getElementById('unitsModalBody').innerHTML = '<div style="color:#bbb;font-size:.87rem;padding:.5rem 0">Loading…</div>';
        openModal('modalUnits');

        fetch(BASE + 'projects/' + PID + '/resources/units/' + currentResId)
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.success || !d.items || d.items.length === 0) {
                    document.getElementById('unitsModalBody').innerHTML =
                        '<p style="color:#bbb;font-size:.85rem">' +
                        (d.success ? 'No units found for this resource.' : 'Failed to load units.') + '</p>';
                    return;
                }
                var html = '';
                d.items.forEach(function (item) {
                    var serial = jEsc(item.serial_number || item.unit_name || item.id);
                    var status = jEsc(item.status || 'Unknown');
                    var btnHtml;
                    if (item.assigned_to_this_project) {
                        btnHtml = '<button class="pres-btn pres-btn-remove" data-action="unassign-unit"' +
                                  ' data-assignment-id="' + jEsc(item.assignment_id) + '">REMOVE</button>';
                    } else if ((item.status || '') === 'Available' || (item.status || '') === 'In-Storage') {
                        btnHtml = '<button class="pres-btn pres-btn-assign" data-action="assign-unit"' +
                                  ' data-resource-id="' + jEsc(currentResId) + '"' +
                                  ' data-unit-id="'     + jEsc(item.id)      + '"' +
                                  ' data-unit-name="'   + serial              + '"' +
                                  ' data-type="equipment">ASSIGN</button>';
                    } else {
                        btnHtml = '<button class="pres-btn pres-btn-unavail" disabled>UNAVAILABLE</button>';
                    }
                    html += '<div class="pres-unit-card">' +
                            '<div><div class="pres-unit-serial">' + serial + '</div>' +
                            '<div class="pres-unit-status">Status: ' + status + '</div></div>' +
                            btnHtml + '</div>';
                });
                document.getElementById('unitsModalBody').innerHTML = html;
            })
            .catch(function () {
                document.getElementById('unitsModalBody').innerHTML = '<p style="color:#bbb;font-size:.85rem">Error loading units.</p>';
            });
    }

    function doAssignUnit(btn) {
        var name = document.getElementById('unitsModalTitle').textContent;
        btn.disabled = true; btn.textContent = '…';
        post(BASE + 'projects/' + PID + '/resources/assign', {
            type:      btn.dataset.type,
            ref_id:    btn.dataset.resourceId,
            name:      name,
            unit_id:   btn.dataset.unitId,
            unit_name: btn.dataset.unitName,
        }).then(function (d) {
            if (d.success) { location.reload(); }
            else { showToast(d.message || 'Failed'); btn.disabled = false; btn.textContent = 'ASSIGN'; }
        }).catch(function () { showToast('Network error'); btn.disabled = false; btn.textContent = 'ASSIGN'; });
    }

    function doUnassignUnit(btn) {
        if (!confirm('Remove this unit from the project?')) return;
        btn.disabled = true;
        post(BASE + 'projects/' + PID + '/resources/unassign', {
            assignment_id: btn.dataset.assignmentId,
        }).then(function (d) {
            if (d.success) { location.reload(); }
            else { showToast(d.message || 'Failed'); btn.disabled = false; }
        });
    }

    /* ── Bulk Assign Manpower modal ── */
    var bulkQty = 1;

    document.getElementById('btnBulkManpower').addEventListener('click', function () {
        bulkQty = 1;
        updateQtyDisplay();
        openModal('modalBulkManpower');
    });

    document.getElementById('bulkQtyMinus').addEventListener('click', function () {
        if (bulkQty > 1) { bulkQty--; updateQtyDisplay(); }
    });
    document.getElementById('bulkQtyPlus').addEventListener('click', function () {
        bulkQty++; updateQtyDisplay();
    });

    function updateQtyDisplay() {
        document.getElementById('bulkQtyVal').textContent = bulkQty;
        document.getElementById('bulkAssignConfirm').textContent = 'ASSIGN ' + bulkQty;
    }

    document.getElementById('bulkAssignConfirm').addEventListener('click', function () {
        var btn = this;
        btn.disabled = true; btn.textContent = 'Assigning…';

        post(BASE + 'projects/' + PID + '/resources/bulk-assign-manpower', {
            role:           document.getElementById('bulkRole').value,
            specialization: document.getElementById('bulkSpec').value,
            skill:          document.getElementById('bulkSkill').value,
            quantity:       bulkQty,
        }).then(function (d) {
            btn.disabled = false;
            updateQtyDisplay();
            if (d.success) {
                closeModal('modalBulkManpower');
                showToast(d.assigned + ' worker(s) assigned successfully.', 'success');
                setTimeout(function () { location.reload(); }, 900);
            } else {
                showToast(d.message || 'Failed to assign workers');
            }
        }).catch(function () {
            btn.disabled = false;
            updateQtyDisplay();
            showToast('Network error. Please try again.');
        });
    });

    function jEsc(s) {
        return String(s || '').replace(/[&<>"']/g, function (c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    }
}());
</script>

<?= $this->endSection() ?>
