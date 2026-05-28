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

<div class="page-module">
<div class="resource-controls">
    <input type="text" id="searchStaff" placeholder="🔍 Search staff..." class="search-input">

    <button class="btn btn-filter">
        ▼ FILTER
    </button>

    <a href="<?= base_url('staff/create') ?>" class="btn btn-success">
        + ADD
    </a>

    <button class="btn btn-import">
        ⬇ IMPORT
    </button>

    <button class="btn btn-export">
        ⬆ EXPORT
    </button>
</div>

<!-- Staff Table -->
<div class="table-container">
    <table id="staffTable" class="resource-table">
        <thead>
            <tr>
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
                    <td colspan="7" class="empty-state">
                        No staff members found. <a href="<?= base_url('staff/create') ?>">Add one now</a>
                    </td>
                </tr>
            <?php else: ?>
                <?php $offset = $pagerOffset ?? 0; ?>
                <?php foreach ($staff as $index => $person): ?>
                    <tr>
                        <td><?= $offset + $index + 1 ?></td>
                        <td><?= esc((string)($person['last_name'] ?? 'N/A')) ?></td>
                        <td><strong><?= esc((string)($person['first_name'] ?? 'N/A')) ?></strong></td>
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

    let pendingDeleteId = null;
    let pendingRow = null;

    document.querySelectorAll('.action-delete').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            pendingDeleteId = this.dataset.id;
            pendingRow = this.closest('tr');
            const name = this.dataset.name || 'this staff member';
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

        fetch('<?= base_url('staff/delete') ?>', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        })
        .then(r => r.json())
        .then(data => {
            if (data && data.success) {
                showToast('Staff deleted', 'success');
                if (pendingRow) {
                    pendingRow.classList.add('removing');
                    setTimeout(() => pendingRow?.remove(), 350);
                }
            } else {
                showToast(data.message || 'Delete failed', 'error');
            }
        })
        .catch(() => showToast('Error deleting staff', 'error'))
        .finally(() => {
            pendingDeleteId = null;
            pendingRow = null;
            confirmDialog.classList.remove('open');
        });
    });
});
</script>

<?= $this->endSection() ?>
