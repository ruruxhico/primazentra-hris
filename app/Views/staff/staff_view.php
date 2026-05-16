
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
        <div class="user-profile">
            <strong><?= esc($userName ?? 'User') ?></strong>
            <small><?= strtoupper($role ?? 'USER') ?></small>
        </div>
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
                            <td><?= $index + 1 ?></td>
                            <td><?= esc((string)($person['last_name'] ?? 'N/A')) ?></td>
                            <td><?= esc((string)($person['first_name'] ?? 'N/A')) ?></td>
                            <td><?= esc((string)($person['middle_name'] ?? 'N/A')) ?></td>
                            <td><?= esc((string)($person['email'] ?? 'N/A')) ?></td>
                            <td>
                                <span class="role-badge">
                                    <?= esc((string)($person['role'] ?? 'STAFF')) ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <button class="action-button" title="View" onclick="alert('View functionality coming soon')">👁️</button>
                                <a href="<?= base_url('staff/update/' . $person['id']) ?>" class="action-button" title="Edit">✏️</a>
                                <a href="<?= base_url('staff/delete/' . $person['id']) ?>" class="action-button" title="Delete" onclick="return confirm('Are you sure?');">🗑️</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?= $this->endSection() ?>
