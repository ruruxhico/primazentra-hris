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

<style>
/* ── Controls bar ── */
.rv-controls { display:flex; align-items:center; flex-wrap:wrap; gap:8px; margin: 0 2rem 0.9rem; }
.rv-search-wrap { position:relative; flex:1; min-width:200px; }
.rv-search-wrap .material-symbols-outlined { position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:18px; color:#aaa; pointer-events:none; }
.rv-search { width:100%; padding:8px 14px 8px 38px; border:1.5px solid #ddd; border-radius:999px; font-size:0.87rem; font-family:inherit; outline:none; box-sizing:border-box; }
.rv-search:focus { border-color:#C47A0F; box-shadow:0 0 0 3px rgba(196,122,15,.1); }
.rv-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:8px; font-size:0.8rem; font-weight:700; letter-spacing:0.04em; cursor:pointer; border:1.5px solid transparent; font-family:inherit; white-space:nowrap; font-variation-settings:'FILL' 0,'wght' 400; text-decoration:none; }
.rv-btn .material-symbols-outlined { font-size:16px; }
.rv-btn-filter { background:#fff; border-color:#ddd; color:#555; }
.rv-btn-filter:hover { border-color:#C47A0F; color:#8C701C; }
.rv-btn-add { background:#8C701C; color:#fff; border-color:#8C701C; }
.rv-btn-add:hover { background:#6e570f; color:#fff; }
.rv-btn-import { background:#fff; border-color:#adb5bd; color:#495057; }
.rv-btn-import:hover { border-color:#8C701C; color:#8C701C; }
.rv-btn-export { background:#fff; border-color:#adb5bd; color:#495057; }
.rv-btn-export:hover { border-color:#8C701C; color:#8C701C; }

.filter-panel { background:#fff; border:1px solid #e0d4b0; border-radius:8px; padding:16px 20px 12px; margin: 0 2rem 12px; box-shadow:0 2px 8px rgba(0,0,0,0.07); }
.filter-rows  { display:flex; flex-direction:column; gap:12px; }
.filter-row   { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.filter-row label { font-size:11px; font-weight:600; text-transform:uppercase; color:#666; letter-spacing:.4px; min-width:80px; }
.filter-row select { padding:6px 8px; border:1px solid #ccc; border-radius:4px; font-size:13px; background:#fff; }
.ln-ranges    { display:flex; flex-wrap:wrap; gap:6px; }
.ln-chip      { padding:4px 10px; border:1px solid #ccc; border-radius:4px; font-size:12px; font-weight:600; cursor:pointer; background:#fff; color:#444; transition:background .15s,color .15s,border-color .15s; }
.ln-chip:hover   { border-color:#8C701C; color:#8C701C; }
.ln-chip.active  { background:#8C701C; color:#fff; border-color:#8C701C; }
.filter-footer   { display:flex; align-items:center; gap:10px; margin-top:14px; }
.filter-badge    { display:inline-flex; align-items:center; justify-content:center; background:#8C701C; color:#fff; border-radius:50%; font-size:10px; font-weight:700; width:16px; height:16px; margin-left:5px; vertical-align:middle; }

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
                        <td>
                            <span class="role-badge">
                                <?= esc((string)($person['role'] ?? 'STAFF')) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="<?= base_url('staff/show/' . $person['id']) ?>" class="action-btn" title="View"><span class="material-symbols-outlined">visibility</span></a>
                            <a href="<?= base_url('staff/update/' . $person['id']) ?>" class="action-btn" title="Edit"><span class="material-symbols-outlined">edit</span></a>
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
                    if (data.trash_id) addToRecentlyDeleted(data.trash_id, data.name, data.deleted_by, data.deleted_at);
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
                    if (data.trash_id) addToRecentlyDeleted(data.trash_id, data.name, data.deleted_by, data.deleted_at);
                } else {
                    showToast(data.message || 'Delete failed', 'error');
                }
            })
            .catch(() => showToast('Error deleting staff', 'error'));
        });
    }
    document.querySelectorAll('.action-delete').forEach(wireDeleteBtn);

    // ── Recently Deleted ────────────────────────────────────────
    const rdToggleBtn = document.getElementById('rdToggleBtn');
    const rdPanel     = document.getElementById('rdPanel');

    rdToggleBtn && rdToggleBtn.addEventListener('click', function () {
        const open = rdPanel && rdPanel.style.display !== 'none';
        if (rdPanel) rdPanel.style.display = open ? 'none' : '';
        const chevron = this.querySelector('.rd-chevron');
        if (chevron) chevron.style.transform = open ? '' : 'rotate(180deg)';
    });

    function escHtml(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function fmtTs(ts) {
        const d = new Date(ts * 1000);
        const mo = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][d.getMonth()];
        return String(d.getDate()).padStart(2,'0') + ' ' + mo + ' ' + d.getFullYear()
             + ' ' + String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');
    }

    function addToRecentlyDeleted(trashId, name, deletedBy, deletedAt) {
        // Expand the panel
        if (rdPanel) { rdPanel.style.display = ''; }
        const chevron = rdToggleBtn?.querySelector('.rd-chevron');
        if (chevron) chevron.style.transform = 'rotate(180deg)';

        // Badge: increment if exists, otherwise create
        const wrap = document.getElementById('recentlyDeletedWrap');
        let badge = wrap?.querySelector('.rd-badge');
        if (badge) {
            badge.textContent = parseInt(badge.textContent || '0') + 1;
        } else if (rdToggleBtn) {
            badge = document.createElement('span');
            badge.className = 'rd-badge';
            badge.textContent = '1';
            rdToggleBtn.insertBefore(badge, rdToggleBtn.querySelector('.rd-chevron'));
        }

        // Remove empty-state paragraph if present
        rdPanel?.querySelector('.rd-empty')?.remove();

        // Get or create the table
        let tbody = rdPanel?.querySelector('.rd-table tbody');
        if (!tbody) {
            const tbl = document.createElement('table');
            tbl.className = 'resource-table rd-table';
            tbl.innerHTML = '<thead><tr><th>Name</th><th>Deleted</th><th>Expires</th><th class="text-center">Actions</th></tr></thead><tbody></tbody>';
            rdPanel.appendChild(tbl);
            tbody = tbl.querySelector('tbody');
        }

        // Build the row
        const tr = document.createElement('tr');
        tr.dataset.trashId = trashId;
        tr.innerHTML =
            '<td>' + escHtml(name || '—') + '</td>' +
            '<td>' + fmtTs(deletedAt) + '</td>' +
            '<td>30 days</td>' +
            '<td class="text-center" style="white-space:nowrap">' +
              '<button type="button" class="action-btn rd-restore-btn" data-id="' + escHtml(trashId) + '" title="Restore"><span class="material-symbols-outlined">restore</span></button>' +
              '<button type="button" class="action-btn rd-perma-btn" style="color:#e53e3e" data-id="' + escHtml(trashId) + '" title="Delete forever"><span class="material-symbols-outlined">delete_forever</span></button>' +
            '</td>';

        // Slide in
        tr.style.opacity = '0';
        tr.style.transform = 'translateX(-8px)';
        tbody.prepend(tr);
        requestAnimationFrame(() => {
            tr.style.transition = 'opacity 260ms ease, transform 260ms ease';
            tr.style.opacity = '1';
            tr.style.transform = '';
        });
    }

    function addRestoredStaffRow(id, d) {
        const tbody = document.querySelector('#staffTable tbody');
        if (!tbody) return;
        tbody.querySelector('[colspan]')?.closest('tr')?.remove();

        const initial  = ((d.first_name || 'S')[0] || 'S').toUpperCase();
        const fullName = escHtml(((d.first_name || '') + ' ' + (d.last_name || '')).trim());
        const pic      = d.profile_picture || '';

        const avatarHtml = pic
            ? '<img src="' + ASSET_BASE + escHtml(pic) + '" alt="" style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid #d0dce8;display:block">'
            : '<div style="width:32px;height:32px;border-radius:50%;background:#e8eef5;border:2px solid #d0dce8;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#1a3c5e">' + escHtml(initial) + '</div>';

        const tr = document.createElement('tr');
        tr.innerHTML =
            '<td class="pz-col-check"><input type="checkbox" class="row-cb" value="' + escHtml(id) + '" data-name="' + fullName + '"></td>' +
            '<td style="padding:4px 8px">' + avatarHtml + '</td>' +
            '<td>0</td>' +
            '<td><strong>' + escHtml(d.last_name  || 'N/A') + '</strong></td>' +
            '<td>' + escHtml(d.first_name  || 'N/A') + '</td>' +
            '<td>' + escHtml(d.middle_name || 'N/A') + '</td>' +
            '<td>' + escHtml(d.email       || 'N/A') + '</td>' +
            '<td><span class="role-badge">' + escHtml(d.role || 'STAFF') + '</span></td>' +
            '<td class="text-center">' +
                '<a href="' + BASE_URL + 'staff/show/'   + escHtml(id) + '" class="action-btn" title="View"><span class="material-symbols-outlined">visibility</span></a>' +
                '<a href="' + BASE_URL + 'staff/update/' + escHtml(id) + '" class="action-btn" title="Edit"><span class="material-symbols-outlined">edit</span></a>' +
                '<a href="#" data-id="' + escHtml(id) + '" data-name="' + fullName + '" class="action-btn action-delete" title="Delete"><span class="material-symbols-outlined">delete</span></a>' +
            '</td>';

        wireDeleteBtn(tr.querySelector('.action-delete'));
        wireRowCb(tr.querySelector('.row-cb'));

        tr.style.opacity = '0';
        tr.style.background = '#edf7e6';
        tbody.appendChild(tr);
        requestAnimationFrame(() => {
            tr.style.transition = 'opacity 300ms ease, background 1.2s ease';
            tr.style.opacity = '1';
            setTimeout(() => { tr.style.background = ''; }, 1000);
        });

        renumberTable(document.getElementById('staffTable'), 2);
    }

    // Event delegation for restore / perma-delete (handles both server-rendered and dynamically added rows)
    rdPanel?.addEventListener('click', async function (e) {
        const restoreBtn = e.target.closest('.rd-restore-btn');
        const permaBtn   = e.target.closest('.rd-perma-btn');

        if (restoreBtn) {
            const trashId = restoreBtn.dataset.id;
            const row = restoreBtn.closest('tr');
            const fd = new FormData();
            fd.append(csrfName, csrfHash);
            fd.append('trash_id', trashId);
            fetch('<?= base_url('trash/restore') ?>', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast((data.name || 'Item') + ' restored', 'success');
                        animateRowOut(row);
                        if (data.id && data.data) addRestoredStaffRow(data.id, data.data);
                        const b = document.querySelector('#recentlyDeletedWrap .rd-badge');
                        if (b) { const n = parseInt(b.textContent||'0')-1; if (n <= 0) b.remove(); else b.textContent = n; }
                    } else {
                        showToast(data.message || 'Restore failed', 'error');
                    }
                })
                .catch(() => showToast('Restore failed', 'error'));
        }

        if (permaBtn) {
            const trashId = permaBtn.dataset.id;
            const row = permaBtn.closest('tr');
            if (!await openConfirm('Permanently delete this item? This cannot be undone.')) return;
            const fd = new FormData();
            fd.append(csrfName, csrfHash);
            fd.append('trash_id', trashId);
            fetch('<?= base_url('trash/permanent-delete') ?>', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast('Permanently deleted', 'success');
                        animateRowOut(row);
                        const b = document.querySelector('#recentlyDeletedWrap .rd-badge');
                        if (b) { const n = parseInt(b.textContent||'0')-1; if (n <= 0) b.remove(); else b.textContent = n; }
                    } else {
                        showToast(data.message || 'Failed', 'error');
                    }
                })
                .catch(() => showToast('Failed', 'error'));
        }
    });
});
</script>

<!-- Import Modal -->
<div id="importModal" style="display:none;position:fixed;inset:0;z-index:3000;background:rgba(0,0,0,.45);align-items:center;justify-content:center;" hidden>
  <div style="background:#fff;border-radius:12px;padding:28px 32px;min-width:360px;max-width:480px;box-shadow:0 8px 32px rgba(0,0,0,.18);">
    <h3 style="margin:0 0 6px;color:#2c1f07;font-size:17px;">Import Staff</h3>
    <p style="margin:0 0 14px;color:#555;font-size:13px;">Upload an .xlsx or .csv file. <a id="importTplLink" href="<?= base_url('import/template?module=staff') ?>" style="color:#8C701C;font-weight:600;">Download template</a></p>
    <div id="importDropZone" style="border:2px dashed #d4c185;border-radius:8px;padding:24px;text-align:center;cursor:pointer;color:#888;font-size:13px;">
      <div style="font-size:28px;margin-bottom:6px;">📂</div>
      Click or drag &amp; drop your .xlsx or .csv file here
      <input type="file" id="importFileInput" accept=".xlsx,.xls,.csv" style="display:none;">
    </div>
    <div id="importFileName" style="margin-top:8px;font-size:12px;color:#555;"></div>
    <div id="importResult"   style="margin-top:10px;font-size:13px;"></div>
    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:18px;">
      <button onclick="closeImportModal()" style="padding:8px 18px;background:#f0f0f0;border:none;border-radius:6px;cursor:pointer;font-size:13px;">Cancel</button>
      <button id="importSubmitBtn" onclick="submitImport()" style="padding:8px 18px;background:#8C701C;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;">Import</button>
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
<div id="exportModal" style="display:none;position:fixed;inset:0;z-index:3000;background:rgba(0,0,0,.45);align-items:center;justify-content:center;" hidden>
  <div style="background:#fff;border-radius:12px;padding:28px 32px;min-width:320px;box-shadow:0 8px 32px rgba(0,0,0,.18);">
    <h3 style="margin:0 0 18px;color:#1a3c5e;font-size:17px;">Export Staff</h3>
    <p style="margin:0 0 18px;color:#555;font-size:13px;">Choose a format to download:</p>
    <div style="display:flex;flex-direction:column;gap:10px;">
      <a id="exportXlsx" href="<?= base_url('export?module=staff&format=xlsx') ?>" target="_blank" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px solid #1a3c5e;border-radius:8px;text-decoration:none;color:#1a3c5e;font-weight:600;font-size:14px;">
        <span style="font-size:20px;">📊</span> Download XLSX
      </a>
      <a id="exportCsv" href="<?= base_url('export?module=staff&format=csv') ?>" target="_blank" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px solid #7b5ea7;border-radius:8px;text-decoration:none;color:#7b5ea7;font-weight:600;font-size:14px;">
        <span style="font-size:20px;">📋</span> Download CSV
      </a>
      <a id="exportPdf" href="<?= base_url('export?module=staff&format=pdf') ?>" target="_blank" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px solid #c0392b;border-radius:8px;text-decoration:none;color:#c0392b;font-weight:600;font-size:14px;">
        <span style="font-size:20px;">📄</span> Download PDF
      </a>
      <a id="exportPrint" href="<?= base_url('export?module=staff&format=print') ?>" target="_blank" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px solid #27ae60;border-radius:8px;text-decoration:none;color:#27ae60;font-weight:600;font-size:14px;">
        <span style="font-size:20px;">🖨️</span> Open Printable View
      </a>
    </div>
    <div style="text-align:right;margin-top:18px;">
      <button onclick="document.getElementById('exportModal').hidden=true;document.getElementById('exportModal').style.display='none';" style="padding:8px 18px;background:#f0f0f0;border:none;border-radius:6px;cursor:pointer;font-size:13px;">Close</button>
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
