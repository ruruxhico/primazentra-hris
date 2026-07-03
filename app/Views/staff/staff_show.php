<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $staffId  = $staffId ?? '';
    $fullName = trim(($staff['first_name'] ?? '') . ' ' . ($staff['middle_name'] ? $staff['middle_name'] . ' ' : '') . ($staff['last_name'] ?? ''));
    $initials = strtoupper(substr($staff['first_name'] ?? 'S', 0, 1));
    $pic      = (string)($staff['profile_photo_url'] ?? '');
    if ($pic === '') {
        $picLocal = (string)($staff['profile_picture'] ?? '');
        $pic = ($picLocal !== '' && file_exists(FCPATH . $picLocal)) ? asset_url($picLocal) . '?v=' . time() : '';
    }
?>

<?php if (session()->getFlashdata('success')): ?>
<div class="pz-alert pz-alert-success" style="max-width:1100px;margin:0 auto .8rem">
    <span class="material-symbols-outlined" style="font-size:1rem">check_circle</span>
    <?= session()->getFlashdata('success') ?>
</div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="pz-alert pz-alert-error" style="max-width:1100px;margin:0 auto .8rem">
    <span class="material-symbols-outlined" style="font-size:1rem">error</span>
    <?= session()->getFlashdata('error') ?>
</div>
<?php endif; ?>

<div class="pd-shell">

    <!-- Back + Edit bar -->
    <div class="pd-topbar">
        <a href="<?= base_url('staff') ?>" class="pd-back">
            <span class="material-symbols-outlined">arrow_back</span>
            Staff
        </a>
        <div style="display:flex;gap:.5rem;align-items:center">
            <?php if (in_array($role ?? '', ['Admin', 'Operations Manager', 'Project Manager'])): ?>
            <form method="POST" action="<?= base_url('staff/resend-credentials/' . $staffId) ?>" id="resendForm">
                <?= csrf_field() ?>
                <button type="button" class="pd-edit-btn" onclick="confirmResend()">
                    <span class="material-symbols-outlined">send</span> Resend Credentials
                </button>
            </form>
            <?php endif; ?>
            <a href="<?= base_url('staff/update/' . $staffId) ?>" class="pd-edit-btn">
                <span class="material-symbols-outlined">edit</span> Edit
            </a>
        </div>
    </div>

    <!-- Name + avatar card -->
    <div class="pd-project-card" style="display:flex;align-items:center;gap:1.2rem">
        <?php if ($pic !== ''): ?>
            <img src="<?= esc($pic) ?>" alt="Profile"
                 style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:3px solid #d0dce8;flex-shrink:0">
        <?php else: ?>
            <div style="width:64px;height:64px;border-radius:50%;background:#e8eef5;border:3px solid #d0dce8;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:700;color:#1a3c5e;flex-shrink:0">
                <?= esc($initials) ?>
            </div>
        <?php endif; ?>
        <div>
            <div class="pd-project-name"><?= esc($fullName) ?></div>
            <div class="pd-project-type"><?= esc((string)($staff['role'] ?? '')) ?></div>
        </div>
    </div>

    <!-- Staff Details -->
    <div class="pd-section">
        <div class="pd-section-hdr">Staff Details</div>
        <div class="pd-section-body">
            <div class="pd-row">
                <span class="pd-row-label">Full Name</span>
                <span class="pd-row-value"><?= esc($fullName) ?></span>
            </div>
            <div class="pd-row">
                <span class="pd-row-label">Email</span>
                <span class="pd-row-value"><?= esc((string)($staff['email'] ?? '—')) ?></span>
            </div>
            <div class="pd-row">
                <span class="pd-row-label">Contact No</span>
                <span class="pd-row-value"><?= esc((string)($staff['contact_no'] ?? '—')) ?></span>
            </div>
            <div class="pd-row">
                <span class="pd-row-label">Role</span>
                <span class="pd-row-value"><?= esc((string)($staff['role'] ?? '—')) ?></span>
            </div>
            <?php if (!empty($staff['must_change_password'])): ?>
            <div class="pd-row">
                <span class="pd-row-label">Password Status</span>
                <span class="pd-row-value">
                    <span class="pz-badge pz-badge-pending">Temporary — not yet changed</span>
                </span>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /.pd-shell -->

<div id="staffShowConfirm" class="confirm-dialog" role="dialog" aria-modal="true">
    <div class="confirm-backdrop"></div>
    <div class="confirm-box">
        <div class="confirm-hdr">
            <span class="material-symbols-outlined confirm-hdr-icon" style="color:#1565c0;font-variation-settings:'FILL' 1">send</span>
            <p class="confirm-hdr-title">Resend Credentials</p>
        </div>
        <div class="confirm-content"><p id="staffShowConfirmMsg">Send a new temporary password to <strong><?= esc($staff['email'] ?? '') ?></strong>? The staff member will receive a new login email and must change the password within 15 minutes.</p></div>
        <div class="confirm-actions">
            <button id="staffShowConfirmOk"     class="confirm-btn" style="background:#1565c0;border-color:#1565c0;color:#fff;flex:1;display:inline-flex;align-items:center;justify-content:center;gap:.4rem;padding:.7rem 1rem;border-radius:8px;font-weight:700;font-size:1rem;cursor:pointer;">Send</button>
            <button id="staffShowConfirmCancel" class="confirm-btn confirm-btn-cancel">Cancel</button>
        </div>
    </div>
</div>
<script>
var _ssCr = null;
var ssCd  = document.getElementById('staffShowConfirm');
document.getElementById('staffShowConfirmOk')?.addEventListener('click', function () {
    ssCd.classList.remove('open');
    document.getElementById('resendForm').submit();
});
document.getElementById('staffShowConfirmCancel')?.addEventListener('click', function () {
    ssCd.classList.remove('open');
});
function confirmResend() {
    ssCd.classList.add('open');
}
</script>

<?= $this->endSection() ?>
