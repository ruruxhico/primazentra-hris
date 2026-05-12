
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
    <!-- Alert Messages -->
    <?php if (session()->getFlashdata('error')): ?>
        <div class="form-error">
            ✗ <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <header class="staff-header">
        <h1><?= esc($pageTitle ?? 'Staff') ?></h1>
        <div class="user-profile">
            <strong><?= esc($userName ?? 'User') ?></strong>
            <small><?= strtoupper($role ?? 'USER') ?></small>
        </div>
    </header>

    <!-- Form Container -->
    <div class="form-container">
        <form method="POST" action="<?= base_url(isset($staffId) ? 'staff/update/' . $staffId : 'staff/create') ?>">
            <?= csrf_field() ?>

            <div class="form-group">
                <label>Last Name *</label>
                <input type="text" name="last_name" value="<?= esc((string)($staff['last_name'] ?? '')) ?>" required>
            </div>

            <div class="form-group">
                <label>First Name *</label>
                <input type="text" name="first_name" value="<?= esc((string)($staff['first_name'] ?? '')) ?>" required>
            </div>

            <div class="form-group">
                <label>Middle Name</label>
                <input type="text" name="middle_name" value="<?= esc((string)($staff['middle_name'] ?? '')) ?>">
            </div>

            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" value="<?= esc((string)($staff['email'] ?? '')) ?>" required>
            </div>

            <div class="form-group">
                <label>Role *</label>
                <select name="role" required>
                    <option value="">Select Role</option>
                    <option value="ADMIN" <?= ($staff['role'] ?? '') === 'ADMIN' ? 'selected' : '' ?>>ADMIN</option>
                    <option value="SE" <?= ($staff['role'] ?? '') === 'SE' ? 'selected' : '' ?>>SITE ENGINEER</option>
                    <option value="PM" <?= ($staff['role'] ?? '') === 'PM' ? 'selected' : '' ?>>PROJECT MANAGER</option>
                    <option value="CREW WORKER" <?= ($staff['role'] ?? '') === 'CREW WORKER' ? 'selected' : '' ?>>CREW WORKER</option>
                    <option value="ELECTRICIAN" <?= ($staff['role'] ?? '') === 'ELECTRICIAN' ? 'selected' : '' ?>>ELECTRICIAN</option>
                    <option value="WELDER" <?= ($staff['role'] ?? '') === 'WELDER' ? 'selected' : '' ?>>WELDER</option>
                </select>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    <?= isset($staffId) ? 'Update Staff' : 'Create Staff' ?>
                </button>
                <a href="<?= base_url('staff') ?>" class="btn-cancel">
                    Cancel
                </a>
            </div>
        </form>
    </div>

<?= $this->endSection() ?>
