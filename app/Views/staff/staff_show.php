<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<a href="<?= base_url('staff') ?>" class="pz-back-link">
    <span class="material-symbols-outlined">arrow_back</span> Staff
</a>

<div class="pz-page-header">
    <h1><?= esc((string)(($staff['first_name'] ?? '') . ' ' . ($staff['middle_name'] ? $staff['middle_name'] . ' ' : '') . ($staff['last_name'] ?? ''))) ?></h1>
    <p><?= esc((string)($staff['role'] ?? '')) ?></p>
</div>

<div class="pz-card">
    <p class="pz-card-section">Staff Details</p>
    <div class="pz-info-grid">
        <span class="pz-info-label">Full Name</span>
        <span class="pz-info-value"><?= esc((string)(($staff['first_name'] ?? '') . ' ' . ($staff['middle_name'] ?? '') . ' ' . ($staff['last_name'] ?? ''))) ?></span>

        <span class="pz-info-label">Email</span>
        <span class="pz-info-value"><?= esc((string)($staff['email'] ?? '—')) ?></span>

        <span class="pz-info-label">Contact No</span>
        <span class="pz-info-value"><?= esc((string)($staff['contact_no'] ?? '—')) ?></span>

        <span class="pz-info-label">Role</span>
        <span class="pz-info-value"><?= esc((string)($staff['role'] ?? '—')) ?></span>
    </div>

    <div class="pz-card-actions">
        <a href="<?= base_url('staff/update/' . ($staffId ?? '')) ?>" class="pz-btn pz-btn-primary">
            <span class="material-symbols-outlined">edit</span> Edit
        </a>
        <a href="<?= base_url('staff') ?>" class="pz-btn pz-btn-outline">
            <span class="material-symbols-outlined">arrow_back</span> Back
        </a>
    </div>
</div>

<?= $this->endSection() ?>
