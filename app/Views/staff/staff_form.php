
<?= $this->extend('layouts/main') ?>

<?= $this->section('head') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.12/build/css/intlTelInput.css">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<a href="<?= base_url('staff') ?>" class="pz-back-link">
    <span class="material-symbols-outlined">arrow_back</span> Staff
</a>

<?php if (session()->getFlashdata('error')): ?>
    <div class="pz-alert pz-alert-error">
        <span class="material-symbols-outlined" style="font-size:1rem">error</span>
        <?= session()->getFlashdata('error') ?>
    </div>
<?php endif; ?>

<div class="pz-page-header">
    <h1><?= esc($pageTitle ?? 'Staff') ?></h1>
</div>

<div class="pz-form-container">
    <form method="POST" action="<?= base_url(isset($staffId) ? 'staff/update/' . $staffId : 'staff/create') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <!-- Profile Picture -->
        <div class="pz-form-section">
            <p class="pz-form-section-title">Profile Picture</p>
            <div class="avatar-upload-wrap">
                <div class="avatar-circle" id="avatarCircle">
                    <?php if (!empty($staff['profile_picture'])): ?>
                        <img src="<?= asset_url($staff['profile_picture']) ?>?v=<?= time() ?>" alt="Profile" id="avatarImg">
                    <?php else: ?>
                        <span id="avatarInitial"><?= strtoupper(substr((string)($staff['first_name'] ?? 'S'), 0, 1)) ?></span>
                    <?php endif; ?>
                </div>
                <div class="avatar-upload-controls">
                    <label class="pz-btn pz-btn-outline" for="profile_picture" style="cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                        <span class="material-symbols-outlined" style="font-size:16px">upload</span> Choose Photo
                    </label>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none">
                    <small>JPG, PNG, WEBP &middot; Max 2 MB</small>
                    <small id="avatarFileName" style="color:#1a3c5e;font-weight:600"></small>
                </div>
            </div>
        </div>

        <!-- Name row -->
        <div class="pz-form-section">
            <p class="pz-form-section-title">Personal Information</p>
            <div class="pz-form-row cols-3">
                <div class="pz-form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" value="<?= esc((string)($staff['first_name'] ?? '')) ?>" required placeholder="First name">
                </div>
                <div class="pz-form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" value="<?= esc((string)($staff['middle_name'] ?? '')) ?>" placeholder="Middle name">
                </div>
                <div class="pz-form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" value="<?= esc((string)($staff['last_name'] ?? '')) ?>" required placeholder="Last name">
                </div>
            </div>
        </div>

        <!-- Contact row -->
        <div class="pz-form-section">
            <p class="pz-form-section-title">Contact & Access</p>
            <div class="pz-form-row cols-2">
                <div class="pz-form-group">
                    <label>Email *</label>
                    <input type="email" name="email" value="<?= esc((string)($staff['email'] ?? '')) ?>" required placeholder="email@example.com">
                </div>
                <div class="pz-form-group">
                    <label>Role *</label>
                    <select name="role" required>
                        <option value="">Select Role</option>
                        <option value="Administrator"     <?= ($staff['role'] ?? '') === 'Administrator'     ? 'selected' : '' ?>>Administrator</option>
                        <option value="Operations Manager"<?= ($staff['role'] ?? '') === 'Operations Manager'? 'selected' : '' ?>>Operations Manager</option>
                        <option value="Project Manager"   <?= ($staff['role'] ?? '') === 'Project Manager'  ? 'selected' : '' ?>>Project Manager</option>
                        <option value="Site Engineer"     <?= ($staff['role'] ?? '') === 'Site Engineer'    ? 'selected' : '' ?>>Site Engineer</option>
                    </select>
                </div>
            </div>
            <div class="pz-form-row full" style="margin-top:.65rem">
                <div class="pz-form-group">
                    <label>Contact No</label>
                    <div class="phone-input-group">
                        <input type="tel" id="staffContact" name="contact_no_display"
                               placeholder="Phone number" inputmode="tel" autocomplete="tel"
                               value="<?= esc((string)($staff['contact_no'] ?? '')) ?>">
                    </div>
                    <input type="hidden" name="country_code" id="staffCountryCode" value="PH">
                    <input type="hidden" name="contact_no" id="staffContactFull" value="<?= esc((string)($staff['contact_no'] ?? '')) ?>">
                    <small class="phone-hint" id="staffPhoneHint">Include an active mobile number.</small>
                    <small class="phone-error" id="staffPhoneError" hidden>Please enter a valid phone number.</small>
                </div>
            </div>
        </div>

        <div class="pz-policy-notice">
            <input type="radio" checked disabled>
            <input type="hidden" name="must_change_password" value="1">
            Users must change password on first login
        </div>

        <div class="pz-form-actions">
            <button type="submit" class="pz-btn pz-btn-primary">
                <span class="material-symbols-outlined">save</span>
                <?= isset($staffId) ? 'Update Staff' : 'Create Staff' ?>
            </button>
            <a href="<?= base_url('staff') ?>" class="pz-btn pz-btn-cancel">Cancel</a>
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
        if (phoneInput.value.trim() && !iti.isValidNumber()) phoneError.hidden = false;
        syncFields();
    });
    syncFields();
});

// Avatar preview
document.getElementById('profile_picture').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
        alert('File is too large. Maximum size is 2 MB.');
        this.value = '';
        return;
    }
    document.getElementById('avatarFileName').textContent = file.name;
    const reader = new FileReader();
    reader.onload = function (e) {
        const circle = document.getElementById('avatarCircle');
        circle.innerHTML = '<img src="' + e.target.result + '" alt="Preview" style="width:100%;height:100%;object-fit:cover">';
    };
    reader.readAsDataURL(file);
});

// Keep initial letter in sync with first name input
const fnInput = document.querySelector('input[name="first_name"]');
if (fnInput) {
    fnInput.addEventListener('input', function () {
        const initial = document.getElementById('avatarInitial');
        if (initial) initial.textContent = (this.value.trim()[0] || 'S').toUpperCase();
    });
}
</script>
<?= $this->endSection() ?>
