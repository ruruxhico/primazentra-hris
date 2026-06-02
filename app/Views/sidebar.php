<!-- SIDEBAR -->
<?php
    $currentPath = trim((string) uri_string(), '/');
    $isDashboard = $currentPath === '' || $currentPath === 'dashboard' || str_starts_with($currentPath, 'dashboard/') || $currentPath === 'summary' || $currentPath === 'monitor';
    $isSummary   = $currentPath === 'summary'  || $currentPath === 'dashboard' || $currentPath === '';
    $isMonitor   = $currentPath === 'monitor';
    $isProjects = $currentPath === 'projects' || str_starts_with($currentPath, 'projects/');
    $isResources = $currentPath === 'resources' || $currentPath === 'requests' || str_starts_with($currentPath, 'resources/') || str_starts_with($currentPath, 'requests/');
    $isStaff = $currentPath === 'staff' || str_starts_with($currentPath, 'staff/');
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <?php $logo = base_url('assets/primazentra_colored.png'); ?>
            <a href="<?= base_url('dashboard') ?>" class="logo-link">
                <img src="<?= $logo ?>" alt="PrimaZentra logo" class="logo-img" onerror="this.style.display='none'">
            </a>
            <button id="sidebarToggle" class="sidebar-toggle" aria-label="Toggle sidebar">☰</button>
        </div>
    </div>
    <nav>
        <ul class="sidebar-menu top-menu">
            <li>
          <a href="<?= base_url('dashboard') ?>" class="<?= $isDashboard ? 'active' : '' ?>"><span class="icon material-symbols-outlined" aria-hidden="true">grid_view</span><span class="label">Dashboard</span></a>
                <ul class="submenu">
            <li><a href="<?= base_url('dashboard') ?>#summary" class="<?= $isSummary ? 'active' : '' ?>"><span class="icon material-symbols-outlined" aria-hidden="true">stacked_line_chart</span><span class="label">Summary</span></a></li>
            <li><a href="<?= base_url('dashboard') ?>#monitor" class="<?= $isMonitor ? 'active' : '' ?>"><span class="icon material-symbols-outlined" aria-hidden="true">videocam</span><span class="label">Monitor</span></a></li>
                </ul>
            </li>
        <li><a href="<?= base_url('projects') ?>" class="<?= $isProjects ? 'active' : '' ?>"><span class="icon material-symbols-outlined" aria-hidden="true">edit_calendar</span><span class="label">Projects</span></a></li>
            <li>
          <a href="<?= base_url('resources') ?>" class="<?= $isResources ? 'active' : '' ?>"><span class="icon material-symbols-outlined" aria-hidden="true">inventory_2</span><span class="label">Resources</span></a>
                <ul class="submenu">
            <li><a href="<?= base_url('requests') ?>"><span class="icon material-symbols-outlined" aria-hidden="true">contact_support</span><span class="label">Resource Requests & Transfers</span></a></li>
                </ul>
            </li>
        <li><a href="<?= base_url('staff') ?>" class="<?= $isStaff ? 'active' : '' ?>"><span class="icon material-symbols-outlined" aria-hidden="true">person</span><span class="label">Staff</span></a></li>
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
document.addEventListener('DOMContentLoaded', function () {
  var sidebar = document.querySelector('.sidebar');
  var overlay = document.getElementById('sidebarOverlay');

  // Restore desktop collapsed state
  if (localStorage.getItem('pz_sidebar_collapsed') === 'true') {
    sidebar.classList.add('toggled');
    document.body.classList.add('sidebar-toggled');
  }

  function toggleSidebar() {
    sidebar.classList.toggle('toggled');
    document.body.classList.toggle('sidebar-toggled');
    localStorage.setItem('pz_sidebar_collapsed',
      sidebar.classList.contains('toggled'));
    if (overlay) overlay.classList.toggle('show',
      window.innerWidth < 768 && !sidebar.classList.contains('toggled'));
  }

  ['sidebarToggle','sidebarToggleTop'].forEach(function(id) {
    var btn = document.getElementById(id);
    if (btn) btn.addEventListener('click', toggleSidebar);
  });

  if (overlay) overlay.addEventListener('click', function() {
    sidebar.classList.add('toggled');
    document.body.classList.add('sidebar-toggled');
    overlay.classList.remove('show');
  });

  window.addEventListener('resize', function() {
    if (window.innerWidth >= 768 && overlay) overlay.classList.remove('show');
    if (window.innerWidth < 480 && !sidebar.classList.contains('toggled')) {
      sidebar.classList.add('toggled');
      document.body.classList.add('sidebar-toggled');
    }
  });
});
</script>
