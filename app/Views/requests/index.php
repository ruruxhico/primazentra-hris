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

$activeFilterCount = ($filterStatus !== '' ? 1 : 0) + ($filterCategory !== '' ? 1 : 0);
?>

<style>
/* ── Controls bar ── */
.rv-controls { display:flex; align-items:center; flex-wrap:wrap; gap:8px; padding: 0 2rem; margin-bottom:.7rem; }
.rv-search { flex:1; min-width:160px; padding:8px 12px; border:1px solid #ddd; border-radius:8px; font-size:0.87rem; font-family:inherit; outline:none; }
.rv-search:focus { border-color:#C47A0F; box-shadow:0 0 0 2px rgba(196,122,15,.12); }
.rv-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:8px; font-size:0.8rem; font-weight:700; letter-spacing:0.04em; cursor:pointer; border:1.5px solid transparent; font-family:inherit; white-space:nowrap; font-variation-settings:'FILL' 0,'wght' 400; text-decoration:none; }
.rv-btn .material-symbols-outlined { font-size:16px; }
.rv-btn-filter { background:#fff; border-color:#ddd; color:#555; }
.rv-btn-filter:hover { border-color:#C47A0F; color:#8C701C; }
.rv-filter-badge { display:inline-flex; align-items:center; justify-content:center; background:#8C701C; color:#fff; border-radius:50%; font-size:9px; font-weight:800; width:14px; height:14px; margin-left:3px; }

/* ── Filter panel ── */
.rr-filter-panel {
    margin: 0 2rem .7rem; background:#fdf8ee; border:1px solid #e8d9a8; border-radius:8px; padding:12px 16px 10px;
}
.rr-filter-section { margin-bottom:10px; }
.rr-filter-section:last-child { margin-bottom:0; }
.rr-filter-label { font-size:0.72rem; font-weight:700; text-transform:uppercase; color:#8C701C; letter-spacing:0.05em; margin-bottom:6px; }

/* ── Responsive table ── */
.pz-table-wrap { margin: 0 2rem .75rem; overflow-x: auto; border-radius: 10px; border: 1.5px solid #e8dfc7; -webkit-overflow-scrolling: touch; }
.pz-table { width: 100%; border-collapse: collapse; background: #fff; min-width: 540px; }

/* Hide lower-priority columns on smaller screens */
@media (max-width: 900px) {
    .rr-col-md { display: none; }
}
@media (max-width: 640px) {
    .rr-col-sm { display: none; }
    .pz-table th, .pz-table td { padding: .45rem .6rem; font-size: .82rem; }
}
</style>

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
    <input type="text" class="rv-search" id="rrSearch"
           placeholder="Search requests…"
           value="<?= esc($filterSearch) ?>">
    <button type="button" class="rv-btn rv-btn-filter" id="rrFilterToggle">
        <span class="material-symbols-outlined">tune</span>
        Filter<?php if ($activeFilterCount > 0): ?><span class="rv-filter-badge"><?= $activeFilterCount ?></span><?php endif; ?>
    </button>
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
    <?= service('pager')->links('requests', 'bootstrap_full') ?>
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
