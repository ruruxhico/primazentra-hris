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
    <h1>Staff</h1>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert-success">✓ <?= session()->getFlashdata('success') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert-error">✗ <?= session()->getFlashdata('error') ?></div>
<?php endif; ?>


<?php
    $activeFilters = $activeFilters ?? [];
    $activeCount   = count(array_filter($activeFilters));
    $roleOptions   = $roleOptions   ?? [];
    $af            = $activeFilters;
?>

<div class="page-module">
<div class="rv-controls">
    <div class="rv-search-wrap">
        <span class="material-symbols-outlined">search</span>
        <input type="text" id="searchStaff" placeholder="Search staff…" class="rv-search">
    </div>

    <button type="button" class="rv-btn rv-btn-filter" id="filterToggleBtn">
        <span class="material-symbols-outlined">tune</span>
        Filter<?php if ($activeCount > 0): ?><span class="filter-badge"><?= $activeCount ?></span><?php endif; ?>
    </button>

    <?php if (pz_can('create_staff')): ?>
    <a href="<?= base_url('staff/create') ?>" class="rv-btn rv-btn-add">
        <span class="material-symbols-outlined">add</span> Add
    </a>
    <?php endif; ?>

    <button class="rv-btn rv-btn-import" id="staffImportBtn">
        <span class="material-symbols-outlined">download</span> Import
    </button>

    <button class="rv-btn rv-btn-export" data-module="staff" onclick="openStaffExportModal()">
        <span class="material-symbols-outlined">upload</span> Export
    </button>

    <div class="rv-btn-group-view">
        <a href="<?= base_url('staff/deleted') ?>" class="rv-btn-trash">
            <span class="material-symbols-outlined">restore_from_trash</span> Deleted
        </a>
        <a href="<?= base_url('staff/archived') ?>" class="rv-btn-archive">
            <span class="material-symbols-outlined">inventory_2</span> Archive
        </a>
    </div>
</div>

<!-- Filter Panel -->
<div id="filterPanel" class="filter-panel" style="display:none">
    <form id="filterForm" method="get" action="<?= base_url('staff') ?>">
        <input type="hidden" id="lnRangeInput" name="ln_range" value="<?= esc($af['ln_range'] ?? '') ?>">

        <div class="filter-rows">

            <div class="filter-row">
                <label>Last Name</label>
                <div class="ln-ranges">
                    <?php foreach (['A-C','D-F','G-I','J-L','M-O','P-R','S-U','V-Z'] as $range): ?>
                    <button type="button"
                            class="ln-chip <?= ($af['ln_range'] ?? '') === $range ? 'active' : '' ?>"
                            data-range="<?= $range ?>"><?= $range ?></button>
                    <?php endforeach; ?>
                </div>
                <label style="min-width:unset">Role</label>
                <select name="role_filter" style="flex:1;min-width:120px">
                    <option value="">All Roles</option>
                    <?php foreach ($roleOptions as $r): ?>
                    <option value="<?= esc($r) ?>" <?= ($af['role_filter'] ?? '') === $r ? 'selected' : '' ?>><?= esc($r) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="filter-footer">
            <a href="<?= base_url('staff') ?>" class="btn btn-secondary">Reset</a>
        </div>
    </form>
</div>

<!-- Staff Table -->
<div class="pager-section" id="staff-section">
<div class="table-container">
    <table id="staffTable" class="resource-table" data-offset="<?= $pagerOffset ?? 0 ?>">
        <thead>
            <tr>
                <th class="pz-col-check"><input type="checkbox" class="select-all-cb" title="Select all"></th>
                <th style="width:44px"></th>
                <th>NO.</th>
                <th>LAST NAME</th>
                <th>FIRST NAME</th>
                <th>MIDDLE NAME</th>
                <th>EMAIL</th>
                <th>ROLE</th>
                <th class="text-center">ACTIONS</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($staff)): ?>
                <tr>
                    <td colspan="9" class="empty-state">
                        No staff members found. <a href="<?= base_url('staff/create') ?>">Add one now</a>
                    </td>
                </tr>
            <?php else: ?>
                <?php $offset = $pagerOffset ?? 0; ?>
                <?php foreach ($staff as $index => $person): ?>
                    <?php
                        $pic = $person['profile_picture'] ?? '';
                        $ini = strtoupper(substr((string)($person['first_name'] ?? 'S'), 0, 1));
                    ?>
                    <tr>
                        <td class="pz-col-check">
                            <input type="checkbox" class="row-cb" value="<?= esc((string)$person['id']) ?>" data-name="<?= esc(trim(($person['first_name'] ?? '') . ' ' . ($person['last_name'] ?? ''))) ?>">
                        </td>
                        <td style="padding:4px 8px">
                            <?php if ($pic !== ''): ?>
                                <img src="<?= asset_url($pic) ?>" alt=""
                                     style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid #d0dce8;display:block">
                            <?php else: ?>
                                <div style="width:32px;height:32px;border-radius:50%;background:#e8eef5;border:2px solid #d0dce8;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#1a3c5e"><?= esc($ini) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= $offset + $index + 1 ?></td>
                        <td><strong><?= esc((string)($person['last_name'] ?? 'N/A')) ?></strong></td>
                        <td><?= esc((string)($person['first_name'] ?? 'N/A')) ?></td>
                        <td><?= esc((string)($person['middle_name'] ?? 'N/A')) ?></td>
                        <td><?= esc((string)($person['email'] ?? 'N/A')) ?></td>
                        <?php
                            $roleVal   = (string)($person['role'] ?? '');
                            $roleClass = match($roleVal) {
                                'Administrator'    => 'role-badge--administrator',
                                'Operations Manager' => 'role-badge--ops-manager',
                                'Project Manager'  => 'role-badge--project-manager',
                                'Site Engineer'    => 'role-badge--site-engineer',
                                default            => '',
                            };
                        ?>
                        <td>
                            <span class="role-badge <?= $roleClass ?>">
                                <?= esc($roleVal ?: 'Staff') ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="<?= base_url('staff/show/' . $person['id']) ?>" class="action-btn" title="View"><span class="material-symbols-outlined">visibility</span></a>
                            <a href="<?= base_url('staff/update/' . $person['id']) ?>" class="action-btn" title="Edit"><span class="material-symbols-outlined">edit</span></a>
                            <button type="button" class="action-btn action-archive" data-id="<?= esc((string)$person['id']) ?>" data-name="<?= esc((string)(($person['first_name'] ?? '') . ' ' . ($person['last_name'] ?? ''))) ?>" title="Archive"><span class="material-symbols-outlined">inventory_2</span></button>
                            <a href="#" data-id="<?= esc((string)$person['id']) ?>" data-name="<?= esc((string)(($person['first_name'] ?? '') . ' ' . ($person['last_name'] ?? ''))) ?>" class="action-btn action-delete" title="Delete"><span class="material-symbols-outlined">delete</span></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="pager-wrapper">
    <?php
        $staffTotal  = $staffTotal ?? count($staff ?? []);
        $perPage     = $perPage ?? 20;
        $pagerOffset = $pagerOffset ?? 0;
        $showingFrom = $staffTotal > 0 ? $pagerOffset + 1 : 0;
        $showingTo   = min($pagerOffset + $perPage, $staffTotal);
    ?>
    <span class="pager-info">Showing <?= $showingFrom ?>–<?= $showingTo ?> of <?= $staffTotal ?> records</span>
    <?= service('pager')->links('staff', 'bootstrap_full') ?>
    <?php $staffTotalPages = $staffTotal > 0 ? (int)ceil($staffTotal / $perPage) : 1; ?>
    <?php if ($staffTotalPages > 1): ?>
    <div class="pager-jump">
        Go to page
        <input type="number" class="pager-jump-input" min="1" max="<?= $staffTotalPages ?>"
               data-total-pages="<?= $staffTotalPages ?>" data-page-param="page" placeholder="—">
        <span>of <?= $staffTotalPages ?></span>
    </div>
    <?php endif; ?>
</div>
</div><!-- /.pager-section#staff-section -->

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
    const DELETE_URL  = '<?= base_url('staff/delete') ?>';
    const BASE_URL    = '<?= base_url() ?>';
    const ASSET_BASE  = '<?= asset_url('') ?>';

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

    // — Auto-submit: role dropdown → immediate; ln chips → toggle + immediate —
    if (filterForm) {
        filterForm.querySelectorAll('select').forEach(function (sel) {
            sel.addEventListener('change', function () { filterForm.submit(); });
        });

        const lnRangeInput = document.getElementById('lnRangeInput');
        filterForm.querySelectorAll('.ln-chip').forEach(function (chip) {
            chip.addEventListener('click', function () {
                const range = this.dataset.range;
                lnRangeInput.value = lnRangeInput.value === range ? '' : range;
                filterForm.submit();
            });
        });
    }

    const search = document.getElementById('searchStaff');
    const table = document.getElementById('staffTable');
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

    function wireRowCb(cb) {
        cb.addEventListener('change', function () {
            if (selectAllCb) {
                const all = document.querySelectorAll('.row-cb');
                selectAllCb.checked = all.length > 0 && [...all].every(c => c.checked);
                selectAllCb.indeterminate = getChecked().length > 0 && !selectAllCb.checked;
            }
            updateBulkBar();
        });
    }
    document.querySelectorAll('.row-cb').forEach(wireRowCb);

    // ── Promise-based custom confirm (no native browser dialogs) ──
    let _confirmResolve = null;
    function openConfirm(message) {
        if (_confirmResolve) return Promise.resolve(false); // block overlap
        return new Promise(resolve => {
            _confirmResolve = resolve;
            confirmMessage.innerText = message;
            confirmDialog.classList.add('open');
        });
    }
    confirmOk?.addEventListener('click', function () {
        if (!_confirmResolve) return;
        const r = _confirmResolve; _confirmResolve = null;
        confirmDialog.classList.remove('open');
        r(true);
    });
    confirmCancel?.addEventListener('click', function () {
        if (!_confirmResolve) return;
        const r = _confirmResolve; _confirmResolve = null;
        confirmDialog.classList.remove('open');
        r(false);
    });

    // ── Bulk archive ──────────────────────────────────────────────
    const bulkArchBtn = document.getElementById('bulkArchiveBtn');
    bulkArchBtn && bulkArchBtn.addEventListener('click', async function () {
        const checked = getChecked();
        if (!checked.length) return;
        const names = checked.map(c => c.dataset.name || c.value).join(', ');
        if (!await openConfirm('Archive ' + checked.length + ' item(s)?\n' + names)) return;

        const items = checked.map(c => ({ id: c.value, row: c.closest('tr') }));
        document.querySelectorAll('.row-cb').forEach(c => { c.checked = false; });
        if (selectAllCb) selectAllCb.checked = false;
        updateBulkBar();

        const staffTable = document.getElementById('staffTable');
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
                    setTimeout(() => renumberTable(staffTable, 2), 290);
                }
            });
        });
    });

    // ── Bulk delete ───────────────────────────────────────────────
    bulkDelBtn && bulkDelBtn.addEventListener('click', async function () {
        const checked = getChecked();
        if (!checked.length) return;
        const names = checked.map(c => c.dataset.name || c.value).join(', ');
        if (!await openConfirm('Delete ' + checked.length + ' item(s)?\n' + names)) return;

        const items = checked.map(c => ({ id: c.value, row: c.closest('tr') }));
        document.querySelectorAll('.row-cb').forEach(c => { c.checked = false; });
        if (selectAllCb) selectAllCb.checked = false;
        updateBulkBar();

        let done = 0;
        const staffTable = document.getElementById('staffTable');
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
            .then(data => {
                if (data && data.success) {
                    animateRowOut(item.row);
                }
            })
            .finally(() => {
                done++;
                if (done === items.length) {
                    showToast(items.length + ' item(s) deleted', 'success');
                    setTimeout(() => renumberTable(staffTable, 2), 290);
                }
            });
        });
    });

    // ── Single delete ─────────────────────────────────────────────
    function wireDeleteBtn(btn) {
        btn.addEventListener('click', async function (e) {
            e.preventDefault();
            const id   = this.dataset.id;
            const row  = this.closest('tr');
            const name = this.dataset.name || 'this staff member';

            if (!await openConfirm('Delete "' + name + '"?')) return;

            const body = new URLSearchParams();
            if (csrfName && csrfHash) body.append(csrfName, csrfHash);
            body.append('id', id);

            fetch(DELETE_URL, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString()
            })
            .then(r => r.json())
            .then(data => {
                if (data && data.success) {
                    showToast('Staff deleted', 'success');
                    animateRowOut(row);
                    setTimeout(() => renumberTable(document.getElementById('staffTable'), 2), 290);
                } else {
                    showToast(data.message || 'Delete failed', 'error');
                }
            })
            .catch(() => showToast('Error deleting staff', 'error'));
        });
    }
    document.querySelectorAll('.action-delete').forEach(wireDeleteBtn);

    // ── Archive ───────────────────────────────────────────────
    const ARCHIVE_URL = '<?= base_url('staff/archive') ?>';
    document.querySelectorAll('.action-archive').forEach(function (btn) {
        btn.addEventListener('click', async function (e) {
            e.preventDefault();
            const id   = this.dataset.id;
            const name = this.dataset.name || 'this staff member';
            const row  = this.closest('tr');
            if (!await openConfirm('Archive "' + name + '"? They will be moved to the archive and removed from active records.')) return;
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
                    setTimeout(() => renumberTable(document.getElementById('staffTable'), 2), 290);
                } else {
                    showToast(data.message || 'Archive failed', 'error');
                }
            })
            .catch(() => showToast('Error archiving staff', 'error'));
        });
    });

});
</script>

<!-- Import Modal -->
<div id="importModal" class="pz-modal-overlay" style="display:none" hidden>
  <div class="pz-modal-box">
    <h3 class="pz-modal-title">Import Staff</h3>
    <p class="pz-modal-subtitle">Upload an .xlsx or .csv file. <a id="importTplLink" href="<?= base_url('import/template?module=staff') ?>">Download template</a></p>
    <div id="importDropZone" class="pz-modal-drop-zone">
      <div style="font-size:28px;margin-bottom:6px;">📂</div>
      Click or drag &amp; drop your .xlsx or .csv file here
      <input type="file" id="importFileInput" accept=".xlsx,.xls,.csv" style="display:none;">
    </div>
    <div id="importFileName" class="pz-modal-file-name"></div>
    <div id="importResult"   class="pz-modal-result"></div>
    <div class="pz-modal-actions">
      <button onclick="closeImportModal()" class="pz-btn-modal-cancel">Cancel</button>
      <button id="importSubmitBtn" onclick="submitImport()" class="pz-btn-modal-import">Import</button>
    </div>
  </div>
</div>

<script>
(function () {
  const baseUrl   = '<?= base_url() ?>';
  const csrfName  = document.querySelector('meta[name="csrf-name"]')?.content;
  const csrfHash  = document.querySelector('meta[name="csrf-hash"]')?.content;

  document.getElementById('staffImportBtn')?.addEventListener('click', function () {
    const m = document.getElementById('importModal');
    document.getElementById('importFileName').textContent = '';
    document.getElementById('importResult').textContent   = '';
    document.getElementById('importFileInput').value      = '';
    m.hidden = false; m.style.display = 'flex';
  });

  window.closeImportModal = function () {
    const m = document.getElementById('importModal');
    m.hidden = true; m.style.display = 'none';
  };

  window.submitImport = function () {
    const fi  = document.getElementById('importFileInput');
    const re  = document.getElementById('importResult');
    if (!fi.files.length) { re.style.color='#c0392b'; re.textContent='Please select a file first.'; return; }
    const btn = document.getElementById('importSubmitBtn');
    btn.disabled = true; btn.textContent = 'Importing…';
    re.style.color = '#555'; re.textContent = 'Processing…';
    const fd = new FormData();
    fd.append('module', 'staff');
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
    dz.addEventListener('dragover', e => { e.preventDefault(); dz.style.borderColor='#C47A0F'; });
    dz.addEventListener('dragleave', () => { dz.style.borderColor='#d4c185'; });
    dz.addEventListener('drop', e => { e.preventDefault(); dz.style.borderColor='#d4c185'; if(e.dataTransfer.files.length){ fi.files=e.dataTransfer.files; fn.textContent='📎 '+fi.files[0].name; } });
    fi.addEventListener('change', () => { if(fi.files.length) fn.textContent='📎 '+fi.files[0].name; });
  }
  document.getElementById('importModal')?.addEventListener('click', function(e){ if(e.target===this){this.hidden=true;this.style.display='none';} });
}());
</script>

<!-- Export Modal -->
<div id="exportModal" class="pz-modal-overlay" style="display:none" hidden>
  <div class="pz-modal-box">
    <h3 class="pz-modal-title">Export Staff</h3>
    <p class="pz-modal-subtitle">Choose a format to download:</p>
    <div class="pz-modal-export-list">
      <a id="exportXlsx" href="<?= base_url('export?module=staff&format=xlsx') ?>" target="_blank" class="pz-export-link pz-export-link--xlsx">
        <span class="pz-export-link-icon">📊</span> Download XLSX
      </a>
      <a id="exportCsv" href="<?= base_url('export?module=staff&format=csv') ?>" target="_blank" class="pz-export-link pz-export-link--csv">
        <span class="pz-export-link-icon">📋</span> Download CSV
      </a>
      <a id="exportPdf" href="<?= base_url('export?module=staff&format=pdf') ?>" target="_blank" class="pz-export-link pz-export-link--pdf">
        <span class="pz-export-link-icon">📄</span> Download PDF
      </a>
      <a id="exportPrint" href="<?= base_url('export?module=staff&format=print') ?>" target="_blank" class="pz-export-link pz-export-link--print">
        <span class="pz-export-link-icon">🖨️</span> Open Printable View
      </a>
    </div>
    <div class="pz-modal-close-row">
      <button onclick="document.getElementById('exportModal').hidden=true;document.getElementById('exportModal').style.display='none';" class="pz-btn-modal-cancel">Close</button>
    </div>
  </div>
</div>

<script>
function openStaffExportModal() {
  const m = document.getElementById('exportModal');
  m.hidden = false; m.style.display = 'flex';
}
document.getElementById('exportModal')?.addEventListener('click', function(e) {
  if (e.target === this) { this.hidden = true; this.style.display = 'none'; }
});
</script>

<?= $this->endSection() ?>
