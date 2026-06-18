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
.filter-panel { background:#fafafa; border:1px solid #e0e0e0; border-radius:6px; padding:16px 20px 12px; margin-bottom:12px; }
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
<div class="resource-controls">
    <input type="text" id="searchStaff" placeholder="🔍 Search staff..." class="search-input">

    <button type="button" class="btn btn-filter" id="filterToggleBtn">
        ▼ FILTER<?php if ($activeCount > 0): ?><span class="filter-badge"><?= $activeCount ?></span><?php endif; ?>
    </button>

    <a href="<?= base_url('staff/create') ?>" class="btn btn-success">
        + ADD
    </a>

    <button class="btn btn-export" data-module="staff" onclick="openStaffExportModal()">
        ⬆ EXPORT
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
<div class="table-container">
    <table id="staffTable" class="resource-table">
        <thead>
            <tr>
                <th class="pz-col-check"><input type="checkbox" class="select-all-cb" title="Select all"></th>
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
                    <td colspan="8" class="empty-state">
                        No staff members found. <a href="<?= base_url('staff/create') ?>">Add one now</a>
                    </td>
                </tr>
            <?php else: ?>
                <?php $offset = $pagerOffset ?? 0; ?>
                <?php foreach ($staff as $index => $person): ?>
                    <tr>
                        <td class="pz-col-check">
                            <input type="checkbox" class="row-cb" value="<?= esc((string)$person['id']) ?>" data-name="<?= esc(trim(($person['first_name'] ?? '') . ' ' . ($person['last_name'] ?? ''))) ?>">
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
    <?= service('pager')->links('staff', 'bootstrap_full') ?>
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
                <th>Deleted By</th>
                <th>Deleted</th>
                <th>Expires</th>
                <th class="text-center">Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach ($recentlyDeleted as $rd): ?>
            <tr data-trash-id="<?= esc($rd['id']) ?>">
                <td><?= esc($rd['name'] ?? '—') ?></td>
                <td><?= esc($rd['deleted_by'] ?? '—') ?></td>
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
    const DELETE_URL = '<?= base_url('staff/delete') ?>';

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
            const name = this.dataset.name || 'this staff member';
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
                    if (done === items.length) showToast(items.length + ' item(s) deleted', 'success');
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
                showToast('Staff deleted', 'success');
                animateRowOut(pendingRow);
            } else {
                showToast(data.message || 'Delete failed', 'error');
            }
        })
        .catch(() => showToast('Error deleting staff', 'error'))
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
});
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
