<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
    <div class="auth-shell auth-shell--login">
        <div class="auth-brand">
            <img src="<?= asset_url('assets/primazentra_colored.png') ?>" alt="PrimaZentra" class="auth-logo">
        </div>

        <div class="auth-card auth-card--login">
            <h1>Set New Password</h1>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="auth-message auth-message--error">
                    <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>

            <p class="auth-email-display" style="text-align:center;margin-bottom:.6rem;font-size:.88rem;color:#7a6040;">
                Your account was created with a temporary password.<br>
                Please set a new password to continue.
            </p>

            <?php if (!empty($tempPasswordExpiresAt)): ?>
            <div id="expiryBanner" style="text-align:center;margin-bottom:1rem;padding:.45rem .8rem;border-radius:8px;background:#fff8e1;border:1px solid #d4a910;font-size:.84rem;font-weight:600;color:#7a5c1e;">
                <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle;margin-right:.2rem;">timer</span>
                Temporary password expires in: <span id="expiryCountdown" style="color:#b8860b;font-variant-numeric:tabular-nums;">--:--:--</span>
            </div>
            <script>
                (function () {
                    var expiresAt = <?= (int) $tempPasswordExpiresAt ?> * 1000;
                    var banner = document.getElementById('expiryBanner');
                    var countdown = document.getElementById('expiryCountdown');

                    function tick() {
                        var remaining = expiresAt - Date.now();
                        if (remaining <= 0) {
                            banner.style.background = '#fdecea';
                            banner.style.borderColor = '#e53935';
                            banner.style.color = '#c62828';
                            countdown.textContent = 'EXPIRED';
                            countdown.style.color = '#c62828';
                            clearInterval(timer);
                            return;
                        }
                        var h = Math.floor(remaining / 3600000);
                        var m = Math.floor((remaining % 3600000) / 60000);
                        var s = Math.floor((remaining % 60000) / 1000);
                        countdown.textContent =
                            String(h).padStart(2, '0') + ':' +
                            String(m).padStart(2, '0') + ':' +
                            String(s).padStart(2, '0');

                        if (remaining < 600000) { // last 10 minutes — turn red
                            banner.style.background = '#fdecea';
                            banner.style.borderColor = '#e53935';
                            countdown.style.color = '#c62828';
                        }
                    }

                    tick();
                    var timer = setInterval(tick, 1000);
                })();
            </script>
            <?php endif; ?>

            <form action="<?= base_url('auth/processChangePassword') ?>" method="post" class="auth-form" novalidate id="changeForm">
                <?= csrf_field() ?>

                <div class="auth-field">
                    <label for="newPassword">NEW PASSWORD</label>
                    <div class="auth-input-wrap auth-input-wrap--icon-left auth-input-wrap--icon-right">
                        <span class="auth-icon material-symbols-outlined" aria-hidden="true">lock</span>
                        <input type="password" id="newPassword" name="password" placeholder="Enter new password" required autofocus>
                        <button type="button" class="auth-icon-button" data-password-toggle="#newPassword" aria-label="Show password">
                            <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                        </button>
                    </div>
                    <div class="field-error hidden" id="newPasswordError"></div>
                </div>

                <div class="auth-field">
                    <label for="confirmPassword">CONFIRM PASSWORD</label>
                    <div class="auth-input-wrap auth-input-wrap--icon-left auth-input-wrap--icon-right">
                        <span class="auth-icon material-symbols-outlined" aria-hidden="true">password</span>
                        <input type="password" id="confirmPassword" name="confirm_password" placeholder="Repeat new password" required>
                        <button type="button" class="auth-icon-button" data-password-toggle="#confirmPassword" aria-label="Show password">
                            <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                        </button>
                    </div>
                    <div class="field-error hidden" id="confirmPasswordError"></div>
                    <div class="pw-strength" aria-hidden="true"><i id="pwBar"></i></div>
                    <ul class="pw-rules" id="pwRules">
                        <li id="ruleLength">At least 8 characters</li>
                        <li id="ruleUpper">Uppercase &amp; lowercase letters</li>
                        <li id="ruleNumber">At least one number</li>
                        <li id="ruleSpecial">At least one special character</li>
                    </ul>
                </div>

                <div class="auth-submit-wrap">
                    <button type="submit" class="auth-submit" style="white-space:nowrap;width:fit-content">Set Password &amp; Re-Login</button>
                </div>
            </form>

            <div class="auth-footer-link">
                <form action="<?= base_url('auth/logout') ?>" method="post" style="display:inline">
                    <?= csrf_field() ?>
                    <button type="submit" style="background:none;border:none;color:#9a7b3a;font-size:.9rem;cursor:pointer;text-decoration:underline;padding:0;font-weight:700;">
                        Sign out
                    </button>
                </form>
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
        const icon = toggle.querySelector('.material-symbols-outlined');
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        if (icon) icon.textContent = isPassword ? 'visibility_off' : 'visibility';
        toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    });

    (function () {
        const pw  = document.getElementById('newPassword');
        const pw2 = document.getElementById('confirmPassword');
        const pwErr  = document.getElementById('newPasswordError');
        const pw2Err = document.getElementById('confirmPasswordError');
        const pwBar  = document.getElementById('pwBar');
        const rules  = {
            length:  document.getElementById('ruleLength'),
            upper:   document.getElementById('ruleUpper'),
            number:  document.getElementById('ruleNumber'),
            special: document.getElementById('ruleSpecial'),
        };

        function showError(el, msg) { if (!el) return; el.textContent = msg; el.classList.remove('hidden'); }
        function clearError(el)     { if (!el) return; el.textContent = ''; el.classList.add('hidden'); }

        function score(val) {
            let s = 0;
            if (val.length >= 8) s += 25;
            if (/[A-Z]/.test(val) && /[a-z]/.test(val)) s += 25;
            if (/[0-9]/.test(val)) s += 25;
            if (/[^A-Za-z0-9]/.test(val)) s += 25;
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
            pwBar.style.width = s + '%';
            pwBar.style.background = s < 50 ? '#f39c12' : '#27ae60';
        }

        function validateMatch() {
            clearError(pwErr); clearError(pw2Err);
            if (pw.value && pw.value.length < 8) { showError(pwErr, 'Password must be at least 8 characters.'); return false; }
            if (pw.value !== pw2.value) { if (pw2.value) showError(pw2Err, 'Passwords do not match.'); return false; }
            return true;
        }

        pw.addEventListener('input',  function () { updateRules(pw.value); updateBar(pw.value); validateMatch(); });
        pw2.addEventListener('input', validateMatch);

        document.getElementById('changeForm').addEventListener('submit', function (e) {
            const valid = validateMatch();
            if (!pw.value || !pw2.value || !valid) {
                e.preventDefault();
                if (!pw.value)       { showError(pwErr,  'Password is required.'); pw.focus(); }
                else if (!pw2.value) { showError(pw2Err, 'Please confirm your password.'); pw2.focus(); }
                else                 { pw2.focus(); }
            }
        });
    })();
</script>
<?= $this->endSection() ?>
