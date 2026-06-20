<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $resource = $resource ?? [];
    $asset = $resource['asset_details'] ?? [];
    $items = $items ?? [];
    $projectSites = $projectSites ?? [];
    $warehouseLocations = $warehouseLocations ?? [];
    $defaultWarehouseAddress = $defaultWarehouseAddress ?? '';
    $isEdit = !empty($resource['id'] ?? null);
    $formAction = $isEdit ? base_url('resources/update/' . $resource['id']) : base_url('resources/store');
    $hasItems = !empty($items);
    $addSpecificUnitsNow = (bool) ($asset['add_specific_units_now'] ?? $hasItems);
?>

<?php $mapsKey = $mapsKey ?? ''; ?>

<style>
.unit-cards-container { display: flex; flex-direction: column; gap: 0.6rem; }
.unit-card { border: 1.5px solid #c8a951; border-radius: 10px; overflow: hidden; background: #fff; }
.unit-card-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0.6rem 1rem; background: #f5e7b2; cursor: pointer; user-select: none;
}
.unit-card-title { font-weight: 700; font-size: 0.82rem; color: #5c3d11; }
.unit-card-chevron { font-size: 20px !important; color: #7a5c1e; transition: transform 0.2s ease; flex-shrink: 0; }
.unit-card.collapsed .unit-card-chevron { transform: rotate(180deg); }
.unit-card-body { padding: 1rem 1.1rem 0.8rem; }
.unit-card.collapsed .unit-card-body { display: none; }
.unit-fields-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.65rem 1rem; }
@media (max-width: 600px) { .unit-fields-grid { grid-template-columns: 1fr; } }
.unit-field { display: flex; flex-direction: column; gap: 0.25rem; }
.unit-field > label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: #7a5c1e; letter-spacing: 0.3px; }
.unit-field input, .unit-field select {
    width: 100%; padding: 0.38rem 0.6rem;
    border: 1.5px solid #d4b87a; border-radius: 7px;
    font-size: 0.85rem; background: #fafaf8; color: #333; box-sizing: border-box;
}
.unit-field input:focus, .unit-field select:focus { outline: none; border-color: #a0782a; background: #fff; }
.unit-field-address { grid-column: 1 / -1; }
.unit-warehouse-input-wrap { display: flex; gap: 0.4rem; }
.unit-warehouse-input-wrap input { flex: 1; }
.unit-card-footer { display: flex; justify-content: flex-end; margin-top: 0.75rem; padding-top: 0.6rem; border-top: 1px solid #e8d5a0; }
.qty-stepper { display: flex; align-items: center; background: #f5f5f3; border-radius: 999px; border: 1.5px solid #d4b87a; flex-shrink: 0; min-width: 0; width: fit-content; }
.qty-btn { flex: 0 0 28px !important; width: 28px !important; min-width: 0 !important; height: 24px !important; font-size: 14px !important; line-height: 24px !important; text-align: center; background: none !important; border: none !important; color: #7a5c1e !important; cursor: pointer; transition: background 0.15s; user-select: none; padding: 0 !important; }
.qty-btn:hover { background: #ede3c4 !important; border-radius: 999px; }
.qty-input { flex: 0 0 28px !important; width: 28px !important; min-width: 0 !important; text-align: center; border: none !important; background: transparent !important; font-size: 12px !important; font-weight: 600; color: #333; padding: 0 !important; -moz-appearance: textfield; }
.qty-input::-webkit-inner-spin-button, .qty-input::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
.qty-input:focus { outline: none; }
.qty-stepper .qty-input { border: none !important; border-radius: 0 !important; background: transparent !important; }
.add-flow-controls { display: flex; align-items: center; gap: 0.8rem; flex-wrap: wrap; }
.unit-qty-row { display: flex; justify-content: flex-end; align-items: center; gap: 0.3rem; margin-top: 0.4rem; }
.unit-qty-label { font-size: 0.62rem; font-weight: 700; text-transform: uppercase; color: #7a5c1e; letter-spacing: 0.3px; }
.equipment-remove-btn { background: transparent !important; border: 1.5px solid #e53e3e !important; color: #e53e3e !important; border-radius: 8px !important; }
.equipment-remove-btn:hover { background: #e53e3e !important; color: #fff !important; }
/* ── Warehouse default banner ─────────────────────────────── */
.warehouse-default-banner { display:flex; align-items:flex-start; gap:0.55rem; background:#fdf8ee; border:1px solid #d4b87a; border-radius:9px; padding:0.6rem 0.85rem; margin-bottom:0.75rem; font-size:0.83rem; }
.wdb-icon { font-size:18px !important; color:#8c701c; margin-top:1px; flex-shrink:0; }
.wdb-body { flex:1; min-width:0; }
.wdb-label { color:#8c701c; font-weight:700; margin-right:0.3rem; }
.wdb-address { color:#3a2a0a; word-break:break-word; }
.wdb-not-set { color:#aaa; font-style:italic; }
.wdb-edit-form { display:flex; flex-direction:column; gap:0.4rem; margin-top:0.2rem; }
.wdb-input-row { display:flex; gap:0.4rem; align-items:center; }
.wdb-action-row { display:flex; gap:0.4rem; justify-content:flex-end; }
.wdb-input { flex:1; min-width:0; padding:0.3rem 0.6rem; border:1.5px solid #d4b87a; border-radius:7px; font-size:0.82rem; background:#fff; box-sizing:border-box; font-family:inherit; }
.wdb-save-btn { padding:0.3rem 0.75rem; background:#c8a951; color:#fff; border:none; border-radius:7px; font-size:0.8rem; font-weight:700; cursor:pointer; font-family:inherit; white-space:nowrap; }
.wdb-save-btn:hover { background:#8c701c; }
.wdb-save-btn:disabled { opacity:0.6; cursor:not-allowed; }
.wdb-cancel-btn { padding:0.3rem 0.55rem; background:none; border:1px solid #ccc; border-radius:7px; font-size:0.8rem; cursor:pointer; font-family:inherit; color:#666; }
.wdb-map-btn { padding:0.3rem 0.65rem; background:#fff; border:1.5px solid #d4b87a; border-radius:7px; font-size:0.8rem; color:#7a5c1e; cursor:pointer; font-family:inherit; white-space:nowrap; }
.wdb-map-btn:hover { background:#f5e7b2; }
.wdb-edit-btn { background:none; border:1px solid #d4b87a; border-radius:6px; font-size:0.74rem; color:#8c701c; cursor:pointer; padding:0.18rem 0.5rem; font-family:inherit; flex-shrink:0; margin-top:1px; }
.wdb-edit-btn:hover { background:#f5e7b2; }
/* ── Warehouse saved chip (per card) ─────────────────────── */
.warehouse-saved-strip { margin-top:0.35rem; }
.warehouse-use-saved-btn { display:inline-flex; align-items:center; gap:0.3rem; background:#fdf8ee; border:1px solid #d4b87a; border-radius:20px; padding:0.2rem 0.75rem 0.2rem 0.55rem; font-size:0.75rem; color:#5c3d11; cursor:pointer; font-family:inherit; transition:background 0.15s; max-width:100%; }
.warehouse-use-saved-btn:hover { background:#f5e7b2; border-color:#a0782a; }
.wss-label { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:200px; font-weight:600; }
</style>

<a href="<?= base_url('resources') ?>" class="pz-back-link">
    <span class="material-symbols-outlined">arrow_back</span> Resources
</a>

<?php if (session()->getFlashdata('error')): ?>
    <div class="pz-alert pz-alert-error"><span class="material-symbols-outlined" style="font-size:1rem">error</span><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('success')): ?>
    <div class="pz-alert pz-alert-success"><span class="material-symbols-outlined" style="font-size:1rem">check_circle</span><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>

<div class="pz-page-header">
    <h1><?= $isEdit ? 'Edit Tool Model' : 'Add Tool Model' ?></h1>
    <p>Register a new type of tool. You can add specific physical units after creating this profile.</p>
</div>

<div class="equipment-shell">
    <div class="equipment-card">
        <form method="POST" action="<?= $formAction ?>" id="toolsForm">
            <?= csrf_field() ?>
            <input type="hidden" name="category" value="Tools">
            <input type="hidden" name="status" value="<?= esc((string)($resource['status'] ?? 'Available')) ?>">
            <input type="hidden" name="next_availability" value="<?= esc((string)($resource['next_availability'] ?? '')) ?>">
            <input type="hidden" name="location_address" value="<?= esc((string)($resource['location']['address'] ?? '')) ?>">
            <input type="hidden" name="location_type" value="<?= esc((string)($resource['location']['type'] ?? '')) ?>">
            <input type="hidden" name="asset_status" value="<?= esc((string)($asset['status'] ?? 'In-Storage')) ?>">
            <input type="hidden" name="asset_assigned_project_id" value="<?= esc((string)($asset['assigned_project_id'] ?? '')) ?>">

            <div class="equipment-field">
                <label for="name">Tool Name *</label>
                <input type="text" id="name" name="name" placeholder="e.g., Power Drill, Angle Grinder" value="<?= esc((string)($resource['name'] ?? '')) ?>" required>
            </div>

            <div class="equipment-toggle-card">
                <label class="equipment-checkbox-label">
                    <input type="checkbox" id="add_specific_units_now" name="add_specific_units_now" value="1" <?= $addSpecificUnitsNow ? 'checked' : '' ?>>
                    <span>Add specific units now</span>
                </label>
            </div>

            <div id="equipmentUnitsSection" class="equipment-units-section <?= $addSpecificUnitsNow ? 'open' : '' ?>">
                <div class="equipment-section-head">
                    <h2>Specific Units</h2>
                    <p>Add the physical units tied to this tool model.</p>
                </div>

                <div class="warehouse-default-banner" id="warehouseDefaultBanner">
                    <span class="material-symbols-outlined wdb-icon">warehouse</span>
                    <div class="wdb-body">
                        <div id="wdbStatic">
                            <span class="wdb-label">Default warehouse:</span>
                            <?php if ($defaultWarehouseAddress): ?>
                                <span class="wdb-address" id="wdbAddress"><?= esc($defaultWarehouseAddress) ?></span>
                            <?php else: ?>
                                <span class="wdb-not-set" id="wdbAddress">Not set — click Edit to save one</span>
                            <?php endif; ?>
                        </div>
                        <div class="wdb-edit-form" id="wdbEditForm" style="display:none">
                            <div class="wdb-input-row">
                                <input type="text" id="wdbInput" class="wdb-input" placeholder="Enter default warehouse address" value="<?= esc($defaultWarehouseAddress) ?>">
                                <button type="button" id="wdbMapBtn" class="wdb-map-btn">Map</button>
                            </div>
                            <div class="wdb-action-row">
                                <button type="button" id="wdbSaveBtn" class="wdb-save-btn">Save</button>
                                <button type="button" id="wdbCancelBtn" class="wdb-cancel-btn">Cancel</button>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="wdbEditBtn" class="wdb-edit-btn">Edit</button>
                </div>

                <div class="unit-cards-container" id="equipmentItemsContainer">
                    <?php if (!empty($items) && is_array($items)): ?>
                        <?php foreach ($items as $index => $item): ?>
                            <?php
                                $location = is_array($item['location'] ?? null) ? $item['location'] : [];
                                $locationType = (string) ($location['type'] ?? 'Warehouse');
                                $currentAddress = (string) ($location['address'] ?? '');
                                $serialLabel = !empty($item['serial_number']) ? ' — ' . esc((string)$item['serial_number']) : '';
                            ?>
                            <div class="unit-card" data-warehouse-value="<?= esc($currentAddress) ?>" data-project-value="<?= esc($currentAddress) ?>">
                                <div class="unit-card-header" onclick="toggleUnit(this)">
                                    <span class="unit-card-title">Unit #<?= $index + 1 ?><?= $serialLabel ?></span>
                                    <span class="unit-card-chevron material-symbols-outlined">expand_less</span>
                                </div>
                                <div class="unit-card-body">
                                    <div class="unit-fields-grid">
                                        <div class="unit-field">
                                            <label>Serial Number</label>
                                            <input type="text" name="items[<?= $index ?>][serial_number]" value="<?= esc((string)($item['serial_number'] ?? '')) ?>" placeholder="SN-12345" class="unit-serial-input">
                                        </div>
                                        <div class="unit-field">
                                            <label>Brand</label>
                                            <input type="text" name="items[<?= $index ?>][brand]" value="<?= esc((string)($item['brand'] ?? '')) ?>" placeholder="e.g. Makita">
                                        </div>
                                        <div class="unit-field">
                                            <label>Model Number</label>
                                            <input type="text" name="items[<?= $index ?>][model_number]" value="<?= esc((string)($item['model_number'] ?? '')) ?>" placeholder="e.g. DHP482">
                                        </div>
                                        <div class="unit-field">
                                            <label>Condition</label>
                                            <select name="items[<?= $index ?>][condition]">
                                                <option value="">Select</option>
                                                <?php foreach (['New', 'Functional', 'Worn', 'Broken'] as $opt): ?>
                                                    <option value="<?= esc($opt) ?>" <?= (($item['condition'] ?? '') === $opt) ? 'selected' : '' ?>><?= esc($opt) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="unit-field">
                                            <label>Status</label>
                                            <select name="items[<?= $index ?>][status]">
                                                <option value="">Select</option>
                                                <?php foreach (['In-Storage', 'In-Use', 'Repair'] as $opt): ?>
                                                    <option value="<?= esc($opt) ?>" <?= (($item['status'] ?? '') === $opt) ? 'selected' : '' ?>><?= esc($opt) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="unit-field">
                                            <label>Location Type</label>
                                            <select name="items[<?= $index ?>][location_type]" class="equipment-location-type">
                                                <option value="">Select Type</option>
                                                <option value="Warehouse" <?= $locationType === 'Warehouse' ? 'selected' : '' ?>>Warehouse</option>
                                                <option value="Project Site" <?= $locationType === 'Project Site' ? 'selected' : '' ?>>Project Site</option>
                                            </select>
                                        </div>
                                        <div class="unit-field unit-field-address">
                                            <label>Current Address</label>
                                            <div class="equipment-address-group equipment-address-project">
                                                <select name="items[<?= $index ?>][address]" class="equipment-address-select">
                                                    <option value="">Select project site</option>
                                                    <?php if (!empty($projectSites)): ?>
                                                        <?php foreach ($projectSites as $site): ?>
                                                            <option value="<?= esc((string)($site['value'] ?? '')) ?>" <?= $currentAddress === (string)($site['value'] ?? '') ? 'selected' : '' ?>><?= esc((string)($site['label'] ?? '')) ?></option>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <option value="">No project sites found</option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                            <div class="equipment-address-group equipment-address-warehouse">
                                                <div class="unit-warehouse-input-wrap">
                                                    <input type="text" name="items[<?= $index ?>][address]" class="equipment-warehouse-input" value="<?= esc($currentAddress) ?>" placeholder="Type warehouse location or pick on map" autocomplete="off">
                                                    <button type="button" class="equipment-map-btn">Map</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="unit-qty-row">
                                        <span class="unit-qty-label">Qty</span>
                                        <div class="qty-stepper unit-qty-stepper">
                                            <button type="button" class="qty-btn unit-qty-minus">&#8722;</button>
                                            <input type="number" name="items[<?= $index ?>][quantity]" class="qty-input unit-quantity-input" value="<?= esc((string)($item['quantity'] ?? 1)) ?>" min="1">
                                            <button type="button" class="qty-btn unit-qty-plus">+</button>
                                        </div>
                                    </div>
                                    <div class="unit-card-footer">
                                        <button type="button" class="equipment-remove-btn" onclick="removeEquipmentRow(this)">Remove Unit</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="equipment-actions-row">
                    <div class="add-flow-controls">
                        <button type="button" class="btn btn-secondary" id="addEquipmentUnitBtn">+ Add Unit</button>
                    </div>
                </div>
            </div>

            <div class="equipment-actions">
                <button type="submit" class="btn btn-save-mobile">Save Model</button>
                <a href="<?= base_url('resources') ?>" class="btn btn-cancel-mobile">Cancel</a>
            </div>
        </form>
    </div>
</div>

<template id="equipmentItemTemplate">
    <div class="unit-card" data-warehouse-value="" data-project-value="">
        <div class="unit-card-header" onclick="toggleUnit(this)">
            <span class="unit-card-title">New Unit</span>
            <span class="unit-card-chevron material-symbols-outlined">expand_less</span>
        </div>
        <div class="unit-card-body">
            <div class="unit-fields-grid">
                <div class="unit-field">
                    <label>Serial Number</label>
                    <input type="text" name="__INDEX__[serial_number]" placeholder="SN-12345" class="unit-serial-input">
                </div>
                <div class="unit-field">
                    <label>Brand</label>
                    <input type="text" name="__INDEX__[brand]" placeholder="e.g. Makita">
                </div>
                <div class="unit-field">
                    <label>Model Number</label>
                    <input type="text" name="__INDEX__[model_number]" placeholder="e.g. DHP482">
                </div>
                <div class="unit-field">
                    <label>Condition</label>
                    <select name="__INDEX__[condition]">
                        <option value="">Select</option>
                        <option value="New" selected>New</option>
                        <option value="Functional">Functional</option>
                        <option value="Worn">Worn</option>
                        <option value="Broken">Broken</option>
                    </select>
                </div>
                <div class="unit-field">
                    <label>Status</label>
                    <select name="__INDEX__[status]">
                        <option value="">Select</option>
                        <option value="In-Storage" selected>In-Storage</option>
                        <option value="In-Use">In-Use</option>
                        <option value="Repair">Repair</option>
                    </select>
                </div>
                <div class="unit-field">
                    <label>Location Type</label>
                    <select name="__INDEX__[location_type]" class="equipment-location-type">
                        <option value="">Select Type</option>
                        <option value="Warehouse" selected>Warehouse</option>
                        <option value="Project Site">Project Site</option>
                    </select>
                </div>
                <div class="unit-field unit-field-address">
                    <label>Current Address</label>
                    <div class="equipment-address-group equipment-address-project">
                        <select name="__INDEX__[address]" class="equipment-address-select">
                            <option value="">Select project site</option>
                            <?php if (!empty($projectSites)): ?>
                                <?php foreach ($projectSites as $site): ?>
                                    <option value="<?= esc((string)($site['value'] ?? '')) ?>"><?= esc((string)($site['label'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">No project sites found</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="equipment-address-group equipment-address-warehouse">
                        <div class="unit-warehouse-input-wrap">
                            <input type="text" name="__INDEX__[address]" class="equipment-warehouse-input" placeholder="Type warehouse location or pick on map" autocomplete="off">
                            <button type="button" class="equipment-map-btn">Map</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="unit-qty-row">
                <span class="unit-qty-label">Qty</span>
                <div class="qty-stepper unit-qty-stepper">
                    <button type="button" class="qty-btn unit-qty-minus">&#8722;</button>
                    <input type="number" name="__INDEX__[quantity]" class="qty-input unit-quantity-input" value="1" min="1">
                    <button type="button" class="qty-btn unit-qty-plus">+</button>
                </div>
            </div>
            <div class="unit-card-footer">
                <button type="button" class="equipment-remove-btn" onclick="removeEquipmentRow(this)">Remove Unit</button>
            </div>
        </div>
    </div>
</template>

<div id="equipmentMapModal" class="equipment-map-modal" aria-hidden="true">
    <div class="equipment-map-backdrop"></div>
    <div class="equipment-map-dialog">
        <div class="equipment-map-header">
            <div>
                <h2>Select Warehouse Location</h2>
                <p>Click on the map to fill the current warehouse location.</p>
            </div>
            <button type="button" class="equipment-map-close" id="equipmentMapClose">×</button>
        </div>
        <input type="text" id="equipmentMapSearch" class="equipment-map-search" placeholder="Search a location or type an address">
        <div id="equipmentMapCanvas" class="equipment-map-canvas"></div>
        <div class="equipment-map-footer">
            <button type="button" class="btn btn-secondary" id="equipmentMapUseCurrent">Use Current Address</button>
            <button type="button" class="btn btn-cancel-mobile" id="equipmentMapCancel">Cancel</button>
        </div>
    </div>
</div>

<script>
var _eqMap = null, _eqMarker = null, _eqAuto = null;
window.initEquipmentMap = function () { /* Maps API ready; map created lazily on modal open */ };

var warehouseDefault = <?= json_encode($defaultWarehouseAddress) ?>;
window.whSavedLocations  = <?= json_encode($savedWarehouseLocations ?? []) ?>;
window.whOtherLocations  = <?= json_encode(array_column($warehouseLocations ?? [], 'value')) ?>;
window.whSaveUrl         = '<?= base_url('resources/warehouse-saved-location') ?>';
window.whDeleteUrl       = '<?= base_url('resources/warehouse-saved-location-delete') ?>';

document.addEventListener('DOMContentLoaded', function () {
    var checkbox  = document.getElementById('add_specific_units_now');
    var section   = document.getElementById('equipmentUnitsSection');
    var addBtn    = document.getElementById('addEquipmentUnitBtn');
    var container = document.getElementById('equipmentItemsContainer');
    var template  = document.getElementById('equipmentItemTemplate');
    var mapModal  = document.getElementById('equipmentMapModal');
    var mapClose  = document.getElementById('equipmentMapClose');
    var mapCancel = document.getElementById('equipmentMapCancel');
    var mapUseCurrent = document.getElementById('equipmentMapUseCurrent');
    var mapSearch = document.getElementById('equipmentMapSearch');

    var rowIndex = container.querySelectorAll('.unit-card').length;
    var nextSerialIndex = rowIndex + 1;
    var activeWarehouseInput = null;
    var mapPendingAddress = '';

    function generatePrefix(name) {
        var words = name.toUpperCase().replace(/[^A-Z0-9 ]/g, ' ').trim().split(/\s+/).filter(Boolean);
        if (words.length === 0) return '';
        if (words.length === 1) return words[0].substring(0, 3);
        if (words.length === 2) return words[0].substring(0, 3) + '_' + words[1];
        return words[0].substring(0, 3) + '_' + words[1] + words[2];
    }

    function generateSerialRange(name, startIdx, qty) {
        var prefix = generatePrefix(name);
        if (!prefix) return '';
        var start = String(startIdx).padStart(3, '0');
        if (qty <= 1) return prefix + '-' + start;
        return prefix + '-' + start + '-' + String(startIdx + qty - 1).padStart(3, '0');
    }

    function recalculateSerialIndices() {
        var name = (document.getElementById('name') || {}).value || '';
        var allCards = container.querySelectorAll('.unit-card');
        var currentIdx = 1;
        allCards.forEach(function (card, pos) {
            var qtyInput = card.querySelector('.unit-quantity-input');
            var qty = Math.max(1, parseInt((qtyInput && qtyInput.value) || 1, 10) || 1);
            card.dataset.serialIndex = currentIdx;
            var si = card.querySelector('.unit-serial-input');
            if (si && si.dataset.autoSerial === 'true') {
                si.value = name ? generateSerialRange(name, currentIdx, qty) : '';
            }
            var titleEl = card.querySelector('.unit-card-title');
            if (titleEl) {
                var serial = (si && si.value) || '';
                titleEl.textContent = 'Unit #' + (pos + 1) + (serial ? ' — ' + serial : '');
            }
            currentIdx += qty;
        });
        nextSerialIndex = currentIdx;
    }

    function setSectionVisible(visible) {
        section.classList.toggle('open', visible);
        section.querySelectorAll('input, select, button').forEach(function (field) {
            if (field === addBtn) return;
            field.disabled = !visible;
        });
        addBtn.disabled = !visible;
    }

    function reindexCards() {
        container.querySelectorAll('.unit-card').forEach(function (card, index) {
            card.querySelectorAll('input[name], select[name]').forEach(function (field) {
                field.name = field.name.replace(/items\[\d+\]/, 'items[' + index + ']');
            });
            var titleEl = card.querySelector('.unit-card-title');
            if (titleEl) {
                var serial = (card.querySelector('.unit-serial-input') || {}).value || '';
                titleEl.textContent = 'Unit #' + (index + 1) + (serial ? ' — ' + serial : '');
            }
        });
    }

    function syncLocationFields(card) {
        var typeSelect    = card.querySelector('.equipment-location-type');
        var projectWrap   = card.querySelector('.equipment-address-project');
        var warehouseWrap = card.querySelector('.equipment-address-warehouse');
        var projectSelect = card.querySelector('.equipment-address-select');
        var warehouseInput = card.querySelector('.equipment-warehouse-input');
        var currentType = (typeSelect && typeSelect.value) || 'Warehouse';

        if (currentType === 'Project Site') {
            card.dataset.warehouseValue = (warehouseInput && warehouseInput.value) || card.dataset.warehouseValue || '';
            projectWrap.style.display = 'block';
            warehouseWrap.style.display = 'none';
            if (projectSelect) projectSelect.disabled = false;
            if (warehouseInput) warehouseInput.disabled = true;
            if (projectSelect && card.dataset.projectValue) projectSelect.value = card.dataset.projectValue;
        } else {
            card.dataset.projectValue = (projectSelect && projectSelect.value) || card.dataset.projectValue || '';
            projectWrap.style.display = 'none';
            warehouseWrap.style.display = 'block';
            if (projectSelect) projectSelect.disabled = true;
            if (warehouseInput) {
                warehouseInput.disabled = false;
                if (card.dataset.warehouseValue) warehouseInput.value = card.dataset.warehouseValue;
            }
        }
    }

    function bindCard(card) {
        var typeSelect     = card.querySelector('.equipment-location-type');
        var projectSelect  = card.querySelector('.equipment-address-select');
        var warehouseInput = card.querySelector('.equipment-warehouse-input');
        var mapBtn         = card.querySelector('.equipment-map-btn');
        var serialInput    = card.querySelector('.unit-serial-input');
        var titleEl        = card.querySelector('.unit-card-title');

        if (serialInput && titleEl) {
            serialInput.addEventListener('input', function () {
                this.removeAttribute('data-auto-serial');
                var idx = Array.from(container.querySelectorAll('.unit-card')).indexOf(card) + 1;
                titleEl.textContent = 'Unit #' + idx + (this.value ? ' — ' + this.value : '');
            });
        }

        var qtyMinus = card.querySelector('.unit-qty-minus');
        var qtyPlus  = card.querySelector('.unit-qty-plus');
        var qtyInput = card.querySelector('.unit-quantity-input');
        if (qtyMinus && qtyInput) {
            qtyMinus.addEventListener('click', function () {
                var v = Math.max(1, parseInt(qtyInput.value, 10) || 1);
                if (v > 1) { qtyInput.value = v - 1; recalculateSerialIndices(); }
            });
        }
        if (qtyPlus && qtyInput) {
            qtyPlus.addEventListener('click', function () {
                qtyInput.value = (parseInt(qtyInput.value, 10) || 1) + 1;
                recalculateSerialIndices();
            });
        }
        if (qtyInput) {
            qtyInput.addEventListener('change', function () {
                var v = parseInt(this.value, 10);
                if (isNaN(v) || v < 1) this.value = 1;
                recalculateSerialIndices();
            });
        }

        if (typeSelect) {
            typeSelect.addEventListener('change', function () {
                card.dataset.projectValue   = (projectSelect  && projectSelect.value)  || card.dataset.projectValue   || '';
                card.dataset.warehouseValue = (warehouseInput && warehouseInput.value) || card.dataset.warehouseValue || '';
                syncLocationFields(card);
            });
        }

        if (projectSelect)  projectSelect.addEventListener('change',  function () { card.dataset.projectValue   = this.value; });
        if (warehouseInput) warehouseInput.addEventListener('input',   function () { card.dataset.warehouseValue = this.value; });

        if (mapBtn) {
            mapBtn.addEventListener('click', function () {
                activeWarehouseInput = warehouseInput || null;
                mapPendingAddress = (activeWarehouseInput && activeWarehouseInput.value) || '';
                openMapModal();
            });
        }

        if (typeSelect && !typeSelect.value) typeSelect.value = 'Warehouse';
        syncLocationFields(card);

        var wi = card.querySelector('.equipment-warehouse-input');
        if (wi && !wi.value && !card.dataset.warehouseValue) {
            var firstSaved = (window.whSavedLocations || [])[0];
            var autoAddr   = firstSaved ? firstSaved.address : (warehouseDefault || '');
            if (autoAddr) {
                wi.value = autoAddr;
                card.dataset.warehouseValue = autoAddr;
            }
        }

        if (wi && typeof window.initWarehousePicker === 'function') {
            window.initWarehousePicker(wi, card);
        }
    }

    window.toggleUnit = function (header) {
        var card = header.closest('.unit-card');
        if (card) card.classList.toggle('collapsed');
    };

    function openMapModal() {
        mapModal.classList.add('open');
        mapModal.setAttribute('aria-hidden', 'false');
        mapSearch.value = mapPendingAddress;
        setTimeout(function () {
            ensureMap();
            if (_eqMap) google.maps.event.trigger(_eqMap, 'resize');
        }, 50);
    }

    function closeMapModal() {
        mapModal.classList.remove('open');
        mapModal.setAttribute('aria-hidden', 'true');
    }

    function reverseGeocode(lat, lng) {
        fetch('<?= base_url('maps/reverse') ?>?lat=' + lat + '&lng=' + lng)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status === 'OK') {
                    mapPendingAddress = data.formatted_address;
                    mapSearch.value   = data.formatted_address;
                }
            })
            .catch(function () {});
    }

    function serverGeocode(address) {
        if (!address.trim()) return;
        fetch('<?= base_url('maps/geocode') ?>?address=' + encodeURIComponent(address))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status === 'OK') {
                    var latLng = { lat: data.lat, lng: data.lng };
                    _eqMap.setCenter(latLng);
                    _eqMap.setZoom(15);
                    placeMarker(latLng);
                    mapPendingAddress = data.formatted_address;
                    mapSearch.value   = data.formatted_address;
                }
            })
            .catch(function () {});
    }

    function placeMarker(latLng) {
        _eqMarker.setAnimation(null);
        _eqMarker.setPosition(latLng);
        _eqMarker.setMap(_eqMap);
        setTimeout(function () { _eqMarker.setAnimation(google.maps.Animation.DROP); }, 10);
    }

    function ensureMap() {
        if (_eqMap || typeof google === 'undefined') return;
        _eqMap = new google.maps.Map(document.getElementById('equipmentMapCanvas'), {
            center: { lat: 14.5995, lng: 120.9842 },
            zoom: 11,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
        });
        _eqMarker = new google.maps.Marker({
            map: null,
            draggable: false,
            title: 'Selected location',
        });
        _eqMap.addListener('click', function (event) {
            placeMarker(event.latLng);
            reverseGeocode(event.latLng.lat(), event.latLng.lng());
        });
        _eqAuto = new google.maps.places.Autocomplete(mapSearch, {
            types: ['geocode', 'establishment'],
            componentRestrictions: { country: 'ph' },
        });
        _eqAuto.bindTo('bounds', _eqMap);
        _eqAuto.addListener('place_changed', function () {
            var place = _eqAuto.getPlace();
            if (!place.geometry || !place.geometry.location) {
                serverGeocode(mapSearch.value);
                return;
            }
            var lat = place.geometry.location.lat();
            var lng = place.geometry.location.lng();
            var latLng = { lat: lat, lng: lng };
            _eqMap.setCenter(latLng);
            _eqMap.setZoom(15);
            placeMarker(latLng);
            mapPendingAddress = place.formatted_address || mapSearch.value;
            mapSearch.value   = mapPendingAddress;
        });
    }

    addBtn && addBtn.addEventListener('click', function () {
        var addFlowQtyEl = document.getElementById('addFlowQty');
        var addCount = Math.max(1, parseInt((addFlowQtyEl && addFlowQtyEl.value) || 1, 10) || 1);
        for (var i = 0; i < addCount; i++) {
            var html = template.innerHTML.replace(/__INDEX__/g, 'items[' + rowIndex + ']');
            container.insertAdjacentHTML('beforeend', html);
            rowIndex++;
            setSectionVisible(true);
            var cards = container.querySelectorAll('.unit-card');
            var newCard = cards[cards.length - 1];
            var si = newCard.querySelector('.unit-serial-input');
            if (si) si.dataset.autoSerial = 'true';
            bindCard(newCard);
        }
        recalculateSerialIndices();
        reindexCards();
    });

    window.removeEquipmentRow = function (button) {
        var card = button.closest('.unit-card');
        if (!card) return;
        card.remove();
        reindexCards();
        recalculateSerialIndices();
        if (container.querySelectorAll('.unit-card').length === 0) {
            checkbox.checked = false;
            setSectionVisible(false);
        }
    };

    var addFlowQtyEl = document.getElementById('addFlowQty');
    var addFlowMinus = document.getElementById('addFlowQtyMinus');
    var addFlowPlus  = document.getElementById('addFlowQtyPlus');
    addFlowMinus && addFlowMinus.addEventListener('click', function () {
        var v = Math.max(1, parseInt(addFlowQtyEl.value, 10) || 1);
        if (v > 1) addFlowQtyEl.value = v - 1;
    });
    addFlowPlus && addFlowPlus.addEventListener('click', function () {
        addFlowQtyEl.value = (parseInt(addFlowQtyEl.value, 10) || 1) + 1;
    });
    addFlowQtyEl && addFlowQtyEl.addEventListener('change', function () {
        var v = parseInt(this.value, 10);
        if (isNaN(v) || v < 1) this.value = 1;
    });

    checkbox && checkbox.addEventListener('change', function () { setSectionVisible(this.checked); });

    mapClose  && mapClose.addEventListener('click',  closeMapModal);
    mapCancel && mapCancel.addEventListener('click', closeMapModal);
    var backdrop = mapModal && mapModal.querySelector('.equipment-map-backdrop');
    if (backdrop) backdrop.addEventListener('click', closeMapModal);

    mapUseCurrent && mapUseCurrent.addEventListener('click', function () {
        if (activeWarehouseInput) {
            activeWarehouseInput.value = mapPendingAddress || activeWarehouseInput.value;
            var card = activeWarehouseInput.closest('.unit-card');
            if (card) card.dataset.warehouseValue = activeWarehouseInput.value;
        }
        closeMapModal();
    });

    mapSearch && mapSearch.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (_eqMap && this.value.trim()) serverGeocode(this.value.trim());
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && mapModal.classList.contains('open')) closeMapModal();
    });

    var resourceNameInput = document.getElementById('name');
    if (resourceNameInput) {
        resourceNameInput.addEventListener('input', function () {
            recalculateSerialIndices();
        });
    }

    container.querySelectorAll('.unit-card').forEach(bindCard);
    recalculateSerialIndices();
    setSectionVisible(checkbox ? checkbox.checked : false);

    // ── Warehouse default banner ────────────────────────────────
    var wdbEditBtn  = document.getElementById('wdbEditBtn');
    var wdbSaveBtn  = document.getElementById('wdbSaveBtn');
    var wdbCancelBtn= document.getElementById('wdbCancelBtn');
    var wdbMapBtn   = document.getElementById('wdbMapBtn');
    var wdbStatic   = document.getElementById('wdbStatic');
    var wdbEditForm = document.getElementById('wdbEditForm');
    var wdbInput    = document.getElementById('wdbInput');
    var wdbAddress  = document.getElementById('wdbAddress');

    if (wdbMapBtn) {
        wdbMapBtn.addEventListener('click', function () {
            activeWarehouseInput = wdbInput;
            mapPendingAddress = (wdbInput && wdbInput.value.trim()) || '';
            openMapModal();
        });
    }

    if (wdbEditBtn) {
        wdbEditBtn.addEventListener('click', function () {
            wdbStatic.style.display = 'none';
            wdbEditForm.style.display = 'flex';
            wdbInput.value = warehouseDefault;
            wdbEditBtn.style.display = 'none';
            wdbInput.focus();
        });
    }

    if (wdbCancelBtn) {
        wdbCancelBtn.addEventListener('click', function () {
            wdbStatic.style.display = '';
            wdbEditForm.style.display = 'none';
            wdbEditBtn.style.display = '';
        });
    }

    if (wdbSaveBtn) {
        wdbSaveBtn.addEventListener('click', function () {
            var newAddr = (wdbInput && wdbInput.value.trim()) || '';
            if (!newAddr) { wdbInput && wdbInput.focus(); return; }
            wdbSaveBtn.disabled = true;
            wdbSaveBtn.textContent = 'Saving…';
            var csrfName = '<?= csrf_token() ?>';
            var csrfEl = document.querySelector('input[name="' + csrfName + '"]');
            var fd = new FormData();
            fd.append('warehouse_default_address', newAddr);
            if (csrfEl) fd.append(csrfName, csrfEl.value);
            fetch('<?= base_url('resources/warehouse-default') ?>', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        warehouseDefault = data.address;
                        wdbAddress.textContent = data.address;
                        wdbAddress.className = 'wdb-address';
                        wdbStatic.style.display = '';
                        wdbEditForm.style.display = 'none';
                        wdbEditBtn.style.display = '';
                    } else {
                        alert('Could not save: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(function () { alert('Network error — warehouse address not saved.'); })
                .finally(function () { wdbSaveBtn.disabled = false; wdbSaveBtn.textContent = 'Save'; });
        });
    }
});
</script>

<?php if ($mapsKey): ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= esc($mapsKey) ?>&libraries=places&callback=initEquipmentMap" async defer></script>
<?php else: ?>
<script>
if (window.location.hostname === 'localhost') {
    console.warn('[PrimaZentra] GOOGLE_MAPS_BROWSER_KEY is not set. Equipment map picker is disabled.');
}
</script>
<?php endif; ?>

<?= $this->endSection() ?>
