
<?= $this->extend('layouts/main') ?>

<?= $this->section('head') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.12/build/css/intlTelInput.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <!-- Alert Messages -->
    <?php if (session()->getFlashdata('error')): ?>
        <div class="form-error">
            ✗ <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <header class="staff-header">
        <h1><?= esc($pageTitle ?? 'Staff') ?></h1>
    </header>

    <!-- Form Container -->
    <div class="form-container staff-form-container">
        <form method="POST" action="<?= base_url(isset($staffId) ? 'staff/update/' . $staffId : 'staff/create') ?>">
            <?= csrf_field() ?>

            <!-- Names row: First | Middle | Last -->
            <div class="staff-names-row">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" value="<?= esc((string)($staff['first_name'] ?? '')) ?>" required>
                </div>

                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" value="<?= esc((string)($staff['middle_name'] ?? '')) ?>">
                </div>

                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" value="<?= esc((string)($staff['last_name'] ?? '')) ?>" required>
                </div>
            </div>

            <!-- Contact row: Email | Contact No -->
            <div class="staff-contact-row">
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" value="<?= esc((string)($staff['email'] ?? '')) ?>" required>
                </div>

                <div class="form-group">
                    <label>Contact No</label>
                    <div class="phone-input-group">
                        <input
                            type="tel"
                            id="staffContact"
                            name="contact_no_display"
                            placeholder="Phone number"
                            inputmode="tel"
                            autocomplete="tel"
                            value="<?= esc((string)($staff['contact_no'] ?? '')) ?>"
                        >
                    </div>
                    <input type="hidden" name="country_code" id="staffCountryCode" value="PH">
                    <input type="hidden" name="contact_no" id="staffContactFull" value="<?= esc((string)($staff['contact_no'] ?? '')) ?>">
                    <small class="phone-hint" id="staffPhoneHint">Include an active mobile number.</small>
                    <small class="phone-error" id="staffPhoneError" hidden>Please enter a valid phone number for the selected country.</small>
                </div>
            </div>

            <!-- Role -->
            <div class="form-group">
                <label>Role *</label>
                <select name="role" required>
                    <option value="">Select Role</option>
                    <option value="Administrator" <?= ($staff['role'] ?? '') === 'Administrator' ? 'selected' : '' ?>>Administrator</option>
                    <option value="Operations Manager" <?= ($staff['role'] ?? '') === 'Operations Manager' ? 'selected' : '' ?>>Operations Manager</option>
                    <option value="Project Manager" <?= ($staff['role'] ?? '') === 'Project Manager' ? 'selected' : '' ?>>Project Manager</option>
                    <option value="Site Engineer" <?= ($staff['role'] ?? '') === 'Site Engineer' ? 'selected' : '' ?>>Site Engineer</option>
                </select>
            </div>

            <div class="staff-policy-notice">
                <input type="radio" checked disabled>
                <input type="hidden" name="must_change_password" value="1">
                Users must change password on first login
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-add">
                    <?= isset($staffId) ? 'Update Staff' : 'Create Staff' ?>
                </button>
                <a href="<?= base_url('staff') ?>" class="btn btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.12/build/js/intlTelInput.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const phoneInput    = document.getElementById('staffContact');
    const countryInput  = document.getElementById('staffCountryCode');
    const hiddenContact = document.getElementById('staffContactFull');
    const phoneHint     = document.getElementById('staffPhoneHint');
    const phoneError    = document.getElementById('staffPhoneError');

    if (!window.intlTelInput || !phoneInput) return;

    const iti = window.intlTelInput(phoneInput, {
        initialCountry: 'ph',
        preferredCountries: ['ph', 'us', 'sg', 'au', 'gb'],
        separateDialCode: true,
        autoPlaceholder: 'aggressive',
        formatOnDisplay: true,
        strictMode: false,
        utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.12/build/js/utils.js'
    });

    function syncFields() {
        const data = iti.getSelectedCountryData() || {};
        countryInput.value  = String(data.iso2 || 'ph').toUpperCase();
        hiddenContact.value = iti.getNumber() || phoneInput.value.trim();
        phoneHint.textContent = 'Selected: ' + (data.name || 'Country') + (data.dialCode ? ' (+' + data.dialCode + ')' : '');
    }

    phoneInput.addEventListener('countrychange', syncFields);
    phoneInput.addEventListener('input', function () { phoneError.hidden = true; syncFields(); });
    phoneInput.addEventListener('blur', function () {
        if (phoneInput.value.trim() && !iti.isValidNumber()) {
            phoneError.hidden = false;
        }
        syncFields();
    });

    syncFields();
});
</script>
<?= $this->endSection() ?>
