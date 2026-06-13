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
</style>

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
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
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
var _eqMap = null, _eqMarker = null, _eqAuto = null;
window.initEquipmentMap = function () { /* Maps API ready; map created lazily on modal open */ };

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
    var activeWarehouseInput = null;
    var mapPendingAddress = '';

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
                var idx = Array.from(container.querySelectorAll('.unit-card')).indexOf(card) + 1;
                titleEl.textContent = 'Unit #' + idx + (this.value ? ' — ' + this.value : '');
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
        var html = template.innerHTML.replace(/__INDEX__/g, 'items[' + rowIndex + ']');
        container.insertAdjacentHTML('beforeend', html);
        rowIndex++;
        setSectionVisible(true);
        var cards = container.querySelectorAll('.unit-card');
        bindCard(cards[cards.length - 1]);
        reindexCards();
    });

    window.removeEquipmentRow = function (button) {
        var card = button.closest('.unit-card');
        if (!card) return;
        card.remove();
        reindexCards();
        if (container.querySelectorAll('.unit-card').length === 0) {
            checkbox.checked = false;
            setSectionVisible(false);
        }
    };

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

    container.querySelectorAll('.unit-card').forEach(bindCard);
    setSectionVisible(checkbox ? checkbox.checked : false);
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
