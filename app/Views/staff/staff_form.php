
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
                <label>Contact No</label>
                <input type="text" name="contact_no" value="<?= esc((string)($staff['contact_no'] ?? '')) ?>">
            </div>

            <div class="form-group">
                <label>Role *</label>
                <select name="role" required>
                    <option value="">Select Role</option>
                    <option value="Administrator" <?= ($staff['role'] ?? '') === 'Administrator' ? 'selected' : '' ?>>Administrator</option>
                    <option value="Operations Manager" <?= ($staff['role'] ?? '') === 'Operations Manager' ? 'selected' : '' ?>>Operations Manager</option>
                    <option value="Project Manager" <?= ($staff['role'] ?? '') === 'Project Manager' ? 'selected' : '' ?>>Project Manager</option>
                    <option value="Site Engineer" <?= ($staff['role'] ?? '') === 'Site Engineer' ? 'selected' : '' ?>>Site Engineer</option>
                </select>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="must_change_password" value="1" <?= (($staff['must_change_password'] ?? true) ? 'checked' : '') ?> />
                    Users must change password on first login
                </label>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-add">
                    <?= isset($staffId) ? 'Update Staff' : 'Create Staff' ?>
                </button>
                <a href="<?= base_url('staff') ?>" class="btn btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>

<?= $this->endSection() ?>
