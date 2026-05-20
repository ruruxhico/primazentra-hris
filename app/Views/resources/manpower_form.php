<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $resource = $resource ?? [];
    $manpower = $resource['manpower_details'] ?? [];
    $isEdit = !empty($resource['id'] ?? null);
    // Manpower form posts to the dedicated Manpower controller
    $formAction = $isEdit ? base_url('manpower/update/' . $resource['id']) : base_url('manpower/store');
    $fullName = trim((string)($resource['name'] ?? ''));
    if ($fullName === '') {
        $fullName = trim((string)($manpower['first_name'] ?? '') . ' ' . (string)($manpower['last_name'] ?? ''));
    }
?>

<header class="resource-header">
    <div class="manpower-topbar">
        <a href="<?= base_url('resources') ?>" class="manpower-back" aria-label="Back to resources">←</a>
        <h1 class="manpower-title"><?= $isEdit ? 'Edit Manpower' : 'Add Manpower' ?></h1>
    </div>
</header>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger">
        <?= session()->getFlashdata('error') ?>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success">
        <?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>

<div class="manpower-shell">
    <div class="manpower-card">
        <form method="POST" action="<?= $formAction ?>" id="manpowerForm">
            <?= csrf_field() ?>
            <input type="hidden" name="category" value="Manpower">
            <input type="hidden" name="manpower_id" value="<?= esc((string)($manpower['manpower_id'] ?? '')) ?>">

            <div class="manpower-grid">
                <div class="manpower-field">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" placeholder="e.g., Juan" value="<?= esc((string)($manpower['first_name'] ?? '')) ?>" required>
                </div>

                <div class="manpower-field">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" placeholder="e.g., Dela Cruz" value="<?= esc((string)($manpower['last_name'] ?? '')) ?>" required>
                </div>
                
                <div class="manpower-field">
                    <label for="middle_name">Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name" placeholder="Optional" value="<?= esc((string)($manpower['middle_name'] ?? '')) ?>">
                </div>

                <div class="manpower-field">
                    <label for="manpower_role">Role</label>
                    <select id="manpower_role" name="manpower_role" required>
                        <option value="">Select Role</option>
                        <?php foreach (['Field Worker', 'Foreman'] as $roleOption): ?>
                            <option value="<?= esc($roleOption) ?>" <?= (($manpower['role'] ?? '') === $roleOption) ? 'selected' : '' ?>><?= esc($roleOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="manpower-field">
                    <label for="specialization">Specialization</label>
                    <select id="specialization" name="specialization" required>
                        <option value="">Select Specialization</option>
                        <?php foreach (['Residential', 'Towers'] as $specializationOption): ?>
                            <option value="<?= esc($specializationOption) ?>" <?= (($manpower['specialization'] ?? '') === $specializationOption) ? 'selected' : '' ?>><?= esc($specializationOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="manpower-field manpower-span-2">
                    <label for="assigned_project_id">Assigned Project / Site</label>
                    <select id="assigned_project_id" name="assigned_project_id">
                        <option value="">Select project (optional)</option>
                        <?php if (!empty($projects) && is_array($projects)): ?>
                            <?php foreach ($projects as $proj): ?>
                                <option value="<?= esc((string)($proj['value'] ?? '')) ?>" data-label="<?= esc((string)($proj['label'] ?? '')) ?>" <?= ((string)($manpower['assigned_project_id'] ?? '') === (string)($proj['value'] ?? '')) ? 'selected' : '' ?>><?= esc((string)($proj['label'] ?? $proj['value'])) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <?php
                        $initialProjectLabel = '';
                        if (!empty($manpower['assigned_project_id']) && !empty($projects) && is_array($projects)) {
                            foreach ($projects as $proj) {
                                if ((string)($proj['value'] ?? '') === (string)($manpower['assigned_project_id'] ?? '')) {
                                    $initialProjectLabel = $proj['label'] ?? '';
                                    break;
                                }
                            }
                        }
                        $displayProject = $manpower['current_site_deployment'] ?? $initialProjectLabel ?? ($manpower['location']['address'] ?? '');
                    ?>
                    <div id="selectedProjectAddress" class="project-address-note" style="margin-top:6px;color:#555;font-size:0.95em;">
                        <?= esc((string)$displayProject) ?>
                    </div>
                </div>

                <!-- assigned_project_id is selected above from projects list -->

                <div class="manpower-field manpower-span-2">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <?php foreach (['Available', 'Deployed', 'On-Leave'] as $statusOption): ?>
                            <option value="<?= esc($statusOption) ?>" <?= (($resource['status'] ?? 'Available') === $statusOption) ? 'selected' : '' ?>><?= esc($statusOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <p class="manpower-note">
                The first and last name combine into the resource name saved in the backend. Deployment is stored in the manpower details for later filtering.
            </p>

            <div class="manpower-actions">
                <button type="submit" class="btn btn-save-mobile"><?= $isEdit ? 'Update' : 'Save' ?></button>
                <a href="<?= base_url('resources') ?>" class="btn btn-cancel-mobile">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var sel = document.getElementById('assigned_project_id');
    var addr = document.getElementById('selectedProjectAddress');
    if (!sel || !addr) return;

    function updateAddr() {
        var opt = sel.options[sel.selectedIndex];
        var label = opt ? opt.getAttribute('data-label') || '' : '';
        addr.textContent = label;
        addr.style.display = label ? 'block' : 'none';
    }

    sel.addEventListener('change', updateAddr);
    updateAddr();
});
</script>
<?php $this->endSection() ?>
