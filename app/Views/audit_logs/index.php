<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$items            = $items            ?? [];
$filters          = $filters          ?? [];
$availableModules = $availableModules ?? ['project','manpower','resource','staff','request','report','auth'];
$pageTitle        = $pageTitle        ?? 'Audit Logs';
$pageSub          = $pageSub          ?? 'Full trail of create, update, delete, and access-control actions across every role.';
$scopeNotice      = $scopeNotice      ?? null;
$count   = count($items);

$ts2int = fn($v, $d = 0) => $v instanceof \Google\Cloud\Core\Timestamp
    ? $v->get()->getTimestamp() : (int)($v ?? $d);

$statusClass = fn($s) => $s === 'failed' ? 'pz-badge-rejected' : 'pz-badge-completed';
?>

<div class="del-shell">

    <div class="del-header">
        <a href="<?= base_url('dashboard') ?>" class="del-back" title="Back">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h1 class="del-title"><?= esc($pageTitle) ?></h1>
            <p class="del-sub"><?= esc($pageSub) ?></p>
        </div>
    </div>

    <?php if ($scopeNotice): ?>
    <div class="del-flash del-flash-ok">
        <span class="material-symbols-outlined" style="font-size:16px;vertical-align:-3px;">info</span>
        <?= esc($scopeNotice) ?>
    </div>
    <?php endif; ?>

    <div class="del-meta-row">
        <span class="del-count-badge">
            <span class="material-symbols-outlined" style="font-size:14px">history</span>
            <?= $count ?> matching entr<?= $count !== 1 ? 'ies' : 'y' ?>
        </span>
    </div>

    <form method="get" action="<?= base_url('audit-logs') ?>" class="pz-filter-bar" style="display:flex;flex-wrap:wrap;gap:.6rem;margin:.75rem 0 1rem;">
        <input type="text" name="q" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Search actor, target, action…"
               style="flex:1 1 220px;padding:.5rem .75rem;border:1px solid #d9dde3;border-radius:.5rem;">

        <select name="module" style="padding:.5rem .6rem;border:1px solid #d9dde3;border-radius:.5rem;">
            <option value="">All modules</option>
            <?php foreach ($availableModules as $m): ?>
            <option value="<?= $m ?>" <?= ($filters['module'] ?? '') === $m ? 'selected' : '' ?>><?= ucfirst($m) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="actor_role" style="padding:.5rem .6rem;border:1px solid #d9dde3;border-radius:.5rem;">
            <option value="">All roles</option>
            <?php foreach (['Administrator','Operations Manager','Project Manager','Site Engineer','System'] as $r): ?>
            <option value="<?= $r ?>" <?= ($filters['actor_role'] ?? '') === $r ? 'selected' : '' ?>><?= $r ?></option>
            <?php endforeach; ?>
        </select>

        <select name="status" style="padding:.5rem .6rem;border:1px solid #d9dde3;border-radius:.5rem;">
            <option value="">Any status</option>
            <option value="success" <?= ($filters['status'] ?? '') === 'success' ? 'selected' : '' ?>>Success</option>
            <option value="failed"  <?= ($filters['status'] ?? '') === 'failed'  ? 'selected' : '' ?>>Failed</option>
        </select>

        <input type="date" name="date_from" value="<?= esc($filters['date_from'] ?? '') ?>"
               style="padding:.5rem .6rem;border:1px solid #d9dde3;border-radius:.5rem;">
        <input type="date" name="date_to" value="<?= esc($filters['date_to'] ?? '') ?>"
               style="padding:.5rem .6rem;border:1px solid #d9dde3;border-radius:.5rem;">

        <button type="submit" class="btn btn-export">Filter</button>
        <?php if (array_filter($filters ?? [])): ?>
        <a href="<?= base_url('audit-logs') ?>" class="btn" style="border:1px solid #d9dde3;">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (empty($items)): ?>
    <div class="del-empty">
        <span class="material-symbols-outlined">history</span>
        <p>No audit entries match these filters.</p>
    </div>
    <?php else: ?>

    <div class="pz-table-wrap">
        <table class="pz-table" id="auditTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date / Time</th>
                    <th>Actor</th>
                    <th>Role</th>
                    <th>Action</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $i => $log):
                $when      = $ts2int($log['created_at'] ?? null);
                $sentence  = \App\Services\AuditLogService::describe($log);
                $rawAction = (string) ($log['action'] ?? '');
            ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td style="white-space:nowrap"><?= $when ? date('d M Y, h:i A', $when) : '—' ?></td>
                <td><?= esc($log['actor_name'] ?? 'System') ?></td>
                <td><?= esc($log['actor_role'] ?? '—') ?></td>
                <td title="<?= esc($rawAction) ?>"><?= esc($sentence) ?></td>
                <td><span class="pz-badge <?= $statusClass($log['status'] ?? 'success') ?>"><?= esc(strtoupper($log['status'] ?? 'success')) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="pager-wrapper">
        <span class="pager-info" id="auditPagerInfo"></span>
        <div id="auditPagerNav"></div>
    </div>

    <?php endif; ?>
</div><!-- /.del-shell -->

<script>
(function () {
    if (!document.getElementById('auditTable')) return;
    window._auditPager = new PzClientPager({
        tableId: 'auditTable',
        rowSel:  'tbody tr',
        noCol:   0,
        infoId:  'auditPagerInfo',
        navId:   'auditPagerNav',
    });
    window._auditPager.render();
}());
</script>

<?= $this->endSection() ?>
