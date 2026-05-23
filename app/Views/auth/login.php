<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PrimaZentra</title>
    <link rel="stylesheet" href="<?= base_url('public/css/styles.css') ?>?v=<?= filemtime(FCPATH . 'public/css/styles.css') ?>">
</head>
<body class="auth-page">
    <div class="auth-shell auth-shell--login">
        <div class="auth-brand">
            <img src="<?= base_url('public/assets/primazentra_colored.png') ?>" alt="PrimaZentra" class="auth-logo">
        </div>

        <div class="auth-card">
            <h1>LOGIN</h1>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="auth-message auth-message--error">
                    <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>

            <form action="<?= base_url('auth/processLogin') ?>" method="post" class="auth-form">
                <div class="auth-field">
                    <label for="loginEmail">E-MAIL</label>
                    <div class="auth-input-wrap auth-input-wrap--icon-left">
                        <span class="auth-icon">✉</span>
                        <input type="email" id="loginEmail" name="email" placeholder="email@domain.com" value="<?= esc(old('email', $rememberedEmail ?? '')) ?>" required autofocus>
                    </div>
                </div>

                <div class="auth-field">
                    <label for="loginPassword">PASSWORD</label>
                    <div class="auth-input-wrap auth-input-wrap--icon-left auth-input-wrap--icon-right">
                        <span class="auth-icon">🔒</span>
                        <input type="password" id="loginPassword" name="password" placeholder="Password" required>
                        <button type="button" class="auth-icon-button" data-password-toggle="#loginPassword" aria-label="Show password">👁</button>
                    </div>
                    <div class="auth-link-row auth-link-row--right">
                        <a href="#">Forgot Password?</a>
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
    </script>
</body>
</html>