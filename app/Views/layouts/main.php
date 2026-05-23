<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $pageTitle = isset($pageTitle) && is_string($pageTitle) ? $pageTitle : 'Dashboard'; ?>
    <?php $userName = isset($userName) && is_string($userName) ? $userName : 'User'; ?>
    <title>PrimaZentra | <?= htmlspecialchars(is_array($pageTitle) ? 'Dashboard' : (string) $pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= base_url('public/css/styles.css') ?>?v=<?= filemtime(FCPATH . 'public/css/styles.css') ?>">
    <link rel="stylesheet" href="https://googleapis.com">
</head>
<body>
    <?= $this->include('sidebar') ?>

    <div class="main-content">
        <header class="header">
            <div></div>
            <div class="header-right">
                <span class="notification-icon">💬</span>
                <span class="notification-icon">🔔</span>
                <div class="user-profile">
                    <span style="width: 40px; height: 40px; border-radius: 50%; background-color: #D99600; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                        <?= strtoupper(substr($userName, 0, 1)) ?>
                    </span>
                    <div>
                        <strong><?= htmlspecialchars(is_array($userName) ? 'User' : (string) $userName, ENT_QUOTES, 'UTF-8') ?></strong><br>
                        <small><?= strtoupper($role ?? 'USER') ?></small>
                    </div>
                </div>
            </div>
        </header>

        <div class="page-content">
            <?= $this->renderSection('content') ?>
        </div>
    </div>
</body>
</html>
