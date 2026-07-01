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
                    <th><?= $module === 'request' ? 'ID' : 'Name' ?></th>

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
            <?php
            $ts2int = fn($v, $d = 0) => $v instanceof \Google\Cloud\Core\Timestamp
                ? $v->get()->getTimestamp() : (int)($v ?? $d);
            foreach ($items as $i => $item):
                $data       = $item['data'] ?? [];
                $archivedAt = $ts2int($item['archived_at'] ?? null);
            ?>
            <tr data-arch-id="<?= esc($item['id']) ?>">
                <td><?= $i + 1 ?></td>
                <td><?php if ($module === 'request'): ?>
                    <span style="font-family:monospace;letter-spacing:.05em;font-weight:700"><?= esc(strtoupper(substr((string)($item['original_id'] ?? $item['id']), 0, 6))) ?></span>
                <?php else: ?>
                    <strong><?= esc($item['name'] ?? '—') ?></strong>
                <?php endif; ?></td>

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
                <td><?= esc($data['source_site_name'] ?? $data['source_site'] ?? '—') ?></td>
                <td><?= esc($data['destination_site_name'] ?? $data['destination_site'] ?? '—') ?></td>
                <td><?= esc($data['resource_category'] ?? $data['category'] ?? '—') ?></td>
                <?php endif; ?>

                <td style="white-space:nowrap"><?= esc($item['archived_by'] ?? 'System') ?></td>
                <td style="white-space:nowrap"><?= $archivedAt ? date('d M Y, h:i A', $archivedAt) : '—' ?></td>
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
        }).then(function (r) { return r.json(); }).then(function (d) {
            if (d && d.csrf_hash) CSRF_V = d.csrf_hash;
            return d;
        });
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

<?= $this->endSection() ?>

<?php $this->section('firebase-scripts') ?>
<script>
(function () {
  window._pzPageListeners = true;
  var _module = '<?= esc($module) ?>';
  var _base   = window.pzBaseUrl || '';

  function fmtDate(ts) {
    if (!ts || typeof ts.toDate !== 'function') return '—';
    var d = ts.toDate();
    return d.toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' })
         + ', ' + d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
  }

  function moduleCells(item) {
    var d = item.data || {};
    if (_module === 'staff')
      return '<td>' + (d.role  || '—') + '</td><td>' + (d.email || '—') + '</td>';
    if (_module === 'project')
      return '<td>' + (d.project_type || '—') + '</td>'
           + '<td>' + ((d.location && d.location.address) || '—') + '</td>'
           + '<td>' + (d.status || '—') + '</td>';
    if (_module === 'resource')
      return '<td>' + (d.category || '—') + '</td>';
    if (_module === 'manpower')
      return '<td>' + (d.role || '—') + '</td><td>' + (d.status || '—') + '</td>';
    if (_module === 'request')
      return '<td>' + (d.source_site_name || d.source_site || '—') + '</td>'
           + '<td>' + (d.destination_site_name || d.destination_site || '—') + '</td>'
           + '<td>' + (d.resource_category || d.category || '—') + '</td>';
    return '';
  }

  function rowCount() {
    return document.querySelectorAll('#archTable tbody tr[data-arch-id]').length;
  }

  function updateMeta() {
    var n    = rowCount();
    var info = document.querySelector('.pager-info');
    var badge = document.querySelector('.del-count-badge');
    if (info)  info.textContent = 'Showing ' + n + ' record' + (n !== 1 ? 's' : '');
    if (badge) badge.innerHTML  = '<span class="material-symbols-outlined" style="font-size:14px">inventory_2</span> '
                                + n + ' archived record' + (n !== 1 ? 's' : '');
  }

  function renumber() {
    document.querySelectorAll('#archTable tbody tr').forEach(function (tr, i) {
      if (tr.cells[0]) tr.cells[0].textContent = i + 1;
    });
    updateMeta();
  }

  function buildRow(id, item) {
    var nameCell = _module === 'request'
        ? '<td><span style="font-family:monospace;letter-spacing:.05em;font-weight:700">' + (item.original_id || id).substring(0, 6).toUpperCase() + '</span></td>'
        : '<td><strong>' + (item.name || '—').replace(/</g, '&lt;') + '</strong></td>';
    var no = rowCount() + 1;
    return '<tr data-arch-id="' + id + '" class="pz-row-new">'
         + '<td>' + no + '</td>'
         + nameCell
         + moduleCells(item)
         + '<td>' + (item.archived_by || 'System') + '</td>'
         + '<td>' + fmtDate(item.archived_at) + '</td>'
         + '<td class="text-center" style="white-space:nowrap">'
         + '<button class="del-btn del-btn-unarchive" data-id="' + id + '">'
         + '<span class="material-symbols-outlined">unarchive</span> Unarchive</button>'
         + '<button class="del-btn del-btn-perma" data-id="' + id + '">'
         + '<span class="material-symbols-outlined">delete_forever</span> Purge</button>'
         + '</td></tr>';
  }

  function ensureTable() {
    if (document.getElementById('archTable')) return true;
    // Table not rendered (page was empty on load) — show bar instead
    window.pzShowLiveBar && window.pzShowLiveBar('New archived record added — refresh when ready');
    return false;
  }

  var _setupDone = false;
  function setup(db) {
    if (_setupDone) return; _setupDone = true;
    var _ready = false;

    db.collection('archived')
      .where('module_type', '==', _module)
      .onSnapshot(function (snap) {
        if (!_ready) { _ready = true; return; }
        snap.docChanges().forEach(function (chg) {
          var id   = chg.doc.id;
          var item = chg.doc.data();

          if (chg.type === 'added') {
            if (document.querySelector('tr[data-arch-id="' + id + '"]')) return;
            if (!ensureTable()) return;
            var tbody = document.querySelector('#archTable tbody');
            if (!tbody) return;
            var tmp = document.createElement('tbody');
            tmp.innerHTML = buildRow(id, item);
            tbody.insertBefore(tmp.firstElementChild, tbody.firstChild);
            renumber();
            return;
          }

          if (chg.type === 'removed') {
            var row = document.querySelector('tr[data-arch-id="' + id + '"]');
            if (!row) return;
            row.style.transition = 'opacity .4s, background .4s';
            row.style.background = '#fee2e2';
            row.style.opacity    = '0';
            setTimeout(function () {
              row.remove();
              renumber();
            }, 400);
          }
        });
      }, function (err) { console.warn('[PZ] Archive listener:', err.message); });
  }

  firebase.auth().onAuthStateChanged(function (user) {
    if (user && window.pzFirebaseDb) setup(window.pzFirebaseDb);
  });
}());
</script>
<?php $this->endSection() ?>
