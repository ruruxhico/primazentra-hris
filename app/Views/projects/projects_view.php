<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="customToast" class="custom-toast" aria-live="polite" aria-atomic="true"></div>

<meta name="csrf-name" content="<?= csrf_token() ?>">
<meta name="csrf-hash" content="<?= csrf_hash() ?>">

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

<div class="resource-header">
    <h1>Projects</h1>
    <?php if (($isPm ?? false)): ?>
    <div style="margin-left:auto;display:flex;align-items:center;gap:.5rem;">
        <?php if (!($isViewAll ?? false)): ?>
            <span style="font-size:.82rem;color:#666;">Showing your assigned projects.</span>
            <a href="<?= base_url('projects?view_all=1') ?>" class="btn-toolbar" style="font-size:.82rem;padding:5px 12px;">View All Ongoing</a>
        <?php else: ?>
            <span style="font-size:.82rem;color:#666;">Showing all projects.</span>
            <a href="<?= base_url('projects') ?>" class="btn-toolbar" style="font-size:.82rem;padding:5px 12px;">My Projects Only</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert-success">✓ <?= session()->getFlashdata('success') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert-error">✗ <?= session()->getFlashdata('error') ?></div>
<?php endif; ?>


<?php
    $activeFilters  = $activeFilters  ?? [];
    $activeCount    = count(array_filter($activeFilters));
    $pmOptions      = $pmOptions      ?? [];
    $seOptions      = $seOptions      ?? [];
    $clientOptions  = $clientOptions  ?? [];
    $af             = $activeFilters;
?>

<div class="page-module">
<div class="rv-controls">
    <div class="rv-search-wrap">
        <span class="material-symbols-outlined">search</span>
        <input type="text" id="searchProjects" placeholder="" class="rv-search">
    </div>

    <button type="button" class="rv-btn rv-btn-filter" id="filterToggleBtn">
        <span class="material-symbols-outlined">tune</span>
        Filter<?php if ($activeCount > 0): ?><span class="filter-badge"><?= $activeCount ?></span><?php endif; ?>
    </button>

    <?php if (pz_can('create_project')): ?>
    <a href="<?= base_url('projects/create') ?>" class="rv-btn rv-btn-add">
        <span class="material-symbols-outlined">add</span> Add
    </a>
    <?php endif; ?>

    <button class="rv-btn rv-btn-import" id="importBtn" data-module="projects">
        <span class="material-symbols-outlined">download</span> Import
    </button>

    <button class="rv-btn rv-btn-export" id="exportBtn" data-module="projects">
        <span class="material-symbols-outlined">upload</span> Export
    </button>

    <div class="rv-btn-group-view">
        <a href="<?= base_url('projects/deleted') ?>" class="rv-btn-trash">
            <span class="material-symbols-outlined">restore_from_trash</span> Deleted
        </a>
        <a href="<?= base_url('projects/archived') ?>" class="rv-btn-archive">
            <span class="material-symbols-outlined">inventory_2</span> Archive
        </a>
    </div>
</div>

<!-- Filter Panel -->
<div id="filterPanel" class="filter-panel" style="display:none">
    <form id="filterForm" method="get" action="<?= base_url('projects') ?>">
        <div class="filter-grid">

            <div class="filter-group">
                <label>Status</label>
                <select name="status">
                    <option value="">All Statuses</option>
                    <?php foreach (['Planned' => 'Planned', 'On-going' => 'On-going', 'Completed' => 'Completed', 'Past Deadline' => 'Past Deadline', 'Archived' => 'Archived'] as $val => $label): ?>
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
                    <?php foreach (['BUILD-TO-SUIT' => 'Build-to-Suit', 'RETROFITTING' => 'Retrofitting', 'COLOCATION' => 'Colocation', 'CONVERSION' => 'Conversion'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= ($af['type'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (!empty($clientOptions)): ?>
            <div class="filter-group">
                <label>Client</label>
                <select name="client">
                    <option value="">All Clients</option>
                    <?php foreach ($clientOptions as $val => $label): ?>
                    <option value="<?= esc($val) ?>" <?= ($af['client'] ?? '') === $val ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

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
<div class="pager-section" id="projects-section">
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
                            <?php if (pz_can('edit_project')): ?>
                            <a href="<?= base_url('projects/update/' . $project['id']) ?>" class="action-btn" title="Edit"><span class="material-symbols-outlined">edit</span></a>
                            <button type="button" class="action-btn action-archive" data-id="<?= esc(view_text($project['id'] ?? '')) ?>" data-name="<?= esc(view_text($project['project_name'] ?? '')) ?>" title="Archive"><span class="material-symbols-outlined">inventory_2</span></button>
                            <a href="#" data-id="<?= esc(view_text($project['id'] ?? '')) ?>" data-name="<?= esc(view_text($project['project_name'] ?? '')) ?>" class="action-btn action-delete" title="Delete"><span class="material-symbols-outlined">delete</span></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="pager-wrapper">
    <?php
        $projectsTotal = $projectsTotal ?? count($projects ?? []);
        $perPage       = $perPage ?? 20;
        $pagerOffset   = $pagerOffset ?? 0;
        $showingFrom   = $projectsTotal > 0 ? $pagerOffset + 1 : 0;
        $showingTo     = min($pagerOffset + $perPage, $projectsTotal);
    ?>
    <span class="pager-info">Showing <?= $showingFrom ?>–<?= $showingTo ?> of <?= $projectsTotal ?> records</span>
    <?= service('pager')->links('projects', 'bootstrap_full') ?>
    <?php $projTotalPages = $projectsTotal > 0 ? (int)ceil($projectsTotal / $perPage) : 1; ?>
    <?php if ($projTotalPages > 1): ?>
    <div class="pager-jump">
        Go to page
        <input type="number" class="pager-jump-input" min="1" max="<?= $projTotalPages ?>"
               data-total-pages="<?= $projTotalPages ?>" data-page-param="page" placeholder="—">
        <span>of <?= $projTotalPages ?></span>
    </div>
    <?php endif; ?>
</div>
</div><!-- /.pager-section#projects-section -->

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
    function setConfirmType(type) {
        const isArchive = type === 'archive';
        confirmDialog.classList.toggle('confirm-archive', isArchive);
        var icon  = document.getElementById('confirmIcon');
        var title = document.getElementById('confirmTitle');
        if (icon)  icon.textContent  = isArchive ? 'inventory_2' : 'delete_forever';
        if (title) title.textContent = isArchive ? 'Archive' : 'Delete';
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

    const bulkArchBtn = document.getElementById('bulkArchiveBtn');
    bulkArchBtn && bulkArchBtn.addEventListener('click', function () {
        const checked = getChecked();
        if (!checked.length) return;
        const names = checked.map(c => c.dataset.name || c.value).join(', ');
        setConfirmType('archive');
        confirmMessage.innerText = 'Archive ' + checked.length + ' item(s)?\n' + names;
        pendingDeleteId = '__bulk_archive__';
        pendingBulkArchiveItems = checked.map(c => ({ id: c.value, row: c.closest('tr') }));
        confirmDialog.classList.add('open');
    });

    bulkDelBtn && bulkDelBtn.addEventListener('click', function () {
        const checked = getChecked();
        if (!checked.length) return;
        const names = checked.map(c => c.dataset.name || c.value).join(', ');
        setConfirmType('delete');
        confirmMessage.innerText = 'Delete ' + checked.length + ' item(s)?\n' + names;
        pendingDeleteId = '__bulk__';
        pendingBulkItems = checked.map(c => ({ id: c.value, row: c.closest('tr') }));
        confirmDialog.classList.add('open');
    });

    // ── Single delete ─────────────────────────────────────────────
    let pendingDeleteId       = null;
    let pendingRow            = null;
    let pendingBulkItems      = [];
    let pendingArchiveId      = null;
    let pendingArchiveRow     = null;
    let pendingBulkArchiveItems = [];

    document.querySelectorAll('.action-delete').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            pendingDeleteId = this.dataset.id;
            pendingRow = this.closest('tr');
            const name = this.dataset.name || 'this project';
            setConfirmType('delete');
            confirmMessage.innerText = 'Delete "' + name + '"?';
            confirmDialog.classList.add('open');
        });
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
            const name = row?.dataset?.name || id;
            pendingArchiveId = null; pendingArchiveRow = null;
            confirmDialog.classList.remove('open');
            const body = new URLSearchParams();
            if (csrfName && csrfHash) body.append(csrfName, csrfHash);
            body.append('id', id);
            fetch(ARCHIVE_URL, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString()
            })
            .then(r => r.json())
            .then(data => {
                if (data && data.success) {
                    showToast('"' + name + '" archived', 'success');
                    animateRowOut(row);
                    setTimeout(() => renumberTable(document.getElementById('projectsTable'), 1), 290);
                } else {
                    showToast(data.message || 'Archive failed', 'error');
                }
            })
            .catch(() => showToast('Error archiving project', 'error'));
            return;
        }

        if (!pendingDeleteId) return;

        // Bulk archive path
        if (pendingDeleteId === '__bulk_archive__') {
            const items = [...pendingBulkArchiveItems];
            pendingBulkArchiveItems = []; pendingDeleteId = null;
            confirmDialog.classList.remove('open');
            const projectsTable = document.getElementById('projectsTable');
            let done = 0;
            items.forEach(item => {
                const body = new URLSearchParams();
                if (csrfName && csrfHash) body.append(csrfName, csrfHash);
                body.append('id', item.id);
                fetch(ARCHIVE_URL, {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: body.toString()
                })
                .then(r => r.json())
                .then(data => { if (data && data.success) animateRowOut(item.row); })
                .finally(() => {
                    done++;
                    if (done === items.length) {
                        showToast(items.length + ' item(s) archived', 'success');
                        setTimeout(() => renumberTable(projectsTable, 1), 290);
                    }
                });
            });
            return;
        }

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

    // ── Archive ──────────────────────────────────────────────────────────────
    const ARCHIVE_URL = '<?= base_url('projects/archive') ?>';
    document.querySelectorAll('.action-archive').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const row  = this.closest('tr');
            row.dataset.name = this.dataset.name || 'this project';
            pendingArchiveId  = this.dataset.id;
            pendingArchiveRow = row;
            setConfirmType('archive');
            confirmMessage.innerText = 'Archive "' + (this.dataset.name || 'this project') + '"? It will be removed from active projects and stored in the archive.';
            confirmDialog.classList.add('open');
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
<div id="exportModal" class="pz-modal-overlay" style="display:none" hidden>
  <div class="pz-modal-box">
    <h3 class="pz-modal-title">Export Projects</h3>
    <p class="pz-modal-subtitle">Choose a format to download:</p>
    <div class="pz-modal-export-list">
      <a id="exportXlsx" href="#" target="_blank" class="pz-export-link pz-export-link--xlsx">
        <span class="pz-export-link-icon">📊</span> Download XLSX
      </a>
      <a id="exportCsv" href="#" target="_blank" class="pz-export-link pz-export-link--csv">
        <span class="pz-export-link-icon">📋</span> Download CSV
      </a>
      <a id="exportPdf" href="#" target="_blank" class="pz-export-link pz-export-link--pdf">
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
<div id="importModal" class="pz-modal-overlay" style="display:none" hidden>
  <div class="pz-modal-box">
    <h3 class="pz-modal-title">Import Projects</h3>
    <p class="pz-modal-subtitle">Upload an .xlsx or .csv file. <a id="importTplLink" href="#">Download template</a></p>
    <div id="importDropZone" class="pz-modal-drop-zone">
      <div style="font-size:28px;margin-bottom:6px;">📂</div>
      Click or drag &amp; drop your .xlsx or .csv file here
      <input type="file" id="importFileInput" accept=".xlsx,.xls,.csv" style="display:none;">
    </div>
    <div id="importFileName" class="pz-modal-file-name"></div>
    <div id="importResult" class="pz-modal-result"></div>
    <div class="pz-modal-actions">
      <button onclick="closeImportModal()" class="pz-btn-modal-cancel">Cancel</button>
      <button id="importSubmitBtn" onclick="submitImport()" class="pz-btn-modal-import-navy">Import</button>
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
