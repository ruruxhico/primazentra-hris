<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$role           = $role ?? '';
$requests       = $requests ?? [];
$filterStatus   = $filterStatus ?? '';
$filterCategory = $filterCategory ?? '';
$filterSearch   = $filterSearch ?? '';

function rr_badge(string $status): string {
    $map = [
        'pending'           => 'pz-badge-pending',
        'pending secondary' => 'pz-badge-pending-secondary',
        'approved'          => 'pz-badge-approved',
        'rejected'          => 'pz-badge-rejected',
        'completed'         => 'pz-badge-completed',
    ];
    $cls = $map[strtolower($status)] ?? 'pz-badge-pending';
    return '<span class="pz-badge ' . $cls . '">' . esc(strtoupper($status)) . '</span>';
}

function rr_url(array $params): string {
    $qs = http_build_query(array_filter($params));
    return base_url('requests') . ($qs ? '?' . $qs : '');
}
?>

<div class="pz-page-header">
    <h1>Resource Requests &amp; Transfers</h1>
    <p>Requests are created from drag and drop or the request form, then approved before transfer is completed at the destination site.</p>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="pz-alert pz-alert-success">
        <span class="material-symbols-outlined" style="font-size:1rem">check_circle</span>
        <?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="pz-alert pz-alert-error">
        <span class="material-symbols-outlined" style="font-size:1rem">error</span>
        <?= session()->getFlashdata('error') ?>
    </div>
<?php endif; ?>

<!-- Toolbar: search -->
<div class="pz-toolbar">
    <div class="pz-search-wrap">
        <span class="material-symbols-outlined">search</span>
        <input type="text" class="pz-search" id="rrSearch" placeholder="Search requests…" value="<?= esc($filterSearch) ?>">
    </div>
</div>

<!-- Filters -->
<div style="padding: 0 2rem .6rem; display:flex; flex-direction:column; gap:.45rem">
    <div class="pz-chip-row">
        <a href="<?= rr_url(['category' => $filterCategory, 'search' => $filterSearch]) ?>"
           class="pz-chip <?= $filterStatus === '' ? 'active' : '' ?>">
            <?php if ($filterStatus === ''): ?><span class="material-symbols-outlined">check</span><?php endif; ?>All statuses
        </a>
        <?php foreach (['Pending', 'Pending Secondary', 'Approved', 'Rejected', 'Completed'] as $s): ?>
        <a href="<?= rr_url(['status' => $s, 'category' => $filterCategory, 'search' => $filterSearch]) ?>"
           class="pz-chip <?= $filterStatus === $s ? 'active' : '' ?>">
            <?php if ($filterStatus === $s): ?><span class="material-symbols-outlined">check</span><?php endif; ?>
            <?= esc($s) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <div class="pz-chip-row">
        <a href="<?= rr_url(['status' => $filterStatus, 'search' => $filterSearch]) ?>"
           class="pz-chip <?= $filterCategory === '' ? 'active' : '' ?>">
            <?php if ($filterCategory === ''): ?><span class="material-symbols-outlined">check</span><?php endif; ?>All categories
        </a>
        <?php foreach (['Manpower', 'Equipment', 'Tools'] as $c): ?>
        <a href="<?= rr_url(['status' => $filterStatus, 'category' => $c, 'search' => $filterSearch]) ?>"
           class="pz-chip <?= $filterCategory === $c ? 'active' : '' ?>">
            <?php if ($filterCategory === $c): ?><span class="material-symbols-outlined">check</span><?php endif; ?>
            <?= esc($c) ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Table -->
<div class="pz-table-wrap">
    <table class="pz-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Source</th>
                <th>Destination</th>
                <th>Status</th>
                <th>Category</th>
                <th>Qty</th>
                <th>Created</th>
                <th class="center">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($requests)): ?>
            <tr><td colspan="8" class="pz-table-empty">No requests found.</td></tr>
        <?php else: ?>
            <?php foreach ($requests as $r): ?>
            <tr>
                <td class="pz-row-num" style="font-family:monospace;letter-spacing:.05em"><?= esc(strtoupper(substr($r['id'], 0, 6))) ?></td>
                <td class="pz-row-muted" title="<?= esc($r['source_site_name'] ?? '—') ?>"><?= esc($r['source_site_name'] ?? '—') ?></td>
                <td class="pz-row-muted" title="<?= esc($r['destination_site_name'] ?? '—') ?>"><?= esc($r['destination_site_name'] ?? '—') ?></td>
                <td><?= rr_badge($r['status'] ?? 'Pending') ?></td>
                <td><?= esc($r['category'] ?? '—') ?></td>
                <td><?= esc($r['quantity'] ?? 1) ?></td>
                <td class="pz-row-muted" style="white-space:nowrap"><?= esc($r['created_at_fmt'] ?? '—') ?></td>
                <td class="actions">
                    <a href="<?= base_url('requests/' . $r['id']) ?>" class="pz-btn-icon" title="View details">
                        <span class="material-symbols-outlined">visibility</span>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="pager-wrapper">
    <?= service('pager')->links('requests', 'bootstrap_full') ?>
</div>

<?= $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var searchInput = document.getElementById('rrSearch');
    var timeout = null;
    searchInput && searchInput.addEventListener('input', function () {
        clearTimeout(timeout);
        var q = this.value;
        timeout = setTimeout(function () {
            var url = new URL(window.location.href);
            url.searchParams.set('search', q);
            window.location.href = url.toString();
        }, 500);
    });
});
</script>
<?php $this->endSection() ?>
