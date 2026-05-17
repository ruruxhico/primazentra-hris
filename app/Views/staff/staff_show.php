<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
    <header class="staff-header">
        <h1>Staff Detail</h1>
    </header>

    <div class="detail-container">
        <dl>
            <dt>Full Name</dt>
            <dd><?= esc((string)(($staff['first_name'] ?? '') . ' ' . ($staff['middle_name'] ?? '') . ' ' . ($staff['last_name'] ?? ''))) ?></dd>

            <dt>Email</dt>
            <dd><?= esc((string)($staff['email'] ?? '')) ?></dd>

            <dt>Contact No</dt>
            <dd><?= esc((string)($staff['contact_no'] ?? '')) ?></dd>

            <dt>Role</dt>
            <dd><?= esc((string)($staff['role'] ?? '')) ?></dd>

            <!-- Hidden: must_change_password, created_at, updated_at per UI requirements -->
        </dl>

        <div class="form-actions">
            <a href="<?= base_url('staff/update/' . ($staffId ?? '')) ?>" class="btn-add">Edit</a>
            <a href="<?= base_url('staff') ?>" class="btn-secondary">Back</a>
        </div>
    </div>

<?= $this->endSection() ?>