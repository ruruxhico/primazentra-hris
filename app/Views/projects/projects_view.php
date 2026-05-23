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

    <header class="projects-header">
        <h1>Projects</h1>
    </header>

    <!-- Search and Filter Section -->
    <div class="projects-toolbar">
        <input type="text" id="searchProjects" placeholder="🔍 Search projects..." class="search-input">
        
        <button class="btn-toolbar btn-filter">
            ▼ FILTER
        </button>
        
        <a href="<?= base_url('projects/create') ?>" class="btn-toolbar btn-add">
            + ADD
        </a>
        
        <button class="btn-toolbar btn-import">
            ⬇ IMPORT
        </button>
        
        <button class="btn-toolbar btn-export">
            ⬆ EXPORT
        </button>
    </div>

    <div id="customToast" class="custom-toast" aria-live="polite" aria-atomic="true"></div>

    <!-- Custom confirmation dialog -->
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
        const search = document.getElementById('searchProjects');
        const table = document.querySelector('.projects-table tbody');
        if (search && table) {
            search.addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                table.querySelectorAll('tr').forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = q === '' || text.includes(q) ? '' : 'none';
                });
            });
        }

        // Delete via POST with CSRF protection, animate row removal
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
                const name = this.datasetName || this.dataset.name || this.getAttribute('data-name') || 'this project';
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

            const body = new URLSearchParams();
            if (csrfName && csrfHash) body.append(csrfName, csrfHash);
            body.append('id', id);

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
                showToast('Error deleting project', 'error');
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

    <!-- Projects Table -->
    <div class="table-container">
        <table class="projects-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>PROJECT NAME</th>
                    <th>TYPE</th>
                    <th>LOCATION</th>
                    <th style="text-align: center;">PROGRESS</th>
                    <th>PROJECT MANAGER</th>
                    <th>SITE ENGINEER</th>
                    <th style="text-align: center;">ACTIONS</th>
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
                    <?php foreach ($projects as $index => $project): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <strong><?= esc(view_text($project['project_name'] ?? 'N/A', 'N/A')) ?></strong>
                            </td>
                            <td><?= esc(view_text($project['project_type'] ?? 'N/A', 'N/A')) ?></td>
                            <td><?= esc(view_text($project['location_label'] ?? ($project['location']['address'] ?? 'N/A'), 'N/A')) ?></td>
                            <td style="text-align: center;">
                                <span class="progress-badge">
                                    <?= esc(view_text($project['progress_label'] ?? ($project['overall_progress'] ?? 0))) ?>%
                                </span>
                            </td>
                            <td><?= esc(view_text($project['project_manager_name'] ?? 'N/A', 'N/A')) ?></td>
                            <td><?= esc(view_text($project['site_engineer_name'] ?? 'N/A', 'N/A')) ?></td>
                            <td style="text-align: center;">
                                <a href="<?= base_url('projects/show/' . $project['id']) ?>" class="action-button" title="View">👁️</a>
                                <a href="<?= base_url('projects/update/' . $project['id']) ?>" class="action-button" title="Edit">✏️</a>
                                <a href="#" data-id="<?= esc(view_text($project['id'] ?? '')) ?>" data-name="<?= esc(view_text($project['project_name'] ?? '')) ?>" class="action-button action-delete" title="Delete">🗑️</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?= $this->endSection() ?>
