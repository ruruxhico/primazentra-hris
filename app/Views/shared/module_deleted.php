<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$module  = $module  ?? 'unknown';
$items   = $items   ?? [];
$title   = $title   ?? 'Deleted Records';
$backUrl = $backUrl ?? base_url();
$tab     = $tab     ?? '';

$count = count($items);
?>

<div class="del-shell">

    <div class="del-header">
        <a href="<?= esc($backUrl) ?>" class="del-back" title="Back">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h1 class="del-title"><?= esc($title) ?></h1>
            <p class="del-sub">Records are permanently removed after 30 days from deletion date.</p>
        </div>
    </div>

    <div class="del-meta-row">
        <span class="del-count-badge del-type-trash">
            <span class="material-symbols-outlined" style="font-size:14px">restore_from_trash</span>
            <?= $count ?> deleted record<?= $count !== 1 ? 's' : '' ?>
        </span>
    </div>

    <?php if (empty($items)): ?>
    <div class="del-empty">
        <span class="material-symbols-outlined">delete_sweep</span>
        <p>No deleted records found.</p>
    </div>
    <?php else: ?>

    <div class="pz-table-wrap">
        <table class="pz-table" id="delTable">
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

                    <th>Deleted By</th>
                    <th>Date Deleted</th>
                    <th>Expires In</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $ts2int = fn($v, $d = 0) => $v instanceof \Google\Cloud\Core\Timestamp
                ? $v->get()->getTimestamp() : (int)($v ?? $d);
            foreach ($items as $i => $item):
                $data      = $item['data'] ?? [];
                $deletedAt = $ts2int($item['deleted_at'] ?? null);
                $expiresAt = $ts2int($item['expires_at'] ?? null) ?: ($deletedAt + 30 * 86400);
                $daysLeft  = max(0, (int) ceil(($expiresAt - time()) / 86400));
                $expClass  = $daysLeft <= 3 ? 'del-expiry-urgent' : ($daysLeft <= 7 ? 'del-expiry-warn' : '');
            ?>
            <tr data-del-id="<?= esc($item['id']) ?>">
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

                <td><?= esc($item['deleted_by'] ?? 'System') ?></td>
                <td><?= $deletedAt ? date('d M Y, h:i A', $deletedAt) : '—' ?></td>
                <td>
                    <span class="del-expiry <?= $expClass ?>">
                        <?= $daysLeft ?> day<?= $daysLeft !== 1 ? 's' : '' ?>
                    </span>
                </td>
                <td class="text-center" style="white-space:nowrap">
                    <button class="del-btn del-btn-restore" data-id="<?= esc($item['id']) ?>">
                        <span class="material-symbols-outlined">restore</span> Restore
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

<div id="delConfirmDialog" class="confirm-dialog" role="dialog" aria-modal="true">
    <div class="confirm-backdrop"></div>
    <div class="confirm-box">
        <div class="confirm-hdr">
            <span class="material-symbols-outlined confirm-hdr-icon">delete_forever</span>
            <p class="confirm-hdr-title">Delete</p>
        </div>
        <div class="confirm-content"><p id="delConfirmMessage"></p></div>
        <div class="confirm-actions">
            <button id="delConfirmOk"     class="confirm-btn confirm-btn-action">Delete</button>
            <button id="delConfirmCancel" class="confirm-btn confirm-btn-cancel">Cancel</button>
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
        document.querySelectorAll('#delTable tbody tr').forEach(function (tr, i) {
            if (tr.cells[0]) tr.cells[0].textContent = i + 1;
        });
        var count = document.querySelectorAll('#delTable tbody tr').length;
        var info  = document.querySelector('.pager-info');
        if (info) info.textContent = 'Showing ' + count + ' record' + (count !== 1 ? 's' : '');
    }

    var _delCr = null;
    var delCd  = document.getElementById('delConfirmDialog');
    var delCm  = document.getElementById('delConfirmMessage');
    function openDelConfirm(message) {
        if (_delCr) return Promise.resolve(false);
        return new Promise(function (resolve) {
            _delCr = resolve;
            delCm.innerText = message;
            delCd.classList.add('open');
        });
    }
    document.getElementById('delConfirmOk')?.addEventListener('click', function () {
        if (!_delCr) return; var r = _delCr; _delCr = null; delCd.classList.remove('open'); r(true);
    });
    document.getElementById('delConfirmCancel')?.addEventListener('click', function () {
        if (!_delCr) return; var r = _delCr; _delCr = null; delCd.classList.remove('open'); r(false);
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
        var restoreBtn = e.target.closest('.del-btn-restore');
        var permaBtn   = e.target.closest('.del-btn-perma');

        if (restoreBtn) {
            var row = restoreBtn.closest('tr');
            var id  = restoreBtn.dataset.id;
            restoreBtn.disabled = true;
            restoreBtn.innerHTML = '<span class="material-symbols-outlined">hourglass_top</span> Restoring…';
            post(BASE + 'trash/restore', { trash_id: id })
                .then(function (d) {
                    if (d.success) {
                        flash((d.name || 'Item') + ' restored successfully.', true);
                        animateOut(row);
                    } else {
                        flash(d.message || 'Restore failed.', false);
                        restoreBtn.disabled = false;
                        restoreBtn.innerHTML = '<span class="material-symbols-outlined">restore</span> Restore';
                    }
                })
                .catch(function () {
                    flash('Network error. Please try again.', false);
                    restoreBtn.disabled = false;
                    restoreBtn.innerHTML = '<span class="material-symbols-outlined">restore</span> Restore';
                });
        }

        if (permaBtn) {
            var row  = permaBtn.closest('tr');
            var id   = permaBtn.dataset.id;
            var name = (row.cells[1] && row.cells[1].textContent.trim()) || 'this record';
            if (!await openDelConfirm('Permanently delete "' + name + '"? This cannot be undone.')) return;
            permaBtn.disabled = true;
            permaBtn.innerHTML = '<span class="material-symbols-outlined">hourglass_top</span> Deleting…';
            post(BASE + 'trash/permanent-delete', { trash_id: id })
                .then(function (d) {
                    if (d.success) {
                        flash('Record permanently deleted.', true);
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

  function fmtDate(ts) {
    if (!ts || typeof ts.toDate !== 'function') return '—';
    var d = ts.toDate();
    return d.toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' })
         + ', ' + d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
  }

  function moduleCells(item) {
    var d = item.data || {};
    if (_module === 'staff')
      return '<td>' + (d.role || '—') + '</td><td>' + (d.email || '—') + '</td>';
    if (_module === 'project')
      return '<td>' + (d.project_type || '—') + '</td>'
           + '<td>' + ((d.location && d.location.address) || '—') + '</td>'
           + '<td>' + (d.status || '—') + '</td>';
    if (_module === 'resource')
      return '<td>' + (d.category || '—') + '</td>';
    if (_module === 'manpower')
      return '<td>' + (d.role || '—') + '</td><td>' + (d.status || '—') + '</td>';
    if (_module === 'request')
      return '<td>' + (d.source_site || '—') + '</td>'
           + '<td>' + (d.destination_site || '—') + '</td>'
           + '<td>' + (d.category || '—') + '</td>';
    return '';
  }

  function rowCount() {
    return document.querySelectorAll('#delTable tbody tr[data-del-id]').length;
  }

  function updateMeta() {
    var n     = rowCount();
    var info  = document.querySelector('.pager-info');
    var badge = document.querySelector('.del-count-badge');
    if (info)  info.textContent = 'Showing ' + n + ' record' + (n !== 1 ? 's' : '');
    if (badge) badge.innerHTML  = '<span class="material-symbols-outlined" style="font-size:14px">restore_from_trash</span> '
                                + n + ' deleted record' + (n !== 1 ? 's' : '');
  }

  function renumber() {
    document.querySelectorAll('#delTable tbody tr').forEach(function (tr, i) {
      if (tr.cells[0]) tr.cells[0].textContent = i + 1;
    });
    updateMeta();
  }

  function buildRow(id, item) {
    var name = (item.name || '—').replace(/</g, '&lt;');
    var no   = rowCount() + 1;
    return '<tr data-del-id="' + id + '" class="pz-row-new">'
         + '<td>' + no + '</td>'
         + '<td><strong>' + name + '</strong></td>'
         + moduleCells(item)
         + '<td>' + (item.deleted_by || 'System') + '</td>'
         + '<td>' + fmtDate(item.deleted_at) + '</td>'
         + '<td><span class="del-expiry">30 days</span></td>'
         + '<td class="text-center" style="white-space:nowrap">'
         + '<button class="del-btn del-btn-restore" data-id="' + id + '">'
         + '<span class="material-symbols-outlined">restore</span> Restore</button>'
         + '<button class="del-btn del-btn-perma" data-id="' + id + '">'
         + '<span class="material-symbols-outlined">delete_forever</span> Purge</button>'
         + '</td></tr>';
  }

  var _setupDone = false;
  function setup(db) {
    if (_setupDone) return; _setupDone = true;
    var _ready = false;

    db.collection('recently_deleted')
      .where('module_type', '==', _module)
      .onSnapshot(function (snap) {
        if (!_ready) { _ready = true; return; }
        snap.docChanges().forEach(function (chg) {
          var id   = chg.doc.id;
          var item = chg.doc.data();

          if (chg.type === 'added') {
            if (document.querySelector('tr[data-del-id="' + id + '"]')) return;
            var tbody = document.querySelector('#delTable tbody');
            if (!tbody) {
              window.pzShowLiveBar && window.pzShowLiveBar('A record was deleted — refresh when ready');
              return;
            }
            var tmp = document.createElement('tbody');
            tmp.innerHTML = buildRow(id, item);
            tbody.insertBefore(tmp.firstElementChild, tbody.firstChild);
            renumber();
            return;
          }

          if (chg.type === 'removed') {
            var row = document.querySelector('tr[data-del-id="' + id + '"]');
            if (!row) return;
            row.style.transition = 'opacity .4s, background .4s';
            row.style.background = '#fee2e2';
            row.style.opacity    = '0';
            setTimeout(function () { row.remove(); renumber(); }, 400);
          }
        });
      }, function (err) { console.warn('[PZ] Trash listener:', err.message); });
  }

  firebase.auth().onAuthStateChanged(function (user) {
    if (user && window.pzFirebaseDb) setup(window.pzFirebaseDb);
  });
}());
</script>
<?php $this->endSection() ?>
