<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<a href="<?= base_url('projects') ?>" class="pz-back-link">
    <span class="material-symbols-outlined">arrow_back</span> Projects
</a>

<div class="pz-page-header">
    <h1><?= esc(view_text($project['project_name'] ?? 'Project Detail')) ?></h1>
    <p><?= esc(view_text($project['project_type'] ?? '')) ?></p>
</div>

<div class="pz-card">
    <p class="pz-card-section">Overview</p>
    <div class="pz-info-grid">
        <span class="pz-info-label">Status</span>
        <span class="pz-info-value">
            <?php
                $st = strtolower($project['status'] ?? '');
                $badgeMap = ['completed'=>'pz-badge-completed','done'=>'pz-badge-done','halted'=>'pz-badge-halted','planned'=>'pz-badge-pending','on-going'=>'pz-badge-ongoing','ongoing'=>'pz-badge-ongoing'];
                $bc = $badgeMap[$st] ?? 'pz-badge-active';
            ?>
            <span class="pz-badge <?= $bc ?>"><?= esc(strtoupper($project['status'] ?? '—')) ?></span>
        </span>

        <span class="pz-info-label">Progress</span>
        <span class="pz-info-value">
            <span class="pz-badge pz-badge-progress"><?= esc(view_text($project['progress_label'] ?? ($project['overall_progress'] ?? 0))) ?>%</span>
        </span>

        <div class="pz-info-divider"></div>

        <span class="pz-info-label">Location</span>
        <span class="pz-info-value"><?= esc(view_text($project['location_label'] ?? ($project['location']['address'] ?? '—'))) ?></span>

        <span class="pz-info-label">Start Date</span>
        <span class="pz-info-value"><?= esc(view_text($project['start_date_value'] ?? '—')) ?></span>

        <span class="pz-info-label">End Date</span>
        <span class="pz-info-value"><?= esc(view_text($project['end_date_value'] ?? '—')) ?></span>

        <span class="pz-info-label">Budget</span>
        <span class="pz-info-value"><?= esc(view_text($project['budget'] ?? '—')) ?></span>

        <div class="pz-info-divider"></div>

        <span class="pz-info-label">Project Manager</span>
        <span class="pz-info-value"><?= esc(view_text($project['project_manager_name'] ?? 'N/A', 'N/A')) ?></span>

        <span class="pz-info-label">Site Engineer</span>
        <span class="pz-info-value"><?= esc(view_text($project['site_engineer_name'] ?? 'N/A', 'N/A')) ?></span>
    </div>

    <div class="pz-card-actions">
        <a href="<?= base_url('projects/' . ($project['id'] ?? '') . '/resources') ?>" class="pz-btn pz-btn-outline">
            <span class="material-symbols-outlined">inventory_2</span> Resources
        </a>
        <a href="<?= base_url('projects/update/' . ($project['id'] ?? '')) ?>" class="pz-btn pz-btn-primary">
            <span class="material-symbols-outlined">edit</span> Edit
        </a>
    </div>
</div>

<?= $this->endSection() ?>
