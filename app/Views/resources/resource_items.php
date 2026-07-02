<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="customToast" class="custom-toast" aria-live="polite" aria-atomic="true"></div>

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

<meta name="csrf-name" content="<?= csrf_token() ?>">
<meta name="csrf-hash" content="<?= csrf_hash() ?>">

<?php
    $activeFilters           = $activeFilters ?? [];
    $activeCount             = count(array_filter($activeFilters));
    $af                      = $activeFilters;
    $resourceId              = $resource['id'] ?? '';
    $defaultWarehouseAddress = $defaultWarehouseAddress ?? '';
    $savedWarehouseLocations = $savedWarehouseLocations ?? [];
    $warehouseLocations      = $warehouseLocations ?? [];
    $mapsKey                 = $mapsKey ?? '';
?>

<a href="<?= base_url('resources') ?>" class="pz-back-link">
    <span class="material-symbols-outlined">arrow_back</span> Resources
</a>

<div class="pz-page-header">
    <h1><?= esc((string)($resource['name'] ?? 'Resource')) ?> — Units</h1>
    <p>Category: <?= esc((string)($resource['category'] ?? 'N/A')) ?></p>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="pz-alert pz-alert-success">
        <span class="material-symbols-outlined" style="font-size:1rem">check_circle</span>
        <?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="pz-alert pz-alert-error">
        <span class="material-symbols-outlined" style="font-size:1rem">error</span>
        <?= session()->getFlashdata('error') ?>
    </div>
<?php endif; ?>

<div class="page-module">
<div class="pz-toolbar">
    <div class="rv-search-wrap" style="flex:1;">
        <span class="material-symbols-outlined">search</span>
        <input type="text" id="unitSearch" class="rv-search" placeholder="">
    </div>
    <div class="pz-toolbar-right">
        <button type="button" class="pz-btn pz-btn-outline" id="filterToggleBtn">
            <span class="material-symbols-outlined">filter_list</span>
            Filter<?php if ($activeCount > 0): ?><span class="pz-filter-badge"><?= $activeCount ?></span><?php endif; ?>
        </button>
        <button class="pz-btn pz-btn-primary"
                onclick="document.getElementById('addItemModal').style.display='flex'">
            <span class="material-symbols-outlined">add</span> Add Unit
        </button>
        <button type="button" class="pz-btn pz-btn-outline" id="importUnitsBtn">
            <span class="material-symbols-outlined">upload</span> Import
        </button>
        <button type="button" class="pz-btn pz-btn-outline" id="exportUnitsBtn">
            <span class="material-symbols-outlined">download</span> Export
        </button>

        <div class="rv-btn-group-view">
            <a href="<?= base_url('resources/viewItems/' . $resourceId . '/deleted') ?>" class="rv-btn-trash">
                <span class="material-symbols-outlined">restore_from_trash</span> Deleted
            </a>
            <a href="<?= base_url('resources/viewItems/' . $resourceId . '/archived') ?>" class="rv-btn-archive">
                <span class="material-symbols-outlined">inventory_2</span> Archive
            </a>
        </div>
    </div>
</div>

<!-- Bulk Action Bar -->
<div class="bulk-action-bar" id="bulkActionBar" style="display:none">
    <span class="bulk-count" id="bulkCount">0 selected</span>
    <button type="button" class="bulk-archive-btn" id="bulkArchiveBtn">
        <span class="material-symbols-outlined">inventory_2</span> Archive Selected
    </button>
    <button type="button" class="bulk-delete-btn" id="bulkDeleteBtn">
        <span class="material-symbols-outlined">delete</span> Delete Selected
    </button>
</div>

<!-- Filter Panel -->
<div id="filterPanel" class="pz-filter-panel">
    <form id="filterForm" method="get" action="<?= base_url('resources/viewItems/' . $resourceId) ?>">

        <div class="pz-filter-grid" style="margin-bottom:.65rem">
            <div class="pz-filter-group">
                <label>Brand</label>
                <input type="text" name="brand" id="brandInput" value="<?= esc($af['brand'] ?? '') ?>" placeholder="Search brand…">
            </div>
        </div>

        <input type="hidden" id="conditionInput" name="condition" value="<?= esc($af['condition'] ?? '') ?>">
        <div style="margin-bottom:.55rem">
            <p class="pz-filter-group" style="margin-bottom:.3rem"><label>Condition</label></p>
            <div class="pz-chip-row">
                <?php foreach (['New','Functional','Worn','Broken'] as $opt): ?>
                <button type="button" class="pz-chip condition-chip <?= ($af['condition'] ?? '') === $opt ? 'active' : '' ?>" data-val="<?= $opt ?>"><?= $opt ?></button>
                <?php endforeach; ?>
            </div>
        </div>

        <input type="hidden" id="statusInput" name="status" value="<?= esc($af['status'] ?? '') ?>">
        <div style="margin-bottom:.55rem">
            <p class="pz-filter-group" style="margin-bottom:.3rem"><label>Status</label></p>
            <div class="pz-chip-row">
                <?php foreach (['In-Storage','In-Use','Repair'] as $opt): ?>
                <button type="button" class="pz-chip status-chip <?= ($af['status'] ?? '') === $opt ? 'active' : '' ?>" data-val="<?= $opt ?>"><?= $opt ?></button>
                <?php endforeach; ?>
            </div>
        </div>

        <input type="hidden" id="locTypeInput" name="loc_type" value="<?= esc($af['loc_type'] ?? '') ?>">
        <div>
            <p class="pz-filter-group" style="margin-bottom:.3rem"><label>Location</label></p>
            <div class="pz-chip-row">
                <?php foreach (['Warehouse','Project Site'] as $opt): ?>
                <button type="button" class="pz-chip loctype-chip <?= ($af['loc_type'] ?? '') === $opt ? 'active' : '' ?>" data-val="<?= $opt ?>"><?= $opt ?></button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="pz-filter-footer">
            <a href="<?= base_url('resources/viewItems/' . $resourceId) ?>" class="pz-btn pz-btn-ghost pz-btn-sm">
                <span class="material-symbols-outlined">restart_alt</span> Reset
            </a>
        </div>
    </form>
</div>

<!-- Units Table -->
<div class="pager-top">
    <span>Show</span>
    <input type="number" class="pager-per-page" value="<?= $perPage ?? 20 ?>" min="5" max="200" title="Rows per page — press Enter to apply">
    <span>records per page</span>
</div>
<div class="pz-table-wrap">
    <table id="itemsTable" class="pz-table" data-offset="<?= $pagerOffset ?? 0 ?>">
        <thead>
            <tr>
                <th style="width:36px"><input type="checkbox" id="selectAll" title="Select all"></th>
                <th>#</th>
                <th>Serial No.</th>
                <th>Brand</th>
                <th>Condition</th>
                <th>Status</th>
                <th>Location</th>
                <th>Availability</th>
                <th class="center">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $pagerOffset = $pagerOffset ?? 0; ?>
            <?php if (empty($items)): ?>
                <tr><td colspan="9" class="pz-table-empty">No units found.</td></tr>
            <?php else: ?>
                <?php foreach ($items as $index => $item): ?>
                <?php
                    $isLocked   = (bool)($item['transfer_locked'] ?? false);
                    $rawRel     = $item['released_at'] ?? null;
                    $releasedBy = (string)($item['released_by'] ?? '');
                    $releasedDate = '';
                    if ($rawRel !== null) {
                        if ($rawRel instanceof \Google\Cloud\Core\Timestamp) {
                            $releasedDate = date('m/d/Y', $rawRel->get()->getTimestamp());
                        } elseif (is_numeric($rawRel)) {
                            $releasedDate = date('m/d/Y', (int)$rawRel);
                        }
                    }
                    $isReleased = $rawRel !== null && !$isLocked;
                ?>
                <tr data-id="<?= esc($item['id'] ?? '') ?>"
                    data-condition="<?= esc(strtolower($item['condition'] ?? '')) ?>"
                    data-item-status="<?= esc(strtolower($item['status'] ?? '')) ?>"
                    data-loc-type="<?= esc(strtolower($item['location']['type'] ?? '')) ?>"
                    data-brand="<?= esc(strtolower($item['brand_name'] ?? $item['brand'] ?? '')) ?>">
                    <td><input type="checkbox" class="row-check" value="<?= esc($item['id'] ?? '') ?>"></td>
                    <td class="pz-row-num"><?= $pagerOffset + $index + 1 ?></td>
                    <td><?= esc((string)($item['serial_number'] ?? 'N/A')) ?></td>
                    <td class="pz-row-muted"><?= esc((string)($item['brand_name'] ?? $item['brand'] ?? '—')) ?></td>
                    <td>
                        <?php $cond = strtolower($item['condition'] ?? ''); $cbc = $cond === 'new' ? 'pz-badge-approved' : ($cond === 'functional' ? 'pz-badge-available' : ($cond === 'worn' ? 'pz-badge-deployed' : 'pz-badge-rejected')); ?>
                        <span class="pz-badge <?= $cbc ?>"><?= esc((string)($item['condition'] ?? 'N/A')) ?></span>
                    </td>
                    <td>
                        <?php $stt = strtolower(str_replace(['-',' '],'', $item['status'] ?? '')); $stbc = $stt === 'instorage' ? 'pz-badge-available' : ($stt === 'inuse' ? 'pz-badge-deployed' : ($stt === 'transferpending' ? 'pz-badge-pending' : 'pz-badge-maintenance')); ?>
                        <span class="pz-badge <?= $stbc ?>"><?= esc((string)($item['status'] ?? 'N/A')) ?></span>
                    </td>
                    <td class="pz-row-muted">
                        <?php
                            $assignedTo  = (string)($item['assigned_to'] ?? '');
                            $locStatus   = strtolower(str_replace(['-',' '], '', $item['status'] ?? ''));
                            if ($assignedTo !== '' && in_array($locStatus, ['inuse','transferpending'], true)):
                        ?>
                            <?= esc($assignedTo) ?>
                        <?php else:
                            $locStr = trim((string)($item['location']['type'] ?? '') . (($item['location']['address'] ?? '') !== '' ? ' — ' . ($item['location']['address'] ?? '') : ''));
                        ?>
                            <?= esc($locStr) ?: '—' ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($isLocked): ?>
                            <span class="pz-badge pz-badge-pending"
                                  title="In transit to: <?= esc((string)($item['transfer_destination_site_name'] ?? 'destination')) ?>">
                                <span class="material-symbols-outlined" style="font-size:.9rem;vertical-align:middle">lock</span>
                                In Transfer
                            </span>
                        <?php elseif ($isReleased): ?>
                            <div>
                                <span class="pz-badge pz-badge-approved">
                                    <span class="material-symbols-outlined" style="font-size:.9rem;vertical-align:middle">check_circle</span>
                                    Available
                                </span>
                                <?php if ($releasedBy || $releasedDate): ?>
                                <div style="font-size:.73rem;color:#555;margin-top:.25rem;line-height:1.3">
                                    Released by <?= esc($releasedBy) ?><?= $releasedDate ? '<br>' . $releasedDate : '' ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span style="color:#bbb">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <div class="action-wrap">
                            <button class="action-btn view-unit-btn" title="View"
                                data-serial="<?= esc($item['serial_number'] ?? '') ?>"
                                data-brand="<?= esc($item['brand_name'] ?? $item['brand'] ?? '') ?>"
                                data-condition="<?= esc($item['condition'] ?? '') ?>"
                                data-status="<?= esc($item['status'] ?? '') ?>"
                                data-loc-type="<?= esc($item['location']['type'] ?? '') ?>"
                                data-loc-addr="<?= esc($item['location']['address'] ?? '') ?>">
                                <span class="material-symbols-outlined">visibility</span>
                            </button>
                            <button class="action-btn edit-unit-btn" title="Edit"
                                data-id="<?= esc($item['id'] ?? '') ?>"
                                data-serial="<?= esc($item['serial_number'] ?? '') ?>"
                                data-brand="<?= esc($item['brand_name'] ?? $item['brand'] ?? '') ?>"
                                data-condition="<?= esc($item['condition'] ?? '') ?>"
                                data-status="<?= esc($item['status'] ?? '') ?>"
                                data-loc-type="<?= esc($item['location']['type'] ?? '') ?>"
                                data-loc-addr="<?= esc($item['location']['address'] ?? '') ?>">
                                <span class="material-symbols-outlined">edit</span>
                            </button>
                            <button class="action-btn action-archive archive-unit-btn" title="Archive"
                                data-id="<?= esc($item['id'] ?? '') ?>"
                                data-serial="<?= esc($item['serial_number'] ?? $item['id'] ?? 'this unit') ?>">
                                <span class="material-symbols-outlined">inventory_2</span>
                            </button>
                            <button class="action-btn action-delete delete-unit-btn" title="Delete"
                                data-id="<?= esc($item['id'] ?? '') ?>"
                                data-serial="<?= esc($item['serial_number'] ?? $item['id'] ?? 'this unit') ?>">
                                <span class="material-symbols-outlined">delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<div class="pager-wrapper">
    <?php
        $showingFrom = $itemsTotal > 0 ? $pagerOffset + 1 : 0;
        $showingTo   = min($pagerOffset + $perPage, $itemsTotal);
    ?>
    <span class="pager-info">Showing <?= $showingFrom ?>–<?= $showingTo ?> of <?= $itemsTotal ?> records</span>
    <?= service('pager')->links('items', 'bootstrap_full') ?>
    <?php if ($itemsTotalPages > 1): ?>
    <div class="pager-jump">
        Go to page
        <input type="number" class="pager-jump-input" min="1" max="<?= $itemsTotalPages ?>"
               data-total-pages="<?= $itemsTotalPages ?>" data-page-param="page" placeholder="—">
        <span>of <?= $itemsTotalPages ?></span>
    </div>
    <?php endif; ?>
</div>
</div><!-- /.page-module -->

<!-- View Unit Modal -->
<div id="viewUnitModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:1.6rem 1.5rem 1.3rem;width:100%;max-width:480px;position:relative;box-shadow:0 8px 40px rgba(0,0,0,.18);border:1.5px solid #e2d9c0;">
        <button onclick="document.getElementById('viewUnitModal').style.display='none'" style="position:absolute;top:.75rem;right:1rem;background:none;border:none;font-size:1.2rem;cursor:pointer;color:#7a5c1e;line-height:1">✕</button>
        <p style="font-weight:700;font-size:1rem;color:#3b2a0e;margin:0 0 1rem">Unit Details</p>
        <div class="pz-info-grid" style="gap:.5rem 1.2rem">
            <span class="pz-info-label">Serial No.</span><span class="pz-info-value" id="vu-serial">—</span>
            <span class="pz-info-label">Brand</span><span class="pz-info-value" id="vu-brand">—</span>
            <span class="pz-info-label">Condition</span><span class="pz-info-value" id="vu-condition">—</span>
            <span class="pz-info-label">Status</span><span class="pz-info-value" id="vu-status">—</span>
            <span class="pz-info-label">Location Type</span><span class="pz-info-value" id="vu-loctype">—</span>
            <span class="pz-info-label">Address</span><span class="pz-info-value" id="vu-locaddr">—</span>
        </div>
    </div>
</div>

<!-- Export Units Modal -->
<div id="unitsExportModal" class="pz-modal-overlay" style="display:none" hidden>
  <div class="pz-modal-box">
    <h3 class="pz-modal-title">Export <?= esc($resource['name'] ?? 'Units') ?></h3>
    <p class="pz-modal-subtitle">Choose a format to download:</p>
    <?php
    $exportBase = base_url('export?module=resource_units&resource_id=' . esc($resourceId) . '&resource_name=' . urlencode($resource['name'] ?? 'Resource') . '&format=');
    ?>
    <div class="pz-modal-export-list">
      <a href="<?= $exportBase ?>xlsx" target="_blank" class="pz-export-link pz-export-link--xlsx">
        <span class="pz-export-link-icon">📊</span> Download XLSX
      </a>
      <a href="<?= $exportBase ?>csv" target="_blank" class="pz-export-link pz-export-link--csv">
        <span class="pz-export-link-icon">📋</span> Download CSV
      </a>
      <a href="<?= $exportBase ?>pdf" target="_blank" class="pz-export-link pz-export-link--pdf">
        <span class="pz-export-link-icon">📄</span> Download PDF
      </a>
      <a href="<?= $exportBase ?>print" target="_blank" class="pz-export-link pz-export-link--print">
        <span class="pz-export-link-icon">🖨️</span> Open Printable View
      </a>
    </div>
    <div class="pz-modal-close-row">
      <button class="pz-btn-modal-cancel">Close</button>
    </div>
  </div>
</div>

<!-- Import Units Modal -->
<div id="importUnitsModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:1.6rem 1.5rem 1.3rem;width:100%;max-width:420px;position:relative;box-shadow:0 8px 40px rgba(0,0,0,.18);border:1.5px solid #e2d9c0;">
        <button onclick="document.getElementById('importUnitsModal').style.display='none'" style="position:absolute;top:.75rem;right:1rem;background:none;border:none;font-size:1.2rem;cursor:pointer;color:#7a5c1e;line-height:1">✕</button>
        <p style="font-weight:700;font-size:.95rem;color:#3b2a0e;margin:0 0 .25rem">Import Units</p>
        <p style="font-size:.82rem;color:#666;margin:0 0 1rem">
            Upload .xlsx or .csv.
            <a id="unitsTplLink" href="<?= base_url('import/template?module=resource_units') ?>" style="color:#8C701C;font-weight:600;">Download template</a>
        </p>

        <div id="unitsDropZone" style="border:2px dashed #d4c185;border-radius:8px;padding:1.4rem;text-align:center;cursor:pointer;color:#888;font-size:.85rem;transition:border-color .2s;">
            <div style="font-size:1.8rem;margin-bottom:.4rem;">📂</div>
            Click or drag &amp; drop .xlsx / .csv here
            <input type="file" id="unitsFileInput" accept=".xlsx,.xls,.csv" style="display:none;">
        </div>
        <div id="unitsFileName" style="margin-top:.5rem;font-size:.8rem;color:#555;"></div>
        <div id="unitsResult"   style="margin-top:.6rem;font-size:.83rem;"></div>

        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1.1rem;padding-top:.9rem;border-top:1px solid #e8dfc7;">
            <button onclick="document.getElementById('importUnitsModal').style.display='none'" class="pz-btn pz-btn-cancel" style="min-width:80px;">Cancel</button>
            <button id="unitsImportBtn" class="pz-btn pz-btn-primary" style="min-width:80px;"><span class="material-symbols-outlined">upload</span> Import</button>
        </div>
    </div>
</div>

<!-- Delete Unit Confirmation Modal -->
<div id="deleteItemModal" class="confirm-dialog" role="dialog" aria-modal="true">
    <div class="confirm-backdrop" onclick="document.getElementById('deleteItemModal').style.display='none'"></div>
    <div class="confirm-box">
        <div class="confirm-hdr">
            <span class="material-symbols-outlined confirm-hdr-icon">delete_forever</span>
            <p class="confirm-hdr-title">Delete Unit</p>
        </div>
        <div class="confirm-content">
            <p>Are you sure you want to delete unit <strong id="deleteUnitLabel"></strong>? This cannot be undone.</p>
        </div>
        <form id="deleteItemForm" method="POST" action="">
            <?= csrf_field() ?>
            <div class="confirm-actions">
                <button type="submit" class="confirm-btn confirm-btn-action"><span class="material-symbols-outlined" style="font-size:1.1rem">delete_forever</span> Delete</button>
                <button type="button" class="confirm-btn confirm-btn-cancel" onclick="document.getElementById('deleteItemModal').classList.remove('open')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Unit Modal -->
<div id="editItemModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fdf8ee;border-radius:14px;padding:1.5rem;width:100%;max-width:420px;position:relative;border:1.5px solid #e8dfc7;box-shadow:0 8px 40px rgba(0,0,0,.18)">
        <button type="button" onclick="document.getElementById('editItemModal').style.display='none'" style="position:absolute;top:.75rem;right:1rem;background:none;border:none;font-size:1.2rem;cursor:pointer;color:#7a5c1e;line-height:1">✕</button>
        <p style="font-weight:700;font-size:.95rem;color:#3b2a0e;margin:0 0 1.1rem">Edit Unit</p>

        <form id="editItemForm" method="POST" action="">
            <?= csrf_field() ?>
            <div style="display:flex;flex-direction:column;gap:.65rem">
                <div class="pz-form-group">
                    <label>Serial Number *</label>
                    <input type="text" id="editSerial" name="serial_number" placeholder="SN-12345" required>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem">
                    <div class="pz-form-group">
                        <label>Brand</label>
                        <input type="text" id="editBrand" name="brand" placeholder="e.g. Makita">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem">
                    <div class="pz-form-group">
                        <label>Condition *</label>
                        <select id="editCondition" name="condition" required>
                            <option value="">— Select —</option>
                            <option>New</option><option>Functional</option><option>Worn</option><option>Broken</option>
                        </select>
                    </div>
                    <div class="pz-form-group">
                        <label>Status *</label>
                        <select id="editStatus" name="status" required>
                            <option value="">— Select —</option>
                            <option>In-Storage</option><option>In-Use</option><option>Repair</option>
                        </select>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem">
                    <div class="pz-form-group">
                        <label>Location Type</label>
                        <select id="editLocType" name="location_type">
                            <option value="">— Select —</option>
                            <option>Warehouse</option><option>Project Site</option>
                        </select>
                    </div>
                    <div class="pz-form-group">
                        <label>Location Address</label>
                        <input type="text" id="editLocAddr" name="location_address" placeholder="Address">
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:.55rem;margin-top:1.1rem;padding-top:.9rem;border-top:1px solid #e8dfc7">
                <button type="submit" class="pz-btn pz-btn-primary" style="flex:1"><span class="material-symbols-outlined">save</span> Save Changes</button>
                <button type="button" class="pz-btn pz-btn-cancel" style="flex:1" onclick="document.getElementById('editItemModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Unit Modal -->
<div id="addItemModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:1.5rem;width:100%;max-width:600px;position:relative;border:1.5px solid #e8dfc7;box-shadow:0 8px 40px rgba(0,0,0,.18)">
        <button type="button" id="addModalClose" style="position:absolute;top:.75rem;right:1rem;background:none;border:none;font-size:1.2rem;cursor:pointer;color:#7a5c1e;line-height:1">✕</button>
        <p style="font-weight:700;font-size:.95rem;color:#3b2a0e;margin:0 0 1.1rem">Add New Unit</p>

        <form method="POST" action="<?= base_url('resources/addItem/' . $resourceId) ?>" id="addUnitForm">
            <?= csrf_field() ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                <div class="pz-form-group">
                    <label>Serial Number</label>
                    <input type="text" name="serial_number" id="addSerial" placeholder="SN-12345">
                </div>
                <div class="pz-form-group">
                    <label>Brand</label>
                    <input type="text" name="brand" id="addBrand" placeholder="e.g. Caterpillar">
                </div>
                <div class="pz-form-group">
                    <label>Condition</label>
                    <select name="condition" id="addCondition">
                        <option value="">Select</option>
                        <option value="New" selected>New</option>
                        <option value="Functional">Functional</option>
                        <option value="Worn">Worn</option>
                        <option value="Broken">Broken</option>
                    </select>
                </div>
                <div class="pz-form-group">
                    <label>Status *</label>
                    <select name="status" id="addStatus" required>
                        <option value="">— Select —</option>
                        <option value="In-Storage" selected>In-Storage</option>
                        <option value="In-Use">In-Use</option>
                        <option value="Repair">Repair</option>
                    </select>
                </div>
                <div class="pz-form-group">
                    <label>Location Type</label>
                    <select name="location_type" id="addLocType">
                        <option value="">— Select —</option>
                        <option value="Warehouse" selected>Warehouse</option>
                        <option value="Project Site">Project Site</option>
                    </select>
                </div>
            </div>
            <div class="pz-form-group" style="margin-top:.75rem">
                <label>Current Address</label>
                <div class="unit-warehouse-input-wrap" style="display:flex;gap:.35rem;align-items:stretch">
                    <input type="text" name="location_address" id="addLocAddr"
                           placeholder="Type warehouse location or pick on map" style="flex:1"
                           value="<?= esc($defaultWarehouseAddress) ?>">
                    <button type="button" id="addUnitMapBtn"
                            style="padding:0 .9rem;border:1.5px solid #d4c185;border-radius:7px;background:#fff;color:#5a3e10;font-size:.85rem;font-weight:600;cursor:pointer;white-space:nowrap">
                        Map
                    </button>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;align-items:center;gap:.6rem;margin-top:.85rem">
                <span style="font-size:.8rem;color:#7a6030;font-weight:700;text-transform:uppercase;letter-spacing:.05em">Qty</span>
                <div class="qty-stepper">
                    <button type="button" id="addQtyMinus" class="qty-btn">&#8722;</button>
                    <input type="number" name="quantity" id="addQty" class="qty-input" value="1" min="1" style="width:3.2rem;text-align:center">
                    <button type="button" id="addQtyPlus" class="qty-btn">+</button>
                </div>
            </div>
            <div style="display:flex;gap:.55rem;margin-top:1.1rem;padding-top:.9rem;border-top:1px solid #e8dfc7">
                <button type="submit" class="pz-btn pz-btn-primary" style="flex:1"><span class="material-symbols-outlined">add</span> Add Unit</button>
                <button type="button" class="pz-btn pz-btn-cancel" style="flex:1" id="addModalCancelBtn">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Unit Map Modal -->
<div id="addUnitMapModal" class="equipment-map-modal" aria-hidden="true">
    <div class="equipment-map-backdrop"></div>
    <div class="equipment-map-dialog">
        <div class="equipment-map-header">
            <div>
                <h2>Select Location</h2>
                <p>Click on the map or search an address.</p>
            </div>
            <button type="button" class="equipment-map-close" id="addUnitMapClose">×</button>
        </div>
        <input type="text" id="addUnitMapSearch" class="equipment-map-search" placeholder="Search a location or type an address">
        <div id="addUnitMapCanvas" class="equipment-map-canvas"></div>
        <div class="equipment-map-footer">
            <button type="button" class="btn btn-secondary" id="addUnitMapUse">Use This Address</button>
            <button type="button" class="btn btn-cancel-mobile" id="addUnitMapCancel">Cancel</button>
        </div>
    </div>
</div>

<script>
var _auMap = null, _auMarker = null, _auAuto = null;
var _auPendingAddress = '';
var _auDefaultAddress = <?= json_encode($defaultWarehouseAddress) ?>;
var _auResourceName   = <?= json_encode((string)($resource['name'] ?? '')) ?>;
var _auNextSerial     = <?= count($items ?? []) + 1 ?>;
window.whSavedLocations = <?= json_encode($savedWarehouseLocations) ?>;
window.whOtherLocations = <?= json_encode(array_column($warehouseLocations, 'value')) ?>;
window.whSaveUrl        = '<?= base_url('resources/warehouse-saved-location') ?>';
window.whDeleteUrl      = '<?= base_url('resources/warehouse-saved-location-delete') ?>';
window.initAddUnitMap = function () {};

function auGeneratePrefix(name) {
    var words = name.toUpperCase().replace(/[^A-Z0-9 ]/g, ' ').trim().split(/\s+/).filter(Boolean);
    if (words.length === 0) return '';
    if (words.length === 1) return words[0].substring(0, 3);
    if (words.length === 2) return words[0].substring(0, 3) + '_' + words[1];
    return words[0].substring(0, 3) + '_' + words[1] + words[2];
}
function auGenerateSerial(name, idx) {
    var prefix = auGeneratePrefix(name);
    if (!prefix) return '';
    return prefix + '-' + String(idx).padStart(3, '0');
}

document.addEventListener('DOMContentLoaded', function () {
    // ── Add Unit Modal reset & map ───────────────────────────────────────────
    const addModal       = document.getElementById('addItemModal');
    const addForm        = document.getElementById('addUnitForm');
    const addLocAddr     = document.getElementById('addLocAddr');
    const addModalClose  = document.getElementById('addModalClose');
    const addCancelBtn   = document.getElementById('addModalCancelBtn');
    const addUnitMapBtn  = document.getElementById('addUnitMapBtn');
    const addUnitMapModal  = document.getElementById('addUnitMapModal');
    const addUnitMapClose  = document.getElementById('addUnitMapClose');
    const addUnitMapCancel = document.getElementById('addUnitMapCancel');
    const addUnitMapUse    = document.getElementById('addUnitMapUse');
    const addUnitMapSearch = document.getElementById('addUnitMapSearch');

    function closeAddModal() { addModal.style.display = 'none'; }
    function closeAddMapModal() {
        addUnitMapModal.classList.remove('open');
        addUnitMapModal.setAttribute('aria-hidden', 'true');
    }
    function openAddMapModal() {
        _auPendingAddress = (addLocAddr && addLocAddr.value.trim()) || '';
        addUnitMapSearch.value = _auPendingAddress;
        addUnitMapModal.classList.add('open');
        addUnitMapModal.setAttribute('aria-hidden', 'false');
        setTimeout(function () { ensureAddUnitMap(); if (_auMap) google.maps.event.trigger(_auMap, 'resize'); }, 50);
    }

    var addQtyEl    = document.getElementById('addQty');
    var addQtyMinus = document.getElementById('addQtyMinus');
    var addQtyPlus  = document.getElementById('addQtyPlus');

    function getQty() { return Math.max(1, parseInt((addQtyEl && addQtyEl.value) || 1, 10) || 1); }

    function refreshAutoSerial() {
        var serialEl = document.getElementById('addSerial');
        if (serialEl && serialEl.dataset.autoSerial === 'true') {
            var auto = auGenerateSerial(_auResourceName, _auNextSerial);
            serialEl.value = auto;
        }
    }

    function resetAddForm() {
        if (addForm) addForm.reset();
        document.getElementById('addCondition').value = 'New';
        document.getElementById('addStatus').value    = 'In-Storage';
        document.getElementById('addLocType').value   = 'Warehouse';
        if (addLocAddr) addLocAddr.value = _auDefaultAddress;
        if (addQtyEl)   addQtyEl.value   = '1';
        var serialEl = document.getElementById('addSerial');
        if (serialEl) {
            var auto = auGenerateSerial(_auResourceName, _auNextSerial);
            serialEl.value = auto;
            serialEl.dataset.autoSerial = auto ? 'true' : '';
        }
    }

    // Qty stepper
    addQtyMinus && addQtyMinus.addEventListener('click', function () {
        var v = getQty(); if (v > 1) { addQtyEl.value = v - 1; refreshAutoSerial(); }
    });
    addQtyPlus && addQtyPlus.addEventListener('click', function () {
        addQtyEl.value = getQty() + 1; refreshAutoSerial();
    });
    addQtyEl && addQtyEl.addEventListener('change', function () {
        var v = parseInt(this.value, 10); if (isNaN(v) || v < 1) this.value = 1;
        refreshAutoSerial();
    });

    // Clear auto-serial flag if user edits the field manually
    var addSerialEl = document.getElementById('addSerial');
    if (addSerialEl) {
        addSerialEl.addEventListener('input', function () { this.dataset.autoSerial = ''; });
    }

    // Bump serial index by qty on form submit so re-opens get the next number
    if (addForm) {
        addForm.addEventListener('submit', function () { _auNextSerial += getQty(); });
    }

    // Init warehouse picker on the address input
    if (addLocAddr && typeof window.initWarehousePicker === 'function') {
        window.initWarehousePicker(addLocAddr, null);
    }

    // Open via toolbar button — reset defaults each time
    document.querySelector('[onclick*="addItemModal"]') && (() => {
        const btn = document.querySelector('[onclick*="addItemModal"]');
        btn.removeAttribute('onclick');
        btn.addEventListener('click', function () { resetAddForm(); addModal.style.display = 'flex'; });
    })();

    addModalClose  && addModalClose.addEventListener('click',  closeAddModal);
    addCancelBtn   && addCancelBtn.addEventListener('click',   closeAddModal);
    addModal       && addModal.addEventListener('click', function (e) { if (e.target === addModal) closeAddModal(); });

    addUnitMapBtn    && addUnitMapBtn.addEventListener('click',    openAddMapModal);
    addUnitMapClose  && addUnitMapClose.addEventListener('click',  closeAddMapModal);
    addUnitMapCancel && addUnitMapCancel.addEventListener('click', closeAddMapModal);
    addUnitMapModal.querySelector('.equipment-map-backdrop') &&
        addUnitMapModal.querySelector('.equipment-map-backdrop').addEventListener('click', closeAddMapModal);

    addUnitMapUse && addUnitMapUse.addEventListener('click', function () {
        if (addLocAddr) addLocAddr.value = _auPendingAddress || addLocAddr.value;
        closeAddMapModal();
    });

    addUnitMapSearch && addUnitMapSearch.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); if (_auMap && this.value.trim()) auServerGeocode(this.value.trim()); }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && addUnitMapModal.classList.contains('open')) closeAddMapModal();
    });

    function ensureAddUnitMap() {
        if (_auMap || typeof google === 'undefined') return;
        _auMap = new google.maps.Map(document.getElementById('addUnitMapCanvas'), {
            center: { lat: 14.5995, lng: 120.9842 }, zoom: 11,
            mapTypeControl: false, streetViewControl: false, fullscreenControl: false,
        });
        _auMarker = new google.maps.Marker({ map: null, draggable: false, title: 'Selected location' });
        _auMap.addListener('click', function (event) {
            auPlaceMarker(event.latLng);
            auReverseGeocode(event.latLng.lat(), event.latLng.lng());
        });
        _auAuto = new google.maps.places.Autocomplete(addUnitMapSearch, {
            types: ['geocode', 'establishment'], componentRestrictions: { country: 'ph' },
        });
        _auAuto.bindTo('bounds', _auMap);
        _auAuto.addListener('place_changed', function () {
            var place = _auAuto.getPlace();
            if (!place.geometry || !place.geometry.location) { auServerGeocode(addUnitMapSearch.value); return; }
            var latLng = { lat: place.geometry.location.lat(), lng: place.geometry.location.lng() };
            _auMap.setCenter(latLng); _auMap.setZoom(15); auPlaceMarker(latLng);
            _auPendingAddress = place.formatted_address || addUnitMapSearch.value;
            addUnitMapSearch.value = _auPendingAddress;
        });
    }

    function auPlaceMarker(latLng) {
        _auMarker.setAnimation(null); _auMarker.setPosition(latLng); _auMarker.setMap(_auMap);
        setTimeout(function () { _auMarker.setAnimation(google.maps.Animation.DROP); }, 10);
    }

    function auReverseGeocode(lat, lng) {
        fetch('<?= base_url('maps/reverse') ?>?lat=' + lat + '&lng=' + lng)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status === 'OK') { _auPendingAddress = data.formatted_address; addUnitMapSearch.value = data.formatted_address; }
            }).catch(function () {});
    }

    function auServerGeocode(address) {
        if (!address.trim()) return;
        fetch('<?= base_url('maps/geocode') ?>?address=' + encodeURIComponent(address))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status === 'OK') {
                    var latLng = { lat: data.lat, lng: data.lng };
                    _auMap.setCenter(latLng); _auMap.setZoom(15); auPlaceMarker(latLng);
                    _auPendingAddress = data.formatted_address; addUnitMapSearch.value = data.formatted_address;
                }
            }).catch(function () {});
    }

    // ── Edit Unit Modal ──────────────────────────────────────────────────────
    const editModal = document.getElementById('editItemModal');
    const editForm  = document.getElementById('editItemForm');
    const baseUpdateUrl = '<?= base_url('resources/updateItem/' . $resourceId) ?>/';

    document.querySelectorAll('.edit-unit-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const d = this.dataset;
            editForm.action = baseUpdateUrl + encodeURIComponent(d.id);
            document.getElementById('editSerial').value    = d.serial;
            document.getElementById('editBrand').value     = d.brand;
            document.getElementById('editCondition').value = d.condition;
            document.getElementById('editStatus').value    = d.status;
            document.getElementById('editLocType').value   = d.locType || '';
            document.getElementById('editLocAddr').value   = d.locAddr || '';
            editModal.style.display = 'flex';
        });
    });

    // Close edit modal when clicking the backdrop
    editModal.addEventListener('click', function (e) {
        if (e.target === editModal) editModal.style.display = 'none';
    });

    // ── Promise-based custom confirm ─────────────────────────────────────────
    const confirmDialog  = document.getElementById('confirmDialog');
    const confirmMessage = document.getElementById('confirmMessage');
    const confirmOk      = document.getElementById('confirmOk');
    const confirmCancel  = document.getElementById('confirmCancel');
    let _confirmResolve  = null;
    function openConfirm(message, type = 'delete') {
        if (_confirmResolve) return Promise.resolve(false);
        return new Promise(resolve => {
            _confirmResolve = resolve;
            confirmMessage.innerText = message;
            const isArchive = type === 'archive';
            confirmDialog.classList.toggle('confirm-archive', isArchive);
            var icon  = document.getElementById('confirmIcon');
            var title = document.getElementById('confirmTitle');
            if (icon)  icon.textContent  = isArchive ? 'inventory_2' : 'delete_forever';
            if (title) title.textContent = isArchive ? 'Archive' : 'Delete';
            confirmDialog.classList.add('open');
        });
    }
    confirmOk?.addEventListener('click', function () {
        if (!_confirmResolve) return;
        const r = _confirmResolve; _confirmResolve = null;
        confirmDialog.classList.remove('open'); r(true);
    });
    confirmCancel?.addEventListener('click', function () {
        if (!_confirmResolve) return;
        const r = _confirmResolve; _confirmResolve = null;
        confirmDialog.classList.remove('open'); r(false);
    });

    // ── Row animation + renumber helpers ────────────────────────────────────
    var itemsTable = document.getElementById('itemsTable');

    function animateRowOut(row, cb) {
        if (!row) { if (cb) cb(); return; }
        var h = row.offsetHeight;
        row.style.height = h + 'px';
        row.style.overflow = 'hidden';
        requestAnimationFrame(function () {
            row.style.transition = 'height 260ms ease, opacity 260ms ease, transform 260ms ease';
            row.style.height  = '0';
            row.style.opacity = '0';
            row.style.transform = 'translateX(-8px)';
        });
        setTimeout(function () { row.remove(); if (cb) cb(); }, 280);
    }

    function renumberTable() {
        if (!itemsTable) return;
        var offset = parseInt(itemsTable.dataset.offset || '0');
        var num = offset + 1;
        itemsTable.querySelectorAll('tbody tr').forEach(function (row) {
            if (row.cells.length < 3) return;
            row.cells[1].textContent = num++;
        });
    }

    // ── Delete Unit Modal ────────────────────────────────────────────────────
    const deleteModal     = document.getElementById('deleteItemModal');
    const deleteForm      = document.getElementById('deleteItemForm');
    const deleteUnitLabel = document.getElementById('deleteUnitLabel');
    const baseDeleteUrl   = '<?= base_url('resources/deleteItem/' . $resourceId) ?>/';

    document.querySelectorAll('.delete-unit-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id     = this.dataset.id;
            const serial = this.dataset.serial || id;
            deleteForm.action      = baseDeleteUrl + encodeURIComponent(id);
            deleteUnitLabel.textContent = serial;
            deleteModal.classList.add('open');
        });
    });

    deleteModal.querySelector('.confirm-backdrop')?.addEventListener('click', function () {
        deleteModal.classList.remove('open');
    });

    // ── View unit modal ──────────────────────────────────────────────────────
    var viewModal = document.getElementById('viewUnitModal');
    document.querySelectorAll('.view-unit-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('vu-serial').textContent    = this.dataset.serial || '—';
            document.getElementById('vu-brand').textContent     = this.dataset.brand || '—';
            document.getElementById('vu-condition').textContent = this.dataset.condition || '—';
            document.getElementById('vu-status').textContent    = this.dataset.status || '—';
            document.getElementById('vu-loctype').textContent   = this.dataset.locType || '—';
            document.getElementById('vu-locaddr').textContent   = this.dataset.locAddr || '—';
            viewModal.style.display = 'flex';
        });
    });
    viewModal && viewModal.addEventListener('click', function (e) { if (e.target === viewModal) viewModal.style.display = 'none'; });

    // ── Archive unit ─────────────────────────────────────────────────────────
    var baseArchiveUrl = '<?= base_url('resources/archiveItem/' . $resourceId) ?>/';
    var csrfName = document.querySelector('meta[name="csrf-name"]')?.content;
    var csrfHash = document.querySelector('meta[name="csrf-hash"]')?.content;

    document.querySelectorAll('.archive-unit-btn').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            var id = this.dataset.id, serial = this.dataset.serial || id;
            if (!await openConfirm('Archive unit "' + serial + '"? It can be restored from the archive.', 'archive')) return;
            var fd = new FormData();
            if (csrfName && csrfHash) fd.append(csrfName, csrfHash);
            fetch(baseArchiveUrl + encodeURIComponent(id), { method: 'POST', body: fd })
                .then(r => r.json())
                .then(function (d) {
                    if (d.success) {
                        animateRowOut(btn.closest('tr'), renumberTable);
                        showToast('Unit archived', 'success');
                    } else showToast(d.message || 'Archive failed', 'error');
                }).catch(function () { showToast('Archive failed', 'error'); });
        });
    });

    // ── Select all + bulk actions ─────────────────────────────────────────────
    var selectAll   = document.getElementById('selectAll');
    var bulkBar     = document.getElementById('bulkActionBar');
    var bulkCount   = document.getElementById('bulkCount');

    function getCheckedIds() {
        return Array.from(document.querySelectorAll('.row-check:checked')).map(c => c.value);
    }
    function updateBulkBar() {
        var ids = getCheckedIds();
        bulkBar.style.display = ids.length > 0 ? 'flex' : 'none';
        bulkCount.textContent = ids.length + ' selected';
        if (selectAll) selectAll.indeterminate = ids.length > 0 && ids.length < document.querySelectorAll('.row-check').length;
        if (selectAll) selectAll.checked = ids.length > 0 && ids.length === document.querySelectorAll('.row-check').length;
    }
    document.querySelectorAll('.row-check').forEach(c => c.addEventListener('change', updateBulkBar));
    if (selectAll) selectAll.addEventListener('change', function () {
        document.querySelectorAll('.row-check').forEach(c => { c.checked = this.checked; });
        updateBulkBar();
    });

    var bulkArchBtn = document.getElementById('bulkArchiveBtn');
    var bulkDelBtn  = document.getElementById('bulkDeleteBtn');

    bulkArchBtn && bulkArchBtn.addEventListener('click', async function () {
        var ids = getCheckedIds();
        if (!ids.length || !await openConfirm('Archive ' + ids.length + ' unit(s)? They can be restored from the archive.', 'archive')) return;
        var fd = new FormData(); ids.forEach(id => fd.append('ids[]', id));
        if (csrfName && csrfHash) fd.append(csrfName, csrfHash);
        fetch('<?= base_url('resources/bulkArchiveItems/' . $resourceId) ?>', { method: 'POST', body: fd })
            .then(r => r.json()).then(function (d) {
                if (d.success) {
                    ids.forEach(id => { animateRowOut(document.querySelector('.row-check[value="' + id + '"]')?.closest('tr')); });
                    setTimeout(renumberTable, 290);
                    showToast(d.archived + ' unit(s) archived', 'success');
                    updateBulkBar();
                }
            });
    });

    bulkDelBtn && bulkDelBtn.addEventListener('click', async function () {
        var ids = getCheckedIds();
        if (!ids.length || !await openConfirm('Permanently delete ' + ids.length + ' unit(s)? This cannot be undone.')) return;
        var fd = new FormData(); ids.forEach(id => fd.append('ids[]', id));
        if (csrfName && csrfHash) fd.append(csrfName, csrfHash);
        fetch('<?= base_url('resources/bulkDeleteItems/' . $resourceId) ?>', { method: 'POST', body: fd })
            .then(r => r.json()).then(function (d) {
                if (d.success) {
                    ids.forEach(id => { animateRowOut(document.querySelector('.row-check[value="' + id + '"]')?.closest('tr')); });
                    setTimeout(renumberTable, 290);
                    showToast(d.deleted + ' unit(s) deleted', 'success');
                    updateBulkBar();
                }
            });
    });

    // ── Live filter (search + chips + brand) ─────────────────────────────────
    const unitSearch  = document.getElementById('unitSearch');
    const filterToggleBtn = document.getElementById('filterToggleBtn');
    const filterPanel     = document.getElementById('filterPanel');

    if (filterToggleBtn && filterPanel) {
        <?php if ($activeCount > 0): ?>filterPanel.classList.add('open');<?php endif; ?>
        filterToggleBtn.addEventListener('click', () => filterPanel.classList.toggle('open'));
    }

    const itemFilters = { condition: '', status: '', locType: '', brand: '' };

    function applyItemFilters() {
        const q = (unitSearch?.value || '').toLowerCase().trim();
        document.querySelectorAll('#itemsTable tbody tr[data-id]').forEach(row => {
            let show = true;
            if (row.cells.length < 2) return;
            if (q && !Array.from(row.cells).map(c => c.textContent).join(' ').toLowerCase().includes(q)) show = false;
            if (itemFilters.condition && (row.dataset.condition   ||'') !== itemFilters.condition) show = false;
            if (itemFilters.status    && (row.dataset.itemStatus  ||'') !== itemFilters.status)    show = false;
            if (itemFilters.locType   && (row.dataset.locType     ||'') !== itemFilters.locType)   show = false;
            if (itemFilters.brand     && !(row.dataset.brand      ||'').includes(itemFilters.brand)) show = false;
            row.style.display = show ? '' : 'none';
        });
    }

    if (unitSearch) unitSearch.addEventListener('input', applyItemFilters);

    const brandInput = document.getElementById('brandInput');
    if (brandInput) brandInput.addEventListener('input', function () {
        itemFilters.brand = this.value.trim().toLowerCase();
        applyItemFilters();
    });

    function wireChips(selector, key) {
        document.querySelectorAll(selector).forEach(chip => {
            chip.addEventListener('click', function () {
                const val = this.dataset.val.toLowerCase();
                itemFilters[key] = itemFilters[key] === val ? '' : val;
                document.querySelectorAll(selector).forEach(c => c.classList.toggle('active', c.dataset.val.toLowerCase() === itemFilters[key]));
                applyItemFilters();
            });
        });
    }
    wireChips('.condition-chip', 'condition');
    wireChips('.status-chip',    'status');
    wireChips('.loctype-chip',   'locType');

    // ── Export modal ─────────────────────────────────────────────────────────
    const exportBtn = document.getElementById('exportUnitsBtn');
    const exportModal = document.getElementById('unitsExportModal');
    if (exportBtn && exportModal) {
        exportBtn.addEventListener('click', function () {
            exportModal.style.display = 'flex';
            exportModal.removeAttribute('hidden');
        });
        exportModal.querySelector('.pz-btn-modal-cancel').addEventListener('click', function () {
            exportModal.style.display = 'none';
            exportModal.setAttribute('hidden', '');
        });
        exportModal.addEventListener('click', function (e) {
            if (e.target === exportModal) { exportModal.style.display = 'none'; exportModal.setAttribute('hidden', ''); }
        });
    }

    // ── Import units ─────────────────────────────────────────────────────────
    const importModal  = document.getElementById('importUnitsModal');
    const dropZone     = document.getElementById('unitsDropZone');
    const fileInput    = document.getElementById('unitsFileInput');
    const fileNameEl   = document.getElementById('unitsFileName');
    const resultEl     = document.getElementById('unitsResult');
    const importBtn    = document.getElementById('unitsImportBtn');
    const importToggle = document.getElementById('importUnitsBtn');

    let selectedFile = null;

    if (importToggle) {
        importToggle.addEventListener('click', function () {
            selectedFile = null;
            if (fileInput) fileInput.value = '';
            if (fileNameEl) fileNameEl.textContent = '';
            if (resultEl) resultEl.innerHTML = '';
            importModal.style.display = 'flex';
        });
    }

    if (dropZone && fileInput) {
        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.borderColor = '#8C701C'; });
        dropZone.addEventListener('dragleave', () => { dropZone.style.borderColor = '#d4c185'; });
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.style.borderColor = '#d4c185';
            const f = e.dataTransfer.files[0];
            if (f) { selectedFile = f; fileNameEl.textContent = '📎 ' + f.name; }
        });
        fileInput.addEventListener('change', function () {
            if (this.files[0]) { selectedFile = this.files[0]; fileNameEl.textContent = '📎 ' + this.files[0].name; }
        });
    }

    if (importBtn) {
        importBtn.addEventListener('click', function () {
            if (!selectedFile) { resultEl.innerHTML = '<span style="color:#c0392b">Please select a file first.</span>'; return; }
            importBtn.disabled = true;
            importBtn.textContent = 'Importing…';
            resultEl.innerHTML = '';

            const csrfName = document.querySelector('meta[name="csrf-name"]')?.content;
            const csrfHash = document.querySelector('meta[name="csrf-hash"]')?.content;
            const fd = new FormData();
            fd.append('module', 'resource_units');
            fd.append('resource_id', '<?= esc($resourceId) ?>');
            fd.append('import_file', selectedFile);
            if (csrfName && csrfHash) fd.append(csrfName, csrfHash);

            fetch('<?= base_url('import/process') ?>', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(function (data) {
                    if (data.success) {
                        resultEl.innerHTML = '<span style="color:#16a34a;font-weight:700">✓ ' + data.message + '</span>';
                        setTimeout(() => { window.location.reload(); }, 1200);
                    } else {
                        resultEl.innerHTML = '<span style="color:#c0392b">✗ ' + (data.message || 'Import failed.') + '</span>';
                    }
                })
                .catch(function () {
                    resultEl.innerHTML = '<span style="color:#c0392b">✗ Network error. Please try again.</span>';
                })
                .finally(function () {
                    importBtn.disabled = false;
                    importBtn.textContent = 'Import';
                });
        });
    }
});
</script>

<?php if ($mapsKey): ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= esc($mapsKey) ?>&libraries=places&callback=initAddUnitMap" async defer></script>
<?php endif; ?>

<?= $this->endSection() ?>
