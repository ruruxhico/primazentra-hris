<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
    <!-- Alert Messages -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert-success">
            ✓ <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert-error">
            ✗ <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <header class="projects-header">
        <h1>Projects</h1>
        <div class="user-profile">
            <strong><?= esc($userName ?? 'User') ?></strong>
            <small><?= strtoupper($role ?? 'USER') ?></small>
        </div>
    </header>

    <!-- Search and Filter Section -->
    <div class="projects-toolbar">
        <input type="text" id="searchProjects" placeholder="🔍 Search projects..." class="search-input">
        
        <button class="btn-toolbar btn-filter">
            ▼ FILTER
        </button>
        
        <a href="<?= base_url('projects/create') ?>" class="btn-toolbar btn-add">
            + ADD
        </a>
        
        <button class="btn-toolbar btn-import">
            ⬇ IMPORT
        </button>
        
        <button class="btn-toolbar btn-export">
            ⬆ EXPORT
        </button>
    </div>

    <!-- Projects Table -->
    <div class="projects-table-container">
        <table class="projects-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>PROJECT NAME</th>
                    <th>TYPE</th>
                    <th>LOCATION</th>
                    <th style="text-align: center;">PROGRESS</th>
                    <th>PROJECT MANAGER</th>
                    <th>SITE ENGINEER</th>
                    <th style="text-align: center;">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($projects)): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            No projects found. <a href="<?= base_url('projects/create') ?>">Create one now</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($projects as $index => $project): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <strong><?= esc((string)($project['project_name'] ?? 'N/A')) ?></strong>
                            </td>
                            <td><?= esc((string)($project['project_type'] ?? 'N/A')) ?></td>
                            <td><?= esc((string)($project['location'] ?? 'N/A')) ?></td>
                            <td style="text-align: center;">
                                <span class="status-badge">
                                    <?= esc((string)($project['status'] ?? 'N/A')) ?>
                                </span>
                            </td>
                            <td><?= esc((string)($project['project_manager_id'] ?? 'N/A')) ?></td>
                            <td><?= esc((string)($project['site_engineer_id'] ?? 'N/A')) ?></td>
                            <td style="text-align: center;">
                                <button class="action-button" title="View" onclick="alert('View functionality coming soon')">👁️</button>
                                <a href="<?= base_url('projects/update/' . $project['id']) ?>" class="action-button" title="Edit">✏️</a>
                                <a href="<?= base_url('projects/delete/' . $project['id']) ?>" class="action-button" title="Delete" onclick="return confirm('Are you sure?');">🗑️</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination-container">
        <button class="pagination-nav">← Previous</button>
        
        <button class="pagination-button active">1</button>
        <button class="pagination-button">2</button>
        <button class="pagination-button">3</button>
        <button class="pagination-button">4</button>
        <button class="pagination-button">5</button>
        
        <button class="pagination-nav">Next →</button>
    </div>

<?= $this->endSection() ?>
