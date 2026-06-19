<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $pageTitle = isset($pageTitle) && is_string($pageTitle) ? $pageTitle : 'Authentication'; ?>
    <title>PrimaZentra | <?= htmlspecialchars(is_array($pageTitle) ? 'Authentication' : (string) $pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/x-icon" href="<?= asset_url('favicon.ico') ?>?v=3">
    <link rel="shortcut icon" type="image/x-icon" href="<?= asset_url('favicon.ico') ?>?v=3">
    <link rel="stylesheet" href="<?= base_url(is_file(FCPATH . 'css/styles.css') ? 'css/styles.css' : 'public/css/styles.css') ?>?v=<?= filemtime(ROOTPATH . 'public/css/styles.css') ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400&display=swap">
    <?= $this->renderSection('head') ?>
</head>
<body class="auth-page">
    <div class="auth-layout">
        <?= $this->renderSection('content') ?>
    </div>

    <?= $this->renderSection('scripts') ?>
</body>
</html>
