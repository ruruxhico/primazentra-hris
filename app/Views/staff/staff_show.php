<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<a href="<?= base_url('staff') ?>" class="pz-back-link">
    <span class="material-symbols-outlined">arrow_back</span> Staff
</a>

<?php if (session()->getFlashdata('success')): ?>
    <div class="pz-alert pz-alert-success">
        <span class="material-symbols-outlined" style="font-size:1rem">check_circle</span>
        <?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="pz-alert pz-alert-error">
        <span class="material-symbols-outlined" style="font-size:1rem">error</span>
        <?= session()->getFlashdata('error') ?>
    </div>
<?php endif; ?>

<div class="pz-page-header" style="display:flex;align-items:center;gap:20px">
    <?php
        $pic = $staff['profile_picture'] ?? '';
        $initials = strtoupper(substr($staff['first_name'] ?? 'S', 0, 1));
    ?>
    <?php if ($pic !== ''): ?>
        <img src="<?= asset_url($pic) ?>?v=<?= time() ?>" alt="Profile"
             style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid #d0dce8;flex-shrink:0">
    <?php else: ?>
        <div style="width:72px;height:72px;border-radius:50%;background:#e8eef5;border:3px solid #d0dce8;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;color:#1a3c5e;flex-shrink:0">
            <?= esc($initials) ?>
        </div>
    <?php endif; ?>
    <div>
        <h1><?= esc((string)(($staff['first_name'] ?? '') . ' ' . ($staff['middle_name'] ? $staff['middle_name'] . ' ' : '') . ($staff['last_name'] ?? ''))) ?></h1>
        <p><?= esc((string)($staff['role'] ?? '')) ?></p>
    </div>
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

        <?php if (!empty($staff['must_change_password'])): ?>
        <span class="pz-info-label">Password Status</span>
        <span class="pz-info-value">
            <span class="pz-badge pz-badge-pending">Temporary password not yet changed</span>
        </span>
        <?php endif; ?>
    </div>

    <div class="pz-card-actions">
        <a href="<?= base_url('staff/update/' . ($staffId ?? '')) ?>" class="pz-btn pz-btn-primary">
            <span class="material-symbols-outlined">edit</span> Edit
        </a>
        <?php if (in_array($role ?? '', ['Admin', 'Operations Manager', 'Project Manager'])): ?>
        <form method="POST" action="<?= base_url('staff/resend-credentials/' . ($staffId ?? '')) ?>" id="resendForm" style="display:inline">
            <?= csrf_field() ?>
            <button type="button" class="pz-btn pz-btn-outline" onclick="confirmResend()">
                <span class="material-symbols-outlined">send</span> Resend Credentials
            </button>
        </form>
        <?php endif; ?>
        <a href="<?= base_url('staff') ?>" class="pz-btn pz-btn-ghost">
            <span class="material-symbols-outlined">arrow_back</span> Back
        </a>
    </div>
</div>

<script>
function confirmResend() {
    if (confirm('Send a new temporary password to <?= esc(addslashes($staff['email'] ?? '')) ?>?\n\nThe staff member will receive a new login email and must change the password within 15 minutes.')) {
        document.getElementById('resendForm').submit();
    }
}
</script>

<?= $this->endSection() ?>
