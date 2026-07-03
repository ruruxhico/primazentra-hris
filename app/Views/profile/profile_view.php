<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="resource-header">
    <h1>My Profile</h1>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert-success">✓ <?= session()->getFlashdata('success') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert-error">✗ <?= session()->getFlashdata('error') ?></div>
<?php endif; ?>


<div class="profile-shell">

    <div class="profile-avatar-row">
        <!-- Hidden file input triggered by clicking the avatar -->
        <input type="file" id="avatarFileInput" accept="image/jpeg,image/png,image/gif,image/webp"
               style="display:none">

        <div class="profile-avatar-wrap" id="avatarWrap" onclick="document.getElementById('avatarFileInput').click()" title="Click to change photo">
            <div class="profile-avatar-circle" id="avatarCircle">
                <?php if (!empty($profilePicture)): ?>
                    <?php $pvSrc = str_starts_with($profilePicture, 'http') ? $profilePicture : base_url($profilePicture) . '?v=' . time(); ?>
                    <img src="<?= esc($pvSrc) ?>" alt="Profile photo" id="avatarImg">
                <?php else: ?>
                    <span id="avatarInitial"><?= strtoupper(substr((string)($user['first_name'] ?? $userName), 0, 1)) ?></span>
                <?php endif; ?>
            </div>
            <div class="profile-avatar-overlay">
                <span class="material-symbols-outlined">photo_camera</span>
            </div>
        </div>

        <div class="profile-avatar-meta">
            <div class="profile-role"><?= esc($role) ?></div>
            <div class="profile-email"><?= esc($user['email'] ?? '') ?></div>
            <div class="profile-change-hint">Click photo to change</div>
            <div id="avatarUploadProgress">Uploading…</div>
            <div class="profile-avatar-upload-err" id="avatarUploadErr"></div>
        </div>
    </div>

    <div class="profile-card">
        <h3>Personal Information</h3>
        <form action="<?= base_url('profile/update') ?>" method="post">
            <?= csrf_field() ?>
            <div class="profile-field">
                <label>First Name *</label>
                <input type="text" name="first_name" value="<?= esc($user['first_name'] ?? '') ?>" placeholder="e.g. JUAN" required>
            </div>
            <div class="profile-field">
                <label>Middle Name</label>
                <input type="text" name="middle_name" value="<?= esc($user['middle_name'] ?? '') ?>" placeholder="e.g. DELA CRUZ">
            </div>
            <div class="profile-field">
                <label>Last Name *</label>
                <input type="text" name="last_name" value="<?= esc($user['last_name'] ?? '') ?>" placeholder="e.g. SANTOS" required>
            </div>
            <div class="profile-field">
                <label>Role</label>
                <input type="text" value="<?= esc($role) ?>" readonly>
            </div>
            <div class="profile-actions">
                <button type="submit" class="btn-profile-save">Save Changes</button>
                <a href="<?= base_url('profile/change-password') ?>" class="btn-profile-pw">
                    <span class="material-symbols-outlined">lock_reset</span>
                    Change Password
                </a>
            </div>
        </form>
    </div>

</div>

<script>
(function () {
    var input    = document.getElementById('avatarFileInput');
    var wrap     = document.getElementById('avatarWrap');
    var circle   = document.getElementById('avatarCircle');
    var progress = document.getElementById('avatarUploadProgress');
    var errEl    = document.getElementById('avatarUploadErr');

    input.addEventListener('change', function () {
        var file = input.files[0];
        if (!file) return;

        // Client-side size guard (5 MB)
        if (file.size > 5 * 1024 * 1024) {
            showErr('Image must be under 5 MB.');
            return;
        }

        var fd = new FormData();
        fd.append('profile_picture', file);

        // CSRF
        var csrfName = document.querySelector('input[name^="csrf"]');
        if (csrfName) fd.append(csrfName.name, csrfName.value);

        wrap.classList.add('profile-avatar-uploading');
        progress.style.display = 'block';
        errEl.style.display    = 'none';

        fetch('<?= base_url('profile/upload-photo') ?>', {
            method: 'POST', body: fd, credentials: 'same-origin'
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            wrap.classList.remove('profile-avatar-uploading');
            progress.style.display = 'none';

            if (d.success) {
                // Swap avatar to the uploaded image
                circle.innerHTML = '<img src="' + d.url + '" alt="Profile photo" id="avatarImg" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">';

                // Also update the header avatar if it shows an initial
                var hdrAvatar = document.querySelector('.avatar-initial');
                if (hdrAvatar) {
                    hdrAvatar.innerHTML = '<img src="' + d.url + '" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">';
                    hdrAvatar.style.padding = '0';
                    hdrAvatar.style.overflow = 'hidden';
                }
            } else {
                showErr(d.error || 'Upload failed.');
            }
        })
        .catch(function () {
            wrap.classList.remove('profile-avatar-uploading');
            progress.style.display = 'none';
            showErr('Upload failed. Please try again.');
        });

        // Reset input so the same file can be re-selected
        input.value = '';
    });

    function showErr(msg) {
        errEl.textContent    = msg;
        errEl.style.display  = 'block';
        progress.style.display = 'none';
        wrap.classList.remove('profile-avatar-uploading');
    }
}());
</script>

<?= $this->endSection() ?>
