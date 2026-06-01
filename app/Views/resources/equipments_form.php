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
</style>

<header class="resource-header">
    <div class="equipment-topbar">
        <a href="<?= base_url('resources') ?>" class="equipment-back" aria-label="Back to resources">←</a>
        <div>
            <h1 class="equipment-title"><?= $isEdit ? 'Edit Equipment Model' : 'Add Equipment Model' ?></h1>
            <p class="equipment-subtitle">Register a new type of equipment. You can add specific physical units after creating this profile.</p>
        </div>
    </div>
</header>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>

<div class="equipment-shell">
    <div class="equipment-card">
        <form method="POST" action="<?= $formAction ?>" id="equipmentForm">
            <?= csrf_field() ?>
            <input type="hidden" name="category" value="Equipment">
            <input type="hidden" name="status" value="<?= esc((string)($resource['status'] ?? 'Available')) ?>">
            <input type="hidden" name="next_availability" value="<?= esc((string)($resource['next_availability'] ?? '')) ?>">
            <input type="hidden" name="location_address" value="<?= esc((string)($resource['location']['address'] ?? '')) ?>">
            <input type="hidden" name="location_type" value="<?= esc((string)($resource['location']['type'] ?? '')) ?>">
            <input type="hidden" name="asset_status" value="<?= esc((string)($asset['status'] ?? 'In-Storage')) ?>">
            <input type="hidden" name="asset_assigned_project_id" value="<?= esc((string)($asset['assigned_project_id'] ?? '')) ?>">

            <div class="equipment-field">
                <label for="name">Equipment Name *</label>
                <input type="text" id="name" name="name" placeholder="e.g., Tower Crane, Concrete Mixer" value="<?= esc((string)($resource['name'] ?? '')) ?>" required>
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
                    <p>Add the physical units tied to this equipment model.</p>
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
                                            <label>Model Number / Brand</label>
                                            <input type="text" name="items[<?= $index ?>][model_number]" value="<?= esc((string)($item['model_number'] ?? '')) ?>" placeholder="e.g. CAT-950GC">
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
                                                    <input type="text" name="items[<?= $index ?>][address]" class="equipment-warehouse-input" list="warehouseLocationSuggestions" value="<?= esc($currentAddress) ?>" placeholder="Type warehouse location or pick on map">
                                                    <button type="button" class="equipment-map-btn">Map</button>
                                                </div>
                                            </div>
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
                    <button type="button" class="btn btn-secondary" id="addEquipmentUnitBtn">+ Add Unit</button>
                </div>
            </div>

            <div class="equipment-actions">
                <button type="submit" class="btn btn-save-mobile">Save Model</button>
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
                    <label>Model Number / Brand</label>
                    <input type="text" name="__INDEX__[model_number]" placeholder="e.g. CAT-950GC">
                </div>
                <div class="unit-field">
                    <label>Condition</label>
                    <select name="__INDEX__[condition]">
                        <option value="">Select</option>
                        <option value="New">New</option>
                        <option value="Functional">Functional</option>
                        <option value="Worn">Worn</option>
                        <option value="Broken">Broken</option>
                    </select>
                </div>
                <div class="unit-field">
                    <label>Status</label>
                    <select name="__INDEX__[status]">
                        <option value="">Select</option>
                        <option value="In-Storage">In-Storage</option>
                        <option value="In-Use">In-Use</option>
                        <option value="Repair">Repair</option>
                    </select>
                </div>
                <div class="unit-field">
                    <label>Location Type</label>
                    <select name="__INDEX__[location_type]" class="equipment-location-type">
                        <option value="">Select Type</option>
                        <option value="Warehouse">Warehouse</option>
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
                            <input type="text" name="__INDEX__[address]" class="equipment-warehouse-input" list="warehouseLocationSuggestions" placeholder="Type warehouse location or pick on map">
                            <button type="button" class="equipment-map-btn">Map</button>
                        </div>
                    </div>
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
document.addEventListener('DOMContentLoaded', function () {
    const checkbox  = document.getElementById('add_specific_units_now');
    const section   = document.getElementById('equipmentUnitsSection');
    const addBtn    = document.getElementById('addEquipmentUnitBtn');
    const container = document.getElementById('equipmentItemsContainer');
    const template  = document.getElementById('equipmentItemTemplate');
    const mapModal  = document.getElementById('equipmentMapModal');
    const mapClose  = document.getElementById('equipmentMapClose');
    const mapCancel = document.getElementById('equipmentMapCancel');
    const mapUseCurrent = document.getElementById('equipmentMapUseCurrent');
    const mapSearch = document.getElementById('equipmentMapSearch');

    let rowIndex = container.querySelectorAll('.unit-card').length;
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

    function reindexCards() {
        container.querySelectorAll('.unit-card').forEach((card, index) => {
            card.querySelectorAll('input[name], select[name]').forEach(field => {
                field.name = field.name.replace(/items\[\d+\]/, 'items[' + index + ']');
            });
            const titleEl = card.querySelector('.unit-card-title');
            if (titleEl) {
                const serial = card.querySelector('.unit-serial-input')?.value || '';
                titleEl.textContent = 'Unit #' + (index + 1) + (serial ? ' — ' + serial : '');
            }
        });
    }

    function syncLocationFields(card) {
        const typeSelect    = card.querySelector('.equipment-location-type');
        const projectWrap   = card.querySelector('.equipment-address-project');
        const warehouseWrap = card.querySelector('.equipment-address-warehouse');
        const projectSelect = card.querySelector('.equipment-address-select');
        const warehouseInput = card.querySelector('.equipment-warehouse-input');
        const currentType = typeSelect?.value || 'Warehouse';

        if (currentType === 'Project Site') {
            card.dataset.warehouseValue = warehouseInput?.value || card.dataset.warehouseValue || '';
            projectWrap.style.display = 'block';
            warehouseWrap.style.display = 'none';
            if (projectSelect) projectSelect.disabled = false;
            if (warehouseInput) warehouseInput.disabled = true;
            if (projectSelect && card.dataset.projectValue) projectSelect.value = card.dataset.projectValue;
        } else {
            card.dataset.projectValue = projectSelect?.value || card.dataset.projectValue || '';
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
        const typeSelect     = card.querySelector('.equipment-location-type');
        const projectSelect  = card.querySelector('.equipment-address-select');
        const warehouseInput = card.querySelector('.equipment-warehouse-input');
        const mapBtn         = card.querySelector('.equipment-map-btn');
        const serialInput    = card.querySelector('.unit-serial-input');
        const titleEl        = card.querySelector('.unit-card-title');

        if (serialInput && titleEl) {
            serialInput.addEventListener('input', function () {
                const idx = Array.from(container.querySelectorAll('.unit-card')).indexOf(card) + 1;
                titleEl.textContent = 'Unit #' + idx + (this.value ? ' — ' + this.value : '');
            });
        }

        if (typeSelect) {
            typeSelect.addEventListener('change', function () {
                card.dataset.projectValue   = projectSelect?.value  || card.dataset.projectValue   || '';
                card.dataset.warehouseValue = warehouseInput?.value || card.dataset.warehouseValue || '';
                syncLocationFields(card);
            });
        }

        if (projectSelect) {
            projectSelect.addEventListener('change', function () { card.dataset.projectValue = this.value; });
        }

        if (warehouseInput) {
            warehouseInput.addEventListener('input', function () { card.dataset.warehouseValue = this.value; });
        }

        mapBtn?.addEventListener('click', function () {
            activeWarehouseInput = warehouseInput || null;
            mapPendingAddress = activeWarehouseInput?.value || '';
            openMapModal();
        });

        if (!typeSelect?.value) typeSelect.value = 'Warehouse';
        syncLocationFields(card);
    }

    window.toggleUnit = function (header) {
        header.closest('.unit-card')?.classList.toggle('collapsed');
    };

    function openMapModal() {
        mapModal.classList.add('open');
        mapModal.setAttribute('aria-hidden', 'false');
        mapSearch.value = mapPendingAddress;
        setTimeout(() => { if (mapInstance) mapInstance.invalidateSize(); }, 50);
        ensureMap();
    }

    function closeMapModal() {
        mapModal.classList.remove('open');
        mapModal.setAttribute('aria-hidden', 'true');
    }

    async function reverseGeocode(latlng) {
        const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(latlng.lat)}&lon=${encodeURIComponent(latlng.lng)}`;
        try {
            const data = await (await fetch(url, { headers: { 'Accept': 'application/json' } })).json();
            return data.display_name || `${latlng.lat.toFixed(6)}, ${latlng.lng.toFixed(6)}`;
        } catch {
            return `${latlng.lat.toFixed(6)}, ${latlng.lng.toFixed(6)}`;
        }
    }

    function ensureMap() {
        if (mapInstance || typeof L === 'undefined') return;
        mapInstance = L.map('equipmentMapCanvas').setView([14.5995, 120.9842], 11);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'
        }).addTo(mapInstance);
        mapInstance.on('click', async function (event) {
            mapPendingAddress = await reverseGeocode(event.latlng);
            if (mapMarker) mapMarker.setLatLng(event.latlng);
            else mapMarker = L.marker(event.latlng).addTo(mapInstance);
        });
    }

    addBtn?.addEventListener('click', function () {
        const html = template.innerHTML.replace(/__INDEX__/g, 'items[' + rowIndex + ']');
        container.insertAdjacentHTML('beforeend', html);
        rowIndex++;
        setSectionVisible(true);
        const cards = container.querySelectorAll('.unit-card');
        bindCard(cards[cards.length - 1]);
        reindexCards();
    });

    window.removeEquipmentRow = function (button) {
        const card = button.closest('.unit-card');
        if (!card) return;
        card.remove();
        reindexCards();
        if (container.querySelectorAll('.unit-card').length === 0) {
            checkbox.checked = false;
            setSectionVisible(false);
        }
    };

    checkbox?.addEventListener('change', function () { setSectionVisible(this.checked); });

    mapClose?.addEventListener('click', closeMapModal);
    mapCancel?.addEventListener('click', closeMapModal);
    mapModal?.querySelector('.equipment-map-backdrop')?.addEventListener('click', closeMapModal);

    mapUseCurrent?.addEventListener('click', function () {
        if (activeWarehouseInput) {
            activeWarehouseInput.value = mapPendingAddress || activeWarehouseInput.value;
            const card = activeWarehouseInput.closest('.unit-card');
            if (card) card.dataset.warehouseValue = activeWarehouseInput.value;
        }
        closeMapModal();
    });

    mapSearch?.addEventListener('change', function () {
        mapPendingAddress = this.value.trim();
        if (activeWarehouseInput) {
            activeWarehouseInput.value = mapPendingAddress;
            const card = activeWarehouseInput.closest('.unit-card');
            if (card) card.dataset.warehouseValue = activeWarehouseInput.value;
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && mapModal.classList.contains('open')) closeMapModal();
    });

    container.querySelectorAll('.unit-card').forEach(bindCard);
    setSectionVisible(checkbox?.checked || false);
});
</script>

<?= $this->endSection() ?>
