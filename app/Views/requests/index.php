<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$role           = $role ?? '';
$requests       = $requests ?? [];
$filterStatus   = $filterStatus ?? '';
$filterCategory = $filterCategory ?? '';
$filterSearch   = $filterSearch ?? '';
$activeFilterCount = ($filterStatus !== '' ? 1 : 0) + ($filterCategory !== '' ? 1 : 0);
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

<!-- Controls bar -->
<div class="rv-controls">
    <div class="rv-search-wrap">
        <span class="material-symbols-outlined">search</span>
        <input type="text" class="rv-search" id="rrSearch"
               placeholder=""
               value="<?= esc($filterSearch) ?>">
    </div>
    <button type="button" class="rv-btn rv-btn-filter" id="rrFilterToggle">
        <span class="material-symbols-outlined">tune</span>
        Filter<?php if ($activeFilterCount > 0): ?><span class="rv-filter-badge"><?= $activeFilterCount ?></span><?php endif; ?>
    </button>

    <div class="rv-btn-group-view">
        <a href="<?= base_url('requests/deleted') ?>" class="rv-btn-trash">
            <span class="material-symbols-outlined">restore_from_trash</span> Deleted
        </a>
        <a href="<?= base_url('requests/archived') ?>" class="rv-btn-archive">
            <span class="material-symbols-outlined">inventory_2</span> Archive
        </a>
    </div>
</div>

<!-- Filter panel (collapsible chip rows) -->
<div id="rrFilterPanel" class="rr-filter-panel" style="display:<?= $activeFilterCount > 0 ? 'block' : 'none' ?>">
    <div class="rr-filter-section">
        <div class="rr-filter-label">Status</div>
        <div class="pz-chip-row">
            <a href="<?= rr_url(['category' => $filterCategory, 'search' => $filterSearch]) ?>"
               class="pz-chip <?= $filterStatus === '' ? 'active' : '' ?>">
                <?php if ($filterStatus === ''): ?><span class="material-symbols-outlined">check</span><?php endif; ?>All
            </a>
            <?php foreach (['Pending', 'Pending Secondary', 'Approved', 'Rejected', 'Completed'] as $s): ?>
            <a href="<?= rr_url(['status' => $s, 'category' => $filterCategory, 'search' => $filterSearch]) ?>"
               class="pz-chip <?= $filterStatus === $s ? 'active' : '' ?>">
                <?php if ($filterStatus === $s): ?><span class="material-symbols-outlined">check</span><?php endif; ?>
                <?= esc($s) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="rr-filter-section">
        <div class="rr-filter-label">Category</div>
        <div class="pz-chip-row">
            <a href="<?= rr_url(['status' => $filterStatus, 'search' => $filterSearch]) ?>"
               class="pz-chip <?= $filterCategory === '' ? 'active' : '' ?>">
                <?php if ($filterCategory === ''): ?><span class="material-symbols-outlined">check</span><?php endif; ?>All
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
    <?php if ($activeFilterCount > 0): ?>
    <div style="margin-top:10px">
        <a href="<?= base_url('requests') ?>" style="font-size:0.78rem;font-weight:600;color:#8C701C;text-decoration:none;border:1px solid #d4c185;border-radius:6px;padding:4px 12px;display:inline-block;">
            Reset filters
        </a>
    </div>
    <?php endif; ?>
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
                <th class="rr-col-sm">Category</th>
                <th class="rr-col-md">Qty</th>
                <th class="rr-col-md">Created</th>
                <th class="center">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($requests)): ?>
            <?php $hasFilters = $filterStatus !== '' || $filterCategory !== '' || $filterSearch !== ''; ?>
            <tr><td colspan="8" class="pz-table-empty <?= $hasFilters ? 'pz-table-empty--filter' : '' ?>">
                <?php if ($hasFilters): ?>
                    <span class="material-symbols-outlined" style="font-size:1.3rem;vertical-align:middle;margin-right:.3rem;opacity:.55">filter_list_off</span>No record found for the selected filter combination.
                <?php else: ?>
                    No requests found.
                <?php endif; ?>
            </td></tr>
        <?php else: ?>
            <?php foreach ($requests as $r): ?>
            <tr>
                <td style="font-family:monospace;letter-spacing:.05em"><?= esc(strtoupper(substr($r['id'], 0, 6))) ?></td>
                <td class="pz-row-muted" title="<?= esc($r['source_site_name'] ?? '—') ?>"><?= esc($r['source_site_name'] ?? '—') ?></td>
                <td class="pz-row-muted" title="<?= esc($r['destination_site_name'] ?? '—') ?>"><?= esc($r['destination_site_name'] ?? '—') ?></td>
                <td><?= rr_badge($r['status'] ?? 'Pending') ?></td>
                <td class="rr-col-sm"><?= esc($r['category'] ?? '—') ?></td>
                <td class="rr-col-md"><?= esc($r['quantity'] ?? 1) ?></td>
                <td class="pz-row-muted rr-col-md" style="white-space:nowrap"><?= esc($r['created_at_fmt'] ?? '—') ?></td>
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
    <?php
        $showingFrom = $requestsTotal > 0 ? $pagerOffset + 1 : 0;
        $showingTo   = min($pagerOffset + $perPage, $requestsTotal);
    ?>
    <span class="pager-info">Showing <?= $showingFrom ?>–<?= $showingTo ?> of <?= $requestsTotal ?> records</span>
    <?= service('pager')->links('requests', 'bootstrap_full') ?>
    <?php if ($requestsTotalPages > 1): ?>
    <div class="pager-jump">
        Go to page
        <input type="number" class="pager-jump-input" min="1" max="<?= $requestsTotalPages ?>"
               data-total-pages="<?= $requestsTotalPages ?>" data-page-param="page" placeholder="—">
        <span>of <?= $requestsTotalPages ?></span>
    </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    /* ── Search (debounced URL redirect) ── */
    var searchInput = document.getElementById('rrSearch');
    var timeout = null;
    searchInput && searchInput.addEventListener('input', function () {
        clearTimeout(timeout);
        var q = this.value;
        timeout = setTimeout(function () {
            var url = new URL(window.location.href);
            url.searchParams.set('search', q);
            if (!q) url.searchParams.delete('search');
            window.location.href = url.toString();
        }, 500);
    });

    /* ── Filter panel toggle ── */
    var filterToggle = document.getElementById('rrFilterToggle');
    var filterPanel  = document.getElementById('rrFilterPanel');
    filterToggle && filterToggle.addEventListener('click', function () {
        filterPanel.style.display = filterPanel.style.display === 'none' ? 'block' : 'none';
    });
});
</script>
<?php $this->endSection() ?>
