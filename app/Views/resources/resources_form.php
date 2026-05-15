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
                        <option value="Manpower" <?= isset($resource['category']) && $resource['category'] === 'Manpower' ? 'selected' : '' ?>>Manpower</option>
                        <option value="Equipment" <?= isset($resource['category']) && $resource['category'] === 'Equipment' ? 'selected' : '' ?>>Equipment</option>
                        <option value="Tools" <?= isset($resource['category']) && $resource['category'] === 'Tools' ? 'selected' : '' ?>>Tools</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="">-- Select Status --</option>
                        <option value="Available" <?= isset($resource['status']) && $resource['status'] === 'Available' ? 'selected' : '' ?>>Available</option>
                        <option value="Deployed" <?= isset($resource['status']) && $resource['status'] === 'Deployed' ? 'selected' : '' ?>>Deployed</option>
                        <option value="Maintenance" <?= isset($resource['status']) && $resource['status'] === 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                        <option value="On-Leave" <?= isset($resource['status']) && $resource['status'] === 'On-Leave' ? 'selected' : '' ?>>On-Leave</option>
                    </select>
                </div>

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
                    <label for="manpower_role">Role</label>
                    <select id="manpower_role" name="manpower_role" class="form-control">
                        <option value="">-- Select Role --</option>
                        <option value="Field Worker" <?= isset($resource['manpower_details']['role']) && $resource['manpower_details']['role'] === 'Field Worker' ? 'selected' : '' ?>>Field Worker</option>
                        <option value="Foreman" <?= isset($resource['manpower_details']['role']) && $resource['manpower_details']['role'] === 'Foreman' ? 'selected' : '' ?>>Foreman</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="specialization">Specialization</label>
                    <select id="specialization" name="specialization" class="form-control">
                        <option value="">-- Select Specialization --</option>
                        <option value="Residential" <?= isset($resource['manpower_details']['specialization']) && $resource['manpower_details']['specialization'] === 'Residential' ? 'selected' : '' ?>>Residential</option>
                        <option value="Towers" <?= isset($resource['manpower_details']['specialization']) && $resource['manpower_details']['specialization'] === 'Towers' ? 'selected' : '' ?>>Towers</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="contact_number">Contact Number</label>
                    <input type="tel" id="contact_number" name="contact_number" class="form-control"
                           value="<?= isset($resource['manpower_details']['contact_number']) ? esc($resource['manpower_details']['contact_number']) : '' ?>"
                           placeholder="09XX-XXXX-XXX">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="assigned_project_id">Assigned Project ID (Optional)</label>
                    <input type="text" id="assigned_project_id" name="assigned_project_id" class="form-control"
                           value="<?= isset($resource['manpower_details']['assigned_project_id']) ? esc($resource['manpower_details']['assigned_project_id']) : '' ?>">
                </div>
            </div>
        </div>

        <!-- Asset Details Section (Equipment/Tools) -->
        <div id="asset-section" class="form-section" style="display: none;">
            <h2>Asset Details</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="model_number">Model Number</label>
                    <input type="text" id="model_number" name="model_number" class="form-control"
                           value="<?= isset($resource['asset_details']['model_number']) ? esc($resource['asset_details']['model_number']) : '' ?>"
                           placeholder="e.g., HD500-PRO">
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
                           placeholder="e.g., 8">
                    <small>Calculated as: Total - Deployed/Broken</small>
                </div>

                <div class="form-group">
                    <label for="condition_summary">Condition Summary (JSON)</label>
                    <textarea id="condition_summary" name="condition_summary" class="form-control" rows="3"
                              placeholder='{"New": 5, "Worn": 2, "Functional": 3}'><?= isset($resource['asset_details']['condition_summary']) ? json_encode($resource['asset_details']['condition_summary']) : '' ?></textarea>
                    <small>Format: {"New": #, "Worn": #, "Functional": #, "Broken": #}</small>
                </div>
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
    function updateFormFields() {
        const category = document.getElementById('category').value;
        const manpowerSection = document.getElementById('manpower-section');
        const assetSection = document.getElementById('asset-section');

        // Hide both sections first
        manpowerSection.style.display = 'none';
        assetSection.style.display = 'none';

        // Show appropriate section based on category
        if (category === 'Manpower') {
            manpowerSection.style.display = 'block';
        } else if (category === 'Equipment' || category === 'Tools') {
            assetSection.style.display = 'block';
        }
    }

    // Initialize form fields on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateFormFields();
    });
</script>

<?= $this->endSection() ?>
