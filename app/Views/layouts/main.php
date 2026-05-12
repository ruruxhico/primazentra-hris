<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrimaZentra | <?= esc($pageTitle ?? 'Dashboard') ?></title>
    <link rel="stylesheet" href="<?= base_url('public/css/styles.css') ?>">
</head>
<body>
    <?= $this->include('sidebar') ?>
    
    <div class="main-content">
        <?= $this->renderSection('content') ?>
    </div>
</body>
</html>
