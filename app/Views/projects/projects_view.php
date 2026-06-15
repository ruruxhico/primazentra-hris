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
    <h1>Projects</h1>
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
    $activeFilters  = $activeFilters  ?? [];
    $activeCount    = count(array_filter($activeFilters));
    $pmOptions      = $pmOptions      ?? [];
    $seOptions      = $seOptions      ?? [];
    $af             = $activeFilters;
?>

<div class="page-module">

<!-- Toolbar -->
<div class="pz-toolbar">
    <div class="pz-search-wrap">
        <span class="material-symbols-outlined">search</span>
        <input type="text" id="searchProjects" placeholder="Search projects…" class="pz-search">
    </div>
    <div class="pz-toolbar-right">
        <button type="button" class="pz-btn pz-btn-outline" id="filterToggleBtn">
            <span class="material-symbols-outlined">filter_list</span>
            Filter<?php if ($activeCount > 0): ?><span class="pz-filter-badge"><?= $activeCount ?></span><?php endif; ?>
        </button>
        <a href="<?= base_url('projects/create') ?>" class="pz-btn pz-btn-primary">
            <span class="material-symbols-outlined">add</span> Add
        </a>
        <button class="pz-btn pz-btn-ghost"><span class="material-symbols-outlined">upload</span> Import</button>
        <button class="pz-btn pz-btn-ghost"><span class="material-symbols-outlined">download</span> Export</button>
    </div>
</div>

<!-- Filter Panel -->
<div id="filterPanel" class="pz-filter-panel">
    <form id="filterForm" method="get" action="<?= base_url('projects') ?>">
        <div class="pz-filter-grid">
            <div class="pz-filter-group">
                <label>Status</label>
                <select name="status">
                    <option value="">All Statuses</option>
                    <?php foreach (['PLANNED' => 'Planned', 'ON-GOING' => 'On Going', 'COMPLETED' => 'Completed', 'HALTED' => 'Halted'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= ($af['status'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="pz-filter-group">
                <label>Region</label>
                <select name="region">
                    <option value="">All Regions</option>
                    <?php foreach (['LUZON' => 'Luzon', 'VISAYAS' => 'Visayas', 'MINDANAO' => 'Mindanao'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= ($af['region'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="pz-filter-group">
                <label>Type</label>
                <select name="type">
                    <option value="">All Types</option>
                    <?php foreach (['RESIDENTIAL' => 'Residential', 'TOWERS' => 'Towers', 'COMMERCIAL' => 'Commercial'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= ($af['type'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="pz-filter-group">
                <label>Project Manager</label>
                <select name="pm_id">
                    <option value="">All PMs</option>
                    <?php foreach ($pmOptions as $id => $name): ?>
                    <option value="<?= esc($id) ?>" <?= ($af['pm_id'] ?? '') === $id ? 'selected' : '' ?>><?= esc($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="pz-filter-group">
                <label>Site Engineer</label>
                <select name="se_id">
                    <option value="">All SEs</option>
                    <?php foreach ($seOptions as $id => $name): ?>
                    <option value="<?= esc($id) ?>" <?= ($af['se_id'] ?? '') === $id ? 'selected' : '' ?>><?= esc($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="pz-filter-group">
                <label>Sort by Progress</label>
                <select name="sort">
                    <option value="">Default</option>
                    <option value="progress_desc" <?= ($af['sort'] ?? '') === 'progress_desc' ? 'selected' : '' ?>>Highest First</option>
                    <option value="progress_asc"  <?= ($af['sort'] ?? '') === 'progress_asc'  ? 'selected' : '' ?>>Lowest First</option>
                </select>
            </div>
        </div>
        <div class="pz-filter-footer">
            <a href="<?= base_url('projects') ?>" class="pz-btn pz-btn-ghost pz-btn-sm">
                <span class="material-symbols-outlined">restart_alt</span> Reset
            </a>
        </div>
    </form>
</div>

<!-- Table -->
<div class="pz-table-wrap">
    <table id="projectsTable" class="pz-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Project Name</th>
                <th>Type</th>
                <th>Location</th>
                <th class="center">Progress</th>
                <th>Project Manager</th>
                <th>Site Engineer</th>
                <th class="center">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($projects)): ?>
                <tr><td colspan="8" class="pz-table-empty">No projects found. <a href="<?= base_url('projects/create') ?>">Create one now</a></td></tr>
            <?php else: ?>
                <?php $offset = $pagerOffset ?? 0; ?>
                <?php foreach ($projects as $index => $project): ?>
                <tr>
                    <td class="pz-row-num"><?= $offset + $index + 1 ?></td>
                    <td class="pz-row-name"><?= esc(view_text($project['project_name'] ?? 'N/A', 'N/A')) ?></td>
                    <td class="pz-row-muted"><?= esc(view_text($project['project_type'] ?? 'N/A', 'N/A')) ?></td>
                    <td class="pz-row-muted"><?= esc(view_text($project['location_label'] ?? ($project['location']['address'] ?? 'N/A'), 'N/A')) ?></td>
                    <td class="center">
                        <span class="pz-badge pz-badge-progress">
                            <?= esc(view_text($project['progress_label'] ?? ($project['overall_progress'] ?? 0))) ?>%
                        </span>
                    </td>
                    <td><?= esc(view_text($project['project_manager_name'] ?? 'N/A', 'N/A')) ?></td>
                    <td><?= esc(view_text($project['site_engineer_name'] ?? 'N/A', 'N/A')) ?></td>
                    <td class="actions">
                        <a href="<?= base_url('projects/show/' . $project['id']) ?>" class="pz-btn-icon" title="View"><span class="material-symbols-outlined">visibility</span></a>
                        <a href="<?= base_url('projects/update/' . $project['id']) ?>" class="pz-btn-icon" title="Edit"><span class="material-symbols-outlined">edit</span></a>
                        <a href="#" data-id="<?= esc(view_text($project['id'] ?? '')) ?>" data-name="<?= esc(view_text($project['project_name'] ?? '')) ?>" class="pz-btn-icon danger action-delete" title="Delete"><span class="material-symbols-outlined">delete</span></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="pager-wrapper">
    <?= service('pager')->links('projects', 'bootstrap_full') ?>
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

    // Search
    const search = document.getElementById('searchProjects');
    const table  = document.getElementById('projectsTable');
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
            confirmMessage.innerText = 'Delete "' + (this.dataset.name || 'this project') + '"?';
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

        fetch('<?= base_url('projects/delete') ?>', {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        })
        .then(r => r.json())
        .then(data => {
            if (data && data.success) {
                showToast('Project deleted', 'success');
                if (pendingRow) animateRowOut(pendingRow);
            } else {
                showToast(data.message || 'Delete failed', 'error');
            }
        })
        .catch(() => showToast('Error deleting project', 'error'))
        .finally(() => {
            pendingDeleteId = null; pendingRow = null;
            confirmDialog.classList.remove('open');
        });
    });
});
</script>

<?= $this->endSection() ?>
