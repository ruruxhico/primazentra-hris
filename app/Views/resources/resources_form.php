<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<header class="resource-header">
    <h1><?= isset($resource) && $resource ? 'Edit Resource' : 'Create New Resource' ?></h1>
    <div class="user-profile">
        <strong><?= esc($userName ?? 'User') ?></strong><br>
        <small><?= strtoupper($role ?? 'USER') ?></small>
    </div>
</header>

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

<div class="resource-form-container">
    <form method="POST" action="<?= isset($resource) && $resource ? base_url('resources/update/' . $resource['id']) : base_url('resources/store') ?>" id="resourceForm">
        <?= csrf_field() ?>

        <!-- Basic Information Section -->
        <div class="form-section">
            <h2>Basic Information</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Resource Name *</label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?= isset($resource['name']) ? esc($resource['name']) : '' ?>" 
                           placeholder="e.g., John Doe or Hilti Jackhammer" required>
                </div>

                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" class="form-control" required onchange="updateFormFields()">
                        <option value="">-- Select Category --</option>
                        <option value="Manpower" <?= isset($resource['category']) && $resource['category'] === 'Manpower' ? 'selected' : (isset($_GET['category']) && $_GET['category'] === 'Manpower' ? 'selected' : '') ?>>Manpower</option>
                        <option value="Equipment" <?= isset($resource['category']) && $resource['category'] === 'Equipment' ? 'selected' : (isset($_GET['category']) && $_GET['category'] === 'Equipment' ? 'selected' : '') ?>>Equipment</option>
                        <option value="Tools" <?= isset($resource['category']) && $resource['category'] === 'Tools' ? 'selected' : (isset($_GET['category']) && $_GET['category'] === 'Tools' ? 'selected' : '') ?>>Tools</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="next_availability">Next Availability</label>
                    <input type="datetime-local" id="next_availability" name="next_availability" class="form-control"
                           value="<?= isset($resource['next_availability']) ? esc($resource['next_availability']) : '' ?>">
                </div>
            </div>
        </div>

        <!-- Location Information Section -->
        <div class="form-section">
            <h2>Location Information</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="location_address">Address *</label>
                    <input type="text" id="location_address" name="location_address" class="form-control" 
                           value="<?= isset($resource['location']['address']) ? esc($resource['location']['address']) : '' ?>"
                           placeholder="Full address" required>
                </div>

                <div class="form-group">
                    <label for="location_type">Location Type *</label>
                    <select id="location_type" name="location_type" class="form-control" required>
                        <option value="">-- Select Type --</option>
                        <option value="Warehouse" <?= isset($resource['location']['type']) && $resource['location']['type'] === 'Warehouse' ? 'selected' : '' ?>>Warehouse</option>
                        <option value="Project Site" <?= isset($resource['location']['type']) && $resource['location']['type'] === 'Project Site' ? 'selected' : '' ?>>Project Site</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="geopoint">Geopoint (Lat/Lng)</label>
                    <input type="text" id="geopoint" name="geopoint" class="form-control" 
                           value="<?= isset($resource['location']['geopoint']) ? esc($resource['location']['geopoint']) : '' ?>"
                           placeholder="e.g., 14.5994,-120.9842">
                    <small>Format: latitude,longitude</small>
                </div>
            </div>
        </div>

        <!-- Manpower Specific Section -->
        <div id="manpower-section" class="form-section" style="display: none;">
            <h2>Manpower Details</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="manpower_id">Manpower ID</label>
                    <input type="text" id="manpower_id" name="manpower_id" class="form-control"
                           value="<?= isset($resource['manpower_details']['manpower_id']) ? esc($resource['manpower_details']['manpower_id']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="manpower_status">Status *</label>
                    <select id="manpower_status" name="manpower_status" class="form-control" required>
                        <option value="">-- Select Status --</option>
                        <option value="Available" <?= isset($resource['manpower_details']['status']) && $resource['manpower_details']['status'] === 'Available' ? 'selected' : '' ?>>Available</option>
                        <option value="Deployed" <?= isset($resource['manpower_details']['status']) && $resource['manpower_details']['status'] === 'Deployed' ? 'selected' : '' ?>>Deployed</option>
                        <option value="On-Leave" <?= isset($resource['manpower_details']['status']) && $resource['manpower_details']['status'] === 'On-Leave' ? 'selected' : '' ?>>On-Leave</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="manpower_role">Role *</label>
                    <select id="manpower_role" name="manpower_role" class="form-control" required>
                        <option value="">-- Select Role --</option>
                        <option value="Field Worker" <?= isset($resource['manpower_details']['role']) && $resource['manpower_details']['role'] === 'Field Worker' ? 'selected' : '' ?>>Field Worker</option>
                        <option value="Foreman" <?= isset($resource['manpower_details']['role']) && $resource['manpower_details']['role'] === 'Foreman' ? 'selected' : '' ?>>Foreman</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="specialization">Specialization *</label>
                    <select id="specialization" name="specialization" class="form-control" required>
                        <option value="">-- Select Specialization --</option>
                        <option value="Residential" <?= isset($resource['manpower_details']['specialization']) && $resource['manpower_details']['specialization'] === 'Residential' ? 'selected' : '' ?>>Residential</option>
                        <option value="Towers" <?= isset($resource['manpower_details']['specialization']) && $resource['manpower_details']['specialization'] === 'Towers' ? 'selected' : '' ?>>Towers</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="contact_number">Contact Number *</label>
                    <input type="tel" id="contact_number" name="contact_number" class="form-control"
                           value="<?= isset($resource['manpower_details']['contact_number']) ? esc($resource['manpower_details']['contact_number']) : '' ?>"
                           placeholder="09XX-XXXX-XXX" required>
                </div>

                <div class="form-group">
                    <label for="manpower_assigned_project_id">Assigned Project ID (Optional)</label>
                    <input type="text" id="manpower_assigned_project_id" name="manpower_assigned_project_id" class="form-control"
                           value="<?= isset($resource['manpower_details']['assigned_project_id']) ? esc($resource['manpower_details']['assigned_project_id']) : '' ?>">
                </div>
            </div>
        </div>

        <!-- Asset Details Section (Equipment/Tools) -->
        <div id="asset-section" class="form-section" style="display: none;">
            <h2>Asset Details</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="model_number">Model Number *</label>
                    <input type="text" id="model_number" name="model_number" class="form-control"
                           value="<?= isset($resource['asset_details']['model_number']) ? esc($resource['asset_details']['model_number']) : '' ?>"
                           placeholder="e.g., HD500-PRO" required>
                </div>

                <div class="form-group">
                    <label for="total_quantity">Total Quantity *</label>
                    <input type="number" id="total_quantity" name="total_quantity" class="form-control" min="1"
                           value="<?= isset($resource['asset_details']['total_quantity']) ? esc($resource['asset_details']['total_quantity']) : '' ?>"
                           placeholder="e.g., 10" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="available_quantity">Available Quantity *</label>
                    <input type="number" id="available_quantity" name="available_quantity" class="form-control" min="0"
                           value="<?= isset($resource['asset_details']['available_quantity']) ? esc($resource['asset_details']['available_quantity']) : '' ?>"
                           placeholder="e.g., 8" required>
                    <small>Calculated as: Total - Deployed/Broken</small>
                </div>

                <div class="form-group">
                    <label for="condition_summary">Condition Summary (JSON) *</label>
                    <textarea id="condition_summary" name="condition_summary" class="form-control" rows="3"
                              placeholder='{"New": 5, "Worn": 2, "Functional": 3}' required><?= isset($resource['asset_details']['condition_summary']) ? json_encode($resource['asset_details']['condition_summary']) : '' ?></textarea>
                    <small>Format: {"New": #, "Worn": #, "Functional": #, "Broken": #}</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="asset_status">Status *</label>
                    <select id="asset_status" name="asset_status" class="form-control" required>
                        <option value="">-- Select Status --</option>
                        <option value="In-Storage" <?= isset($resource['asset_details']['status']) && $resource['asset_details']['status'] === 'In-Storage' ? 'selected' : '' ?>>In-Storage</option>
                        <option value="In-Use" <?= isset($resource['asset_details']['status']) && $resource['asset_details']['status'] === 'In-Use' ? 'selected' : '' ?>>In-Use</option>
                        <option value="Repair" <?= isset($resource['asset_details']['status']) && $resource['asset_details']['status'] === 'Repair' ? 'selected' : '' ?>>Repair</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="asset_assigned_project_id">Assigned Project ID (Optional)</label>
                    <input type="text" id="asset_assigned_project_id" name="asset_assigned_project_id" class="form-control"
                           value="<?= isset($resource['asset_details']['assigned_project_id']) ? esc($resource['asset_details']['assigned_project_id']) : '' ?>">
                </div>
            </div>
        </div>

        <!-- Individual Items Section (Equipment/Tools) -->
        <div id="items-section" class="form-section" style="display: none;">
            <h2>Individual Items</h2>
            <p><small>Add individual units for this resource</small></p>
            
            <div class="items-container">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Unit ID</th>
                            <th>Serial Number</th>
                            <th>Condition</th>
                            <th>Status</th>
                            <th>Address</th>
                            <th>Location Type</th>
                            <th>Geopoint</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="items-tbody">
                        <?php if (isset($resource['items']) && is_array($resource['items'])): ?>
                            <?php foreach ($resource['items'] as $index => $item): ?>
                                <tr class="item-row" data-index="<?= $index ?>">
                                    <td><input type="text" name="items[<?= $index ?>][unit_id]" class="form-control" value="<?= esc((string)($item['unit_id'] ?? '')) ?>" placeholder="UNIT-001"></td>
                                    <td><input type="text" name="items[<?= $index ?>][serial_number]" class="form-control" value="<?= esc((string)($item['serial_number'] ?? '')) ?>" placeholder="SN-12345"></td>
                                    <td>
                                        <select name="items[<?= $index ?>][condition]" class="form-control">
                                            <option value="">Select</option>
                                            <option value="New" <?= isset($item['condition']) && $item['condition'] === 'New' ? 'selected' : '' ?>>New</option>
                                            <option value="Functional" <?= isset($item['condition']) && $item['condition'] === 'Functional' ? 'selected' : '' ?>>Functional</option>
                                            <option value="Worn" <?= isset($item['condition']) && $item['condition'] === 'Worn' ? 'selected' : '' ?>>Worn</option>
                                            <option value="Broken" <?= isset($item['condition']) && $item['condition'] === 'Broken' ? 'selected' : '' ?>>Broken</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="items[<?= $index ?>][status]" class="form-control">
                                            <option value="">Select</option>
                                            <option value="In-Storage" <?= isset($item['status']) && $item['status'] === 'In-Storage' ? 'selected' : '' ?>>In-Storage</option>
                                            <option value="In-Use" <?= isset($item['status']) && $item['status'] === 'In-Use' ? 'selected' : '' ?>>In-Use</option>
                                            <option value="Repair" <?= isset($item['status']) && $item['status'] === 'Repair' ? 'selected' : '' ?>>Repair</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="items[<?= $index ?>][address]" class="form-control" value="<?= isset($item['location']['address']) ? esc($item['location']['address']) : '' ?>" placeholder="Address"></td>
                                    <td>
                                        <select name="items[<?= $index ?>][location_type]" class="form-control">
                                            <option value="">Select</option>
                                            <option value="Warehouse" <?= isset($item['location']['type']) && $item['location']['type'] === 'Warehouse' ? 'selected' : '' ?>>Warehouse</option>
                                            <option value="Project Site" <?= isset($item['location']['type']) && $item['location']['type'] === 'Project Site' ? 'selected' : '' ?>>Project Site</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="items[<?= $index ?>][geopoint]" class="form-control" value="<?= isset($item['location']['geopoint']) ? esc($item['location']['geopoint']) : '' ?>" placeholder="lat,lng"></td>
                                    <td><button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">Remove</button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="form-actions" style="margin-top: 15px;">
                <button type="button" class="btn btn-secondary" onclick="addItem()">+ Add Item</button>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <?= isset($resource) && $resource ? 'Update Resource' : 'Create Resource' ?>
            </button>
            <a href="<?= base_url('resources') ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
    let itemCounter = <?= isset($resource['items']) ? count($resource['items']) : 0 ?>;

    function updateFormFields() {
        const category = document.getElementById('category').value;
        const manpowerSection = document.getElementById('manpower-section');
        const assetSection = document.getElementById('asset-section');
        const itemsSection = document.getElementById('items-section');

        // Hide all sections first
        manpowerSection.style.display = 'none';
        assetSection.style.display = 'none';
        itemsSection.style.display = 'none';

        // Show appropriate section based on category
        if (category === 'Manpower') {
            manpowerSection.style.display = 'block';
        } else if (category === 'Equipment' || category === 'Tools') {
            assetSection.style.display = 'block';
            itemsSection.style.display = 'block';
        }
    }

    function addItem() {
        const tbody = document.getElementById('items-tbody');
        const newRow = document.createElement('tr');
        newRow.className = 'item-row';
        newRow.dataset.index = itemCounter;
        
        newRow.innerHTML = `
            <td><input type="text" name="items[${itemCounter}][unit_id]" class="form-control" placeholder="UNIT-001"></td>
            <td><input type="text" name="items[${itemCounter}][serial_number]" class="form-control" placeholder="SN-12345"></td>
            <td>
                <select name="items[${itemCounter}][condition]" class="form-control">
                    <option value="">Select</option>
                    <option value="New">New</option>
                    <option value="Functional">Functional</option>
                    <option value="Worn">Worn</option>
                    <option value="Broken">Broken</option>
                </select>
            </td>
            <td>
                <select name="items[${itemCounter}][status]" class="form-control">
                    <option value="">Select</option>
                    <option value="In-Storage">In-Storage</option>
                    <option value="In-Use">In-Use</option>
                    <option value="Repair">Repair</option>
                </select>
            </td>
            <td><input type="text" name="items[${itemCounter}][address]" class="form-control" placeholder="Address"></td>
            <td>
                <select name="items[${itemCounter}][location_type]" class="form-control">
                    <option value="">Select</option>
                    <option value="Warehouse">Warehouse</option>
                    <option value="Project Site">Project Site</option>
                </select>
            </td>
            <td><input type="text" name="items[${itemCounter}][geopoint]" class="form-control" placeholder="lat,lng"></td>
            <td><button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)">Remove</button></td>
        `;
        
        tbody.appendChild(newRow);
        itemCounter++;
    }

    function removeItem(button) {
        const row = button.closest('tr');
        row.remove();
    }

    // Initialize form fields on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateFormFields();
    });
</script>

<?= $this->endSection() ?>
