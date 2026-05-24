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

            <form action="<?= base_url('auth/processRegister') ?>" method="post" class="auth-form" novalidate id="registerForm">
                <div class="auth-section-label">
                    <span class="auth-section-icon">👤</span>
                    <span>Personal Information</span>
                </div>

                <div class="form-row form-row--auth">
                    <div class="auth-field">
                        <label for="firstName">FIRST NAME *</label>
                        <input type="text" id="firstName" name="firstName" placeholder="FIRST NAME" required autocomplete="given-name" autocapitalize="characters" class="name-uppercase">
                    </div>
                    <div class="auth-field">
                        <label for="middleName">MIDDLE NAME</label>
                        <input type="text" id="middleName" name="middleName" placeholder="MIDDLE NAME" autocomplete="additional-name" autocapitalize="characters" class="name-uppercase">
                    </div>
                    <div class="auth-field">
                        <label for="lastName">LAST NAME *</label>
                        <input type="text" id="lastName" name="lastName" placeholder="LAST NAME" required autocomplete="family-name" autocapitalize="characters" class="name-uppercase">
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
                        <div class="field-error hidden" id="registerEmailError"></div>
                    </div>
                </div>

                <div class="form-row form-row--auth">
                    <div class="auth-field">
                        <label for="password">PASSWORD *</label>
                        <div class="auth-input-wrap auth-input-wrap--icon-left">
                            <input type="password" id="password" name="password" placeholder="PASSWORD" required>
                        </div>
                        <div class="field-error hidden" id="registerPasswordError"></div>
                    </div>
                    <div class="auth-field">
                        <label for="confirmPassword">CONFIRM PASSWORD *</label>
                        <div class="auth-input-wrap auth-input-wrap--icon-left">
                            <input type="password" id="confirmPassword" name="confirm_password" placeholder="CONFIRM PASSWORD" required>
                        </div>
                        <div class="field-error hidden" id="registerConfirmError"></div>
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

            <script>
                // Custom client-side validation for registration
                (function () {
                    const form = document.getElementById('registerForm');
                    const firstName = document.getElementById('firstName');
                    const middleName = document.getElementById('middleName');
                    const lastName = document.getElementById('lastName');
                    const email = document.getElementById('email');
                    const password = document.getElementById('password');
                    const confirm = document.getElementById('confirmPassword');
                    const emailErr = document.getElementById('registerEmailError');
                    const pwErr = document.getElementById('registerPasswordError');
                    const confirmErr = document.getElementById('registerConfirmError');
                    const phoneError = document.getElementById('phoneError');

                    function toUpperCaseValue(value) {
                        return value
                            .replace(/\s+/g, ' ')
                            .toUpperCase();
                    }

                    function bindCapitalization(input) {
                        if (!input) return;

                        const normalize = function () {
                            const value = input.value;
                            if (value === '') return;
                            const normalized = toUpperCaseValue(value);
                            if (normalized !== value) {
                                input.value = normalized;
                            }
                        };

                        input.addEventListener('input', normalize);
                        input.addEventListener('blur', normalize);
                    }

                    function normalizeNameFields() {
                        [firstName, middleName, lastName].forEach(function (input) {
                            if (!input || input.value === '') return;
                            const normalized = toUpperCaseValue(input.value);
                            if (normalized !== input.value) {
                                input.value = normalized;
                            }
                        });
                    }

                    bindCapitalization(firstName);
                    bindCapitalization(middleName);
                    bindCapitalization(lastName);

                    form.addEventListener('submit', function () {
                        normalizeNameFields();
                    });

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

                    function checkPasswordRules(v) {
                        if (!v || v.length < 8) return 'Password must be at least 8 characters.';
                        if (!(/[A-Z]/.test(v) && /[a-z]/.test(v))) return 'Use both uppercase and lowercase letters.';
                        if (!/[0-9]/.test(v)) return 'Include at least one number.';
                        return '';
                    }

                    function clearPhoneError() {
                        if (!phoneError) return;
                        phoneError.textContent = 'Please enter a valid phone number for the selected country.';
                        phoneError.hidden = true;
                    }

                    [email, password, confirm].forEach(function (el) {
                        if (!el) return;
                        el.addEventListener('input', function () {
                            const id = el.id;
                            if (id === 'email') clearError(emailErr);
                            if (id === 'password') clearError(pwErr);
                            if (id === 'confirmPassword') clearError(confirmErr);
                        });
                    });

                    form.addEventListener('submit', function (e) {
                        let firstInvalid = null;

                        if (!email.value || !isValidEmail(email.value.trim())) {
                            showError(emailErr, 'Please enter a valid email address.');
                            firstInvalid = firstInvalid || email;
                        }

                        const pwRule = checkPasswordRules(password.value);
                        if (pwRule) {
                            showError(pwErr, pwRule);
                            firstInvalid = firstInvalid || password;
                        }

                        if (password.value !== confirm.value) {
                            showError(confirmErr, 'Passwords do not match.');
                            firstInvalid = firstInvalid || confirm;
                        }

                        if (firstInvalid) {
                            e.preventDefault();
                            firstInvalid.focus();
                        }
                    });
                })();
            </script>

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
            const phoneError = document.getElementById('phoneError');
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
                if (!phoneInstance || !phoneInput) {
                    return true;
                }

                const value = phoneInput.value.trim();
                if (value === '') {
                    phoneError.textContent = 'Contact number is required.';
                    phoneError.hidden = false;
                    return false;
                }

                const isValid = phoneInstance.isValidNumber();
                if (!isValid) {
                    phoneError.textContent = 'Please enter a valid phone number for the selected country.';
                    phoneError.hidden = false;
                    return false;
                }

                clearPhoneError();

                return true;
            };

            const showPhoneError = function (message) {
                if (!phoneError) return;
                phoneError.textContent = message;
                phoneError.hidden = false;
            };

            const clearPhoneError = function () {
                if (!phoneError) return;
                phoneError.textContent = 'Please enter a valid phone number for the selected country.';
                phoneError.hidden = true;
            };

            if (phoneInput) {
                phoneInput.addEventListener('blur', setPhoneValidity);
                phoneInput.addEventListener('input', function () {
                    clearPhoneError();
                    if (phoneInput.value.trim() !== '') {
                        setPhoneValidity();
                    }
                });
            }

            form.addEventListener('submit', function (event) {
                if (password.value !== confirmPassword.value) {
                    event.preventDefault();
                    const confirmError = document.getElementById('registerConfirmError');
                    if (confirmError) {
                        confirmError.textContent = 'Passwords do not match.';
                        confirmError.classList.remove('hidden');
                    }
                    return;
                }

                if (!setPhoneValidity()) {
                    event.preventDefault();
                    return;
                }

                if (phoneInstance && hiddenContact && countryCodeInput) {
                    const countryData = phoneInstance.getSelectedCountryData() || {};
                    countryCodeInput.value = String(countryData.iso2 || 'ph').toUpperCase();
                    hiddenContact.value = phoneInstance.getNumber() || phoneInput.value.trim();
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
</body>
</html>