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
    <div class="pz-toolbar-right">
        <button type="button" class="pz-btn pz-btn-outline" id="filterToggleBtn">
            <span class="material-symbols-outlined">filter_list</span>
            Filter<?php if ($activeCount > 0): ?><span class="pz-filter-badge"><?= $activeCount ?></span><?php endif; ?>
        </button>
        <button class="pz-btn pz-btn-primary" onclick="document.getElementById('addItemModal').style.display='flex'">
            <span class="material-symbols-outlined">add</span> Add Unit
        </button>
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
                <th class="center">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $pagerOffset = $pagerOffset ?? 0; ?>
            <?php if (empty($items)): ?>
                <tr><td colspan="8" class="pz-table-empty">No units found.</td></tr>
            <?php else: ?>
                <?php foreach ($items as $index => $item): ?>
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
                        <?php $stt = strtolower(str_replace(['-',' '],'', $item['status'] ?? '')); $stbc = $stt === 'instorage' ? 'pz-badge-available' : ($stt === 'inuse' ? 'pz-badge-deployed' : 'pz-badge-maintenance'); ?>
                        <span class="pz-badge <?= $stbc ?>"><?= esc((string)($item['status'] ?? 'N/A')) ?></span>
                    </td>
                    <td class="pz-row-muted"><?= esc(trim((string)($item['location']['type'] ?? '') . (($item['location']['address'] ?? '') !== '' ? ' — ' . ($item['location']['address'] ?? '') : ''))) ?: '—' ?></td>
                    <td class="actions">
                        <button class="pz-btn-icon" title="Edit" onclick="alert('Edit coming soon')"><span class="material-symbols-outlined">edit</span></button>
                        <button class="pz-btn-icon danger" title="Delete" onclick="return confirm('Delete this unit?')"><span class="material-symbols-outlined">delete</span></button>
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

<!-- Add Unit Modal -->
<div id="addItemModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fdf8ee;border-radius:14px;padding:1.5rem;width:100%;max-width:400px;position:relative;border:1.5px solid #e8dfc7;box-shadow:0 8px 40px rgba(0,0,0,.18)">
        <button onclick="document.getElementById('addItemModal').style.display='none'" style="position:absolute;top:.75rem;right:1rem;background:none;border:none;font-size:1.2rem;cursor:pointer;color:#7a5c1e;line-height:1">✕</button>
        <p style="font-weight:700;font-size:.95rem;color:#3b2a0e;margin:0 0 1.1rem">Add New Unit</p>

        <form method="POST" action="<?= base_url('resources/addItem/' . $resourceId) ?>">
            <?= csrf_field() ?>
            <div style="display:flex;flex-direction:column;gap:.65rem">
                <div class="pz-form-group">
                    <label>Serial Number *</label>
                    <input type="text" name="serial_number" placeholder="SN-12345" required>
                </div>
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
            <div style="display:flex;gap:.55rem;margin-top:1.1rem;padding-top:.9rem;border-top:1px solid #e8dfc7">
                <button type="submit" class="pz-btn pz-btn-primary" style="flex:1">Add Unit</button>
                <button type="button" class="pz-btn pz-btn-outline" style="flex:1" onclick="document.getElementById('addItemModal').style.display='none'">Cancel</button>
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
});
</script>

<?= $this->endSection() ?>
