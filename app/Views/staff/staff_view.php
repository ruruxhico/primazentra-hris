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
            <button id="confirmOk" class="btn-toolbar btn-add">Delete</button>
        </div>
    </div>
</div>

<div class="pz-page-header">
    <h1>Staff</h1>
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

<?php
    $activeFilters = $activeFilters ?? [];
    $activeCount   = count(array_filter($activeFilters));
    $roleOptions   = $roleOptions   ?? [];
    $af            = $activeFilters;
?>

<div class="page-module">

<!-- Toolbar -->
<div class="pz-toolbar">
    <div class="pz-search-wrap">
        <span class="material-symbols-outlined">search</span>
        <input type="text" id="searchStaff" placeholder="Search staff…" class="pz-search">
    </div>
    <div class="pz-toolbar-right">
        <button type="button" class="pz-btn pz-btn-outline" id="filterToggleBtn">
            <span class="material-symbols-outlined">filter_list</span>
            Filter<?php if ($activeCount > 0): ?><span class="pz-filter-badge"><?= $activeCount ?></span><?php endif; ?>
        </button>
        <a href="<?= base_url('staff/create') ?>" class="pz-btn pz-btn-primary">
            <span class="material-symbols-outlined">add</span> Add
        </a>
        <button class="pz-btn pz-btn-ghost"><span class="material-symbols-outlined">upload</span> Import</button>
        <button class="pz-btn pz-btn-ghost"><span class="material-symbols-outlined">download</span> Export</button>
    </div>
</div>

<!-- Filter Panel -->
<div id="filterPanel" class="pz-filter-panel">
    <form id="filterForm" method="get" action="<?= base_url('staff') ?>">
        <input type="hidden" id="lnRangeInput" name="ln_range" value="<?= esc($af['ln_range'] ?? '') ?>">

        <div style="margin-bottom:.65rem">
            <p class="pz-filter-group" style="margin-bottom:.35rem"><label>Last Name</label></p>
            <div class="pz-chip-row">
                <?php foreach (['A-C','D-F','G-I','J-L','M-O','P-R','S-U','V-Z'] as $range): ?>
                <button type="button"
                        class="pz-chip <?= ($af['ln_range'] ?? '') === $range ? 'active' : '' ?>"
                        data-range="<?= $range ?>"><?= $range ?></button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="pz-filter-grid" style="max-width:320px">
            <div class="pz-filter-group">
                <label>Role</label>
                <select name="role_filter">
                    <option value="">All Roles</option>
                    <?php foreach ($roleOptions as $r): ?>
                    <option value="<?= esc($r) ?>" <?= ($af['role_filter'] ?? '') === $r ? 'selected' : '' ?>><?= esc($r) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="pz-filter-footer">
            <a href="<?= base_url('staff') ?>" class="pz-btn pz-btn-ghost pz-btn-sm">
                <span class="material-symbols-outlined">restart_alt</span> Reset
            </a>
        </div>
    </form>
</div>

<!-- Table -->
<div class="pz-table-wrap">
    <table id="staffTable" class="pz-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Last Name</th>
                <th>First Name</th>
                <th>Middle Name</th>
                <th>Email</th>
                <th>Role</th>
                <th class="center">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($staff)): ?>
                <tr><td colspan="7" class="pz-table-empty">No staff members found. <a href="<?= base_url('staff/create') ?>">Add one now</a></td></tr>
            <?php else: ?>
                <?php $offset = $pagerOffset ?? 0; ?>
                <?php foreach ($staff as $index => $person): ?>
                <tr>
                    <td class="pz-row-num"><?= $offset + $index + 1 ?></td>
                    <td class="pz-row-name"><?= esc((string)($person['last_name'] ?? 'N/A')) ?></td>
                    <td><?= esc((string)($person['first_name'] ?? 'N/A')) ?></td>
                    <td class="pz-row-muted"><?= esc((string)($person['middle_name'] ?? '—')) ?></td>
                    <td class="pz-row-muted"><?= esc((string)($person['email'] ?? 'N/A')) ?></td>
                    <td>
                        <?php
                            $role = strtolower($person['role'] ?? '');
                            $rbc  = str_contains($role,'admin') ? 'pz-badge-rejected' : (str_contains($role,'manager') ? 'pz-badge-deployed' : (str_contains($role,'engineer') ? 'pz-badge-maintenance' : 'pz-badge-available'));
                        ?>
                        <span class="pz-badge <?= $rbc ?>"><?= esc((string)($person['role'] ?? 'Staff')) ?></span>
                    </td>
                    <td class="actions">
                        <a href="<?= base_url('staff/show/' . $person['id']) ?>" class="pz-btn-icon" title="View"><span class="material-symbols-outlined">visibility</span></a>
                        <a href="<?= base_url('staff/update/' . $person['id']) ?>" class="pz-btn-icon" title="Edit"><span class="material-symbols-outlined">edit</span></a>
                        <a href="#" data-id="<?= esc((string)$person['id']) ?>" data-name="<?= esc((string)(($person['first_name'] ?? '') . ' ' . ($person['last_name'] ?? ''))) ?>" class="pz-btn-icon danger action-delete" title="Delete"><span class="material-symbols-outlined">delete</span></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="pager-wrapper">
    <?= service('pager')->links('staff', 'bootstrap_full') ?>
</div>

</div><!-- /.page-module -->

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfName = document.querySelector('meta[name="csrf-name"]')?.getAttribute('content');
    const csrfHash = document.querySelector('meta[name="csrf-hash"]')?.getAttribute('content');
    const confirmDialog  = document.getElementById('confirmDialog');
    const confirmMessage = document.getElementById('confirmMessage');
    const confirmOk      = document.getElementById('confirmOk');
    const confirmCancel  = document.getElementById('confirmCancel');
    const toast          = document.getElementById('customToast');

    function showToast(msg, type) {
        if (!toast) return;
        toast.className = 'custom-toast show ' + (type === 'error' ? 'error' : 'success');
        toast.innerText = msg;
        setTimeout(() => { toast.className = 'custom-toast'; }, 3000);
    }

    function animateRowOut(row) {
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

    // Filter panel
    const filterToggleBtn = document.getElementById('filterToggleBtn');
    const filterPanel     = document.getElementById('filterPanel');
    const filterForm      = document.getElementById('filterForm');
    if (filterToggleBtn && filterPanel) {
        <?php if ($activeCount > 0): ?>filterPanel.classList.add('open');<?php endif; ?>
        filterToggleBtn.addEventListener('click', () => filterPanel.classList.toggle('open'));
    }
    filterForm && filterForm.querySelectorAll('select').forEach(sel => {
        sel.addEventListener('change', () => filterForm.submit());
    });

    const lnRangeInput = document.getElementById('lnRangeInput');
    filterForm && filterForm.querySelectorAll('.pz-chip[data-range]').forEach(chip => {
        chip.addEventListener('click', function () {
            lnRangeInput.value = lnRangeInput.value === this.dataset.range ? '' : this.dataset.range;
            filterForm.submit();
        });
    });

    // Search
    const search = document.getElementById('searchStaff');
    const table  = document.getElementById('staffTable');
    search && search.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        table.querySelectorAll('tbody tr').forEach(row => {
            row.style.display = q === '' || row.innerText.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    // Delete
    let pendingDeleteId = null;
    let pendingRow = null;

    document.querySelectorAll('.action-delete').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            pendingDeleteId = this.dataset.id;
            pendingRow = this.closest('tr');
            confirmMessage.innerText = 'Delete "' + (this.dataset.name || 'this staff member') + '"?';
            confirmDialog.classList.add('open');
        });
    });

    confirmCancel?.addEventListener('click', () => {
        pendingDeleteId = null; pendingRow = null;
        confirmDialog.classList.remove('open');
    });

    confirmOk?.addEventListener('click', () => {
        if (!pendingDeleteId) return;
        const body = new URLSearchParams();
        if (csrfName && csrfHash) body.append(csrfName, csrfHash);
        body.append('id', pendingDeleteId);

        fetch('<?= base_url('staff/delete') ?>', {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        })
        .then(r => r.json())
        .then(data => {
            if (data && data.success) {
                showToast('Staff member deleted', 'success');
                if (pendingRow) animateRowOut(pendingRow);
            } else {
                showToast(data.message || 'Delete failed', 'error');
            }
        })
        .catch(() => showToast('Error deleting staff member', 'error'))
        .finally(() => {
            pendingDeleteId = null; pendingRow = null;
            confirmDialog.classList.remove('open');
        });
    });
});
</script>

<?= $this->endSection() ?>
