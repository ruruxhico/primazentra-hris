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

<div class="resource-header">
    <h1>Projects</h1>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert-success">✓ <?= session()->getFlashdata('success') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert-error">✗ <?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<style>
.filter-panel { background:#fafafa; border:1px solid #e0e0e0; border-radius:6px; padding:16px 20px 12px; margin-bottom:12px; }
.filter-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:12px 16px; }
.filter-group { display:flex; flex-direction:column; gap:4px; }
.filter-group label { font-size:11px; font-weight:600; text-transform:uppercase; color:#666; letter-spacing:.4px; }
.filter-group select { padding:6px 8px; border:1px solid #ccc; border-radius:4px; font-size:13px; background:#fff; }
.filter-footer { display:flex; align-items:center; gap:10px; margin-top:14px; flex-wrap:wrap; }
.filter-badge { display:inline-flex; align-items:center; justify-content:center; background:#8C701C; color:#fff; border-radius:50%; font-size:10px; font-weight:700; width:16px; height:16px; margin-left:5px; vertical-align:middle; }
</style>

<?php
    $activeFilters  = $activeFilters  ?? [];
    $activeCount    = count(array_filter($activeFilters));
    $pmOptions      = $pmOptions      ?? [];
    $seOptions      = $seOptions      ?? [];
    $af             = $activeFilters;
?>

<div class="page-module">
<div class="resource-controls">
    <input type="text" id="searchProjects" placeholder="🔍 Search projects..." class="search-input">

    <button type="button" class="btn btn-filter" id="filterToggleBtn">
        ▼ FILTER<?php if ($activeCount > 0): ?><span class="filter-badge"><?= $activeCount ?></span><?php endif; ?>
    </button>

    <a href="<?= base_url('projects/create') ?>" class="btn btn-success">
        + ADD
    </a>

    <button class="btn btn-import">
        ⬇ IMPORT
    </button>

    <button class="btn btn-export">
        ⬆ EXPORT
    </button>
</div>

<!-- Filter Panel -->
<div id="filterPanel" class="filter-panel" style="display:none">
    <form id="filterForm" method="get" action="<?= base_url('projects') ?>">
        <div class="filter-grid">

            <div class="filter-group">
                <label>Status</label>
                <select name="status">
                    <option value="">All Statuses</option>
                    <?php foreach (['PLANNED' => 'Planned', 'ON-GOING' => 'On Going', 'COMPLETED' => 'Completed', 'HALTED' => 'Halted'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= ($af['status'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Region</label>
                <select name="region">
                    <option value="">All Regions</option>
                    <?php foreach (['LUZON' => 'Luzon', 'VISAYAS' => 'Visayas', 'MINDANAO' => 'Mindanao'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= ($af['region'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Type</label>
                <select name="type">
                    <option value="">All Types</option>
                    <?php foreach (['RESIDENTIAL' => 'Residential', 'TOWERS' => 'Towers', 'COMMERCIAL' => 'Commercial'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= ($af['type'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Project Manager</label>
                <select name="pm_id">
                    <option value="">All PMs</option>
                    <?php foreach ($pmOptions as $id => $name): ?>
                    <option value="<?= esc($id) ?>" <?= ($af['pm_id'] ?? '') === $id ? 'selected' : '' ?>><?= esc($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Site Engineer</label>
                <select name="se_id">
                    <option value="">All SEs</option>
                    <?php foreach ($seOptions as $id => $name): ?>
                    <option value="<?= esc($id) ?>" <?= ($af['se_id'] ?? '') === $id ? 'selected' : '' ?>><?= esc($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Sort by Progress</label>
                <select name="sort">
                    <option value="">Default</option>
                    <option value="progress_desc" <?= ($af['sort'] ?? '') === 'progress_desc' ? 'selected' : '' ?>>Highest First</option>
                    <option value="progress_asc"  <?= ($af['sort'] ?? '') === 'progress_asc'  ? 'selected' : '' ?>>Lowest First</option>
                </select>
            </div>

        </div>
        <div class="filter-footer">
            <a href="<?= base_url('projects') ?>" class="btn btn-secondary">Reset</a>
        </div>
    </form>
</div>

<!-- Projects Table -->
<div class="table-container">
    <table id="projectsTable" class="resource-table">
        <thead>
            <tr>
                <th>NO.</th>
                <th>PROJECT NAME</th>
                <th>TYPE</th>
                <th>LOCATION</th>
                <th class="text-center">PROGRESS</th>
                <th>PROJECT MANAGER</th>
                <th>SITE ENGINEER</th>
                <th class="text-center">ACTIONS</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($projects)): ?>
                <tr>
                    <td colspan="8" class="empty-state">
                        No projects found. <a href="<?= base_url('projects/create') ?>">Create one now</a>
                    </td>
                </tr>
            <?php else: ?>
                <?php $offset = $pagerOffset ?? 0; ?>
                <?php foreach ($projects as $index => $project): ?>
                    <tr>
                        <td><?= $offset + $index + 1 ?></td>
                        <td><strong><?= esc(view_text($project['project_name'] ?? 'N/A', 'N/A')) ?></strong></td>
                        <td><?= esc(view_text($project['project_type'] ?? 'N/A', 'N/A')) ?></td>
                        <td><?= esc(view_text($project['location_label'] ?? ($project['location']['address'] ?? 'N/A'), 'N/A')) ?></td>
                        <td class="text-center">
                            <span class="progress-badge">
                                <?= esc(view_text($project['progress_label'] ?? ($project['overall_progress'] ?? 0))) ?>%
                            </span>
                        </td>
                        <td><?= esc(view_text($project['project_manager_name'] ?? 'N/A', 'N/A')) ?></td>
                        <td><?= esc(view_text($project['site_engineer_name'] ?? 'N/A', 'N/A')) ?></td>
                        <td class="text-center" style="white-space: nowrap;">
                            <a href="<?= base_url('projects/show/' . $project['id']) ?>" class="action-btn" title="View"><span class="material-symbols-outlined">visibility</span></a>
                            <a href="<?= base_url('projects/update/' . $project['id']) ?>" class="action-btn" title="Edit"><span class="material-symbols-outlined">edit</span></a>
                            <a href="#" data-id="<?= esc(view_text($project['id'] ?? '')) ?>" data-name="<?= esc(view_text($project['project_name'] ?? '')) ?>" class="action-btn action-delete" title="Delete"><span class="material-symbols-outlined">delete</span></a>
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
    const confirmDialog = document.getElementById('confirmDialog');
    const confirmMessage = document.getElementById('confirmMessage');
    const confirmOk = document.getElementById('confirmOk');
    const confirmCancel = document.getElementById('confirmCancel');
    const toast = document.getElementById('customToast');

    function showToast(message, type = 'info') {
        if (!toast) return;
        toast.className = 'custom-toast show ' + (type === 'error' ? 'error' : 'success');
        toast.innerText = message;
        setTimeout(() => { toast.className = 'custom-toast'; }, 3000);
    }

    // — Filter panel toggle —
    const filterToggleBtn = document.getElementById('filterToggleBtn');
    const filterPanel     = document.getElementById('filterPanel');
    const filterForm      = document.getElementById('filterForm');
    if (filterToggleBtn && filterPanel) {
        <?php if ($activeCount > 0): ?>
        filterPanel.style.display = 'block';
        <?php endif; ?>
        filterToggleBtn.addEventListener('click', function () {
            filterPanel.style.display = filterPanel.style.display === 'none' ? 'block' : 'none';
        });
    }

    // — Auto-submit on any filter change —
    if (filterForm) {
        filterForm.querySelectorAll('select').forEach(function (sel) {
            sel.addEventListener('change', function () { filterForm.submit(); });
        });
    }

    const search = document.getElementById('searchProjects');
    const table = document.getElementById('projectsTable');
    if (search && table) {
        search.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            table.querySelectorAll('tbody tr').forEach(row => {
                row.style.display = q === '' || row.innerText.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }

    let pendingDeleteId = null;
    let pendingRow = null;

    document.querySelectorAll('.action-delete').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            pendingDeleteId = this.dataset.id;
            pendingRow = this.closest('tr');
            const name = this.dataset.name || 'this project';
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

        fetch('<?= base_url('projects/delete') ?>', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        })
        .then(r => r.json())
        .then(data => {
            if (data && data.success) {
                showToast('Project deleted', 'success');
                if (pendingRow) {
                    pendingRow.classList.add('removing');
                    setTimeout(() => pendingRow?.remove(), 350);
                }
            } else {
                showToast(data.message || 'Delete failed', 'error');
            }
        })
        .catch(() => showToast('Error deleting project', 'error'))
        .finally(() => {
            pendingDeleteId = null;
            pendingRow = null;
            confirmDialog.classList.remove('open');
        });
    });
});
</script>

<?= $this->endSection() ?>
