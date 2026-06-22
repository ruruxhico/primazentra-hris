<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="customToast" class="custom-toast" aria-live="polite" aria-atomic="true"></div>

<meta name="csrf-name" content="<?= csrf_token() ?>">
<meta name="csrf-hash" content="<?= csrf_hash() ?>">

<!-- Confirm dialog -->
<div id="confirmDialog" class="confirm-dialog" role="dialog" aria-modal="true">
    <div class="confirm-backdrop"></div>
    <div class="confirm-box">
        <div class="confirm-hdr">
            <span class="material-symbols-outlined confirm-hdr-icon" id="confirmIcon">delete_forever</span>
            <p class="confirm-hdr-title" id="confirmTitle">Delete</p>
        </div>
        <div class="confirm-content"><p id="confirmMessage"></p></div>
        <div class="confirm-actions">
            <button id="confirmOk"     class="confirm-btn confirm-btn-action">OK</button>
            <button id="confirmCancel" class="confirm-btn confirm-btn-cancel">Cancel</button>
        </div>
    </div>
</div>


<?php
    $activeEquipFilters    = $activeEquipFilters    ?? [];
    $activeToolFilters     = $activeToolFilters     ?? [];
    $activeManpowerFilters = $activeManpowerFilters ?? [];
    $activeTab             = $activeTab             ?? 'equipment';
    $eqActiveCount         = count(array_filter($activeEquipFilters));
    $toolsActiveCount      = count(array_filter($activeToolFilters));
    $mpActiveCount         = count(array_filter($activeManpowerFilters));
    $equipment   = $equipment ?? [];
    $tools       = $tools     ?? [];
    $manpower    = $manpower  ?? [];
    $perPage     = $perPage   ?? 20;
    $eqOffset    = $eqOffset    ?? 0;
    $toolsOffset = $toolsOffset ?? 0;
    $mpOffset    = $mpOffset    ?? 0;
?>

<div class="resource-header">
    <h1>Resources</h1>
</div>

<!-- Tabs -->
<div class="rv-tabs" style="margin: 0 2rem 1.2rem;">
    <button class="rv-tab <?= $activeTab === 'equipment' ? 'active' : '' ?>" data-panel="equipment">EQUIPMENT</button>
    <button class="rv-tab <?= $activeTab === 'tools'     ? 'active' : '' ?>" data-panel="tools">TOOLS</button>
    <button class="rv-tab <?= $activeTab === 'manpower'  ? 'active' : '' ?>" data-panel="manpower">MANPOWER</button>
</div>

<!-- ══════════════════════════════════════════════════════════
     EQUIPMENT PANEL
══════════════════════════════════════════════════════════ -->
<div id="panel-equipment" class="rv-panel <?= $activeTab === 'equipment' ? 'active' : '' ?>">

    <div class="rv-controls">
        <div class="rv-search-wrap">
            <span class="material-symbols-outlined">search</span>
            <input type="text" id="searchEquipment" class="rv-search" placeholder="">
        </div>
        <button type="button" class="rv-btn rv-btn-filter" id="eqFilterToggle">
            <span class="material-symbols-outlined">tune</span>
            Filter<?php if ($eqActiveCount > 0): ?><span class="rv-filter-badge"><?= $eqActiveCount ?></span><?php endif; ?>
        </button>
        <?php if (pz_can('add_edit_resources')): ?>
        <a href="<?= base_url('resources/create?category=Equipment') ?>" class="rv-btn rv-btn-add">
            <span class="material-symbols-outlined">add</span> Add
        </a>
        <?php endif; ?>
        <button class="rv-btn rv-btn-import" onclick="openImportModal('equipment')">
            <span class="material-symbols-outlined">download</span> Import
        </button>
        <button class="rv-btn rv-btn-export" onclick="openExportModal('equipment','Equipment')">
            <span class="material-symbols-outlined">upload</span> Export
        </button>

        <div class="rv-btn-group-view">
            <a href="<?= base_url('resources/deleted?tab=equipment') ?>" class="rv-btn-trash">
                <span class="material-symbols-outlined">restore_from_trash</span> Deleted
            </a>
            <a href="<?= base_url('resources/archived?tab=equipment') ?>" class="rv-btn-archive">
                <span class="material-symbols-outlined">inventory_2</span> Archive
            </a>
        </div>
    </div>

    <div id="eqFilterPanel" class="rv-filter-panel" style="display:none">
        <form id="eqFilterForm" method="get" action="<?= base_url('resources') ?>">
            <input type="hidden" name="tab" value="equipment">
            <input type="hidden" id="eqSortInput" name="eq_sort" value="<?= esc($activeEquipFilters['eq_sort'] ?? '') ?>">
            <div class="rv-filter-row">
                <span class="rv-filter-label">Sort by</span>
                <div class="rv-chip-row">
                    <button type="button" class="rv-chip sort-chip <?= ($activeEquipFilters['eq_sort'] ?? '') === 'total_desc' ? 'active' : '' ?>" data-val="total_desc">Most Total</button>
                    <button type="button" class="rv-chip sort-chip <?= ($activeEquipFilters['eq_sort'] ?? '') === 'avail_desc' ? 'active' : '' ?>" data-val="avail_desc">Most Available</button>
                </div>
                <a href="<?= base_url('resources?tab=equipment') ?>" class="rv-filter-reset">Reset</a>
            </div>
        </form>
    </div>

    <div class="pager-section" id="eq-section">
        <div class="table-container">
            <table class="resource-table" id="equipmentTable">
                <thead>
                    <tr>
                        <th class="pz-col-check"><input type="checkbox" class="select-all-cb" data-tab="equipment" title="Select all"></th>
                        <th>NO.</th>
                        <th>NAME</th>
                        <th class="text-center">TOTAL</th>
                        <th class="text-center">AVAILABLE</th>
                        <th class="text-center">IN-USE</th>
                        <th class="text-center">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($equipment)): ?>
                    <tr><td colspan="7" class="empty-state">No equipment found.</td></tr>
                <?php else: ?>
                <?php foreach ($equipment as $idx => $item):
                    $total = (int)($item['total_quantity_display']    ?? $item['asset_details']['total_quantity']    ?? 0);
                    $avail = (int)($item['available_units_display']   ?? $item['asset_details']['available_quantity'] ?? 0);
                    $inuse = $total - $avail;
                ?>
                <tr>
                    <td class="pz-col-check">
                        <input type="checkbox" class="row-cb eq-cb"
                               value="<?= esc((string)$item['id']) ?>"
                               data-name="<?= esc((string)($item['name'] ?? '')) ?>">
                    </td>
                    <td><?= $eqOffset + $idx + 1 ?></td>
                    <td><strong><?= esc(strtoupper((string)($item['name'] ?? 'N/A'))) ?></strong></td>
                    <td class="text-center"><?= $total ?></td>
                    <td class="text-center"><?= $avail ?></td>
                    <td class="text-center"><?= $inuse > 0 ? $inuse : '—' ?></td>
                    <td class="text-center" style="white-space:nowrap">
                        <a href="<?= base_url('resources/show/' . $item['id']) ?>" class="action-btn" title="View">
                            <span class="material-symbols-outlined">visibility</span>
                        </a>
                        <a href="<?= base_url('resources/edit/' . $item['id']) ?>" class="action-btn" title="Edit">
                            <span class="material-symbols-outlined">edit</span>
                        </a>
                        <button type="button" class="action-btn action-archive"
                                data-id="<?= esc((string)$item['id']) ?>"
                                data-name="<?= esc((string)($item['name'] ?? 'this resource')) ?>"
                                title="Archive">
                            <span class="material-symbols-outlined">inventory_2</span>
                        </button>
                        <button type="button" class="action-btn action-delete"
                                data-id="<?= esc((string)$item['id']) ?>"
                                data-name="<?= esc((string)($item['name'] ?? 'this resource')) ?>"
                                title="Delete">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pager-wrapper">
            <?php $eqTotal = $eqTotal ?? count($equipment); $showTo = min($eqOffset + $perPage, $eqTotal); ?>
            <span class="pager-info">Showing <?= $eqTotal > 0 ? $eqOffset + 1 : 0 ?>–<?= $showTo ?> of <?= $eqTotal ?> records</span>
            <?= service('pager')->links('equipment', 'bootstrap_full') ?>
            <?php $eqTotalPages = $eqTotal > 0 ? (int)ceil($eqTotal / $perPage) : 1; ?>
            <?php if ($eqTotalPages > 1): ?>
            <div class="pager-jump">
                Go to page
                <input type="number" class="pager-jump-input" min="1" max="<?= $eqTotalPages ?>"
                       data-total-pages="<?= $eqTotalPages ?>" data-page-param="page_equipment" placeholder="—">
                <span>of <?= $eqTotalPages ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /panel-equipment -->

<!-- ══════════════════════════════════════════════════════════
     TOOLS PANEL
══════════════════════════════════════════════════════════ -->
<div id="panel-tools" class="rv-panel <?= $activeTab === 'tools' ? 'active' : '' ?>">

    <div class="rv-controls">
        <div class="rv-search-wrap">
            <span class="material-symbols-outlined">search</span>
            <input type="text" id="searchTools" class="rv-search" placeholder="">
        </div>
        <button type="button" class="rv-btn rv-btn-filter" id="toolsFilterToggle">
            <span class="material-symbols-outlined">tune</span>
            Filter<?php if ($toolsActiveCount > 0): ?><span class="rv-filter-badge"><?= $toolsActiveCount ?></span><?php endif; ?>
        </button>
        <?php if (pz_can('add_edit_resources')): ?>
        <a href="<?= base_url('resources/create?category=Tools') ?>" class="rv-btn rv-btn-add">
            <span class="material-symbols-outlined">add</span> Add
        </a>
        <?php endif; ?>
        <button class="rv-btn rv-btn-import" onclick="openImportModal('tools')">
            <span class="material-symbols-outlined">download</span> Import
        </button>
        <button class="rv-btn rv-btn-export" onclick="openExportModal('tools','Tools')">
            <span class="material-symbols-outlined">upload</span> Export
        </button>

        <div class="rv-btn-group-view">
            <a href="<?= base_url('resources/deleted?tab=tools') ?>" class="rv-btn-trash">
                <span class="material-symbols-outlined">restore_from_trash</span> Deleted
            </a>
            <a href="<?= base_url('resources/archived?tab=tools') ?>" class="rv-btn-archive">
                <span class="material-symbols-outlined">inventory_2</span> Archive
            </a>
        </div>
    </div>

    <div id="toolsFilterPanel" class="rv-filter-panel" style="display:none">
        <form id="toolsFilterForm" method="get" action="<?= base_url('resources') ?>">
            <input type="hidden" name="tab" value="tools">
            <input type="hidden" id="toolsSortInput" name="tools_sort" value="<?= esc($activeToolFilters['tools_sort'] ?? '') ?>">
            <div class="rv-filter-row">
                <span class="rv-filter-label">Sort by</span>
                <div class="rv-chip-row">
                    <button type="button" class="rv-chip sort-chip <?= ($activeToolFilters['tools_sort'] ?? '') === 'total_desc' ? 'active' : '' ?>" data-val="total_desc">Most Total</button>
                    <button type="button" class="rv-chip sort-chip <?= ($activeToolFilters['tools_sort'] ?? '') === 'avail_desc' ? 'active' : '' ?>" data-val="avail_desc">Most Available</button>
                </div>
                <a href="<?= base_url('resources?tab=tools') ?>" class="rv-filter-reset">Reset</a>
            </div>
        </form>
    </div>

    <div class="pager-section" id="tools-section">
        <div class="table-container">
            <table class="resource-table" id="toolsTable">
                <thead>
                    <tr>
                        <th class="pz-col-check"><input type="checkbox" class="select-all-cb" data-tab="tools" title="Select all"></th>
                        <th>NO.</th>
                        <th>NAME</th>
                        <th class="text-center">TOTAL</th>
                        <th class="text-center">AVAILABLE</th>
                        <th class="text-center">IN-USE</th>
                        <th class="text-center">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($tools)): ?>
                    <tr><td colspan="7" class="empty-state">No tools found.</td></tr>
                <?php else: ?>
                <?php foreach ($tools as $idx => $item):
                    $total = (int)($item['total_quantity_display']  ?? $item['asset_details']['total_quantity']    ?? 0);
                    $avail = (int)($item['available_units_display'] ?? $item['asset_details']['available_quantity'] ?? 0);
                    $inuse = $total - $avail;
                ?>
                <tr>
                    <td class="pz-col-check">
                        <input type="checkbox" class="row-cb tools-cb"
                               value="<?= esc((string)$item['id']) ?>"
                               data-name="<?= esc((string)($item['name'] ?? '')) ?>">
                    </td>
                    <td><?= $toolsOffset + $idx + 1 ?></td>
                    <td><strong><?= esc(strtoupper((string)($item['name'] ?? 'N/A'))) ?></strong></td>
                    <td class="text-center"><?= $total ?></td>
                    <td class="text-center"><?= $avail ?></td>
                    <td class="text-center"><?= $inuse > 0 ? $inuse : '—' ?></td>
                    <td class="text-center" style="white-space:nowrap">
                        <a href="<?= base_url('resources/show/' . $item['id']) ?>" class="action-btn" title="View">
                            <span class="material-symbols-outlined">visibility</span>
                        </a>
                        <a href="<?= base_url('resources/edit/' . $item['id']) ?>" class="action-btn" title="Edit">
                            <span class="material-symbols-outlined">edit</span>
                        </a>
                        <button type="button" class="action-btn action-archive"
                                data-id="<?= esc((string)$item['id']) ?>"
                                data-name="<?= esc((string)($item['name'] ?? 'this resource')) ?>"
                                title="Archive">
                            <span class="material-symbols-outlined">inventory_2</span>
                        </button>
                        <button type="button" class="action-btn action-delete"
                                data-id="<?= esc((string)$item['id']) ?>"
                                data-name="<?= esc((string)($item['name'] ?? 'this resource')) ?>"
                                title="Delete">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pager-wrapper">
            <?php $toolsTotal = $toolsTotal ?? count($tools); $tShowTo = min($toolsOffset + $perPage, $toolsTotal); ?>
            <span class="pager-info">Showing <?= $toolsTotal > 0 ? $toolsOffset + 1 : 0 ?>–<?= $tShowTo ?> of <?= $toolsTotal ?> records</span>
            <?= service('pager')->links('tools', 'bootstrap_full') ?>
            <?php $toolsTotalPages = $toolsTotal > 0 ? (int)ceil($toolsTotal / $perPage) : 1; ?>
            <?php if ($toolsTotalPages > 1): ?>
            <div class="pager-jump">
                Go to page
                <input type="number" class="pager-jump-input" min="1" max="<?= $toolsTotalPages ?>"
                       data-total-pages="<?= $toolsTotalPages ?>" data-page-param="page_tools" placeholder="—">
                <span>of <?= $toolsTotalPages ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /panel-tools -->

<!-- ══════════════════════════════════════════════════════════
     MANPOWER PANEL
══════════════════════════════════════════════════════════ -->
<div id="panel-manpower" class="rv-panel <?= $activeTab === 'manpower' ? 'active' : '' ?>">

    <div class="rv-controls">
        <div class="rv-search-wrap">
            <span class="material-symbols-outlined">search</span>
            <input type="text" id="searchManpower" class="rv-search" placeholder="">
        </div>
        <button type="button" class="rv-btn rv-btn-filter" id="mpFilterToggle">
            <span class="material-symbols-outlined">tune</span>
            Filter<?php if ($mpActiveCount > 0): ?><span class="rv-filter-badge"><?= $mpActiveCount ?></span><?php endif; ?>
        </button>
        <?php if (pz_can('add_edit_resources')): ?>
        <a href="<?= base_url('resources/create?category=Manpower') ?>" class="rv-btn rv-btn-add">
            <span class="material-symbols-outlined">add</span> Add
        </a>
        <?php endif; ?>
        <button class="rv-btn rv-btn-import" onclick="openImportModal('manpower')">
            <span class="material-symbols-outlined">download</span> Import
        </button>
        <button class="rv-btn rv-btn-export" onclick="openExportModal('manpower','Manpower')">
            <span class="material-symbols-outlined">upload</span> Export
        </button>

        <div class="rv-btn-group-view">
            <a href="<?= base_url('resources/deleted?tab=manpower') ?>" class="rv-btn-trash">
                <span class="material-symbols-outlined">restore_from_trash</span> Deleted
            </a>
            <a href="<?= base_url('resources/archived?tab=manpower') ?>" class="rv-btn-archive">
                <span class="material-symbols-outlined">inventory_2</span> Archive
            </a>
        </div>
    </div>

    <div id="mpFilterPanel" class="rv-filter-panel" style="display:none">
        <form id="mpFilterForm" method="get" action="<?= base_url('resources') ?>">
            <input type="hidden" name="tab" value="manpower">
            <input type="hidden" id="mpRoleInput"   name="mp_role"   value="<?= esc($activeManpowerFilters['mp_role']   ?? '') ?>">
            <input type="hidden" id="mpSpecInput"   name="mp_spec"   value="<?= esc($activeManpowerFilters['mp_spec']   ?? '') ?>">
            <input type="hidden" id="mpStatusInput" name="mp_status" value="<?= esc($activeManpowerFilters['mp_status'] ?? '') ?>">
            <div class="rv-filter-row">
                <span class="rv-filter-label">Role</span>
                <div class="rv-chip-row">
                    <?php foreach (['FIELD WORKER' => 'Field Worker', 'FOREMAN' => 'Foreman'] as $val => $label): ?>
                    <button type="button" class="rv-chip mp-role-chip <?= strcasecmp(($activeManpowerFilters['mp_role'] ?? ''), $val) === 0 ? 'active' : '' ?>" data-val="<?= esc($val) ?>"><?= esc($label) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="rv-filter-row">
                <span class="rv-filter-label">Specialization</span>
                <div class="rv-chip-row">
                    <?php foreach (['BUILD-TO-SUIT' => 'Build-to-Suit', 'RETROFITTING' => 'Retrofitting', 'COLOCATION' => 'Colocation', 'CONVERSION' => 'Conversion'] as $val => $label): ?>
                    <button type="button" class="rv-chip mp-spec-chip <?= strcasecmp(($activeManpowerFilters['mp_spec'] ?? ''), $val) === 0 ? 'active' : '' ?>" data-val="<?= esc($val) ?>"><?= esc($label) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="rv-filter-row">
                <span class="rv-filter-label">Status</span>
                <div class="rv-chip-row">
                    <?php foreach (['AVAILABLE' => 'Available', 'DEPLOYED' => 'Deployed', 'ON-LEAVE' => 'On-Leave'] as $val => $label): ?>
                    <button type="button" class="rv-chip mp-status-chip <?= strcasecmp(($activeManpowerFilters['mp_status'] ?? ''), $val) === 0 ? 'active' : '' ?>" data-val="<?= esc($val) ?>"><?= esc($label) ?></button>
                    <?php endforeach; ?>
                </div>
                <a href="<?= base_url('resources?tab=manpower') ?>" class="rv-filter-reset">Reset</a>
            </div>
        </form>
    </div>

    <div class="pager-section" id="mp-section">
        <div class="table-container">
            <table class="resource-table" id="manpowerTable">
                <thead>
                    <tr>
                        <th class="pz-col-check"><input type="checkbox" class="select-all-cb" data-tab="manpower" title="Select all"></th>
                        <th>NO.</th>
                        <th>NAME</th>
                        <th>ROLE</th>
                        <th>SPECIALIZATION</th>
                        <th class="text-center">STATUS</th>
                        <th class="text-center">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($manpower)): ?>
                    <tr><td colspan="7" class="empty-state">No manpower records found.</td></tr>
                <?php else: ?>
                <?php foreach ($manpower as $idx => $person):
                    $mpStatusRaw = (string)($person['status'] ?? 'Available');
                    $mpStatusKey = strtolower(str_replace(['-', ' '], '', $mpStatusRaw));
                    $stClass = match($mpStatusKey) {
                        'available' => 'mp-status-available',
                        'deployed'  => 'mp-status-deployed',
                        'onleave'   => 'mp-status-onleave',
                        default     => 'mp-status-other',
                    };
                    $role = (string)($person['manpower_details']['role']           ?? $person['role']           ?? '');
                    $spec = (string)($person['manpower_details']['specialization'] ?? $person['specialization'] ?? '');
                ?>
                <tr>
                    <td class="pz-col-check">
                        <input type="checkbox" class="row-cb mp-cb"
                               value="<?= esc((string)$person['id']) ?>"
                               data-name="<?= esc((string)($person['name'] ?? '')) ?>">
                    </td>
                    <td><?= $mpOffset + $idx + 1 ?></td>
                    <td><strong><?= esc(strtoupper((string)($person['name'] ?? 'N/A'))) ?></strong></td>
                    <td><?= esc($role) ?></td>
                    <td><?= esc($spec) ?></td>
                    <td class="text-center">
                        <span class="mp-status-badge <?= $stClass ?>"><?= esc($mpStatusRaw) ?></span>
                    </td>
                    <td class="text-center" style="white-space:nowrap">
                        <a href="<?= base_url('resources/show/' . $person['id']) ?>" class="action-btn" title="View">
                            <span class="material-symbols-outlined">visibility</span>
                        </a>
                        <a href="<?= base_url('resources/edit/' . $person['id']) ?>" class="action-btn" title="Edit">
                            <span class="material-symbols-outlined">edit</span>
                        </a>
                        <button type="button" class="action-btn action-delete"
                                data-id="<?= esc((string)$person['id']) ?>"
                                data-name="<?= esc((string)($person['name'] ?? 'this resource')) ?>"
                                title="Delete">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pager-wrapper">
            <?php $mpTotal = $mpTotal ?? count($manpower); $mShowTo = min($mpOffset + $perPage, $mpTotal); ?>
            <span class="pager-info">Showing <?= $mpTotal > 0 ? $mpOffset + 1 : 0 ?>–<?= $mShowTo ?> of <?= $mpTotal ?> records</span>
            <?= service('pager')->links('manpower', 'bootstrap_full') ?>
            <?php $mpTotalPages = $mpTotal > 0 ? (int)ceil($mpTotal / $perPage) : 1; ?>
            <?php if ($mpTotalPages > 1): ?>
            <div class="pager-jump">
                Go to page
                <input type="number" class="pager-jump-input" min="1" max="<?= $mpTotalPages ?>"
                       data-total-pages="<?= $mpTotalPages ?>" data-page-param="page_manpower" placeholder="—">
                <span>of <?= $mpTotalPages ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /panel-manpower -->

<!-- Bulk action bar -->
<div class="rv-bulk-bar" id="bulkActionBar" style="display:none">
    <span class="rv-bulk-count" id="bulkCount">0 selected</span>
    <button type="button" class="rv-bulk-arch-btn" id="bulkArchiveBtn">
        <span class="material-symbols-outlined">inventory_2</span> Archive Selected
    </button>
    <button type="button" class="rv-bulk-del-btn" id="bulkDeleteBtn">
        <span class="material-symbols-outlined">delete</span> Delete Selected
    </button>
</div>

<!-- Export Modal -->
<div id="exportModal" class="pz-modal-overlay" style="display:none" hidden>
  <div class="pz-modal-box">
    <h3 id="exportModalTitle" class="pz-modal-title">Export</h3>
    <p class="pz-modal-subtitle">Choose a format to download:</p>
    <div id="exportSubtypeWrap" style="display:none;margin:0 0 16px;background:#fdf8ee;border:1px solid #e8d9a8;border-radius:8px;padding:12px 16px;">
      <p style="margin:0 0 10px;font-size:12px;font-weight:700;color:#8C701C;text-transform:uppercase;letter-spacing:.4px;">Export Type</p>
      <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;margin-bottom:8px;">
        <input type="radio" name="exportSubtype" value="models" checked style="margin-top:2px;accent-color:#8C701C;">
        <span style="font-size:13px;color:#333;"><strong>Models only</strong><br><span style="font-size:11px;color:#777;">One row per model with condition summary counts</span></span>
      </label>
      <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;">
        <input type="radio" name="exportSubtype" value="units" style="margin-top:2px;accent-color:#8C701C;">
        <span style="font-size:13px;color:#333;"><strong>All specific units per model</strong><br><span style="font-size:11px;color:#777;">One row per unit across all models in one file</span></span>
      </label>
    </div>
    <div class="pz-modal-export-list">
      <a id="exportXlsx"  href="#" target="_blank" class="pz-export-link pz-export-link--xlsx">
        <span class="pz-export-link-icon">📊</span> Download XLSX
      </a>
      <a id="exportCsv"   href="#" target="_blank" class="pz-export-link pz-export-link--csv">
        <span class="pz-export-link-icon">📋</span> Download CSV
      </a>
      <a id="exportPdf"   href="#" target="_blank" class="pz-export-link pz-export-link--pdf">
        <span class="pz-export-link-icon">📄</span> Download PDF
      </a>
      <a id="exportPrint" href="#" target="_blank" class="pz-export-link pz-export-link--print">
        <span class="pz-export-link-icon">🖨️</span> Open Printable View
      </a>
    </div>
    <div class="pz-modal-close-row">
      <button onclick="closeExportModal()" class="pz-btn-modal-cancel">Close</button>
    </div>
  </div>
</div>

<!-- Import Modal -->
<div id="importModal" style="display:none;position:fixed;inset:0;z-index:3000;background:rgba(0,0,0,.45);align-items:center;justify-content:center;" hidden>
  <div style="background:#fff;border-radius:12px;padding:28px 32px;min-width:360px;max-width:480px;box-shadow:0 8px 32px rgba(0,0,0,.18);">
    <h3 id="importModalTitle" style="margin:0 0 6px;color:#2c1f07;font-size:17px;">Import</h3>
    <p style="margin:0 0 14px;color:#555;font-size:13px;">Upload an .xlsx or .csv file. <a id="importTplLink" href="#" style="color:#8C701C;font-weight:600;">Download template</a></p>
    <div id="importDropZone" style="border:2px dashed #d4c185;border-radius:8px;padding:24px;text-align:center;cursor:pointer;color:#888;font-size:13px;">
      <div style="font-size:28px;margin-bottom:6px;">📂</div>
      Click or drag &amp; drop your .xlsx or .csv file here
      <input type="file" id="importFileInput" accept=".xlsx,.xls,.csv" style="display:none;">
    </div>
    <div id="importFileName" style="margin-top:8px;font-size:12px;color:#555;"></div>
    <div id="importResult"   style="margin-top:10px;font-size:13px;"></div>
    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:18px;">
      <button onclick="closeImportModal()" style="padding:8px 18px;background:#f0f0f0;border:none;border-radius:6px;cursor:pointer;font-size:13px;">Cancel</button>
      <button id="importSubmitBtn" onclick="submitImport()" style="padding:8px 18px;background:#8C701C;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;">Import</button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfName = document.querySelector('meta[name="csrf-name"]')?.content;
    const csrfHash = document.querySelector('meta[name="csrf-hash"]')?.content;
    const confirmDialog  = document.getElementById('confirmDialog');
    const confirmMessage = document.getElementById('confirmMessage');
    const confirmOk      = document.getElementById('confirmOk');
    const confirmCancel  = document.getElementById('confirmCancel');
    const toast          = document.getElementById('customToast');
    function setConfirmType(type) {
        const isArchive = type === 'archive';
        confirmDialog.classList.toggle('confirm-archive', isArchive);
        var icon  = document.getElementById('confirmIcon');
        var title = document.getElementById('confirmTitle');
        if (icon)  icon.textContent  = isArchive ? 'inventory_2' : 'delete_forever';
        if (title) title.textContent = isArchive ? 'Archive' : 'Delete';
    }
    const DELETE_URL     = '<?= base_url('resources/delete') ?>';
    const BASE_URL       = '<?= base_url() ?>';

    /* ── Tabs ── */
    document.querySelectorAll('.rv-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.rv-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.rv-panel').forEach(p => p.classList.remove('active'));
            tab.classList.add('active');
            const panel = document.getElementById('panel-' + tab.dataset.panel);
            if (panel) panel.classList.add('active');
            updateBulkBar();
        });
    });

    /* ── Toast ── */
    function showToast(msg, type) {
        if (!toast) return;
        toast.className = 'custom-toast show ' + (type === 'error' ? 'error' : 'success');
        toast.innerText = msg;
        setTimeout(() => { toast.className = 'custom-toast'; }, 3000);
    }

    function escHtml(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ── Fade row out ── */
    function animateRowOut(row) {
        if (!row) return;
        row.style.transition = 'opacity 260ms ease';
        row.style.opacity = '0';
        setTimeout(() => row.remove(), 280);
    }

    function renumberTable(tableEl, noColIdx) {
        if (!tableEl) return;
        tableEl.querySelectorAll('tbody tr').forEach(function (tr, i) {
            if (tr.cells[noColIdx]) tr.cells[noColIdx].textContent = i + 1;
        });
    }

    /* ── Add restored resource as table row ── */
    function addRestoredRow(module, id, d) {
        let tbodyId, cbClass, colCount;
        if (module === 'manpower') {
            tbodyId = 'manpowerTable'; cbClass = 'mp-cb'; colCount = 7;
        } else {
            const cat = (d.category || '').toLowerCase();
            tbodyId   = cat === 'tools' ? 'toolsTable' : 'equipmentTable';
            cbClass   = cat === 'tools' ? 'tools-cb'   : 'eq-cb';
            colCount  = 7;
        }
        const table = document.getElementById(tbodyId);
        if (!table) return;
        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        // Remove empty-state row if present
        const emptyRow = tbody.querySelector('.empty-state');
        if (emptyRow) emptyRow.closest('tr')?.remove();

        const nameVal = escHtml((d.name || 'N/A').toUpperCase());
        let cellsHtml;

        if (module === 'manpower') {
            const role   = escHtml((d.manpower_details && d.manpower_details.role)           || d.role           || '');
            const spec   = escHtml((d.manpower_details && d.manpower_details.specialization) || d.specialization || '');
            const status = d.status || 'Available';
            const stKey  = status.toLowerCase().replace(/[^a-z]/g,'');
            const stCls  = {available:'mp-status-available',deployed:'mp-status-deployed',onleave:'mp-status-onleave'}[stKey] || 'mp-status-other';
            cellsHtml =
                '<td class="pz-col-check"><input type="checkbox" class="row-cb ' + cbClass + '" value="' + escHtml(id) + '" data-name="' + escHtml(d.name||'') + '"></td>' +
                '<td>—</td>' +
                '<td><strong>' + nameVal + '</strong></td>' +
                '<td>' + role + '</td>' +
                '<td>' + spec + '</td>' +
                '<td class="text-center"><span class="mp-status-badge ' + stCls + '">' + escHtml(status) + '</span></td>';
        } else {
            const total = (d.asset_details && d.asset_details.total_quantity)     || 0;
            const avail = (d.asset_details && d.asset_details.available_quantity) || 0;
            const inuse = total - avail;
            cellsHtml =
                '<td class="pz-col-check"><input type="checkbox" class="row-cb ' + cbClass + '" value="' + escHtml(id) + '" data-name="' + escHtml(d.name||'') + '"></td>' +
                '<td>—</td>' +
                '<td><strong>' + nameVal + '</strong></td>' +
                '<td class="text-center">' + total + '</td>' +
                '<td class="text-center">' + avail + '</td>' +
                '<td class="text-center">' + (inuse > 0 ? inuse : '—') + '</td>';
        }
        cellsHtml +=
            '<td class="text-center" style="white-space:nowrap">' +
              '<a href="' + BASE_URL + 'resources/show/' + escHtml(id) + '" class="action-btn" title="View"><span class="material-symbols-outlined">visibility</span></a>' +
              '<a href="' + BASE_URL + 'resources/edit/' + escHtml(id) + '" class="action-btn" title="Edit"><span class="material-symbols-outlined">edit</span></a>' +
              '<button type="button" class="action-btn action-archive" data-id="' + escHtml(id) + '" data-name="' + escHtml(d.name||'') + '" title="Archive"><span class="material-symbols-outlined">inventory_2</span></button>' +
              '<button type="button" class="action-btn action-delete" data-id="' + escHtml(id) + '" data-name="' + escHtml(d.name||'') + '" title="Delete"><span class="material-symbols-outlined">delete</span></button>' +
            '</td>';

        const row = document.createElement('tr');
        row.innerHTML = cellsHtml;
        row.querySelector('.action-delete').addEventListener('click', function () {
            pendingDeleteId = this.dataset.id;
            pendingRow      = row;
            setConfirmType('delete');
            confirmMessage.innerText = 'Delete "' + (this.dataset.name || 'this resource') + '"?';
            confirmDialog.classList.add('open');
        });
        row.querySelector('.action-archive').addEventListener('click', function () {
            wireArchiveBtn(this, row);
        });
        row.querySelector('.row-cb').addEventListener('change', updateBulkBar);

        row.style.opacity = '0'; row.style.background = '#edf7e6';
        tbody.appendChild(row);
        requestAnimationFrame(() => {
            row.style.transition = 'opacity 300ms ease, background 1.2s ease';
            row.style.opacity = '1';
            setTimeout(() => { row.style.background = ''; }, 1000);
        });
    }

    /* ── Filter panels ── */
    function wireFilterToggle(btnId, panelId, isActive) {
        const btn   = document.getElementById(btnId);
        const panel = document.getElementById(panelId);
        if (!btn || !panel) return;
        if (isActive) panel.style.display = 'block';
        btn.addEventListener('click', () => {
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        });
    }
    wireFilterToggle('eqFilterToggle',    'eqFilterPanel',    <?= $eqActiveCount > 0 ? 'true' : 'false' ?>);
    wireFilterToggle('toolsFilterToggle', 'toolsFilterPanel', <?= $toolsActiveCount > 0 ? 'true' : 'false' ?>);
    wireFilterToggle('mpFilterToggle',    'mpFilterPanel',    <?= $mpActiveCount > 0 ? 'true' : 'false' ?>);

    /* ── Sort chips (equipment & tools) ── */
    function wireSortChips(formId, hiddenId) {
        const form = document.getElementById(formId);
        const inp  = document.getElementById(hiddenId);
        if (!form || !inp) return;
        form.querySelectorAll('.sort-chip').forEach(chip => {
            chip.addEventListener('click', function () {
                inp.value = inp.value === this.dataset.val ? '' : this.dataset.val;
                form.submit();
            });
        });
    }
    wireSortChips('eqFilterForm',    'eqSortInput');
    wireSortChips('toolsFilterForm', 'toolsSortInput');

    /* ── Manpower filter chips ── */
    const mpForm = document.getElementById('mpFilterForm');
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

    /* ── Search (client-side filter on visible table) ── */
    function wireSearch(inputId, tableId) {
        const inp   = document.getElementById(inputId);
        const table = document.getElementById(tableId);
        if (!inp || !table) return;
        inp.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            table.querySelectorAll('tbody tr').forEach(row => {
                row.style.display = !q || row.innerText.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }
    wireSearch('searchEquipment', 'equipmentTable');
    wireSearch('searchTools',     'toolsTable');
    wireSearch('searchManpower',  'manpowerTable');

    /* ── Bulk select ── */
    const bulkBar     = document.getElementById('bulkActionBar');
    const bulkCountEl = document.getElementById('bulkCount');
    const bulkDelBtn  = document.getElementById('bulkDeleteBtn');

    function getActivePanel() { return document.querySelector('.rv-panel.active'); }
    function getCheckedInPanel(panel) {
        return panel ? Array.from(panel.querySelectorAll('.row-cb:checked')) : [];
    }
    window.updateBulkBar = function () {
        const panel   = getActivePanel();
        const checked = getCheckedInPanel(panel);
        if (bulkBar)     bulkBar.style.display = checked.length > 0 ? 'flex' : 'none';
        if (bulkCountEl) bulkCountEl.textContent = checked.length + ' selected';
    };

    document.querySelectorAll('.select-all-cb').forEach(sa => {
        sa.addEventListener('change', function () {
            const panel = this.closest('.rv-panel');
            if (!panel) return;
            panel.querySelectorAll('.row-cb').forEach(cb => { cb.checked = this.checked; });
            updateBulkBar();
        });
    });
    document.querySelectorAll('.row-cb').forEach(cb => {
        cb.addEventListener('change', updateBulkBar);
    });

    const bulkArchBtn = document.getElementById('bulkArchiveBtn');
    bulkArchBtn && bulkArchBtn.addEventListener('click', function () {
        const panel   = getActivePanel();
        const checked = getCheckedInPanel(panel);
        if (!checked.length) return;
        const names = checked.map(c => c.dataset.name || c.value).join(', ');
        setConfirmType('archive');
        confirmMessage.innerText = 'Archive ' + checked.length + ' item(s)?\n' + names;
        pendingDeleteId         = '__bulk_archive__';
        pendingBulkArchiveItems = checked.map(c => ({ id: c.value, row: c.closest('tr') }));
        confirmDialog.classList.add('open');
    });

    bulkDelBtn && bulkDelBtn.addEventListener('click', function () {
        const panel   = getActivePanel();
        const checked = getCheckedInPanel(panel);
        if (!checked.length) return;
        const names = checked.map(c => c.dataset.name || c.value).join(', ');
        setConfirmType('delete');
        confirmMessage.innerText = 'Delete ' + checked.length + ' item(s)?\n' + names;
        pendingDeleteId   = '__bulk__';
        pendingBulkItems  = checked.map(c => ({ id: c.value, row: c.closest('tr') }));
        confirmDialog.classList.add('open');
    });

    /* ── Single delete ── */
    let pendingDeleteId         = null;
    let pendingRow              = null;
    let pendingBulkItems        = [];
    let pendingArchiveId        = null;
    let pendingArchiveRow       = null;
    let pendingBulkArchiveItems = [];

    document.querySelectorAll('.action-delete').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            pendingDeleteId = this.dataset.id;
            pendingRow      = this.closest('tr');
            setConfirmType('delete');
            confirmMessage.innerText = 'Delete "' + (this.dataset.name || 'this resource') + '"?';
            confirmDialog.classList.add('open');
        });
    });

    // ── Archive ───────────────────────────────────────────────
    const ARCHIVE_URL = '<?= base_url('resources/archive') ?>';
    function wireArchiveBtn(btn, row) {
        const resolvedRow = row || btn.closest('tr');
        if (resolvedRow) resolvedRow.dataset.name = btn.dataset.name || 'this resource';
        pendingArchiveId  = btn.dataset.id;
        pendingArchiveRow = resolvedRow;
        setConfirmType('archive');
        confirmMessage.innerText = 'Archive "' + (btn.dataset.name || 'this resource') + '"? It will be moved to the archive and removed from active records.';
        confirmDialog.classList.add('open');
    }
    document.querySelectorAll('.action-archive').forEach(function (btn) {
        btn.addEventListener('click', function () { wireArchiveBtn(this, null); });
    });

    confirmCancel?.addEventListener('click', function () {
        pendingDeleteId = null; pendingRow = null; pendingBulkItems = [];
        pendingArchiveId = null; pendingArchiveRow = null; pendingBulkArchiveItems = [];
        confirmDialog.classList.remove('open');
    });

    confirmOk?.addEventListener('click', function () {
        // ── Archive path ──────────────────────────────────────────────
        if (pendingArchiveId) {
            const id   = pendingArchiveId;
            const row  = pendingArchiveRow;
            pendingArchiveId = null; pendingArchiveRow = null;
            confirmDialog.classList.remove('open');
            const body = new URLSearchParams();
            if (csrfName) body.append(csrfName, csrfHash);
            body.append('id', id);
            fetch(ARCHIVE_URL, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body })
                .then(r => r.json())
                .then(d => {
                    if (d && d.success) {
                        showToast('"' + (row?.dataset?.name || id) + '" archived', 'success');
                        const tbl = row?.closest('table');
                        animateRowOut(row);
                        if (tbl) setTimeout(() => renumberTable(tbl, 1), 290);
                        updateBulkBar();
                    } else {
                        showToast(d.message || 'Archive failed', 'error');
                    }
                })
                .catch(() => showToast('Error archiving resource', 'error'));
            return;
        }

        if (!pendingDeleteId) return;

        // Bulk archive path
        if (pendingDeleteId === '__bulk_archive__') {
            const items = [...pendingBulkArchiveItems];
            pendingBulkArchiveItems = []; pendingDeleteId = null;
            confirmDialog.classList.remove('open');
            let done = 0;
            items.forEach(item => {
                const body = new URLSearchParams();
                if (csrfName) body.append(csrfName, csrfHash);
                body.append('id', item.id);
                fetch(ARCHIVE_URL, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body })
                    .then(r => r.json())
                    .then(d => {
                        if (d && d.success) {
                            const tbl = item.row?.closest('table');
                            animateRowOut(item.row);
                            if (tbl) setTimeout(() => renumberTable(tbl, 1), 290);
                        }
                    })
                    .finally(() => {
                        done++;
                        if (done === items.length) {
                            showToast(items.length + ' item(s) archived', 'success');
                            updateBulkBar();
                        }
                    });
            });
            return;
        }

        if (pendingDeleteId === '__bulk__') {
            const items = [...pendingBulkItems];
            pendingBulkItems = []; pendingDeleteId = null;
            confirmDialog.classList.remove('open');
            let done = 0;
            items.forEach(item => {
                const body = new URLSearchParams();
                if (csrfName) body.append(csrfName, csrfHash);
                body.append('id', item.id);
                fetch(DELETE_URL, { method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body })
                    .then(r => r.json())
                    .then(d => { if (d && d.success) animateRowOut(item.row); })
                    .finally(() => { done++; if (done === items.length) { showToast(items.length + ' item(s) deleted', 'success'); updateBulkBar(); } });
            });
            return;
        }

        const body = new URLSearchParams();
        if (csrfName) body.append(csrfName, csrfHash);
        body.append('id', pendingDeleteId);
        fetch(DELETE_URL, { method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body })
            .then(r => r.json())
            .then(d => {
                if (d && d.success) {
                    showToast('Resource deleted', 'success');
                    animateRowOut(pendingRow);
                    updateBulkBar();
                } else {
                    showToast(d.message || 'Delete failed', 'error');
                }
            })
            .catch(() => showToast('Error deleting resource', 'error'))
            .finally(() => { pendingDeleteId = null; pendingRow = null; confirmDialog.classList.remove('open'); });
    });

});

/* ── Export / Import modals ─────────────────────────────── */
(function () {
    const baseUrl = '<?= base_url() ?>';
    const csrfName = document.querySelector('meta[name="csrf-name"]')?.content;
    const csrfHash = document.querySelector('meta[name="csrf-hash"]')?.content;
    let currentImportModule = '';
    const resourceModules = ['equipment', 'tools'];

    function buildExportLinks(module, subtype) {
        const sub = resourceModules.includes(module) ? '&subtype=' + subtype : '';
        document.getElementById('exportXlsx').href  = baseUrl + 'export?module=' + module + '&format=xlsx'  + sub;
        document.getElementById('exportCsv').href   = baseUrl + 'export?module=' + module + '&format=csv'   + sub;
        document.getElementById('exportPdf').href   = baseUrl + 'export?module=' + module + '&format=pdf'   + sub;
        document.getElementById('exportPrint').href = baseUrl + 'export?module=' + module + '&format=print' + sub;
    }

    window.openExportModal = function (module, label) {
        const m = document.getElementById('exportModal');
        document.getElementById('exportModalTitle').textContent = 'Export ' + (label || module);
        m.dataset.module = module;
        const isRes = resourceModules.includes(module);
        document.getElementById('exportSubtypeWrap').style.display = isRes ? 'block' : 'none';
        document.querySelectorAll('input[name="exportSubtype"]').forEach(r => { r.checked = r.value === 'models'; });
        buildExportLinks(module, 'models');
        m.hidden = false; m.style.display = 'flex';
    };
    window.closeExportModal = function () {
        const m = document.getElementById('exportModal');
        m.hidden = true; m.style.display = 'none';
    };
    document.querySelectorAll('input[name="exportSubtype"]').forEach(r => {
        r.addEventListener('change', function () {
            buildExportLinks(document.getElementById('exportModal').dataset.module || '', this.value);
        });
    });

    window.openImportModal = function (module) {
        currentImportModule = module;
        const label = module.charAt(0).toUpperCase() + module.slice(1);
        document.getElementById('importModalTitle').textContent = 'Import ' + label;
        document.getElementById('importTplLink').href = baseUrl + 'import/template?module=' + module;
        document.getElementById('importFileName').textContent = '';
        document.getElementById('importResult').textContent   = '';
        document.getElementById('importFileInput').value      = '';
        const m = document.getElementById('importModal');
        m.hidden = false; m.style.display = 'flex';
    };
    window.closeImportModal = function () {
        const m = document.getElementById('importModal');
        m.hidden = true; m.style.display = 'none';
    };
    window.submitImport = function () {
        const fi = document.getElementById('importFileInput');
        const re = document.getElementById('importResult');
        if (!fi.files.length) { re.style.color = '#c0392b'; re.textContent = 'Please select a file first.'; return; }
        const btn = document.getElementById('importSubmitBtn');
        btn.disabled = true; btn.textContent = 'Importing…';
        re.style.color = '#555'; re.textContent = 'Processing…';
        const fd = new FormData();
        fd.append('module', currentImportModule);
        fd.append('import_file', fi.files[0]);
        if (csrfName) fd.append(csrfName, csrfHash);
        fetch(baseUrl + 'import/process', { method:'POST', body:fd })
            .then(r => r.json())
            .then(d => {
                btn.disabled = false; btn.textContent = 'Import';
                if (d.success) { re.style.color='#27ae60'; re.textContent='✓ '+d.message; setTimeout(() => { closeImportModal(); location.reload(); }, 1800); }
                else { re.style.color='#c0392b'; re.textContent='✗ '+(d.message||'Import failed.'); }
            })
            .catch(() => { btn.disabled=false; btn.textContent='Import'; re.style.color='#c0392b'; re.textContent='Network error.'; });
    };

    const dz = document.getElementById('importDropZone');
    const fi = document.getElementById('importFileInput');
    const fn = document.getElementById('importFileName');
    if (dz) {
        dz.addEventListener('click', () => fi.click());
        dz.addEventListener('dragover', e => { e.preventDefault(); dz.style.borderColor = '#C47A0F'; });
        dz.addEventListener('dragleave', () => { dz.style.borderColor = '#d4c185'; });
        dz.addEventListener('drop', e => { e.preventDefault(); dz.style.borderColor='#d4c185'; if(e.dataTransfer.files.length){ fi.files=e.dataTransfer.files; fn.textContent='📎 '+fi.files[0].name; } });
        fi.addEventListener('change', () => { if(fi.files.length) fn.textContent='📎 '+fi.files[0].name; });
    }
    ['exportModal','importModal'].forEach(id => {
        document.getElementById(id)?.addEventListener('click', function(e){ if(e.target===this){this.hidden=true;this.style.display='none';} });
    });
}());
</script>

<?= $this->endSection() ?>
