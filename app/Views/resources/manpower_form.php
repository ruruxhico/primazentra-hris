<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $resource = $resource ?? [];
    $manpower = $resource['manpower_details'] ?? [];
    $isEdit = !empty($resource['id'] ?? null);
    $formAction = $isEdit ? base_url('resources/update/' . $resource['id']) : base_url('resources/store');
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
                    <label for="current_site_deployment">Current Site / Deployment</label>
                    <input type="text" id="current_site_deployment" name="current_site_deployment" placeholder="e.g., Site A / Tower 3 / Cavite" value="<?= esc((string)($manpower['current_site_deployment'] ?? '')) ?>">
                </div>

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
