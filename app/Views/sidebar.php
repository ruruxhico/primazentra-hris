<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-header">
        <h1>PrimaZentra</h1>
    </div>
    <nav>
        <ul>
            <li><a href="<?= base_url('dashboard') ?>" class="active">Dashboard</a></li>
                <ul class="submenu">
                    <li><a href="#summary">Summary</a></li>
                    <li><a href="#monitor">Monitor</a></li>                    
                </ul>
            <li><a href="<?= base_url('projects') ?>">Projects</a></li>
            <li><a href="<?= base_url('resources') ?>">Resources</a></li>
                <ul class="submenu">
                    <li><a href="#summary">Resource Requests & Transfers</a></li>
                </ul>
            <li><a href="<?= base_url('staff') ?>">Staff Management</a></li>
            <li class="logout-section">
                <form action="<?= base_url('auth/logout') ?>" method="post">
                    <?= csrf_field() ?>
                    <button type="submit">Logout</button>
                </form>
            </li>
        </ul>
    </nav>
</div>
