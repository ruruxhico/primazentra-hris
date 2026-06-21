<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $pageTitle = isset($pageTitle) && is_string($pageTitle) ? $pageTitle : 'Dashboard'; ?>
    <?php $userName = (isset($userName) && is_string($userName) && $userName !== '') ? $userName : (session()->get('userName') ?: 'User'); ?>
    <?php $roleLabel = isset($role) && is_scalar($role) ? (string) $role : 'USER'; ?>
    <title>PrimaZentra | <?= htmlspecialchars(is_array($pageTitle) ? 'Dashboard' : (string) $pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/x-icon" href="<?= asset_url('favicon.ico') ?>?v=3">
    <link rel="shortcut icon" type="image/x-icon" href="<?= asset_url('favicon.ico') ?>?v=3">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= base_url(is_file(FCPATH . 'css/styles.css') ? 'css/styles.css' : 'public/css/styles.css') ?>?v=<?= filemtime(ROOTPATH . 'public/css/styles.css') ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap">
    <?= $this->renderSection('head') ?>
</head>
<body>
  <div id="wrapper">

    <?= $this->include('sidebar') ?>

    <div id="content-wrapper" class="main-content">

      <div id="content">
        <header class="header">
          <button id="sidebarToggleTop" class="sidebar-mobile-toggle d-md-none"
                  aria-label="Toggle sidebar">☰</button>
          <img src="<?= asset_url('assets/PrimaSolution.png') ?>" alt="PrimaSolutions" class="header-logo">
          <div class="header-right">
            <!-- Notification bell -->
            <div class="pz-notif-wrap" id="pzNotifWrap">
              <button class="pz-notif-btn" id="pzNotifBtn" aria-label="Notifications" title="Notifications">
                <span class="material-symbols-outlined">notifications</span>
                <span class="pz-notif-badge" id="pzNotifBadge" style="display:none"></span>
              </button>
              <div class="pz-notif-panel" id="pzNotifPanel">
                <div class="pz-notif-hdr">
                  <span>Notifications</span>
                  <button class="pz-notif-mark-all" id="pzMarkAll" title="Mark all as read">Mark all read</button>
                </div>
                <div class="pz-notif-list" id="pzNotifList">
                  <div class="pz-notif-empty">Loading…</div>
                </div>
                <a href="<?= base_url('notifications') ?>" class="pz-notif-view-all">View all notifications</a>
              </div>
            </div>
            <!-- Messages shortcut -->
            <div class="pz-msg-wrap" id="pzMsgWrap">
              <a href="<?= base_url('messages') ?>" class="pz-msg-btn" title="Messages" aria-label="Messages">
                <span class="material-symbols-outlined">chat</span>
                <span class="pz-msg-badge" id="pzMsgBadge" style="display:none"></span>
              </a>
            </div>
            <?php
              $hdrPic = (string)(session()->get('profilePicture') ?? '');
              $hdrPicValid = $hdrPic !== '' && file_exists(FCPATH . $hdrPic);
            ?>
            <a href="<?= base_url('profile') ?>" class="user-profile" style="text-decoration:none;color:inherit;" title="My Profile">
              <span class="avatar-initial" style="<?= $hdrPicValid ? 'padding:0;overflow:hidden;' : '' ?>">
                <?php if ($hdrPicValid): ?>
                  <img src="<?= base_url($hdrPic) ?>?v=<?= filemtime(FCPATH . $hdrPic) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                <?php else: ?>
                  <?= strtoupper(substr($userName !== '' ? $userName : 'User', 0, 1)) ?>
                <?php endif; ?>
              </span>
              <div>
                <strong><?= htmlspecialchars((is_array($userName) || (string)$userName === '') ? 'User' : (string) $userName, ENT_QUOTES, 'UTF-8') ?></strong><br>
                <small><?= strtoupper($roleLabel) ?></small>
              </div>
            </a>
          </div>
        </header>

        <div class="page-content">
          <?= $this->renderSection('content') ?>
        </div>
      </div>

    </div>
  </div>
  <?= $this->renderSection('scripts') ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

  <!-- ── Notification bell ── -->
  <style>
  .pz-notif-wrap { position:relative; }
  .pz-notif-btn {
    background:none; border:none; cursor:pointer; padding:6px;
    display:flex; align-items:center; justify-content:center;
    position:relative; color:#fff; border-radius:8px;
    transition:background .15s;
  }
  .pz-notif-btn:hover { background:rgba(255,255,255,.15); }
  .pz-notif-btn .material-symbols-outlined { font-size:22px; color:#ffd740; font-variation-settings:'FILL' 1,'wght' 400; }
  .pz-notif-badge {
    position:absolute; top:3px; right:3px;
    background:#e53935; color:#fff; font-size:10px; font-weight:700;
    min-width:16px; height:16px; border-radius:8px;
    display:flex; align-items:center; justify-content:center;
    padding:0 3px; pointer-events:none; line-height:1;
  }
  .pz-notif-panel {
    display:none; position:absolute; right:0; top:calc(100% + 6px);
    width:340px; background:#fff; border-radius:10px;
    box-shadow:0 4px 24px rgba(0,0,0,.18); z-index:9999;
    overflow:hidden;
  }
  .pz-notif-panel.open { display:block; }
  .pz-notif-hdr {
    display:flex; align-items:center; justify-content:space-between;
    padding:12px 16px; border-bottom:1px solid #eee;
    font-weight:700; font-size:.92rem; color:#333;
  }
  .pz-notif-mark-all {
    background:none; border:none; cursor:pointer; font-size:.78rem;
    color:#b8960c; font-weight:600; padding:0;
  }
  .pz-notif-mark-all:hover { text-decoration:underline; }
  .pz-notif-list { max-height:380px; overflow-y:auto; }
  .pz-notif-empty { padding:32px 16px; text-align:center; color:#999; font-size:.88rem; }
  .pz-notif-item {
    display:flex; align-items:flex-start; gap:10px;
    padding:12px 16px; border-bottom:1px solid #f5f5f5;
    cursor:pointer; transition:background .12s;
    text-decoration:none; color:inherit;
  }
  .pz-notif-item:hover { background:#fdf8e1; }
  .pz-notif-item.unread { background:#fffbea; }
  .pz-notif-item.unread:hover { background:#fdf3c0; }
  .pz-notif-icon {
    width:34px; height:34px; border-radius:50%; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    background:#f0e9c5; color:#b8960c;
  }
  .pz-notif-icon .material-symbols-outlined { font-size:18px; }
  .pz-notif-content { flex:1; min-width:0; }
  .pz-notif-title { font-weight:600; font-size:.85rem; color:#222; line-height:1.3; }
  .pz-notif-body  { font-size:.8rem; color:#555; margin-top:2px; line-height:1.35; }
  .pz-notif-time  { font-size:.72rem; color:#aaa; margin-top:4px; }
  .pz-notif-dot   { width:8px; height:8px; border-radius:50%; background:#b8960c; flex-shrink:0; margin-top:5px; }
  .pz-notif-view-all {
    display:block; text-align:center; padding:10px;
    font-size:.8rem; color:#b8960c; font-weight:600;
    border-top:1px solid #eee; text-decoration:none;
    transition:background .12s;
  }
  .pz-notif-view-all:hover { background:#fdf8e1; }
  .pz-msg-wrap { position:relative; }
  .pz-msg-badge {
    position:absolute; top:3px; right:3px;
    background:#e53935; color:#fff; font-size:10px; font-weight:700;
    min-width:16px; height:16px; border-radius:8px;
    display:flex; align-items:center; justify-content:center;
    padding:0 3px; pointer-events:none; line-height:1;
  }
  .pz-msg-btn {
    background:none; border:none; cursor:pointer; padding:6px;
    display:flex; align-items:center; justify-content:center;
    border-radius:50%; transition:background .15s; text-decoration:none; position:relative;
  }
  .pz-msg-btn:hover { background:rgba(255,255,255,.15); }
  .pz-msg-btn .material-symbols-outlined { font-size:22px; color:#90caf9; font-variation-settings:'FILL' 1,'wght' 400; }
  </style>
  <script>
  (function () {
    var _base    = '<?= base_url() ?>';
    var _csrf    = '<?= csrf_token() ?>';
    var _open    = false;
    var _data    = [];

    var btn    = document.getElementById('pzNotifBtn');
    var panel  = document.getElementById('pzNotifPanel');
    var badge  = document.getElementById('pzNotifBadge');
    var list   = document.getElementById('pzNotifList');
    var markAll= document.getElementById('pzMarkAll');

    var TYPE_ICON = {
      messageSent:       'chat_bubble',
      toolRequested:     'build',
      secondaryApproval: 'approval',
      requestApproved:   'check_circle',
      projectAssigned:   'folder_special',
      emergencyCreated:  'warning',
      progressReported:  'assignment_turned_in',
    };

    function h(s) {
      var d = document.createElement('div');
      d.appendChild(document.createTextNode(String(s || '')));
      return d.innerHTML;
    }

    function relTime(iso) {
      if (!iso) return '';
      var diff = Math.floor((Date.now() - new Date(iso.replace(' ', 'T') + '+08:00')) / 1000);
      if (diff <  60)  return 'just now';
      if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
      if (diff < 86400)return Math.floor(diff / 3600) + 'h ago';
      return Math.floor(diff / 86400) + 'd ago';
    }

    function render(items) {
      if (!items.length) {
        list.innerHTML = '<div class="pz-notif-empty">No notifications yet</div>';
        return;
      }
      list.innerHTML = items.map(function (n) {
        var icon = TYPE_ICON[n.type] || 'notifications';
        return '<div class="pz-notif-item' + (n.isRead ? '' : ' unread') + '" data-id="' + h(n.id) + '" data-url="' + h(navUrl(n)) + '">' +
          '<div class="pz-notif-icon"><span class="material-symbols-outlined">' + icon + '</span></div>' +
          '<div class="pz-notif-content">' +
            '<div class="pz-notif-title">' + h(n.title) + '</div>' +
            '<div class="pz-notif-body">'  + h(n.body)  + '</div>' +
            '<div class="pz-notif-time">'  + relTime(n.createdAt) + '</div>' +
          '</div>' +
          (n.isRead ? '' : '<div class="pz-notif-dot"></div>') +
        '</div>';
      }).join('');

      list.querySelectorAll('.pz-notif-item').forEach(function (el) {
        el.addEventListener('click', function () {
          var id  = el.dataset.id;
          var url = el.dataset.url;
          if (!el.classList.contains('unread')) {
            if (url) window.location.href = url;
            return;
          }
          el.classList.remove('unread');
          el.querySelector('.pz-notif-dot') && el.querySelector('.pz-notif-dot').remove();
          var fd = new FormData();
          fd.append(_csrf, document.querySelector('input[name="' + _csrf + '"]')?.value || '');
          fetch(_base + 'notifications/mark-read', {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json',
                       'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ id: id }),
          }).catch(function(){});
          updateBadge(_data.filter(function(x){ return !x.isRead && x.id !== id; }).length);
          if (url) window.location.href = url;
        });
      });
    }

    function navUrl(n) {
      var m = n.metadata || {};
      switch (n.type) {
        case 'projectAssigned':
        case 'emergencyCreated':
        case 'progressReported':  return (m.project_id||m.projectId) ? _base + 'projects/show/' + (m.project_id||m.projectId) : '';
        case 'toolRequested':
        case 'secondaryApproval':
        case 'requestApproved':   return (m.request_id||m.requestId) ? _base + 'requests/' + (m.request_id||m.requestId) : _base + 'requests';
        case 'messageSent':       return (m.chat_id||m.chatId) ? _base+'messages/'+(m.chat_id||m.chatId) : _base+'messages';
        default:                  return '';
      }
    }

    function updateBadge(count) {
      if (count > 0) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.style.display = 'flex';
      } else {
        badge.style.display = 'none';
      }
    }

    function load() {
      fetch(_base + 'notifications/json', { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          _data = d.notifications || [];
          updateBadge(d.unreadCount || 0);
          if (_open) render(_data);
        })
        .catch(function () {});
    }

    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      _open = !_open;
      panel.classList.toggle('open', _open);
      if (_open) render(_data);
    });

    document.addEventListener('click', function (e) {
      if (_open && !document.getElementById('pzNotifWrap').contains(e.target)) {
        _open = false;
        panel.classList.remove('open');
      }
    });

    markAll.addEventListener('click', function (e) {
      e.stopPropagation();
      _data.forEach(function (n) { n.isRead = true; });
      updateBadge(0);
      render(_data);
      fetch(_base + 'notifications/mark-all-read', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json',
                   'X-Requested-With': 'XMLHttpRequest' },
        body: '{}',
      }).catch(function(){});
    });

    // initial load + poll every 30 s
    load();
    setInterval(load, 30000);

    // ── Message unread badge ──────────────────────────────────────────────
    var msgBadge = document.getElementById('pzMsgBadge');
    function updateMsgBadge(n) {
      if (n > 0) {
        msgBadge.textContent = n > 99 ? '99+' : n;
        msgBadge.style.display = 'flex';
      } else {
        msgBadge.style.display = 'none';
      }
    }
    function loadMsgCount() {
      fetch(_base + 'messages/unread-count', { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) { updateMsgBadge(d.count || 0); })
        .catch(function () {});
    }
    loadMsgCount();
    setInterval(loadMsgCount, 30000);
  }());
  </script>

  <!-- Long-press drag-to-scroll (hold left button 300 ms on any non-interactive area) -->
  <style>
    body.pz-drag-scroll,
    body.pz-drag-scroll * { cursor: grabbing !important; user-select: none !important; }
  </style>
  <script>
  (function () {
    var DELAY = 300; // ms before drag-scroll activates
    var SKIP  = 'a, button, input, select, textarea, label, ' +
                '.db-pill-icons, .db-res-group-hdr, .map-panel-hdr, ' +
                '.db-btn-locate, .db-icon-chat, .db-icon-warn, ' +
                '[onclick], .sidebar-link, .nav-link';

    var timer  = null;
    var active = false;
    var lastX  = 0, lastY = 0;

    function scrollTarget() {
      var cw = document.getElementById('content-wrapper');
      if (cw && (cw.scrollHeight > cw.clientHeight || cw.scrollWidth > cw.clientWidth)) return cw;
      return document.documentElement;
    }

    document.addEventListener('mousedown', function (e) {
      if (e.button !== 0) return;
      if (e.target.closest(SKIP)) return;

      lastX = e.clientX;
      lastY = e.clientY;

      timer = setTimeout(function () {
        active = true;
        document.body.classList.add('pz-drag-scroll');
      }, DELAY);
    });

    document.addEventListener('mousemove', function (e) {
      if (!active) return;
      var dx = e.clientX - lastX;
      var dy = e.clientY - lastY;
      lastX = e.clientX;
      lastY = e.clientY;
      var el = scrollTarget();
      el.scrollLeft -= dx;
      el.scrollTop  -= dy;
      e.preventDefault();
    }, { passive: false });

    function stop() {
      clearTimeout(timer);
      timer  = null;
      active = false;
      document.body.classList.remove('pz-drag-scroll');
    }

    document.addEventListener('mouseup',    stop);
    document.addEventListener('mouseleave', stop);
  }());
  </script>

  <!-- ── Warehouse address picker (shared between equipment & tools forms) ── -->
  <script>
  window.whCsrfTokenName = '<?= csrf_token() ?>';

  window.initWarehousePicker = function (input, card) {
    var wrap = input.closest('.unit-warehouse-input-wrap');
    if (!wrap || wrap.dataset.pickerInit) return;
    wrap.dataset.pickerInit = '1';

    var picker = document.createElement('div');
    picker.className = 'wh-picker';
    wrap.appendChild(picker);

    var nameRow = null;

    function getSaved()  { return window.whSavedLocations  || []; }
    function getOther()  {
      var savedAddrs = getSaved().map(function (l) { return (l.address || '').toLowerCase(); });
      return (window.whOtherLocations || []).filter(function (a) {
        return a.trim() !== '' && savedAddrs.indexOf(a.toLowerCase()) === -1;
      });
    }

    function esc(str) {
      var d = document.createElement('div');
      d.appendChild(document.createTextNode(String(str || '')));
      return d.innerHTML;
    }

    function renderPicker(filter) {
      picker.innerHTML = '';
      filter = (filter || '').toLowerCase().trim();

      var saved = getSaved().filter(function (l) {
        return !filter || (l.name || '').toLowerCase().includes(filter) || (l.address || '').toLowerCase().includes(filter);
      });
      var other = getOther().filter(function (a) {
        return !filter || a.toLowerCase().includes(filter);
      });
      var curAddr   = (input.value || '').trim();
      var alreadySaved = getSaved().some(function (l) {
        return (l.address || '').toLowerCase() === curAddr.toLowerCase();
      });

      if (saved.length > 0) {
        var lbl = document.createElement('div');
        lbl.className = 'wh-picker-section-label';
        lbl.textContent = 'Saved Locations';
        picker.appendChild(lbl);

        saved.forEach(function (loc) {
          var item = document.createElement('div');
          item.className = 'wh-picker-item';
          item.innerHTML =
            '<span class="material-symbols-outlined wh-picker-pin">push_pin</span>' +
            '<div class="wh-picker-info">' +
              '<span class="wh-picker-name">'       + esc(loc.name || loc.address) + '</span>' +
              '<span class="wh-picker-addr-small">' + esc(loc.address)             + '</span>' +
            '</div>' +
            '<button type="button" class="wh-picker-remove" title="Remove">&#10005;</button>';

          item.querySelector('.wh-picker-info').addEventListener('mousedown', function (e) {
            e.preventDefault();
            input.value = loc.address;
            if (card) card.dataset.warehouseValue = loc.address;
            closePicker();
            input.dispatchEvent(new Event('input', { bubbles: true }));
          });
          item.querySelector('.wh-picker-remove').addEventListener('mousedown', function (e) {
            e.preventDefault();
            e.stopPropagation();
            doDelete(loc.address);
          });
          picker.appendChild(item);
        });
      }

      if (other.length > 0) {
        if (saved.length > 0) {
          var div = document.createElement('hr');
          div.className = 'wh-picker-divider';
          picker.appendChild(div);
        }
        var lbl2 = document.createElement('div');
        lbl2.className = 'wh-picker-section-label';
        lbl2.textContent = 'Other Warehouse Locations';
        picker.appendChild(lbl2);

        other.forEach(function (addr) {
          var item = document.createElement('div');
          item.className = 'wh-picker-item';
          item.innerHTML =
            '<span class="material-symbols-outlined wh-picker-other-icon">warehouse</span>' +
            '<span class="wh-picker-addr-small" style="flex:1;min-width:0;color:#444;">' + esc(addr) + '</span>';
          item.addEventListener('mousedown', function (e) {
            e.preventDefault();
            input.value = addr;
            if (card) card.dataset.warehouseValue = addr;
            closePicker();
            input.dispatchEvent(new Event('input', { bubbles: true }));
          });
          picker.appendChild(item);
        });
      }

      if (saved.length === 0 && other.length === 0) {
        var empty = document.createElement('div');
        empty.className = 'wh-picker-empty';
        empty.textContent = filter ? 'No matches found' : 'No saved locations yet — type an address below, then save it';
        picker.appendChild(empty);
      }

      if (curAddr && !alreadySaved) {
        var saveRow = document.createElement('div');
        saveRow.className = 'wh-picker-save-row';
        var saveBtn = document.createElement('button');
        saveBtn.type = 'button';
        saveBtn.className = 'wh-picker-save-btn';
        var preview = curAddr.length > 38 ? curAddr.slice(0, 38) + '…' : curAddr;
        saveBtn.textContent = '+ Save "' + preview + '" as…';
        saveBtn.addEventListener('mousedown', function (e) {
          e.preventDefault();
          showNameRow(curAddr, saveRow);
        });
        saveRow.appendChild(saveBtn);
        picker.appendChild(saveRow);
      }
    }

    function showNameRow(address, saveRow) {
      if (nameRow) return;
      nameRow = document.createElement('div');
      nameRow.className = 'wh-picker-name-row';
      var ni  = document.createElement('input');
      ni.type = 'text';
      ni.className = 'wh-picker-name-input';
      ni.placeholder = 'Nickname, e.g. "Main Warehouse"';
      var ok  = document.createElement('button');
      ok.type = 'button';
      ok.className = 'wh-picker-name-confirm';
      ok.textContent = 'Save';
      var cn  = document.createElement('button');
      cn.type = 'button';
      cn.className = 'wh-picker-name-cancel';
      cn.textContent = '×';

      nameRow.appendChild(ni);
      nameRow.appendChild(ok);
      nameRow.appendChild(cn);
      picker.insertBefore(nameRow, saveRow);
      ni.focus();

      ok.addEventListener('mousedown', function (e) {
        e.preventDefault();
        doSave(ni.value.trim() || address, address);
      });
      cn.addEventListener('mousedown', function (e) {
        e.preventDefault();
        nameRow.remove();
        nameRow = null;
      });
      ni.addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter')  { ev.preventDefault(); ok.dispatchEvent(new MouseEvent('mousedown')); }
        if (ev.key === 'Escape') { ev.preventDefault(); cn.dispatchEvent(new MouseEvent('mousedown')); }
      });
    }

    function csrfFd() {
      var fd = new FormData();
      var n  = window.whCsrfTokenName;
      var el = n ? document.querySelector('input[name="' + n + '"]') : null;
      if (el) fd.append(n, el.value);
      return fd;
    }

    function doSave(name, address) {
      var fd = csrfFd();
      fd.append('name', name);
      fd.append('address', address);
      fetch(window.whSaveUrl || '', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d.success && d.saved_locations) {
            window.whSavedLocations = d.saved_locations;
            if (nameRow) { nameRow.remove(); nameRow = null; }
            renderPicker(input.value);
          }
        })
        .catch(function () {});
    }

    function doDelete(address) {
      var fd = csrfFd();
      fd.append('address', address);
      fetch(window.whDeleteUrl || '', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d.success) {
            window.whSavedLocations = d.saved_locations || [];
            renderPicker(input.value);
          }
        })
        .catch(function () {});
    }

    function openPicker() {
      renderPicker(input.value);
      picker.classList.add('open');
    }

    function closePicker() {
      picker.classList.remove('open');
      if (nameRow) { nameRow.remove(); nameRow = null; }
    }

    input.addEventListener('focus', openPicker);
    input.addEventListener('input', function () {
      if (!picker.classList.contains('open')) picker.classList.add('open');
      renderPicker(this.value);
    });
    input.addEventListener('blur', function () {
      setTimeout(closePicker, 150);
    });
  };
  </script>

  <!-- ── Interactive pagination ── -->
  <script>
  (function () {
    // Show loading spinner + navigate when a pager link is clicked
    document.addEventListener('click', function (e) {
      var link = e.target.closest('.pager-link');
      if (!link || !link.href || link.href === '#') return;

      var section = link.closest('.pager-section');
      if (!section) return;

      e.preventDefault();

      var overlay = document.createElement('div');
      overlay.className = 'pager-loading-overlay';
      overlay.innerHTML = '<div class="pager-spinner"></div>';
      section.appendChild(overlay);

      // Navigate after a short delay so the spinner is visible
      var dest = link.href;
      setTimeout(function () { window.location.href = dest; }, 80);
    });

    // Auto-scroll to the paginated section when a page param is present in the URL
    document.addEventListener('DOMContentLoaded', function () {
      var p = new URLSearchParams(window.location.search);
      var target = null;

      if (p.has('page_equipment')) target = document.getElementById('eq-section');
      if (p.has('page_tools'))    target = document.getElementById('tools-section');
      if (p.has('page_manpower')) target = document.getElementById('mp-section');
      // Staff and Projects both use 'page' — pick the one present on the page
      if (!target && p.has('page')) {
        target = document.getElementById('staff-section') ||
                 document.getElementById('projects-section');
      }

      if (target) {
        setTimeout(function () {
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 120);
      }

      // "Go to page" — jump on Enter
      document.querySelectorAll('.pager-jump-input').forEach(function (inp) {
        var maxPage   = parseInt(inp.dataset.totalPages, 10) || 1;
        var pageParam = inp.dataset.pageParam || 'page';

        inp.addEventListener('keydown', function (e) {
          if (e.key !== 'Enter') return;
          e.preventDefault();
          var n = parseInt(inp.value, 10);
          if (isNaN(n) || n < 1 || n > maxPage) return;
          var url = new URL(window.location.href);
          url.searchParams.set(pageParam, n);
          window.location.href = url.toString();
        });
      });
    });
  }());
  </script>

  <!-- ── Global ESC to close any open modal ── -->
  <script>
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;

    // 1. .open-class modals (pres-modal-overlay, reportModal, dndConfirmOverlay, etc.)
    document.querySelectorAll('.pres-modal-overlay.open, #reportModal.open, #dndConfirmOverlay.open').forEach(function (el) {
      el.classList.remove('open');
    });

    // 2. display:block modals (#addItemModal and any other legacy style.display modal)
    document.querySelectorAll('.modal-overlay, #addItemModal').forEach(function (el) {
      if (el.style.display && el.style.display !== 'none') {
        el.style.display = 'none';
      }
    });

    // 3. Page-specific close hooks (optional override per page)
    if (typeof window.pzEscClose === 'function') window.pzEscClose();
  });
  </script>
</body>
</html>
