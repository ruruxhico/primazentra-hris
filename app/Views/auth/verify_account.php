<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
    <div class="auth-shell auth-shell--login">
        <div class="auth-brand">
            <img src="<?= asset_url('assets/primazentra_colored.png') ?>" alt="PrimaZentra" class="auth-logo">
        </div>

        <div class="auth-card auth-card--login">
            <h1>Verify Account</h1>
            <p class="auth-email-display">Setting password for <strong><?= esc($email ?? '') ?></strong></p>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="auth-message auth-message--error">
                    <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>

            <form action="<?= base_url('verify-account/' . esc($uid ?? '')) ?>" method="post" class="auth-form" novalidate id="verifyForm">
                <?= csrf_field() ?>

                <div class="auth-field">
                    <label for="password">NEW PASSWORD</label>
                    <div class="auth-input-wrap auth-input-wrap--icon-left auth-input-wrap--icon-right">
                        <span class="auth-icon material-symbols-outlined" aria-hidden="true">lock</span>
                        <input type="password" id="password" name="password" placeholder="Enter new password" required>
                        <button type="button" class="auth-icon-button" data-password-toggle="#password" aria-label="Show password">
                            <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                        </button>
                    </div>
                    <div class="field-error hidden" id="passwordError"></div>
                </div>

                <div class="auth-field">
                    <label for="confirm_password">CONFIRM PASSWORD</label>
                    <div class="auth-input-wrap auth-input-wrap--icon-left auth-input-wrap--icon-right">
                        <span class="auth-icon material-symbols-outlined" aria-hidden="true">password</span>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat new password" required>
                        <button type="button" class="auth-icon-button" data-password-toggle="#confirm_password" aria-label="Show password">
                            <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                        </button>
                    </div>
                    <div class="field-error hidden" id="confirmError"></div>
                    <div class="pw-strength" aria-hidden="true"><i id="pwBar"></i></div>
                    <ul class="pw-rules" id="pwRules">
                        <li id="ruleLength">At least 8 characters</li>
                        <li id="ruleUpper">Uppercase &amp; lowercase letters</li>
                        <li id="ruleNumber">At least one number</li>
                        <li id="ruleSpecial">At least one special character</li>
                    </ul>
                </div>

                <div class="auth-submit-wrap">
                    <button type="submit" class="auth-submit">Verify &amp; Set Password</button>
                </div>
            </form>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('click', function (e) {
        const toggle = e.target.closest('[data-password-toggle]');
        if (!toggle) return;
        const input = document.querySelector(toggle.getAttribute('data-password-toggle'));
        if (!input) return;
        const icon = toggle.querySelector('.material-symbols-outlined');
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        if (icon) icon.textContent = isPassword ? 'visibility_off' : 'visibility';
        toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    });

    (function () {
        const pw   = document.getElementById('password');
        const pw2  = document.getElementById('confirm_password');
        const pwErr  = document.getElementById('passwordError');
        const pw2Err = document.getElementById('confirmError');
        const pwBar  = document.getElementById('pwBar');
        const rules  = {
            length:  document.getElementById('ruleLength'),
            upper:   document.getElementById('ruleUpper'),
            number:  document.getElementById('ruleNumber'),
            special: document.getElementById('ruleSpecial'),
        };

        function showError(el, msg) { if (el) { el.textContent = msg; el.classList.remove('hidden'); } }
        function clearError(el)     { if (el) { el.textContent = '';  el.classList.add('hidden');    } }

        function score(val) {
            let s = 0;
            if (val.length >= 8)                         s += 25;
            if (/[A-Z]/.test(val) && /[a-z]/.test(val)) s += 25;
            if (/[0-9]/.test(val))                       s += 25;
            if (/[^A-Za-z0-9]/.test(val))                s += 25;
            return s;
        }

        function updateRules(val) {
            rules.length.style.opacity  = val.length >= 8 ? '1' : '0.45';
            rules.upper.style.opacity   = /[A-Z]/.test(val) && /[a-z]/.test(val) ? '1' : '0.45';
            rules.number.style.opacity  = /[0-9]/.test(val) ? '1' : '0.45';
            rules.special.style.opacity = /[^A-Za-z0-9]/.test(val) ? '1' : '0.45';
        }

        function updateBar(val) {
            const s = score(val);
            pwBar.style.width      = s + '%';
            pwBar.style.background = s < 50 ? '#f39c12' : '#27ae60';
        }

        function validateMatch() {
            clearError(pwErr);
            clearError(pw2Err);
            if (pw.value && pw.value.length < 8) { showError(pwErr, 'Password must be at least 8 characters.'); return false; }
            if (pw.value !== pw2.value) { if (pw2.value) showError(pw2Err, 'Passwords do not match.'); return false; }
            return true;
        }

        pw.addEventListener('input',  function () { updateRules(pw.value); updateBar(pw.value); validateMatch(); });
        pw2.addEventListener('input', validateMatch);

        document.getElementById('verifyForm').addEventListener('submit', function (e) {
            if (!validateMatch() || pw.value === '' || pw2.value === '') {
                e.preventDefault();
                if (!pw.value)  { showError(pwErr,  'Password is required.'); pw.focus(); }
                else if (!pw2.value) { showError(pw2Err, 'Please confirm your password.'); pw2.focus(); }
            }
        });
    })();
</script>
<?= $this->endSection() ?>
