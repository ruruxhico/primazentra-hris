<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $resource = $resource ?? [];
    $asset = $resource['asset_details'] ?? [];
    $items = $items ?? [];
    $projectSites = $projectSites ?? [];
    $warehouseLocations = $warehouseLocations ?? [];
    $isEdit = !empty($resource['id'] ?? null);
    $formAction = $isEdit ? base_url('resources/update/' . $resource['id']) : base_url('resources/store');
    $hasItems = !empty($items);
    $addSpecificUnitsNow = (bool) ($asset['add_specific_units_now'] ?? $hasItems);
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<header class="resource-header">
    <div class="equipment-topbar">
        <a href="<?= base_url('resources') ?>" class="equipment-back" aria-label="Back to resources">←</a>
        <div>
            <h1 class="equipment-title"><?= $isEdit ? 'Edit Tool Model' : 'Add Tool Model' ?></h1>
            <p class="equipment-subtitle">Register a new type of tool. You can add specific physical units after creating this profile.</p>
        </div>
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

            <div class="equipment-field">
                <label for="model_number">Model Number / Brand</label>
                <input type="text" id="model_number" name="model_number" placeholder="e.g. Makita DHP482" value="<?= esc((string)($asset['model_number'] ?? '')) ?>">
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

                <div class="equipment-items-wrap">
                    <table class="equipment-items-table" id="equipmentItemsTable">
                        <thead>
                            <tr>
                                <th>Serial Number</th>
                                <th>Condition</th>
                                <th>Status</th>
                                <th>Location Type</th>
                                <th>Current Address</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="equipmentItemsTbody">
                            <?php if (!empty($items) && is_array($items)): ?>
                                <?php foreach ($items as $index => $item): ?>
                                    <?php
                                        $location = is_array($item['location'] ?? null) ? $item['location'] : [];
                                        $locationType = (string) ($location['type'] ?? 'Warehouse');
                                        $currentAddress = (string) ($location['address'] ?? '');
                                    ?>
                                    <tr class="equipment-item-row" data-warehouse-value="<?= esc($currentAddress) ?>" data-project-value="<?= esc($currentAddress) ?>">
                                        <td>
                                            <input type="text" name="items[<?= $index ?>][serial_number]" value="<?= esc((string)($item['serial_number'] ?? '')) ?>" placeholder="SN-12345">
                                        </td>
                                        <td>
                                            <select name="items[<?= $index ?>][condition]">
                                                <option value="">Select</option>
                                                <?php foreach (['New', 'Functional', 'Worn', 'Broken'] as $conditionOption): ?>
                                                    <option value="<?= esc($conditionOption) ?>" <?= (($item['condition'] ?? '') === $conditionOption) ? 'selected' : '' ?>><?= esc($conditionOption) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="items[<?= $index ?>][status]">
                                                <option value="">Select</option>
                                                <?php foreach (['In-Storage', 'In-Use', 'Repair'] as $statusOption): ?>
                                                    <option value="<?= esc($statusOption) ?>" <?= (($item['status'] ?? '') === $statusOption) ? 'selected' : '' ?>><?= esc($statusOption) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="items[<?= $index ?>][location_type]" class="equipment-location-type">
                                                <option value="">Select Type</option>
                                                <option value="Warehouse" <?= $locationType === 'Warehouse' ? 'selected' : '' ?>>Warehouse</option>
                                                <option value="Project Site" <?= $locationType === 'Project Site' ? 'selected' : '' ?>>Project Site</option>
                                            </select>
                                        </td>
                                        <td>
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
                                                <div class="equipment-warehouse-input-wrap">
                                                    <input type="text" name="items[<?= $index ?>][address]" class="equipment-warehouse-input" list="warehouseLocationSuggestions" value="<?= esc($currentAddress) ?>" placeholder="Type warehouse location or pick on map">
                                                    <button type="button" class="equipment-map-btn">Map</button>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <button type="button" class="equipment-remove-btn" onclick="removeEquipmentRow(this)">Remove</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="equipment-actions-row">
                    <button type="button" class="btn btn-secondary" id="addEquipmentUnitBtn">+ Add Unit</button>
                </div>
            </div>

            <div class="equipment-actions">
                <button type="submit" class="btn btn-save-mobile"><?= $isEdit ? 'Save Model' : 'Save Model' ?></button>
                <a href="<?= base_url('resources') ?>" class="btn btn-cancel-mobile">Cancel</a>
            </div>
        </form>
    </div>
</div>

<datalist id="warehouseLocationSuggestions">
    <?php foreach ($warehouseLocations as $warehouse): ?>
        <option value="<?= esc((string)($warehouse['value'] ?? '')) ?>"></option>
    <?php endforeach; ?>
</datalist>

<template id="equipmentItemTemplate">
    <tr class="equipment-item-row" data-warehouse-value="" data-project-value="">
        <td><input type="text" name="__INDEX__[serial_number]" placeholder="SN-12345"></td>
        <td>
            <select name="__INDEX__[condition]">
                <option value="">Select</option>
                <option value="New">New</option>
                <option value="Functional">Functional</option>
                <option value="Worn">Worn</option>
                <option value="Broken">Broken</option>
            </select>
        </td>
        <td>
            <select name="__INDEX__[status]">
                <option value="">Select</option>
                <option value="In-Storage">In-Storage</option>
                <option value="In-Use">In-Use</option>
                <option value="Repair">Repair</option>
            </select>
        </td>
        <td>
            <select name="__INDEX__[location_type]" class="equipment-location-type">
                <option value="">Select Type</option>
                <option value="Warehouse">Warehouse</option>
                <option value="Project Site">Project Site</option>
            </select>
        </td>
        <td>
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
                <div class="equipment-warehouse-input-wrap">
                    <input type="text" name="__INDEX__[address]" class="equipment-warehouse-input" list="warehouseLocationSuggestions" placeholder="Type warehouse location or pick on map">
                    <button type="button" class="equipment-map-btn">Map</button>
                </div>
            </div>
        </td>
        <td><button type="button" class="equipment-remove-btn" onclick="removeEquipmentRow(this)">Remove</button></td>
    </tr>
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
document.addEventListener('DOMContentLoaded', function () {
    const checkbox = document.getElementById('add_specific_units_now');
    const section = document.getElementById('equipmentUnitsSection');
    const addBtn = document.getElementById('addEquipmentUnitBtn');
    const tbody = document.getElementById('equipmentItemsTbody');
    const template = document.getElementById('equipmentItemTemplate');
    const mapModal = document.getElementById('equipmentMapModal');
    const mapClose = document.getElementById('equipmentMapClose');
    const mapCancel = document.getElementById('equipmentMapCancel');
    const mapUseCurrent = document.getElementById('equipmentMapUseCurrent');
    const mapSearch = document.getElementById('equipmentMapSearch');

    let rowIndex = tbody.querySelectorAll('.equipment-item-row').length;
    let mapInstance = null;
    let mapMarker = null;
    let activeWarehouseInput = null;
    let mapPendingAddress = '';

    function setSectionVisible(visible) {
        section.classList.toggle('open', visible);
        section.querySelectorAll('input, select, button').forEach(field => {
            if (field === addBtn) return;
            field.disabled = !visible;
        });
        addBtn.disabled = !visible;
    }

    function reindexRows() {
        tbody.querySelectorAll('.equipment-item-row').forEach((row, index) => {
            row.querySelectorAll('input, select').forEach(field => {
                field.name = field.name.replace(/__INDEX__/g, 'items[' + index + ']');
            });
        });
    }

    function syncLocationFields(row) {
        const typeSelect = row.querySelector('.equipment-location-type');
        const projectWrap = row.querySelector('.equipment-address-project');
        const warehouseWrap = row.querySelector('.equipment-address-warehouse');
        const projectSelect = row.querySelector('.equipment-address-select');
        const warehouseInput = row.querySelector('.equipment-warehouse-input');
        const currentType = typeSelect?.value || 'Warehouse';

        if (currentType === 'Project Site') {
            row.dataset.warehouseValue = warehouseInput?.value || row.dataset.warehouseValue || '';
            projectWrap.style.display = 'block';
            warehouseWrap.style.display = 'none';
            if (projectSelect) projectSelect.disabled = false;
            if (warehouseInput) warehouseInput.disabled = true;
            if (projectSelect && row.dataset.projectValue) {
                projectSelect.value = row.dataset.projectValue;
            }
        } else {
            row.dataset.projectValue = projectSelect?.value || row.dataset.projectValue || '';
            projectWrap.style.display = 'none';
            warehouseWrap.style.display = 'block';
            if (projectSelect) projectSelect.disabled = true;
            if (warehouseInput) {
                warehouseInput.disabled = false;
                if (row.dataset.warehouseValue) {
                    warehouseInput.value = row.dataset.warehouseValue;
                }
            }
        }
    }

    function bindRow(row) {
        const typeSelect = row.querySelector('.equipment-location-type');
        const projectSelect = row.querySelector('.equipment-address-select');
        const warehouseInput = row.querySelector('.equipment-warehouse-input');
        const mapBtn = row.querySelector('.equipment-map-btn');

        if (typeSelect) {
            typeSelect.addEventListener('change', function () {
                row.dataset.projectValue = projectSelect?.value || row.dataset.projectValue || '';
                row.dataset.warehouseValue = warehouseInput?.value || row.dataset.warehouseValue || '';
                syncLocationFields(row);
            });
        }

        if (projectSelect) {
            projectSelect.addEventListener('change', function () {
                row.dataset.projectValue = this.value;
            });
        }

        if (warehouseInput) {
            warehouseInput.addEventListener('input', function () {
                row.dataset.warehouseValue = this.value;
            });
        }

        mapBtn?.addEventListener('click', function () {
            activeWarehouseInput = warehouseInput || null;
            mapPendingAddress = activeWarehouseInput?.value || '';
            openMapModal();
        });

        if (!typeSelect?.value) {
            typeSelect.value = 'Warehouse';
        }

        syncLocationFields(row);
    }

    function openMapModal() {
        mapModal.classList.add('open');
        mapModal.setAttribute('aria-hidden', 'false');
        mapSearch.value = mapPendingAddress;
        setTimeout(() => {
            if (mapInstance) {
                mapInstance.invalidateSize();
            }
        }, 50);
    }

    function closeMapModal() {
        mapModal.classList.remove('open');
        mapModal.setAttribute('aria-hidden', 'true');
    }

    async function reverseGeocode(latlng) {
        const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(latlng.lat)}&lon=${encodeURIComponent(latlng.lng)}`;
        try {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();
            return data.display_name || `${latlng.lat.toFixed(6)}, ${latlng.lng.toFixed(6)}`;
        } catch (error) {
            return `${latlng.lat.toFixed(6)}, ${latlng.lng.toFixed(6)}`;
        }
    }

    function ensureMap() {
        if (mapInstance || typeof L === 'undefined') {
            return;
        }

        mapInstance = L.map('equipmentMapCanvas').setView([14.5995, 120.9842], 11);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(mapInstance);

        mapInstance.on('click', async function (event) {
            const address = await reverseGeocode(event.latlng);
            mapPendingAddress = address;
            if (mapMarker) {
                mapMarker.setLatLng(event.latlng);
            } else {
                mapMarker = L.marker(event.latlng).addTo(mapInstance);
            }
        });
    }

    addBtn?.addEventListener('click', function () {
        const html = template.innerHTML.replace(/__INDEX__/g, 'items[' + rowIndex + ']');
        tbody.insertAdjacentHTML('beforeend', html);
        rowIndex++;
        setSectionVisible(true);
        const newRow = tbody.querySelectorAll('.equipment-item-row')[tbody.querySelectorAll('.equipment-item-row').length - 1];
        bindRow(newRow);
        reindexRows();
    });

    window.removeEquipmentRow = function (button) {
        const row = button.closest('.equipment-item-row');
        if (!row) return;
        row.remove();
        reindexRows();
        if (tbody.querySelectorAll('.equipment-item-row').length === 0) {
            checkbox.checked = false;
            setSectionVisible(false);
        }
    };

    checkbox?.addEventListener('change', function () {
        setSectionVisible(this.checked);
    });

    mapClose?.addEventListener('click', closeMapModal);
    mapCancel?.addEventListener('click', closeMapModal);
    mapModal?.querySelector('.equipment-map-backdrop')?.addEventListener('click', closeMapModal);

    mapUseCurrent?.addEventListener('click', function () {
        if (activeWarehouseInput) {
            activeWarehouseInput.value = mapPendingAddress || activeWarehouseInput.value;
            const row = activeWarehouseInput.closest('.equipment-item-row');
            if (row) {
                row.dataset.warehouseValue = activeWarehouseInput.value;
            }
        }
        closeMapModal();
    });

    mapSearch?.addEventListener('change', function () {
        mapPendingAddress = this.value.trim();
        if (activeWarehouseInput) {
            activeWarehouseInput.value = mapPendingAddress;
            const row = activeWarehouseInput.closest('.equipment-item-row');
            if (row) {
                row.dataset.warehouseValue = activeWarehouseInput.value;
            }
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && mapModal.classList.contains('open')) {
            closeMapModal();
        }
    });

    const existingRows = tbody.querySelectorAll('.equipment-item-row');
    existingRows.forEach(bindRow);
    setSectionVisible(checkbox?.checked || false);
    ensureMap();
});
</script>

<?= $this->endSection() ?>
