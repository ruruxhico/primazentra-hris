<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $resource = $resource ?? [];
    $isEdit = !empty($resource['id'] ?? null);
    $formAction = $isEdit ? base_url('resources/update/' . $resource['id']) : base_url('resources/store');
?>

<a href="<?= base_url('resources') ?>" class="pz-back-link">
    <span class="material-symbols-outlined">arrow_back</span> Resources
</a>

<?php if (session()->getFlashdata('error')): ?>
    <div class="pz-alert pz-alert-error">
        <span class="material-symbols-outlined" style="font-size:1rem">error</span>
        <?= session()->getFlashdata('error') ?>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('success')): ?>
    <div class="pz-alert pz-alert-success">
        <span class="material-symbols-outlined" style="font-size:1rem">check_circle</span>
        <?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>

<div class="pz-page-header">
    <h1><?= $isEdit ? 'Edit Resource' : 'Create Resource' ?></h1>
</div>

<div class="pz-form-container">
    <form method="POST" action="<?= $formAction ?>" id="resourceForm">
        <?= csrf_field() ?>

        <!-- Basic Information -->
        <div class="pz-form-section">
            <p class="pz-form-section-title">Basic Information</p>
            <div class="pz-form-row cols-3">
                <div class="pz-form-group">
                    <label for="name">Resource Name *</label>
                    <input type="text" id="name" name="name"
                           value="<?= isset($resource['name']) ? esc($resource['name']) : '' ?>"
                           placeholder="e.g., Hilti Jackhammer" required>
                </div>
                <div class="pz-form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required onchange="updateFormFields()">
                        <option value="">Select Category</option>
                        <option value="Manpower"  <?= ($resource['category'] ?? ($_GET['category'] ?? '')) === 'Manpower'  ? 'selected' : '' ?>>Manpower</option>
                        <option value="Equipment" <?= ($resource['category'] ?? ($_GET['category'] ?? '')) === 'Equipment' ? 'selected' : '' ?>>Equipment</option>
                        <option value="Tools"     <?= ($resource['category'] ?? ($_GET['category'] ?? '')) === 'Tools'     ? 'selected' : '' ?>>Tools</option>
                    </select>
                </div>
                <div class="pz-form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        <option value="">Select Status</option>
                        <option value="Available"         <?= ($resource['status'] ?? '') === 'Available'         ? 'selected' : '' ?>>Available</option>
                        <option value="Deployed"          <?= ($resource['status'] ?? '') === 'Deployed'          ? 'selected' : '' ?>>Deployed</option>
                        <option value="Under Maintenance" <?= ($resource['status'] ?? '') === 'Under Maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
                        <option value="Unavailable"       <?= ($resource['status'] ?? '') === 'Unavailable'       ? 'selected' : '' ?>>Unavailable</option>
                    </select>
                </div>
            </div>
            <div class="pz-form-row" style="max-width:280px;margin-top:.65rem">
                <div class="pz-form-group">
                    <label for="next_availability">Next Availability</label>
                    <input type="datetime-local" id="next_availability" name="next_availability"
                           value="<?= isset($resource['next_availability']) ? esc($resource['next_availability']) : '' ?>">
                </div>
            </div>
        </div>

        <!-- Location -->
        <div class="pz-form-section">
            <p class="pz-form-section-title">Location</p>
            <div class="pz-form-row cols-2">
                <div class="pz-form-group">
                    <label for="location_address">Address *</label>
                    <input type="text" id="location_address" name="location_address"
                           value="<?= isset($resource['location']['address']) ? esc($resource['location']['address']) : '' ?>"
                           placeholder="Full address" required>
                </div>
                <div class="pz-form-group">
                    <label for="location_type">Location Type *</label>
                    <select id="location_type" name="location_type" required>
                        <option value="">Select Type</option>
                        <option value="Warehouse"    <?= ($resource['location']['type'] ?? '') === 'Warehouse'    ? 'selected' : '' ?>>Warehouse</option>
                        <option value="Project Site" <?= ($resource['location']['type'] ?? '') === 'Project Site' ? 'selected' : '' ?>>Project Site</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Manpower Details (conditional) -->
        <div id="manpower-section" class="pz-form-section d-none">
            <p class="pz-form-section-title">Manpower Details</p>
            <div class="pz-form-row cols-2">
                <div class="pz-form-group">
                    <label for="manpower_id">Manpower ID</label>
                    <input type="text" id="manpower_id" name="manpower_id"
                           value="<?= esc((string)($resource['manpower_details']['manpower_id'] ?? '')) ?>">
                </div>
                <div class="pz-form-group">
                    <label for="manpower_status">Status *</label>
                    <select id="manpower_status" name="manpower_status" required>
                        <option value="">Select Status</option>
                        <?php foreach (['AVAILABLE' => 'Available', 'DEPLOYED' => 'Deployed', 'ON-LEAVE' => 'On-Leave'] as $val => $label): ?>
                        <option value="<?= esc($val) ?>" <?= strcasecmp((string)($resource['manpower_details']['status'] ?? $resource['status'] ?? ''), $val) === 0 ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="pz-form-row cols-2" style="margin-top:.65rem">
                <div class="pz-form-group">
                    <label for="manpower_role">Role *</label>
                    <select id="manpower_role" name="manpower_role" required>
                        <option value="">Select Role</option>
                        <option value="FIELD WORKER" <?= strcasecmp((string)($resource['manpower_details']['role'] ?? ''), 'FIELD WORKER') === 0 ? 'selected' : '' ?>>Field Worker</option>
                        <option value="FOREMAN"      <?= strcasecmp((string)($resource['manpower_details']['role'] ?? ''), 'FOREMAN')      === 0 ? 'selected' : '' ?>>Foreman</option>
                    </select>
                </div>
                <div class="pz-form-group">
                    <label for="specialization">Specialization *</label>
                    <select id="specialization" name="specialization" required>
                        <option value="">Select Specialization</option>
                        <?php foreach (['BUILD-TO-SUIT' => 'Build-to-Suit', 'RETROFITTING' => 'Retrofitting', 'COLOCATION' => 'Colocation', 'CONVERSION' => 'Conversion'] as $val => $label): ?>
                        <option value="<?= esc($val) ?>" <?= strcasecmp((string)($resource['manpower_details']['specialization'] ?? ''), $val) === 0 ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="pz-form-row cols-2" style="margin-top:.65rem">
                <div class="pz-form-group">
                    <label for="contact_number">Contact Number *</label>
                    <input type="tel" id="contact_number" name="contact_number"
                           value="<?= esc((string)($resource['manpower_details']['contact_number'] ?? '')) ?>"
                           placeholder="09XX-XXXX-XXX" required>
                </div>
                <div class="pz-form-group">
                    <label for="manpower_assigned_project_id">Assigned Project (Optional)</label>
                    <input type="text" id="manpower_assigned_project_id" name="manpower_assigned_project_id"
                           value="<?= esc((string)($resource['manpower_details']['assigned_project_id'] ?? '')) ?>">
                </div>
            </div>
        </div>

        <!-- Asset Details (Equipment/Tools, conditional) -->
        <div id="asset-section" class="pz-form-section d-none">
            <p class="pz-form-section-title">Asset Details</p>
            <div class="pz-form-row cols-2">
                <div class="pz-form-group">
                    <label for="total_quantity">Total Quantity *</label>
                    <input type="number" id="total_quantity" name="total_quantity" min="1"
                           value="<?= esc((string)($resource['asset_details']['total_quantity'] ?? '')) ?>"
                           placeholder="e.g., 10" required>
                </div>
            </div>
            <div class="pz-form-row cols-2" style="margin-top:.65rem">
                <div class="pz-form-group">
                    <label for="available_quantity">Available Quantity *</label>
                    <input type="number" id="available_quantity" name="available_quantity" min="0"
                           value="<?= esc((string)($resource['asset_details']['available_quantity'] ?? '')) ?>"
                           placeholder="e.g., 8" required>
                    <small>Total minus deployed / broken units</small>
                </div>
                <div class="pz-form-group">
                    <label for="asset_status">Asset Status *</label>
                    <select id="asset_status" name="asset_status" required>
                        <option value="">Select Status</option>
                        <option value="In-Storage" <?= ($resource['asset_details']['status'] ?? '') === 'In-Storage' ? 'selected' : '' ?>>In-Storage</option>
                        <option value="In-Use"     <?= ($resource['asset_details']['status'] ?? '') === 'In-Use'     ? 'selected' : '' ?>>In-Use</option>
                        <option value="Repair"     <?= ($resource['asset_details']['status'] ?? '') === 'Repair'     ? 'selected' : '' ?>>Repair</option>
                    </select>
                </div>
            </div>
            <div class="pz-form-row" style="max-width:320px;margin-top:.65rem">
                <div class="pz-form-group">
                    <label for="asset_assigned_project_id">Assigned Project (Optional)</label>
                    <input type="text" id="asset_assigned_project_id" name="asset_assigned_project_id"
                           value="<?= esc((string)($resource['asset_details']['assigned_project_id'] ?? '')) ?>">
                </div>
            </div>
        </div>

        <!-- Individual Items (Equipment/Tools, conditional) -->
        <div id="items-section" class="pz-form-section d-none">
            <p class="pz-form-section-title">Individual Units</p>
            <div class="pz-table-wrap">
                <table id="itemsTable" class="pz-table">
                    <thead>
                        <tr>
                            <th>Serial No.</th>
                            <th>Condition</th>
                            <th>Status</th>
                            <th>Address</th>
                            <th>Location Type</th>
                            <th class="center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="items-tbody">
                        <?php if (isset($resource['items']) && is_array($resource['items'])): ?>
                            <?php foreach ($resource['items'] as $index => $item): ?>
                            <tr class="item-row" data-index="<?= $index ?>">
                                <td><input type="text" name="items[<?= $index ?>][serial_number]" class="pz-form-group input" value="<?= esc((string)($item['serial_number'] ?? '')) ?>" placeholder="SN-12345" style="width:100%"></td>
                                <td>
                                    <select name="items[<?= $index ?>][condition]" style="width:100%">
                                        <option value="">Select</option>
                                        <?php foreach (['New','Functional','Worn','Broken'] as $opt): ?>
                                        <option value="<?= esc($opt) ?>" <?= ($item['condition'] ?? '') === $opt ? 'selected' : '' ?>><?= esc($opt) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="items[<?= $index ?>][status]" style="width:100%">
                                        <option value="">Select</option>
                                        <?php foreach (['In-Storage','In-Use','Repair'] as $opt): ?>
                                        <option value="<?= esc($opt) ?>" <?= ($item['status'] ?? '') === $opt ? 'selected' : '' ?>><?= esc($opt) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="text" name="items[<?= $index ?>][address]" value="<?= esc((string)($item['location']['address'] ?? '')) ?>" placeholder="Address" style="width:100%"></td>
                                <td>
                                    <select name="items[<?= $index ?>][location_type]" style="width:100%">
                                        <option value="">Select</option>
                                        <option value="Warehouse"    <?= ($item['location']['type'] ?? '') === 'Warehouse'    ? 'selected' : '' ?>>Warehouse</option>
                                        <option value="Project Site" <?= ($item['location']['type'] ?? '') === 'Project Site' ? 'selected' : '' ?>>Project Site</option>
                                    </select>
                                </td>
                                <td class="center">
                                    <button type="button" class="pz-btn-icon danger" onclick="removeItem(this)" title="Remove">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top:.75rem">
                <button type="button" class="pz-btn pz-btn-outline" onclick="addItem()">
                    <span class="material-symbols-outlined">add</span> Add Unit
                </button>
            </div>
        </div>

        <div class="pz-form-actions">
            <button type="submit" class="pz-btn pz-btn-primary">
                <span class="material-symbols-outlined">save</span>
                <?= $isEdit ? 'Update Resource' : 'Create Resource' ?>
            </button>
            <a href="<?= base_url('resources') ?>" class="pz-btn pz-btn-cancel">Cancel</a>
        </div>
    </form>
</div>

<script>
let itemCounter = <?= isset($resource['items']) ? count($resource['items']) : 0 ?>;

function updateFormFields() {
    const category = document.getElementById('category').value;
    const manpowerSection = document.getElementById('manpower-section');
    const assetSection    = document.getElementById('asset-section');
    const itemsSection    = document.getElementById('items-section');

    function setSectionState(section, enabled) {
        section.querySelectorAll('input, select, textarea').forEach(function (f) {
            if (f.hasAttribute('data-base-required')) f.required = enabled;
            f.disabled = !enabled;
        });
    }

    manpowerSection.style.display = 'none';
    assetSection.style.display    = 'none';
    itemsSection.style.display    = 'none';
    setSectionState(manpowerSection, false);
    setSectionState(assetSection,    false);
    setSectionState(itemsSection,    false);

    if (category === 'Manpower') {
        manpowerSection.style.display = 'block';
        setSectionState(manpowerSection, true);
    } else if (category === 'Equipment' || category === 'Tools') {
        assetSection.style.display  = 'block';
        itemsSection.style.display  = 'block';
        setSectionState(assetSection,  true);
        setSectionState(itemsSection,  true);
    }
}

function addItem() {
    const tbody = document.getElementById('items-tbody');
    const tr = document.createElement('tr');
    tr.className  = 'item-row';
    tr.dataset.index = itemCounter;
    tr.innerHTML = `
        <td><input type="text" name="items[${itemCounter}][serial_number]" placeholder="SN-12345" style="width:100%"></td>
        <td><select name="items[${itemCounter}][condition]" style="width:100%">
            <option value="">Select</option>
            <option>New</option><option>Functional</option><option>Worn</option><option>Broken</option>
        </select></td>
        <td><select name="items[${itemCounter}][status]" style="width:100%">
            <option value="">Select</option>
            <option>In-Storage</option><option>In-Use</option><option>Repair</option>
        </select></td>
        <td><input type="text" name="items[${itemCounter}][address]" placeholder="Address" style="width:100%"></td>
        <td><select name="items[${itemCounter}][location_type]" style="width:100%">
            <option value="">Select</option>
            <option>Warehouse</option><option>Project Site</option>
        </select></td>
        <td class="center"><button type="button" class="pz-btn-icon danger" onclick="removeItem(this)" title="Remove">
            <span class="material-symbols-outlined">delete</span>
        </button></td>`;
    tbody.appendChild(tr);
    itemCounter++;
}

function removeItem(btn) { btn.closest('tr').remove(); }

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('#manpower-section [required], #asset-section [required], #items-section [required]').forEach(function (f) {
        f.setAttribute('data-base-required', '1');
    });
    updateFormFields();
});
</script>

<?= $this->endSection() ?>
