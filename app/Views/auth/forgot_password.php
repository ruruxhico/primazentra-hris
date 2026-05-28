<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
    <div class="auth-shell auth-shell--login">
        <div class="auth-brand">
            <img src="<?= base_url('public/assets/primazentra_colored.png') ?>" alt="PrimaZentra" class="auth-logo">
        </div>

        <div class="auth-card auth-card--login">
            <h1>FORGOT PASSWORD</h1>

            <?php if (session()->getFlashdata('success')): ?>
                <div class="auth-message auth-message--success">
                    <?= session()->getFlashdata('success') ?>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="auth-message auth-message--error">
                    <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>

            <p class="auth-note">
                Enter the email address linked to your account. Password reset links are only sent to registered accounts.
            </p>

            <form action="<?= base_url('auth/processForgotPassword') ?>" method="post" class="auth-form">
                <div class="auth-field">
                    <label for="resetEmail">E-MAIL</label>
                    <div class="auth-input-wrap auth-input-wrap--icon-left">
                        <span class="auth-icon">✉</span>
                        <input type="email" id="resetEmail" name="email" placeholder="email@domain.com" value="<?= esc(old('email')) ?>" required autofocus>
                    </div>
                </div>

                <button type="submit" class="auth-submit">SEND RESET LINK</button>
            </form>

            <div class="auth-footer-link">
                Remembered your password? <a href="<?= base_url('auth/login') ?>">Back to login</a>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>