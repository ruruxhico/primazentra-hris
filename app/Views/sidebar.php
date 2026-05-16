<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="logo-placeholder" role="img" aria-label="Logo placeholder">PrimaZentra</div>
            <button class="sidebar-toggle" aria-label="Toggle sidebar">☰</button>
        </div>
    </div>
    <nav>
        <ul class="sidebar-menu top-menu">
            <li><a href="<?= base_url('dashboard') ?>" class="active"><span class="icon">▦</span><span class="label">Dashboard</span></a></li>
            <li><a href="<?= base_url('summary') ?>"><span class="icon">📊</span><span class="label">Summary</span></a></li>
            <li><a href="<?= base_url('monitor') ?>"><span class="icon">🖥️</span><span class="label">Monitor</span></a></li>
        </ul>

        <div class="menu-section">
            <h4>MANAGE</h4>
            <ul class="sidebar-menu">
                <li><a href="<?= base_url('projects') ?>"><span class="icon">📁</span><span class="label">Projects</span></a></li>
                <li><a href="<?= base_url('resources') ?>"><span class="icon">🔧</span><span class="label">Resources</span></a></li>
                <li><a href="<?= base_url('requests') ?>"><span class="icon">❓</span><span class="label">Resource Requests & Transfers</span></a></li>
                <li><a href="<?= base_url('staff') ?>"><span class="icon">👥</span><span class="label">Staff</span></a></li>
            </ul>
        </div>

        <div class="logout-section">
            <form action="<?= base_url('auth/logout') ?>" method="post">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-export">LOGOUT</button>
            </form>
        </div>
    </nav>
</div>
