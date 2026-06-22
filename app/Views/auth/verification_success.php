<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
    <div class="auth-shell auth-shell--login">
        <div class="auth-brand">
            <img src="<?= asset_url('assets/primazentra_colored.png') ?>" alt="PrimaZentra" class="auth-logo">
        </div>

        <div class="auth-card auth-card--login" style="text-align:center;">
            <span class="material-symbols-outlined" style="font-size:56px; color:#b8860b; display:block; margin-bottom:12px;">
                verified_user
            </span>
            <h1>Account Verified</h1>
            <p style="color:#555; line-height:1.7;">
                Your account has been verified and your password has been set successfully.
                You can now log in to the <strong>PrimaZentra mobile app</strong> using your email and new password.
            </p>

            <div class="auth-submit-wrap" style="margin-top:24px;">
                <a href="<?= base_url('login') ?>" class="auth-submit" style="display:block; text-align:center; text-decoration:none;">
                    Go to Login
                </a>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>
