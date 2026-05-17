
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
    <!-- Alert Messages -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert-success">
            ✓ <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert-error">
            ✗ <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <header class="staff-header">
        <h1>Staff</h1>
    </header>

    <!-- Search and Filter Section -->
    <div class="staff-toolbar">
        <input type="text" id="searchStaff" placeholder="🔍 Search staff..." class="search-input">
        
        <button class="btn-toolbar btn-filter">
            ▼ FILTER
        </button>
        
        <a href="<?= base_url('staff/create') ?>" class="btn-toolbar btn-add">
            + ADD
        </a>
        
        <button class="btn-toolbar btn-import">
            ⬇ IMPORT
        </button>
        
        <button class="btn-toolbar btn-export">
            ⬆ EXPORT
        </button>
    </div>
    

    <div class="pager-wrapper">
        <?= service('pager')->links('staff','bootstrap_full') ?>
    </div>

    <div id="customToast" class="custom-toast" aria-live="polite" aria-atomic="true"></div>

    <!-- Custom confirmation dialog -->
    <!-- CSRF tokens for AJAX requests -->
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

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Simple client-side search
        const search = document.getElementById('searchStaff');
        const table = document.querySelector('.staff-table tbody');
        if (search && table) {
            search.addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                table.querySelectorAll('tr').forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = q === '' || text.includes(q) ? '' : 'none';
                });
            });
        }

        // Delete via POST with CSRF protection, animate row removal, show staff name in dialog
        const confirmDialog = document.getElementById('confirmDialog');
        const confirmMessage = document.getElementById('confirmMessage');
        const confirmOk = document.getElementById('confirmOk');
        const confirmCancel = document.getElementById('confirmCancel');

        const csrfName = document.querySelector('meta[name="csrf-name"]')?.getAttribute('content');
        const csrfHash = document.querySelector('meta[name="csrf-hash"]')?.getAttribute('content');

        let pendingDeleteId = null;
        let pendingRow = null;

        document.querySelectorAll('.action-delete').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const id = this.dataset.id;
                const name = this.datasetName || this.dataset.name || this.getAttribute('data-name') || 'this staff member';
                pendingDeleteId = id;
                pendingRow = this.closest('tr');
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
            const id = pendingDeleteId;
            const row = pendingRow;
            pendingDeleteId = null;
            pendingRow = null;
            confirmDialog.classList.remove('open');

            // Build urlencoded body so CI4 CSRF can validate via POST
            const body = new URLSearchParams();
            if (csrfName && csrfHash) body.append(csrfName, csrfHash);
            body.append('id', id);

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
                    if (row) {
                        row.classList.add('removing');
                        setTimeout(() => { row.remove(); }, 350);
                    }
                } else {
                    showToast(data.message || 'Delete failed', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Error deleting staff', 'error');
            });
        });

        function showToast(message, type='info') {
            const t = document.getElementById('customToast');
            if (!t) return;
            t.className = 'custom-toast show ' + (type === 'error' ? 'error' : 'success');
            t.innerText = message;
            setTimeout(() => { t.className = 'custom-toast'; }, 3000);
        }
    });
    </script>
    <!-- Staff Table -->
    <div class="staff-table-container">
        <table class="staff-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>LAST NAME</th>
                    <th>FIRST NAME</th>
                    <th>MIDDLE NAME</th>
                    <th>EMAIL</th>
                    <th>ROLE</th>
                    <th style="text-align: center;">ACTIONS</th>
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
                    <?php foreach ($staff as $index => $person): ?>
                        <tr>
                            <td data-label="ID"><?= $index + 1 ?></td>
                            <td data-label="Last Name"><?= esc((string)($person['last_name'] ?? 'N/A')) ?></td>
                            <td data-label="First Name"><?= esc((string)($person['first_name'] ?? 'N/A')) ?></td>
                            <td data-label="Middle Name"><?= esc((string)($person['middle_name'] ?? 'N/A')) ?></td>
                            <td data-label="Email"><?= esc((string)($person['email'] ?? 'N/A')) ?></td>
                            <td data-label="Role">
                                <span class="role-badge">
                                    <?= esc((string)($person['role'] ?? 'STAFF')) ?>
                                </span>
                            </td>
                            <td data-label="Actions" style="text-align: center;">
                                <a href="<?= base_url('staff/show/' . $person['id']) ?>" class="action-button" title="View">👁️</a>
                                <a href="<?= base_url('staff/update/' . $person['id']) ?>" class="action-button" title="Edit">✏️</a>
                                <a href="#" data-id="<?= esc((string)$person['id']) ?>" data-name="<?= esc((string)($person['first_name'] . ' ' . $person['last_name'])) ?>" class="action-button action-delete" title="Delete">🗑️</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?= $this->endSection() ?>
