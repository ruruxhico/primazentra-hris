<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="resource-header">
    <h1>Change Password</h1>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert-error">✗ <?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<style>
.pw-shell { max-width:480px; margin:0 auto; }
.pw-card { background:#fff; border:1px solid #e5e0d0; border-radius:10px; padding:1.6rem 1.8rem; }
.pw-card h3 { font-size:0.92rem; font-weight:700; text-transform:uppercase; color:#5c3d11; letter-spacing:.4px; margin:0 0 1rem; }
.pw-field { margin-bottom:1rem; }
.pw-field label { display:block; font-size:0.78rem; font-weight:700; text-transform:uppercase; color:#666; letter-spacing:.4px; margin-bottom:4px; }
.pw-field input { width:100%; padding:8px 10px; border:1px solid #ccc; border-radius:6px; font-size:0.92rem; font-family:inherit; }
.pw-actions { display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap; margin-top:0.5rem; }
.btn-pw-save { background:#8C701C; color:#fff; border:none; border-radius:6px; padding:9px 22px; font-size:0.9rem; font-weight:700; cursor:pointer; font-family:inherit; }
.btn-pw-save:hover { background:#6e570f; }
.btn-pw-back { background:#f5f0e4; color:#5c3d11; border:1px solid #d4c185; border-radius:6px; padding:8px 18px; font-size:0.9rem; font-weight:600; cursor:pointer; text-decoration:none; font-family:inherit; }
.btn-pw-back:hover { background:#ede3c6; }
.pw-hint { font-size:0.78rem; color:#888; margin-top:4px; }
</style>

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
