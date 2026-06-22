<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $category   = (string)($resource['category'] ?? '');
    $isAsset    = in_array($category, ['Equipment', 'Tools'], true);
    $isManpower = $category === 'Manpower';
    $resId      = (string)($resource['id'] ?? '');

    $st = strtolower($resource['status'] ?? '');
    $stColors = [
        'available'   => ['bg' => '#e8f5e9', 'color' => '#2e7d32', 'border' => '#a5d6a7'],
        'in-use'      => ['bg' => '#fff3e0', 'color' => '#e65100', 'border' => '#ffcc80'],
        'deployed'    => ['bg' => '#fff3e0', 'color' => '#e65100', 'border' => '#ffcc80'],
        'maintenance' => ['bg' => '#fce4ec', 'color' => '#b71c1c', 'border' => '#ef9a9a'],
        'unavailable' => ['bg' => '#f5f5f5', 'color' => '#757575', 'border' => '#e0e0e0'],
    ];
    $stStyle = $stColors[$st] ?? ['bg' => '#f5f5f5', 'color' => '#555', 'border' => '#ccc'];
?>

<?php if (session()->getFlashdata('success')): ?>
<div class="pz-alert pz-alert-success" style="max-width:1100px;margin:0 auto .8rem">
    <span class="material-symbols-outlined" style="font-size:1rem">check_circle</span>
    <?= session()->getFlashdata('success') ?>
</div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="pz-alert pz-alert-error" style="max-width:1100px;margin:0 auto .8rem">
    <span class="material-symbols-outlined" style="font-size:1rem">error</span>
    <?= session()->getFlashdata('error') ?>
</div>
<?php endif; ?>

<div class="pd-shell">

    <!-- Back + Edit / View Units bar -->
    <div class="pd-topbar">
        <a href="<?= base_url('resources') ?>" class="pd-back">
            <span class="material-symbols-outlined">arrow_back</span>
            Resources
        </a>
        <div style="display:flex;gap:.5rem">
            <?php if ($isAsset): ?>
            <a href="<?= base_url('resources/viewItems/' . $resId) ?>" class="pd-edit-btn">
                <span class="material-symbols-outlined">inventory_2</span> View Units
            </a>
            <?php endif; ?>
            <a href="<?= base_url('resources/edit/' . $resId) ?>" class="pd-edit-btn">
                <span class="material-symbols-outlined">edit</span> Edit
            </a>
        </div>
    </div>

    <!-- Name + status card (full width) -->
    <div class="pd-project-card">
        <div class="pd-project-card-top">
            <div class="pd-project-name"><?= esc((string)($resource['name'] ?? 'Resource')) ?></div>
            <span class="pd-status-badge"
                  style="background:<?= $stStyle['bg'] ?>;color:<?= $stStyle['color'] ?>;border-color:<?= $stStyle['border'] ?>">
                <?= esc((string)($resource['status'] ?? '—')) ?>
            </span>
        </div>
        <div class="pd-project-type"><?= esc($category) ?></div>
    </div>

    <!-- Two-column: General Info + Asset/Manpower Details -->
    <div class="pd-two-col">

        <!-- General Info -->
        <div class="pd-section">
            <div class="pd-section-hdr">General Info</div>
            <div class="pd-section-body">
                <div class="pd-row">
                    <span class="pd-row-label">Category</span>
                    <span class="pd-row-value"><?= esc($category ?: '—') ?></span>
                </div>
                <div class="pd-row">
                    <span class="pd-row-label">Next Availability</span>
                    <span class="pd-row-value"><?= esc((string)($resource['next_availability_value'] ?? $resource['next_availability'] ?? '—')) ?></span>
                </div>
                <div class="pd-row">
                    <span class="pd-row-label">Location</span>
                    <span class="pd-row-value"><?= esc((string)($resource['location']['address'] ?? '—')) ?></span>
                </div>
                <div class="pd-row">
                    <span class="pd-row-label">Location Type</span>
                    <span class="pd-row-value"><?= esc((string)($resource['location']['type'] ?? '—')) ?></span>
                </div>
            </div>
        </div>

        <?php if ($isAsset): ?>
        <!-- Asset Details -->
        <div class="pd-section">
            <div class="pd-section-hdr">Asset Details</div>
            <div class="pd-section-body">
                <div class="pd-row">
                    <span class="pd-row-label">Model Number</span>
                    <span class="pd-row-value"><?= esc((string)($resource['asset_details']['model_number'] ?? '—')) ?></span>
                </div>
                <div class="pd-row">
                    <span class="pd-row-label">Total Quantity</span>
                    <span class="pd-row-value"><?= esc((string)($resource['asset_details']['total_quantity'] ?? '—')) ?></span>
                </div>
                <div class="pd-row">
                    <span class="pd-row-label">Available</span>
                    <span class="pd-row-value"><?= esc((string)($resource['asset_details']['available_quantity'] ?? '—')) ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($isManpower): ?>
        <!-- Manpower Details -->
        <div class="pd-section">
            <div class="pd-section-hdr">Manpower Details</div>
            <div class="pd-section-body">
                <div class="pd-row">
                    <span class="pd-row-label">Manpower ID</span>
                    <span class="pd-row-value"><?= esc((string)($resource['manpower_details']['manpower_id'] ?? '—')) ?></span>
                </div>
                <div class="pd-row">
                    <span class="pd-row-label">Role</span>
                    <span class="pd-row-value"><?= esc((string)($resource['manpower_details']['role'] ?? '—')) ?></span>
                </div>
                <div class="pd-row">
                    <span class="pd-row-label">Specialization</span>
                    <span class="pd-row-value"><?= esc((string)($resource['manpower_details']['specialization'] ?? '—')) ?></span>
                </div>
                <div class="pd-row">
                    <span class="pd-row-label">Contact</span>
                    <span class="pd-row-value"><?= esc((string)($resource['manpower_details']['contact_number'] ?? '—')) ?></span>
                </div>
                <div class="pd-row">
                    <span class="pd-row-label">Assigned Project</span>
                    <span class="pd-row-value"><?= esc((string)($resource['manpower_details']['assigned_project_id'] ?? '—')) ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /.pd-two-col -->

    <?php if ($isAsset && !empty($items) && is_array($items)): ?>
    <!-- Units table (full width) -->
    <div class="pd-section">
        <div class="pd-section-hdr">Units</div>
        <div style="overflow-x:auto">
            <table class="pz-table" style="min-width:0;border-radius:0">
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

</div><!-- /.pd-shell -->

<?= $this->endSection() ?>
