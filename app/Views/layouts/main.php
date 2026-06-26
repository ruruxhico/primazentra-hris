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
    <link href="<?= asset_url('css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url(is_file(FCPATH . 'css/styles.css') ? 'css/styles.css' : 'public/css/styles.css') ?>?v=<?= filemtime(ROOTPATH . 'public/css/styles.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('css/material-symbols.css') ?>">
    <?= $this->renderSection('head') ?>
    <!-- Live-change bar (global, all pages) -->
    <style>
    #pzLiveBar {
        position: fixed; bottom: 1.2rem; left: 50%; transform: translateX(-50%) translateY(4px);
        background: #1e293b; color: #fff; border-radius: 2rem;
        padding: .55rem 1.25rem; font-size: .85rem; font-weight: 500;
        display: flex; align-items: center; gap: .6rem;
        box-shadow: 0 4px 18px rgba(0,0,0,.25); z-index: 9999;
        opacity: 0; pointer-events: none;
        transition: opacity .3s ease, transform .3s ease;
        white-space: nowrap;
    }
    #pzLiveBar.show { opacity: 1; pointer-events: auto; transform: translateX(-50%) translateY(0); }
    #pzLiveBar .pz-live-dot {
        width: 8px; height: 8px; border-radius: 50%; background: #4ade80;
        animation: pz-pulse 1.4s infinite; flex-shrink: 0;
    }
    @keyframes pz-pulse { 0%,100%{opacity:1} 50%{opacity:.35} }
    #pzLiveBar button {
        background: #3b82f6; border: none; color: #fff; border-radius: 1rem;
        padding: .25rem .8rem; font-size: .8rem; cursor: pointer; font-weight: 600;
        margin-left: .25rem;
    }
    #pzLiveBar button:hover { background: #2563eb; }
    #pzLiveBar .pz-bar-dismiss {
        cursor: pointer; opacity: .55; margin-left: .15rem;
        font-size: 1.1rem; line-height: 1; flex-shrink: 0;
    }
    #pzLiveBar .pz-bar-dismiss:hover { opacity: 1; }
    /* New row flash animation (used by all live-insert pages) */
    @keyframes pzRowNew { 0%{background:#dbeafe} 100%{background:transparent} }
    .pz-row-new { animation: pzRowNew 1.5s ease forwards; }
    </style>
</head>
<body>
<!-- ── PZ Form Auto-save — defined here (before page scripts) so hooks can reference it ── -->
<script>
(function () {
    var _P = 'pz_as_';
    function _k(form)  { return _P + (form.id || 'form') + location.pathname; }
    function _xk(form) { return _k(form) + '_x'; }

    function doSave(form) {
        var csrf = window.whCsrfTokenName || '';
        var data = {};
        form.querySelectorAll('input,select,textarea').forEach(function (el) {
            var n = el.name;
            if (!n || el.type === 'file' || el.type === 'submit' || el.type === 'button' || el.type === 'password') return;
            if (csrf && n === csrf) return;
            if (el.type === 'checkbox') data[n] = el.checked;
            else if (el.type === 'radio') { if (el.checked) data[n] = el.value; }
            else data[n] = el.value;
        });
        try { sessionStorage.setItem(_k(form), JSON.stringify(data)); } catch (e) {}
        if (typeof window['pzAsSave_' + form.id] === 'function') window['pzAsSave_' + form.id]();
    }

    function doRestore(form) {
        var raw = null;
        try { raw = sessionStorage.getItem(_k(form)); } catch (e) {}
        if (raw) {
            try {
                var data = JSON.parse(raw);
                var csrf = window.whCsrfTokenName || '';
                form.querySelectorAll('input,select,textarea').forEach(function (el) {
                    var n = el.name;
                    if (!n || el.type === 'file' || el.type === 'submit' || el.type === 'button' || el.type === 'password') return;
                    if (csrf && n === csrf) return;
                    if (!(n in data)) return;
                    if (el.type === 'checkbox') el.checked = !!data[n];
                    else if (el.type === 'radio') { if (el.value === data[n]) el.checked = true; }
                    else el.value = data[n];
                });
            } catch (e) {}
        }
        var xraw = null;
        try { xraw = sessionStorage.getItem(_xk(form)); } catch (e) {}
        if (typeof window['pzAsRestore_' + form.id] === 'function') {
            window['pzAsRestore_' + form.id](xraw ? JSON.parse(xraw) : null);
        }
    }

    function doClear(form) {
        try { sessionStorage.removeItem(_k(form)); sessionStorage.removeItem(_xk(form)); } catch (e) {}
    }

    window.pzAutoSave = { save: doSave, restore: doRestore, clear: doClear };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-autosave]').forEach(function (form) {
            var t;
            function debSave() { clearTimeout(t); t = setTimeout(function () { doSave(form); }, 400); }
            form.addEventListener('input',  debSave);
            form.addEventListener('change', debSave);
            form.addEventListener('submit', function () { doClear(form); });
            // setTimeout so other DOMContentLoaded listeners (date pickers, etc.) run first
            setTimeout(function () { doRestore(form); }, 0);
        });
    });
}());
</script>
<!-- ── PZ Custom Calendar — shared utility ── -->
<script>
(function () {
    function createCalendar(container, inputHidden, inputDisplay, opts) {
        opts = opts || {};
        var getMin = opts.getMin || function () { return ''; };
        var getMax = opts.getMax || function () { return ''; };
        var onSelect = opts.onSelect || function () {};
        var now = new Date(), viewYear = now.getFullYear(), viewMonth = now.getMonth();
        var mode = 'days', yearsStart = Math.floor(viewYear / 12) * 12;
        var header = document.createElement('div'); header.className = 'cal-header';
        var prev = document.createElement('button'); prev.type = 'button'; prev.className = 'cal-nav-btn'; prev.innerHTML = '<span class="material-symbols-outlined">chevron_left</span>';
        var title = document.createElement('div'); title.className = 'cal-title';
        var next = document.createElement('button'); next.type = 'button'; next.className = 'cal-nav-btn'; next.innerHTML = '<span class="material-symbols-outlined">chevron_right</span>';
        header.appendChild(prev); header.appendChild(title); header.appendChild(next);
        var grid = document.createElement('div'); grid.className = 'cal-grid';
        container.appendChild(header); container.appendChild(grid);
        function pad(n) { return String(n).padStart(2, '0'); }
        function renderDays() {
            title.textContent = new Date(viewYear, viewMonth).toLocaleString(undefined, { month: 'long', year: 'numeric' });
            title.setAttribute('data-mode', 'days'); grid.className = 'cal-grid'; grid.innerHTML = '';
            ['Su','Mo','Tu','We','Th','Fr','Sa'].forEach(function (d) { var e = document.createElement('div'); e.className = 'cal-day'; e.style.fontWeight = '600'; e.textContent = d; e.style.cursor = 'default'; grid.appendChild(e); });
            var first = new Date(viewYear, viewMonth, 1), start = first.getDay(), days = new Date(viewYear, viewMonth + 1, 0).getDate();
            var minVal = getMin(); var maxVal = getMax();
            for (var i = 0; i < start; i++) { var e = document.createElement('div'); e.className = 'cal-day disabled'; grid.appendChild(e); }
            for (var d = 1; d <= days; d++) {
                (function (day) {
                    var cellDate = viewYear + '-' + pad(viewMonth + 1) + '-' + pad(day);
                    var isDisabled = (minVal && cellDate < minVal) || (maxVal && cellDate > maxVal);
                    var e = document.createElement('div');
                    e.className = 'cal-day' + (isDisabled ? ' disabled' : '');
                    e.textContent = day;
                    if (!isDisabled) {
                        e.addEventListener('click', function () {
                            inputHidden.value = cellDate;
                            inputDisplay.value = pad(day) + '/' + pad(viewMonth + 1) + '/' + viewYear;
                            inputHidden.dispatchEvent(new Event('change'));
                            container.classList.remove('open'); container.setAttribute('aria-hidden', 'true');
                            onSelect(cellDate);
                        });
                    }
                    grid.appendChild(e);
                }(d));
            }
        }
        function renderMonths() {
            title.textContent = String(viewYear); title.setAttribute('data-mode', 'months');
            grid.className = 'months-grid'; grid.innerHTML = '';
            Array.from({ length: 12 }, function (_, i) { return new Date(0, i).toLocaleString(undefined, { month: 'short' }); }).forEach(function (mn, mi) {
                var cell = document.createElement('div'); cell.className = 'month-cell'; cell.textContent = mn;
                cell.addEventListener('click', function () { viewMonth = mi; mode = 'days'; render(); });
                grid.appendChild(cell);
            });
        }
        function renderYears() {
            title.textContent = yearsStart + ' - ' + (yearsStart + 11); title.setAttribute('data-mode', 'years');
            grid.className = 'year-grid'; grid.innerHTML = '';
            for (var y = yearsStart; y < yearsStart + 12; y++) {
                (function (yr) {
                    var cell = document.createElement('div'); cell.className = 'year-cell'; cell.textContent = yr;
                    if (yr === viewYear) cell.classList.add('active');
                    cell.addEventListener('click', function () { viewYear = yr; mode = 'months'; render(); });
                    grid.appendChild(cell);
                }(y));
            }
        }
        function render() { if (mode === 'days') return renderDays(); if (mode === 'months') return renderMonths(); renderYears(); }
        prev.addEventListener('click', function () {
            if (mode === 'days') { viewMonth--; if (viewMonth < 0) { viewMonth = 11; viewYear--; } }
            else if (mode === 'months') { viewYear--; }
            else { yearsStart -= 12; }
            render();
        });
        next.addEventListener('click', function () {
            if (mode === 'days') { viewMonth++; if (viewMonth > 11) { viewMonth = 0; viewYear++; } }
            else if (mode === 'months') { viewYear++; }
            else { yearsStart += 12; }
            render();
        });
        title.addEventListener('click', function () {
            if (mode === 'days') mode = 'months';
            else if (mode === 'months') { mode = 'years'; yearsStart = Math.floor(viewYear / 12) * 12; }
            else mode = 'days';
            render();
        });
        if (inputHidden.value) {
            try { var dt = new Date(inputHidden.value); if (!isNaN(dt)) { viewYear = dt.getFullYear(); viewMonth = dt.getMonth(); inputDisplay.value = pad(dt.getDate()) + '/' + pad(dt.getMonth() + 1) + '/' + dt.getFullYear(); } } catch (e) {}
        }
        container._rerender = render;
        render();
    }

    window.pzCreateCalendar = createCalendar;

    window.pzInitDatePicker = function (wrapper, opts) {
        var hidden  = wrapper.querySelector('input[type="hidden"]');
        var display = wrapper.querySelector('.date-display');
        var toggle  = wrapper.querySelector('.date-toggle');
        var popup   = wrapper.querySelector('.calendar-popup');
        createCalendar(popup, hidden, display, opts || {});
        toggle.addEventListener('click', function () {
            var open = popup.classList.toggle('open');
            popup.setAttribute('aria-hidden', !open);
            if (open) {
                if (popup._rerender) popup._rerender();
                popup.classList.remove('above');
                var rect = popup.getBoundingClientRect();
                if (rect.bottom > window.innerHeight - 16) popup.classList.add('above');
            }
        });
        document.addEventListener('click', function (e) {
            if (!wrapper.contains(e.target)) { popup.classList.remove('open', 'above'); popup.setAttribute('aria-hidden', 'true'); }
        });
    };
}());
</script>
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
              $hdrPic      = (string)(session()->get('profilePicture') ?? '');
              $hdrPicValid = $hdrPic !== '' && file_exists(FCPATH . $hdrPic);
              $hdrInitial  = strtoupper(substr($userName !== '' ? $userName : 'User', 0, 1));
            ?>
            <a href="<?= base_url('profile') ?>" class="user-profile" style="text-decoration:none;color:inherit;" title="My Profile">
              <span class="avatar-initial" id="hdrAvatarWrap" style="<?= $hdrPicValid ? 'padding:0;overflow:hidden;' : '' ?>">
                <?php if ($hdrPicValid): ?>
                  <img src="<?= base_url($hdrPic) ?>?v=<?= filemtime(FCPATH . $hdrPic) ?>"
                       alt=""
                       id="hdrAvatarImg"
                       style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
                       onerror="this.style.display='none';document.getElementById('hdrAvatarFallback').style.display='';document.getElementById('hdrAvatarWrap').style.padding='';">
                  <span id="hdrAvatarFallback" style="display:none"><?= $hdrInitial ?></span>
                <?php else: ?>
                  <?= $hdrInitial ?>
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
  <script src="<?= asset_url('js/bootstrap.bundle.min.js') ?>"></script>

  <!-- ── Firebase JS SDK (real-time listeners) ── -->
  <script src="https://www.gstatic.com/firebasejs/10.12.2/firebase-app-compat.js"></script>
  <script src="https://www.gstatic.com/firebasejs/10.12.2/firebase-auth-compat.js"></script>
  <script src="https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore-compat.js"></script>
  <script>
  (function () {
    var _fbConfig = {
      apiKey:            'AIzaSyCq8YNyOXnaYP7Q-BKI65CuDG2bemwCFaI',
      authDomain:        'nebuhive-primazentra.firebaseapp.com',
      projectId:         'nebuhive-primazentra',
      storageBucket:     'nebuhive-primazentra.firebasestorage.app',
      messagingSenderId: '385215892270',
      appId:             '1:385215892270:web:d50e3773b7688c0aaf88bf',
    };
    var _fbApp  = firebase.initializeApp(_fbConfig);
    var _fbDb   = firebase.firestore(_fbApp);
    // Expose for other page scripts
    window.pzFirebaseDb  = _fbDb;
    window.pzBaseUrl     = '<?= base_url() ?>';
    // Sign in with a PHP-issued custom token (ties browser auth to the PHP session user)
    // so Firestore security rules see the real UID. Falls back to anonymous on failure.
    (function () {
      function _doSignIn(token) {
        var authInst = firebase.auth(_fbApp);
        if (token) {
          authInst.signInWithCustomToken(token).catch(function (err) {
            console.warn('[PZ] Custom token sign-in failed, falling back to anonymous:', err.message);
            authInst.signInAnonymously().catch(function () {});
          });
        } else {
          authInst.signInAnonymously().catch(function () {});
        }
      }
      fetch('<?= base_url('auth/firebase-token') ?>', { credentials: 'same-origin' })
        .then(function (r) { return r.ok ? r.json() : {}; })
        .then(function (d) {
          console.log('[PZ-auth] token fetch ok, has token:', !!d.token);
          _doSignIn(d.token || null);
        })
        .catch(function (e) {
          console.warn('[PZ-auth] token fetch failed:', e.message);
          _doSignIn(null);
        });
    }());
  }());
  </script>

  <!-- Page-specific Firebase scripts (must come after Firebase SDK loads) -->
  <?= $this->renderSection('firebase-scripts') ?>

  <!-- ── Notification bell ── -->
  <script>
  (function () {
    var _base    = '<?= base_url() ?>';
    var _csrf    = '<?= csrf_token() ?>';
    var _uid     = '<?= session()->get('userId') ?? '' ?>';
    var _open    = false;
    var _data    = [];

    // ── Cross-tab badge sync via BroadcastChannel ─────────────────────────
    var _bc = null;
    try { _bc = new BroadcastChannel('pz_badges'); } catch (e) {}

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

    function updateBadge(count, _noBC) {
      if (count > 0) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.style.display = 'flex';
      } else {
        badge.style.display = 'none';
      }
      if (_bc && !_noBC) { try { _bc.postMessage({ type: 'notif', count: count }); } catch (e) {} }
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
      if (_open) { load(); } // always fetch fresh content when opening panel
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

    // Initial panel data load (populates _data for when bell is clicked)
    load();

    // ── Notification badge: real-time via Firestore onSnapshot ───────────
    // Falls back to 10s polling if Firestore permission is denied.
    function startNotifListener() {
      if (!window.pzFirebaseDb || !_uid) {
        setInterval(load, 10000);
        return;
      }
      window.pzFirebaseDb
        .collection('notifications').doc(_uid).collection('items')
        .where('isRead', '==', false)
        .onSnapshot(
          function (snap) { updateBadge(snap.size); },
          function (err)  {
            console.warn('[PZ] Notification listener:', err.message);
            setInterval(load, 10000); // fallback polling
          }
        );
    }
    // Wait for anonymous auth before attaching listener
    firebase.auth().onAuthStateChanged(function (user) {
      if (user && !window._pzNotifListenerStarted) {
        window._pzNotifListenerStarted = true;
        startNotifListener();
      }
    });
    // Fallback: start listener even if auth state never fires within 4 s
    setTimeout(function () {
      if (!window._pzNotifListenerStarted) {
        window._pzNotifListenerStarted = true;
        startNotifListener();
      }
    }, 4000);

    // ── Message unread badge ──────────────────────────────────────────────
    var msgBadge = document.getElementById('pzMsgBadge');
    function updateMsgBadge(n, _noBC) {
      if (n > 0) {
        msgBadge.textContent = n > 99 ? '99+' : n;
        msgBadge.style.display = 'flex';
      } else {
        msgBadge.style.display = 'none';
      }
      if (_bc && !_noBC) { try { _bc.postMessage({ type: 'msg', count: n }); } catch (e) {} }
    }
    function loadMsgCount() {
      fetch(_base + 'messages/unread-count', { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) { updateMsgBadge(d.count || 0); })
        .catch(function () {});
    }
    // Initial load for the badge
    loadMsgCount();

    // ── Message badge: real-time via Firestore onSnapshot ────────────────
    function startMsgListener() {
      if (!window.pzFirebaseDb || !_uid) {
        setInterval(loadMsgCount, 10000);
        return;
      }
      window.pzFirebaseDb
        .collection('chats')
        .where('members', 'array-contains', _uid)
        .onSnapshot(
          function (snap) {
            var total = 0;
            snap.forEach(function (doc) {
              var counts = doc.data().unread_counts || {};
              total += parseInt(counts[_uid] || 0, 10);
            });
            updateMsgBadge(total);
          },
          function (err) {
            console.warn('[PZ] Message listener:', err.message);
            setInterval(loadMsgCount, 10000); // fallback polling
          }
        );
    }
    firebase.auth().onAuthStateChanged(function (user) {
      if (user && !window._pzMsgListenerStarted) {
        window._pzMsgListenerStarted = true;
        startMsgListener();
      }
    });
    setTimeout(function () {
      if (!window._pzMsgListenerStarted) {
        window._pzMsgListenerStarted = true;
        startMsgListener();
      }
    }, 4000);

    // ── Receive badge updates from other tabs (BroadcastChannel) ─────────
    if (_bc) {
      _bc.onmessage = function (e) {
        if (e.data && e.data.type === 'notif') updateBadge(e.data.count, true);
        if (e.data && e.data.type === 'msg')   updateMsgBadge(e.data.count, true);
      };
    }
  }());
  </script>

  <!-- Long-press drag-to-scroll (hold left button 300 ms on any non-interactive area) -->
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

  <!-- ── Global live-change bar ── -->
  <div id="pzLiveBar">
    <span class="pz-live-dot"></span>
    <span id="pzLiveBarMsg">Data has changed</span>
    <button onclick="location.reload()">Refresh Now</button>
    <span class="pz-bar-dismiss" title="Dismiss"
          onclick="document.getElementById('pzLiveBar').classList.remove('show')">×</span>
  </div>

  <!-- ── Page-aware Firestore change listeners ── -->
  <script>
  (function () {
    var _path = window.location.pathname.toLowerCase();

    // Global live-bar function — NO auto-reload; user chooses when
    window.pzShowLiveBar = function (msg) {
      var bar = document.getElementById('pzLiveBar');
      if (!bar || bar.classList.contains('show')) return;
      var msgEl = document.getElementById('pzLiveBarMsg');
      if (msgEl) msgEl.textContent = msg || 'Data has changed — refresh when ready';
      bar.classList.add('show');
    };

    // JS equivalent of PHP rr_badge() class map
    window.pzBadgeClass = function (status) {
      var map = {
        'pending':                    'pz-badge-pending',
        'pending secondary approval': 'pz-badge-pending-secondary',
        'in-transit':                 'pz-badge-in-transit',
        'rejected':                   'pz-badge-rejected',
        'cancelled':                  'pz-badge-rejected',
        'completed':                  'pz-badge-completed',
      };
      return map[(status || '').toLowerCase()] || 'pz-badge-pending';
    };

    // Pages that manage their own Firestore listeners skip the global handler
    var _skipPaths = /\/login|\/register|\/forgot|\/reset|\/verify|\/requests\/?$|\/requests\/[^/]+\/?$|\/projects\/?$|\/staff\/?$|\/resources\/?$/i;
    if (_skipPaths.test(_path)) return;

    // Collection → page message map
    var _rules = [
      { pat: /\/dashboard|\/summary|\/monitor/, col: 'project',           msg: 'Project data has changed — refresh when ready' },
      { pat: /\/dashboard|\/summary|\/monitor/, col: 'resource_requests', msg: 'Transfer status has changed — refresh when ready' },
      { pat: /\/requests\/?$/,                  col: 'resource_requests', inPlace: true },
      { pat: /\/projects/,                      col: 'project',           msg: 'Project data has changed — refresh when ready' },
      { pat: /\/resources/,                     col: 'resource',          msg: 'Resource data has changed — refresh when ready' },
      { pat: /\/resources/,                     col: 'manpower',          msg: 'Resource data has changed — refresh when ready' },
      { pat: /\/manpower/,                      col: 'manpower',          msg: 'Manpower data has changed — refresh when ready' },
      { pat: /\/staff/,                         col: 'users',             msg: 'Staff data has changed — refresh when ready' },
    ];

    var _attached = {};

    function attachListener(db, col, msg, inPlace) {
      if (_attached[col]) return;
      _attached[col] = true;
      var _ready = false;
      db.collection(col).onSnapshot(
        function (snap) {
          if (!_ready) { _ready = true; return; }
          var changes = snap.docChanges();
          if (!changes.length) return;

          // In-place: update request status badges without any page reload
          if (inPlace && col === 'resource_requests') {
            var hasNew = false;
            changes.forEach(function (chg) {
              if (chg.type === 'added') { hasNew = true; return; }
              if (chg.type !== 'modified') return;
              var row = document.querySelector('tr[data-req-id="' + chg.doc.id + '"]');
              if (!row) return;
              var cell = row.querySelector('[data-req-status]');
              if (!cell) return;
              var s = chg.doc.data().status || '';
              cell.innerHTML = '<span class="pz-badge ' + window.pzBadgeClass(s) + '">'
                             + s.toUpperCase() + '</span>';
            });
            if (hasNew) window.pzShowLiveBar('New request received — refresh when ready');
            return;
          }

          window.pzShowLiveBar(msg);
        },
        function (err) { console.warn('[PZ] Listener (' + col + '):', err.message); }
      );
    }

    function setupListeners(db) {
      var seen = {};
      _rules.forEach(function (r) {
        if (!r.pat.test(_path)) return;
        var key = r.col;
        if (seen[key]) return;
        seen[key] = true;
        attachListener(db, r.col, r.msg, r.inPlace);
      });
    }

    firebase.auth().onAuthStateChanged(function (user) {
      if (user && window.pzFirebaseDb && !window._pzPageListeners) {
        window._pzPageListeners = true;
        setupListeners(window.pzFirebaseDb);
      }
    });
  }());
  </script>
</body>
</html>
