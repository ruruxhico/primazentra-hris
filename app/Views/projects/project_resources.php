<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $projectId   = $projectId   ?? '';
    $project     = $project     ?? [];
    $projectName = strtoupper($project['project_name'] ?? 'PROJECT');

    // Collect unique roles for bulk-assign dropdown
    $mpRoles = [];
    foreach (($allManpower ?? []) as $m) {
        if (!empty($m['role'])) $mpRoles[$m['role']] = true;
    }
    ksort($mpRoles);
    $mpSpecs = ['BUILD-TO-SUIT', 'RETROFITTING', 'COLOCATION', 'CONVERSION'];
?>


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

        <div class="pres-role-filter">
            <button class="pres-role-chip active" id="roleChipAll" data-role="">ALL</button>
            <button class="pres-role-chip" id="roleChipFW" data-role="FIELD WORKER">FIELD WORKER</button>
            <button class="pres-role-chip" id="roleChipFM" data-role="FOREMAN">FOREMAN</button>
        </div>

        <?php if (empty($allManpower)): ?>
        <p class="pres-empty">No manpower records found.</p>
        <?php else: ?>
        <div class="pres-res-grid pres-paged-grid" id="mpGrid">
        <?php foreach ($allManpower as $m):
            $mpId         = $m['id'];
            $mpName       = strtoupper($m['name'] ?: 'UNNAMED');
            $mpRole       = strtoupper($m['role'] ?? '');
            $mpStatus     = $m['status'] ?? 'Available';
            $mpSite       = $m['current_site'] ?? '';
            $isHere       = isset($assignedManpowerIds[$mpId]);
            $assignmentId = $assignedManpowerIds[$mpId] ?? '';
        ?>
        <div class="pres-res-card" data-role="<?= esc($mpRole) ?>">
            <div class="pres-res-info">
                <div class="pres-res-name"><?= esc($mpName) ?></div>
                <?php if ($mpRole): ?>
                <?php $badgeClass = $mpRole === 'FOREMAN' ? 'pres-role-foreman' : 'pres-role-field-worker'; ?>
                <span class="pres-role-badge <?= $badgeClass ?>"><?= esc($mpRole) ?></span>
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
        <div class="pres-pager-wrap" id="mpPager"></div>
        <?php endif; ?>
    </div>

    <!-- ── EQUIPMENT panel ────────────────────────────────────── -->
    <div class="pres-tab-panel" id="panel-equipment">
        <?php if (empty($allEquipment)): ?>
        <p class="pres-empty">No equipment found.</p>
        <?php else: ?>
        <div class="pres-res-grid pres-paged-grid" id="eqGrid">
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
        <div class="pres-pager-wrap" id="eqPager"></div>
        <?php endif; ?>
    </div>

    <!-- ── TOOLS panel ────────────────────────────────────────── -->
    <div class="pres-tab-panel" id="panel-tools">
        <?php if (empty($allTools)): ?>
        <p class="pres-empty">No tools found.</p>
        <?php else: ?>
        <div class="pres-res-grid pres-paged-grid" id="toolsGrid">
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
        <div class="pres-pager-wrap" id="toolsPager"></div>
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
                <?php foreach ($mpSpecs as $s): ?>
                <option value="<?= esc($s) ?>"><?= esc($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="pres-bulk-field">
            <label>Skill Needed (optional)</label>
            <select class="pres-bulk-select" id="bulkSkill">
                <option value="">Any skill</option>
                <?php foreach (['ELECTRICIAN', 'MASON', 'LABOR/HELPER'] as $sk): ?>
                <option value="<?= esc($sk) ?>"><?= esc($sk) ?></option>
                <?php endforeach; ?>
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

    /* ── Client-side grid pager ── */
    var mpPager, eqPager, toolsPager;
    (function () {
        var PER_PAGE = 20;

        function makePager(gridId, pagerId) {
            var grid = document.getElementById(gridId);
            var wrap = document.getElementById(pagerId);
            if (!grid || !wrap) return null;
            var state = { page: 1 };

            function render() {
                var cards   = Array.from(grid.querySelectorAll('.pres-res-card'));
                var visible = cards.filter(function (c) { return !c.classList.contains('role-hidden'); });
                var total   = visible.length;
                var totalPages = Math.max(1, Math.ceil(total / PER_PAGE));
                if (state.page > totalPages) state.page = 1;

                var start = (state.page - 1) * PER_PAGE;
                var end   = start + PER_PAGE;

                visible.forEach(function (c, i) {
                    c.classList.toggle('pager-hidden', i < start || i >= end);
                });

                var from = total === 0 ? 0 : start + 1;
                var to   = Math.min(end, total);
                var html = '<span class="pager-info">Showing ' + from + '–' + to + ' of ' + total + ' records</span>';
                html += '<ul class="pager-list">';
                if (state.page > 1) {
                    html += '<li class="pager-item"><a class="pager-link" data-go="1">«</a></li>';
                    html += '<li class="pager-item"><a class="pager-link" data-go="' + (state.page - 1) + '">‹</a></li>';
                }
                for (var p = 1; p <= totalPages; p++) {
                    if (p === 1 || p === totalPages || Math.abs(p - state.page) <= 2) {
                        html += '<li class="pager-item"><a class="' + (p === state.page ? 'pager-active' : 'pager-link') + '" data-go="' + p + '">' + p + '</a></li>';
                    }
                }
                if (state.page < totalPages) {
                    html += '<li class="pager-item"><a class="pager-link" data-go="' + (state.page + 1) + '">›</a></li>';
                    html += '<li class="pager-item"><a class="pager-link" data-go="' + totalPages + '">»</a></li>';
                }
                html += '</ul>';
                wrap.innerHTML = html;
                wrap.querySelectorAll('[data-go]').forEach(function (a) {
                    a.addEventListener('click', function (e) {
                        e.preventDefault();
                        state.page = parseInt(a.dataset.go, 10);
                        render();
                    });
                });
            }

            render();
            return { render: render, reset: function () { state.page = 1; render(); } };
        }

        mpPager    = makePager('mpGrid',    'mpPager');
        eqPager    = makePager('eqGrid',    'eqPager');
        toolsPager = makePager('toolsGrid', 'toolsPager');
    }());

    /* ── Role filter ── */
    (function () {
        var chips      = document.querySelectorAll('.pres-role-chip');
        var grid       = document.getElementById('mpGrid');
        var activeRole = '';

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                activeRole = chip.dataset.role;
                chips.forEach(function (c) {
                    c.classList.remove('active', 'active-fw', 'active-fm');
                });
                if (!activeRole) {
                    chip.classList.add('active');
                } else if (activeRole === 'FIELD WORKER') {
                    chip.classList.add('active-fw');
                } else {
                    chip.classList.add('active-fm');
                }
                if (!grid) return;
                grid.querySelectorAll('.pres-res-card').forEach(function (card) {
                    card.classList.toggle('role-hidden', !(!activeRole || card.dataset.role === activeRole));
                });
                if (mpPager) mpPager.reset();
            });
        });
    }());

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
