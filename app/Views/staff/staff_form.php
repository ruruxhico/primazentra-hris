
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
    <form method="POST" action="<?= base_url(isset($staffId) ? 'staff/update/' . $staffId : 'staff/create') ?>" enctype="multipart/form-data" id="staffForm" data-autosave>
        <?= csrf_field() ?>

        <!-- Profile Picture -->
        <div class="pz-form-section">
            <p class="pz-form-section-title">Profile Picture</p>
            <div class="avatar-upload-wrap">
                <div class="avatar-circle" id="avatarCircle">
                    <?php
                        $sfPic = (string)($staff['profile_photo_url'] ?? '');
                        if ($sfPic === '') {
                            $sfPicLocal = (string)($staff['profile_picture'] ?? '');
                            $sfPic = ($sfPicLocal !== '' && file_exists(FCPATH . $sfPicLocal)) ? asset_url($sfPicLocal) . '?v=' . time() : '';
                        }
                    ?>
                    <?php if ($sfPic !== ''): ?>
                        <img src="<?= esc($sfPic) ?>" alt="Profile" id="avatarImg">
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
                    <input type="text" name="first_name" value="<?= esc(old('first_name', (string)($staff['first_name'] ?? ''))) ?>" required placeholder="First name">
                </div>
                <div class="pz-form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" value="<?= esc(old('middle_name', (string)($staff['middle_name'] ?? ''))) ?>" placeholder="Middle name">
                </div>
                <div class="pz-form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" value="<?= esc(old('last_name', (string)($staff['last_name'] ?? ''))) ?>" required placeholder="Last name">
                </div>
            </div>
        </div>

        <!-- Contact row -->
        <div class="pz-form-section">
            <p class="pz-form-section-title">Contact & Access</p>
            <div class="pz-form-row cols-2">
                <div class="pz-form-group">
                    <label>Email *</label>
                    <input type="email" name="email" value="<?= esc(old('email', (string)($staff['email'] ?? ''))) ?>" required placeholder="email@example.com">
                </div>
                <div class="pz-form-group">
                    <label>Role *</label>
                    <select name="role" required>
                        <option value="">Select Role</option>
                        <?php $staffRoleOld = old('role', $staff['role'] ?? ''); ?>
                        <?php if ($staffRoleOld === 'Administrator'): ?>
                        <option value="Administrator" selected>Administrator</option>
                        <?php endif; ?>
                        <option value="Operations Manager"<?= $staffRoleOld === 'Operations Manager'? 'selected' : '' ?>>Operations Manager</option>
                        <option value="Project Manager"   <?= $staffRoleOld === 'Project Manager'  ? 'selected' : '' ?>>Project Manager</option>
                        <option value="Site Engineer"     <?= $staffRoleOld === 'Site Engineer'    ? 'selected' : '' ?>>Site Engineer</option>
                    </select>
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
<script>

// Avatar preview
document.getElementById('profile_picture').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
        var t = document.getElementById('sfToast');
        if (t) { t.className = 'custom-toast show error'; t.innerText = 'File is too large. Maximum size is 2 MB.'; setTimeout(function(){t.className='custom-toast';},3000); }
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
<div id="sfToast" class="custom-toast" aria-live="polite"></div>
<?= $this->endSection() ?>
