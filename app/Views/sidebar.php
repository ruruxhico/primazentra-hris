<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <?php $logo = base_url('public/assets/primazentra_colored.png'); ?>
            <a href="<?= base_url('dashboard') ?>" class="logo-link">
                <img src="<?= $logo ?>" alt="PrimaZentra logo" class="logo-img" onerror="this.style.display='none'">
            </a>
            <button id="sidebarToggle" class="sidebar-toggle" aria-label="Toggle sidebar">☰</button>
        </div>
    </div>
    <nav>
        <ul class="sidebar-menu top-menu">
            <li>
                <a href="<?= base_url('dashboard') ?>" class="active"><span class="icon">▦</span><span class="label">Dashboard</span></a>
                <ul class="submenu">
                    <li><a href="<?= base_url('summary') ?>">Summary</a></li>
                    <li><a href="<?= base_url('monitor') ?>">Monitor</a></li>
                </ul>
            </li>
            <li><a href="<?= base_url('projects') ?>"><span class="icon">📁</span><span class="label">Projects</span></a></li>
            <li>
                <a href="<?= base_url('resources') ?>"><span class="icon">🔧</span><span class="label">Resources</span></a>
                <ul class="submenu">
                    <li><a href="<?= base_url('requests') ?>">Resource Requests & Transfers</a></li>
                </ul>
            </li>
            <li><a href="<?= base_url('staff') ?>"><span class="icon">👥</span><span class="label">Staff</span></a></li>
        </ul>

        <div class="logout-section">
            <form action="<?= base_url('auth/logout') ?>" method="post">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-export">LOGOUT</button>
            </form>
        </div>
    </nav>
</div>

<script>
// Sidebar toggle for responsive behavior
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('sidebarToggle');
    var sidebar = document.querySelector('.sidebar');
    var stored = localStorage.getItem('pz_sidebar_collapsed');
    if (stored === 'true') sidebar.classList.add('collapsed');

    btn && btn.addEventListener('click', function () {
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('pz_sidebar_collapsed', sidebar.classList.contains('collapsed'));
    });
});
</script>
