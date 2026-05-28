<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
    <div class="auth-shell auth-shell--login">
        <div class="auth-brand">
            <img src="<?= base_url('public/assets/primazentra_colored.png') ?>" alt="PrimaZentra" class="auth-logo">
        </div>

        <div class="auth-card auth-card--login">
            <h1>Reset Password</h1>

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

            <p class="auth-email-display">Resetting password for <strong><?= esc($email ?? '') ?></strong></p>

            <form action="<?= base_url('auth/processResetPassword') ?>" method="post" class="auth-form" novalidate id="resetForm">
                <input type="hidden" name="oobCode" value="<?= esc($oobCode ?? '') ?>">

                <div class="auth-field">
                    <label for="newPassword">NEW PASSWORD</label>
                    <div class="auth-input-wrap auth-input-wrap--icon-left auth-input-wrap--icon-right">
                        <span class="auth-icon material-symbols-outlined" aria-hidden="true">lock</span>
                        <input type="password" id="newPassword" name="password" placeholder="Enter new password" required>
                        <button type="button" class="auth-icon-button" data-password-toggle="#newPassword" aria-label="Show password">
                            <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                        </button>
                    </div>
                    <div class="field-error hidden" id="newPasswordError"></div>
                </div>

                <div class="auth-field">
                    <label for="confirmNewPassword">CONFIRM PASSWORD</label>
                    <div class="auth-input-wrap auth-input-wrap--icon-left auth-input-wrap--icon-right">
                        <span class="auth-icon material-symbols-outlined" aria-hidden="true">password</span>
                        <input type="password" id="confirmNewPassword" name="confirm_password" placeholder="Repeat new password" required>
                        <button type="button" class="auth-icon-button" data-password-toggle="#confirmNewPassword" aria-label="Show password">
                            <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                        </button>
                    </div>
                    <div class="field-error hidden" id="confirmPasswordError"></div>
                    <div class="pw-strength" aria-hidden="true"><i id="pwBar"></i></div>
                    <ul class="pw-rules" id="pwRules">
                        <li id="ruleLength">At least 8 characters</li>
                        <li id="ruleUpper">Uppercase & lowercase letters</li>
                        <li id="ruleNumber">At least one number</li>
                        <li id="ruleSpecial">At least one special character</li>
                    </ul>
                </div>

                <div class="auth-submit-wrap">
                    <button type="submit" class="auth-submit">Reset Password</button>
                </div>
            </form>

            <div class="auth-footer-link">
                Back to <a href="<?= base_url('auth/login') ?>">login</a>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Password toggle handlers
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

    // Strength & match validation
    (function () {
        const pw = document.getElementById('newPassword');
        const pw2 = document.getElementById('confirmNewPassword');
        const pwErr = document.getElementById('newPasswordError');
        const pw2Err = document.getElementById('confirmPasswordError');
        const pwBar = document.getElementById('pwBar');
        const rules = {
            length: document.getElementById('ruleLength'),
            upper: document.getElementById('ruleUpper'),
            number: document.getElementById('ruleNumber'),
            special: document.getElementById('ruleSpecial')
        };

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

        function scorePassword(val) {
            let score = 0;
            if (val.length >= 8) score += 25;
            if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score += 25;
            if (/[0-9]/.test(val)) score += 25;
            if (/[^A-Za-z0-9]/.test(val)) score += 25;
            return score;
        }

        function updateRules(val) {
            rules.length.style.opacity = val.length >= 8 ? '1' : '0.45';
            rules.upper.style.opacity = /[A-Z]/.test(val) && /[a-z]/.test(val) ? '1' : '0.45';
            rules.number.style.opacity = /[0-9]/.test(val) ? '1' : '0.45';
            rules.special.style.opacity = /[^A-Za-z0-9]/.test(val) ? '1' : '0.45';
        }

        function updateBar(val) {
            const s = scorePassword(val);
            pwBar.style.width = s + '%';
            if (s < 50) pwBar.style.background = '#f39c12'; else pwBar.style.background = '#27ae60';
        }

        function validateMatch() {
            clearError(pwErr);
            clearError(pw2Err);

            if (pw.value && pw.value.length < 8) {
                showError(pwErr, 'Password must be at least 8 characters long.');
                return false;
            }

            if (pw.value !== pw2.value) {
                if (pw2.value !== '') {
                    showError(pw2Err, 'Passwords do not match.');
                }
                return false;
            }

            return true;
        }

        pw.addEventListener('input', function () {
            updateRules(pw.value);
            updateBar(pw.value);
            validateMatch();
        });

        pw2.addEventListener('input', validateMatch);

        document.getElementById('resetForm').addEventListener('submit', function (e) {
            const valid = validateMatch();
            if (pw.value === '' || pw2.value === '' || !valid) {
                e.preventDefault();
                if (pw.value === '') {
                    showError(pwErr, 'Password is required.');
                    pw.focus();
                } else if (pw2.value === '') {
                    showError(pw2Err, 'Please confirm your password.');
                    pw2.focus();
                } else {
                    pw2.focus();
                }
            }
        });
    })();
</script>
<?= $this->endSection() ?>
