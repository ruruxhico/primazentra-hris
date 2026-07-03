<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$role           = $role ?? '';
$requests       = $requests ?? [];
$filterStatus   = $filterStatus ?? '';
$filterCategory = $filterCategory ?? '';
$filterSearch   = $filterSearch ?? '';
$activeFilterCount = ($filterStatus !== '' ? 1 : 0) + ($filterCategory !== '' ? 1 : 0);

helper('access');
$canArchive = pz_can('archive_request');
$canBulk    = $canArchive;
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
        <a href="<?= base_url('requests/archived') ?>" class="rv-btn-archive">
            <span class="material-symbols-outlined">inventory_2</span> Archive
        </a>
    </div>

    <div class="pager-top">
        <span>Show</span>
        <select class="pager-per-page">
            <?php foreach ([5,10,20,30,40,50] as $_pp): ?>
            <option value="<?= $_pp ?>"<?= (($perPage??10)==$_pp&&($perPage??10)<99999)?' selected':'' ?>><?= $_pp ?></option>
            <?php endforeach; ?>
            <option value="all"<?= (($perPage??10)>=99999)?' selected':'' ?>>All</option>
        </select>
        <span>records per page</span>
    </div>
</div>

<!-- Filter panel -->
<div id="rrFilterPanel" class="rv-filter-panel" style="display:<?= $activeFilterCount > 0 ? 'block' : 'none' ?>">
    <div class="rv-filter-row">
        <span class="rv-filter-label">Status</span>
        <div class="rv-chip-row">
            <a href="#" data-filter="status" data-val=""
               class="rv-chip <?= $filterStatus === '' ? 'active' : '' ?>">
                <span class="rr-check-icon material-symbols-outlined" <?= $filterStatus === '' ? '' : 'style="display:none"' ?>>check</span>All
            </a>
            <?php foreach (['Pending', 'Pending Secondary Approval', 'In-Transit', 'Completed'] as $s): ?>
            <a href="#" data-filter="status" data-val="<?= esc($s) ?>"
               class="rv-chip <?= $filterStatus === $s ? 'active' : '' ?>">
                <span class="rr-check-icon material-symbols-outlined" <?= $filterStatus === $s ? '' : 'style="display:none"' ?>>check</span><?= esc($s) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="rv-filter-row">
        <span class="rv-filter-label">Category</span>
        <div class="rv-chip-row">
            <a href="#" data-filter="category" data-val=""
               class="rv-chip <?= $filterCategory === '' ? 'active' : '' ?>">
                <span class="rr-check-icon material-symbols-outlined" <?= $filterCategory === '' ? '' : 'style="display:none"' ?>>check</span>All
            </a>
            <?php foreach (['Manpower', 'Equipment', 'Tools'] as $c): ?>
            <a href="#" data-filter="category" data-val="<?= esc($c) ?>"
               class="rv-chip <?= $filterCategory === $c ? 'active' : '' ?>">
                <span class="rr-check-icon material-symbols-outlined" <?= $filterCategory === $c ? '' : 'style="display:none"' ?>>check</span><?= esc($c) ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php if ($activeFilterCount > 0): ?>
        <a href="<?= base_url('requests') ?>" class="rv-filter-reset">Reset filters</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($canBulk): ?>
<!-- Bulk action bar -->
<div id="rrBulkBar" style="display:none;align-items:center;gap:10px;padding:8px 14px;background:#fdf8e1;border:1px solid #e8d97a;border-radius:8px;margin-bottom:10px;flex-wrap:wrap;">
    <span id="rrBulkCount" style="font-size:.85rem;font-weight:700;color:#7a5c1e;min-width:90px;">0 selected</span>
    <?php if ($canArchive): ?>
    <button type="button" id="rrBulkArchiveBtn" class="pz-btn-sm pz-btn-archive" style="display:flex;align-items:center;gap:5px;">
        <span class="material-symbols-outlined" style="font-size:16px">inventory_2</span> Archive Selected
    </button>
    <?php endif; ?>
    <button type="button" id="rrBulkClearBtn" style="background:none;border:none;font-size:.8rem;color:#999;cursor:pointer;padding:2px 6px;">Clear</button>
</div>
<?php endif; ?>

<!-- Table -->
<div class="table-container">
    <table class="resource-table" id="rrTable">
        <thead>
            <tr>
                <?php if ($canBulk): ?>
                <th class="pz-col-check">
                    <input type="checkbox" id="rrSelectAll" title="Select all">
                </th>
                <?php endif; ?>
                <th>No.</th>
                <th>Source</th>
                <th>Destination</th>
                <th class="text-center">Status</th>
                <th class="rr-col-sm">Category</th>
                <th class="rr-col-md text-center">Qty</th>
                <th class="rr-col-md">Created</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($requests)): ?>
            <?php $hasFilters = $filterStatus !== '' || $filterCategory !== '' || $filterSearch !== ''; ?>
            <tr><td colspan="<?= $canBulk ? 9 : 8 ?>" class="pz-table-empty <?= $hasFilters ? 'pz-table-empty--filter' : '' ?>">
                <?php if ($hasFilters): ?>
                    <span class="material-symbols-outlined" style="font-size:1.3rem;vertical-align:middle;margin-right:.3rem;opacity:.55">filter_list_off</span>No record found for the selected filter combination.
                <?php else: ?>
                    No requests found.
                <?php endif; ?>
            </td></tr>
        <?php else: ?>
            <?php $rrRowNum = 0; ?>
            <?php foreach ($requests as $r): ?>
            <?php $rrRowNum++; ?>
            <tr data-req-id="<?= esc($r['id']) ?>"
                data-req-status="<?= esc(strtolower($r['status'] ?? 'pending')) ?>"
                data-req-category="<?= esc(strtolower($r['resource_category'] ?? '')) ?>">
                <?php if ($canBulk): ?>
                <td class="pz-col-check">
                    <input type="checkbox" class="rr-row-cb" value="<?= esc($r['id']) ?>">
                </td>
                <?php endif; ?>
                <td><?= $rrRowNum ?></td>
                <td title="<?= esc($r['source_site_name'] ?? '—') ?>"><?= esc($r['source_site_name'] ?? '—') ?></td>
                <td title="<?= esc($r['destination_site_name'] ?? '—') ?>"><?= esc($r['destination_site_name'] ?? '—') ?></td>
                <td class="text-center" data-req-status><?= rr_badge($r['status'] ?? 'Pending') ?></td>
                <td class="rr-col-sm"><?= esc($r['resource_category'] ?? '—') ?></td>
                <td class="rr-col-md text-center"><?= esc($r['requested_quantity'] ?? $r['quantity'] ?? 1) ?></td>
                <td class="rr-col-md" style="white-space:nowrap"><?= esc($r['created_at_fmt'] ?? '—') ?></td>
                <td class="text-center" style="white-space:nowrap">
                    <a href="<?= base_url('requests/' . $r['id']) ?>" class="action-btn" title="View details">
                        <span class="material-symbols-outlined">visibility</span>
                    </a>
                    <?php if ($canArchive): ?>
                    <button type="button" class="action-btn rr-archive-btn" data-id="<?= esc($r['id']) ?>" title="Archive">
                        <span class="material-symbols-outlined" style="color:#c07000">inventory_2</span>
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="pager-wrapper">
    <span class="pager-info" id="rrPagerInfo"></span>
    <div id="rrPagerNav"></div>
</div>

<!-- Confirm dialog reuse -->
<div id="rrConfirmDialog" class="confirm-dialog" role="dialog" aria-modal="true" style="display:none">
    <div class="confirm-backdrop"></div>
    <div class="confirm-box">
        <div class="confirm-hdr">
            <span class="material-symbols-outlined confirm-hdr-icon" id="rrConfirmIcon">delete_forever</span>
            <p class="confirm-hdr-title" id="rrConfirmTitle">Confirm</p>
        </div>
        <div class="confirm-content"><p id="rrConfirmMessage"></p></div>
        <div class="confirm-actions">
            <button id="rrConfirmOk"     class="confirm-btn confirm-btn-action">Confirm</button>
            <button id="rrConfirmCancel" class="confirm-btn confirm-btn-cancel">Cancel</button>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
(function () {
var CSRF_N = '<?= csrf_token() ?>';
var CSRF_V = '<?= csrf_hash() ?>';
var BASE   = '<?= base_url() ?>';
var CAN_ARCHIVE = <?= $canArchive ? 'true' : 'false' ?>;

/* ── Filter panel toggle ── */
var filterToggle = document.getElementById('rrFilterToggle');
var filterPanel  = document.getElementById('rrFilterPanel');
filterToggle && filterToggle.addEventListener('click', function () {
    filterPanel.style.display = filterPanel.style.display === 'none' ? 'block' : 'none';
});

/* ── Live filter state ── */
var rrFilters = {
    status   : '<?= esc($filterStatus) ?>'.toLowerCase(),
    category : '<?= esc($filterCategory) ?>'.toLowerCase()
};

function applyRRFilters() {
    var q = (document.getElementById('rrSearch')?.value || '').trim().toLowerCase();
    document.querySelectorAll('tbody tr[data-req-id]').forEach(function (row) {
        var show = true;
        if (q && !row.innerText.toLowerCase().includes(q)) show = false;
        if (rrFilters.status   && (row.dataset.reqStatus   ||'') !== rrFilters.status)   show = false;
        if (rrFilters.category && (row.dataset.reqCategory ||'') !== rrFilters.category) show = false;
        row.style.display = show ? '' : 'none';
    });
    if (window._rrPager) window._rrPager.render();
}

var searchInput = document.getElementById('rrSearch');
searchInput && searchInput.addEventListener('input', applyRRFilters);

document.querySelectorAll('.rv-chip[data-filter]').forEach(function (chip) {
    chip.addEventListener('click', function (e) {
        e.preventDefault();
        var filter = this.dataset.filter;
        var val    = this.dataset.val.toLowerCase();
        rrFilters[filter] = val;
        var row = this.closest('.rv-chip-row');
        if (row) {
            row.querySelectorAll('.rv-chip').forEach(function (c) {
                var active = c.dataset.val.toLowerCase() === val;
                c.classList.toggle('active', active);
                var icon = c.querySelector('.rr-check-icon');
                if (icon) icon.style.display = active ? '' : 'none';
            });
        }
        applyRRFilters();
    });
});

/* ── Confirm dialog ── */
var _dlgResolve = null;
var dlg    = document.getElementById('rrConfirmDialog');
var dlgOk  = document.getElementById('rrConfirmOk');
var dlgHdr = dlg ? dlg.querySelector('.confirm-hdr') : null;
function openConfirm(title, msg, icon, type) {
    if (_dlgResolve) return Promise.resolve(false);
    return new Promise(function (resolve) {
        _dlgResolve = resolve;
        document.getElementById('rrConfirmTitle').textContent   = title;
        document.getElementById('rrConfirmMessage').textContent = msg;
        document.getElementById('rrConfirmIcon').textContent    = icon || 'delete_forever';
        // Apply colour theme
        var isArchive = type === 'archive';
        var color     = isArchive ? '#c07000' : '#c0392b';
        var bg        = isArchive ? '#e65c00' : '#c0392b';
        var bgHover   = isArchive ? '#b85000' : '#a93226';
        if (dlgOk) {
            dlgOk.style.background = bg;
            dlgOk.style.borderColor = bg;
            dlgOk.onmouseenter = function () { dlgOk.style.background = bgHover; };
            dlgOk.onmouseleave = function () { dlgOk.style.background = bg; };
        }
        if (dlgHdr) dlgHdr.querySelector('.confirm-hdr-icon').style.color = color;
        dlg.style.display = '';
        dlg.classList.add('open');
    });
}
document.getElementById('rrConfirmOk')?.addEventListener('click', function () {
    if (!_dlgResolve) return;
    var r = _dlgResolve; _dlgResolve = null;
    dlg.classList.remove('open'); dlg.style.display = 'none'; r(true);
});
document.getElementById('rrConfirmCancel')?.addEventListener('click', function () {
    if (!_dlgResolve) return;
    var r = _dlgResolve; _dlgResolve = null;
    dlg.classList.remove('open'); dlg.style.display = 'none'; r(false);
});
document.querySelector('#rrConfirmDialog .confirm-backdrop')?.addEventListener('click', function () {
    if (!_dlgResolve) return;
    var r = _dlgResolve; _dlgResolve = null;
    dlg.classList.remove('open'); dlg.style.display = 'none'; r(false);
});

/* ── Post helper ── */
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

function flash(msg, ok) {
    var b = document.createElement('div');
    b.className = 'pz-alert ' + (ok ? 'pz-alert-success' : 'pz-alert-error');
    b.style.cssText = 'margin-bottom:10px';
    b.innerHTML = '<span class="material-symbols-outlined" style="font-size:1rem">' + (ok ? 'check_circle' : 'error') + '</span> ' + msg;
    var header = document.querySelector('.pz-page-header');
    if (header) header.after(b);
    setTimeout(function () { b.remove(); }, 3500);
}

function removeRows(ids) {
    ids.forEach(function (id) {
        var row = document.querySelector('tr[data-req-id="' + id + '"]');
        if (!row) return;
        row.style.transition = 'opacity .3s, background .3s';
        row.style.opacity = '0';
        row.style.background = '#fee2e2';
        setTimeout(function () { row.remove(); updateBulkBar(); }, 320);
    });
}

/* ── Checkboxes & bulk bar ── */
var bulkBar   = document.getElementById('rrBulkBar');
var bulkCount = document.getElementById('rrBulkCount');
var selectAll = document.getElementById('rrSelectAll');

function getChecked() {
    return Array.from(document.querySelectorAll('.rr-row-cb:checked')).map(function (cb) { return cb.value; });
}

function updateBulkBar() {
    if (!bulkBar) return;
    var checked = getChecked();
    if (checked.length > 0) {
        bulkBar.style.display = 'flex';
        bulkCount.textContent = checked.length + ' selected';
    } else {
        bulkBar.style.display = 'none';
        if (selectAll) selectAll.checked = false;
    }
}

selectAll && selectAll.addEventListener('change', function () {
    document.querySelectorAll('.rr-row-cb').forEach(function (cb) {
        var row = cb.closest('tr');
        if (!row || row.style.display === 'none') return;
        cb.checked = selectAll.checked;
    });
    updateBulkBar();
});

document.addEventListener('change', function (e) {
    if (e.target && e.target.classList.contains('rr-row-cb')) updateBulkBar();
});

document.getElementById('rrBulkClearBtn')?.addEventListener('click', function () {
    document.querySelectorAll('.rr-row-cb').forEach(function (cb) { cb.checked = false; });
    if (selectAll) selectAll.checked = false;
    updateBulkBar();
});

/* ── Bulk archive ── */
document.getElementById('rrBulkArchiveBtn')?.addEventListener('click', async function () {
    var ids = getChecked();
    if (!ids.length) return;
    if (!await openConfirm('Archive Requests', 'Archive ' + ids.length + ' selected request(s)?', 'inventory_2', 'archive')) return;
    post(BASE + 'requests/archive', { 'ids[]': ids }).then(function (d) {
        if (d.success) { removeRows(ids); flash(ids.length + ' request(s) archived.', true); }
        else flash(d.message || 'Archive failed.', false);
    }).catch(function () { flash('Network error.', false); });
});

/* ── Row-level archive ── */
document.addEventListener('click', async function (e) {
    var archBtn = e.target.closest('.rr-archive-btn');

    if (archBtn) {
        var id = archBtn.dataset.id;
        if (!await openConfirm('Archive Request', 'Archive request ' + id.substring(0,6).toUpperCase() + '?', 'inventory_2', 'archive')) return;
        archBtn.disabled = true;
        post(BASE + 'requests/archive', { 'ids[]': [id] }).then(function (d) {
            if (d.success) { removeRows([id]); flash('Request archived.', true); }
            else { flash(d.message || 'Archive failed.', false); archBtn.disabled = false; }
        }).catch(function () { flash('Network error.', false); archBtn.disabled = false; });
    }

});

/* ── Client-side pager ── */
window._rrPager = new PzClientPager({
    tableId: 'rrTable',
    rowSel:  'tbody tr[data-req-id]',
    noCol:   -1,
    infoId:  'rrPagerInfo',
    navId:   'rrPagerNav',
});
window._rrPager.render();
}());
</script>
<?php $this->endSection() ?>

<?php $this->section('firebase-scripts') ?>
<script>
(function () {
  window._pzPageListeners = true;

  var _base = window.pzBaseUrl || '';
  var _fStatus  = (new URLSearchParams(window.location.search).get('status')   || '').toLowerCase();
  var _fCat     = (new URLSearchParams(window.location.search).get('category') || '').toLowerCase();
  var CAN_ARCHIVE = <?= $canArchive ? 'true' : 'false' ?>;

  function fmtDate(ts) {
    if (!ts || typeof ts.toDate !== 'function') return '—';
    var d = ts.toDate();
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
         + ' ' + d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
  }

  function buildRow(id, data) {
    var status  = data.status   || 'Pending';
    var cat     = data.resource_category || '—';
    var cls     = window.pzBadgeClass ? window.pzBadgeClass(status) : '';
    var shortId = id.substring(0, 6).toUpperCase();
    var src     = (data.source_site_name      || '—').replace(/</g,'&lt;');
    var dst     = (data.destination_site_name || '—').replace(/</g,'&lt;');
    var cbCol   = CAN_ARCHIVE ? '<td class="pz-col-check"><input type="checkbox" class="rr-row-cb" value="' + id + '"></td>' : '';
    var archBtn = CAN_ARCHIVE ? '<button type="button" class="action-btn rr-archive-btn" data-id="' + id + '" title="Archive"><span class="material-symbols-outlined" style="color:#c07000">inventory_2</span></button>' : '';
    return '<tr data-req-id="' + id + '" class="pz-row-new">'
         + cbCol
         + '<td style="font-family:monospace;letter-spacing:.05em">' + shortId + '</td>'
         + '<td title="' + src + '">' + src + '</td>'
         + '<td title="' + dst + '">' + dst + '</td>'
         + '<td class="text-center" data-req-status><span class="pz-badge ' + cls + '">' + status.toUpperCase() + '</span></td>'
         + '<td class="rr-col-sm">' + cat + '</td>'
         + '<td class="rr-col-md text-center">' + (data.requested_quantity || data.quantity || 1) + '</td>'
         + '<td class="rr-col-md" style="white-space:nowrap">' + fmtDate(data.created_at) + '</td>'
         + '<td class="text-center" style="white-space:nowrap"><a href="' + _base + 'requests/' + id + '" class="action-btn" title="View details">'
         + '<span class="material-symbols-outlined">visibility</span></a>'
         + archBtn + '</td>'
         + '</tr>';
  }

  function matchesFilter(data) {
    var status = (data.status   || '').toLowerCase();
    var cat    = (data.resource_category || '').toLowerCase();
    if (_fStatus && _fStatus !== status) return false;
    if (_fCat    && _fCat    !== cat)    return false;
    return true;
  }

  function flashRow(row) {
    row.style.transition  = 'background .2s';
    row.style.background  = '#dbeafe';
    setTimeout(function () { row.style.background = ''; }, 1000);
  }

  var _setupDone = false;
  function setup(db) {
    if (_setupDone) return; _setupDone = true;
    var _ready = false;
    db.collection('resource_requests').onSnapshot(function (snap) {
      if (!_ready) { _ready = true; return; }
      snap.docChanges().forEach(function (chg) {
        var id   = chg.doc.id;
        var data = chg.doc.data();

        if (chg.type === 'modified') {
          var row = document.querySelector('tr[data-req-id="' + id + '"]');
          if (!row) return;
          var cell = row.querySelector('[data-req-status]');
          if (cell) {
            var s = data.status || '';
            cell.innerHTML = '<span class="pz-badge ' + (window.pzBadgeClass ? window.pzBadgeClass(s) : '') + '">' + s.toUpperCase() + '</span>';
          }
          flashRow(row);
          return;
        }

        if (chg.type === 'added' && matchesFilter(data)) {
          if (document.querySelector('tr[data-req-id="' + id + '"]')) return;
          var tbody = document.querySelector('#rrTable tbody');
          if (!tbody) return;
          var empty = tbody.querySelector('td[colspan]');
          if (empty) empty.closest('tr').remove();
          var tmp = document.createElement('tbody');
          tmp.innerHTML = buildRow(id, data);
          tbody.insertBefore(tmp.firstElementChild, tbody.firstChild);
          if (window._rrPager) window._rrPager.render();
          return;
        }

        if (chg.type === 'removed') {
          var row = document.querySelector('tr[data-req-id="' + id + '"]');
          if (!row) return;
          row.style.transition = 'opacity .4s, background .4s';
          row.style.background = '#fee2e2';
          row.style.opacity    = '0';
          setTimeout(function () {
            row.remove();
            var tbody = document.querySelector('#rrTable tbody');
            if (tbody && !tbody.querySelector('tr[data-req-id]')) {
              var cols = CAN_ARCHIVE ? 9 : 8;
              tbody.innerHTML = '<tr><td colspan="' + cols + '" class="pz-table-empty">No requests found.</td></tr>';
            }
          }, 400);
        }
      });
    }, function (err) { console.warn('[PZ] Requests listener:', err.message); });
  }

  firebase.auth().onAuthStateChanged(function (user) {
    if (user && window.pzFirebaseDb) setup(window.pzFirebaseDb);
  });
}());
</script>
<?php $this->endSection() ?>
