<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<header class="resource-header">
    <div>
        <h1><?= isset($resource['name']) ? esc((string)$resource['name']) : 'Resource' ?> - Items</h1>
        <p class="muted-sub">Category: <?= isset($resource['category']) ? esc((string)$resource['category']) : 'N/A' ?></p>
    </div>
    <div class="user-profile">
        <strong><?= esc($userName ?? 'User') ?></strong><br>
        <small><?= strtoupper($role ?? 'USER') ?></small>
    </div>
</header>

<div class="mb-20">
    <a href="<?= base_url('resources') ?>" class="btn btn-secondary mr-10">← Back to Resources</a>
    <button class="btn btn-success" onclick="document.getElementById('addItemModal').style.display='block'">+ Add Item</button>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger">
        <?= session()->getFlashdata('error') ?>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success">
        <?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>

<!-- Items Table -->
<div class="table-container">
    <table class="resource-table">
        <thead>
            <tr>
                <th>Serial Number</th>
                <th>Condition</th>
                <th>Status</th>
                <th>Assigned To</th>
                <th class="text-center">ACTIONS</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted">
                        No items found for this resource.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= isset($item['serial_number']) ? esc((string)$item['serial_number']) : 'N/A' ?></td>
                        <td>
                            <span class="status-badge status-<?= isset($item['condition']) ? strtolower($item['condition']) : 'n/a' ?>">
                                <?= isset($item['condition']) ? esc((string)$item['condition']) : 'N/A' ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-<?= isset($item['status']) ? strtolower(str_replace('-', '', $item['status'])) : 'n/a' ?>">
                                <?= isset($item['status']) ? esc((string)$item['status']) : 'N/A' ?>
                            </span>
                        </td>
                        <td><?= isset($item['assigned_to']) ? esc((string)$item['assigned_to']) : 'Unassigned' ?></td>
                        <td class="text-center">
                            <button class="action-btn" title="Edit" onclick="alert('Edit functionality coming soon')"><span class="material-symbols-outlined">edit</span></button>
                            <button class="action-btn" title="Delete" onclick="return confirm('Are you sure?');"><span class="material-symbols-outlined">delete</span></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Item Modal -->
<div id="addItemModal">
    <div class="modal-content">
        <span class="modal-close" onclick="document.getElementById('addItemModal').style.display='none'">&times;</span>
        <h2 class="modal-heading">Add New Item</h2>
        
        <form method="POST" action="<?= base_url('resources/addItem/' . (isset($resource['id']) ? $resource['id'] : '')) ?>">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="serial_number">Serial Number *</label>
                <input type="text" id="serial_number" name="serial_number" class="form-control"
                       placeholder="Physical tag number" required>
            </div>

            <div class="form-group">
                <label for="condition">Condition *</label>
                <select id="condition" name="condition" class="form-control" required>
                    <option value="">-- Select Condition --</option>
                    <option value="New">New</option>
                    <option value="Functional">Functional</option>
                    <option value="Worn">Worn</option>
                    <option value="Broken">Broken</option>
                </select>
            </div>

            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status" class="form-control" required>
                    <option value="">-- Select Status --</option>
                    <option value="In-Storage">In-Storage</option>
                    <option value="In-Use">In-Use</option>
                    <option value="Repair">Repair</option>
                </select>
            </div>

            <div class="form-group">
                <label for="assigned_to">Assigned To (Manpower ID)</label>
                <input type="text" id="assigned_to" name="assigned_to" class="form-control"
                       placeholder="Manpower document reference">
            </div>

            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="btn btn-primary flex-1">Add Item</button>
                <button type="button" class="btn btn-secondary flex-1" 
                        onclick="document.getElementById('addItemModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
