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

/* Checkbox column */
.pz-col-check { width: 36px; text-align: center; }
.pz-col-check input[type="checkbox"] { width: 15px; height: 15px; cursor: pointer; accent-color: #c8a951; }

/* Bulk action bar */
.bulk-action-bar {
    position: fixed; bottom: 1.5rem; left: 50%; transform: translateX(-50%);
    background: #3a2a0a; color: #fff; border-radius: 30px;
    padding: 0.65rem 1.2rem; display: flex; align-items: center; gap: 1rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.35); z-index: 200;
    animation: slideUp 0.2s ease;
}
@keyframes slideUp { from { opacity: 0; transform: translateX(-50%) translateY(16px); } to { opacity: 1; transform: translateX(-50%) translateY(0); } }
.bulk-count { font-size: 0.88rem; font-weight: 600; }
.bulk-delete-btn {
    display: flex; align-items: center; gap: 0.3rem;
    background: #e53e3e; color: #fff; border: none; border-radius: 20px;
    padding: 0.4rem 1rem; font-size: 0.82rem; font-weight: 700; cursor: pointer; font-family: inherit;
}
.bulk-delete-btn:hover { background: #c53030; }
.bulk-delete-btn .material-symbols-outlined { font-size: 16px; }

/* Recently Deleted section */
.recently-deleted-wrap { margin-top: 1.5rem; border: 1px solid #e8d9a8; border-radius: 10px; overflow: hidden; }
.rd-toggle-btn {
    width: 100%; display: flex; align-items: center; gap: 0.5rem;
    background: #fdf8ee; border: none; padding: 0.75rem 1rem;
    font-size: 0.88rem; font-weight: 700; color: #5c3d11; cursor: pointer;
    font-family: inherit; text-align: left;
}
.rd-toggle-btn:hover { background: #f5e7b2; }
.rd-toggle-btn .material-symbols-outlined { font-size: 18px; color: #8c701c; }
.rd-chevron { margin-left: auto; transition: transform 0.2s; }
.rd-badge {
    background: #e53e3e; color: #fff; border-radius: 10px;
    font-size: 0.72rem; font-weight: 800; padding: 0.1rem 0.45rem; line-height: 1.4;
}
.rd-panel { padding: 0.75rem 1rem; background: #fff; }
.rd-empty { color: #aaa; font-size: 0.85rem; text-align: center; padding: 1rem 0; }
.rd-table { font-size: 0.84rem; }
</style>

<?php
    $activeEquipFilters       = $activeEquipFilters       ?? [];
    $activeToolFilters        = $activeToolFilters        ?? [];
    $activeManpowerFilters    = $activeManpowerFilters    ?? [];
    $activeTab                = $activeTab                ?? 'equipment';
    $eqActiveCount            = count(array_filter($activeEquipFilters));
    $toolsActiveCount         = count(array_filter($activeToolFilters));
    $mpActiveCount            = count(array_filter($activeManpowerFilters));
    $recentlyDeletedResources = $recentlyDeletedResources ?? [];
    $recentlyDeletedManpower  = $recentlyDeletedManpower  ?? [];

    // Partition recently deleted resources by category
    $rdEquipment = array_values(array_filter($recentlyDeletedResources, fn($r) => strcasecmp((string)($r['data']['category'] ?? ''), 'Equipment') === 0));
    $rdTools     = array_values(array_filter($recentlyDeletedResources, fn($r) => strcasecmp((string)($r['data']['category'] ?? ''), 'Tools') === 0));
    $rdManpower  = $recentlyDeletedManpower;
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

        <button class="btn btn-import" data-module="equipment" onclick="openImportModal(this.dataset.module)">
            ⬇ IMPORT
        </button>

        <button class="btn btn-export" data-module="equipment" onclick="openExportModal(this.dataset.module, 'Equipment')">
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
        <table class="resource-table" id="equipmentTable">
            <thead>
                <tr>
                    <th class="pz-col-check"><input type="checkbox" class="select-all-cb" data-tab="equipment" title="Select all"></th>
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
                        <td colspan="6" class="text-center">No equipment found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($equipment as $index => $item): ?>
                        <tr>
                            <td class="pz-col-check">
                                <input type="checkbox" class="row-cb eq-cb" value="<?= esc((string)$item['id']) ?>" data-name="<?= esc((string)($item['name'] ?? '')) ?>">
                            </td>
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

    <!-- Recently Deleted - Equipment -->
    <div class="recently-deleted-wrap" id="rdWrapEquipment">
        <button type="button" class="rd-toggle-btn" id="rdToggleBtnEquipment">
            <span class="material-symbols-outlined">restore_from_trash</span>
            Recently Deleted
            <?php if (!empty($rdEquipment)): ?><span class="rd-badge"><?= count($rdEquipment) ?></span><?php endif; ?>
            <span class="material-symbols-outlined rd-chevron">expand_more</span>
        </button>
        <div class="rd-panel" id="rdPanelEquipment" style="display:none">
            <?php if (empty($rdEquipment)): ?>
            <p class="rd-empty">No recently deleted equipment.</p>
            <?php else: ?>
            <table class="resource-table rd-table">
                <thead><tr><th>Name</th><th>Deleted By</th><th>Deleted</th><th>Expires</th><th class="text-center">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($rdEquipment as $rd): ?>
                <tr data-trash-id="<?= esc($rd['id']) ?>">
                    <td><?= esc($rd['name'] ?? '—') ?></td>
                    <td><?= esc($rd['deleted_by'] ?? '—') ?></td>
                    <td><?= date('d M Y H:i', (int)($rd['deleted_at'] ?? 0)) ?></td>
                    <td><?= max(0, (int)ceil((($rd['expires_at'] ?? 0) - time()) / 86400)) ?> days</td>
                    <td class="text-center" style="white-space:nowrap">
                        <button type="button" class="action-btn rd-restore-btn" data-id="<?= esc($rd['id']) ?>" title="Restore"><span class="material-symbols-outlined">restore</span></button>
                        <button type="button" class="action-btn rd-perma-btn" style="color:#e53e3e" data-id="<?= esc($rd['id']) ?>" title="Delete forever"><span class="material-symbols-outlined">delete_forever</span></button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
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

        <button class="btn btn-import" data-module="tools" onclick="openImportModal(this.dataset.module)">
            ⬇ IMPORT
        </button>

        <button class="btn btn-export" data-module="tools" onclick="openExportModal(this.dataset.module, 'Tools')">
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
        <table class="resource-table" id="toolsTable">
            <thead>
                <tr>
                    <th class="pz-col-check"><input type="checkbox" class="select-all-cb" data-tab="tools" title="Select all"></th>
                    <th>NO.</th>
                    <th>TOOL NAME</th>
                    <th>TOTAL QUANTITY</th>
                    <th>AVAILABLE</th>
                    <th class="text-center">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php $tools = $tools ?? []; ?>
                <?php if (empty($tools)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No tools found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tools as $index => $item): ?>
                        <tr>
                            <td class="pz-col-check">
                                <input type="checkbox" class="row-cb tools-cb" value="<?= esc((string)$item['id']) ?>" data-name="<?= esc((string)($item['name'] ?? '')) ?>">
                            </td>
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

    <!-- Recently Deleted - Tools -->
    <div class="recently-deleted-wrap" id="rdWrapTools">
        <button type="button" class="rd-toggle-btn" id="rdToggleBtnTools">
            <span class="material-symbols-outlined">restore_from_trash</span>
            Recently Deleted
            <?php if (!empty($rdTools)): ?><span class="rd-badge"><?= count($rdTools) ?></span><?php endif; ?>
            <span class="material-symbols-outlined rd-chevron">expand_more</span>
        </button>
        <div class="rd-panel" id="rdPanelTools" style="display:none">
            <?php if (empty($rdTools)): ?>
            <p class="rd-empty">No recently deleted tools.</p>
            <?php else: ?>
            <table class="resource-table rd-table">
                <thead><tr><th>Name</th><th>Deleted By</th><th>Deleted</th><th>Expires</th><th class="text-center">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($rdTools as $rd): ?>
                <tr data-trash-id="<?= esc($rd['id']) ?>">
                    <td><?= esc($rd['name'] ?? '—') ?></td>
                    <td><?= esc($rd['deleted_by'] ?? '—') ?></td>
                    <td><?= date('d M Y H:i', (int)($rd['deleted_at'] ?? 0)) ?></td>
                    <td><?= max(0, (int)ceil((($rd['expires_at'] ?? 0) - time()) / 86400)) ?> days</td>
                    <td class="text-center" style="white-space:nowrap">
                        <button type="button" class="action-btn rd-restore-btn" data-id="<?= esc($rd['id']) ?>" title="Restore"><span class="material-symbols-outlined">restore</span></button>
                        <button type="button" class="action-btn rd-perma-btn" style="color:#e53e3e" data-id="<?= esc($rd['id']) ?>" title="Delete forever"><span class="material-symbols-outlined">delete_forever</span></button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
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
        
        <button class="btn btn-import" data-module="manpower" onclick="openImportModal(this.dataset.module)">
            ⬇ IMPORT
        </button>

        <button class="btn btn-export" data-module="manpower" onclick="openExportModal(this.dataset.module, 'Manpower')">
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
        <table class="resource-table" id="manpowerTable">
            <thead>
                <tr>
                    <th class="pz-col-check"><input type="checkbox" class="select-all-cb" data-tab="manpower" title="Select all"></th>
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
                        <td colspan="9" class="text-center">No manpower records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($manpower as $index => $person): ?>
                        <tr>
                            <td class="pz-col-check">
                                <input type="checkbox" class="row-cb mp-cb" value="<?= esc((string)$person['id']) ?>" data-name="<?= esc((string)($person['name'] ?? '')) ?>">
                            </td>
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

    <!-- Recently Deleted - Manpower -->
    <div class="recently-deleted-wrap" id="rdWrapManpower">
        <button type="button" class="rd-toggle-btn" id="rdToggleBtnManpower">
            <span class="material-symbols-outlined">restore_from_trash</span>
            Recently Deleted
            <?php if (!empty($rdManpower)): ?><span class="rd-badge"><?= count($rdManpower) ?></span><?php endif; ?>
            <span class="material-symbols-outlined rd-chevron">expand_more</span>
        </button>
        <div class="rd-panel" id="rdPanelManpower" style="display:none">
            <?php if (empty($rdManpower)): ?>
            <p class="rd-empty">No recently deleted manpower.</p>
            <?php else: ?>
            <table class="resource-table rd-table">
                <thead><tr><th>Name</th><th>Deleted By</th><th>Deleted</th><th>Expires</th><th class="text-center">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($rdManpower as $rd): ?>
                <tr data-trash-id="<?= esc($rd['id']) ?>">
                    <td><?= esc($rd['name'] ?? '—') ?></td>
                    <td><?= esc($rd['deleted_by'] ?? '—') ?></td>
                    <td><?= date('d M Y H:i', (int)($rd['deleted_at'] ?? 0)) ?></td>
                    <td><?= max(0, (int)ceil((($rd['expires_at'] ?? 0) - time()) / 86400)) ?> days</td>
                    <td class="text-center" style="white-space:nowrap">
                        <button type="button" class="action-btn rd-restore-btn" data-id="<?= esc($rd['id']) ?>" title="Restore"><span class="material-symbols-outlined">restore</span></button>
                        <button type="button" class="action-btn rd-perma-btn" style="color:#e53e3e" data-id="<?= esc($rd['id']) ?>" title="Delete forever"><span class="material-symbols-outlined">delete_forever</span></button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
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
        updateBulkBar();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const csrfName = document.querySelector('meta[name="csrf-name"]')?.getAttribute('content');
        const csrfHash = document.querySelector('meta[name="csrf-hash"]')?.getAttribute('content');
        const confirmDialog = document.getElementById('confirmDialog');
        const confirmMessage = document.getElementById('confirmMessage');
        const confirmOk = document.getElementById('confirmOk');
        const confirmCancel = document.getElementById('confirmCancel');
        const toast = document.getElementById('customToast');
        const DELETE_URL = '<?= base_url('resources/delete') ?>';

        function showToast(message, type='info') {
            if (!toast) return;
            toast.className = 'custom-toast show ' + (type === 'error' ? 'error' : 'success');
            toast.innerText = message;
            setTimeout(() => { toast.className = 'custom-toast'; }, 3000);
        }

        function animateRowOut(row) {
            if (!row) return;
            const h = row.offsetHeight;
            row.style.height = h + 'px';
            row.style.overflow = 'hidden';
            requestAnimationFrame(() => {
                row.style.transition = 'height 260ms ease, opacity 260ms ease, transform 260ms ease';
                row.style.height = '0';
                row.style.opacity = '0';
                row.style.transform = 'translateX(-8px)';
            });
            setTimeout(() => row.remove(), 280);
        }

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

        // Search
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

        // ── Bulk select ─────────────────────────────────────────────
        const bulkBar    = document.getElementById('bulkActionBar');
        const bulkCountEl = document.getElementById('bulkCount');
        const bulkDelBtn  = document.getElementById('bulkDeleteBtn');

        function getActiveTabName() {
            const active = document.querySelector('.tab-content.tab-content-active');
            return active ? active.id : 'equipment';
        }

        function getCheckedInTab(tabId) {
            const tab = document.getElementById(tabId);
            return tab ? Array.from(tab.querySelectorAll('.row-cb:checked')) : [];
        }

        window.updateBulkBar = function () {
            const tab = getActiveTabName();
            const checked = getCheckedInTab(tab);
            if (bulkBar) bulkBar.style.display = checked.length > 0 ? 'flex' : 'none';
            if (bulkCountEl) bulkCountEl.textContent = checked.length + ' selected';
        };

        // Wire select-all and row checkboxes for each tab
        ['equipment', 'tools', 'manpower'].forEach(tabId => {
            const tabEl = document.getElementById(tabId);
            if (!tabEl) return;
            const selectAll = tabEl.querySelector('.select-all-cb');

            selectAll && selectAll.addEventListener('change', function () {
                tabEl.querySelectorAll('.row-cb').forEach(cb => { cb.checked = this.checked; });
                updateBulkBar();
            });

            tabEl.querySelectorAll('.row-cb').forEach(cb => {
                cb.addEventListener('change', function () {
                    if (selectAll) {
                        const all = tabEl.querySelectorAll('.row-cb');
                        selectAll.checked = all.length > 0 && [...all].every(c => c.checked);
                        selectAll.indeterminate = getCheckedInTab(tabId).length > 0 && !selectAll.checked;
                    }
                    updateBulkBar();
                });
            });
        });

        bulkDelBtn && bulkDelBtn.addEventListener('click', function () {
            const tab = getActiveTabName();
            const checked = getCheckedInTab(tab);
            if (!checked.length) return;
            const names = checked.map(c => c.dataset.name || c.value).join(', ');
            confirmMessage.innerText = 'Delete ' + checked.length + ' item(s)?\n' + names;
            pendingDeleteId = '__bulk__';
            pendingBulkItems = checked.map(c => ({ id: c.value, row: c.closest('tr') }));
            confirmDialog.classList.add('open');
        });

        // ── Single delete ─────────────────────────────────────────────
        let pendingDeleteId  = null;
        let pendingRow       = null;
        let pendingBulkItems = [];

        document.querySelectorAll('.action-delete').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                pendingDeleteId = this.dataset.id;
                pendingRow = this.closest('tr');
                const name = this.dataset.name || 'this resource';
                confirmMessage.innerText = 'Delete "' + name + '"?';
                confirmDialog.classList.add('open');
            });
        });

        confirmCancel?.addEventListener('click', function () {
            pendingDeleteId = null; pendingRow = null; pendingBulkItems = [];
            confirmDialog.classList.remove('open');
        });

        confirmOk?.addEventListener('click', function () {
            if (!pendingDeleteId) return;

            // Bulk delete path
            if (pendingDeleteId === '__bulk__') {
                const items = [...pendingBulkItems];
                pendingBulkItems = [];
                pendingDeleteId = null;
                confirmDialog.classList.remove('open');

                let done = 0;
                items.forEach(item => {
                    const body = new URLSearchParams();
                    if (csrfName && csrfHash) body.append(csrfName, csrfHash);
                    body.append('id', item.id);
                    fetch(DELETE_URL, {
                        method: 'POST', credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                        body: body.toString()
                    })
                    .then(r => r.json())
                    .then(data => { if (data && data.success) animateRowOut(item.row); })
                    .finally(() => {
                        done++;
                        if (done === items.length) showToast(items.length + ' item(s) deleted', 'success');
                    });
                });
                return;
            }

            // Single delete path
            const body = new URLSearchParams();
            if (csrfName && csrfHash) body.append(csrfName, csrfHash);
            body.append('id', pendingDeleteId);

            fetch(DELETE_URL, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString()
            })
            .then(r => r.json())
            .then(data => {
                if (data && data.success) {
                    showToast('Resource deleted', 'success');
                    animateRowOut(pendingRow);
                } else {
                    showToast(data.message || 'Delete failed', 'error');
                }
            })
            .catch(() => showToast('Error deleting resource', 'error'))
            .finally(() => {
                pendingDeleteId = null; pendingRow = null;
                confirmDialog.classList.remove('open');
            });
        });

        // ── Recently Deleted panels ─────────────────────────────────
        function wireRdPanel(btnId, panelId) {
            const btn   = document.getElementById(btnId);
            const panel = document.getElementById(panelId);
            if (!btn || !panel) return;
            btn.addEventListener('click', function () {
                const open = panel.style.display !== 'none';
                panel.style.display = open ? 'none' : '';
                const chevron = this.querySelector('.rd-chevron');
                if (chevron) chevron.style.transform = open ? '' : 'rotate(180deg)';
            });
        }
        wireRdPanel('rdToggleBtnEquipment', 'rdPanelEquipment');
        wireRdPanel('rdToggleBtnTools',     'rdPanelTools');
        wireRdPanel('rdToggleBtnManpower',  'rdPanelManpower');

        document.querySelectorAll('.rd-restore-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const trashId = this.dataset.id;
                const row = this.closest('tr');
                const fd = new FormData();
                fd.append(csrfName, csrfHash);
                fd.append('trash_id', trashId);
                fetch('<?= base_url('trash/restore') ?>', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            showToast((data.name || 'Item') + ' restored', 'success');
                            animateRowOut(row);
                        } else {
                            showToast(data.message || 'Restore failed', 'error');
                        }
                    })
                    .catch(() => showToast('Restore failed', 'error'));
            });
        });

        document.querySelectorAll('.rd-perma-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const trashId = this.dataset.id;
                const row = this.closest('tr');
                if (!confirm('Permanently delete this item? This cannot be undone.')) return;
                const fd = new FormData();
                fd.append(csrfName, csrfHash);
                fd.append('trash_id', trashId);
                fetch('<?= base_url('trash/permanent-delete') ?>', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            showToast('Permanently deleted', 'success');
                            animateRowOut(row);
                        } else {
                            showToast(data.message || 'Failed', 'error');
                        }
                    })
                    .catch(() => showToast('Failed', 'error'));
            });
        });
    });
</script>

<!-- Bulk Action Bar (shared across tabs) -->
<div class="bulk-action-bar" id="bulkActionBar" style="display:none">
    <span class="bulk-count" id="bulkCount">0 selected</span>
    <button type="button" class="bulk-delete-btn" id="bulkDeleteBtn">
        <span class="material-symbols-outlined">delete</span> Delete Selected
    </button>
</div>

<!-- Export Modal -->
<div id="exportModal" style="display:none;position:fixed;inset:0;z-index:3000;background:rgba(0,0,0,.45);align-items:center;justify-content:center;" hidden>
  <div style="background:#fff;border-radius:12px;padding:28px 32px;min-width:320px;box-shadow:0 8px 32px rgba(0,0,0,.18);">
    <h3 id="exportModalTitle" style="margin:0 0 18px;color:#1a3c5e;font-size:17px;">Export</h3>
    <p style="margin:0 0 18px;color:#555;font-size:13px;">Choose a format to download:</p>
    <div style="display:flex;flex-direction:column;gap:10px;">
      <a id="exportXlsx" href="#" target="_blank" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px solid #1a3c5e;border-radius:8px;text-decoration:none;color:#1a3c5e;font-weight:600;font-size:14px;">
        <span style="font-size:20px;">📊</span> Download XLSX
      </a>
      <a id="exportPdf" href="#" target="_blank" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px solid #c0392b;border-radius:8px;text-decoration:none;color:#c0392b;font-weight:600;font-size:14px;">
        <span style="font-size:20px;">📄</span> Download PDF
      </a>
      <a id="exportPrint" href="#" target="_blank" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px solid #27ae60;border-radius:8px;text-decoration:none;color:#27ae60;font-weight:600;font-size:14px;">
        <span style="font-size:20px;">🖨️</span> Open Printable View
      </a>
    </div>
    <div style="text-align:right;margin-top:18px;">
      <button onclick="closeExportModal()" style="padding:8px 18px;background:#f0f0f0;border:none;border-radius:6px;cursor:pointer;font-size:13px;">Close</button>
    </div>
  </div>
</div>

<!-- Import Modal -->
<div id="importModal" style="display:none;position:fixed;inset:0;z-index:3000;background:rgba(0,0,0,.45);align-items:center;justify-content:center;" hidden>
  <div style="background:#fff;border-radius:12px;padding:28px 32px;min-width:360px;max-width:480px;box-shadow:0 8px 32px rgba(0,0,0,.18);">
    <h3 id="importModalTitle" style="margin:0 0 6px;color:#1a3c5e;font-size:17px;">Import</h3>
    <p style="margin:0 0 14px;color:#555;font-size:13px;">Upload an .xlsx file. <a id="importTplLink" href="#" style="color:#1a3c5e;font-weight:600;">Download template</a></p>
    <div id="importDropZone" style="border:2px dashed #b0c4d8;border-radius:8px;padding:24px;text-align:center;cursor:pointer;color:#888;font-size:13px;">
      <div style="font-size:28px;margin-bottom:6px;">📂</div>
      Click or drag &amp; drop your .xlsx file here
      <input type="file" id="importFileInput" accept=".xlsx,.xls" style="display:none;">
    </div>
    <div id="importFileName" style="margin-top:8px;font-size:12px;color:#555;"></div>
    <div id="importResult" style="margin-top:10px;font-size:13px;"></div>
    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:18px;">
      <button onclick="closeImportModal()" style="padding:8px 18px;background:#f0f0f0;border:none;border-radius:6px;cursor:pointer;font-size:13px;">Cancel</button>
      <button id="importSubmitBtn" onclick="submitImport()" style="padding:8px 18px;background:#1a3c5e;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;">Import</button>
    </div>
  </div>
</div>

<script>
(function () {
  const baseUrl = '<?= base_url() ?>';
  const csrfName = document.querySelector('meta[name="csrf-name"]')?.content;
  const csrfHash = document.querySelector('meta[name="csrf-hash"]')?.content;
  let currentImportModule = '';

  window.openExportModal = function (module, label) {
    const m = document.getElementById('exportModal');
    document.getElementById('exportModalTitle').textContent = 'Export ' + (label || module);
    document.getElementById('exportXlsx').href  = baseUrl + 'export?module=' + module + '&format=xlsx';
    document.getElementById('exportPdf').href   = baseUrl + 'export?module=' + module + '&format=pdf';
    document.getElementById('exportPrint').href = baseUrl + 'export?module=' + module + '&format=print';
    m.hidden = false; m.style.display = 'flex';
  };

  window.closeExportModal = function () {
    const m = document.getElementById('exportModal');
    m.hidden = true; m.style.display = 'none';
  };

  window.openImportModal = function (module) {
    currentImportModule = module;
    const label = module.charAt(0).toUpperCase() + module.slice(1);
    document.getElementById('importModalTitle').textContent = 'Import ' + label;
    document.getElementById('importTplLink').href = baseUrl + 'import/template?module=' + module;
    document.getElementById('importFileName').textContent = '';
    document.getElementById('importResult').textContent = '';
    document.getElementById('importFileInput').value = '';
    const m = document.getElementById('importModal');
    m.hidden = false; m.style.display = 'flex';
  };

  window.closeImportModal = function () {
    const m = document.getElementById('importModal');
    m.hidden = true; m.style.display = 'none';
  };

  window.submitImport = function () {
    const fileInput = document.getElementById('importFileInput');
    const resultEl = document.getElementById('importResult');
    if (!fileInput.files.length) { resultEl.style.color='#c0392b'; resultEl.textContent='Please select a file first.'; return; }
    const btn = document.getElementById('importSubmitBtn');
    btn.disabled = true; btn.textContent = 'Importing...';
    resultEl.style.color = '#555'; resultEl.textContent = 'Processing...';
    const fd = new FormData();
    fd.append('module', currentImportModule);
    fd.append('import_file', fileInput.files[0]);
    if (csrfName) fd.append(csrfName, csrfHash);
    fetch(baseUrl + 'import/process', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        btn.disabled = false; btn.textContent = 'Import';
        if (data.success) { resultEl.style.color='#27ae60'; resultEl.textContent='✓ '+data.message; setTimeout(() => { closeImportModal(); location.reload(); }, 1800); }
        else { resultEl.style.color='#c0392b'; resultEl.textContent='✗ '+(data.message||'Import failed.'); }
      })
      .catch(() => { btn.disabled=false; btn.textContent='Import'; resultEl.style.color='#c0392b'; resultEl.textContent='Network error.'; });
  };

  const dz = document.getElementById('importDropZone');
  const fi = document.getElementById('importFileInput');
  const fn = document.getElementById('importFileName');
  if (dz) {
    dz.addEventListener('click', () => fi.click());
    dz.addEventListener('dragover', e => { e.preventDefault(); dz.style.borderColor='#1a3c5e'; });
    dz.addEventListener('dragleave', () => { dz.style.borderColor='#b0c4d8'; });
    dz.addEventListener('drop', e => { e.preventDefault(); dz.style.borderColor='#b0c4d8'; if(e.dataTransfer.files.length){ fi.files=e.dataTransfer.files; fn.textContent='📎 '+fi.files[0].name; } });
    fi.addEventListener('change', () => { if(fi.files.length) fn.textContent='📎 '+fi.files[0].name; });
  }
  ['exportModal','importModal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e){ if(e.target===this){this.hidden=true;this.style.display='none';} });
  });
}());
</script>

<?= $this->endSection() ?>
