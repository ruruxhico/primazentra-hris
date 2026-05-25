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
    <h1>Resources</h1>
</div>

<!-- Tabs Navigation -->
<div class="resource-tabs">
    <button id="tab-equipment" class="tab-btn tab-btn-active" onclick="showResourceTab('equipment')">
        EQUIPMENT
    </button>
    <button id="tab-tools" class="tab-btn" onclick="showResourceTab('tools')">
        TOOLS
    </button>
    <button id="tab-manpower" class="tab-btn" onclick="showResourceTab('manpower')">
        MANPOWER
    </button>
</div>

<!-- EQUIPMENT TAB -->
<div id="equipment" class="tab-content tab-content-active">
    <!-- Search and Filter Section -->
    <div class="resource-controls">
        <input type="text" id="searchEquipment" placeholder="🔍 Search equipment..." class="search-input">
        
        <button class="btn btn-filter">
            ▼ FILTER
        </button>
        
        <a href="<?= base_url('resources/create?category=Equipment') ?>" class="btn btn-success">
            + ADD
        </a>
        
        <button class="btn btn-import">
            ⬇ IMPORT
        </button>
        
        <button class="btn btn-export">
            ⬆ EXPORT
        </button>
    </div>

    <!-- Equipment Table -->
    <div class="table-container">
        <table class="resource-table">
            <thead>
                <tr>
                    <th>NO.</th>
                    <th>MODEL NAME</th>
                    <th>MODEL NUMBER</th>
                    <th>TOTAL QUANTITY</th>
                    <th>AVAILABLE UNITS</th>
                    <th class="text-center">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($equipment)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No equipment found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($equipment as $index => $item): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><strong><?= esc((string)($item['name'] ?? 'N/A')) ?></strong></td>
                            <td><?= esc((string)($item['asset_details']['model_number'] ?? $item['model_code'] ?? 'N/A')) ?></td>
                            <td><?= esc((string)($item['total_quantity_display'] ?? $item['asset_details']['total_quantity'] ?? '0')) ?></td>
                            <td><?= esc((string)($item['available_units_display'] ?? $item['asset_details']['available_quantity'] ?? '0')) ?></td>
                            <td class="text-center">
                                <a href="<?= base_url('resources/show/' . $item['id']) ?>" class="action-btn" title="View">👁️</a>
                                <a href="<?= base_url('resources/edit/' . $item['id']) ?>" class="action-btn" title="Edit">✏️</a>
                                <a href="#" data-id="<?= esc((string)$item['id']) ?>" data-name="<?= esc((string)($item['name'] ?? 'this resource')) ?>" class="action-btn action-delete" title="Delete">🗑️</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- TOOLS TAB -->
<div id="tools" class="tab-content">
    <!-- Search and Filter Section -->
    <div class="resource-controls">
        <input type="text" id="searchTools" placeholder="🔍 Search tools..." class="search-input">
        
        <button class="btn btn-filter">
            ▼ FILTER
        </button>
        
        <a href="<?= base_url('resources/create?category=Tools') ?>" class="btn btn-success">
            + ADD
        </a>
        
        <button class="btn btn-import">
            ⬇ IMPORT
        </button>
        
        <button class="btn btn-export">
            ⬆ EXPORT
        </button>
    </div>

    <!-- Tools Table -->
    <div class="table-container">
        <table class="resource-table">
            <thead>
                <tr>
                    <th>NO.</th>
                    <th>MODEL NAME</th>
                    <th>MODEL NUMBER</th>
                    <th>TOTAL QUANTITY</th>
                    <th>AVAILABLE</th>
                    <th class="text-center">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    $tools = $tools ?? [];
                ?>
                <?php if (empty($tools)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No tools found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tools as $index => $item): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><strong><?= esc((string)($item['name'] ?? 'N/A')) ?></strong></td>
                            <td><?= esc((string)($item['asset_details']['model_number'] ?? $item['model_code'] ?? 'N/A')) ?></td>
                            <td><?= esc((string)($item['total_quantity_display'] ?? $item['asset_details']['total_quantity'] ?? '0')) ?></td>
                            <td><?= esc((string)($item['available_units_display'] ?? $item['asset_details']['available_quantity'] ?? '0')) ?></td>
                            <td class="text-center">
                                <a href="<?= base_url('resources/show/' . $item['id']) ?>" class="action-btn" title="View">👁️</a>
                                <a href="<?= base_url('resources/edit/' . $item['id']) ?>" class="action-btn" title="Edit">✏️</a>
                                <a href="#" data-id="<?= esc((string)$item['id']) ?>" data-name="<?= esc((string)($item['name'] ?? 'this resource')) ?>" class="action-btn action-delete" title="Delete">🗑️</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MANPOWER TAB -->
<div id="manpower" class="tab-content">
    <!-- Search and Filter Section -->
    <div class="resource-controls">
        <input type="text" id="searchManpower" placeholder="🔍 Search manpower..." class="search-input">
        
        <button class="btn btn-filter">
            ▼ FILTER
        </button>
        
        <a href="<?= base_url('resources/create?category=Manpower') ?>" class="btn btn-success">
            + ADD
        </a>
        
        <button class="btn btn-import">
            ⬇ IMPORT
        </button>
        
        <button class="btn btn-export">
            ⬆ EXPORT
        </button>
    </div>

    <!-- Manpower Table -->
    <div class="table-container">
        <table class="resource-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>RESOURCE NAME</th>
                    <th>ROLE</th>
                    <th>NEXT AVAILABLE</th>
                    <th>STATUS</th>
                    <th>CURRENT SITE</th>
                    <th>SPECIALIZATION</th>
                    <th class="text-center">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($manpower)): ?>
                    <tr>
                            No manpower records found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($manpower as $index => $person): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= isset($person['name']) ? esc((string)$person['name']) : 'N/A' ?></td>
                            <td><?= isset($person['manpower_details']['role']) ? esc((string)$person['manpower_details']['role']) : 'N/A' ?></td>
                            <td><?= isset($person['next_availability']) ? esc((string)$person['next_availability']) : 'N/A' ?></td>
                            <td>
                                <span class="status-badge status-<?= strtolower(str_replace('-', '', $person['status'] ?? 'available')) ?>">
                                    <?= esc((string)($person['status'] ?? 'N/A')) ?>
                                </span>
                            </td>
                            <?php $displaySite = $person['manpower_details']['current_site_deployment'] ?? $person['location']['address'] ?? 'N/A'; ?>
                            <td><?= esc((string)$displaySite) ?></td>
                            <td><?= isset($person['manpower_details']['specialization']) ? esc((string)$person['manpower_details']['specialization']) : 'N/A' ?></td>
                            <td class="text-center">
                                <a href="<?= base_url('resources/show/' . $person['id']) ?>" class="action-btn" title="View">👁️</a>
                                <a href="<?= base_url('resources/edit/' . $person['id']) ?>" class="action-btn" title="Edit">✏️</a>
                                <a href="#" data-id="<?= esc((string)$person['id']) ?>" data-name="<?= esc((string)($person['name'] ?? 'this resource')) ?>" class="action-btn action-delete" title="Delete">🗑️</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function showResourceTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(el => {
            el.classList.remove('tab-content-active');
        });
        document.querySelectorAll('.tab-btn').forEach(el => {
            el.classList.remove('tab-btn-active');
        });
        
        // Show selected tab
        const selectedTab = document.getElementById(tabName);
        const selectedBtn = document.getElementById('tab-' + tabName);
        
        if (selectedTab) selectedTab.classList.add('tab-content-active');
        if (selectedBtn) selectedBtn.classList.add('tab-btn-active');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const csrfName = document.querySelector('meta[name="csrf-name"]')?.getAttribute('content');
        const csrfHash = document.querySelector('meta[name="csrf-hash"]')?.getAttribute('content');
        const confirmDialog = document.getElementById('confirmDialog');
        const confirmMessage = document.getElementById('confirmMessage');
        const confirmOk = document.getElementById('confirmOk');
        const confirmCancel = document.getElementById('confirmCancel');
        const toast = document.getElementById('customToast');

        function showToast(message, type='info') {
            if (!toast) return;
            toast.className = 'custom-toast show ' + (type === 'error' ? 'error' : 'success');
            toast.innerText = message;
            setTimeout(() => { toast.className = 'custom-toast'; }, 3000);
        }

        function attachSearch(inputId, tableSelector) {
            const search = document.getElementById(inputId);
            const table = document.querySelector(tableSelector);
            if (!search || !table) return;

            search.addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                table.querySelectorAll('tbody tr').forEach(row => {
                    row.style.display = q === '' || row.innerText.toLowerCase().includes(q) ? '' : 'none';
                });
            });
        }

        attachSearch('searchEquipment', '#equipment .resource-table');
        attachSearch('searchTools', '#tools .resource-table');
        attachSearch('searchManpower', '#manpower .resource-table');

        let pendingDeleteId = null;
        let pendingRow = null;

        document.querySelectorAll('.action-delete').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                pendingDeleteId = this.dataset.id;
                pendingRow = this.closest('tr');
                const name = this.dataset.name || 'this resource';
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

            fetch('<?= base_url('resources/delete') ?>', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString()
            })
            .then(r => r.json())
            .then(data => {
                if (data && data.success) {
                    showToast('Resource deleted', 'success');
                    if (pendingRow) {
                        pendingRow.classList.add('removing');
                        setTimeout(() => pendingRow?.remove(), 350);
                    }
                } else {
                    showToast(data.message || 'Delete failed', 'error');
                }
            })
            .catch(() => showToast('Error deleting resource', 'error'))
            .finally(() => {
                pendingDeleteId = null;
                pendingRow = null;
                confirmDialog.classList.remove('open');
            });
        });
    });
</script>

<?= $this->endSection() ?>
