<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="customToast" class="custom-toast" aria-live="polite" aria-atomic="true"></div>

<meta name="csrf-name" content="<?= csrf_token() ?>">
<meta name="csrf-hash" content="<?= csrf_hash() ?>">

<!-- Confirm dialog -->
<div id="confirmDialog" class="confirm-dialog" role="dialog" aria-modal="true">
    <div class="confirm-backdrop"></div>
    <div class="confirm-box">
        <div class="confirm-content"><p id="confirmMessage">Are you sure?</p></div>
        <div class="confirm-actions">
            <button id="confirmCancel" class="btn-toolbar">Cancel</button>
            <button id="confirmOk"     class="btn-toolbar btn-add">OK</button>
        </div>
    </div>
</div>

<style>
/* ── Page shell ─────────────────────────────────────────────── */
.rv-shell { max-width: 860px; }

/* ── Tabs ───────────────────────────────────────────────────── */
.rv-tabs {
    display: flex; border-bottom: 2px solid #e5e0d0; margin-bottom: 1.2rem;
}
.rv-tab {
    background: none; border: none; padding: 9px 22px;
    font-size: 0.8rem; font-weight: 700; letter-spacing: 0.06em;
    color: #bbb; cursor: pointer; border-bottom: 2px solid transparent;
    margin-bottom: -2px; font-family: inherit; transition: color .15s;
}
.rv-tab.active  { color: #8C701C; border-bottom-color: #C47A0F; }
.rv-tab:hover   { color: #8C701C; }
.rv-panel       { display: none; }
.rv-panel.active { display: block; }

/* ── Controls bar ───────────────────────────────────────────── */
.rv-controls {
    display: flex; align-items: center; flex-wrap: wrap; gap: 8px;
    margin-bottom: 0.9rem;
}
.rv-search {
    flex: 1; min-width: 160px; padding: 8px 12px;
    border: 1px solid #ddd; border-radius: 8px;
    font-size: 0.87rem; font-family: inherit; outline: none;
}
.rv-search:focus { border-color: #C47A0F; }
.rv-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 7px 14px; border-radius: 8px; font-size: 0.8rem;
    font-weight: 700; letter-spacing: 0.04em; cursor: pointer;
    border: 1.5px solid transparent; font-family: inherit; white-space: nowrap;
    font-variation-settings: 'FILL' 0, 'wght' 400;
}
.rv-btn .material-symbols-outlined { font-size: 16px; }
.rv-btn-filter  { background: #fff; border-color: #ddd; color: #555; }
.rv-btn-filter:hover { border-color: #C47A0F; color: #8C701C; }
.rv-btn-add     { background: #8C701C; color: #fff; border-color: #8C701C; text-decoration: none; }
.rv-btn-add:hover { background: #6e570f; color: #fff; text-decoration: none; }
.rv-btn-import  { background: #fff; border-color: #adb5bd; color: #495057; }
.rv-btn-import:hover { border-color: #8C701C; color: #8C701C; }
.rv-btn-export  { background: #fff; border-color: #adb5bd; color: #495057; }
.rv-btn-export:hover { border-color: #8C701C; color: #8C701C; }

/* ── Filter panel ───────────────────────────────────────────── */
.rv-filter-panel {
    background: #fdf8ee; border: 1px solid #e8d9a8; border-radius: 8px;
    padding: 14px 16px 10px; margin-bottom: 0.9rem;
}
.rv-filter-row  { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 10px; }
.rv-filter-row:last-child { margin-bottom: 0; }
.rv-filter-label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
    color: #8C701C; letter-spacing: 0.05em; min-width: 100px; }
.rv-chip-row    { display: flex; flex-wrap: wrap; gap: 6px; }
.rv-chip {
    padding: 4px 12px; border: 1.5px solid #d4c185; border-radius: 20px;
    font-size: 0.75rem; font-weight: 700; cursor: pointer; background: #fff;
    color: #8C701C; font-family: inherit;
}
.rv-chip:hover  { background: #8C701C; color: #fff; border-color: #8C701C; }
.rv-chip.active { background: #8C701C; color: #fff; border-color: #8C701C; }
.rv-filter-badge {
    display: inline-flex; align-items: center; justify-content: center;
    background: #8C701C; color: #fff; border-radius: 50%;
    font-size: 9px; font-weight: 800; width: 14px; height: 14px; margin-left: 3px;
}
.rv-filter-reset {
    background: none; border: 1px solid #d4c185; color: #8C701C;
    border-radius: 6px; padding: 5px 12px; font-size: 0.78rem; font-weight: 600;
    cursor: pointer; font-family: inherit; text-decoration: none;
    display: inline-block;
}
.rv-filter-reset:hover { background: #8C701C; color: #fff; text-decoration: none; }

/* ── Resource cards ─────────────────────────────────────────── */
.rv-cards { display: flex; flex-direction: column; gap: 8px; }
.rv-card {
    background: #fff; border: 1px solid #e5e0d0; border-radius: 10px;
    padding: 12px 14px; display: flex; align-items: center; gap: 12px;
    transition: border-color .15s, box-shadow .15s;
}
.rv-card:hover { border-color: #d4c185; box-shadow: 0 2px 8px rgba(140,112,28,.08); }

/* checkbox column */
.rv-card-check { flex-shrink: 0; }
.rv-card-check input[type="checkbox"] {
    width: 15px; height: 15px; cursor: pointer; accent-color: #C47A0F;
}

/* icon column */
.rv-card-icon {
    flex-shrink: 0; width: 40px; height: 40px; border-radius: 10px;
    background: #fdf8ee; display: flex; align-items: center; justify-content: center;
    font-variation-settings: 'FILL' 1, 'wght' 600;
}
.rv-card-icon .material-symbols-outlined { font-size: 20px; color: #C47A0F; }

/* info column */
.rv-card-info { flex: 1; min-width: 0; }
.rv-card-name {
    font-size: 0.92rem; font-weight: 800; color: #2c1f07;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.rv-card-sub  { font-size: 0.75rem; color: #999; margin-top: 1px; }
.rv-card-stats { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 5px; }
.rv-stat {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 0.75rem; color: #666;
}
.rv-stat-dot {
    width: 7px; height: 7px; border-radius: 50%;
}
.rv-stat-dot.green  { background: #2e7d32; }
.rv-stat-dot.orange { background: #e65100; }
.rv-stat-dot.grey   { background: #bbb; }

/* status badge */
.rv-status {
    display: inline-block; padding: 3px 10px; border-radius: 20px;
    font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;
    margin-top: 4px;
}
.rv-status-available { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
.rv-status-deployed  { background: #fff3e0; color: #e65100; border: 1px solid #ffcc80; }
.rv-status-onleave   { background: #f5f5f5; color: #757575; border: 1px solid #e0e0e0; }
.rv-status-other     { background: #f5f5f5; color: #757575; border: 1px solid #e0e0e0; }

/* secondary info */
.rv-card-meta { font-size: 0.73rem; color: #aaa; margin-top: 2px; }

/* actions column */
.rv-card-actions { display: flex; align-items: center; gap: 2px; flex-shrink: 0; }
.rv-action {
    background: none; border: none; cursor: pointer; padding: 6px;
    border-radius: 6px; color: #aaa; text-decoration: none;
    display: inline-flex; align-items: center; justify-content: center;
    font-variation-settings: 'FILL' 0, 'wght' 400;
}
.rv-action .material-symbols-outlined { font-size: 18px; }
.rv-action:hover { background: #fdf8ee; color: #8C701C; text-decoration: none; }
.rv-action.rv-action-delete:hover { background: #fff0f0; color: #c62828; }

/* select-all row */
.rv-select-all-row {
    display: flex; align-items: center; gap: 8px;
    padding: 6px 14px; font-size: 0.78rem; color: #888;
    margin-bottom: 4px;
}
.rv-select-all-row input[type="checkbox"] { width: 15px; height: 15px; accent-color: #C47A0F; cursor: pointer; }

/* empty state */
.rv-empty {
    text-align: center; padding: 2.5rem 1rem; color: #ccc; font-size: 0.9rem;
}
.rv-empty .material-symbols-outlined {
    font-size: 2.5rem; display: block; margin-bottom: 0.5rem; color: #e5e0d0;
}

/* pager */
.rv-pager-wrap { margin-top: 1rem; }

/* ── Recently Deleted ───────────────────────────────────────── */
.rv-rd-wrap { margin-top: 1.5rem; border: 1px solid #e8d9a8; border-radius: 10px; overflow: hidden; }
.rv-rd-toggle {
    width: 100%; display: flex; align-items: center; gap: 0.5rem;
    background: #fdf8ee; border: none; padding: 0.7rem 1rem;
    font-size: 0.85rem; font-weight: 700; color: #5c3d11; cursor: pointer;
    font-family: inherit; text-align: left;
    font-variation-settings: 'FILL' 0, 'wght' 400;
}
.rv-rd-toggle:hover { background: #f5e7b2; }
.rv-rd-toggle .material-symbols-outlined { font-size: 18px; color: #8c701c; }
.rv-rd-chevron { margin-left: auto; transition: transform 0.2s; font-size: 18px !important; }
.rv-rd-badge {
    background: #e53e3e; color: #fff; border-radius: 10px;
    font-size: 0.7rem; font-weight: 800; padding: 0.1rem 0.45rem; line-height: 1.4;
}
.rv-rd-panel { padding: 0.7rem 1rem; background: #fff; }
.rv-rd-empty { color: #aaa; font-size: 0.85rem; text-align: center; padding: 0.8rem 0; }
.rd-table { font-size: 0.84rem; width: 100%; border-collapse: collapse; }
.rd-table th { background: #fdf8ee; color: #8c701c; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; padding: 6px 10px; border-bottom: 2px solid #e8d9a8; text-align: left; }
.rd-table td { padding: 6px 10px; border-bottom: 1px solid #f0e6c8; font-size: 0.82rem; }
.rd-table tr:last-child td { border-bottom: none; }

/* ── Bulk action bar ────────────────────────────────────────── */
.rv-bulk-bar {
    position: fixed; bottom: 1.5rem; left: 50%; transform: translateX(-50%);
    background: #3a2a0a; color: #fff; border-radius: 30px;
    padding: 0.65rem 1.2rem; display: flex; align-items: center; gap: 1rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.35); z-index: 200;
    animation: rvSlideUp 0.2s ease;
}
@keyframes rvSlideUp { from { opacity:0; transform:translateX(-50%) translateY(16px); } to { opacity:1; transform:translateX(-50%) translateY(0); } }
.rv-bulk-count { font-size: 0.88rem; font-weight: 600; }
.rv-bulk-del-btn {
    display: flex; align-items: center; gap: 5px;
    background: #e53e3e; color: #fff; border: none; border-radius: 20px;
    padding: 0.4rem 1rem; font-size: 0.82rem; font-weight: 700; cursor: pointer;
    font-family: inherit; font-variation-settings: 'FILL' 0, 'wght' 400;
}
.rv-bulk-del-btn:hover { background: #c53030; }
.rv-bulk-del-btn .material-symbols-outlined { font-size: 16px; }
</style>

<?php
    $activeEquipFilters    = $activeEquipFilters    ?? [];
    $activeToolFilters     = $activeToolFilters     ?? [];
    $activeManpowerFilters = $activeManpowerFilters ?? [];
    $activeTab             = $activeTab             ?? 'equipment';
    $eqActiveCount         = count(array_filter($activeEquipFilters));
    $toolsActiveCount      = count(array_filter($activeToolFilters));
    $mpActiveCount         = count(array_filter($activeManpowerFilters));
    $recentlyDeletedResources = $recentlyDeletedResources ?? [];
    $recentlyDeletedManpower  = $recentlyDeletedManpower  ?? [];
    $rdEquipment = array_values(array_filter($recentlyDeletedResources, fn($r) => strcasecmp((string)($r['data']['category'] ?? ''), 'Equipment') === 0));
    $rdTools     = array_values(array_filter($recentlyDeletedResources, fn($r) => strcasecmp((string)($r['data']['category'] ?? ''), 'Tools')     === 0));
    $rdManpower  = $recentlyDeletedManpower;
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
<div class="rv-shell">
<div class="rv-tabs">
    <button class="rv-tab <?= $activeTab === 'equipment' ? 'active' : '' ?>" data-panel="equipment">EQUIPMENT</button>
    <button class="rv-tab <?= $activeTab === 'tools'     ? 'active' : '' ?>" data-panel="tools">TOOLS</button>
    <button class="rv-tab <?= $activeTab === 'manpower'  ? 'active' : '' ?>" data-panel="manpower">MANPOWER</button>
</div>

<!-- ══════════════════════════════════════════════════════════
     EQUIPMENT PANEL
══════════════════════════════════════════════════════════ -->
<div id="panel-equipment" class="rv-panel <?= $activeTab === 'equipment' ? 'active' : '' ?>">

    <div class="rv-controls">
        <input type="text" id="searchEquipment" class="rv-search" placeholder="Search equipment…">
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
        <!-- Select-all row -->
        <div class="rv-select-all-row">
            <input type="checkbox" class="select-all-cb" data-tab="equipment" title="Select all">
            <span>Select all</span>
        </div>

        <div class="rv-cards" id="equipmentCards">
            <?php if (empty($equipment)): ?>
            <div class="rv-empty">
                <span class="material-symbols-outlined">build</span>
                No equipment found.
            </div>
            <?php else: ?>
            <?php foreach ($equipment as $item):
                $total = (int)($item['total_quantity_display']    ?? $item['asset_details']['total_quantity']    ?? 0);
                $avail = (int)($item['available_units_display']   ?? $item['asset_details']['available_quantity'] ?? 0);
                $inuse = $total - $avail;
            ?>
            <div class="rv-card">
                <div class="rv-card-check">
                    <input type="checkbox" class="row-cb eq-cb"
                           value="<?= esc((string)$item['id']) ?>"
                           data-name="<?= esc((string)($item['name'] ?? '')) ?>">
                </div>
                <div class="rv-card-icon">
                    <span class="material-symbols-outlined">build</span>
                </div>
                <div class="rv-card-info">
                    <div class="rv-card-name"><?= esc(strtoupper((string)($item['name'] ?? 'N/A'))) ?></div>
                    <div class="rv-card-sub">Equipment model</div>
                    <div class="rv-card-stats">
                        <span class="rv-stat">
                            <span class="rv-stat-dot grey"></span> Total: <?= $total ?>
                        </span>
                        <span class="rv-stat">
                            <span class="rv-stat-dot green"></span> Available: <?= $avail ?>
                        </span>
                        <?php if ($inuse > 0): ?>
                        <span class="rv-stat">
                            <span class="rv-stat-dot orange"></span> In-Use: <?= $inuse ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="rv-card-actions">
                    <a href="<?= base_url('resources/show/' . $item['id']) ?>" class="rv-action" title="View">
                        <span class="material-symbols-outlined">visibility</span>
                    </a>
                    <a href="<?= base_url('resources/edit/' . $item['id']) ?>" class="rv-action" title="Edit">
                        <span class="material-symbols-outlined">edit</span>
                    </a>
                    <button type="button" class="rv-action rv-action-delete action-delete"
                            data-id="<?= esc((string)$item['id']) ?>"
                            data-name="<?= esc((string)($item['name'] ?? 'this resource')) ?>"
                            title="Delete">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="rv-pager-wrap pager-wrapper">
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

    <!-- Recently Deleted – Equipment -->
    <?= $this->renderSection('_rd_equipment') ?>
    <div class="rv-rd-wrap">
        <button type="button" class="rv-rd-toggle" id="rdToggleBtnEquipment">
            <span class="material-symbols-outlined">restore_from_trash</span>
            Recently Deleted
            <?php if (!empty($rdEquipment)): ?><span class="rv-rd-badge"><?= count($rdEquipment) ?></span><?php endif; ?>
            <span class="material-symbols-outlined rv-rd-chevron">expand_more</span>
        </button>
        <div class="rv-rd-panel" id="rdPanelEquipment" style="display:none">
            <?php if (empty($rdEquipment)): ?>
            <p class="rv-rd-empty">No recently deleted equipment.</p>
            <?php else: ?>
            <table class="rd-table">
                <thead><tr><th>Name</th><th>Deleted</th><th>Expires</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($rdEquipment as $rd): ?>
                <tr data-trash-id="<?= esc($rd['id']) ?>">
                    <td><?= esc($rd['name'] ?? '—') ?></td>
                    <td><?= date('d M Y H:i', (int)($rd['deleted_at'] ?? 0)) ?></td>
                    <td><?= max(0, (int)ceil((($rd['expires_at'] ?? 0) - time()) / 86400)) ?> days</td>
                    <td style="white-space:nowrap">
                        <button type="button" class="rv-action rd-restore-btn" data-id="<?= esc($rd['id']) ?>" title="Restore"><span class="material-symbols-outlined">restore</span></button>
                        <button type="button" class="rv-action rv-action-delete rd-perma-btn" data-id="<?= esc($rd['id']) ?>" title="Delete forever"><span class="material-symbols-outlined">delete_forever</span></button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div><!-- /panel-equipment -->

<!-- ══════════════════════════════════════════════════════════
     TOOLS PANEL
══════════════════════════════════════════════════════════ -->
<div id="panel-tools" class="rv-panel <?= $activeTab === 'tools' ? 'active' : '' ?>">

    <div class="rv-controls">
        <input type="text" id="searchTools" class="rv-search" placeholder="Search tools…">
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
        <div class="rv-select-all-row">
            <input type="checkbox" class="select-all-cb" data-tab="tools" title="Select all">
            <span>Select all</span>
        </div>

        <div class="rv-cards" id="toolsCards">
            <?php if (empty($tools)): ?>
            <div class="rv-empty">
                <span class="material-symbols-outlined">construction</span>
                No tools found.
            </div>
            <?php else: ?>
            <?php foreach ($tools as $item):
                $total = (int)($item['total_quantity_display']  ?? $item['asset_details']['total_quantity']    ?? 0);
                $avail = (int)($item['available_units_display'] ?? $item['asset_details']['available_quantity'] ?? 0);
                $inuse = $total - $avail;
            ?>
            <div class="rv-card">
                <div class="rv-card-check">
                    <input type="checkbox" class="row-cb tools-cb"
                           value="<?= esc((string)$item['id']) ?>"
                           data-name="<?= esc((string)($item['name'] ?? '')) ?>">
                </div>
                <div class="rv-card-icon">
                    <span class="material-symbols-outlined">construction</span>
                </div>
                <div class="rv-card-info">
                    <div class="rv-card-name"><?= esc(strtoupper((string)($item['name'] ?? 'N/A'))) ?></div>
                    <div class="rv-card-sub">Tool</div>
                    <div class="rv-card-stats">
                        <span class="rv-stat"><span class="rv-stat-dot grey"></span> Total: <?= $total ?></span>
                        <span class="rv-stat"><span class="rv-stat-dot green"></span> Available: <?= $avail ?></span>
                        <?php if ($inuse > 0): ?>
                        <span class="rv-stat"><span class="rv-stat-dot orange"></span> In-Use: <?= $inuse ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="rv-card-actions">
                    <a href="<?= base_url('resources/show/' . $item['id']) ?>" class="rv-action" title="View">
                        <span class="material-symbols-outlined">visibility</span>
                    </a>
                    <a href="<?= base_url('resources/edit/' . $item['id']) ?>" class="rv-action" title="Edit">
                        <span class="material-symbols-outlined">edit</span>
                    </a>
                    <button type="button" class="rv-action rv-action-delete action-delete"
                            data-id="<?= esc((string)$item['id']) ?>"
                            data-name="<?= esc((string)($item['name'] ?? 'this resource')) ?>"
                            title="Delete">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="rv-pager-wrap pager-wrapper">
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

    <div class="rv-rd-wrap">
        <button type="button" class="rv-rd-toggle" id="rdToggleBtnTools">
            <span class="material-symbols-outlined">restore_from_trash</span>
            Recently Deleted
            <?php if (!empty($rdTools)): ?><span class="rv-rd-badge"><?= count($rdTools) ?></span><?php endif; ?>
            <span class="material-symbols-outlined rv-rd-chevron">expand_more</span>
        </button>
        <div class="rv-rd-panel" id="rdPanelTools" style="display:none">
            <?php if (empty($rdTools)): ?>
            <p class="rv-rd-empty">No recently deleted tools.</p>
            <?php else: ?>
            <table class="rd-table">
                <thead><tr><th>Name</th><th>Deleted</th><th>Expires</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($rdTools as $rd): ?>
                <tr data-trash-id="<?= esc($rd['id']) ?>">
                    <td><?= esc($rd['name'] ?? '—') ?></td>
                    <td><?= date('d M Y H:i', (int)($rd['deleted_at'] ?? 0)) ?></td>
                    <td><?= max(0, (int)ceil((($rd['expires_at'] ?? 0) - time()) / 86400)) ?> days</td>
                    <td style="white-space:nowrap">
                        <button type="button" class="rv-action rd-restore-btn" data-id="<?= esc($rd['id']) ?>" title="Restore"><span class="material-symbols-outlined">restore</span></button>
                        <button type="button" class="rv-action rv-action-delete rd-perma-btn" data-id="<?= esc($rd['id']) ?>" title="Delete forever"><span class="material-symbols-outlined">delete_forever</span></button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div><!-- /panel-tools -->

<!-- ══════════════════════════════════════════════════════════
     MANPOWER PANEL
══════════════════════════════════════════════════════════ -->
<div id="panel-manpower" class="rv-panel <?= $activeTab === 'manpower' ? 'active' : '' ?>">

    <div class="rv-controls">
        <input type="text" id="searchManpower" class="rv-search" placeholder="Search manpower…">
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
        <div class="rv-select-all-row">
            <input type="checkbox" class="select-all-cb" data-tab="manpower" title="Select all">
            <span>Select all</span>
        </div>

        <div class="rv-cards" id="manpowerCards">
            <?php if (empty($manpower)): ?>
            <div class="rv-empty">
                <span class="material-symbols-outlined">groups</span>
                No manpower records found.
            </div>
            <?php else: ?>
            <?php foreach ($manpower as $person):
                $mpStatus = strtolower(str_replace(['-', ' '], '', (string)($person['status'] ?? 'available')));
                $statusClass = match($mpStatus) {
                    'available' => 'rv-status-available',
                    'deployed'  => 'rv-status-deployed',
                    'onleave'   => 'rv-status-onleave',
                    default     => 'rv-status-other',
                };
                $role    = (string)($person['manpower_details']['role']           ?? $person['role']           ?? '');
                $spec    = (string)($person['manpower_details']['specialization'] ?? $person['specialization'] ?? '');
                $site    = (string)($person['manpower_details']['current_site_deployment'] ?? $person['location']['address'] ?? '');
                $nextAv  = (string)($person['next_availability'] ?? '');
            ?>
            <div class="rv-card">
                <div class="rv-card-check">
                    <input type="checkbox" class="row-cb mp-cb"
                           value="<?= esc((string)$person['id']) ?>"
                           data-name="<?= esc((string)($person['name'] ?? '')) ?>">
                </div>
                <div class="rv-card-icon">
                    <span class="material-symbols-outlined">person</span>
                </div>
                <div class="rv-card-info">
                    <div class="rv-card-name"><?= esc(strtoupper((string)($person['name'] ?? 'N/A'))) ?></div>
                    <div class="rv-card-sub">
                        <?= esc($role) ?><?= ($role && $spec) ? ' &bull; ' : '' ?><?= esc($spec) ?>
                    </div>
                    <?php if ($site): ?>
                    <div class="rv-card-meta">
                        <span class="material-symbols-outlined" style="font-size:13px;vertical-align:-2px;color:#bbb">location_on</span>
                        <?= esc($site) ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($nextAv && $nextAv !== 'N/A'): ?>
                    <div class="rv-card-meta">Next available: <?= esc($nextAv) ?></div>
                    <?php endif; ?>
                    <span class="rv-status <?= $statusClass ?>">
                        <?= esc((string)($person['status'] ?? 'Available')) ?>
                    </span>
                </div>
                <div class="rv-card-actions">
                    <a href="<?= base_url('resources/show/' . $person['id']) ?>" class="rv-action" title="View">
                        <span class="material-symbols-outlined">visibility</span>
                    </a>
                    <a href="<?= base_url('resources/edit/' . $person['id']) ?>" class="rv-action" title="Edit">
                        <span class="material-symbols-outlined">edit</span>
                    </a>
                    <button type="button" class="rv-action rv-action-delete action-delete"
                            data-id="<?= esc((string)$person['id']) ?>"
                            data-name="<?= esc((string)($person['name'] ?? 'this resource')) ?>"
                            title="Delete">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="rv-pager-wrap pager-wrapper">
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

    <div class="rv-rd-wrap">
        <button type="button" class="rv-rd-toggle" id="rdToggleBtnManpower">
            <span class="material-symbols-outlined">restore_from_trash</span>
            Recently Deleted
            <?php if (!empty($rdManpower)): ?><span class="rv-rd-badge"><?= count($rdManpower) ?></span><?php endif; ?>
            <span class="material-symbols-outlined rv-rd-chevron">expand_more</span>
        </button>
        <div class="rv-rd-panel" id="rdPanelManpower" style="display:none">
            <?php if (empty($rdManpower)): ?>
            <p class="rv-rd-empty">No recently deleted manpower.</p>
            <?php else: ?>
            <table class="rd-table">
                <thead><tr><th>Name</th><th>Deleted</th><th>Expires</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($rdManpower as $rd): ?>
                <tr data-trash-id="<?= esc($rd['id']) ?>">
                    <td><?= esc($rd['name'] ?? '—') ?></td>
                    <td><?= date('d M Y H:i', (int)($rd['deleted_at'] ?? 0)) ?></td>
                    <td><?= max(0, (int)ceil((($rd['expires_at'] ?? 0) - time()) / 86400)) ?> days</td>
                    <td style="white-space:nowrap">
                        <button type="button" class="rv-action rd-restore-btn" data-id="<?= esc($rd['id']) ?>" title="Restore"><span class="material-symbols-outlined">restore</span></button>
                        <button type="button" class="rv-action rv-action-delete rd-perma-btn" data-id="<?= esc($rd['id']) ?>" title="Delete forever"><span class="material-symbols-outlined">delete_forever</span></button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div><!-- /panel-manpower -->
</div><!-- /.rv-shell -->

<!-- Bulk action bar -->
<div class="rv-bulk-bar" id="bulkActionBar" style="display:none">
    <span class="rv-bulk-count" id="bulkCount">0 selected</span>
    <button type="button" class="rv-bulk-del-btn" id="bulkDeleteBtn">
        <span class="material-symbols-outlined">delete</span> Delete Selected
    </button>
</div>

<!-- Export Modal -->
<div id="exportModal" style="display:none;position:fixed;inset:0;z-index:3000;background:rgba(0,0,0,.45);align-items:center;justify-content:center;" hidden>
  <div style="background:#fff;border-radius:12px;padding:28px 32px;min-width:340px;max-width:420px;box-shadow:0 8px 32px rgba(0,0,0,.18);">
    <h3 id="exportModalTitle" style="margin:0 0 6px;color:#2c1f07;font-size:17px;">Export</h3>
    <div id="exportSubtypeWrap" style="display:none;margin:14px 0;background:#fdf8ee;border:1px solid #e8d9a8;border-radius:8px;padding:12px 16px;">
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
    <p style="margin:12px 0 14px;color:#555;font-size:13px;">Choose a format to download:</p>
    <div style="display:flex;flex-direction:column;gap:10px;">
      <a id="exportXlsx"  href="#" target="_blank" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px solid #8C701C;border-radius:8px;text-decoration:none;color:#8C701C;font-weight:600;font-size:14px;"><span style="font-size:20px;">📊</span> Download XLSX</a>
      <a id="exportCsv"   href="#" target="_blank" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px solid #7b5ea7;border-radius:8px;text-decoration:none;color:#7b5ea7;font-weight:600;font-size:14px;"><span style="font-size:20px;">📋</span> Download CSV</a>
      <a id="exportPdf"   href="#" target="_blank" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px solid #c0392b;border-radius:8px;text-decoration:none;color:#c0392b;font-weight:600;font-size:14px;"><span style="font-size:20px;">📄</span> Download PDF</a>
      <a id="exportPrint" href="#" target="_blank" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px solid #27ae60;border-radius:8px;text-decoration:none;color:#27ae60;font-weight:600;font-size:14px;"><span style="font-size:20px;">🖨️</span> Open Printable View</a>
    </div>
    <div style="text-align:right;margin-top:18px;">
      <button onclick="closeExportModal()" style="padding:8px 18px;background:#f0f0f0;border:none;border-radius:6px;cursor:pointer;font-size:13px;">Close</button>
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

    /* ── Animate card out ── */
    function animateCardOut(card) {
        if (!card) return;
        const h = card.offsetHeight;
        card.style.height  = h + 'px';
        card.style.overflow = 'hidden';
        requestAnimationFrame(() => {
            card.style.transition = 'height 260ms ease, opacity 260ms ease, transform 260ms ease';
            card.style.height     = '0';
            card.style.padding    = '0';
            card.style.opacity    = '0';
            card.style.transform  = 'translateX(-8px)';
        });
        setTimeout(() => card.remove(), 280);
    }

    /* ── Add restored resource as card ── */
    function addRestoredCard(module, id, d) {
        let containerId, cbClass;
        if (module === 'manpower') {
            containerId = 'manpowerCards'; cbClass = 'mp-cb';
        } else {
            const cat = (d.category || '').toLowerCase();
            containerId = cat === 'tools' ? 'toolsCards' : 'equipmentCards';
            cbClass     = cat === 'tools' ? 'tools-cb' : 'eq-cb';
        }
        const container = document.getElementById(containerId);
        if (!container) return;

        // Remove empty-state placeholder if present
        container.querySelector('.rv-empty')?.remove();

        const nameVal = escHtml((d.name || 'N/A').toUpperCase());
        let infoHtml, iconName;

        if (module === 'manpower') {
            iconName = 'person';
            const role   = escHtml(d.role || (d.manpower_details && d.manpower_details.role) || '');
            const spec   = escHtml(d.specialization || (d.manpower_details && d.manpower_details.specialization) || '');
            const status = d.status || 'Available';
            const stLow  = status.toLowerCase().replace(/[^a-z]/g,'');
            const stClass = {available:'rv-status-available', deployed:'rv-status-deployed', onleave:'rv-status-onleave'}[stLow] || 'rv-status-other';
            infoHtml = '<div class="rv-card-name">' + nameVal + '</div>' +
                '<div class="rv-card-sub">' + (role + (role && spec ? ' &bull; ' : '') + spec || '&nbsp;') + '</div>' +
                '<span class="rv-status ' + stClass + '">' + escHtml(status) + '</span>';
        } else {
            iconName = d.category === 'Tools' ? 'construction' : 'build';
            const total = (d.asset_details && d.asset_details.total_quantity)     || 0;
            const avail = (d.asset_details && d.asset_details.available_quantity) || 0;
            const inuse = total - avail;
            infoHtml = '<div class="rv-card-name">' + nameVal + '</div>' +
                '<div class="rv-card-sub">' + (d.category === 'Tools' ? 'Tool' : 'Equipment model') + '</div>' +
                '<div class="rv-card-stats">' +
                '<span class="rv-stat"><span class="rv-stat-dot grey"></span> Total: ' + total + '</span>' +
                '<span class="rv-stat"><span class="rv-stat-dot green"></span> Available: ' + avail + '</span>' +
                (inuse > 0 ? '<span class="rv-stat"><span class="rv-stat-dot orange"></span> In-Use: ' + inuse + '</span>' : '') +
                '</div>';
        }

        const card = document.createElement('div');
        card.className = 'rv-card';
        card.innerHTML =
            '<div class="rv-card-check"><input type="checkbox" class="row-cb ' + cbClass + '" value="' + escHtml(id) + '" data-name="' + escHtml(d.name || '') + '"></div>' +
            '<div class="rv-card-icon"><span class="material-symbols-outlined">' + iconName + '</span></div>' +
            '<div class="rv-card-info">' + infoHtml + '</div>' +
            '<div class="rv-card-actions">' +
              '<a href="' + BASE_URL + 'resources/show/' + escHtml(id) + '" class="rv-action" title="View"><span class="material-symbols-outlined">visibility</span></a>' +
              '<a href="' + BASE_URL + 'resources/edit/' + escHtml(id) + '" class="rv-action" title="Edit"><span class="material-symbols-outlined">edit</span></a>' +
              '<button type="button" class="rv-action rv-action-delete action-delete" data-id="' + escHtml(id) + '" data-name="' + escHtml(d.name || '') + '" title="Delete"><span class="material-symbols-outlined">delete</span></button>' +
            '</div>';

        // Wire delete
        card.querySelector('.action-delete').addEventListener('click', function () {
            pendingDeleteId = this.dataset.id;
            pendingCard     = card;
            confirmMessage.innerText = 'Delete "' + (this.dataset.name || 'this resource') + '"?';
            confirmDialog.classList.add('open');
        });
        // Wire checkbox
        card.querySelector('.row-cb').addEventListener('change', updateBulkBar);

        card.style.opacity = '0'; card.style.background = '#edf7e6';
        container.appendChild(card);
        requestAnimationFrame(() => {
            card.style.transition = 'opacity 300ms ease, background 1.2s ease';
            card.style.opacity = '1';
            setTimeout(() => { card.style.background = ''; }, 1000);
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

    /* ── Search (client-side filter on visible panel) ── */
    function wireSearch(inputId, cardsId) {
        const inp   = document.getElementById(inputId);
        const cards = document.getElementById(cardsId);
        if (!inp || !cards) return;
        inp.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            cards.querySelectorAll('.rv-card').forEach(card => {
                card.style.display = !q || card.innerText.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }
    wireSearch('searchEquipment', 'equipmentCards');
    wireSearch('searchTools',     'toolsCards');
    wireSearch('searchManpower',  'manpowerCards');

    /* ── Bulk select ── */
    const bulkBar     = document.getElementById('bulkActionBar');
    const bulkCountEl = document.getElementById('bulkCount');
    const bulkDelBtn  = document.getElementById('bulkDeleteBtn');

    function getActivePanel() {
        return document.querySelector('.rv-panel.active');
    }
    function getCheckedInPanel(panel) {
        return panel ? Array.from(panel.querySelectorAll('.row-cb:checked')) : [];
    }
    window.updateBulkBar = function () {
        const panel   = getActivePanel();
        const checked = getCheckedInPanel(panel);
        if (bulkBar)     bulkBar.style.display = checked.length > 0 ? 'flex' : 'none';
        if (bulkCountEl) bulkCountEl.textContent = checked.length + ' selected';
    };

    // Wire select-all for each panel
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

    bulkDelBtn && bulkDelBtn.addEventListener('click', function () {
        const panel   = getActivePanel();
        const checked = getCheckedInPanel(panel);
        if (!checked.length) return;
        const names = checked.map(c => c.dataset.name || c.value).join(', ');
        confirmMessage.innerText = 'Delete ' + checked.length + ' item(s)?\n' + names;
        pendingDeleteId   = '__bulk__';
        pendingBulkItems  = checked.map(c => ({ id: c.value, card: c.closest('.rv-card') }));
        confirmDialog.classList.add('open');
    });

    /* ── Single delete ── */
    let pendingDeleteId  = null;
    let pendingCard      = null;
    let pendingBulkItems = [];

    document.querySelectorAll('.action-delete').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            pendingDeleteId = this.dataset.id;
            pendingCard     = this.closest('.rv-card');
            confirmMessage.innerText = 'Delete "' + (this.dataset.name || 'this resource') + '"?';
            confirmDialog.classList.add('open');
        });
    });

    confirmCancel?.addEventListener('click', function () {
        pendingDeleteId = null; pendingCard = null; pendingBulkItems = [];
        confirmDialog.classList.remove('open');
    });

    confirmOk?.addEventListener('click', function () {
        if (!pendingDeleteId) return;

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
                    .then(d => { if (d && d.success) animateCardOut(item.card); })
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
                    animateCardOut(pendingCard);
                    updateBulkBar();
                } else {
                    showToast(d.message || 'Delete failed', 'error');
                }
            })
            .catch(() => showToast('Error deleting resource', 'error'))
            .finally(() => { pendingDeleteId = null; pendingCard = null; confirmDialog.classList.remove('open'); });
    });

    /* ── Recently Deleted panels ── */
    function wireRdPanel(btnId, panelId) {
        const btn   = document.getElementById(btnId);
        const panel = document.getElementById(panelId);
        if (!btn || !panel) return;
        btn.addEventListener('click', function () {
            const open = panel.style.display !== 'none';
            panel.style.display = open ? 'none' : '';
            const ch = this.querySelector('.rv-rd-chevron');
            if (ch) ch.style.transform = open ? '' : 'rotate(180deg)';
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
            fetch('<?= base_url('trash/restore') ?>', { method:'POST', body:fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        showToast((d.name || 'Item') + ' restored', 'success');
                        animateCardOut(row);
                        if (d.id && d.data) addRestoredCard(d.module, d.id, d.data);
                    } else { showToast(d.message || 'Restore failed', 'error'); }
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
            fetch('<?= base_url('trash/permanent-delete') ?>', { method:'POST', body:fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) { showToast('Permanently deleted', 'success'); animateCardOut(row); }
                    else { showToast(d.message || 'Failed', 'error'); }
                })
                .catch(() => showToast('Failed', 'error'));
        });
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
