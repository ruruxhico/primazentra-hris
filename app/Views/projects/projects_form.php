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
    <div class="alert-error">✗ <?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<h2><?= $isEdit ? 'Projects - Edit' : 'Projects - Add' ?></h2>

<style>
#projectForm input[type="text"]:not([readonly]) { text-transform: uppercase; }
</style>

<div class="form-container project-form-container">
    <form method="post" action="<?= base_url($projectAction) ?>" id="projectForm">
        <?= csrf_field() ?>

        <div class="form-section">

            <div class="form-group">
                <label for="project_name">Project Name *</label>
                <input type="text" id="project_name" name="project_name"
                       value="<?= esc($project['project_name'] ?? '') ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="project_type">Project Type *</label>
                    <select id="project_type" name="project_type" required>
                        <option value="">SELECT TYPE</option>
                        <?php foreach (['RESIDENTIAL','TOWERS','COMMERCIAL'] as $type): ?>
                            <option value="<?= esc($type) ?>"
                                <?= ($project['project_type'] ?? '') === $type ? 'selected' : '' ?>>
                                <?= esc($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        <option value="">SELECT STATUS</option>
                        <?php foreach (['PLANNED','ON-GOING','COMPLETED','HALTED'] as $status): ?>
                            <option value="<?= esc($status) ?>"
                                <?= ($project['status'] ?? '') === $status ? 'selected' : '' ?>>
                                <?= esc($status) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- ── Location field with Maps integration ──────── -->
            <div class="form-group">
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

            <div class="form-row">
                <div class="form-group">
                    <label for="project_manager_id">Project Manager *</label>
                    <select id="project_manager_id" name="project_manager_id" required>
                        <option value="">SELECT PROJECT MANAGER</option>
                        <?php foreach ($projectManagers ?? [] as $mgr): ?>
                            <?php $label = trim(($mgr['first_name'] ?? '') . ' ' . ($mgr['last_name'] ?? '')); ?>
                            <option value="<?= esc($mgr['id'] ?? '') ?>"
                                <?= (($project['personnel']['project_manager_id'] ?? '') === ($mgr['id'] ?? '')) ? 'selected' : '' ?>>
                                <?= esc($label ?: ($mgr['email'] ?? 'N/A')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="site_engineer_id">Site Engineer *</label>
                    <select id="site_engineer_id" name="site_engineer_id" required>
                        <option value="">SELECT SITE ENGINEER</option>
                        <?php foreach ($siteEngineers ?? [] as $eng): ?>
                            <?php $label = trim(($eng['first_name'] ?? '') . ' ' . ($eng['last_name'] ?? '')); ?>
                            <option value="<?= esc($eng['id'] ?? '') ?>"
                                <?= (($project['personnel']['site_engineer_id'] ?? '') === ($eng['id'] ?? '')) ? 'selected' : '' ?>>
                                <?= esc($label ?: ($eng['email'] ?? 'N/A')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <div class="date-picker" data-target="start">
                        <input type="text" id="start_date_display" class="date-display"
                               placeholder="DD/MM/YYYY"
                               value="<?= esc((($project['start_date_value'] ?? '') !== '') ? date('d/m/Y', strtotime((string)$project['start_date_value'])) : '') ?>"
                               readonly>
                        <input type="hidden" id="start_date" name="start_date"
                               value="<?= esc($project['start_date_value'] ?? ($project['start_date'] ?? '')) ?>">
                        <button type="button" class="date-toggle">📅</button>
                        <div class="calendar-popup" aria-hidden="true"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <div class="date-picker" data-target="end">
                        <input type="text" id="end_date_display" class="date-display"
                               placeholder="DD/MM/YYYY"
                               value="<?= esc((($project['end_date_value'] ?? '') !== '') ? date('d/m/Y', strtotime((string)$project['end_date_value'])) : '') ?>"
                               readonly>
                        <input type="hidden" id="end_date" name="end_date"
                               value="<?= esc($project['end_date_value'] ?? ($project['end_date'] ?? '')) ?>">
                        <button type="button" class="date-toggle">📅</button>
                        <div class="calendar-popup" aria-hidden="true"></div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="budget">Budget *</label>
                <input type="number" id="budget" name="budget"
                       min="0" step="0.01"
                       value="<?= esc($project['budget'] ?? '') ?>" required>
            </div>

        </div><!-- /form-section -->

        <!-- ── Project Schedule (phase accordion) ─────────── -->
        <div class="form-section">
            <?php $hasSavedPhases = !empty($phases); ?>

            <div class="form-group add-tasks-toggle">
                <label class="checkbox-label">
                    <input type="checkbox" id="add_tasks_now" name="add_tasks_now"
                           <?= $hasSavedPhases ? 'checked' : '' ?>>
                    <span class="checkbox-custom"></span>
                    Add project schedule
                </label>
            </div>

            <input type="hidden" id="overall_progress" name="overall_progress"
                   value="<?= esc($project['overall_progress'] ?? ($project['completion_percent'] ?? 0)) ?>">

            <div id="scheduleSection" <?= $hasSavedPhases ? '' : 'style="display:none"' ?>>
                <div class="section-title-row">
                    <h2>Project Schedule</h2>
                    <button type="button" id="addPhaseBtn" class="btn btn-add">+ Add Phase</button>
                </div>

                <!-- Schedule table -->
                <div class="phase-schedule">
                    <div class="schedule-hdr">
                        <span>PHASE</span>
                        <span>WEIGHT %</span>
                        <span></span>
                    </div>

                    <div id="phasesContainer">
                        <?php foreach ($phases as $pIdx => $phase): ?>
                        <div class="phase-item" data-pidx="<?= $pIdx ?>">
                            <div class="phase-hdr-row">
                                <input type="text"   name="phases[<?= $pIdx ?>][name]"
                                       class="phase-name-inp"
                                       value="<?= esc($phase['name'] ?? '') ?>"
                                       placeholder="Phase name">
                                <div class="phase-weight-cell">
                                    <input type="number" name="phases[<?= $pIdx ?>][weight]"
                                           class="phase-weight-inp"
                                           value="<?= esc(number_format((float)($phase['weight'] ?? 0), 1)) ?>"
                                           min="0" max="100" step="0.1">
                                    <span class="pct-label">%</span>
                                </div>
                                <div class="phase-actions">
                                    <button type="button" class="phase-del-btn" title="Delete phase">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                    <button type="button" class="phase-toggle-btn" title="Toggle tasks">
                                        <span class="material-symbols-outlined">keyboard_arrow_down</span>
                                    </button>
                                </div>
                            </div>
                            <div class="phase-tasks-wrap" style="display:none">
                                <?php foreach (($phase['tasks'] ?? []) as $tIdx => $task): ?>
                                <div class="phase-task-row">
                                    <input type="hidden" name="phases[<?= $pIdx ?>][tasks][<?= $tIdx ?>][id]"
                                           value="<?= esc($task['id'] ?? '') ?>">
                                    <input type="text"
                                           name="phases[<?= $pIdx ?>][tasks][<?= $tIdx ?>][task_name]"
                                           class="task-name-inp"
                                           value="<?= esc($task['task_name'] ?? '') ?>"
                                           placeholder="Task name">
                                    <div class="task-meta-row">
                                        <span class="task-field-label">Qty:</span>
                                        <input type="number"
                                               name="phases[<?= $pIdx ?>][tasks][<?= $tIdx ?>][qty]"
                                               class="task-qty-inp" placeholder="0" min="0"
                                               value="<?= esc($task['qty'] ?? 0) ?>">
                                        <span class="task-field-label">Unit:</span>
                                        <select name="phases[<?= $pIdx ?>][tasks][<?= $tIdx ?>][unit]"
                                                class="task-unit-sel">
                                            <?php foreach (['pcs','hrs','sqm','lm','lot','set'] as $u): ?>
                                            <option value="<?= $u ?>" <?= ($task['unit'] ?? 'pcs') === $u ? 'selected' : '' ?>><?= $u ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <select name="phases[<?= $pIdx ?>][tasks][<?= $tIdx ?>][status]"
                                                class="task-status-sel">
                                            <?php foreach (['Pending','In Progress','Done'] as $s): ?>
                                            <option value="<?= $s ?>" <?= ($task['status'] ?? 'Pending') === $s ? 'selected' : '' ?>><?= $s ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="task-del-btn" title="Remove task">
                                            <span class="material-symbols-outlined">close</span>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <button type="button" class="btn-add-task">+ Add Task</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="schedule-total-row">
                        <span>TOTAL</span>
                        <span id="totalWeightDisplay">0.0%</span>
                        <span></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <?= $isEdit ? 'Update Project' : 'Create Project' ?>
            </button>
            <a href="<?= base_url('projects') ?>" class="btn btn-secondary">Cancel</a>
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
            alert('Geolocation is not supported by your browser.');
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
                alert('Unable to retrieve your location. Please check your browser permissions.');
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

<!-- ── Phase Schedule JS ──────────────────────────────────── -->
<script>
(function () {
    // ── Predefined phase templates (same structure for all types) ──
    var PHASE_TEMPLATE = [
        { name: 'Mobilization & Site Setup',  weight: 9.7  },
        { name: 'Concrete Floor Polishing',   weight: 9.1  },
        { name: 'Plumbing',                   weight: 3.3  },
        { name: 'CHB & Drywall Works',        weight: 11.4 },
        { name: 'HVAC',                       weight: 15.9 },
        { name: 'FDAS',                       weight: 4.7  },
        { name: 'Electrical',                 weight: 15.6 },
        { name: 'Electronics',                weight: 2.4  },
        { name: 'Ceiling Works',              weight: 10.2 },
        { name: 'Floor Tiles',                weight: 3.1  },
        { name: 'Painting Works',             weight: 4.5  },
        { name: 'Doors & Glazing',            weight: 4.5  },
        { name: 'Cabinetry',                  weight: 1.6  },
        { name: 'Furniture Installation',     weight: 3.4  },
        { name: 'Testing & Commissioning',    weight: 0.6  },
    ];

    var container      = document.getElementById('phasesContainer');
    var scheduleSection = document.getElementById('scheduleSection');
    var totalDisplay   = document.getElementById('totalWeightDisplay');
    var progressInput  = document.getElementById('overall_progress');
    var checkbox       = document.getElementById('add_tasks_now');
    var addPhaseBtn    = document.getElementById('addPhaseBtn');
    var projectTypeEl  = document.getElementById('project_type');

    // ── Show / hide schedule section ──────────────────────────
    function setSectionVisible(v) {
        if (scheduleSection) scheduleSection.style.display = v ? '' : 'none';
    }
    if (checkbox) {
        setSectionVisible(checkbox.checked);
        checkbox.addEventListener('change', function () { setSectionVisible(this.checked); });
    }

    // ── Reindex all phase/task name attributes ─────────────────
    function reindex() {
        container.querySelectorAll('.phase-item').forEach(function (phase, pi) {
            phase.dataset.pidx = pi;
            phase.querySelectorAll('[name]').forEach(function (el) {
                el.name = el.name
                    .replace(/phases\[\d+\]/, 'phases[' + pi + ']')
                    .replace(/tasks\[\d+\]/, function (m, offset, str) {
                        // find which task row this belongs to
                        var taskRow = el.closest('.phase-task-row');
                        if (!taskRow) return m;
                        var siblings = Array.from(taskRow.parentElement.querySelectorAll('.phase-task-row'));
                        var ti = siblings.indexOf(taskRow);
                        return 'tasks[' + ti + ']';
                    });
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
            totalDisplay.style.color = (Math.abs(total - 100) < 0.2) ? '#4caf50' : '#e53935';
        }
        if (progressInput) progressInput.value = 0; // recalculated server-side
    }

    // ── Build a task row element ───────────────────────────────
    function buildTaskRow(pi, ti, data) {
        data = data || {};
        var units = ['pcs','hrs','days','sqm','lm','lot','set', 'kgs'];
        var statuses = ['Pending','In Progress','Done'];

        var row = document.createElement('div');
        row.className = 'phase-task-row';
        row.innerHTML =
            '<input type="hidden" name="phases[' + pi + '][tasks][' + ti + '][id]" value="' + (data.id || '') + '">' +
            '<input type="text" name="phases[' + pi + '][tasks][' + ti + '][task_name]" class="task-name-inp" placeholder="Task name" value="' + escHtml(data.task_name || '') + '">' +
            '<div class="task-meta-row">' +
                '<span class="task-field-label">Qty:</span>' +
                '<input type="number" name="phases[' + pi + '][tasks][' + ti + '][qty]" class="task-qty-inp" placeholder="0" min="0" value="' + (data.qty || 0) + '">' +
                '<span class="task-field-label">Unit:</span>' +
                '<select name="phases[' + pi + '][tasks][' + ti + '][unit]" class="task-unit-sel">' +
                    units.map(function(u){ return '<option value="' + u + '"' + (data.unit === u ? ' selected' : '') + '>' + u + '</option>'; }).join('') +
                '</select>' +
                '<select name="phases[' + pi + '][tasks][' + ti + '][status]" class="task-status-sel">' +
                    statuses.map(function(s){ return '<option value="' + s + '"' + ((data.status || 'Pending') === s ? ' selected' : '') + '>' + s + '</option>'; }).join('') +
                '</select>' +
                '<button type="button" class="task-del-btn" title="Remove task"><span class="material-symbols-outlined">close</span></button>' +
            '</div>';

        row.querySelector('.task-del-btn').addEventListener('click', function () {
            row.remove(); reindex(); updateTotal();
        });
        return row;
    }

    // ── Build a full phase item element ────────────────────────
    function buildPhaseItem(pi, data) {
        data = data || {};
        var item = document.createElement('div');
        item.className = 'phase-item';
        item.dataset.pidx = pi;
        item.innerHTML =
            '<div class="phase-hdr-row">' +
                '<input type="text" name="phases[' + pi + '][name]" class="phase-name-inp" placeholder="Phase name" value="' + escHtml(data.name || '') + '">' +
                '<div class="phase-weight-cell">' +
                    '<input type="number" name="phases[' + pi + '][weight]" class="phase-weight-inp" min="0" max="100" step="0.1" value="' + (data.weight != null ? data.weight : '') + '">' +
                    '<span class="pct-label">%</span>' +
                '</div>' +
                '<div class="phase-actions">' +
                    '<button type="button" class="phase-del-btn" title="Delete phase"><span class="material-symbols-outlined">delete</span></button>' +
                    '<button type="button" class="phase-toggle-btn" title="Toggle tasks"><span class="material-symbols-outlined">keyboard_arrow_down</span></button>' +
                '</div>' +
            '</div>' +
            '<div class="phase-tasks-wrap" style="display:none">' +
                '<button type="button" class="btn-add-task">+ Add Task</button>' +
            '</div>';

        var tasksWrap = item.querySelector('.phase-tasks-wrap');
        var addTaskBtn2 = item.querySelector('.btn-add-task');

        // Pre-populate tasks
        if (data.tasks && data.tasks.length) {
            data.tasks.forEach(function (t, ti) {
                tasksWrap.insertBefore(buildTaskRow(pi, ti, t), addTaskBtn2);
            });
        }

        // Weight input → update total
        item.querySelector('.phase-weight-inp').addEventListener('input', updateTotal);

        // Delete phase
        item.querySelector('.phase-del-btn').addEventListener('click', function () {
            item.remove(); reindex(); updateTotal();
        });

        // Toggle expand / collapse
        item.querySelector('.phase-toggle-btn').addEventListener('click', function () {
            var wrap = item.querySelector('.phase-tasks-wrap');
            var icon = this.querySelector('.material-symbols-outlined');
            var open = wrap.style.display === 'none';
            wrap.style.display = open ? '' : 'none';
            icon.textContent = open ? 'keyboard_arrow_up' : 'keyboard_arrow_down';
        });

        // Add task button
        addTaskBtn2.addEventListener('click', function () {
            var ti = tasksWrap.querySelectorAll('.phase-task-row').length;
            var newRow = buildTaskRow(pi, ti, {});
            tasksWrap.insertBefore(newRow, addTaskBtn2);
            reindex();
        });

        return item;
    }

    // ── Attach events to PHP-rendered phase items ──────────────
    function attachExistingPhase(item) {
        var pi = parseInt(item.dataset.pidx, 10);
        var tasksWrap = item.querySelector('.phase-tasks-wrap');
        var addTaskBtn2 = item.querySelector('.btn-add-task');

        item.querySelector('.phase-weight-inp').addEventListener('input', updateTotal);

        item.querySelector('.phase-del-btn').addEventListener('click', function () {
            item.remove(); reindex(); updateTotal();
        });

        item.querySelector('.phase-toggle-btn').addEventListener('click', function () {
            var wrap = item.querySelector('.phase-tasks-wrap');
            var icon = this.querySelector('.material-symbols-outlined');
            var open = wrap.style.display === 'none';
            wrap.style.display = open ? '' : 'none';
            icon.textContent = open ? 'keyboard_arrow_up' : 'keyboard_arrow_down';
        });

        if (addTaskBtn2) {
            addTaskBtn2.addEventListener('click', function () {
                var ti = tasksWrap.querySelectorAll('.phase-task-row').length;
                var newRow = buildTaskRow(pi, ti, {});
                tasksWrap.insertBefore(newRow, addTaskBtn2);
                reindex();
            });
        }

        tasksWrap.querySelectorAll('.task-del-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                btn.closest('.phase-task-row').remove(); reindex(); updateTotal();
            });
        });
    }
    container.querySelectorAll('.phase-item').forEach(attachExistingPhase);

    // ── Add Phase button ───────────────────────────────────────
    if (addPhaseBtn) {
        addPhaseBtn.addEventListener('click', function () {
            if (checkbox && !checkbox.checked) { checkbox.checked = true; setSectionVisible(true); }
            var pi = container.querySelectorAll('.phase-item').length;
            container.appendChild(buildPhaseItem(pi, {}));
            updateTotal();
        });
    }

    // ── Auto-fill phases when project type changes ─────────────
    if (projectTypeEl) {
        projectTypeEl.addEventListener('change', function () {
            if (!this.value) return;
            var existing = container.querySelectorAll('.phase-item').length;
            if (existing > 0) {
                if (!confirm('Auto-fill will replace the current phase schedule. Continue?')) return;
            }
            container.innerHTML = '';
            PHASE_TEMPLATE.forEach(function (phase, pi) {
                container.appendChild(buildPhaseItem(pi, phase));
            });
            if (checkbox && !checkbox.checked) { checkbox.checked = true; setSectionVisible(true); }
            updateTotal();
        });
    }

    // ── Escape helper ──────────────────────────────────────────
    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    updateTotal();
}());
</script>

<!-- ── Date picker JS (unchanged) ────────────────────────── -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    function createCalendar(container, inputHidden, inputDisplay) {
        var now = new Date(), viewYear = now.getFullYear(), viewMonth = now.getMonth();
        var mode = 'days', yearsStart = Math.floor(viewYear / 12) * 12;
        var header = document.createElement('div'); header.className = 'cal-header';
        var prev = document.createElement('button'); prev.type = 'button'; prev.textContent = '◀';
        var title = document.createElement('div'); title.className = 'cal-title';
        var next = document.createElement('button'); next.type = 'button'; next.textContent = '▶';
        header.appendChild(prev); header.appendChild(title); header.appendChild(next);
        var grid = document.createElement('div'); grid.className = 'cal-grid';
        container.appendChild(header); container.appendChild(grid);

        function renderDays() {
            title.textContent = new Date(viewYear, viewMonth).toLocaleString(undefined, { month: 'long', year: 'numeric' });
            title.setAttribute('data-mode', 'days'); grid.className = 'cal-grid'; grid.innerHTML = '';
            ['Su','Mo','Tu','We','Th','Fr','Sa'].forEach(function (d) { var e = document.createElement('div'); e.className = 'cal-day'; e.style.fontWeight = '600'; e.textContent = d; e.style.cursor = 'default'; grid.appendChild(e); });
            var first = new Date(viewYear, viewMonth, 1), start = first.getDay(), days = new Date(viewYear, viewMonth + 1, 0).getDate();
            for (var i = 0; i < start; i++) { var e = document.createElement('div'); e.className = 'cal-day disabled'; grid.appendChild(e); }
            for (var d = 1; d <= days; d++) {
                (function (day) {
                    var e = document.createElement('div'); e.className = 'cal-day'; e.textContent = day;
                    e.addEventListener('click', function () {
                        inputHidden.value = viewYear + '-' + String(viewMonth + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                        inputDisplay.value = String(day).padStart(2, '0') + '/' + String(viewMonth + 1).padStart(2, '0') + '/' + viewYear;
                        container.classList.remove('open'); container.setAttribute('aria-hidden', 'true');
                    });
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
            try { var dt = new Date(inputHidden.value); if (!isNaN(dt)) { viewYear = dt.getFullYear(); viewMonth = dt.getMonth(); inputDisplay.value = String(dt.getDate()).padStart(2, '0') + '/' + String(dt.getMonth() + 1).padStart(2, '0') + '/' + dt.getFullYear(); } } catch (e) {}
        }
        render();
    }

    document.querySelectorAll('.date-picker').forEach(function (wrapper) {
        var hidden = wrapper.querySelector('input[type="hidden"]');
        var display = wrapper.querySelector('.date-display');
        var toggle = wrapper.querySelector('.date-toggle');
        var popup = wrapper.querySelector('.calendar-popup');
        createCalendar(popup, hidden, display);
        toggle.addEventListener('click', function () { var open = popup.classList.toggle('open'); popup.setAttribute('aria-hidden', !open); });
        document.addEventListener('click', function (e) { if (!wrapper.contains(e.target)) { popup.classList.remove('open'); popup.setAttribute('aria-hidden', 'true'); } });
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

<?= $this->endSection() ?>
