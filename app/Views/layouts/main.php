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
    <link rel="stylesheet" href="<?= base_url('public/css/styles.css') ?>?v=<?= filemtime(FCPATH . 'public/css/styles.css') ?>">
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
</body>
</html>
