<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $category   = (string)($resource['category'] ?? '');
    $isAsset    = in_array($category, ['Equipment', 'Tools'], true);
    $isManpower = $category === 'Manpower';

    $st  = strtolower($resource['status'] ?? '');
    $sbc = str_contains($st,'avail') ? 'pz-badge-available' : (str_contains($st,'deploy') ? 'pz-badge-deployed' : (str_contains($st,'maintenance') ? 'pz-badge-maintenance' : 'pz-badge-unavailable'));
?>

<a href="<?= base_url('resources') ?>" class="pz-back-link">
    <span class="material-symbols-outlined">arrow_back</span> Resources
</a>

<div class="pz-page-header">
    <h1><?= esc((string)($resource['name'] ?? 'Resource Detail')) ?></h1>
    <p><?= esc($category) ?></p>
</div>

<!-- Main info card -->
<div class="pz-card">
    <p class="pz-card-section">Details</p>
    <div class="pz-info-grid">
        <span class="pz-info-label">Status</span>
        <span class="pz-info-value"><span class="pz-badge <?= $sbc ?>"><?= esc((string)($resource['status'] ?? '—')) ?></span></span>

        <span class="pz-info-label">Category</span>
        <span class="pz-info-value"><?= esc($category ?: 'N/A') ?></span>

        <span class="pz-info-label">Next Availability</span>
        <span class="pz-info-value"><?= esc((string)($resource['next_availability_value'] ?? $resource['next_availability'] ?? '—')) ?></span>

        <span class="pz-info-label">Location</span>
        <span class="pz-info-value"><?= esc((string)($resource['location']['address'] ?? '—')) ?></span>

        <span class="pz-info-label">Location Type</span>
        <span class="pz-info-value"><?= esc((string)($resource['location']['type'] ?? '—')) ?></span>

        <?php if ($isManpower): ?>
        <div class="pz-info-divider"></div>
        <span class="pz-info-label">Manpower ID</span>
        <span class="pz-info-value"><?= esc((string)($resource['manpower_details']['manpower_id'] ?? '—')) ?></span>

        <span class="pz-info-label">Role</span>
        <span class="pz-info-value"><?= esc((string)($resource['manpower_details']['role'] ?? '—')) ?></span>

        <span class="pz-info-label">Specialization</span>
        <span class="pz-info-value"><?= esc((string)($resource['manpower_details']['specialization'] ?? '—')) ?></span>

        <span class="pz-info-label">Contact</span>
        <span class="pz-info-value"><?= esc((string)($resource['manpower_details']['contact_number'] ?? '—')) ?></span>

        <span class="pz-info-label">Assigned Project</span>
        <span class="pz-info-value"><?= esc((string)($resource['manpower_details']['assigned_project_id'] ?? '—')) ?></span>
        <?php endif; ?>

        <?php if ($isAsset): ?>
        <div class="pz-info-divider"></div>
        <span class="pz-info-label">Model Number</span>
        <span class="pz-info-value"><?= esc((string)($resource['asset_details']['model_number'] ?? '—')) ?></span>

        <span class="pz-info-label">Total Quantity</span>
        <span class="pz-info-value"><?= esc((string)($resource['asset_details']['total_quantity'] ?? '—')) ?></span>

        <span class="pz-info-label">Available</span>
        <span class="pz-info-value"><?= esc((string)($resource['asset_details']['available_quantity'] ?? '—')) ?></span>
        <?php endif; ?>
    </div>

    <div class="pz-card-actions">
        <a href="<?= base_url('resources/edit/' . ($resource['id'] ?? '')) ?>" class="pz-btn pz-btn-primary">
            <span class="material-symbols-outlined">edit</span> Edit
        </a>
        <?php if ($isAsset): ?>
        <a href="<?= base_url('resources/viewItems/' . ($resource['id'] ?? '')) ?>" class="pz-btn pz-btn-outline">
            <span class="material-symbols-outlined">inventory_2</span> View Units
        </a>
        <?php endif; ?>
        <a href="<?= base_url('resources') ?>" class="pz-btn pz-btn-ghost">Back</a>
    </div>
</div>

<?php if ($isAsset && !empty($items) && is_array($items)): ?>
<div class="pz-card">
    <p class="pz-card-section">Units</p>
    <div class="pz-table-wrap" style="margin:0; border-radius:8px">
        <table class="pz-table" style="min-width:0">
            <thead><tr>
                <th>Serial No</th><th>Condition</th><th>Status</th><th>Assigned To</th>
            </tr></thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
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

<?= $this->endSection() ?>
