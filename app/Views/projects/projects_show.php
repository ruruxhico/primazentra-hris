<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
    <header class="projects-header">
        <h1>Project Detail</h1>
    </header>

    <div class="detail-container">
        <dl>
            <dt>Project Name</dt>
            <dd><?= esc(view_text($project['project_name'] ?? '')) ?></dd>

            <dt>Type</dt>
            <dd><?= esc(view_text($project['project_type'] ?? '')) ?></dd>

            <dt>Status</dt>
            <dd><?= esc(view_text($project['status'] ?? '')) ?></dd>

            <dt>Location</dt>
            <dd><?= esc(view_text($project['location_label'] ?? ($project['location']['address'] ?? ''))) ?></dd>

            <dt>Start Date</dt>
            <dd><?= esc(view_text($project['start_date_value'] ?? '')) ?></dd>

            <dt>End Date</dt>
            <dd><?= esc(view_text($project['end_date_value'] ?? '')) ?></dd>

            <dt>Budget</dt>
            <dd><?= esc(view_text($project['budget'] ?? '')) ?></dd>

            <dt>Overall Progress</dt>
            <dd><?= esc(view_text($project['progress_label'] ?? ($project['overall_progress'] ?? 0))) ?>%</dd>

            <dt>Project Manager</dt>
            <dd><?= esc(view_text($project['project_manager_name'] ?? 'N/A', 'N/A')) ?></dd>

            <dt>Site Engineer</dt>
            <dd><?= esc(view_text($project['site_engineer_name'] ?? 'N/A', 'N/A')) ?></dd>
        </dl>

        <div class="form-actions">
            <a href="<?= base_url('projects/update/' . ($project['id'] ?? '')) ?>" class="btn-add">Edit</a>
            <a href="<?= base_url('projects') ?>" class="btn-secondary">Back</a>
        </div>
    </div>

<?= $this->endSection() ?>
