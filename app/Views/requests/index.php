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
            <a href="#" data-filter="status" data-val=""
               class="pz-chip <?= $filterStatus === '' ? 'active' : '' ?>">
                <span class="rr-check-icon material-symbols-outlined" <?= $filterStatus === '' ? '' : 'style="display:none"' ?>>check</span>All
            </a>
            <?php foreach (['Pending', 'Pending Secondary Approval', 'In-Transit', 'Completed'] as $s): ?>
            <a href="#" data-filter="status" data-val="<?= esc($s) ?>"
               class="pz-chip <?= $filterStatus === $s ? 'active' : '' ?>">
                <span class="rr-check-icon material-symbols-outlined" <?= $filterStatus === $s ? '' : 'style="display:none"' ?>>check</span><?= esc($s) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="rr-filter-section">
        <div class="rr-filter-label">Category</div>
        <div class="pz-chip-row">
            <a href="#" data-filter="category" data-val=""
               class="pz-chip <?= $filterCategory === '' ? 'active' : '' ?>">
                <span class="rr-check-icon material-symbols-outlined" <?= $filterCategory === '' ? '' : 'style="display:none"' ?>>check</span>All
            </a>
            <?php foreach (['Manpower', 'Equipment', 'Tools'] as $c): ?>
            <a href="#" data-filter="category" data-val="<?= esc($c) ?>"
               class="pz-chip <?= $filterCategory === $c ? 'active' : '' ?>">
                <span class="rr-check-icon material-symbols-outlined" <?= $filterCategory === $c ? '' : 'style="display:none"' ?>>check</span><?= esc($c) ?>
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
            <tr data-req-id="<?= esc($r['id']) ?>"
                data-req-status="<?= esc(strtolower($r['status'] ?? 'pending')) ?>"
                data-req-category="<?= esc(strtolower($r['resource_category'] ?? '')) ?>">
                <td style="font-family:monospace;letter-spacing:.05em"><?= esc(strtoupper(substr($r['id'], 0, 6))) ?></td>
                <td class="pz-row-muted" title="<?= esc($r['source_site_name'] ?? '—') ?>"><?= esc($r['source_site_name'] ?? '—') ?></td>
                <td class="pz-row-muted" title="<?= esc($r['destination_site_name'] ?? '—') ?>"><?= esc($r['destination_site_name'] ?? '—') ?></td>
                <td data-req-status><?= rr_badge($r['status'] ?? 'Pending') ?></td>
                <td class="rr-col-sm"><?= esc($r['resource_category'] ?? '—') ?></td>
                <td class="rr-col-md"><?= esc($r['requested_quantity'] ?? $r['quantity'] ?? 1) ?></td>
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
    }

    /* ── Search — live ── */
    var searchInput = document.getElementById('rrSearch');
    searchInput && searchInput.addEventListener('input', applyRRFilters);

    /* ── Chip clicks — live ── */
    document.querySelectorAll('.pz-chip[data-filter]').forEach(function (chip) {
        chip.addEventListener('click', function (e) {
            e.preventDefault();
            var filter = this.dataset.filter;
            var val    = this.dataset.val.toLowerCase();
            rrFilters[filter] = val;
            // Update active state + check icon in this chip row
            var row = this.closest('.pz-chip-row');
            if (row) {
                row.querySelectorAll('.pz-chip').forEach(function (c) {
                    var active = c.dataset.val.toLowerCase() === val;
                    c.classList.toggle('active', active);
                    var icon = c.querySelector('.rr-check-icon');
                    if (icon) icon.style.display = active ? '' : 'none';
                });
            }
            applyRRFilters();
        });
    });
});
</script>
<?php $this->endSection() ?>

<?php $this->section('firebase-scripts') ?>
<script>
(function () {
  window._pzPageListeners = true; // own our listeners

  var _base = window.pzBaseUrl || '';
  var _params   = new URLSearchParams(window.location.search);
  var _fStatus  = (_params.get('status')   || '').toLowerCase();
  var _fCat     = (_params.get('category') || '').toLowerCase();
  var _page     = parseInt(_params.get('page') || '1', 10);

  function fmtDate(ts) {
    if (!ts || typeof ts.toDate !== 'function') return '—';
    var d = ts.toDate();
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  }

  function buildRow(id, data) {
    var status  = data.status   || 'Pending';
    var cat     = data.resource_category || '—';
    var cls     = window.pzBadgeClass ? window.pzBadgeClass(status) : '';
    var shortId = id.substring(0, 6).toUpperCase();
    var src     = (data.source_site_name      || '—').replace(/</g,'&lt;');
    var dst     = (data.destination_site_name || '—').replace(/</g,'&lt;');
    return '<tr data-req-id="' + id + '" class="pz-row-new">'
         + '<td style="font-family:monospace;letter-spacing:.05em">' + shortId + '</td>'
         + '<td class="pz-row-muted" title="' + src + '">' + src + '</td>'
         + '<td class="pz-row-muted" title="' + dst + '">' + dst + '</td>'
         + '<td data-req-status><span class="pz-badge ' + cls + '">' + status.toUpperCase() + '</span></td>'
         + '<td class="rr-col-sm">' + cat + '</td>'
         + '<td class="rr-col-md">' + (data.requested_quantity || data.quantity || 1) + '</td>'
         + '<td class="pz-row-muted rr-col-md" style="white-space:nowrap">' + fmtDate(data.created_at) + '</td>'
         + '<td class="actions"><a href="' + _base + 'requests/' + id + '" class="pz-btn-icon" title="View details">'
         + '<span class="material-symbols-outlined">visibility</span></a></td>'
         + '</tr>';
  }

  function matchesFilter(data) {
    if (_page > 1) return false; // only insert on page 1 (newest first)
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
    console.log('[PZ-req] listener attached, page=' + _page + ' fStatus="' + _fStatus + '" fCat="' + _fCat + '"');
    var _ready = false;
    db.collection('resource_requests').onSnapshot(function (snap) {
      if (!_ready) {
        _ready = true;
        console.log('[PZ-req] initial snapshot skipped (' + snap.size + ' docs)');
        return;
      }
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
          var tbody = document.querySelector('table tbody');
          if (!tbody) return;
          var empty = tbody.querySelector('td[colspan]');
          if (empty) empty.closest('tr').remove();
          var tmp = document.createElement('tbody');
          tmp.innerHTML = buildRow(id, data);
          tbody.insertBefore(tmp.firstElementChild, tbody.firstChild);
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
            var tbody = document.querySelector('table tbody');
            if (tbody && !tbody.querySelector('tr[data-req-id]')) {
              tbody.innerHTML = '<tr><td colspan="8" class="pz-table-empty">No requests found.</td></tr>';
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
