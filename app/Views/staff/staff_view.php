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
        <input type="text" id="searchStaff" placeholder="" class="rv-search">
    </div>

    <button type="button" class="rv-btn rv-btn-filter" id="filterToggleBtn">
        <span class="material-symbols-outlined">tune</span>
        Filter<?php if ($activeCount > 0): ?><span class="filter-badge"><?= $activeCount ?></span><?php endif; ?>
    </button>

    <?php if (pz_can('create_staff')): ?>
    <a href="<?= base_url('staff/create') ?>" class="rv-btn rv-btn-add">
        <span class="material-symbols-outlined">add</span> Add
    </a>
    <button type="button" class="rv-btn rv-btn-invite" id="inviteLinkBtn">
        <span class="material-symbols-outlined">link</span> Invite Admin
    </button>
    <?php endif; ?>

    <button class="rv-btn rv-btn-export" data-module="staff" onclick="openStaffExportModal()">
        <span class="material-symbols-outlined">upload</span> Export
    </button>

    <?php if (pz_can('edit_staff')): ?>
    <div class="rv-btn-group-view">
        <a href="<?= base_url('staff/deleted') ?>" class="rv-btn-trash">
            <span class="material-symbols-outlined">restore_from_trash</span> Deleted
        </a>
        <a href="<?= base_url('staff/archived') ?>" class="rv-btn-archive">
            <span class="material-symbols-outlined">inventory_2</span> Archive
        </a>
    </div>
    <?php endif; ?>

    <div class="pager-top">
        <span>Show</span>
        <input type="number" class="pager-per-page" value="<?= $perPage ?? 20 ?>" min="5" max="200" title="Rows per page — press Enter to apply">
        <span>records per page</span>
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
                    <tr data-staff-id="<?= esc((string)($person['id'] ?? '')) ?>"
                        data-staff-role-val="<?= esc(strtolower($person['role'] ?? '')) ?>"
                        data-staff-ln-first="<?= esc(strtolower(substr((string)($person['last_name'] ?? ''), 0, 1))) ?>">
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
                        <td data-staff-ln><strong><?= esc((string)($person['last_name'] ?? 'N/A')) ?></strong></td>
                        <td data-staff-fn><?= esc((string)($person['first_name'] ?? 'N/A')) ?></td>
                        <td><?= esc((string)($person['middle_name'] ?? 'N/A')) ?></td>
                        <td data-staff-email><?= esc((string)($person['email'] ?? 'N/A')) ?></td>
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
                            <span class="role-badge <?= $roleClass ?>" data-staff-role>
                                <?= esc($roleVal ?: 'Staff') ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="action-wrap">
                                <a href="<?= base_url('staff/show/' . $person['id']) ?>" class="action-btn" title="View"><span class="material-symbols-outlined">visibility</span></a>
                                <?php if (pz_can('edit_staff')): ?>
                                <a href="<?= base_url('staff/update/' . $person['id']) ?>" class="action-btn" title="Edit"><span class="material-symbols-outlined">edit</span></a>
                                <button type="button" class="action-btn action-archive" data-id="<?= esc((string)$person['id']) ?>" data-name="<?= esc((string)(($person['first_name'] ?? '') . ' ' . ($person['last_name'] ?? ''))) ?>" title="Archive"><span class="material-symbols-outlined">inventory_2</span></button>
                                <a href="#" data-id="<?= esc((string)$person['id']) ?>" data-name="<?= esc((string)(($person['first_name'] ?? '') . ' ' . ($person['last_name'] ?? ''))) ?>" class="action-btn action-delete" title="Delete"><span class="material-symbols-outlined">delete</span></a>
                                <?php endif; ?>
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
<?php if (pz_can('edit_staff')): ?>
<div class="bulk-action-bar" id="bulkActionBar" style="display:none">
    <span class="bulk-count" id="bulkCount">0 selected</span>
    <button type="button" class="bulk-archive-btn" id="bulkArchiveBtn">
        <span class="material-symbols-outlined">inventory_2</span> Archive Selected
    </button>
    <button type="button" class="bulk-delete-btn" id="bulkDeleteBtn">
        <span class="material-symbols-outlined">delete</span> Delete Selected
    </button>
</div>
<?php endif; ?>

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

    const search = document.getElementById('searchStaff');
    const table  = document.getElementById('staffTable');
    const staffFilters = { role: '', lnRange: '' };

    function applyStaffFilters() {
        const q = search ? search.value.trim().toLowerCase() : '';
        table?.querySelectorAll('tbody tr[data-staff-id]').forEach(row => {
            let show = true;
            if (q && !row.innerText.toLowerCase().includes(q)) show = false;
            if (staffFilters.role) {
                if ((row.dataset.staffRoleVal||'') !== staffFilters.role.toLowerCase()) show = false;
            }
            if (staffFilters.lnRange) {
                const first = row.dataset.staffLnFirst || '';
                const parts = staffFilters.lnRange.split('-');
                if (parts.length === 2 && (first < parts[0].toLowerCase() || first > parts[1].toLowerCase())) show = false;
            }
            row.style.display = show ? '' : 'none';
        });
    }

    if (search) search.addEventListener('input', applyStaffFilters);

    if (filterForm) {
        const roleSelect = filterForm.querySelector('[name="role_filter"]');
        if (roleSelect) roleSelect.addEventListener('change', function () {
            staffFilters.role = this.value;
            applyStaffFilters();
        });

        filterForm.querySelectorAll('.ln-chip').forEach(function (chip) {
            chip.addEventListener('click', function () {
                const range = this.dataset.range;
                staffFilters.lnRange = staffFilters.lnRange === range ? '' : range;
                filterForm.querySelectorAll('.ln-chip').forEach(c => c.classList.toggle('active', c.dataset.range === staffFilters.lnRange));
                applyStaffFilters();
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
    const confirmIcon  = document.getElementById('confirmIcon');
    const confirmTitle = document.getElementById('confirmTitle');
    let _confirmResolve = null;
    function openConfirm(message, type = 'delete') {
        if (_confirmResolve) return Promise.resolve(false);
        return new Promise(resolve => {
            _confirmResolve = resolve;
            confirmMessage.innerText = message;
            const isArchive = type === 'archive';
            confirmDialog.classList.toggle('confirm-archive', isArchive);
            if (confirmIcon)  { confirmIcon.textContent  = isArchive ? 'inventory_2' : 'delete_forever'; }
            if (confirmTitle) { confirmTitle.textContent = isArchive ? 'Archive' : 'Delete'; }
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
        if (!await openConfirm('Archive ' + checked.length + ' item(s)?\n' + names, 'archive')) return;

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

    // ── Single delete (event delegation — works for live-added rows too) ──
    document.addEventListener('click', async function (e) {
        var btn = e.target.closest('.action-delete');
        if (!btn) return;
        e.preventDefault();
        const id   = btn.dataset.id;
        const row  = btn.closest('tr');
        const name = btn.dataset.name || 'this staff member';

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

    // ── Archive (event delegation — works for live-added rows too) ────────
    const ARCHIVE_URL = '<?= base_url('staff/archive') ?>';
    document.addEventListener('click', async function (e) {
        var btn = e.target.closest('.action-archive');
        if (!btn) return;
        e.preventDefault();
        const id   = btn.dataset.id;
        const name = btn.dataset.name || 'this staff member';
        const row  = btn.closest('tr');
        if (!await openConfirm('Archive "' + name + '"? They will be moved to the archive and removed from active records.', 'archive')) return;
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

<!-- Invite Admin Modal -->
<div id="inviteModal" class="invite-modal-backdrop" style="display:none">
    <div class="invite-modal">
        <div class="invite-modal-head">
            <span class="material-symbols-outlined">link</span>
            Admin Invite Link
        </div>
        <p class="invite-modal-desc">Share this link with the person you want to register as Administrator. It expires in <strong>48 hours</strong> and can only be used once.</p>
        <div class="invite-url-wrap">
            <input type="text" id="inviteUrlInput" class="invite-url-input" readonly>
            <button type="button" class="invite-copy-btn" id="inviteCopyBtn">
                <span class="material-symbols-outlined">content_copy</span>
            </button>
        </div>
        <p id="inviteCopied" class="invite-copied" style="display:none">Copied to clipboard!</p>
        <div class="invite-modal-foot">
            <button type="button" class="invite-close-btn" id="inviteCloseBtn">Close</button>
        </div>
    </div>
</div>

<script>
(function () {
    var btn       = document.getElementById('inviteLinkBtn');
    var modal     = document.getElementById('inviteModal');
    var urlInput  = document.getElementById('inviteUrlInput');
    var copyBtn   = document.getElementById('inviteCopyBtn');
    var closeBtn  = document.getElementById('inviteCloseBtn');
    var copied    = document.getElementById('inviteCopied');
    var csrfName  = (document.querySelector('meta[name="csrf-name"]') || {}).content;
    var csrfHash  = (document.querySelector('meta[name="csrf-hash"]') || {}).content;

    if (!btn) return;

    btn.addEventListener('click', function () {
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined">hourglass_empty</span> Generating…';
        var fd = new FormData();
        if (csrfName && csrfHash) fd.append(csrfName, csrfHash);
        fetch('<?= base_url('auth/generateInvite') ?>', { method:'POST', body:fd, credentials:'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined">link</span> Invite Admin';
                if (d.url) {
                    urlInput.value = d.url;
                    copied.style.display = 'none';
                    modal.style.display = 'flex';
                } else {
                    showToast(d.error || 'Could not generate invite link.', 'error');
                }
            })
            .catch(function () {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined">link</span> Invite Admin';
                showToast('Network error. Please try again.', 'error');
            });
    });

    copyBtn.addEventListener('click', function () {
        navigator.clipboard.writeText(urlInput.value).then(function () {
            copied.style.display = 'block';
            setTimeout(function () { copied.style.display = 'none'; }, 2500);
        }).catch(function () {
            urlInput.select();
            document.execCommand('copy');
            copied.style.display = 'block';
            setTimeout(function () { copied.style.display = 'none'; }, 2500);
        });
    });

    closeBtn.addEventListener('click', function () { modal.style.display = 'none'; });
    modal.addEventListener('click', function (e) { if (e.target === modal) modal.style.display = 'none'; });
}());
</script>

<?= $this->endSection() ?>

<?php $this->section('firebase-scripts') ?>
<script>
(function () {
  window._pzPageListeners = true;

  var _base          = window.pzBaseUrl || '';
  var _page          = parseInt(new URLSearchParams(window.location.search).get('page') || '1', 10);
  var CAN_EDIT_STAFF = <?= pz_can('edit_staff') ? 'true' : 'false' ?>;

  var ROLE_CLS = {
    'Administrator':      'role-badge--administrator',
    'Operations Manager': 'role-badge--ops-manager',
    'Project Manager':    'role-badge--project-manager',
    'Site Engineer':      'role-badge--site-engineer',
  };

  function flashRow(row) {
    row.style.transition = 'background .2s';
    row.style.background = '#dbeafe';
    setTimeout(function () { row.style.background = ''; }, 1000);
  }

  function pzUpdateStaffRow(id, data) {
    var row = document.querySelector('tr[data-staff-id="' + id + '"]');
    if (!row) return;
    var lnEl    = row.querySelector('[data-staff-ln]');
    var fnEl    = row.querySelector('[data-staff-fn]');
    var emailEl = row.querySelector('[data-staff-email]');
    var roleEl  = row.querySelector('[data-staff-role]');
    if (lnEl)    lnEl.innerHTML    = '<strong>' + (data.last_name  || 'N/A') + '</strong>';
    if (fnEl)    fnEl.textContent  = data.first_name || 'N/A';
    if (emailEl) emailEl.textContent = data.email || 'N/A';
    if (roleEl) {
      var role = data.role || '';
      roleEl.className   = 'role-badge ' + (ROLE_CLS[role] || '');
      roleEl.textContent = role || 'Staff';
    }
    flashRow(row);
  }

  function buildStaffRow(id, data) {
    var fn  = data.first_name  || 'N/A';
    var ln  = data.last_name   || 'N/A';
    var mn  = data.middle_name || 'N/A';
    var em  = data.email       || 'N/A';
    var role = data.role || '';
    var ini  = (fn.charAt(0) || 'S').toUpperCase();
    var fullName = fn + ' ' + ln;
    var no   = document.querySelectorAll('tr[data-staff-id]').length + 1;
    var avatar = '<div style="width:32px;height:32px;border-radius:50%;background:#e8eef5;border:2px solid #d0dce8;'
               + 'display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#1a3c5e">' + ini + '</div>';
    return '<tr data-staff-id="' + id + '" class="pz-row-new">'
         + '<td class="pz-col-check"><input type="checkbox" class="row-cb" value="' + id + '" data-name="' + fullName + '"></td>'
         + '<td style="padding:4px 8px">' + avatar + '</td>'
         + '<td>' + no + '</td>'
         + '<td data-staff-ln><strong>' + ln + '</strong></td>'
         + '<td data-staff-fn>' + fn + '</td>'
         + '<td>' + mn + '</td>'
         + '<td data-staff-email>' + em + '</td>'
         + '<td><span class="role-badge ' + (ROLE_CLS[role] || '') + '" data-staff-role>' + (role || 'Staff') + '</span></td>'
         + '<td class="text-center"><div class="action-wrap">'
         + '<a href="' + _base + 'staff/show/' + id + '" class="action-btn" title="View"><span class="material-symbols-outlined">visibility</span></a>'
         + (CAN_EDIT_STAFF ? '<a href="' + _base + 'staff/update/' + id + '" class="action-btn" title="Edit"><span class="material-symbols-outlined">edit</span></a>' : '')
         + (CAN_EDIT_STAFF ? '<button type="button" class="action-btn action-archive" data-id="' + id + '" data-name="' + fullName + '" title="Archive"><span class="material-symbols-outlined">inventory_2</span></button>' : '')
         + (CAN_EDIT_STAFF ? '<a href="#" class="action-btn action-delete" data-id="' + id + '" data-name="' + fullName + '" title="Delete"><span class="material-symbols-outlined">delete</span></a>' : '')
         + '</div></td>'
         + '</tr>';
  }

  var _setupDone = false;
  function setup(db) {
    if (_setupDone) return; _setupDone = true;
    var _ready = false;
    db.collection('users').onSnapshot(function (snap) {
      if (!_ready) { _ready = true; return; }
      snap.docChanges().forEach(function (chg) {
        var id = chg.doc.id;

        if (chg.type === 'modified') { pzUpdateStaffRow(id, chg.doc.data()); return; }

        if (chg.type === 'added' && _page === 1) {
          if (document.querySelector('tr[data-staff-id="' + id + '"]')) return;
          var tbody = document.querySelector('table tbody');
          if (!tbody) return;
          var empty = tbody.querySelector('td[colspan]');
          if (empty) empty.closest('tr').remove();
          var tmp = document.createElement('tbody');
          tmp.innerHTML = buildStaffRow(id, chg.doc.data());
          tbody.appendChild(tmp.firstElementChild);
          return;
        }

        if (chg.type === 'removed') {
          var row = document.querySelector('tr[data-staff-id="' + id + '"]');
          if (!row) return;
          row.style.transition = 'opacity .4s, background .4s';
          row.style.background = '#fee2e2';
          row.style.opacity    = '0';
          setTimeout(function () {
            row.remove();
            var tbody = document.querySelector('table tbody');
            if (tbody && !tbody.querySelector('tr[data-staff-id]')) {
              tbody.innerHTML = '<tr><td colspan="9" class="empty-state">No staff found.</td></tr>';
            }
          }, 400);
        }
      });
    }, function (err) { console.warn('[PZ] Staff listener:', err.message); });
  }

  firebase.auth().onAuthStateChanged(function (user) {
    if (user && window.pzFirebaseDb) setup(window.pzFirebaseDb);
  });
}());
</script>
<?php $this->endSection() ?>
