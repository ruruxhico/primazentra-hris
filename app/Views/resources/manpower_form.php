<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
    $resource = $resource ?? [];
    $manpower = $resource['manpower_details'] ?? [];
    $isEdit = !empty($resource['id'] ?? null);
    $formAction = $isEdit ? base_url('manpower/update/' . $resource['id']) : base_url('manpower/store');
?>

<a href="<?= base_url('resources') ?>" class="pz-back-link">
    <span class="material-symbols-outlined">arrow_back</span> Resources
</a>

<?php if (session()->getFlashdata('error')): ?>
    <div class="pz-alert pz-alert-error"><span class="material-symbols-outlined" style="font-size:1rem">error</span><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('success')): ?>
    <div class="pz-alert pz-alert-success"><span class="material-symbols-outlined" style="font-size:1rem">check_circle</span><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>

<div class="pz-page-header">
    <h1><?= $isEdit ? 'Edit Manpower' : 'Add Manpower' ?></h1>
</div>

<div class="pz-form-container">
    <form method="POST" action="<?= $formAction ?>" id="manpowerForm">
        <?= csrf_field() ?>
        <input type="hidden" name="category" value="Manpower">
        <input type="hidden" name="manpower_id" value="<?= esc((string)($manpower['manpower_id'] ?? '')) ?>">

        <!-- Name -->
        <div class="pz-form-section">
            <p class="pz-form-section-title">Personal Information</p>
            <div class="pz-form-row cols-3">
                <div class="pz-form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" placeholder="e.g., JUAN"
                           value="<?= esc(strtoupper((string)($manpower['first_name'] ?? ''))) ?>"
                           style="text-transform:uppercase" required>
                </div>
                <div class="pz-form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" placeholder="Optional"
                           value="<?= esc(strtoupper((string)($manpower['middle_name'] ?? ''))) ?>"
                           style="text-transform:uppercase">
                </div>
                <div class="pz-form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" placeholder="e.g., DELA CRUZ"
                           value="<?= esc(strtoupper((string)($manpower['last_name'] ?? ''))) ?>"
                           style="text-transform:uppercase" required>
                </div>
            </div>
        </div>

        <!-- Role & Specialization -->
        <div class="pz-form-section">
            <p class="pz-form-section-title">Role & Assignment</p>
            <div class="pz-form-row cols-2">
                <div class="pz-form-group">
                    <label>Role *</label>
                    <select name="manpower_role" required>
                        <option value="">Select Role</option>
                        <?php foreach (['FIELD WORKER', 'FOREMAN'] as $opt): ?>
                            <option value="<?= esc($opt) ?>" <?= (($manpower['role'] ?? '') === $opt) ? 'selected' : '' ?>><?= esc($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="pz-form-group">
                    <label>Specialization *</label>
                    <select name="specialization" required>
                        <option value="">Select Specialization</option>
                        <?php foreach (['BUILD-TO-SUIT', 'RETROFITTING', 'COLOCATION', 'CONVERSION'] as $opt): ?>
                            <option value="<?= esc($opt) ?>" <?= (($manpower['specialization'] ?? '') === $opt) ? 'selected' : '' ?>><?= esc($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="pz-form-row cols-2" style="margin-top:.65rem">
                <div class="pz-form-group">
                    <label>Status *</label>
                    <select name="status" required>
                        <?php foreach (['AVAILABLE', 'DEPLOYED', 'ON-LEAVE'] as $opt): ?>
                            <option value="<?= esc($opt) ?>" <?= (($resource['status'] ?? 'AVAILABLE') === $opt) ? 'selected' : '' ?>><?= esc($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Skills -->
        <div class="pz-form-section">
            <p class="pz-form-section-title">Skills</p>
            <div class="skills-chips">
                <?php
                $currentSkills = is_array($manpower['skills'] ?? null) ? $manpower['skills'] : [];
                foreach (['ELECTRICIAN', 'MASON', 'LABOR/HELPER'] as $skill):
                    $checked = in_array($skill, $currentSkills, true);
                ?>
                <label class="skill-chip-label">
                    <input type="checkbox" name="skills[]" value="<?= esc($skill) ?>" <?= $checked ? 'checked' : '' ?>>
                    <span class="skill-chip"><?= esc($skill) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="pz-form-actions">
            <button type="submit" class="pz-btn pz-btn-primary">
                <span class="material-symbols-outlined">save</span>
                <?= $isEdit ? 'Update Manpower' : 'Save Manpower' ?>
            </button>
            <a href="<?= base_url('resources') ?>" class="pz-btn pz-btn-cancel">Cancel</a>
        </div>
    </form>
</div>

<?= $this->endSection() ?>

