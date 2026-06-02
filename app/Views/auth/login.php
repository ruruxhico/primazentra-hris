<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
    <div class="auth-shell auth-shell--login">
        <div class="auth-brand">
            <img src="<?= base_url('assets/primazentra_colored.png') ?>" alt="PrimaZentra" class="auth-logo">
        </div>

        <div class="auth-card auth-card--login">
            <h1>LOGIN</h1>

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

            <form action="<?= base_url('auth/processLogin') ?>" method="post" class="auth-form" novalidate id="loginForm">
                <div class="auth-field">
                    <label for="loginEmail">E-MAIL</label>
                    <div class="auth-input-wrap auth-input-wrap--icon-left">
                        <span class="auth-icon material-symbols-outlined" aria-hidden="true">mail</span>
                        <input type="email" id="loginEmail" name="email" placeholder="email@domain.com" value="<?= esc(old('email', $rememberedEmail ?? '')) ?>" required autofocus>
                    </div>
                    <div class="field-error hidden" id="loginEmailError"></div>
                </div>

                <div class="auth-field">
                    <label for="loginPassword">PASSWORD</label>
                    <div class="auth-input-wrap auth-input-wrap--icon-left auth-input-wrap--icon-right">
                        <span class="auth-icon material-symbols-outlined" aria-hidden="true">lock</span>
                        <input type="password" id="loginPassword" name="password" placeholder="Password" required>
                        <button type="button" class="auth-icon-button" data-password-toggle="#loginPassword" aria-label="Show password">
                            <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                        </button>
                    </div>
                    <div class="field-error hidden" id="loginPasswordError"></div>
                    <div class="auth-link-row auth-link-row--right">
                        <a href="<?= base_url('forgot-password') ?>">Forgot Password?</a>
                    </div>
                </div>

                <div class="auth-options-row">
                    <label class="auth-check">
                        <input type="checkbox" name="remember_me" value="1" <?= old('remember_me') ? 'checked' : (($rememberChecked ?? false) ? 'checked' : '') ?>>
                        <span>Remember me</span>
                    </label>
                </div>

                <button type="submit" class="auth-submit">LOGIN</button>
            </form>

            <div class="auth-footer-link">
                No account yet? <a href="<?= base_url('auth/register') ?>">Register here</a>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Custom client-side validation for login (no native browser tooltips)
    (function () {
        const form = document.getElementById('loginForm');
        const email = document.getElementById('loginEmail');
        const pass = document.getElementById('loginPassword');
        const emailErr = document.getElementById('loginEmailError');
        const passErr = document.getElementById('loginPasswordError');

        function showError(el, msg) {
            if (!el) return;
            el.textContent = msg;
            el.classList.remove('hidden');
        }

        function clearError(el) {
            if (!el) return;
            el.textContent = '';
            el.classList.add('hidden');
        }

        function isValidEmail(v) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
        }

        email.addEventListener('input', function () { clearError(emailErr); });
        pass.addEventListener('input', function () { clearError(passErr); });

        form.addEventListener('submit', function (e) {
            let firstError = null;
            if (!email.value || !isValidEmail(email.value.trim())) {
                showError(emailErr, 'Please enter a valid email address.');
                firstError = firstError || email;
            }

            if (!pass.value) {
                showError(passErr, 'Password is required.');
                firstError = firstError || pass;
            }

            if (firstError) {
                e.preventDefault();
                firstError.focus();
            }
        });
    })();

    document.addEventListener('click', function (event) {
        const toggle = event.target.closest('[data-password-toggle]');
        if (!toggle) return;

        const input = document.querySelector(toggle.getAttribute('data-password-toggle'));
        if (!input) return;

        const icon = toggle.querySelector('.material-symbols-outlined');

        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        if (icon) {
            icon.textContent = isPassword ? 'visibility_off' : 'visibility';
        }
        toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    });
</script>
<?= $this->endSection() ?>