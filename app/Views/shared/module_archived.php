<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$module  = $module  ?? 'unknown';
$items   = $items   ?? [];
$title   = $title   ?? 'Archived Records';
$backUrl = $backUrl ?? base_url();

$count = count($items);
?>

<div class="del-shell">

    <div class="del-header">
        <a href="<?= esc($backUrl) ?>" class="del-back" title="Back">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h1 class="del-title"><?= esc($title) ?></h1>
            <p class="del-sub">Archived records are kept indefinitely and can be restored at any time.</p>
        </div>
    </div>

    <div class="del-meta-row">
        <span class="del-count-badge del-type-archive">
            <span class="material-symbols-outlined" style="font-size:14px">inventory_2</span>
            <?= $count ?> archived record<?= $count !== 1 ? 's' : '' ?>
        </span>
    </div>

    <?php if (empty($items)): ?>
    <div class="del-empty">
        <span class="material-symbols-outlined">inventory_2</span>
        <p>No archived records found.</p>
    </div>
    <?php else: ?>

    <div class="pz-table-wrap">
        <table class="pz-table" id="archTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>

                    <?php if ($module === 'staff'): ?>
                    <th>Role</th>
                    <th>Email</th>

                    <?php elseif ($module === 'project'): ?>
                    <th>Type</th>
                    <th>Location</th>
                    <th>Status</th>

                    <?php elseif ($module === 'resource'): ?>
                    <th>Category</th>

                    <?php elseif ($module === 'manpower'): ?>
                    <th>Role</th>
                    <th>Status</th>

                    <?php elseif ($module === 'request'): ?>
                    <th>Source</th>
                    <th>Destination</th>
                    <th>Category</th>
                    <?php endif; ?>

                    <th>Archived By</th>
                    <th>Date Archived</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $i => $item):
                $data       = $item['data'] ?? [];
                $archivedAt = (int)($item['archived_at'] ?? 0);
            ?>
            <tr data-arch-id="<?= esc($item['id']) ?>">
                <td><?= $i + 1 ?></td>
                <td><strong><?= esc($item['name'] ?? '—') ?></strong></td>

                <?php if ($module === 'staff'): ?>
                <td><?= esc($data['role'] ?? '—') ?></td>
                <td><?= esc($data['email'] ?? '—') ?></td>

                <?php elseif ($module === 'project'): ?>
                <td><?= esc($data['project_type'] ?? '—') ?></td>
                <td><?= esc(is_array($data['location'] ?? null) ? ($data['location']['address'] ?? '—') : ($data['location'] ?? '—')) ?></td>
                <td><?= esc(ucfirst(strtolower($data['status'] ?? '—'))) ?></td>

                <?php elseif ($module === 'resource'): ?>
                <td><?= esc($data['category'] ?? '—') ?></td>

                <?php elseif ($module === 'manpower'): ?>
                <td><?= esc($data['role'] ?? '—') ?></td>
                <td><?= esc($data['status'] ?? '—') ?></td>

                <?php elseif ($module === 'request'): ?>
                <td><?= esc($data['source_site'] ?? '—') ?></td>
                <td><?= esc($data['destination_site'] ?? '—') ?></td>
                <td><?= esc($data['category'] ?? '—') ?></td>
                <?php endif; ?>

                <td><?= esc($item['archived_by'] ?? 'System') ?></td>
                <td><?= $archivedAt ? date('d M Y, h:i A', $archivedAt) : '—' ?></td>
                <td class="text-center" style="white-space:nowrap">
                    <button class="del-btn del-btn-unarchive" data-id="<?= esc($item['id']) ?>">
                        <span class="material-symbols-outlined">unarchive</span> Unarchive
                    </button>
                    <button class="del-btn del-btn-perma" data-id="<?= esc($item['id']) ?>">
                        <span class="material-symbols-outlined">delete_forever</span> Purge
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="pager-wrapper">
        <span class="pager-info">Showing <?= $count ?> record<?= $count !== 1 ? 's' : '' ?></span>
    </div>

    <?php endif; ?>
</div><!-- /.del-shell -->

<script>
(function () {
    var CSRF_N = '<?= csrf_token() ?>';
    var CSRF_V = '<?= csrf_hash() ?>';
    var BASE   = '<?= base_url() ?>';

    function post(url, data) {
        data[CSRF_N] = CSRF_V;
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data),
            credentials: 'same-origin',
        }).then(function (r) { return r.json(); });
    }

    function animateOut(row) {
        row.style.transition = 'opacity 250ms ease, transform 250ms ease';
        row.style.opacity = '0';
        row.style.transform = 'translateX(16px)';
        setTimeout(function () {
            row.remove();
            renumber();
        }, 260);
    }

    function renumber() {
        document.querySelectorAll('#archTable tbody tr').forEach(function (tr, i) {
            if (tr.cells[0]) tr.cells[0].textContent = i + 1;
        });
        var count = document.querySelectorAll('#archTable tbody tr').length;
        var info  = document.querySelector('.pager-info');
        if (info) info.textContent = 'Showing ' + count + ' record' + (count !== 1 ? 's' : '');
    }

    var _archCr = null;
    var archCd  = document.getElementById('archConfirmDialog');
    var archCm  = document.getElementById('archConfirmMessage');
    function openArchConfirm(message, type) {
        if (_archCr) return Promise.resolve(false);
        return new Promise(function (resolve) {
            _archCr = resolve;
            var isArchive = type === 'archive';
            archCd.classList.toggle('confirm-archive', isArchive);
            var icon  = document.getElementById('archConfirmIcon');
            var title = document.getElementById('archConfirmTitle');
            if (icon)  icon.textContent  = isArchive ? 'unarchive' : 'delete_forever';
            if (title) title.textContent = isArchive ? 'Restore' : 'Delete';
            archCm.innerText = message;
            archCd.classList.add('open');
        });
    }
    document.getElementById('archConfirmOk')?.addEventListener('click', function () {
        if (!_archCr) return; var r = _archCr; _archCr = null; archCd.classList.remove('open'); r(true);
    });
    document.getElementById('archConfirmCancel')?.addEventListener('click', function () {
        if (!_archCr) return; var r = _archCr; _archCr = null; archCd.classList.remove('open'); r(false);
    });

    function flash(msg, ok) {
        var b = document.createElement('div');
        b.className = 'del-flash ' + (ok ? 'del-flash-ok' : 'del-flash-err');
        b.textContent = msg;
        var shell = document.querySelector('.del-shell');
        if (shell) shell.insertBefore(b, shell.querySelector('.del-meta-row') || shell.firstChild);
        setTimeout(function () { b.remove(); }, 3500);
    }

    document.addEventListener('click', async function (e) {
        var unarchiveBtn = e.target.closest('.del-btn-unarchive');
        var permaBtn     = e.target.closest('.del-btn-perma');

        if (unarchiveBtn) {
            var row  = unarchiveBtn.closest('tr');
            var id   = unarchiveBtn.dataset.id;
            var name = (row.cells[1] && row.cells[1].textContent.trim()) || 'this record';
            if (!await openArchConfirm('Unarchive "' + name + '" and restore it to active records?', 'archive')) return;
            unarchiveBtn.disabled = true;
            unarchiveBtn.innerHTML = '<span class="material-symbols-outlined">hourglass_top</span> Restoring…';
            post(BASE + 'archive/restore', { arch_id: id })
                .then(function (d) {
                    if (d.success) {
                        flash((d.name || 'Item') + ' restored to active records.', true);
                        animateOut(row);
                    } else {
                        flash(d.message || 'Restore failed.', false);
                        unarchiveBtn.disabled = false;
                        unarchiveBtn.innerHTML = '<span class="material-symbols-outlined">unarchive</span> Unarchive';
                    }
                })
                .catch(function () {
                    flash('Network error. Please try again.', false);
                    unarchiveBtn.disabled = false;
                    unarchiveBtn.innerHTML = '<span class="material-symbols-outlined">unarchive</span> Unarchive';
                });
        }

        if (permaBtn) {
            var row  = permaBtn.closest('tr');
            var id   = permaBtn.dataset.id;
            var name = (row.cells[1] && row.cells[1].textContent.trim()) || 'this record';
            if (!await openArchConfirm('Permanently delete "' + name + '" from archive? This cannot be undone.')) return;
            permaBtn.disabled = true;
            permaBtn.innerHTML = '<span class="material-symbols-outlined">hourglass_top</span> Deleting…';
            post(BASE + 'archive/permanent-delete', { arch_id: id })
                .then(function (d) {
                    if (d.success) {
                        flash('Record permanently deleted from archive.', true);
                        animateOut(row);
                    } else {
                        flash(d.message || 'Delete failed.', false);
                        permaBtn.disabled = false;
                        permaBtn.innerHTML = '<span class="material-symbols-outlined">delete_forever</span> Purge';
                    }
                })
                .catch(function () {
                    flash('Network error. Please try again.', false);
                    permaBtn.disabled = false;
                    permaBtn.innerHTML = '<span class="material-symbols-outlined">delete_forever</span> Purge';
                });
        }
    });
}());
</script>

<div id="archConfirmDialog" class="confirm-dialog" role="dialog" aria-modal="true">
    <div class="confirm-backdrop"></div>
    <div class="confirm-box">
        <div class="confirm-hdr">
            <span class="material-symbols-outlined confirm-hdr-icon" id="archConfirmIcon">delete_forever</span>
            <p class="confirm-hdr-title" id="archConfirmTitle">Delete</p>
        </div>
        <div class="confirm-content"><p id="archConfirmMessage"></p></div>
        <div class="confirm-actions">
            <button id="archConfirmOk"     class="confirm-btn confirm-btn-action">OK</button>
            <button id="archConfirmCancel" class="confirm-btn confirm-btn-cancel">Cancel</button>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
