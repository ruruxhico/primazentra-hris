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

    <button class="btn btn-import" id="importBtn" data-module="projects">
        ⬇ IMPORT
    </button>

    <button class="btn btn-export" id="exportBtn" data-module="projects">
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
    <table id="projectsTable" class="resource-table" data-offset="<?= $pagerOffset ?? 0 ?>">
        <thead>
            <tr>
                <th class="pz-col-check"><input type="checkbox" class="select-all-cb" title="Select all"></th>
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
                    <td colspan="9" class="empty-state">
                        No projects found. <a href="<?= base_url('projects/create') ?>">Create one now</a>
                    </td>
                </tr>
            <?php else: ?>
                <?php $offset = $pagerOffset ?? 0; ?>
                <?php foreach ($projects as $index => $project): ?>
                    <tr>
                        <td class="pz-col-check">
                            <input type="checkbox" class="row-cb" value="<?= esc($project['id'] ?? '') ?>" data-name="<?= esc($project['project_name'] ?? '') ?>">
                        </td>
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

<!-- Bulk Action Bar -->
<div class="bulk-action-bar" id="bulkActionBar" style="display:none">
    <span class="bulk-count" id="bulkCount">0 selected</span>
    <button type="button" class="bulk-delete-btn" id="bulkDeleteBtn">
        <span class="material-symbols-outlined">delete</span> Delete Selected
    </button>
</div>

<!-- Recently Deleted Section -->
<?php $recentlyDeleted = $recentlyDeleted ?? []; ?>
<div class="recently-deleted-wrap" id="recentlyDeletedWrap">
    <button type="button" class="rd-toggle-btn" id="rdToggleBtn">
        <span class="material-symbols-outlined">restore_from_trash</span>
        Recently Deleted
        <?php if (!empty($recentlyDeleted)): ?>
        <span class="rd-badge"><?= count($recentlyDeleted) ?></span>
        <?php endif; ?>
        <span class="material-symbols-outlined rd-chevron">expand_more</span>
    </button>
    <div class="rd-panel" id="rdPanel" style="display:none">
        <?php if (empty($recentlyDeleted)): ?>
        <p class="rd-empty">No recently deleted items.</p>
        <?php else: ?>
        <table class="resource-table rd-table">
            <thead><tr>
                <th>Name</th>
                <th>Deleted</th>
                <th>Expires</th>
                <th class="text-center">Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach ($recentlyDeleted as $rd): ?>
            <tr data-trash-id="<?= esc($rd['id']) ?>">
                <td><?= esc($rd['name'] ?? '—') ?></td>
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
    const DELETE_URL = '<?= base_url('projects/delete') ?>';
    const BASE_URL   = '<?= base_url() ?>';

    function escHtml(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function showToast(message, type = 'info') {
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

    function renumberTable(tableEl, noColIdx) {
        if (!tableEl) return;
        const offset = parseInt(tableEl.dataset.offset || '0');
        let num = offset + 1;
        tableEl.querySelectorAll('tbody tr').forEach(tr => {
            if (tr.cells[0] && tr.cells[0].colSpan > 1) return;
            const td = tr.cells[noColIdx];
            if (td) td.textContent = num++;
        });
    }

    function addRestoredProjectRow(id, d) {
        const table = document.getElementById('projectsTable');
        if (!table) return;
        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        tbody.querySelector('[colspan]')?.closest('tr')?.remove();

        const nameVal  = escHtml(d.project_name || 'N/A');
        const type     = escHtml(d.project_type || 'N/A');
        const location = escHtml((d.location && d.location.address) || 'N/A');
        const progress = d.overall_progress ?? 0;

        const tr = document.createElement('tr');
        tr.innerHTML =
            '<td class="pz-col-check"><input type="checkbox" class="row-cb" value="' + escHtml(id) + '" data-name="' + nameVal + '"></td>' +
            '<td>0</td>' +
            '<td><strong>' + nameVal + '</strong></td>' +
            '<td>' + type + '</td>' +
            '<td>' + location + '</td>' +
            '<td class="text-center"><span class="progress-badge">' + progress + '%</span></td>' +
            '<td>—</td>' +
            '<td>—</td>' +
            '<td class="text-center" style="white-space:nowrap">' +
                '<a href="' + BASE_URL + 'projects/show/'   + escHtml(id) + '" class="action-btn" title="View"><span class="material-symbols-outlined">visibility</span></a>' +
                '<a href="' + BASE_URL + 'projects/update/' + escHtml(id) + '" class="action-btn" title="Edit"><span class="material-symbols-outlined">edit</span></a>' +
                '<a href="#" data-id="' + escHtml(id) + '" data-name="' + nameVal + '" class="action-btn action-delete" title="Delete"><span class="material-symbols-outlined">delete</span></a>' +
            '</td>';

        // Wire delete button (matches existing pendingDeleteId pattern)
        tr.querySelector('.action-delete')?.addEventListener('click', function(e) {
            e.preventDefault();
            pendingDeleteId = this.dataset.id;
            pendingRow = this.closest('tr');
            confirmMessage.innerText = 'Delete "' + (this.dataset.name || 'this project') + '"?';
            confirmDialog.classList.add('open');
        });

        // Wire checkbox for bulk select
        const newCb = tr.querySelector('.row-cb');
        if (newCb && selectAllCb) {
            newCb.addEventListener('change', function() {
                const all = document.querySelectorAll('.row-cb');
                selectAllCb.checked = all.length > 0 && [...all].every(c => c.checked);
                selectAllCb.indeterminate = getChecked().length > 0 && !selectAllCb.checked;
                updateBulkBar();
            });
        }

        tr.style.opacity = '0';
        tr.style.background = '#edf7e6';
        tbody.appendChild(tr);
        requestAnimationFrame(() => {
            tr.style.transition = 'opacity 300ms ease, background 1.2s ease';
            tr.style.opacity = '1';
            setTimeout(() => { tr.style.background = ''; }, 1000);
        });

        renumberTable(table, 1);
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

    // ── Bulk select ─────────────────────────────────────────────
    const bulkBar     = document.getElementById('bulkActionBar');
    const bulkCount   = document.getElementById('bulkCount');
    const bulkDelBtn  = document.getElementById('bulkDeleteBtn');
    const selectAllCb = document.querySelector('.select-all-cb');

    function getChecked() {
        return Array.from(document.querySelectorAll('.row-cb:checked'));
    }

    function updateBulkBar() {
        const checked = getChecked();
        if (bulkBar) bulkBar.style.display = checked.length > 0 ? 'flex' : 'none';
        if (bulkCount) bulkCount.textContent = checked.length + ' selected';
    }

    selectAllCb && selectAllCb.addEventListener('change', function () {
        document.querySelectorAll('.row-cb').forEach(cb => { cb.checked = this.checked; });
        updateBulkBar();
    });

    document.querySelectorAll('.row-cb').forEach(cb => {
        cb.addEventListener('change', function () {
            if (selectAllCb) {
                const all = document.querySelectorAll('.row-cb');
                selectAllCb.checked = all.length > 0 && [...all].every(c => c.checked);
                selectAllCb.indeterminate = getChecked().length > 0 && !selectAllCb.checked;
            }
            updateBulkBar();
        });
    });

    bulkDelBtn && bulkDelBtn.addEventListener('click', function () {
        const checked = getChecked();
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
            const name = this.dataset.name || 'this project';
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
            const projectsTable = document.getElementById('projectsTable');
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
                    if (done === items.length) {
                        showToast(items.length + ' item(s) deleted', 'success');
                        setTimeout(() => renumberTable(projectsTable, 1), 290);
                    }
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
                showToast('Project deleted', 'success');
                const row = pendingRow;
                animateRowOut(row);
                setTimeout(() => renumberTable(document.getElementById('projectsTable'), 1), 290);
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

    // ── Recently Deleted ────────────────────────────────────────
    const rdToggleBtn = document.getElementById('rdToggleBtn');
    const rdPanel     = document.getElementById('rdPanel');

    rdToggleBtn && rdToggleBtn.addEventListener('click', function () {
        const open = rdPanel && rdPanel.style.display !== 'none';
        if (rdPanel) rdPanel.style.display = open ? 'none' : '';
        const chevron = this.querySelector('.rd-chevron');
        if (chevron) chevron.style.transform = open ? '' : 'rotate(180deg)';
    });

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
                        if (data.id && data.data) addRestoredProjectRow(data.id, data.data);
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
    // ── Export / Import ──────────────────────────────────────────────────────
    const exportBtn = document.getElementById('exportBtn');
    const importBtn = document.getElementById('importBtn');

    exportBtn.addEventListener('click', () => openExportModal(exportBtn.dataset.module));
    importBtn.addEventListener('click', () => openImportModal(importBtn.dataset.module));
});
</script>

<!-- Export Modal -->
<div id="exportModal" style="display:none;position:fixed;inset:0;z-index:3000;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;" hidden>
  <div style="background:#fff;border-radius:12px;padding:28px 32px;min-width:320px;box-shadow:0 8px 32px rgba(0,0,0,.18);">
    <h3 style="margin:0 0 18px;color:#1a3c5e;font-size:17px;">Export Projects</h3>
    <p style="margin:0 0 18px;color:#555;font-size:13px;">Choose a format to download:</p>
    <div style="display:flex;flex-direction:column;gap:10px;">
      <a id="exportXlsx" href="#" target="_blank" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px solid #1a3c5e;border-radius:8px;text-decoration:none;color:#1a3c5e;font-weight:600;font-size:14px;transition:background .15s;">
        <span style="font-size:20px;">📊</span> Download XLSX
      </a>
      <a id="exportCsv" href="#" target="_blank" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px solid #7b5ea7;border-radius:8px;text-decoration:none;color:#7b5ea7;font-weight:600;font-size:14px;transition:background .15s;">
        <span style="font-size:20px;">📋</span> Download CSV
      </a>
      <a id="exportPdf" href="#" target="_blank" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px solid #c0392b;border-radius:8px;text-decoration:none;color:#c0392b;font-weight:600;font-size:14px;transition:background .15s;">
        <span style="font-size:20px;">📄</span> Download PDF
      </a>
      <a id="exportPrint" href="#" target="_blank" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px solid #27ae60;border-radius:8px;text-decoration:none;color:#27ae60;font-weight:600;font-size:14px;transition:background .15s;">
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
    <h3 style="margin:0 0 6px;color:#1a3c5e;font-size:17px;">Import Projects</h3>
    <p style="margin:0 0 14px;color:#555;font-size:13px;">Upload an .xlsx or .csv file. <a id="importTplLink" href="#" style="color:#1a3c5e;font-weight:600;">Download template</a></p>
    <div id="importDropZone" style="border:2px dashed #b0c4d8;border-radius:8px;padding:24px;text-align:center;cursor:pointer;color:#888;font-size:13px;transition:border-color .15s;">
      <div style="font-size:28px;margin-bottom:6px;">📂</div>
      Click or drag &amp; drop your .xlsx or .csv file here
      <input type="file" id="importFileInput" accept=".xlsx,.xls,.csv" style="display:none;">
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

  window.openExportModal = function (module) {
    const m = document.getElementById('exportModal');
    document.getElementById('exportXlsx').href  = baseUrl + 'export?module=' + module + '&format=xlsx';
    document.getElementById('exportCsv').href   = baseUrl + 'export?module=' + module + '&format=csv';
    document.getElementById('exportPdf').href   = baseUrl + 'export?module=' + module + '&format=pdf';
    document.getElementById('exportPrint').href = baseUrl + 'export?module=' + module + '&format=print';
    m.hidden = false;
    m.style.display = 'flex';
  };

  window.closeExportModal = function () {
    const m = document.getElementById('exportModal');
    m.hidden = true;
    m.style.display = 'none';
  };

  window.openImportModal = function (module) {
    currentImportModule = module;
    const m = document.getElementById('importModal');
    document.getElementById('importTplLink').href = baseUrl + 'import/template?module=' + module;
    document.getElementById('importFileName').textContent = '';
    document.getElementById('importResult').textContent = '';
    document.getElementById('importFileInput').value = '';
    m.hidden = false;
    m.style.display = 'flex';
  };

  window.closeImportModal = function () {
    const m = document.getElementById('importModal');
    m.hidden = true;
    m.style.display = 'none';
  };

  window.submitImport = function () {
    const fileInput = document.getElementById('importFileInput');
    const resultEl = document.getElementById('importResult');
    if (!fileInput.files.length) {
      resultEl.style.color = '#c0392b';
      resultEl.textContent = 'Please select a file first.';
      return;
    }
    const btn = document.getElementById('importSubmitBtn');
    btn.disabled = true;
    btn.textContent = 'Importing...';
    resultEl.style.color = '#555';
    resultEl.textContent = 'Processing...';

    const fd = new FormData();
    fd.append('module', currentImportModule);
    fd.append('import_file', fileInput.files[0]);
    if (csrfName) fd.append(csrfName, csrfHash);

    fetch(baseUrl + 'import/process', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        btn.disabled = false;
        btn.textContent = 'Import';
        if (data.success) {
          resultEl.style.color = '#27ae60';
          resultEl.textContent = '✓ ' + data.message;
          setTimeout(() => { closeImportModal(); location.reload(); }, 1800);
        } else {
          resultEl.style.color = '#c0392b';
          resultEl.textContent = '✗ ' + (data.message || 'Import failed.');
        }
      })
      .catch(() => {
        btn.disabled = false;
        btn.textContent = 'Import';
        resultEl.style.color = '#c0392b';
        resultEl.textContent = 'Network error. Try again.';
      });
  };

  // Drop zone
  const dz = document.getElementById('importDropZone');
  const fi = document.getElementById('importFileInput');
  const fn = document.getElementById('importFileName');
  if (dz) {
    dz.addEventListener('click', () => fi.click());
    dz.addEventListener('dragover', e => { e.preventDefault(); dz.style.borderColor = '#1a3c5e'; });
    dz.addEventListener('dragleave', () => { dz.style.borderColor = '#b0c4d8'; });
    dz.addEventListener('drop', e => {
      e.preventDefault();
      dz.style.borderColor = '#b0c4d8';
      if (e.dataTransfer.files.length) { fi.files = e.dataTransfer.files; fn.textContent = '📎 ' + fi.files[0].name; }
    });
    fi.addEventListener('change', () => { if (fi.files.length) fn.textContent = '📎 ' + fi.files[0].name; });
  }

  // Close on backdrop click
  ['exportModal', 'importModal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function (e) {
      if (e.target === this) {
        this.hidden = true;
        this.style.display = 'none';
      }
    });
  });
}());
</script>

<?= $this->endSection() ?>
