<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $pageTitle = isset($pageTitle) && is_string($pageTitle) ? $pageTitle : 'Dashboard'; ?>
    <?php $userName = isset($userName) && is_string($userName) ? $userName : 'User'; ?>
    <?php $roleLabel = isset($role) && is_scalar($role) ? (string) $role : 'USER'; ?>
    <title>PrimaZentra | <?= htmlspecialchars(is_array($pageTitle) ? 'Dashboard' : (string) $pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= base_url(is_file(FCPATH . 'css/styles.css') ? 'css/styles.css' : 'public/css/styles.css') ?>?v=<?= filemtime(ROOTPATH . 'public/css/styles.css') ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400&display=swap">
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
          <div></div>
          <div class="header-right">
            <span class="notification-icon">💬</span>
            <span class="notification-icon">🔔</span>
            <div class="user-profile">
              <span class="avatar-initial">
                <?= strtoupper(substr($userName !== '' ? $userName : 'User', 0, 1)) ?>
              </span>
              <div>
                <strong><?= htmlspecialchars(is_array($userName) ? 'User' : (string) $userName, ENT_QUOTES, 'UTF-8') ?></strong><br>
                <small><?= strtoupper($roleLabel) ?></small>
              </div>
            </div>
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
</body>
</html>
