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

    <div id="assignedSection">
    <p class="pres-empty" id="assignedEmpty"<?php if (!empty($assignedManpower) || !empty($assignedEqGroups)): ?> style="display:none"<?php endif; ?>>No resources assigned to this project yet.</p>
    <?php if (!empty($assignedManpower) || !empty($assignedEqGroups)): ?>
    <div class="pres-assigned-grid" id="assignedGrid">
        <?php foreach ($assignedEqGroups as $g): ?>
        <div class="pres-assigned-card" data-ref-id="eq:<?= esc($g['id']) ?>">
            <span class="material-symbols-outlined pres-assigned-icon">build</span>
            <div>
                <div class="pres-assigned-name"><?= esc(strtoupper($g['name'])) ?></div>
                <div class="pres-assigned-meta"><?= (int)$g['count'] ?> assigned unit(s)</div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php foreach ($assignedManpower as $a): ?>
        <div class="pres-assigned-card" data-ref-id="mp:<?= esc($a['id']) ?>">
            <span class="material-symbols-outlined pres-assigned-icon">person</span>
            <div>
                <div class="pres-assigned-name"><?= esc(strtoupper($a['name'])) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    </div>

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
            <input type="text" id="mpResSearch" placeholder="Search name…" style="margin-left:auto;padding:5px 10px;border:1px solid #d4c185;border-radius:6px;font-size:0.8rem;width:180px;outline:none;">
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
            <?php elseif (strtoupper($mpStatus) === 'AVAILABLE'): ?>
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
        <input type="text" id="eqResSearch" placeholder="Search equipment…" style="display:block;width:100%;box-sizing:border-box;margin-bottom:10px;padding:6px 12px;border:1px solid #d4c185;border-radius:6px;font-size:0.82rem;outline:none;">
        <?php if (empty($allEquipment)): ?>
        <p class="pres-empty">No equipment found.</p>
        <?php else: ?>
        <div class="pres-res-grid pres-paged-grid" id="eqGrid">
        <?php foreach ($allEquipment as $eq):
            $eqId      = $eq['id'];
            $eqName    = strtoupper($eq['name'] ?? '');
            $eqStats   = $resourceItemStats[$eqId] ?? ['total' => 0, 'available' => 0, 'assigned' => 0];
        ?>
        <div class="pres-res-card pres-res-card-col" data-res-id="<?= esc($eqId) ?>">
            <div class="pres-res-info">
                <div class="pres-res-name"><?= esc($eqName) ?></div>
                <div class="pres-res-sub">Equipment model</div>
                <div class="pres-res-sub pres-res-assigned">&bull; <?= (int)$eqStats['assigned'] ?> assigned unit(s)</div>
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
        <input type="text" id="toolResSearch" placeholder="Search tools…" style="display:block;width:100%;box-sizing:border-box;margin-bottom:10px;padding:6px 12px;border:1px solid #d4c185;border-radius:6px;font-size:0.82rem;outline:none;">
        <?php if (empty($allTools)): ?>
        <p class="pres-empty">No tools found.</p>
        <?php else: ?>
        <div class="pres-res-grid pres-paged-grid" id="toolsGrid">
        <?php foreach ($allTools as $tool):
            $toolId    = $tool['id'];
            $toolName  = strtoupper($tool['name'] ?? '');
            $toolStats = $resourceItemStats[$toolId] ?? ['total' => 0, 'available' => 0, 'assigned' => 0];
        ?>
        <div class="pres-res-card pres-res-card-col" data-res-id="<?= esc($toolId) ?>">
            <div class="pres-res-info">
                <div class="pres-res-name"><?= esc($toolName) ?></div>
                <div class="pres-res-sub">Tool</div>
                <div class="pres-res-sub pres-res-assigned">&bull; <?= (int)$toolStats['assigned'] ?> assigned unit(s)</div>
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

<!-- ── Confirm dialog (must be in DOM before the script runs) ─── -->
<div id="presConfirmDialog" class="confirm-dialog" role="dialog" aria-modal="true">
    <div class="confirm-backdrop"></div>
    <div class="confirm-box">
        <div class="confirm-hdr">
            <span class="material-symbols-outlined confirm-hdr-icon" id="presConfirmIcon">delete_forever</span>
            <p class="confirm-hdr-title" id="presConfirmTitle">Delete</p>
        </div>
        <div class="confirm-content"><p id="presConfirmMessage"></p></div>
        <div class="confirm-actions">
            <button id="presConfirmOk"     class="confirm-btn confirm-btn-action">OK</button>
            <button id="presConfirmCancel" class="confirm-btn confirm-btn-cancel">Cancel</button>
        </div>
    </div>
</div>

<!-- ── Bulk Assign Manpower modal ─────────────────────────────── -->
<div class="pres-modal-overlay" id="modalBulkManpower">
    <div class="pres-modal">
        <div class="pres-modal-hdr">
            <h3 class="pres-modal-title">Bulk Assign Manpower</h3>
            <button class="pres-modal-close" data-close="modalBulkManpower">&times;</button>
        </div>
        <p class="pres-modal-sub">Filter available workers by role or skill. Matching workers are randomly selected and assigned.</p>

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
    var PID    = '<?= esc($projectId) ?>';
    var CSRF_N = '<?= csrf_token() ?>';
    var CSRF_V = '<?= csrf_hash() ?>';
    var BASE   = '<?= base_url() ?>';

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

    /* ── Resource card live search ── */
    (function () {
        function wireCardSearch(inputId, gridId, pagerRef) {
            var inp  = document.getElementById(inputId);
            var grid = document.getElementById(gridId);
            if (!inp || !grid) return;
            inp.addEventListener('input', function () {
                var q = this.value.trim().toLowerCase();
                grid.querySelectorAll('.pres-res-card').forEach(function (card) {
                    var text = card.textContent.toLowerCase();
                    var roleHidden = card.classList.contains('role-hidden');
                    card.style.display = (!q || text.includes(q)) && !roleHidden ? '' : 'none';
                });
                if (pagerRef && pagerRef.reset) pagerRef.reset();
            });
        }
        wireCardSearch('mpResSearch',   'mpGrid',    mpPager);
        wireCardSearch('eqResSearch',   'eqGrid',    eqPager);
        wireCardSearch('toolResSearch', 'toolsGrid', toolsPager);
    }());

    /* ── Tabs (with sessionStorage persistence) ── */
    var TAB_KEY = 'presTab_' + PID;

    function activateTab(panelName) {
        document.querySelectorAll('.pres-tab').forEach(function (t) { t.classList.remove('active'); });
        document.querySelectorAll('.pres-tab-panel').forEach(function (p) { p.classList.remove('active'); });
        var tab = document.querySelector('.pres-tab[data-panel="' + panelName + '"]');
        if (tab) tab.classList.add('active');
        var panel = document.getElementById('panel-' + panelName);
        if (panel) panel.classList.add('active');
    }

    // Restore last active tab on load
    (function () {
        var saved = sessionStorage.getItem(TAB_KEY);
        if (saved) activateTab(saved);
    }());

    document.querySelectorAll('.pres-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            var panelName = tab.dataset.panel;
            activateTab(panelName);
            try { sessionStorage.setItem(TAB_KEY, panelName); } catch (e) {}
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

    /* ── Post helper (updates CSRF token from every response) ── */
    function post(url, data) {
        data[CSRF_N] = CSRF_V;
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data),
            credentials: 'same-origin',
        }).then(function (r) { return r.json(); }).then(function (d) {
            if (d && d.csrf_hash) CSRF_V = d.csrf_hash;
            return d;
        });
    }

    /* ── "Assigned Resources" section helpers ── */
    function getOrCreateGrid() {
        var grid = document.getElementById('assignedGrid');
        if (!grid) {
            var empty = document.getElementById('assignedEmpty');
            if (empty) empty.style.display = 'none';
            grid = document.createElement('div');
            grid.className = 'pres-assigned-grid';
            grid.id = 'assignedGrid';
            document.getElementById('assignedSection').appendChild(grid);
        }
        return grid;
    }

    function checkAssignedEmpty() {
        var grid = document.getElementById('assignedGrid');
        if (grid && grid.children.length === 0) {
            grid.remove();
            var empty = document.getElementById('assignedEmpty');
            if (empty) empty.style.display = '';
        }
    }

    function addManpowerCard(mpId, name) {
        var refId = 'mp:' + mpId;
        var grid  = getOrCreateGrid();
        if (grid.querySelector('[data-ref-id="' + refId + '"]')) return;
        var card = document.createElement('div');
        card.className = 'pres-assigned-card';
        card.setAttribute('data-ref-id', refId);
        card.innerHTML = '<span class="material-symbols-outlined pres-assigned-icon">person</span>' +
            '<div><div class="pres-assigned-name">' + jEsc(name) + '</div></div>';
        grid.appendChild(card);
    }

    function removeManpowerCard(mpId) {
        var grid = document.getElementById('assignedGrid');
        if (!grid) return;
        var card = grid.querySelector('[data-ref-id="mp:' + mpId + '"]');
        if (card) { card.remove(); checkAssignedEmpty(); }
    }

    function adjustEquipmentCard(resId, name, delta) {
        var refId = 'eq:' + resId;
        if (delta > 0) {
            var grid  = getOrCreateGrid();
            var existing = grid.querySelector('[data-ref-id="' + refId + '"]');
            if (existing) {
                var meta = existing.querySelector('.pres-assigned-meta');
                if (meta) {
                    var cur = parseInt(meta.textContent.replace(/\D/g, ''), 10) || 0;
                    meta.textContent = (cur + delta) + ' assigned unit(s)';
                }
            } else {
                var card = document.createElement('div');
                card.className = 'pres-assigned-card';
                card.setAttribute('data-ref-id', refId);
                card.innerHTML = '<span class="material-symbols-outlined pres-assigned-icon">build</span>' +
                    '<div><div class="pres-assigned-name">' + jEsc(name) + '</div>' +
                    '<div class="pres-assigned-meta">' + delta + ' assigned unit(s)</div></div>';
                grid.appendChild(card);
            }
        } else {
            var grid2 = document.getElementById('assignedGrid');
            if (!grid2) return;
            var existing2 = grid2.querySelector('[data-ref-id="' + refId + '"]');
            if (!existing2) return;
            var meta2 = existing2.querySelector('.pres-assigned-meta');
            if (meta2) {
                var cur2 = parseInt(meta2.textContent.replace(/\D/g, ''), 10) || 0;
                var newCount = cur2 + delta;
                if (newCount <= 0) { existing2.remove(); checkAssignedEmpty(); }
                else { meta2.textContent = newCount + ' assigned unit(s)'; }
            }
        }
    }

    function adjustResItemStats(resId, deltaAssigned, deltaAvail) {
        var card = document.querySelector('.pres-res-card[data-res-id="' + resId + '"]');
        if (!card) return;
        var assignedSub = card.querySelector('.pres-res-assigned');
        var availSub    = card.querySelector('.pres-res-avail');
        if (assignedSub) {
            var cur = parseInt(assignedSub.textContent.replace(/\D/g, ''), 10) || 0;
            assignedSub.textContent = '• ' + Math.max(0, cur + deltaAssigned) + ' assigned unit(s)';
        }
        if (availSub) {
            var cur2 = parseInt(availSub.textContent.replace(/\D/g, ''), 10) || 0;
            availSub.textContent = '• ' + Math.max(0, cur2 + deltaAvail) + ' available unit(s)';
        }
        // Keep BULK button's data-available in sync
        var bulkBtn = card.querySelector('[data-action="bulk-equipment"]');
        if (bulkBtn) {
            var curAvail = parseInt(bulkBtn.dataset.available, 10) || 0;
            bulkBtn.dataset.available = Math.max(0, curAvail + deltaAvail);
        }
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

    /* ── Individual manpower assign ── */
    function doAssign(btn) {
        btn.disabled = true; btn.textContent = '…';
        var refId = btn.dataset.refId;
        var name  = btn.dataset.name;
        post(BASE + 'projects/' + PID + '/resources/assign', {
            type:   btn.dataset.type,
            ref_id: refId,
            name:   name,
        }).then(function (d) {
            if (d.success) {
                var card = btn.closest('.pres-res-card');
                var info = card.querySelector('.pres-res-info');
                // Update or add site label
                var siteDiv = card.querySelector('.pres-res-site');
                if (siteDiv) {
                    siteDiv.className   = 'pres-res-site pres-res-site-here';
                    siteDiv.textContent = 'Assigned to this project';
                } else {
                    var div = document.createElement('div');
                    div.className   = 'pres-res-site pres-res-site-here';
                    div.textContent = 'Assigned to this project';
                    info.appendChild(div);
                }
                // Replace ASSIGN with REMOVE
                var removeBtn = document.createElement('button');
                removeBtn.className = 'pres-btn pres-btn-remove';
                removeBtn.dataset.action       = 'unassign';
                removeBtn.dataset.assignmentId = d.assignment_id || ('mp:' + refId);
                removeBtn.textContent = 'REMOVE';
                btn.parentNode.replaceChild(removeBtn, btn);
                // Update top assigned section
                addManpowerCard(refId, name);
                showToast(name + ' assigned.', 'success');
            } else {
                showToast(d.message || 'Failed to assign');
                btn.disabled = false; btn.textContent = 'ASSIGN';
            }
        }).catch(function () {
            showToast('Network error');
            btn.disabled = false; btn.textContent = 'ASSIGN';
        });
    }

    /* ── Individual manpower unassign ── */
    async function doUnassign(btn) {
        if (!await openPresConfirm('Remove this assignment?', 'delete')) return;
        btn.disabled = true;
        var assignmentId = btn.dataset.assignmentId;
        var mpId = assignmentId.slice(3); // strip 'mp:' prefix
        var card = btn.closest('.pres-res-card');
        var name = (card.querySelector('.pres-res-name') || {}).textContent || '';
        post(BASE + 'projects/' + PID + '/resources/unassign', {
            assignment_id: assignmentId,
        }).then(function (d) {
            if (d.success) {
                // Remove site label
                var siteDiv = card.querySelector('.pres-res-site');
                if (siteDiv) siteDiv.remove();
                // Replace REMOVE with ASSIGN
                var assignBtn = document.createElement('button');
                assignBtn.className = 'pres-btn pres-btn-assign';
                assignBtn.dataset.action  = 'assign';
                assignBtn.dataset.type    = 'manpower';
                assignBtn.dataset.refId   = mpId;
                assignBtn.dataset.name    = name;
                assignBtn.textContent = 'ASSIGN';
                btn.parentNode.replaceChild(assignBtn, btn);
                // Update top assigned section
                removeManpowerCard(mpId);
                showToast('Assignment removed.', 'success');
            } else {
                showToast(d.message || 'Failed');
                btn.disabled = false;
            }
        });
    }

    /* ── Equipment/Tools bulk assign modal ── */
    var bulkEqResourceId = '';
    var bulkEqName       = '';
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
        bulkEqName       = btn.closest('.pres-res-card').querySelector('.pres-res-name').textContent;

        document.getElementById('bulkEqTitle').textContent = bulkEqName;
        document.getElementById('bulkEqSub').textContent   = avail + ' unit(s) available at warehouse';
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
        var resId = bulkEqResourceId;
        var name  = bulkEqName;
        post(BASE + 'projects/' + PID + '/resources/bulk-assign-equipment', {
            resource_id: resId,
            quantity:    bulkEqQty,
        }).then(function (d) {
            btn.disabled = false;
            updateBulkEqDisplay();
            if (d.success) {
                var n = d.assigned;
                closeModal('modalBulkEquipment');
                adjustResItemStats(resId, +n, -n);
                adjustEquipmentCard(resId, name, +n);
                bulkEqAvail = Math.max(0, bulkEqAvail - n);
                showToast(n + ' unit(s) assigned successfully.', 'success');
            } else {
                showToast(d.message || 'Failed to assign');
            }
        }).catch(function () {
            btn.disabled = false;
            updateBulkEqDisplay();
            showToast('Network error. Please try again.');
        });
    });

    /* ── Units modal (view specific units) ── */
    var currentResId, currentResName;

    function openUnitsModal(btn) {
        currentResId   = btn.dataset.resourceId;
        currentResName = btn.dataset.resourceName || '';
        var resType    = btn.dataset.resourceType || 'Equipment';
        document.getElementById('unitsModalTitle').textContent = currentResName;
        document.getElementById('unitsModalSub').textContent   =
            'Assign specific ' + resType + ' units from the resource list.';
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
        btn.disabled = true; btn.textContent = '…';
        var resId  = btn.dataset.resourceId;
        var unitId = btn.dataset.unitId;
        var name   = document.getElementById('unitsModalTitle').textContent;
        post(BASE + 'projects/' + PID + '/resources/assign', {
            type:      btn.dataset.type,
            ref_id:    resId,
            name:      name,
            unit_id:   unitId,
            unit_name: btn.dataset.unitName,
        }).then(function (d) {
            if (d.success) {
                // Update button in modal
                var removeBtn = document.createElement('button');
                removeBtn.className = 'pres-btn pres-btn-remove';
                removeBtn.dataset.action       = 'unassign-unit';
                removeBtn.dataset.assignmentId = d.assignment_id || ('unit:' + resId + ':' + unitId);
                removeBtn.textContent = 'REMOVE';
                btn.parentNode.replaceChild(removeBtn, btn);
                // Update card stats + top section
                adjustResItemStats(resId, +1, -1);
                adjustEquipmentCard(resId, name, +1);
                showToast('Unit assigned.', 'success');
            } else {
                showToast(d.message || 'Failed');
                btn.disabled = false; btn.textContent = 'ASSIGN';
            }
        }).catch(function () {
            showToast('Network error');
            btn.disabled = false; btn.textContent = 'ASSIGN';
        });
    }

    async function doUnassignUnit(btn) {
        if (!await openPresConfirm('Remove this unit from the project?', 'delete')) return;
        btn.disabled = true;
        var assignId = btn.dataset.assignmentId; // 'unit:resId:unitId'
        var parts    = assignId.split(':');
        var resId    = parts[1];
        var name     = document.getElementById('unitsModalTitle').textContent;
        post(BASE + 'projects/' + PID + '/resources/unassign', {
            assignment_id: assignId,
        }).then(function (d) {
            if (d.success) {
                // Restore button in modal
                var assignBtn = document.createElement('button');
                assignBtn.className = 'pres-btn pres-btn-assign';
                assignBtn.dataset.action     = 'assign-unit';
                assignBtn.dataset.resourceId = resId;
                assignBtn.dataset.unitId     = parts[2];
                assignBtn.dataset.type       = 'equipment';
                assignBtn.textContent = 'ASSIGN';
                btn.parentNode.replaceChild(assignBtn, btn);
                // Update card stats + top section
                adjustResItemStats(resId, -1, +1);
                adjustEquipmentCard(resId, name, -1);
                showToast('Unit removed.', 'success');
            } else {
                showToast(d.message || 'Failed');
                btn.disabled = false;
            }
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
            skill:          document.getElementById('bulkSkill').value,
            quantity:       bulkQty,
        }).then(function (d) {
            btn.disabled = false;
            updateQtyDisplay();
            if (d.success) {
                closeModal('modalBulkManpower');
                // Update each assigned worker's card in-place
                if (d.workers && d.workers.length) {
                    d.workers.forEach(function (w) {
                        var assignBtn = document.querySelector(
                            '[data-action="assign"][data-type="manpower"][data-ref-id="' + w.id + '"]'
                        );
                        if (assignBtn) {
                            var card = assignBtn.closest('.pres-res-card');
                            var info = card.querySelector('.pres-res-info');
                            var siteDiv = card.querySelector('.pres-res-site');
                            if (siteDiv) {
                                siteDiv.className   = 'pres-res-site pres-res-site-here';
                                siteDiv.textContent = 'Assigned to this project';
                            } else {
                                var div = document.createElement('div');
                                div.className   = 'pres-res-site pres-res-site-here';
                                div.textContent = 'Assigned to this project';
                                info.appendChild(div);
                            }
                            var removeBtn = document.createElement('button');
                            removeBtn.className = 'pres-btn pres-btn-remove';
                            removeBtn.dataset.action       = 'unassign';
                            removeBtn.dataset.assignmentId = 'mp:' + w.id;
                            removeBtn.textContent = 'REMOVE';
                            assignBtn.parentNode.replaceChild(removeBtn, assignBtn);
                            addManpowerCard(w.id, w.name);
                        }
                    });
                }
                showToast(d.assigned + ' worker(s) assigned successfully.', 'success');
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

    /* ── Custom confirm dialog ── */
    var _presConfirmResolve = null;
    var presCd = document.getElementById('presConfirmDialog');
    var presCm = document.getElementById('presConfirmMessage');
    function openPresConfirm(message, type) {
        if (_presConfirmResolve) return Promise.resolve(false);
        return new Promise(function (resolve) {
            _presConfirmResolve = resolve;
            var isArchive = type === 'archive';
            presCd.classList.toggle('confirm-archive', isArchive);
            var icon  = document.getElementById('presConfirmIcon');
            var title = document.getElementById('presConfirmTitle');
            if (icon)  icon.textContent  = isArchive ? 'inventory_2' : 'delete_forever';
            if (title) title.textContent = isArchive ? 'Archive' : 'Delete';
            presCm.innerText = message;
            presCd.classList.add('open');
        });
    }
    document.getElementById('presConfirmOk')?.addEventListener('click', function () {
        if (!_presConfirmResolve) return;
        var r = _presConfirmResolve; _presConfirmResolve = null;
        presCd.classList.remove('open'); r(true);
    });
    document.getElementById('presConfirmCancel')?.addEventListener('click', function () {
        if (!_presConfirmResolve) return;
        var r = _presConfirmResolve; _presConfirmResolve = null;
        presCd.classList.remove('open'); r(false);
    });
}());
</script>

<?= $this->endSection() ?>
