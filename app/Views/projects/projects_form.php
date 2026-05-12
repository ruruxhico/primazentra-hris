<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
    <!-- Alert Messages -->
    <?php if (session()->getFlashdata('error')): ?>
        <div class="form-error">
            ✗ <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <header class="projects-header">
        <h1><?= esc($pageTitle ?? 'Project') ?></h1>
        <div class="user-profile">
            <strong><?= esc($userName ?? 'User') ?></strong>
            <small><?= strtoupper($role ?? 'USER') ?></small>
        </div>
    </header>

    <!-- Form Container -->
    <div class="form-container">
        <form method="POST" action="<?= base_url(isset($projectId) ? 'projects/update/' . $projectId : 'projects/create') ?>">
            <?= csrf_field() ?>

            <div class="form-group">
                <label>Project Name *</label>
                <input type="text" name="project_name" value="<?= esc((string)($project['project_name'] ?? '')) ?>" required>
            </div>

            <div class="form-group">
                <label>Type *</label>
                <select name="project_type" required>
                    <option value="">Select Type</option>
                    <option value="Residential" <?= ($project['project_type'] ?? '') === 'Residential' ? 'selected' : '' ?>>Residential</option>
                    <option value="Commercial" <?= ($project['project_type'] ?? '') === 'Commercial' ? 'selected' : '' ?>>Commercial</option>
                    <option value="Industrial" <?= ($project['project_type'] ?? '') === 'Industrial' ? 'selected' : '' ?>>Industrial</option>
                    <option value="Tower" <?= ($project['project_type'] ?? '') === 'Tower' ? 'selected' : '' ?>>Tower</option>
                </select>
            </div>

            <div class="form-group">
                <label>Location *</label>
                <input type="text" name="location" value="<?= esc((string)($project['location'] ?? '')) ?>" required>
            </div>

            <div class="form-group">
                <label>Status *</label>
                <select name="status" required>
                    <option value="">Select Status</option>
                    <option value="Planned" <?= ($project['status'] ?? '') === 'Planned' ? 'selected' : '' ?>>Planned</option>
                    <option value="In Progress" <?= ($project['status'] ?? '') === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="On Hold" <?= ($project['status'] ?? '') === 'On Hold' ? 'selected' : '' ?>>On Hold</option>
                    <option value="Completed" <?= ($project['status'] ?? '') === 'Completed' ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>

            <div class="form-group">
                <label>Project Manager *</label>
                <select name="project_manager_id" required>
                    <option value="">Select Project Manager</option>
                    <?php foreach ($projectManagers ?? [] as $pm): ?>
                        <option value="<?= esc((string)($pm['id'] ?? '')) ?>" 
                                <?= ($project['project_manager_id'] ?? '') === ($pm['id'] ?? '') ? 'selected' : '' ?>>
                            <?= esc((string)($pm['name'] ?? $pm['email'] ?? 'N/A')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Site Engineer *</label>
                <select name="site_engineer_id" required>
                    <option value="">Select Site Engineer</option>
                    <?php foreach ($siteEngineers ?? [] as $se): ?>
                        <option value="<?= esc((string)($se['id'] ?? '')) ?>" 
                                <?= ($project['site_engineer_id'] ?? '') === ($se['id'] ?? '') ? 'selected' : '' ?>>
                            <?= esc((string)($se['name'] ?? $se['email'] ?? 'N/A')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Start Date</label>
                <input type="date" name="start_date" value="<?= esc((string)($project['start_date'] ?? '')) ?>">
            </div>

            <div class="form-group">
                <label>End Date</label>
                <input type="date" name="end_date" value="<?= esc((string)($project['end_date'] ?? '')) ?>">
            </div>

            <div class="form-group">
                <label>Budget</label>
                <input type="number" name="budget" value="<?= (int)($project['budget'] ?? 0) ?>">
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    <?= isset($projectId) ? 'Update Project' : 'Create Project' ?>
                </button>
                <a href="<?= base_url('projects') ?>" class="btn-cancel">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <style>
        input:focus, select:focus {
            outline: none;
            border-color: #7A5C2D;
            box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.1);
        }
    </style>
<?= $this->endSection() ?>
