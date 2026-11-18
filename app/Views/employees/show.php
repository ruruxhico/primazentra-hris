<?php
/**
 * Passed in by Employees::show().
 *
 * @var \CodeIgniter\View\View   $this
 * @var array                    $employee
 * @var \App\Libraries\PayPeriod $period
 * @var array|null               $dtr
 * @var string|null              $dtrError
 * @var array|null               $payslips
 * @var string|null              $payError
 */
?>
<?php $this->extend('layouts/main') ?>

<?php $this->section('content') ?>
<?php $e = $employee; $peso = static fn ($v) => '₱' . number_format((float) $v, 2); ?>

<a href="<?= base_url('employees') ?>" class="pz-back-link">
    <span class="material-symbols-outlined">arrow_back</span> Employees
</a>

<div class="pz-page-header">
    <h1><?= esc($e['last_name'] . ', ' . $e['first_name']) ?> · <?= esc($e['employee_id']) ?></h1>
</div>

<div class="page-module">
    <p>
        <?= esc($e['position'] ?? '') ?> · <?= esc($e['schedule_type']) ?> schedule ·
        <?= $peso($e['monthly_salary']) ?>/month · <?= esc(ucfirst($e['status'])) ?>
        <?php if (pz_can('manage_employees')): ?>
            · <a href="<?= base_url('employees/update/' . rawurlencode($e['employee_id'])) ?>">Edit</a>
        <?php endif; ?>
    </p>

    <h2 style="margin-top:1.5rem">Attendance · <?= esc($period->label()) ?></h2>
    <p class="text-muted" style="font-size:.85rem">Live from the Attendance app.</p>
    <?php if ($dtrError): ?>
        <div class="alert-error">⚠ Attendance is unavailable: <?= esc($dtrError) ?></div>
    <?php elseif (empty($dtr)): ?>
        <p>No DTR entries this period (counted as present every day).</p>
    <?php else: ?>
    <table class="resource-table">
        <thead><tr><th>Date</th><th>Late (min)</th><th>Undertime (min)</th><th>OT (hrs)</th><th>Absent</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($dtr as $r): ?>
            <tr>
                <td><?= esc($r['date']) ?></td>
                <td><?= (int) $r['late_minutes'] ?></td>
                <td><?= (int) $r['undertime_minutes'] ?></td>
                <td><?= number_format((float) $r['ot_hours'], 2) ?></td>
                <td><?= $r['is_absent'] ? 'Yes' : '' ?></td>
                <td><?= $r['approved'] ? 'Approved by ' . esc($r['supervisor_name'] ?? '') : 'Pending approval' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <h2 style="margin-top:1.5rem">Payslips</h2>
    <p class="text-muted" style="font-size:.85rem">Live from the Payroll app. Before SSS, PhilHealth, Pag-IBIG and tax, which are not applied yet.</p>
    <?php if ($payError): ?>
        <div class="alert-error">⚠ Payroll is unavailable: <?= esc($payError) ?></div>
    <?php elseif (empty($payslips)): ?>
        <p>No payroll computed for this employee yet.</p>
    <?php else: ?>
    <table class="resource-table">
        <thead><tr><th>Period</th><th>Status</th><th>Basic</th><th>OT pay</th><th>Deductions</th><th>Gross</th></tr></thead>
        <tbody>
        <?php foreach ($payslips as $p): ?>
            <tr>
                <td><?= esc(\App\Libraries\PayPeriod::fromId($p['period_id'])->label()) ?></td>
                <td><?= esc(ucfirst($p['run_status'])) ?><?= $p['run_stale'] ? ' (out of date, updates when opened in Payroll)' : '' ?></td>
                <td><?= $peso($p['basic_pay']) ?></td>
                <td><?= $peso($p['ot_pay']) ?></td>
                <td><?= $peso($p['total_deductions']) ?></td>
                <td><strong><?= $peso($p['gross_pay']) ?></strong></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php $this->endSection() ?>
