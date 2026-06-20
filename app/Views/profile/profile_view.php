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

<style>
.profile-shell { max-width: 560px; margin: 0 auto; }
.profile-avatar-row { display:flex; align-items:center; gap:1.2rem; margin-bottom:1.6rem; }

/* Avatar wrapper — clickable to upload */
.profile-avatar-wrap {
    position:relative; width:80px; height:80px; flex-shrink:0; cursor:pointer;
}
.profile-avatar-circle {
    width:80px; height:80px; border-radius:50%;
    background:#8C701C; color:#fff; font-size:2rem; font-weight:700;
    display:flex; align-items:center; justify-content:center;
    overflow:hidden; border:3px solid #d4c185;
}
.profile-avatar-circle img {
    width:100%; height:100%; object-fit:cover; border-radius:50%;
}
.profile-avatar-overlay {
    position:absolute; inset:0; border-radius:50%;
    background:rgba(0,0,0,0.45); display:flex; align-items:center; justify-content:center;
    opacity:0; transition:opacity .2s;
}
.profile-avatar-wrap:hover .profile-avatar-overlay { opacity:1; }
.profile-avatar-overlay .material-symbols-outlined { color:#fff; font-size:24px; }
.profile-avatar-uploading .profile-avatar-overlay { opacity:1; background:rgba(0,0,0,0.55); }
#avatarUploadProgress {
    display:none; font-size:0.75rem; color:#8C701C; margin-top:4px; text-align:center;
}
.profile-avatar-upload-err {
    font-size:0.75rem; color:#c0392b; margin-top:4px; display:none;
}

.profile-avatar-meta .profile-role { font-size:0.82rem; font-weight:700; text-transform:uppercase; color:#8C701C; letter-spacing:.5px; }
.profile-avatar-meta .profile-email { font-size:0.84rem; color:#555; margin-top:2px; }
.profile-avatar-meta .profile-change-hint { font-size:0.75rem; color:#aaa; margin-top:3px; }

.profile-card { background:#fff; border:1px solid #e5e0d0; border-radius:10px; padding:1.6rem 1.8rem; margin-bottom:1.2rem; }
.profile-card h3 { font-size:0.92rem; font-weight:700; text-transform:uppercase; color:#5c3d11; letter-spacing:.4px; margin:0 0 1rem; }
.profile-field { margin-bottom:1rem; }
.profile-field label { display:block; font-size:0.78rem; font-weight:700; text-transform:uppercase; color:#666; letter-spacing:.4px; margin-bottom:4px; }
.profile-field input { width:100%; padding:8px 10px; border:1px solid #ccc; border-radius:6px; font-size:0.92rem; font-family:inherit; }
.profile-field input[readonly] { background:#f6f4ee; color:#888; cursor:not-allowed; }
.profile-actions { display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap; margin-top:0.5rem; }
.btn-profile-save {
    background:#8C701C; color:#fff; border:none; border-radius:6px;
    padding:9px 22px; font-size:0.9rem; font-weight:700; cursor:pointer; font-family:inherit;
}
.btn-profile-save:hover { background:#6e570f; }
.btn-profile-pw {
    background:#f5f0e4; color:#5c3d11; border:1px solid #d4c185; border-radius:6px;
    padding:8px 18px; font-size:0.9rem; font-weight:600; cursor:pointer;
    text-decoration:none; display:inline-flex; align-items:center; gap:5px; font-family:inherit;
}
.btn-profile-pw:hover { background:#ede3c6; color:#3d2800; }
.btn-profile-pw .material-symbols-outlined { font-size:16px; }
</style>

<div class="profile-shell">

    <div class="profile-avatar-row">
        <!-- Hidden file input triggered by clicking the avatar -->
        <input type="file" id="avatarFileInput" accept="image/jpeg,image/png,image/gif,image/webp"
               style="display:none">

        <div class="profile-avatar-wrap" id="avatarWrap" onclick="document.getElementById('avatarFileInput').click()" title="Click to change photo">
            <div class="profile-avatar-circle" id="avatarCircle">
                <?php if (!empty($profilePicture)): ?>
                    <img src="<?= base_url($profilePicture) ?>?v=<?= time() ?>" alt="Profile photo" id="avatarImg">
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
