<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="customToast" class="custom-toast" aria-live="polite" aria-atomic="true"></div>

<meta name="csrf-name" content="<?= csrf_token() ?>">
<meta name="csrf-hash" content="<?= csrf_hash() ?>">

<div id="confirmDialog" class="confirm-dialog" role="dialog" aria-modal="true">
    <div class="confirm-backdrop"></div>
    <div class="confirm-box">
        <div class="confirm-content">
            <p id="confirmMessage">Are you sure?</p>
        </div>
        <div class="confirm-actions">
            <button id="confirmCancel" class="btn-toolbar">Cancel</button>
            <button id="confirmOk" class="btn-toolbar btn-add">OK</button>
        </div>
    </div>
</div>

<style>
.filter-panel { background:#fafafa; border:1px solid #e0e0e0; border-radius:6px; padding:16px 20px 12px; margin-bottom:12px; }
.filter-rows  { display:flex; flex-direction:column; gap:12px; }
.filter-row   { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.filter-row label { font-size:11px; font-weight:600; text-transform:uppercase; color:#666; letter-spacing:.4px; min-width:80px; }
.sort-chips   { display:flex; flex-wrap:wrap; gap:6px; }
.sort-chip    { padding:4px 10px; border:1px solid #ccc; border-radius:4px; font-size:12px; font-weight:600; cursor:pointer; background:#fff; color:#444; transition:background .15s,color .15s,border-color .15s; }
.sort-chip:hover  { border-color:#8C701C; color:#8C701C; }
.sort-chip.active { background:#8C701C; color:#fff; border-color:#8C701C; }
.filter-footer { display:flex; align-items:center; gap:10px; margin-top:14px; }
.filter-badge  { display:inline-flex; align-items:center; justify-content:center; background:#8C701C; color:#fff; border-radius:50%; font-size:10px; font-weight:700; width:16px; height:16px; margin-left:5px; vertical-align:middle; }
</style>

<?php
    $activeEquipFilters    = $activeEquipFilters    ?? [];
    $activeToolFilters     = $activeToolFilters     ?? [];
    $activeManpowerFilters = $activeManpowerFilters ?? [];
    $activeTab             = $activeTab             ?? 'equipment';
    $eqActiveCount         = count(array_filter($activeEquipFilters));
    $toolsActiveCount      = count(array_filter($activeToolFilters));
    $mpActiveCount         = count(array_filter($activeManpowerFilters));
?>

<div class="resource-header">
    <h1>Resources</h1>
</div>

<!-- Tabs Navigation -->
<div class="resource-tabs">
    <button id="tab-equipment" class="tab-btn <?= $activeTab === 'equipment' ? 'tab-btn-active' : '' ?>" onclick="showResourceTab('equipment')">
        EQUIPMENT
    </button>
    <button id="tab-tools" class="tab-btn <?= $activeTab === 'tools' ? 'tab-btn-active' : '' ?>" onclick="showResourceTab('tools')">
        TOOLS
    </button>
    <button id="tab-manpower" class="tab-btn <?= $activeTab === 'manpower' ? 'tab-btn-active' : '' ?>" onclick="showResourceTab('manpower')">
        MANPOWER
    </button>
</div>

<!-- EQUIPMENT TAB -->
<div id="equipment" class="tab-content <?= $activeTab === 'equipment' ? 'tab-content-active' : '' ?>">
    <!-- Search and Filter Section -->
    <div class="resource-controls">
        <input type="text" id="searchEquipment" placeholder="🔍 Search equipment..." class="search-input">

        <button type="button" class="btn btn-filter" id="eqFilterToggleBtn">
            ▼ FILTER<?php if ($eqActiveCount > 0): ?><span class="filter-badge"><?= $eqActiveCount ?></span><?php endif; ?>
        </button>

        <a href="<?= base_url('resources/create?category=Equipment') ?>" class="btn btn-success">
            + ADD
        </a>

        <button class="btn btn-import">
            ⬇ IMPORT
        </button>

        <button class="btn btn-export">
            ⬆ EXPORT
        </button>
    </div>

    <!-- Equipment Filter Panel -->
    <div id="eqFilterPanel" class="filter-panel" style="display:none">
        <form id="eqFilterForm" method="get" action="<?= base_url('resources') ?>">
            <input type="hidden" name="tab" value="equipment">
            <input type="hidden" id="eqSortInput" name="eq_sort" value="<?= esc($activeEquipFilters['eq_sort'] ?? '') ?>">

            <div class="filter-rows">
                <div class="filter-row">
                    <label>Sort By</label>
                    <div class="sort-chips">
                        <button type="button" class="sort-chip <?= ($activeEquipFilters['eq_sort'] ?? '') === 'total_desc' ? 'active' : '' ?>" data-val="total_desc">Most Total</button>
                        <button type="button" class="sort-chip <?= ($activeEquipFilters['eq_sort'] ?? '') === 'avail_desc' ? 'active' : '' ?>" data-val="avail_desc">Most Available</button>
                    </div>
                </div>
            </div>
            <div class="filter-footer">
                <a href="<?= base_url('resources?tab=equipment') ?>" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <!-- Equipment Table -->
    <div class="table-container">
        <table class="resource-table">
            <thead>
                <tr>
                    <th>NO.</th>
                    <th>EQUIPMENT NAME</th>
                    <th>TOTAL QUANTITY</th>
                    <th>AVAILABLE UNITS</th>
                    <th class="text-center">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($equipment)): ?>
                    <tr>
                        <td colspan="5" class="text-center">No equipment found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($equipment as $index => $item): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <strong><?= esc((string)($item['name'] ?? 'N/A')) ?></strong>
                            </td>
                            <td><?= esc((string)($item['total_quantity_display'] ?? $item['asset_details']['total_quantity'] ?? '0')) ?></td>
                            <td><?= esc((string)($item['available_units_display'] ?? $item['asset_details']['available_quantity'] ?? '0')) ?></td>
                            <td class="text-center">
                                <a href="<?= base_url('resources/show/' . $item['id']) ?>" class="action-btn" title="View"><span class="material-symbols-outlined">visibility</span></a>
                                <a href="<?= base_url('resources/edit/' . $item['id']) ?>" class="action-btn" title="Edit"><span class="material-symbols-outlined">edit</span></a>
                                <a href="#" data-id="<?= esc((string)$item['id']) ?>" data-name="<?= esc((string)($item['name'] ?? 'this resource')) ?>" class="action-btn action-delete" title="Delete"><span class="material-symbols-outlined">delete</span></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- TOOLS TAB -->
<div id="tools" class="tab-content <?= $activeTab === 'tools' ? 'tab-content-active' : '' ?>">
    <!-- Search and Filter Section -->
    <div class="resource-controls">
        <input type="text" id="searchTools" placeholder="🔍 Search tools..." class="search-input">

        <button type="button" class="btn btn-filter" id="toolsFilterToggleBtn">
            ▼ FILTER<?php if ($toolsActiveCount > 0): ?><span class="filter-badge"><?= $toolsActiveCount ?></span><?php endif; ?>
        </button>

        <a href="<?= base_url('resources/create?category=Tools') ?>" class="btn btn-success">
            + ADD
        </a>

        <button class="btn btn-import">
            ⬇ IMPORT
        </button>

        <button class="btn btn-export">
            ⬆ EXPORT
        </button>
    </div>

    <!-- Tools Filter Panel -->
    <div id="toolsFilterPanel" class="filter-panel" style="display:none">
        <form id="toolsFilterForm" method="get" action="<?= base_url('resources') ?>">
            <input type="hidden" name="tab" value="tools">
            <input type="hidden" id="toolsSortInput" name="tools_sort" value="<?= esc($activeToolFilters['tools_sort'] ?? '') ?>">

            <div class="filter-rows">
                <div class="filter-row">
                    <label>Sort By</label>
                    <div class="sort-chips">
                        <button type="button" class="sort-chip <?= ($activeToolFilters['tools_sort'] ?? '') === 'total_desc' ? 'active' : '' ?>" data-val="total_desc">Most Total</button>
                        <button type="button" class="sort-chip <?= ($activeToolFilters['tools_sort'] ?? '') === 'avail_desc' ? 'active' : '' ?>" data-val="avail_desc">Most Available</button>
                    </div>
                </div>
            </div>
            <div class="filter-footer">
                <a href="<?= base_url('resources?tab=tools') ?>" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <!-- Tools Table -->
    <div class="table-container">
        <table class="resource-table">
            <thead>
                <tr>
                    <th>NO.</th>
                    <th>TOOL NAME</th>
                    <th>TOTAL QUANTITY</th>
                    <th>AVAILABLE</th>
                    <th class="text-center">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $tools = $tools ?? [];
                ?>
                <?php if (empty($tools)): ?>
                    <tr>
                        <td colspan="5" class="text-center">No tools found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tools as $index => $item): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <strong><?= esc((string)($item['name'] ?? 'N/A')) ?></strong>
                            </td>
                            <td><?= esc((string)($item['total_quantity_display'] ?? $item['asset_details']['total_quantity'] ?? '0')) ?></td>
                            <td><?= esc((string)($item['available_units_display'] ?? $item['asset_details']['available_quantity'] ?? '0')) ?></td>
                            <td class="text-center">
                                <a href="<?= base_url('resources/show/' . $item['id']) ?>" class="action-btn" title="View"><span class="material-symbols-outlined">visibility</span></a>
                                <a href="<?= base_url('resources/edit/' . $item['id']) ?>" class="action-btn" title="Edit"><span class="material-symbols-outlined">edit</span></a>
                                <a href="#" data-id="<?= esc((string)$item['id']) ?>" data-name="<?= esc((string)($item['name'] ?? 'this resource')) ?>" class="action-btn action-delete" title="Delete"><span class="material-symbols-outlined">delete</span></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MANPOWER TAB -->
<div id="manpower" class="tab-content <?= $activeTab === 'manpower' ? 'tab-content-active' : '' ?>">
    <!-- Search and Filter Section -->
    <div class="resource-controls">
        <input type="text" id="searchManpower" placeholder="🔍 Search manpower..." class="search-input">
        
        <button type="button" class="btn btn-filter" id="mpFilterToggleBtn">
            ▼ FILTER<?php if ($mpActiveCount > 0): ?><span class="filter-badge"><?= $mpActiveCount ?></span><?php endif; ?>
        </button>

        <a href="<?= base_url('resources/create?category=Manpower') ?>" class="btn btn-success">
            + ADD
        </a>
        
        <button class="btn btn-import">
            ⬇ IMPORT
        </button>
        
        <button class="btn btn-export">
            ⬆ EXPORT
        </button>
    </div>

    <!-- Manpower Filter Panel -->
    <div id="mpFilterPanel" class="filter-panel" style="display:none">
        <form id="mpFilterForm" method="get" action="<?= base_url('resources') ?>">
            <input type="hidden" name="tab" value="manpower">

            <div class="filter-rows">
                <input type="hidden" id="mpRoleInput" name="mp_role" value="<?= esc($activeManpowerFilters['mp_role'] ?? '') ?>">
                <div class="filter-row">
                    <label>Role</label>
                    <div class="sort-chips">
                        <?php foreach (['Field Worker','Foreman'] as $opt): ?>
                        <button type="button" class="sort-chip mp-role-chip <?= ($activeManpowerFilters['mp_role'] ?? '') === $opt ? 'active' : '' ?>" data-val="<?= esc($opt) ?>"><?= esc($opt) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <input type="hidden" id="mpSpecInput" name="mp_spec" value="<?= esc($activeManpowerFilters['mp_spec'] ?? '') ?>">
                <div class="filter-row">
                    <label>Specialization</label>
                    <div class="sort-chips">
                        <?php foreach (['Residential','Towers'] as $opt): ?>
                        <button type="button" class="sort-chip mp-spec-chip <?= ($activeManpowerFilters['mp_spec'] ?? '') === $opt ? 'active' : '' ?>" data-val="<?= esc($opt) ?>"><?= esc($opt) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <input type="hidden" id="mpStatusInput" name="mp_status" value="<?= esc($activeManpowerFilters['mp_status'] ?? '') ?>">
                <div class="filter-row">
                    <label>Status</label>
                    <div class="sort-chips">
                        <?php foreach (['Available','Deployed','On-Leave'] as $opt): ?>
                        <button type="button" class="sort-chip mp-status-chip <?= ($activeManpowerFilters['mp_status'] ?? '') === $opt ? 'active' : '' ?>" data-val="<?= esc($opt) ?>"><?= esc($opt) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="filter-footer">
                <a href="<?= base_url('resources?tab=manpower') ?>" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <!-- Manpower Table -->
    <div class="table-container">
        <table class="resource-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>RESOURCE NAME</th>
                    <th>ROLE</th>
                    <th>NEXT AVAILABLE</th>
                    <th>STATUS</th>
                    <th>CURRENT SITE</th>
                    <th>SPECIALIZATION</th>
                    <th class="text-center">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($manpower)): ?>
                    <tr>
                            No manpower records found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($manpower as $index => $person): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= isset($person['name']) ? esc((string)$person['name']) : 'N/A' ?></td>
                            <td><?= isset($person['manpower_details']['role']) ? esc((string)$person['manpower_details']['role']) : 'N/A' ?></td>
                            <td><?= isset($person['next_availability']) ? esc((string)$person['next_availability']) : 'N/A' ?></td>
                            <td>
                                <span class="status-badge status-<?= strtolower(str_replace('-', '', $person['status'] ?? 'available')) ?>">
                                    <?= esc((string)($person['status'] ?? 'N/A')) ?>
                                </span>
                            </td>
                            <?php $displaySite = $person['manpower_details']['current_site_deployment'] ?? $person['location']['address'] ?? 'N/A'; ?>
                            <td><?= esc((string)$displaySite) ?></td>
                            <td><?= isset($person['manpower_details']['specialization']) ? esc((string)$person['manpower_details']['specialization']) : 'N/A' ?></td>
                            <td class="text-center">
                                <a href="<?= base_url('resources/show/' . $person['id']) ?>" class="action-btn" title="View"><span class="material-symbols-outlined">visibility</span></a>
                                <a href="<?= base_url('resources/edit/' . $person['id']) ?>" class="action-btn" title="Edit"><span class="material-symbols-outlined">edit</span></a>
                                <a href="#" data-id="<?= esc((string)$person['id']) ?>" data-name="<?= esc((string)($person['name'] ?? 'this resource')) ?>" class="action-btn action-delete" title="Delete"><span class="material-symbols-outlined">delete</span></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function showResourceTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('tab-content-active'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('tab-btn-active'));
        const selectedTab = document.getElementById(tabName);
        const selectedBtn = document.getElementById('tab-' + tabName);
        if (selectedTab) selectedTab.classList.add('tab-content-active');
        if (selectedBtn) selectedBtn.classList.add('tab-btn-active');
    }

    document.addEventListener('DOMContentLoaded', function () {
        // — Equipment filter panel —
        const eqToggle  = document.getElementById('eqFilterToggleBtn');
        const eqPanel   = document.getElementById('eqFilterPanel');
        const eqForm    = document.getElementById('eqFilterForm');
        const eqSortInp = document.getElementById('eqSortInput');
        if (eqToggle && eqPanel) {
            <?php if ($eqActiveCount > 0): ?>eqPanel.style.display = 'block';<?php endif; ?>
            eqToggle.addEventListener('click', () => {
                eqPanel.style.display = eqPanel.style.display === 'none' ? 'block' : 'none';
            });
        }
        if (eqForm && eqSortInp) {
            eqForm.querySelectorAll('.sort-chip').forEach(chip => {
                chip.addEventListener('click', function () {
                    eqSortInp.value = eqSortInp.value === this.dataset.val ? '' : this.dataset.val;
                    eqForm.submit();
                });
            });
        }

        // — Tools filter panel —
        const toolsToggle  = document.getElementById('toolsFilterToggleBtn');
        const toolsPanel   = document.getElementById('toolsFilterPanel');
        const toolsForm    = document.getElementById('toolsFilterForm');
        const toolsSortInp = document.getElementById('toolsSortInput');
        if (toolsToggle && toolsPanel) {
            <?php if ($toolsActiveCount > 0): ?>toolsPanel.style.display = 'block';<?php endif; ?>
            toolsToggle.addEventListener('click', () => {
                toolsPanel.style.display = toolsPanel.style.display === 'none' ? 'block' : 'none';
            });
        }
        if (toolsForm && toolsSortInp) {
            toolsForm.querySelectorAll('.sort-chip').forEach(chip => {
                chip.addEventListener('click', function () {
                    toolsSortInp.value = toolsSortInp.value === this.dataset.val ? '' : this.dataset.val;
                    toolsForm.submit();
                });
            });
        }

        // — Manpower filter panel —
        const mpToggle = document.getElementById('mpFilterToggleBtn');
        const mpPanel  = document.getElementById('mpFilterPanel');
        const mpForm   = document.getElementById('mpFilterForm');
        if (mpToggle && mpPanel) {
            <?php if ($mpActiveCount > 0): ?>mpPanel.style.display = 'block';<?php endif; ?>
            mpToggle.addEventListener('click', () => {
                mpPanel.style.display = mpPanel.style.display === 'none' ? 'block' : 'none';
            });
        }
        if (mpForm) {
            function wireMpChips(selector, hiddenId) {
                const inp = document.getElementById(hiddenId);
                mpForm.querySelectorAll(selector).forEach(chip => {
                    chip.addEventListener('click', function () {
                        inp.value = inp.value === this.dataset.val ? '' : this.dataset.val;
                        mpForm.submit();
                    });
                });
            }
            wireMpChips('.mp-role-chip',   'mpRoleInput');
            wireMpChips('.mp-spec-chip',   'mpSpecInput');
            wireMpChips('.mp-status-chip', 'mpStatusInput');
        }

        const csrfName = document.querySelector('meta[name="csrf-name"]')?.getAttribute('content');
        const csrfHash = document.querySelector('meta[name="csrf-hash"]')?.getAttribute('content');
        const confirmDialog = document.getElementById('confirmDialog');
        const confirmMessage = document.getElementById('confirmMessage');
        const confirmOk = document.getElementById('confirmOk');
        const confirmCancel = document.getElementById('confirmCancel');
        const toast = document.getElementById('customToast');

        function showToast(message, type='info') {
            if (!toast) return;
            toast.className = 'custom-toast show ' + (type === 'error' ? 'error' : 'success');
            toast.innerText = message;
            setTimeout(() => { toast.className = 'custom-toast'; }, 3000);
        }

        function attachSearch(inputId, tableSelector) {
            const search = document.getElementById(inputId);
            const table = document.querySelector(tableSelector);
            if (!search || !table) return;

            search.addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                table.querySelectorAll('tbody tr').forEach(row => {
                    row.style.display = q === '' || row.innerText.toLowerCase().includes(q) ? '' : 'none';
                });
            });
        }

        attachSearch('searchEquipment', '#equipment .resource-table');
        attachSearch('searchTools', '#tools .resource-table');
        attachSearch('searchManpower', '#manpower .resource-table');

        let pendingDeleteId = null;
        let pendingRow = null;

        document.querySelectorAll('.action-delete').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                pendingDeleteId = this.dataset.id;
                pendingRow = this.closest('tr');
                const name = this.dataset.name || 'this resource';
                confirmMessage.innerText = `Delete ${name}?`;
                confirmDialog.classList.add('open');
            });
        });

        confirmCancel?.addEventListener('click', function () {
            pendingDeleteId = null;
            pendingRow = null;
            confirmDialog.classList.remove('open');
        });

        confirmOk?.addEventListener('click', function () {
            if (!pendingDeleteId) return;
            const body = new URLSearchParams();
            if (csrfName && csrfHash) body.append(csrfName, csrfHash);
            body.append('id', pendingDeleteId);

            fetch('<?= base_url('resources/delete') ?>', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString()
            })
            .then(r => r.json())
            .then(data => {
                if (data && data.success) {
                    showToast('Resource deleted', 'success');
                    if (pendingRow) {
                        pendingRow.classList.add('removing');
                        setTimeout(() => pendingRow?.remove(), 350);
                    }
                } else {
                    showToast(data.message || 'Delete failed', 'error');
                }
            })
            .catch(() => showToast('Error deleting resource', 'error'))
            .finally(() => {
                pendingDeleteId = null;
                pendingRow = null;
                confirmDialog.classList.remove('open');
            });
        });
    });
</script>

<?= $this->endSection() ?>
