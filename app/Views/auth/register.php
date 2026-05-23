<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - PrimaZentra</title>
    <link rel="stylesheet" href="<?= base_url('public/css/styles.css') ?>?v=<?= filemtime(FCPATH . 'public/css/styles.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.12/build/css/intlTelInput.css">
</head>
<body class="auth-page">
    <div class="auth-shell auth-shell--register">
        <div class="auth-brand">
            <img src="<?= base_url('public/assets/primazentra_colored.png') ?>" alt="PrimaZentra" class="auth-logo">
        </div>

        <div class="auth-card auth-card--wide">
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

            <form action="<?= base_url('auth/processRegister') ?>" method="post" class="auth-form">
                <div class="auth-section-label">
                    <span class="auth-section-icon">👤</span>
                    <span>Personal Information</span>
                </div>

                <div class="form-row form-row--auth">
                    <div class="auth-field">
                        <label for="firstName">FIRST NAME *</label>
                        <input type="text" id="firstName" name="firstName" placeholder="FIRST NAME" required>
                    </div>
                    <div class="auth-field">
                        <label for="middleName">MIDDLE NAME</label>
                        <input type="text" id="middleName" name="middleName" placeholder="MIDDLE NAME">
                    </div>
                    <div class="auth-field">
                        <label for="lastName">LAST NAME *</label>
                        <input type="text" id="lastName" name="lastName" placeholder="LAST NAME" required>
                    </div>
                </div>

                <div class="auth-divider"></div>

                <div class="auth-section-label">
                    <span class="auth-section-icon">🪪</span>
                    <span>Account Credentials</span>
                </div>

                <div class="auth-email-block">
                    <div class="auth-field">
                        <label for="email">EMAIL *</label>
                        <div class="auth-input-wrap auth-input-wrap--icon-left">
                            <input type="email" id="email" name="email" placeholder="EMAIL" required>
                        </div>
                    </div>
                </div>

                <div class="form-row form-row--auth">
                    <div class="auth-field">
                        <label for="password">PASSWORD *</label>
                        <div class="auth-input-wrap auth-input-wrap--icon-left">
                            <input type="password" id="password" name="password" placeholder="PASSWORD" required>
                        </div>
                    </div>
                    <div class="auth-field">
                        <label for="confirmPassword">CONFIRM PASSWORD *</label>
                        <div class="auth-input-wrap auth-input-wrap--icon-left">
                            <input type="password" id="confirmPassword" name="confirm_password" placeholder="CONFIRM PASSWORD" required>
                        </div>
                    </div>
                </div>

                <div class="auth-contact-block">
                    <div class="auth-field auth-field--full">
                        <label for="contactNumber">CONTACT NUMBER *</label>
                        <input
                            type="tel"
                            id="contactNumber"
                            name="contact_number_display"
                            placeholder="Phone number"
                            inputmode="tel"
                            autocomplete="tel"
                            required
                        >
                        <input type="hidden" name="country_code" id="countryCode" value="PH">
                        <input type="hidden" name="contact_number" id="contactNumberFull" value="">
                        <small class="phone-hint" id="phoneHint">Include your active mobile number.</small>
                        <small class="phone-error" id="phoneError" hidden>Please enter a valid phone number for the selected country.</small>
                    </div>
                </div>

                <div class="auth-role-row">
                    <label class="auth-radio-pill">
                        <input type="radio" name="role_display" value="executive" checked>
                        <span>Executive</span>
                    </label>
                </div>

                <button type="submit" class="auth-submit">REGISTER</button>
            </form>

            <div class="auth-footer-link">
                Already have an account? <a href="<?= base_url('auth/login') ?>">Login here</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.12/build/js/intlTelInput.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.12/build/js/utils.js"></script>
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
            const phoneInput = document.getElementById('contactNumber');
            const countryCodeInput = document.getElementById('countryCode');
            const hiddenContact = document.getElementById('contactNumberFull');
            const phoneHint = document.getElementById('phoneHint');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirmPassword');

            let phoneInstance = null;
            if (window.intlTelInput && phoneInput && hiddenContact && countryCodeInput) {
                phoneInstance = window.intlTelInput(phoneInput, {
                    initialCountry: 'ph',
                    preferredCountries: ['ph', 'us', 'sg', 'au', 'gb'],
                    separateDialCode: true,
                    autoPlaceholder: 'aggressive',
                    formatOnDisplay: true,
                    strictMode: false,
                    utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.12/build/js/utils.js'
                });

                const syncPhoneFields = function () {
                    const countryData = phoneInstance.getSelectedCountryData() || {};
                    countryCodeInput.value = String(countryData.iso2 || 'ph').toUpperCase();
                    hiddenContact.value = phoneInstance.getNumber() || phoneInput.value.trim();
                    phoneHint.textContent = 'Selected: ' + (countryData.name || 'Country') + ' (' + (countryData.dialCode ? '+' + countryData.dialCode : '') + ')';
                };

                phoneInput.addEventListener('countrychange', syncPhoneFields);
                phoneInput.addEventListener('blur', syncPhoneFields);
                phoneInput.addEventListener('input', syncPhoneFields);
                syncPhoneFields();
            }

            const setPhoneValidity = function () {
                const phoneError = document.getElementById('phoneError');

                if (!phoneInstance || !phoneInput) {
                    return true;
                }

                const value = phoneInput.value.trim();
                if (value === '') {
                    phoneInput.setCustomValidity('Contact number is required.');
                    if (phoneError) {
                        phoneError.textContent = 'Contact number is required.';
                        phoneError.hidden = false;
                    }
                    return false;
                }

                const isValid = phoneInstance.isValidNumber();
                if (!isValid) {
                    phoneInput.setCustomValidity('Please enter a valid phone number for the selected country.');
                    if (phoneError) {
                        phoneError.textContent = 'Please enter a valid phone number for the selected country.';
                        phoneError.hidden = false;
                    }
                    return false;
                }

                phoneInput.setCustomValidity('');
                if (phoneError) {
                    phoneError.hidden = true;
                }

                return true;
            };

            if (phoneInput) {
                phoneInput.addEventListener('blur', setPhoneValidity);
                phoneInput.addEventListener('input', function () {
                    if (phoneInput.value.trim() !== '') {
                        setPhoneValidity();
                    }
                });
            }

            form.addEventListener('submit', function (event) {
                if (password.value !== confirmPassword.value) {
                    event.preventDefault();
                    confirmPassword.setCustomValidity('Passwords do not match.');
                    confirmPassword.reportValidity();
                    return;
                }

                if (!setPhoneValidity()) {
                    event.preventDefault();
                    phoneInput.reportValidity();
                    return;
                }

                if (phoneInstance && hiddenContact && countryCodeInput) {
                    const countryData = phoneInstance.getSelectedCountryData() || {};
                    countryCodeInput.value = String(countryData.iso2 || 'ph').toUpperCase();
                    hiddenContact.value = phoneInstance.getNumber() || phoneInput.value.trim();
                }

                confirmPassword.setCustomValidity('');
            });

            confirmPassword.addEventListener('input', function () {
                if (confirmPassword.value === password.value) {
                    confirmPassword.setCustomValidity('');
                }
            });
        });
    </script>
</body>
</html>