<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
    $project   = $project   ?? [];
    $tasks     = $tasks     ?? [];
    $projectId = $projectId ?? null;
    $isEdit    = !empty($projectId);
    $projectAction = $isEdit ? 'projects/update/' . $projectId : 'projects/create';

    $savedLat = (string)($project['location_lat_value'] ?? ($project['location']['geopoint']['lat'] ?? ''));
    $savedLng = (string)($project['location_lng_value'] ?? ($project['location']['geopoint']['lng'] ?? ''));
    $savedAddress = (string)($project['location_address_value'] ?? ($project['location']['address'] ?? ''));

    $mapsKey = $googleMapsBrowserKey ?? '';
?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="pz-alert pz-alert-error">
        <span class="material-symbols-outlined" style="font-size:1rem">error</span>
        <?= session()->getFlashdata('error') ?>
    </div>
<?php endif; ?>

<a href="<?= base_url('projects') ?>" class="pz-back-link">
    <span class="material-symbols-outlined">arrow_back</span> Projects
</a>

<div class="pz-page-header">
    <h1><?= $isEdit ? 'Edit Project' : 'Add Project' ?></h1>
</div>


<div id="formToast" role="alert" aria-live="assertive">
    <div class="ft-hdr">
        <span class="material-symbols-outlined ft-icon">error</span>
        <span class="ft-title">Please complete all required fields</span>
        <button type="button" class="ft-close" id="formToastClose" aria-label="Dismiss">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>
    <ul class="ft-list" id="formToastList"></ul>
</div>
<?php
    // On redirect-with-input from server validation, override view data with submitted values
    $wasSubmitted = old('_form_submitted') === '1';
    if ($wasSubmitted) {
        $savedAddress = old('location_address') ?: $savedAddress;
        $savedLat     = old('location_lat')     ?: $savedLat;
        $savedLng     = old('location_lng')     ?: $savedLng;
        $oldPhases = old('phases');
        if (is_array($oldPhases)) $phases = $oldPhases;
    }
    $hasSavedPhases = !empty($phases);
    // Start / End date values for display and hidden inputs
    $sdVal = old('start_date', $project['start_date_value'] ?? '');
    $edVal = old('end_date',   $project['end_date_value']   ?? '');
    $sdDisplay = ($sdVal !== '' && ($sdTs = strtotime($sdVal)) !== false) ? date('d/m/Y', $sdTs) : '';
    $edDisplay = ($edVal !== '' && ($edTs = strtotime($edVal)) !== false) ? date('d/m/Y', $edTs) : '';
?>
<div class="pz-form-container">
    <form method="post" action="<?= base_url($projectAction) ?>" id="projectForm" enctype="multipart/form-data" data-autosave novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="_form_submitted" value="1">

        <div class="pz-form-section">
            <p class="pz-form-section-title">Project Information</p>

            <div class="pz-form-row cols-2">
                <div class="pz-form-group">
                    <label for="project_name">Project Name *</label>
                    <input type="text" id="project_name" name="project_name"
                           value="<?= esc(old('project_name', $project['project_name'] ?? '')) ?>" required>
                </div>
                <div class="pz-form-group">
                    <label for="client_name">Client Name *</label>
                <?php
                $predefinedClients = ['ATC', 'COMMTREND', 'EDGEPOINT', 'FTAP', 'ITP', 'LDIC', 'SMSGT', 'SMART'];
                $allClientSuggestions = array_unique(array_merge(
                    $predefinedClients,
                    array_map('strtoupper', $existingClients ?? [])
                ));
                sort($allClientSuggestions);
                ?>
                <input type="text" id="client_name" name="client_name"
                       value="<?= esc(old('client_name', $project['client_name'] ?? '')) ?>"
                       list="clientSuggestions" autocomplete="off"
                       placeholder="e.g. SMART"
                       required>
                <datalist id="clientSuggestions">
                    <?php foreach ($allClientSuggestions as $c): ?>
                    <option value="<?= esc($c) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            </div><!-- /cols-2 -->

            <div class="pz-form-row" style="max-width:320px;margin-top:.65rem">
                <div class="pz-form-group">
                    <label for="project_type">Tower Type *</label>
                    <select id="project_type" name="project_type" required>
                        <option value="">Select Type</option>
                        <?php foreach (['BUILD-TO-SUIT','RETROFITTING','COLOCATION', 'CONVERSION'] as $type): ?>
                            <option value="<?= esc($type) ?>"
                                <?= old('project_type', $project['project_type'] ?? '') === $type ? 'selected' : '' ?>>
                                <?= esc($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- ── Location field with Maps integration ──────── -->
            <div class="pz-form-group" style="margin-top:.65rem">
                <label for="location_address">Address *</label>
                <div class="location-wrap">
                    <div class="location-input-row">
                        <input type="text"
                               id="location_address"
                               name="location_address"
                               value="<?= esc($savedAddress) ?>"
                               placeholder="Start typing an address…"
                               autocomplete="off"
                               required>
                        <button type="button" id="locateMeBtn" class="btn-locate-me"
                                title="Use my current location">
                            <span class="material-symbols-outlined">my_location</span>
                            Location
                        </button>
                    </div>

                    <!-- Hidden lat/lng — auto-filled by Maps JS + server geocoding -->
                    <input type="hidden" id="location_lat" name="location_lat"
                           value="<?= esc($savedLat) ?>">
                    <input type="hidden" id="location_lng" name="location_lng"
                           value="<?= esc($savedLng) ?>">

                    <div id="coordsDisplay" class="location-coords
                        <?= ($savedLat && $savedLng) ? 'has-coords' : '' ?>">
                        <?php if ($savedLat && $savedLng): ?>
                            📍 <?= esc($savedLat) ?>, <?= esc($savedLng) ?>
                        <?php endif; ?>
                    </div>

                    <!-- Map — always visible; click to place the pin -->
                    <div class="map-preview-wrap" id="mapPreviewWrap">
                        <div id="locationMap"></div>
                        <div class="map-hint">
                            <span class="material-symbols-outlined" style="font-size:15px;color:#8C701C;">pin_drop</span>
                            Click anywhere on the map to place the pin — the address and coordinates fill in automatically.
                        </div>
                    </div>
                </div>
            </div>
            <!-- ── /Location ──────────────────────────────────── -->

            <div class="pz-form-row cols-2" style="margin-top:.65rem">
                <div class="pz-form-group">
                    <label for="project_manager_id">Project Manager *</label>
                    <select id="project_manager_id" name="project_manager_id" required>
                        <option value="">Select Project Manager</option>
                        <?php foreach ($projectManagers ?? [] as $mgr): ?>
                            <?php $label = trim(($mgr['first_name'] ?? '') . ' ' . ($mgr['last_name'] ?? '')); ?>
                            <option value="<?= esc($mgr['id'] ?? '') ?>"
                                <?= old('project_manager_id', $project['pm_id_normalized'] ?? '') === ($mgr['id'] ?? '') ? 'selected' : '' ?>>
                                <?= esc($label ?: ($mgr['email'] ?? 'N/A')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="pz-form-group">
                    <label for="site_engineer_id">Site Engineer *</label>
                    <select id="site_engineer_id" name="site_engineer_id" required>
                        <option value="">Select Site Engineer</option>
                        <?php foreach ($siteEngineers ?? [] as $eng): ?>
                            <?php $label = trim(($eng['first_name'] ?? '') . ' ' . ($eng['last_name'] ?? '')); ?>
                            <option value="<?= esc($eng['id'] ?? '') ?>"
                                <?= old('site_engineer_id', $project['se_id_normalized'] ?? '') === ($eng['id'] ?? '') ? 'selected' : '' ?>>
                                <?= esc($label ?: ($eng['email'] ?? 'N/A')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="pz-form-row cols-2" style="margin-top:.65rem">
                <div class="pz-form-group">
                    <label for="start_date">Start Date *</label>
                    <div class="date-picker" data-target="start">
                        <input type="text" id="start_date_display" class="date-display"
                               placeholder="DD/MM/YYYY"
                               value="<?= esc($sdDisplay) ?>"
                               readonly>
                        <input type="hidden" id="start_date" name="start_date"
                               value="<?= esc($sdVal) ?>">
                        <button type="button" class="date-toggle"><span class="material-symbols-outlined">calendar_month</span></button>
                        <div class="calendar-popup" aria-hidden="true"></div>
                    </div>
                </div>

                <div class="pz-form-group">
                    <label for="end_date">End Date *</label>
                    <div class="date-picker" data-target="end">
                        <input type="text" id="end_date_display" class="date-display"
                               placeholder="DD/MM/YYYY"
                               value="<?= esc($edDisplay) ?>"
                               readonly>
                        <input type="hidden" id="end_date" name="end_date"
                               value="<?= esc($edVal) ?>">
                        <button type="button" class="date-toggle"><span class="material-symbols-outlined">calendar_month</span></button>
                        <div class="calendar-popup" aria-hidden="true"></div>
                    </div>
                </div>
            </div>

            <div class="pz-form-row cols-2" style="margin-top:.65rem">
                <div class="pz-form-group">
                    <label for="budget">Budget *</label>
                    <input type="number" id="budget" name="budget"
                           min="0" step="0.01"
                           value="<?= esc(old('budget', $project['budget'] ?? '')) ?>" required>
                </div>

                <div class="pz-form-group">
                    <label for="attachments">Attachments</label>
                    <label class="file-upload-label" for="attachments">
                        <span class="material-symbols-outlined file-upload-icon">upload_file</span>
                        <span class="file-upload-text" id="attachmentsText">Choose files…</span>
                        <input type="file" id="attachments" name="attachments[]"
                               multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg"
                               style="display:none">
                    </label>
                    <div class="file-upload-list" id="attachmentsList"></div>
                    <?php
                        $existingAttachments = is_array($project['attachments'] ?? null) ? $project['attachments'] : [];
                    ?>
                    <?php if (!empty($existingAttachments)): ?>
                    <div class="file-existing-list">
                        <div class="file-existing-label">Saved attachments</div>
                        <?php foreach ($existingAttachments as $att): ?>
                        <div class="file-upload-item">
                            <span class="material-symbols-outlined">description</span>
                            <a class="file-upload-name"
                               href="<?= esc($att['url'] ?? '#') ?>"
                               target="_blank" rel="noopener">
                                <?= esc($att['name'] ?? 'file') ?>
                            </a>
                            <span class="file-upload-size">
                                <?= isset($att['size']) ? number_format($att['size'] / 1024, 1) . ' KB' : '' ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /pz-form-section -->

        <!-- ── Project Schedule (phase accordion) ─────────── -->
        <div class="pz-form-section">
            <?php
                // $hasSavedPhases and $phases already set by the old() override block above
                if ($wasSubmitted) {
                    $schedChecked = !empty(old('add_tasks_now'));
                } else {
                    $schedChecked = $hasSavedPhases;
                }
            ?>

            <div class="form-group add-tasks-toggle">
                <label class="checkbox-label">
                    <input type="checkbox" id="add_tasks_now" name="add_tasks_now"
                           <?= $schedChecked ? 'checked' : '' ?>>
                    <span class="checkbox-custom"></span>
                    Add project schedule<?= !$isEdit ? ' <span style="color:#c0392b;font-weight:700">*</span>' : '' ?>
                </label>
            </div>

            <input type="hidden" id="overall_progress" name="overall_progress"
                   value="<?= esc($project['overall_progress'] ?? ($project['completion_percent'] ?? 0)) ?>">

            <div id="scheduleSection" <?= $schedChecked ? '' : 'style="display:none"' ?>>
                <div class="section-title-row">
                    <h2>Project Schedule</h2>
                    <button type="button" id="addPhaseBtn" class="btn btn-add">+ Add Phase</button>
                </div>

                <!-- Schedule table -->
                <div class="phase-schedule">
                    <div class="schedule-hdr">Milestone Schedule</div>

                    <div id="phasesContainer">
                        <?php
                        $units = ['pcs','lot','m','m²','m³','kg','ton','bag','set','unit','lm','days','weeks'];
                        foreach ($phases as $pIdx => $phase):
                            $phaseQty      = $phase['qty']       ?? 1;
                            $phaseUnit     = $phase['unit']      ?? 'pcs';
                            $phaseSiteDate      = $phase['site_date'] ?? '';
                            $phaseSiteStartDate = $phase['site_start_date'] ?? '';
                            $phaseMode          = $phase['tracking_mode'] ?? 'quantity';
                        ?>
                        <div class="phase-card" data-pidx="<?= $pIdx ?>" draggable="true">
                            <span class="phase-drag-handle material-symbols-outlined" title="Drag to reorder">drag_indicator</span>
                            <input type="text" name="phases[<?= $pIdx ?>][name]"
                                   class="phase-card-name-inp"
                                   value="<?= esc($phase['name'] ?? '') ?>"
                                   placeholder="Milestone name">
                            <div class="phase-field f-weight">
                                <span class="phase-field-lbl">Weight %</span>
                                <div class="phase-weight-group">
                                    <input type="number" name="phases[<?= $pIdx ?>][weight]"
                                           class="phase-weight-inp"
                                           value="<?= esc(number_format((float)($phase['weight'] ?? 0), 1)) ?>"
                                           min="0" max="100" step="0.1">
                                    <span class="phase-weight-unit">%</span>
                                </div>
                            </div>
                            <button type="button" class="phase-del-btn" title="Delete milestone">
                                <span class="material-symbols-outlined">delete</span>
                            </button>
                            <div class="phase-row2">
                                <div class="phase-field f-qty">
                                    <span class="phase-field-lbl">Qty</span>
                                    <input type="number" name="phases[<?= $pIdx ?>][qty]"
                                           class="phase-card-inp"
                                           value="<?= esc($phaseQty) ?>" min="0">
                                </div>
                                <div class="phase-field f-unit">
                                    <span class="phase-field-lbl">Unit</span>
                                    <select name="phases[<?= $pIdx ?>][unit]" class="phase-card-sel">
                                        <?php foreach ($units as $u): ?>
                                        <option value="<?= $u ?>" <?= $phaseUnit === $u ? 'selected' : '' ?>><?= $u ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="phase-field f-date-start">
                                    <span class="phase-field-lbl">Target Start</span>
                                    <div class="date-picker phase-card-date-picker">
                                        <input type="text" class="date-display" placeholder="DD/MM/YYYY" readonly>
                                        <input type="hidden" name="phases[<?= $pIdx ?>][site_start_date]" value="<?= esc($phaseSiteStartDate) ?>">
                                        <button type="button" class="date-toggle"><span class="material-symbols-outlined">calendar_month</span></button>
                                        <div class="calendar-popup" aria-hidden="true"></div>
                                    </div>
                                </div>
                                <div class="phase-field f-date">
                                    <span class="phase-field-lbl">Target Date</span>
                                    <div class="date-picker phase-card-date-picker">
                                        <input type="text" class="date-display" placeholder="DD/MM/YYYY" readonly>
                                        <input type="hidden" name="phases[<?= $pIdx ?>][site_date]" value="<?= esc($phaseSiteDate) ?>">
                                        <button type="button" class="date-toggle"><span class="material-symbols-outlined">calendar_month</span></button>
                                        <div class="calendar-popup" aria-hidden="true"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="phase-row3">
                                <span class="phase-field-lbl">Track by</span>
                                <select name="phases[<?= $pIdx ?>][tracking_mode]" class="phase-mode-sel">
                                    <option value="quantity"    <?= $phaseMode === 'quantity'    ? 'selected' : '' ?>>Quantity</option>
                                    <option value="status_only" <?= $phaseMode === 'status_only' ? 'selected' : '' ?>>Status Only</option>
                                </select>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="schedule-total-row">
                        <span>Total</span>
                        <span id="totalWeightDisplay">0.0%</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group add-tasks-toggle">
            <label class="checkbox-label">
                <input type="checkbox" id="add_resources_now" name="add_resources_now">
                <span class="checkbox-custom"></span>
                Add resources now
            </label>
        </div>

        <div class="pz-form-actions">
            <button type="submit" class="pz-btn pz-btn-primary">
                <span class="material-symbols-outlined">save</span>
                <?= $isEdit ? 'Update Project' : 'Create Project' ?>
            </button>
            <a href="<?= base_url('projects') ?>" class="pz-btn pz-btn-cancel">Cancel</a>
        </div>
    </form>
</div>


<!-- ════════════════════════════════════════════════════════════
     GOOGLE MAPS JAVASCRIPT API
     Loads Places library for autocomplete + Map for preview.
     ════════════════════════════════════════════════════════════ -->
<?php if ($mapsKey): ?>
<script>
(function () {
    // ── State ──────────────────────────────────────────
    var map, marker, autocomplete;
    var latInput   = document.getElementById('location_lat');
    var lngInput   = document.getElementById('location_lng');
    var addrInput  = document.getElementById('location_address');
    var coordsEl   = document.getElementById('coordsDisplay');
    var previewWrap = document.getElementById('mapPreviewWrap');

    var initialLat = parseFloat(latInput.value) || 12.8797;   // PH center fallback
    var initialLng = parseFloat(lngInput.value) || 121.7740;
    var hasCoords  = !!(latInput.value && lngInput.value);

    // ── Called by Maps JS API once loaded ──────────────
    window.initProjectMap = function () {
        // ── Map ──────────────────────────────────────
        map = new google.maps.Map(document.getElementById('locationMap'), {
            center: { lat: initialLat, lng: initialLng },
            zoom: hasCoords ? 15 : 6,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
        });

        // ── Marker ────────────────────────────────────
        marker = new google.maps.Marker({
            position: { lat: initialLat, lng: initialLng },
            map: hasCoords ? map : null,
            draggable: false,
            title: 'Project site',
            animation: google.maps.Animation.DROP,
        });

        // Click anywhere on the map → drop pin at that spot → reverse-geocode address
        map.addListener('click', function (event) {
            var lat = event.latLng.lat();
            var lng = event.latLng.lng();
            setCoords(lat, lng);
            // Replay drop animation on each click for clear visual feedback
            marker.setAnimation(null);
            marker.setPosition(event.latLng);
            marker.setMap(map);
            setTimeout(function () { marker.setAnimation(google.maps.Animation.DROP); }, 10);
            reverseGeocode(lat, lng);
        });

        // ── Places Autocomplete ───────────────────────
        autocomplete = new google.maps.places.Autocomplete(addrInput, {
            types: ['geocode', 'establishment'],
            componentRestrictions: { country: 'ph' },   // Philippines; remove for worldwide
        });
        autocomplete.bindTo('bounds', map);

        autocomplete.addListener('place_changed', function () {
            var place = autocomplete.getPlace();
            if (!place.geometry || !place.geometry.location) {
                // User typed freely — fallback to server-side geocode
                serverGeocode(addrInput.value);
                return;
            }
            var lat = place.geometry.location.lat();
            var lng = place.geometry.location.lng();
            setCoords(lat, lng);
            placeMarker({ lat: lat, lng: lng }, 15);
            addrInput.value = addrInput.value.toUpperCase();
        });

        // If editing with saved coords, zoom in and drop pin on load
        if (hasCoords) {
            placeMarker({ lat: initialLat, lng: initialLng }, 15);
        }
    };

    // ── Helpers ────────────────────────────────────────
    function setCoords(lat, lng) {
        latInput.value  = lat;
        lngInput.value  = lng;
        coordsEl.textContent = '📍 ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
        coordsEl.className = 'location-coords has-coords';
    }

    function placeMarker(latLng, zoom) {
        if (!map || !marker) return;
        map.setCenter(latLng);
        if (zoom) map.setZoom(zoom);
        marker.setAnimation(null);
        marker.setPosition(latLng);
        marker.setMap(map);
        setTimeout(function () { marker.setAnimation(google.maps.Animation.DROP); }, 10);
    }

    function reverseGeocode(lat, lng) {
        fetch('<?= base_url('maps/reverse') ?>?lat=' + lat + '&lng=' + lng)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status === 'OK') {
                    addrInput.value = data.formatted_address.toUpperCase();
                }
            })
            .catch(function () {});
    }

    function serverGeocode(address) {
        if (!address.trim()) return;
        coordsEl.textContent = 'Searching…';
        coordsEl.className = 'location-coords';
        fetch('<?= base_url('maps/geocode') ?>?address=' + encodeURIComponent(address))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status === 'OK') {
                    setCoords(data.lat, data.lng);
                    placeMarker({ lat: data.lat, lng: data.lng }, 15);
                    addrInput.value = data.formatted_address.toUpperCase();
                } else {
                    coordsEl.textContent = 'Address not found — try a more specific address.';
                    coordsEl.className = 'location-coords';
                }
            })
            .catch(function () {
                coordsEl.textContent = 'Search failed. Check your connection.';
                coordsEl.className = 'location-coords';
            });
    }

    // ── Live search: debounced while typing ───────────────
    var geocodeTimer = null;
    addrInput.addEventListener('input', function () {
        clearTimeout(geocodeTimer);
        var val = this.value.trim();
        if (val.length < 5) return;          // wait for a meaningful string
        geocodeTimer = setTimeout(function () {
            serverGeocode(val);
        }, 600);                             // 600 ms after user stops typing
    });

    // ── Press Enter in the address field → search immediately ─
    addrInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();             // don't submit the form
            clearTimeout(geocodeTimer);
            if (this.value.trim()) serverGeocode(this.value.trim());
        }
    });

    // ── "My Location" button ───────────────────────────
    document.getElementById('locateMeBtn').addEventListener('click', function () {
        if (!navigator.geolocation) {
            window._pzToast && _pzToast('Geolocation is not supported by your browser.', 'error');
            return;
        }
        this.disabled = true;
        var btn = this;
        navigator.geolocation.getCurrentPosition(
            function (pos) {
                var lat = pos.coords.latitude;
                var lng = pos.coords.longitude;
                setCoords(lat, lng);
                placeMarker({ lat: lat, lng: lng }, 16);
                reverseGeocode(lat, lng);
                btn.disabled = false;
            },
            function () {
                window._pzToast && _pzToast('Unable to retrieve your location. Please check your browser permissions.', 'error');
                btn.disabled = false;
            }
        );
    });

    // ── Geocode on form submit if coords still missing ─
    document.getElementById('projectForm').addEventListener('submit', function (e) {
        if (!latInput.value || !lngInput.value) {
            if (addrInput.value.trim()) {
                e.preventDefault();
                fetch('<?= base_url('maps/geocode') ?>?address=' + encodeURIComponent(addrInput.value))
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.status === 'OK') {
                            latInput.value = data.lat;
                            lngInput.value = data.lng;
                        }
                        document.getElementById('projectForm').submit();
                    })
                    .catch(function () {
                        document.getElementById('projectForm').submit();
                    });
            }
        }
    });
}());
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= esc($mapsKey) ?>&libraries=places&callback=initProjectMap" async defer></script>
<?php else: ?>
<script>
// No Maps key configured — show a console warning in development
if (window.location.hostname === 'localhost') {
    console.warn('[PrimaZentra] GOOGLE_MAPS_BROWSER_KEY is not set in .env. Address autocomplete and map preview are disabled.');
}
</script>
<?php endif; ?>

<!-- ── Attachment input JS ───────────────────────────────── -->
<script>
(function () {
    var input  = document.getElementById('attachments');
    var textEl = document.getElementById('attachmentsText');
    var listEl = document.getElementById('attachmentsList');
    if (!input) return;

    var allFiles = [];

    function syncInput() {
        var dt = new DataTransfer();
        allFiles.forEach(function (f) { dt.items.add(f); });
        input.files = dt.files;
    }

    function renderList() {
        if (allFiles.length === 0) {
            textEl.textContent = 'Choose files…';
            listEl.innerHTML = '';
            return;
        }
        textEl.textContent = allFiles.length + ' file' + (allFiles.length > 1 ? 's' : '') + ' selected';
        listEl.innerHTML = allFiles.map(function (f, i) {
            var kb = (f.size / 1024).toFixed(1);
            return '<div class="file-upload-item">' +
                '<span class="material-symbols-outlined">description</span>' +
                '<span class="file-upload-name">' + f.name + '</span>' +
                '<span class="file-upload-size">' + kb + ' KB</span>' +
                '<button type="button" class="file-remove-btn" data-idx="' + i + '" title="Remove">✕</button>' +
                '</div>';
        }).join('');
        listEl.querySelectorAll('.file-remove-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                allFiles.splice(parseInt(this.dataset.idx, 10), 1);
                syncInput();
                renderList();
            });
        });
    }

    input.addEventListener('change', function () {
        Array.from(this.files).forEach(function (f) {
            var dupe = allFiles.some(function (ef) { return ef.name === f.name && ef.size === f.size; });
            if (!dupe) allFiles.push(f);
        });
        syncInput();
        renderList();
    });
}());
</script>

<!-- ── Phase Schedule JS ──────────────────────────────────── -->
<script>
(function () {
    var UNITS = ['pcs','lot','m','m²','m³','kg','ton','bag','set','unit','lm'];

    // ── Milestone templates keyed by tower type ────────────────
    var TEMPLATES = {
        'BUILD-TO-SUIT': [
            { name: 'Skom',                       weight: 3, mode: 'status_only' },
            { name: 'Mobilization/Site Clearing', weight: 5, mode: 'status_only' },
            { name: 'Site Layout/Excavation',     weight: 5, mode: 'quantity', unit: 'm³' },
            { name: 'Lean Concrete',              weight: 4, mode: 'quantity', unit: 'm³' },
            { name: 'Rebars Installation',        weight: 5, mode: 'quantity', unit: 'kg' },
            { name: 'ABST Installation',          weight: 5, mode: 'status_only' },
            { name: 'Formworks Installation',     weight: 5, mode: 'quantity', unit: 'm²' },
            { name: 'Tower Foundation Casting',   weight: 6, mode: 'quantity', unit: 'm³' },
            { name: 'Backfilling',                weight: 4, mode: 'quantity', unit: 'm³' },
            { name: 'Tower On Site',              weight: 3, mode: 'status_only' },
            { name: 'Tower Erection',             weight: 8, mode: 'quantity', unit: 'm' },
            { name: 'BTS Pad',                    weight: 4, mode: 'status_only' },
            { name: 'DG Pad',                     weight: 4, mode: 'status_only' },
            { name: 'SEP',                        weight: 4, mode: 'status_only' },
            { name: 'CEF',                        weight: 4, mode: 'status_only' },
            { name: 'Perimeter Fence',            weight: 6, mode: 'quantity', unit: 'lm' },
            { name: 'Gate',                       weight: 4, mode: 'status_only' },
            { name: 'Grounding Systems',          weight: 4, mode: 'status_only' },
            { name: 'E-Wall',                     weight: 5, mode: 'status_only' },
            { name: 'Panel Board Installation',   weight: 5, mode: 'status_only' },
            { name: 'Site Development',           weight: 5, mode: 'status_only' },
            { name: 'PrePAT',                     weight: 2, mode: 'status_only' },
        ],
        'RETROFITTING': [
            { name: 'RM', weight: 40, qty: 14, mode: 'quantity', unit: 'pcs' },
            { name: 'SP', weight: 30, qty: 6,  mode: 'quantity', unit: 'pcs' },
            { name: 'L',  weight: 20, qty: 1,  mode: 'quantity', unit: 'pcs' },
            { name: 'BP', weight: 10,          mode: 'status_only' },
        ],
        'COLOCATION': [
            { name: 'Site Layout',                                  weight: 4.8, mode: 'status_only' },
            { name: 'Trenching, Excavation',                        weight: 4.8, mode: 'quantity', unit: 'm³' },
            { name: 'Order of Materials and Delivery',              weight: 4.8, mode: 'status_only' },
            { name: 'Lean concrete',                                weight: 4.8, mode: 'quantity', unit: 'm³' },
            { name: 'Rebars Fabrication and Formworks Preparation', weight: 4.8, mode: 'status_only' },
            { name: 'Formworks Inst',                               weight: 4.8, mode: 'quantity', unit: 'm²' },
            { name: 'Rebar Inst',                                   weight: 4.8, mode: 'quantity', unit: 'kg' },
            { name: 'Casting(Bot) + Curing',                        weight: 4.8, mode: 'quantity', unit: 'm³' },
            { name: 'CHB Laying',                                   weight: 4.8, mode: 'quantity', unit: 'pcs' },
            { name: 'Backfill (1st and 2nd Layer)',                 weight: 4.8, mode: 'quantity', unit: 'm³' },
            { name: 'Casting(Top) + Curing',                        weight: 4.8, mode: 'quantity', unit: 'm³' },
            { name: 'Plastering',                                   weight: 4.8, mode: 'quantity', unit: 'm²' },
            { name: 'Trenching (Electrical +FOC)',                  weight: 4.8, mode: 'quantity', unit: 'lm' },
            { name: 'Cable Tray Inst',                              weight: 4.8, mode: 'quantity', unit: 'lm' },
            { name: 'Panel Board Inst',                             weight: 4.8, mode: 'status_only' },
            { name: 'CEF',                                          weight: 4.8, mode: 'status_only' },
            { name: 'Electrical Works',                             weight: 4.8, mode: 'status_only' },
            { name: 'Grounding',                                    weight: 4.8, mode: 'status_only' },
            { name: 'Wire Termination',                             weight: 4.8, mode: 'status_only' },
            { name: 'Site Clearing',                                weight: 4.8, mode: 'status_only' },
            { name: 'Site Development',                             weight: 4.0, mode: 'status_only' },
        ],
        'CONVERSION': [
            { name: 'SKOM',                      weight: 5, mode: 'status_only' },
            { name: 'Site Lay-out',              weight: 5, mode: 'status_only' },
            { name: 'Excavation',                weight: 8, mode: 'quantity', unit: 'm³' },
            { name: 'Grounding',                 weight: 7, mode: 'status_only' },
            { name: 'Lean Concrete',             weight: 7, mode: 'quantity', unit: 'm³' },
            { name: 'Rebars Installation',       weight: 8, mode: 'quantity', unit: 'kg' },
            { name: 'ABST Installation',         weight: 8, mode: 'status_only' },
            { name: 'Formworks Installation',    weight: 7, mode: 'quantity', unit: 'm²' },
            { name: 'Tower Foundation Casting',  weight: 8, mode: 'quantity', unit: 'm³' },
            { name: 'Backfilling',               weight: 6, mode: 'quantity', unit: 'm³' },
            { name: 'Tower Erection',            weight: 9, mode: 'quantity', unit: 'm' },
            { name: 'Electrical Works',          weight: 8, mode: 'status_only' },
            { name: 'Cable Ladder Installation', weight: 7, mode: 'quantity', unit: 'lm' },
            { name: 'Site Development',          weight: 7, mode: 'status_only' },
        ],
    };

    var container       = document.getElementById('phasesContainer');
    var scheduleSection = document.getElementById('scheduleSection');
    var totalDisplay    = document.getElementById('totalWeightDisplay');
    var progressInput   = document.getElementById('overall_progress');
    var checkbox        = document.getElementById('add_tasks_now');
    var addPhaseBtn     = document.getElementById('addPhaseBtn');
    var projectTypeEl   = document.getElementById('project_type');

    // ── Show / hide schedule section ──────────────────────────
    function setSectionVisible(v) {
        if (scheduleSection) scheduleSection.style.display = v ? '' : 'none';
    }
    if (checkbox) {
        setSectionVisible(checkbox.checked);
        checkbox.addEventListener('change', function () { setSectionVisible(this.checked); });
    }

    // ── Reindex all phase name attributes ─────────────────────
    function reindex() {
        container.querySelectorAll('.phase-card').forEach(function (card, pi) {
            card.dataset.pidx = pi;
            card.querySelectorAll('[name]').forEach(function (el) {
                el.name = el.name.replace(/phases\[\d+\]/, 'phases[' + pi + ']');
            });
        });
    }

    // ── Update total weight display ────────────────────────────
    function updateTotal() {
        var total = 0;
        container.querySelectorAll('.phase-weight-inp').forEach(function (inp) {
            total += parseFloat(inp.value) || 0;
        });
        total = Math.round(total * 10) / 10;
        if (totalDisplay) {
            totalDisplay.textContent = total.toFixed(1) + '%';
            totalDisplay.style.color = total > 100.05 ? '#e53935' : Math.abs(total - 100) < 0.2 ? '#4caf50' : '#e53935';
        }
        if (progressInput) progressInput.value = 0;
    }

    // ── Form validation toast ──────────────────────────────────
    var _toastTimer = null;
    function showFormToast(msgs, title) {
        var t  = document.getElementById('formToast');
        var tl = document.getElementById('formToastList');
        var th = t ? t.querySelector('.ft-title') : null;
        if (!t || !tl) return;
        if (typeof msgs === 'string') msgs = [msgs];
        if (th) th.textContent = title || 'Please complete all required fields';
        tl.innerHTML = msgs.map(function (m) {
            return '<li><span class="material-symbols-outlined ft-bullet">radio_button_checked</span>' + escHtml(m) + '</li>';
        }).join('');
        t.classList.add('show');
        clearTimeout(_toastTimer);
        _toastTimer = setTimeout(function () { t.classList.remove('show'); }, 6000);
    }
    window.showFormToast = showFormToast;
    var _ftClose = document.getElementById('formToastClose');
    if (_ftClose) {
        _ftClose.addEventListener('click', function () {
            var t = document.getElementById('formToast');
            if (t) { t.classList.remove('show'); clearTimeout(_toastTimer); }
        });
    }

    // ── Build a milestone card element ─────────────────────────
    function buildPhaseItem(pi, data) {
        data = data || {};
        var unitOpts = UNITS.map(function (u) {
            return '<option value="' + u + '"' + ((data.unit || 'pcs') === u ? ' selected' : '') + '>' + u + '</option>';
        }).join('');

        var card = document.createElement('div');
        card.className  = 'phase-card';
        card.dataset.pidx = pi;
        card.setAttribute('draggable', 'true');
        card.innerHTML =
            '<span class="phase-drag-handle material-symbols-outlined" title="Drag to reorder">drag_indicator</span>' +
            '<input type="text" name="phases[' + pi + '][name]" class="phase-card-name-inp" placeholder="Milestone name" value="' + escHtml(data.name || '') + '">' +
            '<div class="phase-field f-weight">' +
                '<span class="phase-field-lbl">Weight %</span>' +
                '<div class="phase-weight-group">' +
                    '<input type="number" name="phases[' + pi + '][weight]" class="phase-weight-inp" min="0" max="100" step="0.1" value="' + (data.weight != null ? data.weight : '') + '">' +
                    '<span class="phase-weight-unit">%</span>' +
                '</div>' +
            '</div>' +
            '<button type="button" class="phase-del-btn" title="Delete milestone"><span class="material-symbols-outlined">delete</span></button>' +
            '<div class="phase-row2">' +
                '<div class="phase-field f-qty">' +
                    '<span class="phase-field-lbl">Qty</span>' +
                    '<input type="number" name="phases[' + pi + '][qty]" class="phase-card-inp" min="0" value="' + (data.qty != null ? data.qty : 1) + '">' +
                '</div>' +
                '<div class="phase-field f-unit">' +
                    '<span class="phase-field-lbl">Unit</span>' +
                    '<select name="phases[' + pi + '][unit]" class="phase-card-sel">' + unitOpts + '</select>' +
                '</div>' +
                '<div class="phase-field f-date-start">' +
                    '<span class="phase-field-lbl">Target Start</span>' +
                    '<div class="date-picker phase-card-date-picker">' +
                        '<input type="text" class="date-display" placeholder="DD/MM/YYYY" readonly>' +
                        '<input type="hidden" name="phases[' + pi + '][site_start_date]" value="' + escHtml(data.site_start_date || '') + '">' +
                        '<button type="button" class="date-toggle"><span class="material-symbols-outlined">calendar_month</span></button>' +
                        '<div class="calendar-popup" aria-hidden="true"></div>' +
                    '</div>' +
                '</div>' +
                '<div class="phase-field f-date">' +
                    '<span class="phase-field-lbl">Target Date</span>' +
                    '<div class="date-picker phase-card-date-picker">' +
                        '<input type="text" class="date-display" placeholder="DD/MM/YYYY" readonly>' +
                        '<input type="hidden" name="phases[' + pi + '][site_date]" value="' + escHtml(data.site_date || '') + '">' +
                        '<button type="button" class="date-toggle"><span class="material-symbols-outlined">calendar_month</span></button>' +
                        '<div class="calendar-popup" aria-hidden="true"></div>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            '<div class="phase-row3">' +
                '<span class="phase-field-lbl">Track by</span>' +
                '<select name="phases[' + pi + '][tracking_mode]" class="phase-mode-sel">' +
                    '<option value="quantity"' + ((data.mode || 'quantity') === 'quantity' ? ' selected' : '') + '>Quantity</option>' +
                    '<option value="status_only"' + (data.mode === 'status_only' ? ' selected' : '') + '>Status Only</option>' +
                '</select>' +
            '</div>';

        if (typeof window.initPhaseDatePicker === 'function') {
            card.querySelectorAll('.date-picker').forEach(function (dp) {
                window.initPhaseDatePicker(dp);
            });
        }

        attachFieldErrorClearing(card);
        card.querySelector('.phase-weight-inp').addEventListener('input', updateTotal);
        card.querySelector('.phase-del-btn').addEventListener('click', function () {
            card.remove(); reindex(); updateTotal();
        });
        card.querySelector('.phase-mode-sel').addEventListener('change', function () {
            applyPhaseMode(card);
        });
        applyPhaseMode(card);
        return card;
    }

    // ── Quantity / Status-only mode ── */
    function applyPhaseMode(card) {
        var modeSel = card.querySelector('.phase-mode-sel');
        var qtyInp  = card.querySelector('.phase-card-inp');
        var unitSel = card.querySelector('.phase-card-sel');
        if (!modeSel) return;
        var statusOnly = modeSel.value === 'status_only';
        if (qtyInp) {
            qtyInp.disabled = statusOnly;
            if (statusOnly) qtyInp.dataset.prevValue = qtyInp.dataset.prevValue || qtyInp.value;
            qtyInp.value = statusOnly ? 1 : (qtyInp.dataset.prevValue || qtyInp.value);
        }
        if (unitSel) unitSel.disabled = statusOnly;
    }

    // ── Clear error highlight as soon as a field is touched ────
    function attachFieldErrorClearing(card) {
        var nameInp   = card.querySelector('.phase-card-name-inp');
        var weightInp = card.querySelector('.phase-weight-inp');
        var weightGrp = card.querySelector('.phase-weight-group');
        var qtyInp    = card.querySelector('.phase-card-inp');
        var dateHid   = card.querySelector('input[type="hidden"][name$="[site_date]"]');
        var dateDisp  = dateHid ? dateHid.closest('.phase-card-date-picker').querySelector('.date-display') : null;

        if (nameInp)   nameInp.addEventListener('input',  function () { nameInp.classList.remove('pz-field-error'); });
        if (weightInp) weightInp.addEventListener('input', function () {
            if (weightGrp) weightGrp.classList.remove('pz-field-error');
            weightInp.classList.remove('pz-field-error');
        });
        if (qtyInp)    qtyInp.addEventListener('input',   function () { qtyInp.classList.remove('pz-field-error'); });
        // date picker clears on hidden input change (set by the calendar)
        if (dateHid)   dateHid.addEventListener('change', function () { if (dateDisp) dateDisp.classList.remove('pz-field-error'); });
    }

    // ── Attach events to PHP-rendered cards (edit mode) ────────
    function attachExistingCard(card) {
        attachFieldErrorClearing(card);
        card.querySelector('.phase-weight-inp').addEventListener('input', updateTotal);
        card.querySelector('.phase-del-btn').addEventListener('click', function () {
            card.remove(); reindex(); updateTotal();
        });
        var modeSel = card.querySelector('.phase-mode-sel');
        if (modeSel) modeSel.addEventListener('change', function () { applyPhaseMode(card); });
        applyPhaseMode(card);
    }
    container.querySelectorAll('.phase-card').forEach(attachExistingCard);

    // ── Add Phase button ───────────────────────────────────────
    if (addPhaseBtn) {
        addPhaseBtn.addEventListener('click', function () {
            if (checkbox && !checkbox.checked) { checkbox.checked = true; setSectionVisible(true); }
            var pi = container.querySelectorAll('.phase-card').length;
            container.appendChild(buildPhaseItem(pi, {}));
            updateTotal();
        });
    }

    // ── Auto-fill milestones when tower type changes ───────────
    if (projectTypeEl) {
        projectTypeEl.addEventListener('change', async function () {
            if (!this.value) return;
            var self = this;
            var existing = container.querySelectorAll('.phase-card').length;
            if (existing > 0) {
                if (!await window.openConfirmForm('Auto-fill will replace the current milestone schedule. Continue?', 'warning')) return;
            }
            var template = TEMPLATES[self.value] || [];
            container.innerHTML = '';
            template.forEach(function (phase, pi) {
                container.appendChild(buildPhaseItem(pi, phase));
            });
            if (checkbox && !checkbox.checked) { checkbox.checked = true; setSectionVisible(true); }
            updateTotal();
        });
    }

    // ── Drag-sort (Notion-style) ───────────────────────────────
    function enableDragSort(cont) {
        var dragged      = null;
        var fromHandle   = false;
        var dropLine     = document.createElement('div');
        dropLine.className = 'phase-drop-line';

        // dragstart fires on the card element (.phase-card), so e.target is the card,
        // not the handle. Track mousedown separately to know the drag origin.
        cont.addEventListener('mousedown', function (e) {
            fromHandle = !!e.target.closest('.phase-drag-handle');
        });

        cont.addEventListener('dragstart', function (e) {
            var card = e.target.closest('.phase-card');
            if (!card || !fromHandle) { e.preventDefault(); return; }
            dragged = card;
            e.dataTransfer.effectAllowed = 'move';
            setTimeout(function () { if (dragged) dragged.classList.add('pz-dragging'); }, 0);
        });

        cont.addEventListener('dragover', function (e) {
            e.preventDefault();
            var card = e.target.closest('.phase-card');
            if (!card || card === dragged) return;
            var rect = card.getBoundingClientRect();
            if (e.clientY < rect.top + rect.height / 2) {
                card.parentNode.insertBefore(dropLine, card);
            } else {
                card.parentNode.insertBefore(dropLine, card.nextSibling);
            }
        });

        cont.addEventListener('dragleave', function (e) {
            if (!cont.contains(e.relatedTarget)) dropLine.remove();
        });

        cont.addEventListener('drop', function (e) {
            e.preventDefault();
            if (!dragged || !dropLine.parentNode) return;
            dropLine.parentNode.insertBefore(dragged, dropLine);
            dropLine.remove();
            dragged.classList.remove('pz-dragging');
            dragged = null;
            reindex();
        });

        cont.addEventListener('dragend', function () {
            if (dragged) dragged.classList.remove('pz-dragging');
            dropLine.remove();
            dragged = null;
        });
    }

    // ── Submit: required fields → milestone fields → weight ───
    var isEdit = <?= $isEdit ? 'true' : 'false' ?>;

    // Clear all milestone field-error highlights
    function clearMilestoneErrors() {
        container.querySelectorAll('.pz-field-error').forEach(function (el) {
            el.classList.remove('pz-field-error');
        });
    }

    document.getElementById('projectForm').addEventListener('submit', function (e) {
        clearMilestoneErrors();

        // ── 1. Top-level required fields ──────────────────────
        var errs       = [];
        var firstErrEl = null;
        var nameEl   = document.getElementById('project_name');
        var clientEl = document.getElementById('client_name');
        var typeEl   = document.getElementById('project_type');
        var addrEl   = document.getElementById('location_address');
        var pmEl     = document.getElementById('project_manager_id');
        var seEl     = document.getElementById('site_engineer_id');
        var budgetEl = document.getElementById('budget');
        var startEl  = document.getElementById('start_date');
        var endEl    = document.getElementById('end_date');
        var startDisp = document.getElementById('start_date_display');
        var endDisp   = document.getElementById('end_date_display');
        var schedCb  = document.getElementById('add_tasks_now');

        // Mark a field invalid: highlight it, track first, wire one-shot clear.
        // el       = element to highlight (and listen on by default)
        // label    = text shown in the toast
        // clearEl  = optional different element to listen on for the "fixed" event
        //            (use for readonly display inputs whose hidden sibling fires change)
        function markErr(el, label, clearEl) {
            errs.push(label);
            if (!el) return;
            // For readonly date-display inputs, highlight the parent .date-picker wrapper
            var highlight = el.readOnly ? (el.closest('.date-picker') || el) : el;
            highlight.classList.add('pz-field-error');
            if (!firstErrEl) firstErrEl = highlight;
            var listenEl  = clearEl || el;
            var clearEvt  = (listenEl.tagName === 'SELECT' || listenEl.type === 'hidden') ? 'change' : 'input';
            function onFix() {
                highlight.classList.remove('pz-field-error');
                listenEl.removeEventListener(clearEvt, onFix);
            }
            listenEl.addEventListener(clearEvt, onFix);
        }

        if (nameEl   && !nameEl.value.trim())   markErr(nameEl,   'Project Name');
        if (clientEl && !clientEl.value.trim()) markErr(clientEl, 'Client Name');
        if (typeEl   && !typeEl.value)          markErr(typeEl,   'Tower Type');
        if (addrEl   && !addrEl.value.trim())   markErr(addrEl,   'Address');
        if (pmEl     && !pmEl.value)            markErr(pmEl,     'Project Manager');
        if (seEl     && !seEl.value)            markErr(seEl,     'Site Engineer');
        // Date pickers: highlight via readonly display input (finds .date-picker wrapper);
        // clear when the hidden input fires change (calendar selection)
        if (!startEl || !startEl.value)         markErr(startDisp || startEl, 'Start Date', startEl);
        if (!endEl   || !endEl.value)           markErr(endDisp   || endEl,   'End Date',   endEl);
        if (budgetEl && budgetEl.value === '')   markErr(budgetEl, 'Budget');

        if (!isEdit) {
            if (!schedCb || !schedCb.checked) {
                errs.push('Project Schedule (check the box and add milestones)');
                if (!firstErrEl && schedCb) firstErrEl = schedCb.closest('.checkbox-label') || schedCb;
            } else if (container.querySelectorAll('.phase-card').length === 0) {
                errs.push('Project Schedule (add at least one milestone)');
                if (!firstErrEl) firstErrEl = document.getElementById('addPhaseBtn') || schedCb;
            }
        } else if (schedCb && schedCb.checked && container.querySelectorAll('.phase-card').length === 0) {
            errs.push('Project Schedule (add at least one milestone)');
            if (!firstErrEl) firstErrEl = document.getElementById('addPhaseBtn') || schedCb;
        }

        if (errs.length > 0) {
            e.preventDefault();
            e.stopImmediatePropagation();
            showFormToast(errs);
            if (window._pzToast) window._pzToast('Please complete all required fields.', 'error');
            if (firstErrEl) firstErrEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        // ── 2. Milestone field validation ──────────────────────
        if (checkbox && checkbox.checked) {
            var phaseCards  = container.querySelectorAll('.phase-card');
            var mileErrs    = [];   // { idx, label, missing[] }
            var firstErrCard = null;

            phaseCards.forEach(function (card, idx) {
                var nameInp    = card.querySelector('.phase-card-name-inp');
                var weightInp  = card.querySelector('.phase-weight-inp');
                var weightGrp  = card.querySelector('.phase-weight-group');
                var qtyInp     = card.querySelector('.phase-card-inp');
                var mileName   = nameInp && nameInp.value.trim() ? nameInp.value.trim() : 'Milestone ' + (idx + 1);
                var missing    = [];

                if (!nameInp || !nameInp.value.trim()) {
                    missing.push('Name');
                    if (nameInp) nameInp.classList.add('pz-field-error');
                }
                var wVal = parseFloat(weightInp ? weightInp.value : '');
                if (!weightInp || weightInp.value === '' || isNaN(wVal) || wVal <= 0) {
                    missing.push('Weight %');
                    if (weightGrp) weightGrp.classList.add('pz-field-error');
                    else if (weightInp) weightInp.classList.add('pz-field-error');
                }
                var qVal = parseFloat(qtyInp ? qtyInp.value : '');
                if (!qtyInp || qtyInp.value === '' || isNaN(qVal)) {
                    missing.push('Qty');
                    if (qtyInp) qtyInp.classList.add('pz-field-error');
                }
                var dateHid = card.querySelector('input[type="hidden"][name$="[site_date]"]');
                var datePicker = dateHid ? dateHid.closest('.phase-card-date-picker') : null;
                if (!dateHid || !dateHid.value.trim()) {
                    missing.push('Target Date');
                    if (datePicker) datePicker.classList.add('pz-field-error');
                }

                if (missing.length > 0) {
                    if (!firstErrCard) firstErrCard = card;
                    mileErrs.push({ idx: idx + 1, label: mileName, missing: missing });
                }
            });

            if (mileErrs.length > 0) {
                e.preventDefault();
                e.stopImmediatePropagation();
                var MAX = 5;
                var lines = mileErrs.slice(0, MAX).map(function (m) {
                    return m.idx + '. ' + m.label + ' — ' + m.missing.join(', ');
                });
                if (mileErrs.length > MAX) lines.push('... and ' + (mileErrs.length - MAX) + ' more milestone(s)');
                showFormToast(lines, 'Fix incomplete milestones (' + mileErrs.length + ')');
                if (window._pzToast) window._pzToast('Fix incomplete milestones before submitting.', 'error');
                if (firstErrCard) firstErrCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            // ── 3. Weight total check ──────────────────────────
            var total = 0;
            container.querySelectorAll('.phase-weight-inp').forEach(function (inp) {
                total += parseFloat(inp.value) || 0;
            });
            total = Math.round(total * 10) / 10;
            if (Math.abs(total - 100) > 0.5) {
                e.preventDefault();
                e.stopImmediatePropagation();
                showFormToast(
                    ['Current total is ' + total.toFixed(1) + '% — adjust weights so they add up to 100%.'],
                    'Milestone weights must total 100%'
                );
                if (window._pzToast) window._pzToast('Milestone weights must total 100%.', 'error');
            }
        }
    }, true);

    // ── Escape helper ──────────────────────────────────────────
    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    enableDragSort(container);
    updateTotal();

    // ── Auto-save hooks ────────────────────────────────────────
    window.pzBuildPhaseCard = buildPhaseItem;

    window['pzAsSave_projectForm'] = function () {
        var xk = 'pz_as_projectForm' + location.pathname + '_x';
        var phData = [];
        container.querySelectorAll('.phase-card').forEach(function (card) {
            phData.push({
                name:      (card.querySelector('.phase-card-name-inp') || {}).value || '',
                weight:    (card.querySelector('.phase-weight-inp')    || {}).value || '',
                qty:       (card.querySelector('.phase-card-inp')      || {}).value || '',
                unit:      (card.querySelector('.phase-card-sel')      || {}).value || 'pcs',
                site_start_date: (card.querySelector('input[type="hidden"][name$="[site_start_date]"]') || {}).value || '',
                site_date: (card.querySelector('input[type="hidden"][name$="[site_date]"]') || {}).value || ''
            });
        });
        try {
            sessionStorage.setItem(xk, JSON.stringify({
                checked: checkbox ? checkbox.checked : false,
                phases:  phData
            }));
        } catch (e) {}
    };

    window['pzAsRestore_projectForm'] = function (extraData) {
        // NOTE: extraData may be null when the _x sessionStorage key doesn't exist.
        // Do NOT bail out early — the phpCards > 0 branch must run regardless.

        if (extraData && checkbox && extraData.checked !== undefined) {
            checkbox.checked = !!extraData.checked;
            setSectionVisible(!!extraData.checked);
        }

        var phpCards = container.querySelectorAll('.phase-card').length;

        if (phpCards > 0) {
            // Edit mode: PHP already rendered cards with correct Firestore data.
            // doRestore() in main.php ran first and may have set stale sessionStorage
            // values on the unit selects (and other fields). Undo that by resetting
            // every phase card field back to the PHP-rendered attribute default.
            container.querySelectorAll('.phase-card input, .phase-card select').forEach(function (el) {
                if (el.tagName === 'SELECT') {
                    Array.from(el.options).forEach(function (opt) { opt.selected = opt.defaultSelected; });
                } else if (el.type !== 'button' && el.type !== 'submit') {
                    el.value = el.defaultValue;
                }
            });
            updateTotal();
            // Clear stale storage so future visits don't repeat the override.
            if (window.pzAutoSave) window.pzAutoSave.clear(document.getElementById('projectForm'));
        } else if (extraData && Array.isArray(extraData.phases) && extraData.phases.length > 0) {
            // Create mode: no PHP-rendered cards — restore from sessionStorage.
            container.innerHTML = '';
            extraData.phases.forEach(function (ph, pi) {
                container.appendChild(buildPhaseItem(pi, ph));
            });
            updateTotal();
        }

        // Refresh date display inputs from their restored hidden values
        function refreshDisp(hidId, dispId) {
            var h = document.getElementById(hidId), d = document.getElementById(dispId);
            if (!h || !d || !h.value) return;
            try {
                var dt = new Date(h.value);
                if (!isNaN(dt)) {
                    d.value = ('0' + dt.getDate()).slice(-2) + '/' +
                              ('0' + (dt.getMonth() + 1)).slice(-2) + '/' +
                              dt.getFullYear();
                }
            } catch (e) {}
        }
        refreshDisp('start_date', 'start_date_display');
        refreshDisp('end_date',   'end_date_display');
    };
}());
</script>

<!-- ── Date picker JS (unchanged) ────────────────────────── -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // opts: { getMin, getMax, onSelect }
    // getMin/getMax: functions returning 'YYYY-MM-DD' constraint, or ''
    // onSelect: function(value) called after a date is committed
    function createCalendar(container, inputHidden, inputDisplay, opts) {
        opts = opts || {};
        var getMin  = opts.getMin  || function () { return ''; };
        var getMax  = opts.getMax  || function () { return ''; };
        var onSelect = opts.onSelect || function () {};

        var now = new Date(), viewYear = now.getFullYear(), viewMonth = now.getMonth();
        var mode = 'days', yearsStart = Math.floor(viewYear / 12) * 12;
        var header = document.createElement('div'); header.className = 'cal-header';
        var prev = document.createElement('button'); prev.type = 'button'; prev.className = 'cal-nav-btn'; prev.innerHTML = '<span class="material-symbols-outlined">chevron_left</span>';
        var title = document.createElement('div'); title.className = 'cal-title';
        var next = document.createElement('button'); next.type = 'button'; next.className = 'cal-nav-btn'; next.innerHTML = '<span class="material-symbols-outlined">chevron_right</span>';
        header.appendChild(prev); header.appendChild(title); header.appendChild(next);
        var grid = document.createElement('div'); grid.className = 'cal-grid';
        container.appendChild(header); container.appendChild(grid);

        function pad(n) { return String(n).padStart(2, '0'); }

        function renderDays() {
            title.textContent = new Date(viewYear, viewMonth).toLocaleString(undefined, { month: 'long', year: 'numeric' });
            title.setAttribute('data-mode', 'days'); grid.className = 'cal-grid'; grid.innerHTML = '';
            ['Su','Mo','Tu','We','Th','Fr','Sa'].forEach(function (d) { var e = document.createElement('div'); e.className = 'cal-day'; e.style.fontWeight = '600'; e.textContent = d; e.style.cursor = 'default'; grid.appendChild(e); });
            var first = new Date(viewYear, viewMonth, 1), start = first.getDay(), days = new Date(viewYear, viewMonth + 1, 0).getDate();
            var minVal = getMin(); var maxVal = getMax();
            for (var i = 0; i < start; i++) { var e = document.createElement('div'); e.className = 'cal-day disabled'; grid.appendChild(e); }
            for (var d = 1; d <= days; d++) {
                (function (day) {
                    var cellDate = viewYear + '-' + pad(viewMonth + 1) + '-' + pad(day);
                    var isDisabled = (minVal && cellDate < minVal) || (maxVal && cellDate > maxVal);
                    var e = document.createElement('div');
                    e.className = 'cal-day' + (isDisabled ? ' disabled' : '');
                    e.textContent = day;
                    if (!isDisabled) {
                        e.addEventListener('click', function () {
                            inputHidden.value = cellDate;
                            inputDisplay.value = pad(day) + '/' + pad(viewMonth + 1) + '/' + viewYear;
                            inputHidden.dispatchEvent(new Event('change'));
                            container.classList.remove('open'); container.setAttribute('aria-hidden', 'true');
                            onSelect(cellDate);
                        });
                    }
                    grid.appendChild(e);
                }(d));
            }
        }

        function renderMonths() {
            title.textContent = String(viewYear); title.setAttribute('data-mode', 'months');
            grid.className = 'months-grid'; grid.innerHTML = '';
            Array.from({ length: 12 }, function (_, i) { return new Date(0, i).toLocaleString(undefined, { month: 'short' }); }).forEach(function (mn, mi) {
                var cell = document.createElement('div'); cell.className = 'month-cell'; cell.textContent = mn;
                cell.addEventListener('click', function () { viewMonth = mi; mode = 'days'; render(); });
                grid.appendChild(cell);
            });
        }

        function renderYears() {
            title.textContent = yearsStart + ' - ' + (yearsStart + 11); title.setAttribute('data-mode', 'years');
            grid.className = 'year-grid'; grid.innerHTML = '';
            for (var y = yearsStart; y < yearsStart + 12; y++) {
                (function (yr) {
                    var cell = document.createElement('div'); cell.className = 'year-cell'; cell.textContent = yr;
                    if (yr === viewYear) cell.classList.add('active');
                    cell.addEventListener('click', function () { viewYear = yr; mode = 'months'; render(); });
                    grid.appendChild(cell);
                }(y));
            }
        }

        function render() { if (mode === 'days') return renderDays(); if (mode === 'months') return renderMonths(); renderYears(); }

        prev.addEventListener('click', function () {
            if (mode === 'days') { viewMonth--; if (viewMonth < 0) { viewMonth = 11; viewYear--; } }
            else if (mode === 'months') { viewYear--; }
            else { yearsStart -= 12; }
            render();
        });
        next.addEventListener('click', function () {
            if (mode === 'days') { viewMonth++; if (viewMonth > 11) { viewMonth = 0; viewYear++; } }
            else if (mode === 'months') { viewYear++; }
            else { yearsStart += 12; }
            render();
        });
        title.addEventListener('click', function () {
            if (mode === 'days') mode = 'months';
            else if (mode === 'months') { mode = 'years'; yearsStart = Math.floor(viewYear / 12) * 12; }
            else mode = 'days';
            render();
        });
        if (inputHidden.value) {
            try { var dt = new Date(inputHidden.value); if (!isNaN(dt)) { viewYear = dt.getFullYear(); viewMonth = dt.getMonth(); inputDisplay.value = pad(dt.getDate()) + '/' + pad(dt.getMonth() + 1) + '/' + dt.getFullYear(); } } catch (e) {}
        }
        // Re-render when the popup opens so constraints reflect the current partner value
        container._rerender = render;
        render();
    }

    function initDatePickerWrapper(wrapper) {
        var hidden  = wrapper.querySelector('input[type="hidden"]');
        var display = wrapper.querySelector('.date-display');
        var toggle  = wrapper.querySelector('.date-toggle');
        var popup   = wrapper.querySelector('.calendar-popup');
        var target  = wrapper.getAttribute('data-target'); // 'start' or 'end'

        var startHidden  = document.getElementById('start_date');
        var startDisplay = document.getElementById('start_date_display');
        var endHidden    = document.getElementById('end_date');
        var endDisplay   = document.getElementById('end_date_display');

        var opts = {};
        if (target === 'start') {
            opts.getMax  = function () { return endHidden ? endHidden.value : ''; };
            opts.onSelect = function (val) {
                // If end date is now earlier than the newly-picked start, clear it
                if (endHidden && endHidden.value && val > endHidden.value) {
                    endHidden.value  = '';
                    endDisplay.value = '';
                    if (window.showFormToast) window.showFormToast('End date cleared — it must be on or after the start date.');
                }
            };
        } else if (target === 'end') {
            opts.getMin  = function () { return startHidden ? startHidden.value : ''; };
            opts.onSelect = function () {};
        }

        window.pzCreateCalendar(popup, hidden, display, opts);

        toggle.addEventListener('click', function () {
            var open = popup.classList.toggle('open');
            popup.setAttribute('aria-hidden', !open);
            if (open) {
                if (popup._rerender) popup._rerender();
                var r = toggle.getBoundingClientRect();
                popup.style.position = 'fixed';
                popup.style.zIndex   = '99999';
                popup.style.right    = 'auto';
                popup.style.left     = r.left + 'px';
                popup.style.top      = (r.bottom + 6) + 'px';
                popup.style.bottom   = 'auto';
                var popH = popup.offsetHeight || 290;
                if (r.bottom + 6 + popH > window.innerHeight - 12) {
                    popup.style.top    = 'auto';
                    popup.style.bottom = (window.innerHeight - r.top + 6) + 'px';
                }
                var popW = popup.offsetWidth || 280;
                if (r.left + popW > window.innerWidth - 8) {
                    popup.style.left = Math.max(8, window.innerWidth - popW - 8) + 'px';
                }
            }
        });
        document.addEventListener('click', function (e) {
            if (!wrapper.contains(e.target)) { popup.classList.remove('open'); popup.setAttribute('aria-hidden', 'true'); }
        });
    }

    document.querySelectorAll('.date-picker').forEach(initDatePickerWrapper);
    window.initPhaseDatePicker = initDatePickerWrapper;

    // Submit-time guard (catches any edge case the calendar constraints miss)
    document.getElementById('projectForm').addEventListener('submit', function (e) {
        var s = document.getElementById('start_date').value;
        var d = document.getElementById('end_date').value;
        if (s && d && s > d) {
            e.preventDefault();
            e.stopImmediatePropagation();
            if (window.showFormToast) {
                window.showFormToast(['End date must be on or after the start date.']);
            }
            return false;
        }
    });
});
</script>

<script>
document.getElementById('projectForm').addEventListener('input', function (e) {
    if (e.target.matches('input[type="text"]') && !e.target.readOnly) {
        var pos = e.target.selectionStart;
        e.target.value = e.target.value.toUpperCase();
        e.target.setSelectionRange(pos, pos);
    }
});
</script>

<div id="confirmDialog" class="confirm-dialog" role="dialog" aria-modal="true">
    <div class="confirm-backdrop"></div>
    <div class="confirm-box">
        <div class="confirm-hdr">
            <span class="material-symbols-outlined confirm-hdr-icon" id="confirmIcon">warning</span>
            <p class="confirm-hdr-title" id="confirmTitle">Confirm</p>
        </div>
        <div class="confirm-content"><p id="confirmMessage"></p></div>
        <div class="confirm-actions">
            <button id="confirmOk"     class="confirm-btn confirm-btn-action">OK</button>
            <button id="confirmCancel" class="confirm-btn confirm-btn-cancel">Cancel</button>
        </div>
    </div>
</div>
<div id="pzToast" class="custom-toast" aria-live="polite"></div>
<script>
(function () {
    var toast = document.getElementById('pzToast');
    window._pzToast = function (msg, type) {
        if (!toast) return;
        toast.className = 'custom-toast show ' + (type === 'error' ? 'error' : 'success');
        toast.innerText = msg;
        setTimeout(function () { toast.className = 'custom-toast'; }, 3000);
    };
    var cd = document.getElementById('confirmDialog');
    var cm = document.getElementById('confirmMessage');
    var _cr = null;
    window.openConfirmForm = function (message, type) {
        if (_cr) return Promise.resolve(false);
        return new Promise(function (resolve) {
            _cr = resolve;
            var isArchive = type === 'archive';
            cd.classList.toggle('confirm-archive', isArchive);
            var icon  = document.getElementById('confirmIcon');
            var title = document.getElementById('confirmTitle');
            if (icon)  icon.textContent  = isArchive ? 'inventory_2' : 'warning';
            if (title) title.textContent = isArchive ? 'Archive' : 'Confirm';
            cm.innerText = message;
            cd.classList.add('open');
        });
    };
    document.getElementById('confirmOk')?.addEventListener('click', function () {
        if (!_cr) return; var r = _cr; _cr = null; cd.classList.remove('open'); r(true);
    });
    document.getElementById('confirmCancel')?.addEventListener('click', function () {
        if (!_cr) return; var r = _cr; _cr = null; cd.classList.remove('open'); r(false);
    });
})();
</script>

<?= $this->endSection() ?>
