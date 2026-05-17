<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $project = $project ?? [];
    $tasks = $tasks ?? [];
    $projectId = $projectId ?? null;
    $isEdit = !empty($projectId);
    $projectTitle = $isEdit ? 'Edit Project' : 'Add Project';
    $projectAction = $isEdit ? 'projects/update/' . $projectId : 'projects/create';
?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert-error">
        ✗ <?= session()->getFlashdata('error') ?>
    </div>
<?php endif; ?>

<h2>Projects - Add </h2>

<div class="form-container project-form-container">
    <form method="post" action="<?= base_url($projectAction) ?>" id="projectForm">
        <?= csrf_field() ?>

        <div class="form-section">
            <div class="form-group">
                <label for="project_name">Project Name *</label>
                <input type="text" id="project_name" name="project_name" value="<?= esc((string)($project['project_name'] ?? '')) ?>" required>
            </div>
                
            <div class="form-row">
                <div class="form-group">
                    <label for="project_type">Project Type *</label>
                    <select id="project_type" name="project_type" required>
                        <option value="">Select Type</option>
                        <?php foreach (['Residential', 'Towers', 'Commercial'] as $type): ?>
                            <option value="<?= esc($type) ?>" <?= ($project['project_type'] ?? '') === $type ? 'selected' : '' ?>><?= esc($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        <option value="">Select Status</option>
                        <?php foreach (['Planned', 'On-going', 'Completed', 'Halted'] as $status): ?>
                            <option value="<?= esc($status) ?>" <?= ($project['status'] ?? '') === $status ? 'selected' : '' ?>><?= esc($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="location_address">Address *</label>
                <input type="text" id="location_address" name="location_address" value="<?= esc((string)($project['location_address_value'] ?? $project['location']['address'] ?? '')) ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="project_manager_id">Project Manager *</label>
                    <select id="project_manager_id" name="project_manager_id" required>
                        <option value="">Select Project Manager</option>
                        <?php foreach ($projectManagers ?? [] as $manager): ?>
                            <option value="<?= esc((string)($manager['id'] ?? '')) ?>" <?= (($project['personnel']['project_manager_id'] ?? '') === ($manager['id'] ?? '')) ? 'selected' : '' ?>>
                                <?= esc((string)($manager['name'] ?? $manager['full_name'] ?? $manager['email'] ?? 'N/A')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="site_engineer_id">Site Engineer *</label>
                    <select id="site_engineer_id" name="site_engineer_id" required>
                        <option value="">Select Site Engineer</option>
                        <?php foreach ($siteEngineers ?? [] as $engineer): ?>
                            <option value="<?= esc((string)($engineer['id'] ?? '')) ?>" <?= (($project['personnel']['site_engineer_id'] ?? '') === ($engineer['id'] ?? '')) ? 'selected' : '' ?>>
                                <?= esc((string)($engineer['name'] ?? $engineer['full_name'] ?? $engineer['email'] ?? 'N/A')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- <div class="form-row">
                <div class="form-group">
                    <label for="location_lat">Latitude</label>
                    <input type="text" id="location_lat" name="location_lat" value="<?= esc((string)($project['location_lat_value'] ?? $project['location']['geopoint']['lat'] ?? '')) ?>" placeholder="14.5995">
                </div>

                <div class="form-group">
                    <label for="location_lng">Longitude</label>
                    <input type="text" id="location_lng" name="location_lng" value="<?= esc((string)($project['location_lng_value'] ?? $project['location']['geopoint']['lng'] ?? '')) ?>" placeholder="120.9842">
                </div>
            </div> -->

            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?= esc((string)($project['start_date_value'] ?? $project['start_date'] ?? '')) ?>">
                </div>

                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?= esc((string)($project['end_date_value'] ?? $project['end_date'] ?? '')) ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="budget">Budget *</label>
                <input type="number" id="budget" name="budget" min="0" step="0.01" value="<?= esc((string)($project['budget'] ?? '')) ?>" required>
            </div>
            
        </div>

        <div class="form-section">
            <?php
                // Determine if there are any meaningful tasks to decide checkbox default
                $hasMeaningfulTasks = false;
                foreach ($tasks as $t) {
                    if (!empty(trim((string)($t['task_name'] ?? ''))) || !empty(trim((string)($t['phase_name'] ?? '')))) {
                        $hasMeaningfulTasks = true;
                        break;
                    }
                }
            ?>

            <div class="form-group add-tasks-toggle">
                <label class="checkbox-label">
                    <input type="checkbox" id="add_tasks_now" name="add_tasks_now" <?= $hasMeaningfulTasks ? 'checked' : '' ?> />
                    <span class="checkbox-custom"></span>
                    Add phases/tasks now
                </label>
            </div>

            <input type="hidden" id="overall_progress" name="overall_progress" value="<?= esc((string)($project['overall_progress'] ?? $project['completion_percent'] ?? 0)) ?>">

            <div class="section-title-row">
                <h2>Tasks</h2>
                <button type="button" class="btn btn-add" id="addTaskBtn">+ Add Task</button>
            </div>

            <div class="tasks-table-wrap" id="tasksSection">
                <table class="tasks-table" id="tasksTable">
                    <thead>
                        <tr>
                            <th>Task Name</th>
                            <th>Phase</th>
                            <th>Weight %</th>
                            <th>Completion %</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $index => $task): ?>
                            <tr class="task-row">
                                <td>
                                    <input type="hidden" name="tasks[<?= $index ?>][id]" value="<?= esc((string)($task['id'] ?? '')) ?>">
                                    <input type="text" name="tasks[<?= $index ?>][task_name]" value="<?= esc((string)($task['task_name'] ?? '')) ?>" placeholder="Task name">
                                </td>
                                <td><input type="text" name="tasks[<?= $index ?>][phase_name]" value="<?= esc((string)($task['phase_name'] ?? '')) ?>" placeholder="Foundation"></td>
                                <td><input type="number" name="tasks[<?= $index ?>][weight_percent]" value="<?= esc((string)($task['weight_percent'] ?? '')) ?>" min="0" max="100" step="0.01" class="task-weight"></td>
                                <td><input type="number" name="tasks[<?= $index ?>][completion_percent]" value="<?= esc((string)($task['completion_percent'] ?? '')) ?>" min="0" max="100" step="0.01" class="task-completion"></td>
                                <td>
                                    <select name="tasks[<?= $index ?>][status]">
                                        <?php foreach (['Pending', 'In Progress', 'Done'] as $taskStatus): ?>
                                            <option value="<?= esc($taskStatus) ?>" <?= ($task['status'] ?? 'Pending') === $taskStatus ? 'selected' : '' ?>><?= esc($taskStatus) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><button type="button" class="btn btn-danger btn-sm remove-task-btn">Remove</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <?= $isEdit ? 'Update Project' : 'Create Project' ?>
            </button>
            <a href="<?= base_url('projects') ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<template id="taskRowTemplate">
    <tr class="task-row">
        <td>
            <input type="hidden" name="__INDEX__[id]" value="">
            <input type="text" name="__INDEX__[task_name]" placeholder="Task name">
        </td>
        <td><input type="text" name="__INDEX__[phase_name]" placeholder="Foundation"></td>
        <td><input type="number" name="__INDEX__[weight_percent]" value="0" min="0" max="100" step="0.01" class="task-weight"></td>
        <td><input type="number" name="__INDEX__[completion_percent]" value="0" min="0" max="100" step="0.01" class="task-completion"></td>
        <td>
            <select name="__INDEX__[status]">
                <option value="Pending">Pending</option>
                <option value="In Progress">In Progress</option>
                <option value="Done">Done</option>
            </select>
        </td>
        <td><button type="button" class="btn btn-danger btn-sm remove-task-btn">Remove</button></td>
    </tr>
</template>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tasksTableBody = document.querySelector('#tasksTable tbody');
    const addTaskBtn = document.getElementById('addTaskBtn');
    const overallProgress = document.getElementById('overall_progress');
    const addTasksCheckbox = document.getElementById('add_tasks_now');
    const tasksSection = document.getElementById('tasksSection');
    const template = document.getElementById('taskRowTemplate');

    function setTasksVisible(visible) {
        if (!tasksSection) return;
        tasksSection.style.display = visible ? '' : 'none';
        // enable/disable inputs so they aren't submitted when hidden
        tasksTableBody.querySelectorAll('input, select').forEach((f) => {
            f.disabled = !visible;
        });
    }

    function reindexTasks() {
        const rows = tasksTableBody.querySelectorAll('.task-row');
        rows.forEach((row, index) => {
            row.querySelectorAll('input, select').forEach((field) => {
                field.name = field.name.replace(/tasks\[\d+\]/, 'tasks[' + index + ']');
            });
        });
    }

    function recalculateOverallProgress() {
        let totalWeight = 0;
        let weightedCompletion = 0;

        tasksTableBody.querySelectorAll('.task-row').forEach((row) => {
            const weight = parseFloat(row.querySelector('.task-weight')?.value || '0') || 0;
            const completion = parseFloat(row.querySelector('.task-completion')?.value || '0') || 0;
            totalWeight += weight;
            weightedCompletion += (weight * completion);
        });

        overallProgress.value = totalWeight > 0 ? (weightedCompletion / totalWeight).toFixed(2) : '0';
    }

    function attachRowEvents(row) {
        row.querySelectorAll('.task-weight, .task-completion').forEach((input) => {
            input.addEventListener('input', recalculateOverallProgress);
        });

        row.querySelector('.remove-task-btn')?.addEventListener('click', function () {
            if (tasksTableBody.querySelectorAll('.task-row').length > 1) {
                row.remove();
                reindexTasks();
                recalculateOverallProgress();
            }
        });
    }

    tasksTableBody.querySelectorAll('.task-row').forEach(attachRowEvents);

    // initialize visibility based on checkbox
    if (addTasksCheckbox) {
        setTasksVisible(!!addTasksCheckbox.checked);
        addTasksCheckbox.addEventListener('change', function () {
            setTasksVisible(!!this.checked);
        });
    }

    addTaskBtn?.addEventListener('click', function () {
        // If tasks section is hidden, reveal it and check the box
        if (addTasksCheckbox && !addTasksCheckbox.checked) {
            addTasksCheckbox.checked = true;
            setTasksVisible(true);
        }

        const index = tasksTableBody.querySelectorAll('.task-row').length;
        const html = template.innerHTML.replace(/__INDEX__/g, 'tasks[' + index + ']');
        tasksTableBody.insertAdjacentHTML('beforeend', html);
        const row = tasksTableBody.querySelectorAll('.task-row')[index];
        attachRowEvents(row);
        recalculateOverallProgress();
    });

    recalculateOverallProgress();
});
</script>

<?= $this->endSection() ?>