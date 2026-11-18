<?php
/**
 * Passed in by Employees::index().
 *
 * @var \CodeIgniter\View\View       $this
 * @var array<int, array>            $employees
 * @var \App\Libraries\PayPeriod     $period
 * @var array<string, array>|null    $attendance  null when Attendance is unreachable
 * @var array<string, float>|null    $latestPay   null when Payroll is unreachable
 * @var array|null                   $latestRun
 */
?>
<?php $this->extend('layouts/main') ?>

<?php $this->section('content') ?>

<div class="resource-header">
    <h1>Employees</h1>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert-success">✓ <?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert-error">✗ <?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('warning')): ?>
    <div class="alert-error">⚠ <?= esc(session()->getFlashdata('warning')) ?></div>
<?php endif; ?>
<?php if ($attendance === null || $latestPay === null): ?>
    <div class="alert-error">⚠ <?= $attendance === null ? 'Attendance' : 'Payroll' ?><?= ($attendance === null && $latestPay === null) ? ' and Payroll are' : ' is' ?> unavailable right now, so some columns are empty.</div>
<?php endif; ?>

<div class="page-module">
    <div class="rv-controls">
        <?php if (pz_can('manage_employees')): ?>
        <a href="<?= base_url('employees/create') ?>" class="rv-btn rv-btn-add">
            <span class="material-symbols-outlined">add</span> Add
        </a>
        <?php endif; ?>
    </div>

    <?php
        // Period context lives here, once, instead of in the column headers.
        $runPeriod  = $latestRun ? \App\Libraries\PayPeriod::fromId($latestRun['period_id']) : null;
        $samePeriod = $runPeriod && $runPeriod->id === $period->id;
        $payrollUrl = rtrim((string) env('HRIS_PAYROLL_URL', ''), '/') . '/';
    ?>
    <p class="text-muted" style="font-size:.85rem;margin:.25rem 0 .75rem">
        Attendance: <strong><?= esc($period->label()) ?></strong>
        <?php if ($runPeriod): ?>
            · Latest pay: <strong><?= esc($runPeriod->label()) ?></strong> payroll (gross, before statutory deductions)
            · <a href="<?= esc($payrollUrl . 'payroll/' . rawurlencode($runPeriod->id)) ?>" target="_blank" rel="noopener">Open in Payroll ↗</a>
        <?php endif; ?>
    </p>

    <table class="resource-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Position</th>
                <th>Schedule</th>
                <th>Monthly Salary</th>
                <th>Status</th>
                <th>Attendance</th>
                <th>Latest Pay</th>
                <th class="text-center">Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($employees)): ?>
            <tr><td colspan="9" class="text-center">No employees yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($employees as $e): ?>
            <tr>
                <td><?= esc($e['employee_id'] ?? '') ?></td>
                <td><?= esc(trim(($e['last_name'] ?? '') . ', ' . ($e['first_name'] ?? ''), ', ')) ?></td>
                <td><?= esc($e['position'] ?? '') ?></td>
                <td><?= esc($e['schedule_type'] ?? '') ?></td>
                <td>₱<?= number_format((float) ($e['monthly_salary'] ?? 0), 2) ?></td>
                <td><?= esc(ucfirst($e['status'] ?? '')) ?></td>
                <?php $a = $attendance[$e['employee_id']] ?? null; ?>
                <td>
                    <?php if ($attendance === null): ?>—
                    <?php elseif ($a === null): ?><span class="text-muted">No entries</span>
                    <?php else: ?>
                        <?= $a['approved'] ?> approved<?= $a['absent'] ? ' · ' . $a['absent'] . ' absent' : '' ?>
                        <?php if ($a['pending']): ?>
                            <span class="pz-cell-sub"><span class="pz-badge pz-badge-sm pz-badge-pending"><?= $a['pending'] ?> pending</span></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
                <?php
                    // Pay status is decided per employee: the run's status, plus
                    // whether this employee still has DTR waiting for approval.
                    $pay = $latestPay[$e['employee_id']] ?? null;
                    $tag = null;
                    if ($pay !== null) {
                        if ($latestRun['status'] === 'final') {
                            $tag = ['approved', 'Final', 'Payroll for this period is finalized.'];
                        } elseif ($latestRun['stale']) {
                            $tag = ['unavailable', 'Updating', 'Employee or attendance data changed. Payroll recalculates this when the run is opened.'];
                        } elseif ($samePeriod && ($a['pending'] ?? 0) > 0) {
                            $tag = ['pending', 'Pending DTR', $a['pending'] . ' DTR record(s) awaiting approval are not included in this amount yet.'];
                        } else {
                            $tag = ['progress', 'Draft', 'Draft payroll. Amounts can still change until Payroll finalizes it.'];
                        }
                    }
                ?>
                <td>
                    <?php if ($pay === null): ?>—
                    <?php else: ?>
                        <span class="<?= $tag[1] === 'Final' ? '' : 'pz-muted-amount' ?>">₱<?= number_format((float) $pay, 2) ?></span>
                        <span class="pz-cell-sub"><span class="pz-badge pz-badge-sm pz-badge-<?= $tag[0] ?>" title="<?= esc($tag[2]) ?>"><?= esc($tag[1]) ?></span></span>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <a href="<?= base_url('employees/show/' . rawurlencode($e['employee_id'] ?? '')) ?>" title="View attendance and pay">
                        <span class="material-symbols-outlined">visibility</span>
                    </a>
                    <?php if (pz_can('manage_employees')): ?>
                    <a href="<?= base_url('employees/update/' . rawurlencode($e['employee_id'] ?? '')) ?>" title="Edit">
                        <span class="material-symbols-outlined">edit</span>
                    </a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php $this->endSection() ?>
