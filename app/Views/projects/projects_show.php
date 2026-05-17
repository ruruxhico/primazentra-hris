<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
    <header class="projects-header">
        <h1>Project Detail</h1>
    </header>

    <div class="detail-container">
        <dl>
            <dt>Project Name</dt>
            <dd><?= esc((string)($project['project_name'] ?? '')) ?></dd>

            <dt>Type</dt>
            <dd><?= esc((string)($project['project_type'] ?? '')) ?></dd>

            <dt>Status</dt>
            <dd><?= esc((string)($project['status'] ?? '')) ?></dd>

            <dt>Location</dt>
            <dd><?= esc((string)($project['location_label'] ?? ($project['location']['address'] ?? ''))) ?></dd>

            <dt>Start Date</dt>
            <dd><?= esc((string)($project['start_date_value'] ?? '')) ?></dd>

            <dt>End Date</dt>
            <dd><?= esc((string)($project['end_date_value'] ?? '')) ?></dd>

            <dt>Budget</dt>
            <dd><?= esc((string)($project['budget'] ?? '')) ?></dd>

            <dt>Overall Progress</dt>
            <dd><?= esc((string)($project['progress_label'] ?? $project['overall_progress'] ?? 0)) ?>%</dd>

            <dt>Project Manager</dt>
            <dd><?= esc((string)($project['project_manager_name'] ?? 'N/A')) ?></dd>

            <dt>Site Engineer</dt>
            <dd><?= esc((string)($project['site_engineer_name'] ?? 'N/A')) ?></dd>
        </dl>

        <div class="form-actions">
            <a href="<?= base_url('projects/update/' . ($project['id'] ?? '')) ?>" class="btn-add">Edit</a>
            <a href="<?= base_url('projects') ?>" class="btn-secondary">Back</a>
        </div>
    </div>

<?= $this->endSection() ?>
