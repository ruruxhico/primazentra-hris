<?php
/**
 * Passed in by Employees::create() / update().
 *
 * @var \CodeIgniter\View\View $this
 * @var string                 $pageTitle
 * @var array|null             $employee
 */
?>
<?php $this->extend('layouts/main') ?>

<?php $this->section('content') ?>
<?php
    $e      = $employee ?? [];
    $isEdit = ! empty($e['employee_id']);
    $action = $isEdit ? 'employees/update/' . rawurlencode($e['employee_id']) : 'employees/create';
    $val    = static fn (string $f, $d = '') => esc(old($f, (string) ($e[$f] ?? $d)));
?>

<a href="<?= base_url('employees') ?>" class="pz-back-link">
    <span class="material-symbols-outlined">arrow_back</span> Employees
</a>

<?php if (session()->getFlashdata('error')): ?>
    <div class="pz-alert pz-alert-error">
        <span class="material-symbols-outlined" style="font-size:1rem">error</span>
        <?= esc(session()->getFlashdata('error')) ?>
    </div>
<?php endif; ?>

<div class="pz-page-header">
    <h1><?= esc($pageTitle) ?><?= $isEdit ? ' · ' . esc($e['employee_id']) : '' ?></h1>
</div>

<div class="pz-form-container">
    <form method="POST" action="<?= base_url($action) ?>">
        <?= csrf_field() ?>

        <div class="pz-form-section">
            <p class="pz-form-section-title">Employee</p>
            <div class="pz-form-row cols-3">
                <div class="pz-form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" value="<?= $val('first_name') ?>" required>
                </div>
                <div class="pz-form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" value="<?= $val('last_name') ?>" required>
                </div>
                <div class="pz-form-group">
                    <label>Position</label>
                    <input type="text" name="position" value="<?= $val('position') ?>">
                </div>
            </div>
        </div>

        <div class="pz-form-section">
            <p class="pz-form-section-title">Pay</p>
            <div class="pz-form-row cols-3">
                <div class="pz-form-group">
                    <label>Schedule *</label>
                    <select name="schedule_type" required>
                        <?php $sel = old('schedule_type', (string) ($e['schedule_type'] ?? '8hrs')); ?>
                        <option value="8hrs"   <?= $sel === '8hrs' ? 'selected' : '' ?>>8 hours / day (monthly ÷ 26)</option>
                        <option value="9.6hrs" <?= $sel === '9.6hrs' ? 'selected' : '' ?>>9.6 hours / day (monthly ÷ 22)</option>
                    </select>
                </div>
                <div class="pz-form-group">
                    <label>Monthly Salary (₱) *</label>
                    <input type="number" name="monthly_salary" min="0.01" step="0.01" value="<?= $val('monthly_salary') ?>" required>
                </div>
                <div class="pz-form-group">
                    <label>Status</label>
                    <?php $st = old('status', (string) ($e['status'] ?? 'active')); ?>
                    <select name="status">
                        <option value="active"   <?= $st === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $st === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="pz-form-actions">
            <button type="submit" class="pz-btn pz-btn-primary">Save</button>
            <a href="<?= base_url('employees') ?>" class="pz-btn pz-btn-cancel">Cancel</a>
        </div>
    </form>
</div>

<?php $this->endSection() ?>
