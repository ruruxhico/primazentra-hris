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
    <link rel="stylesheet" href="<?= asset_url('css/material-symbols.css') ?>">
    <?= $this->renderSection('head') ?>
</head>
<body class="auth-page">
<!-- ── PZ Form Auto-save ── -->
<script>
(function () {
    var _P = 'pz_as_';
    function _k(form)  { return _P + (form.id || 'form') + location.pathname; }
    function _xk(form) { return _k(form) + '_x'; }

    function doSave(form) {
        var data = {};
        form.querySelectorAll('input,select,textarea').forEach(function (el) {
            var n = el.name;
            if (!n || el.type === 'file' || el.type === 'submit' || el.type === 'button' || el.type === 'password') return;
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
                form.querySelectorAll('input,select,textarea').forEach(function (el) {
                    var n = el.name;
                    if (!n || el.type === 'file' || el.type === 'submit' || el.type === 'button' || el.type === 'password') return;
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
            setTimeout(function () { doRestore(form); }, 0);
        });
    });
}());
</script>
    <div class="auth-layout">
        <?= $this->renderSection('content') ?>
    </div>

    <?= $this->renderSection('scripts') ?>
</body>
</html>
