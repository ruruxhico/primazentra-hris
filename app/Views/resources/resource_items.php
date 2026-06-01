<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="customToast" class="custom-toast" aria-live="polite" aria-atomic="true"></div>

<meta name="csrf-name" content="<?= csrf_token() ?>">
<meta name="csrf-hash" content="<?= csrf_hash() ?>">

<style>
.filter-panel { background:#fafafa; border:1px solid #e0e0e0; border-radius:6px; padding:16px 20px 12px; margin-bottom:12px; }
.filter-rows  { display:flex; flex-direction:column; gap:10px; }
.filter-row   { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.filter-row label { font-size:11px; font-weight:600; text-transform:uppercase; color:#666; letter-spacing:.4px; min-width:90px; }
.filter-row input[type="text"] { padding:5px 8px; border:1px solid #ccc; border-radius:4px; font-size:13px; width:180px; }
.filter-chips { display:flex; flex-wrap:wrap; gap:6px; }
.f-chip       { padding:4px 10px; border:1px solid #ccc; border-radius:4px; font-size:12px; font-weight:600; cursor:pointer; background:#fff; color:#444; transition:background .15s,color .15s,border-color .15s; }
.f-chip:hover  { border-color:#8C701C; color:#8C701C; }
.f-chip.active { background:#8C701C; color:#fff; border-color:#8C701C; }
.filter-footer { display:flex; align-items:center; gap:10px; margin-top:14px; }
.filter-badge  { display:inline-flex; align-items:center; justify-content:center; background:#8C701C; color:#fff; border-radius:50%; font-size:10px; font-weight:700; width:16px; height:16px; margin-left:5px; vertical-align:middle; }
</style>

<?php
    $activeFilters = $activeFilters ?? [];
    $activeCount   = count(array_filter($activeFilters));
    $af            = $activeFilters;
    $resourceId    = $resource['id'] ?? '';
?>

<header class="resource-header">
    <div>
        <h1><?= isset($resource['name']) ? esc((string)$resource['name']) : 'Resource' ?> — Units</h1>
        <p class="muted-sub">Category: <?= isset($resource['category']) ? esc((string)$resource['category']) : 'N/A' ?></p>
    </div>
</header>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert-success">✓ <?= session()->getFlashdata('success') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert-error">✗ <?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<div class="page-module">
<div class="resource-controls">
    <a href="<?= base_url('resources') ?>" class="btn btn-secondary">← Back</a>

    <button type="button" class="btn btn-filter" id="filterToggleBtn">
        ▼ FILTER<?php if ($activeCount > 0): ?><span class="filter-badge"><?= $activeCount ?></span><?php endif; ?>
    </button>

    <button class="btn btn-success" onclick="document.getElementById('addItemModal').style.display='flex'">+ Add Unit</button>
</div>

<!-- Filter Panel -->
<div id="filterPanel" class="filter-panel" style="display:none">
    <form id="filterForm" method="get" action="<?= base_url('resources/viewItems/' . $resourceId) ?>">

        <!-- Text filters: Brand and Model No -->
        <div class="filter-rows">
            <div class="filter-row">
                <label>Brand</label>
                <input type="text" name="brand" id="brandInput" value="<?= esc($af['brand'] ?? '') ?>" placeholder="Search brand…">
                <label style="min-width:unset">Model No.</label>
                <input type="text" name="model_no" id="modelNoInput" value="<?= esc($af['model_no'] ?? '') ?>" placeholder="Search model…" style="flex:1;min-width:120px;width:auto">
            </div>

            <!-- Condition chips -->
            <input type="hidden" id="conditionInput" name="condition" value="<?= esc($af['condition'] ?? '') ?>">
            <div class="filter-row">
                <label>Condition</label>
                <div class="filter-chips">
                    <?php foreach (['New','Functional','Worn','Broken'] as $opt): ?>
                    <button type="button" class="f-chip condition-chip <?= ($af['condition'] ?? '') === $opt ? 'active' : '' ?>" data-val="<?= $opt ?>"><?= $opt ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Status chips -->
            <input type="hidden" id="statusInput" name="status" value="<?= esc($af['status'] ?? '') ?>">
            <div class="filter-row">
                <label>Status</label>
                <div class="filter-chips">
                    <?php foreach (['In-Storage','In-Use','Repair'] as $opt): ?>
                    <button type="button" class="f-chip status-chip <?= ($af['status'] ?? '') === $opt ? 'active' : '' ?>" data-val="<?= $opt ?>"><?= $opt ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Location origin chips -->
            <input type="hidden" id="locTypeInput" name="loc_type" value="<?= esc($af['loc_type'] ?? '') ?>">
            <div class="filter-row">
                <label>Location</label>
                <div class="filter-chips">
                    <?php foreach (['Warehouse','Project Site'] as $opt): ?>
                    <button type="button" class="f-chip loctype-chip <?= ($af['loc_type'] ?? '') === $opt ? 'active' : '' ?>" data-val="<?= $opt ?>"><?= $opt ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="filter-footer">
            <a href="<?= base_url('resources/viewItems/' . $resourceId) ?>" class="btn btn-secondary">Reset</a>
        </div>
    </form>
</div>

<!-- Units Table -->
<div class="table-container">
    <table id="itemsTable" class="resource-table">
        <thead>
            <tr>
                <th>NO.</th>
                <th>SERIAL NO.</th>
                <th>BRAND</th>
                <th>MODEL NO.</th>
                <th>CONDITION</th>
                <th>STATUS</th>
                <th>LOCATION</th>
                <th class="text-center">ACTIONS</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="8" class="empty-state">No units found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $index => $item): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= esc((string)($item['serial_number'] ?? 'N/A')) ?></td>
                        <td><?= esc((string)($item['brand'] ?? '—')) ?></td>
                        <td><?= esc((string)($item['model_number'] ?? '—')) ?></td>
                        <td>
                            <span class="status-badge status-<?= strtolower((string)($item['condition'] ?? 'na')) ?>">
                                <?= esc((string)($item['condition'] ?? 'N/A')) ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-<?= strtolower(str_replace(['-',' '], '', (string)($item['status'] ?? 'na'))) ?>">
                                <?= esc((string)($item['status'] ?? 'N/A')) ?>
                            </span>
                        </td>
                        <td><?= esc((string)($item['location']['type'] ?? '') . (($item['location']['address'] ?? '') !== '' ? ' — ' . ($item['location']['address'] ?? '') : '')) ?: '—' ?></td>
                        <td class="text-center">
                            <button class="action-btn" title="Edit" onclick="alert('Edit coming soon')"><span class="material-symbols-outlined">edit</span></button>
                            <button class="action-btn" title="Delete" onclick="return confirm('Delete this unit?');"><span class="material-symbols-outlined">delete</span></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</div><!-- /.page-module -->

<!-- Add Unit Modal -->
<div id="addItemModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:8px;padding:28px 32px;width:100%;max-width:420px;position:relative;">
        <button onclick="document.getElementById('addItemModal').style.display='none'" style="position:absolute;top:12px;right:16px;background:none;border:none;font-size:20px;cursor:pointer;">✕</button>
        <h2 style="margin:0 0 20px;font-size:16px;">Add New Unit</h2>

        <form method="POST" action="<?= base_url('resources/addItem/' . $resourceId) ?>">
            <?= csrf_field() ?>

            <div class="form-group" style="margin-bottom:14px">
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px">Serial Number *</label>
                <input type="text" name="serial_number" class="form-control" placeholder="SN-12345" required style="width:100%;padding:7px 10px;border:1px solid #ccc;border-radius:4px;font-size:13px">
            </div>

            <div class="form-group" style="margin-bottom:14px">
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px">Condition *</label>
                <select name="condition" class="form-control" required style="width:100%;padding:7px 10px;border:1px solid #ccc;border-radius:4px;font-size:13px">
                    <option value="">— Select —</option>
                    <option>New</option><option>Functional</option><option>Worn</option><option>Broken</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:14px">
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px">Status *</label>
                <select name="status" class="form-control" required style="width:100%;padding:7px 10px;border:1px solid #ccc;border-radius:4px;font-size:13px">
                    <option value="">— Select —</option>
                    <option>In-Storage</option><option>In-Use</option><option>Repair</option>
                </select>
            </div>

            <div style="display:flex;gap:10px;margin-top:20px;">
                <button type="submit" class="btn btn-success" style="flex:1">Add Unit</button>
                <button type="button" class="btn btn-secondary" style="flex:1" onclick="document.getElementById('addItemModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterToggleBtn = document.getElementById('filterToggleBtn');
    const filterPanel     = document.getElementById('filterPanel');
    const filterForm      = document.getElementById('filterForm');

    if (filterToggleBtn && filterPanel) {
        <?php if ($activeCount > 0): ?>filterPanel.style.display = 'block';<?php endif; ?>
        filterToggleBtn.addEventListener('click', () => {
            filterPanel.style.display = filterPanel.style.display === 'none' ? 'block' : 'none';
        });
    }

    if (filterForm) {
        // Debounced submit for text inputs
        let debounce;
        ['brandInput','modelNoInput'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', () => { clearTimeout(debounce); debounce = setTimeout(() => filterForm.submit(), 400); });
        });

        // Chip toggles
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
});
</script>

<?= $this->endSection() ?>
