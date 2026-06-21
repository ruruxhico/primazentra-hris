<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="customToast" class="custom-toast" aria-live="polite" aria-atomic="true"></div>

<meta name="csrf-name" content="<?= csrf_token() ?>">
<meta name="csrf-hash" content="<?= csrf_hash() ?>">

<?php
    $activeFilters = $activeFilters ?? [];
    $activeCount   = count(array_filter($activeFilters));
    $af            = $activeFilters;
    $resourceId    = $resource['id'] ?? '';
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
    <div class="rv-search-wrap" style="flex:1;max-width:320px;">
        <span class="material-symbols-outlined">search</span>
        <input type="text" id="unitSearch" class="rv-search" placeholder="Search serial, brand, model…">
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
        <div style="position:relative;display:inline-block;">
            <button type="button" class="pz-btn pz-btn-outline" id="exportUnitsBtn">
                <span class="material-symbols-outlined">download</span> Export
                <span class="material-symbols-outlined" style="font-size:.85rem;margin-left:2px;">expand_more</span>
            </button>
            <div id="exportDropdown" style="display:none;position:absolute;right:0;top:calc(100% + 4px);background:#fff;border:1px solid #e2d9c0;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.12);z-index:200;min-width:150px;overflow:hidden;">
                <?php
                $exportBase = base_url('export?module=resource_units&resource_id=' . esc($resourceId) . '&resource_name=' . urlencode($resource['name'] ?? 'Resource') . '&format=');
                ?>
                <a href="<?= $exportBase ?>xlsx" class="export-opt" style="display:flex;align-items:center;gap:.5rem;padding:.55rem .9rem;font-size:.85rem;color:#2c1a00;text-decoration:none;"><span>📊</span> XLSX</a>
                <a href="<?= $exportBase ?>csv"  class="export-opt" style="display:flex;align-items:center;gap:.5rem;padding:.55rem .9rem;font-size:.85rem;color:#2c1a00;text-decoration:none;"><span>📋</span> CSV</a>
                <a href="<?= $exportBase ?>pdf"  class="export-opt" style="display:flex;align-items:center;gap:.5rem;padding:.55rem .9rem;font-size:.85rem;color:#2c1a00;text-decoration:none;" target="_blank"><span>📄</span> PDF</a>
            </div>
        </div>
    </div>
</div>

<!-- Filter Panel -->
<div id="filterPanel" class="pz-filter-panel">
    <form id="filterForm" method="get" action="<?= base_url('resources/viewItems/' . $resourceId) ?>">

        <div class="pz-filter-grid" style="margin-bottom:.65rem">
            <div class="pz-filter-group">
                <label>Brand</label>
                <input type="text" name="brand" id="brandInput" value="<?= esc($af['brand'] ?? '') ?>" placeholder="Search brand…">
            </div>
            <div class="pz-filter-group">
                <label>Model No.</label>
                <input type="text" name="model_no" id="modelNoInput" value="<?= esc($af['model_no'] ?? '') ?>" placeholder="Search model…">
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
<div class="pz-table-wrap">
    <table id="itemsTable" class="pz-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Serial No.</th>
                <th>Brand</th>
                <th>Model No.</th>
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
                <tr>
                    <td class="pz-row-num"><?= $pagerOffset + $index + 1 ?></td>
                    <td><?= esc((string)($item['serial_number'] ?? 'N/A')) ?></td>
                    <td class="pz-row-muted"><?= esc((string)($item['brand'] ?? '—')) ?></td>
                    <td class="pz-row-muted"><?= esc((string)($item['model_number'] ?? '—')) ?></td>
                    <td>
                        <?php $cond = strtolower($item['condition'] ?? ''); $cbc = $cond === 'new' ? 'pz-badge-approved' : ($cond === 'functional' ? 'pz-badge-available' : ($cond === 'worn' ? 'pz-badge-deployed' : 'pz-badge-rejected')); ?>
                        <span class="pz-badge <?= $cbc ?>"><?= esc((string)($item['condition'] ?? 'N/A')) ?></span>
                    </td>
                    <td>
                        <?php $stt = strtolower(str_replace(['-',' '],'', $item['status'] ?? '')); $stbc = $stt === 'instorage' ? 'pz-badge-available' : ($stt === 'inuse' ? 'pz-badge-deployed' : ($stt === 'transferpending' ? 'pz-badge-pending' : 'pz-badge-maintenance')); ?>
                        <span class="pz-badge <?= $stbc ?>"><?= esc((string)($item['status'] ?? 'N/A')) ?></span>
                    </td>
                    <td class="pz-row-muted"><?= esc(trim((string)($item['location']['type'] ?? '') . (($item['location']['address'] ?? '') !== '' ? ' — ' . ($item['location']['address'] ?? '') : ''))) ?: '—' ?></td>
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
                        <button class="pz-btn-icon edit-unit-btn" title="Edit"
                            data-id="<?= esc($item['id'] ?? '') ?>"
                            data-serial="<?= esc($item['serial_number'] ?? '') ?>"
                            data-brand="<?= esc($item['brand'] ?? '') ?>"
                            data-model="<?= esc($item['model_number'] ?? '') ?>"
                            data-condition="<?= esc($item['condition'] ?? '') ?>"
                            data-status="<?= esc($item['status'] ?? '') ?>"
                            data-loc-type="<?= esc($item['location']['type'] ?? '') ?>"
                            data-loc-addr="<?= esc($item['location']['address'] ?? '') ?>">
                            <span class="material-symbols-outlined">edit</span>
                        </button>
                        <button class="pz-btn-icon danger delete-unit-btn" title="Delete"
                            data-id="<?= esc($item['id'] ?? '') ?>"
                            data-serial="<?= esc($item['serial_number'] ?? $item['id'] ?? 'this unit') ?>">
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
    <?= service('pager')->links('items', 'bootstrap_full') ?>
</div>
</div><!-- /.page-module -->

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
            <button id="unitsImportBtn" class="pz-btn pz-btn-primary" style="min-width:80px;">Import</button>
        </div>
    </div>
</div>

<!-- Delete Unit Confirmation Modal -->
<div id="deleteItemModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:1.6rem 1.5rem 1.3rem;width:100%;max-width:360px;position:relative;border:1.5px solid #f5c6c6;box-shadow:0 8px 40px rgba(0,0,0,.18)">
        <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.8rem">
            <span class="material-symbols-outlined" style="font-size:1.4rem;color:#c0392b">delete_forever</span>
            <p style="font-weight:700;font-size:.95rem;color:#3b2a0e;margin:0">Delete Unit</p>
        </div>
        <p style="font-size:.87rem;color:#555;margin:0 0 1.2rem;line-height:1.5">
            Are you sure you want to delete unit <strong id="deleteUnitLabel"></strong>? This cannot be undone.
        </p>
        <form id="deleteItemForm" method="POST" action="">
            <?= csrf_field() ?>
            <div style="display:flex;gap:.55rem">
                <button type="submit" class="pz-btn" style="flex:1;background:#c0392b;color:#fff;border-color:#c0392b;font-weight:700">Delete</button>
                <button type="button" class="pz-btn pz-btn-cancel" style="flex:1" onclick="document.getElementById('deleteItemModal').style.display='none'">Cancel</button>
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
                    <div class="pz-form-group">
                        <label>Model No.</label>
                        <input type="text" id="editModelNo" name="model_number" placeholder="e.g. DP4011">
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
                <button type="submit" class="pz-btn pz-btn-primary" style="flex:1">Save Changes</button>
                <button type="button" class="pz-btn pz-btn-cancel" style="flex:1" onclick="document.getElementById('editItemModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Unit Modal -->
<div id="addItemModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fdf8ee;border-radius:14px;padding:1.5rem;width:100%;max-width:420px;position:relative;border:1.5px solid #e8dfc7;box-shadow:0 8px 40px rgba(0,0,0,.18)">
        <button onclick="document.getElementById('addItemModal').style.display='none'" style="position:absolute;top:.75rem;right:1rem;background:none;border:none;font-size:1.2rem;cursor:pointer;color:#7a5c1e;line-height:1">✕</button>
        <p style="font-weight:700;font-size:.95rem;color:#3b2a0e;margin:0 0 1.1rem">Add New Unit</p>

        <form method="POST" action="<?= base_url('resources/addItem/' . $resourceId) ?>">
            <?= csrf_field() ?>
            <div style="display:flex;flex-direction:column;gap:.65rem">
                <div class="pz-form-group">
                    <label>Serial Number *</label>
                    <input type="text" name="serial_number" placeholder="SN-12345" required>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem">
                    <div class="pz-form-group">
                        <label>Brand</label>
                        <input type="text" name="brand" placeholder="e.g. Makita">
                    </div>
                    <div class="pz-form-group">
                        <label>Model No.</label>
                        <input type="text" name="model_number" placeholder="e.g. DP4011">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem">
                    <div class="pz-form-group">
                        <label>Condition *</label>
                        <select name="condition" required>
                            <option value="">— Select —</option>
                            <option>New</option><option>Functional</option><option>Worn</option><option>Broken</option>
                        </select>
                    </div>
                    <div class="pz-form-group">
                        <label>Status *</label>
                        <select name="status" required>
                            <option value="">— Select —</option>
                            <option>In-Storage</option><option>In-Use</option><option>Repair</option>
                        </select>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem">
                    <div class="pz-form-group">
                        <label>Location Type</label>
                        <select name="location_type">
                            <option value="">— Select —</option>
                            <option>Warehouse</option><option>Project Site</option>
                        </select>
                    </div>
                    <div class="pz-form-group">
                        <label>Location Address</label>
                        <input type="text" name="location_address" placeholder="Address">
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:.55rem;margin-top:1.1rem;padding-top:.9rem;border-top:1px solid #e8dfc7">
                <button type="submit" class="pz-btn pz-btn-primary" style="flex:1">Add Unit</button>
                <button type="button" class="pz-btn pz-btn-cancel" style="flex:1" onclick="document.getElementById('addItemModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
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
            document.getElementById('editModelNo').value   = d.model;
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
            deleteModal.style.display = 'flex';
        });
    });

    deleteModal.addEventListener('click', function (e) {
        if (e.target === deleteModal) deleteModal.style.display = 'none';
    });

    // ── Live search ──────────────────────────────────────────────────────────
    const unitSearch = document.getElementById('unitSearch');
    if (unitSearch) {
        unitSearch.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('#itemsTable tbody tr').forEach(function (row) {
                if (row.cells.length < 2) return; // empty-state row
                const text = Array.from(row.cells).map(c => c.textContent).join(' ').toLowerCase();
                row.style.display = q === '' || text.includes(q) ? '' : 'none';
            });
        });
    }

    const filterToggleBtn = document.getElementById('filterToggleBtn');
    const filterPanel     = document.getElementById('filterPanel');
    const filterForm      = document.getElementById('filterForm');

    if (filterToggleBtn && filterPanel) {
        <?php if ($activeCount > 0): ?>filterPanel.classList.add('open');<?php endif; ?>
        filterToggleBtn.addEventListener('click', () => filterPanel.classList.toggle('open'));
    }

    if (filterForm) {
        let debounce;
        ['brandInput','modelNoInput'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', () => { clearTimeout(debounce); debounce = setTimeout(() => filterForm.submit(), 400); });
        });

        function wireChips(selector, hiddenId) {
            const inp = document.getElementById(hiddenId);
            filterForm.querySelectorAll(selector).forEach(chip => {
                chip.addEventListener('click', function () {
                    inp.value = inp.value === this.dataset.val ? '' : this.dataset.val;
                    filterForm.submit();
                });
            });
        }
        wireChips('.condition-chip', 'conditionInput');
        wireChips('.status-chip',    'statusInput');
        wireChips('.loctype-chip',   'locTypeInput');
    }

    // ── Export dropdown ──────────────────────────────────────────────────────
    const exportBtn      = document.getElementById('exportUnitsBtn');
    const exportDropdown = document.getElementById('exportDropdown');
    if (exportBtn && exportDropdown) {
        exportBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            exportDropdown.style.display = exportDropdown.style.display === 'block' ? 'none' : 'block';
        });
        document.addEventListener('click', function () { exportDropdown.style.display = 'none'; });
        exportDropdown.addEventListener('click', function (e) { e.stopPropagation(); });
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

<?= $this->endSection() ?>
