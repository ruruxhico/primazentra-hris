<?= $this->extend('layouts/auth') ?>


<?= $this->section('content') ?>
    <div class="auth-shell auth-shell--register">
        <div class="auth-brand">
            <img src="<?= asset_url('assets/primazentra_colored.png') ?>" alt="PrimaZentra" class="auth-logo">
        </div>

        <div class="auth-card auth-card--login">
            <h1>REGISTRATION</h1>

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

            <form action="<?= base_url('auth/processRegister') ?>" method="post" class="auth-form" novalidate id="registerForm" data-autosave>
                <input type="hidden" name="invite_token" value="<?= esc($invite_token) ?>">
                <div class="auth-section-label">
                    <span class="auth-section-icon material-symbols-outlined" aria-hidden="true">person</span>
                    <span>Personal Information</span>
                </div>

                <div class="form-row form-row--auth">
                    <div class="auth-field">
                        <label for="firstName">FIRST NAME *</label>
                        <div class="auth-input-wrap auth-input-wrap--icon-left">
                            <input type="text" id="firstName" name="firstName" placeholder="FIRST NAME" required autocomplete="given-name" autocapitalize="characters" class="name-uppercase" value="<?= esc(old('firstName')) ?>">
                        </div>
                    </div>
                    <div class="auth-field">
                        <label for="middleName">MIDDLE NAME</label>
                        <div class="auth-input-wrap auth-input-wrap--icon-left">
                            <input type="text" id="middleName" name="middleName" placeholder="MIDDLE NAME" autocomplete="additional-name" autocapitalize="characters" class="name-uppercase" value="<?= esc(old('middleName')) ?>">
                        </div>
                    </div>
                    <div class="auth-field">
                        <label for="lastName">LAST NAME *</label>
                        <div class="auth-input-wrap auth-input-wrap--icon-left">
                            <input type="text" id="lastName" name="lastName" placeholder="LAST NAME" required autocomplete="family-name" autocapitalize="characters" class="name-uppercase" value="<?= esc(old('lastName')) ?>">
                        </div>
                    </div>
                </div>

                <div class="auth-divider"></div>

                <div class="auth-section-label">
                    <span class="auth-section-icon material-symbols-outlined" aria-hidden="true">badge</span>
                    <span>Account Credentials</span>
                </div>

                <div class="auth-email-block">
                    <div class="auth-field">
                        <label for="email">EMAIL *</label>
                        <div class="auth-input-wrap auth-input-wrap--icon-left">
                            <input type="email" id="email" name="email" placeholder="EMAIL" required autocapitalize="off" autocorrect="off" value="<?= esc(old('email')) ?>">
                        </div>
                        <div class="field-error hidden" id="registerEmailError"></div>
                    </div>
                </div>

                <div class="form-row form-row--auth">
                    <div class="auth-field">
                        <label for="password">PASSWORD *</label>
                        <div class="auth-input-wrap auth-input-wrap--icon-left auth-input-wrap--icon-right">
                            <input type="password" id="password" name="password" placeholder="PASSWORD" required autocapitalize="off">
                            <button type="button" class="auth-icon-button" data-password-toggle="#password" aria-label="Show password">
                                <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                            </button>
                        </div>
                        <div class="field-error hidden" id="registerPasswordError"></div>
                    </div>
                    <div class="auth-field">
                        <label for="confirmPassword">CONFIRM PASSWORD *</label>
                        <div class="auth-input-wrap auth-input-wrap--icon-left auth-input-wrap--icon-right">
                            <input type="password" id="confirmPassword" name="confirm_password" placeholder="CONFIRM PASSWORD" required autocapitalize="off">
                            <button type="button" class="auth-icon-button" data-password-toggle="#confirmPassword" aria-label="Show password">
                                <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                            </button>
                        </div>
                        <div class="field-error hidden" id="registerConfirmError"></div>
                    </div>
                </div>

                <div class="auth-role-row">
                    <label class="auth-radio-pill">
                        <input type="radio" name="role_display" value="administrator" checked>
                        <span>Administrator</span>
                    </label>
                </div>

                <button type="submit" class="auth-submit">REGISTER</button>
            </form>

            <div class="auth-footer-link">
                Already have an account? <a href="<?= base_url('auth/login') ?>">Login here</a>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
    <script>
        document.addEventListener('click', function (event) {
            const toggle = event.target.closest('[data-password-toggle]');
            if (!toggle) return;

            const input = document.querySelector(toggle.getAttribute('data-password-toggle'));
            if (!input) return;

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            toggle.textContent = isPassword ? '◫' : '👁';
            toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        });

        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('.auth-form');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirmPassword');

            form.addEventListener('submit', function (event) {
                if (password.value !== confirmPassword.value) {
                    event.preventDefault();
                    const confirmError = document.getElementById('registerConfirmError');
                    if (confirmError) {
                        confirmError.textContent = 'Passwords do not match.';
                        confirmError.classList.remove('hidden');
                    }
                    confirmPassword.focus();
                    return;
                }
            });

            confirmPassword.addEventListener('input', function () {
                const confirmError = document.getElementById('registerConfirmError');
                if (confirmError) {
                    confirmError.textContent = '';
                    confirmError.classList.add('hidden');
                }
            });
        });
    </script>
<?= $this->endSection() ?>