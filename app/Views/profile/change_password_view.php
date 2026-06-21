<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="resource-header">
    <h1>Change Password</h1>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert-error">✗ <?= session()->getFlashdata('error') ?></div>
<?php endif; ?>


<div class="pw-shell">
    <div class="pw-card">
        <h3>Update Password</h3>
        <form action="<?= base_url('profile/change-password') ?>" method="post" id="changePwForm">
            <?= csrf_field() ?>
            <div class="pw-field">
                <label>Current Password *</label>
                <input type="password" name="current_password" required autocomplete="current-password">
            </div>
            <div class="pw-field">
                <label>New Password *</label>
                <input type="password" name="password" id="newPw" required autocomplete="new-password">
                <div class="pw-hint">At least 8 characters.</div>
            </div>
            <div class="pw-field">
                <label>Confirm New Password *</label>
                <input type="password" name="confirm_password" id="confirmPw" required autocomplete="new-password">
                <div class="pw-hint" id="pwMatchHint" style="display:none;color:#c62828;">Passwords do not match.</div>
            </div>
            <div class="pw-actions">
                <button type="submit" class="btn-pw-save">Update Password</button>
                <a href="<?= base_url('profile') ?>" class="btn-pw-back">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    var pw1 = document.getElementById('newPw');
    var pw2 = document.getElementById('confirmPw');
    var hint = document.getElementById('pwMatchHint');

    function check() {
        if (pw2.value && pw1.value !== pw2.value) {
            hint.style.display = '';
        } else {
            hint.style.display = 'none';
        }
    }

    pw1.addEventListener('input', check);
    pw2.addEventListener('input', check);

    document.getElementById('changePwForm').addEventListener('submit', function (e) {
        if (pw1.value.length < 8) {
            e.preventDefault();
            alert('New password must be at least 8 characters.');
            pw1.focus();
            return;
        }
        if (pw1.value !== pw2.value) {
            e.preventDefault();
            hint.style.display = '';
            pw2.focus();
        }
    });
}());
</script>
<?= $this->endSection() ?>
