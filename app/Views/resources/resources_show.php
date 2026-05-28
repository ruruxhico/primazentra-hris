<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $category = (string)($resource['category'] ?? '');
    $isAsset = in_array($category, ['Equipment', 'Tools'], true);
    $isManpower = $category === 'Manpower';
?>

<header class="resource-header">
    <h1>Resource Detail</h1>
</header>

<div class="detail-container">
    <dl>
        <dt>Name</dt>
        <dd><?= esc((string)($resource['name'] ?? '')) ?></dd>

        <dt>Category</dt>
        <dd><?= esc($category !== '' ? $category : 'N/A') ?></dd>

        <dt>Status</dt>
        <dd><?= esc((string)($resource['status'] ?? '')) ?></dd>

        <dt>Created At</dt>
        <dd><?= esc((string)($resource['created_at_value'] ?? 'N/A')) ?></dd>

        <dt>Updated At</dt>
        <dd><?= esc((string)($resource['updated_at_value'] ?? 'N/A')) ?></dd>

        <dt>Next Availability</dt>
        <dd><?= esc((string)($resource['next_availability_value'] ?? $resource['next_availability'] ?? '')) ?></dd>

        <dt>Location Address</dt>
        <dd><?= esc((string)($resource['location']['address'] ?? '')) ?></dd>

        <dt>Location Type</dt>
        <dd><?= esc((string)($resource['location']['type'] ?? '')) ?></dd>

        <?php if ($isManpower): ?>
            <dt>Manpower ID</dt>
            <dd><?= esc((string)($resource['manpower_details']['manpower_id'] ?? '')) ?></dd>

            <dt>Manpower Role</dt>
            <dd><?= esc((string)($resource['manpower_details']['role'] ?? '')) ?></dd>

            <dt>Specialization</dt>
            <dd><?= esc((string)($resource['manpower_details']['specialization'] ?? '')) ?></dd>

            <dt>Contact Number</dt>
            <dd><?= esc((string)($resource['manpower_details']['contact_number'] ?? '')) ?></dd>

            <dt>Assigned Project ID</dt>
            <dd><?= esc((string)($resource['manpower_details']['assigned_project_id'] ?? '')) ?></dd>
        <?php endif; ?>

        <?php if ($isAsset): ?>
            <dt>Model Number</dt>
            <dd><?= esc((string)($resource['asset_details']['model_number'] ?? '')) ?></dd>

            <dt>Total Quantity</dt>
            <dd><?= esc((string)($resource['asset_details']['total_quantity'] ?? '')) ?></dd>

            <dt>Available Quantity</dt>
            <dd><?= esc((string)($resource['asset_details']['available_quantity'] ?? '')) ?></dd>

            <dt>Condition Summary</dt>
            <dd>
                <?php if (!empty($resource['asset_details']['condition_summary']) && is_array($resource['asset_details']['condition_summary'])): ?>
                    <?php foreach ($resource['asset_details']['condition_summary'] as $condition => $count): ?>
                        <span class="tag-badge tag-<?= esc(strtolower((string)$condition)) ?>"><?= esc((string)$condition) ?>: <?= esc((string)$count) ?></span>
                    <?php endforeach; ?>
                <?php else: ?>
                    N/A
                <?php endif; ?>
            </dd>
        <?php endif; ?>
    </dl>

    <div class="form-actions">
        <a href="<?= base_url('resources/edit/' . ($resource['id'] ?? '')) ?>" class="btn-add">Edit</a>
        <a href="<?= base_url('resources') ?>" class="btn-secondary">Back</a>
        <?php if ($isAsset): ?>
            <a href="<?= base_url('resources/viewItems/' . ($resource['id'] ?? '')) ?>" class="btn btn-success">View Items</a>
        <?php endif; ?>
    </div>

    <?php if ($isAsset && !empty($items) && is_array($items)): ?>
        <div class="mt-2rem">
            <h2 class="items-heading">Items</h2>
            <div class="table-container">
                <table class="resource-table">
                    <thead>
                        <tr>
                            <th>Unit ID</th>
                            <th>Serial Number</th>
                            <th>Condition</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= esc((string)($item['unit_id'] ?? '')) ?></td>
                                <td><?= esc((string)($item['serial_number'] ?? '')) ?></td>
                                <td><?= esc((string)($item['condition'] ?? '')) ?></td>
                                <td><?= esc((string)($item['status'] ?? '')) ?></td>
                                <td><?= esc((string)($item['assigned_to'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
