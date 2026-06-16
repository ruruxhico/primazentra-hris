<?= $this->extend('layouts/main') ?>

<?= $this->section('head') ?>
<?php if (!empty($googleMapsBrowserKey)): ?>
<script>
window.__monitorMapKey = '<?= esc($googleMapsBrowserKey ?? '') ?>';
</script>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div id="dndToast"></div>

<!-- Transfer confirm modal -->
<div id="dndConfirmOverlay">
    <div id="dndConfirmBox">
        <div class="dnd-modal-title">
            <span class="material-symbols-outlined">sync_alt</span>
            Transfer Resource
        </div>
        <div class="dnd-modal-body">
            Create a resource request to transfer <strong id="dndResLabel"></strong>
            <div class="dnd-modal-label">from</div>
            <div class="dnd-modal-project" id="dndSrcLabel"></div>
            <div class="dnd-modal-label">to</div>
            <div class="dnd-modal-project" id="dndDestLabel"></div>
        </div>
        <div class="dnd-modal-actions">
            <button class="dnd-modal-cancel" id="dndCancelBtn">Cancel</button>
            <button class="dnd-modal-confirm" id="dndConfirmBtn">Confirm Request</button>
        </div>
    </div>
</div>

<?php if (!empty($errorMessage)): ?>
<div class="alert alert-warning mb-3"><?= esc((string)$errorMessage) ?></div>
<?php endif; ?>

<div class="db-root">

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     SUMMARY SECTION
     â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<section id="summary" class="db-section">

    <p class="db-today">Today,</p>
    <p class="db-today-date"><?= date('F j, Y') ?></p>

    <div class="db-info-row">

        <!-- BLOCKERS --------------------------------------- -->
        <div class="db-info-col">
            <div class="db-info-card">
                <div class="db-info-title">Blockers</div>
                <?php if (empty($blockers)): ?>
                    <p class="db-empty">No blockers â€” all projects on track.</p>
                <?php else: ?>
                    <div class="db-blockers-list">
                    <?php foreach (array_slice($blockers, 0, 5) as $bl):
                        $endTs     = is_numeric($bl['end_date'] ?? '') ? (int)$bl['end_date'] : @strtotime((string)($bl['end_date'] ?? ''));
                        $delayDays = ($endTs && $endTs < time()) ? (int)floor((time() - $endTs) / 86400) : 0;
                        $reason    = $bl['reason'] ?? $bl['blocker_reason'] ?? (($bl['status'] ?? '') === 'Halted' ? 'Project Halted' : 'Low Progress');
                    ?>
                    <div class="db-blocker-box">
                        <span class="db-blocker-name"><?= esc(strtoupper($bl['project_name'] ?? 'Project')) ?></span>
                        <?php if ($delayDays > 0): ?>
                        <span class="db-blocker-sep">Â·</span>
                        <span class="db-blocker-line"><?= $delayDays ?>d delay</span>
                        <?php endif; ?>
                        <span class="db-blocker-sep">Â·</span>
                        <span class="db-blocker-line"><?= esc(ucwords((string)$reason)) ?></span>
                    </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- AVAILABLE TOOLS -------------------------------- -->
        <div class="db-info-col">
            <div class="db-info-card">
                <div class="db-info-title">Available Tools</div>
                <?php if (empty($availableTools)): ?>
                    <p class="db-empty">No tools available.</p>
                <?php else: ?>
                    <table class="db-info-tbl">
                        <thead><tr><th>Name</th><th>Qty</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($availableTools, 0, 8) as $t): ?>
                            <tr>
                                <td><?= esc(strtoupper($t['name'] ?? 'N/A')) ?></td>
                                <td><?= esc((string)($t['asset_details']['available_quantity'] ?? $t['available_units_display'] ?? 'â€”')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- AVAILABLE TO DEPLOY ---------------------------- -->
        <div class="db-info-col">
            <div class="db-info-card">
                <div class="db-info-title">Available to Deploy</div>
                <?php if (empty($availableManpower)): ?>
                    <p class="db-empty">No manpower available.</p>
                <?php else: ?>
                    <table class="db-info-tbl">
                        <thead><tr><th>Name</th><th>Role</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($availableManpower, 0, 8) as $mp): ?>
                            <tr>
                                <td><?= esc(strtoupper($mp['name'] ?? 'N/A')) ?></td>
                                <td><?= esc(strtoupper($mp['manpower_details']['role'] ?? $mp['role'] ?? 'Field Worker')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /db-info-row -->

</section><!-- #summary -->


<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     MONITOR SECTION  â€”  cards (left) + sticky map (right)
     â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<section id="monitor" class="db-section">

    <?php if (empty($projectsByRegion)): ?>
        <p class="db-empty">No active projects to monitor.</p>
    <?php else: ?>

    <?php
    // Build sorted unique client list for the filter dropdown
    $monitorClients = [];
    foreach ($activeProjects as $_mp) {
        $c = $_mp['_client'] ?? '';
        if ($c !== '') $monitorClients[$c] = true;
    }
    ksort($monitorClients);
    $monitorClients = array_keys($monitorClients);
    ?>

    <!-- -- Filter toggle + dropdown -- -->
    <div class="db-filter-wrap">
        <button type="button" class="db-filter-toggle-btn" id="dbFilterToggle">
            <span class="db-filter-arrow">▼</span> Filter
        </button>
        <div class="db-filter-dropdown" id="dbFilterDropdown">
            <div class="db-filter-bar">
                <div class="db-filter-row">
                    <span class="db-filter-label">Region</span>
                    <div class="db-filter-pills" id="dbRegionPills">
                        <button type="button" class="db-filter-pill active" data-region="ALL">All</button>
                        <button type="button" class="db-filter-pill" data-region="LUZON">Luzon</button>
                        <button type="button" class="db-filter-pill" data-region="VISAYAS">Visayas</button>
                        <button type="button" class="db-filter-pill" data-region="MINDANAO">Mindanao</button>
                    </div>
                </div>
                <div class="db-filter-row">
                    <span class="db-filter-label">Category</span>
                    <div class="db-filter-pills" id="dbTypePills">
                        <button type="button" class="db-filter-pill active" data-type="ALL">All</button>
                        <button type="button" class="db-filter-pill" data-type="NEW BUILD">New Build</button>
                        <button type="button" class="db-filter-pill" data-type="RETROFITTING">Retrofitting</button>
                        <button type="button" class="db-filter-pill" data-type="COLOCATION">Colocation</button>
                        <button type="button" class="db-filter-pill" data-type="CONVERSION">Conversion</button>
                    </div>
                </div>
                <div class="db-filter-row">
                    <span class="db-filter-label">Status</span>
                    <div class="db-filter-pills" id="dbStatusPills">
                        <button type="button" class="db-filter-pill active" data-status="ALL">All</button>
                        <button type="button" class="db-filter-pill" data-status="ONGOING">Ongoing</button>
                        <button type="button" class="db-filter-pill" data-status="DONE">Done</button>
                    </div>
                </div>
                <?php if (!empty($monitorClients)): ?>
                <div class="db-filter-row">
                    <span class="db-filter-label">Client</span>
                    <select class="db-filter-client-sel" id="dbClientFilter">
                        <option value="ALL">All Clients</option>
                        <?php foreach ($monitorClients as $cl): ?>
                        <option value="<?= esc($cl) ?>"><?= esc($cl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div><!-- .db-filter-bar -->
        </div><!-- .db-filter-dropdown -->
    </div><!-- .db-filter-wrap -->

    <!-- -- Project card groups -- -->
    <?php foreach ($projectsByRegion as $region => $regionProjects): ?>
    <div class="db-monitor-region" data-region-group="<?= esc($region) ?>">
        <h3 class="db-region-title"><?= esc($region) ?></h3>
        <div class="row g-3 mb-2">
            <?php foreach ($regionProjects as $proj): ?>
            <div class="col-xl-6 col-12 db-monitor-col"
                 data-region="<?= esc($proj['_region']) ?>"
                 data-client="<?= esc($proj['_client'] ?? '') ?>"
                 data-type="<?= esc($proj['_project_type'] ?? '') ?>"
                 data-status="<?= esc($proj['_status'] ?? 'ONGOING') ?>">
                <?php echo db_render_card($proj, 'monitor'); ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="db-monitor-no-results" id="dbNoResults">
        <div style="font-size:1.5rem;margin-bottom:0.5rem;">📋</div>
        No projects match the selected filters.
    </div>

    <?php endif; ?>

</section><!-- #monitor -->

</div><!-- .db-root -->

<!-- -- Right map sidebar panel ----------------------------- -->
<div id="mapSidePanel">
    <div class="map-panel-hdr" onclick="toggleMapPanel()">
        <button class="map-panel-toggle" onclick="event.stopPropagation(); toggleMapPanel()" title="Toggle map panel">
            <span class="material-symbols-outlined">chevron_right</span>
        </button>
        <span class="map-panel-hdr-label">
            <span class="material-symbols-outlined">map</span>
            Project Sites
        </span>
        <span class="map-panel-hdr-spacer"></span>
    </div>
    <div id="monitorMap"></div>
    <div class="map-legend">
        <span class="map-legend-item"><span class="map-legend-dot" style="background:#E53935"></span>New Build</span>
        <span class="map-legend-item"><span class="map-legend-dot" style="background:#1E88E5"></span>Retrofitting</span>
        <span class="map-legend-item"><span class="map-legend-dot" style="background:#FDD835"></span>Colocation</span>
        <span class="map-legend-item"><span class="map-legend-dot" style="background:#7B1FA2"></span>Conversion</span>
    </div>
</div>

<?php
/**
 * Render a single project card (shared by Summary and Monitor sections).
 */
function db_render_card(array $p, string $context = 'summary'): string
{
    $name      = esc(strtoupper($p['project_name'] ?? 'Project'));
    $progress  = min(100, max(0, (float)$p['_progress']));
    $startFmt  = esc($p['_start_fmt']);
    $endFmt    = esc($p['_end_fmt']);
    $equipment = (array)($p['_equipment'] ?? []);
    $tools     = (array)($p['_tools']     ?? []);
    $manpower  = (array)($p['_manpower']  ?? []);
    $mapsUrl   = esc($p['_maps_url']);
    $cardId    = 'card-' . preg_replace('/[^a-zA-Z0-9]/', '', (string)($p['id'] ?? uniqid()));
    $lat       = $p['_lat'] ?? null;
    $lng       = $p['_lng'] ?? null;

    ob_start();
    ?>
    <div class="db-project-card" id="<?= $cardId ?>" data-pid="<?= esc($p['id'] ?? '') ?>" data-pname="<?= esc(strtoupper($p['project_name'] ?? '')) ?>">

        <!-- Project name -->
        <div class="db-project-name-box"><?= $name ?></div>

        <!-- Progress bar -->
        <div class="db-progress">
            <div class="db-progress-fill" style="width:<?= $progress ?>%"></div>
            <span class="db-progress-pct"><?= number_format($progress, 0) ?>%</span>
        </div>

        <!-- Dates -->
        <div class="db-dates">
            <div class="db-date-row">
                <span class="db-date-label">Start Date:</span>
                <span class="db-date-val"><?= $startFmt ?></span>
            </div>
            <div class="db-date-row">
                <span class="db-date-label">End Date:</span>
                <span class="db-date-val"><?= $endFmt ?></span>
            </div>
        </div>

        <!-- Resource accordion â€” Equipment / Tools / Manpower -->
        <div class="db-res-accordion">
        <?php
        $resGroups = [
            'equipment' => ['label' => 'Equipment', 'icon' => 'construction', 'items' => $equipment],
            'tools'     => ['label' => 'Tools',     'icon' => 'handyman',     'items' => $tools],
            'manpower'  => ['label' => 'Manpower',  'icon' => 'engineering',  'items' => $manpower],
        ];
        foreach ($resGroups as $cat => $grp):
        ?>
            <div class="db-res-group open" data-cat="<?= $cat ?>">
                <div class="db-res-group-hdr" onclick="dbToggleGroup(this.parentElement)">
                    <span class="material-symbols-outlined db-res-chevron">chevron_right</span>
                    <span class="material-symbols-outlined"><?= $grp['icon'] ?></span>
                    <?= $grp['label'] ?>
                    <span class="db-res-group-count"><?= count($grp['items']) ?></span>
                </div>
                <div class="db-res-group-body">
                    <div class="db-res-drop-zone" data-cat="<?= $cat ?>" data-proj="<?= $cardId ?>" data-pid="<?= esc($p['id'] ?? '') ?>">
                        <?php foreach ($grp['items'] as $res):
                            $resName  = is_array($res) ? ($res['name']    ?? '') : $res;
                            $resId    = is_array($res) ? ($res['id']      ?? '') : '';
                            $resUnitId= is_array($res) ? ($res['unit_id'] ?? '') : '';
                        ?>
                        <div class="db-pill"
                             data-res-name="<?= esc($resName) ?>"
                             data-res-id="<?= esc($resId) ?>"
                             data-unit-id="<?= esc($resUnitId) ?>">
                            <span class="db-pill-icons">
                                <span class="material-symbols-outlined">drag_indicator</span>
                                <span class="material-symbols-outlined"><?= $grp['icon'] ?></span>
                            </span>
                            <span class="db-pill-name"><?= esc($resName) ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($grp['items'])): ?>
                        <p class="db-empty" style="font-size:0.8rem; padding:0.25rem 0; margin:0;">None assigned</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>

        <!-- Footer actions -->
        <div class="db-card-footer">
            <span class="material-symbols-outlined db-icon-chat" title="Messages">chat_bubble</span>
            <span class="material-symbols-outlined db-icon-warn" title="Warnings">warning</span>
            <?php if ($context === 'monitor'): ?>
            <button class="db-btn-locate"
                    data-lat="<?= $lat !== null ? (float)$lat : '' ?>"
                    data-lng="<?= $lng !== null ? (float)$lng : '' ?>"
                    data-name="<?= esc(strtoupper($p['project_name'] ?? '')) ?>"
                    title="Focus on map">Locate Site</button>
            <?php else: ?>
            <a href="<?= $mapsUrl ?>" class="db-btn-locate" target="_blank" rel="noopener">Locate Site</a>
            <?php endif; ?>
        </div>

    </div><!-- .db-project-card -->
    <?php
    return ob_get_clean();
}
?>

<script>
function dbCardToggle(cardId, btn) {
    const card = document.getElementById(cardId);
    if (card) card.classList.toggle('db-card-expanded');
}

function dbToggleGroup(groupEl) {
    groupEl.classList.toggle('open');
}

// -- Filter dropdown toggle ---------------------------------
document.addEventListener('DOMContentLoaded', function () {
    var toggleBtn  = document.getElementById('dbFilterToggle');
    var dropdown   = document.getElementById('dbFilterDropdown');
    if (!toggleBtn || !dropdown) return;

    toggleBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        var isOpen = dropdown.classList.toggle('open');
        toggleBtn.classList.toggle('open', isOpen);
    });

    // Close when clicking outside
    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target) && e.target !== toggleBtn) {
            dropdown.classList.remove('open');
            toggleBtn.classList.remove('open');
        }
    });
});

// -- Monitor filters ----------------------------------------
(function () {
    var activeRegion = 'ALL';
    var activeType   = 'ALL';
    var activeStatus = 'ALL';
    var activeClient = 'ALL';

    function closeDropdown() {
        var dd  = document.getElementById('dbFilterDropdown');
        var btn = document.getElementById('dbFilterToggle');
        if (dd)  dd.classList.remove('open');
        if (btn) btn.classList.remove('open');
    }

    function applyFilters() {
        var anyVisible = false;
        document.querySelectorAll('.db-monitor-region').forEach(function (group) {
            var visibleCols = [];
            group.querySelectorAll('.db-monitor-col').forEach(function (col) {
                var show = (activeRegion === 'ALL' || col.dataset.region === activeRegion) &&
                           (activeType   === 'ALL' || col.dataset.type   === activeType)   &&
                           (activeStatus === 'ALL' || col.dataset.status === activeStatus) &&
                           (activeClient === 'ALL' || col.dataset.client === activeClient);
                col.style.display = show ? '' : 'none';
                if (show) visibleCols.push(col);
            });

            group.style.display = visibleCols.length > 0 ? '' : 'none';
            if (visibleCols.length > 0) anyVisible = true;
        });
        var noResults = document.getElementById('dbNoResults');
        if (noResults) noResults.style.display = anyVisible ? 'none' : 'block';
    }

    function bindPills(containerId, getValue, setter) {
        var container = document.getElementById(containerId);
        if (!container) return;
        container.addEventListener('click', function (e) {
            var pill = e.target.closest('.db-filter-pill');
            if (!pill) return;
            container.querySelectorAll('.db-filter-pill').forEach(function (p) { p.classList.remove('active'); });
            pill.classList.add('active');
            setter(getValue(pill));
            applyFilters();
            closeDropdown();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindPills('dbRegionPills', function (p) { return p.dataset.region; }, function (v) { activeRegion = v; });
        bindPills('dbTypePills',   function (p) { return p.dataset.type;   }, function (v) { activeType   = v; });
        bindPills('dbStatusPills', function (p) { return p.dataset.status; }, function (v) { activeStatus = v; });

        var clientSel = document.getElementById('dbClientFilter');
        if (clientSel) {
            clientSel.addEventListener('change', function () {
                activeClient = this.value;
                applyFilters();
                closeDropdown();
            });
        }
    });
}());

// Smooth-scroll to #summary / #monitor from sidebar links
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('a[href*="#summary"], a[href*="#monitor"]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            const hash = this.getAttribute('href').split('#')[1];
            const target = document.getElementById(hash);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                history.replaceState(null, '', '#' + hash);
            }
        });
    });

    // Auto-scroll to hash on page load
    if (window.location.hash) {
        const el = document.querySelector(window.location.hash);
        if (el) setTimeout(() => el.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
    }
});
</script>

<?php
// Build JS-safe project locations for the monitor map
$jsLocations = json_encode($projectLocations ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
$mapsKey     = esc($googleMapsBrowserKey ?? '');
?>
<?php if ($mapsKey): ?>
<script>
(function () {
    var PROJECT_LOCATIONS = <?= $jsLocations ?>;
    var monitorMap = null;
    var openInfo   = null;

    window.initMonitorMap = function () {
        var mapEl = document.getElementById('monitorMap');
        if (!mapEl) return;

        monitorMap = new google.maps.Map(mapEl, {
            center: { lat: 12.8797, lng: 121.7740 },   // PH centre
            zoom: 6,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
        });
        window.pzMonitorMap = monitorMap;

        var bounds = new google.maps.LatLngBounds();
        var hasMarkers = false;

        function pinIcon(color, stroke) {
            var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="36" viewBox="0 0 28 36">' +
                '<path d="M14 0C6.268 0 0 6.268 0 14c0 9.941 14 22 14 22S28 23.941 28 14C28 6.268 21.732 0 14 0z" ' +
                'fill="' + color + '" stroke="' + (stroke || '#fff') + '" stroke-width="1.5"/>' +
                '<circle cx="14" cy="13" r="5" fill="rgba(255,255,255,0.85)"/>' +
                '</svg>';
            return {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
                scaledSize: new google.maps.Size(28, 36),
                anchor: new google.maps.Point(14, 36),
            };
        }

        function typeColor(type) {
            var t = (type || '').toUpperCase();
            if (t.indexOf('NEW')   !== -1 || t.indexOf('BUILD')  !== -1) return '#E53935'; // red
            if (t.indexOf('RETRO') !== -1)                                return '#1E88E5'; // blue
            if (t.indexOf('COLO')  !== -1)                                return '#FDD835'; // yellow
            if (t.indexOf('CONV')  !== -1)                                return '#7B1FA2'; // violet
            return '#E53935'; // default red
        }

        PROJECT_LOCATIONS.forEach(function (proj) {
            if (proj.lat == null || proj.lng == null) return;

            var pos = { lat: parseFloat(proj.lat), lng: parseFloat(proj.lng) };
            var marker = new google.maps.Marker({
                position: pos,
                map: monitorMap,
                title: proj.name,
                animation: google.maps.Animation.DROP,
                icon: pinIcon(typeColor(proj.type)),
            });

            var info = new google.maps.InfoWindow({
                content: '<div style="max-width:220px;font-family:inherit;padding:2px 0">' +
                         '<div style="font-weight:700;font-size:0.85rem;color:#5a3e00;letter-spacing:0.5px;margin-bottom:4px">' + proj.name + '</div>' +
                         (proj.addr ? '<div style="font-size:0.78rem;color:#666;line-height:1.4">' + proj.addr + '</div>' : '') +
                         '</div>',
            });

            marker.addListener('click', function () {
                if (openInfo) openInfo.close();
                info.open(monitorMap, marker);
                openInfo = info;
            });

            // Store marker reference on the project for fly-to
            proj._marker = marker;
            proj._info   = info;

            bounds.extend(pos);
            hasMarkers = true;
        });

        if (hasMarkers) monitorMap.fitBounds(bounds);
    };

    // Delegated listener — avoids inline-onclick quote-escaping issues
    document.addEventListener('click', function (e) {
        var btn = e.target.closest && e.target.closest('.db-btn-locate[data-name]');
        if (!btn) return;
        var rawLat = btn.dataset.lat;
        var rawLng = btn.dataset.lng;
        var lat  = rawLat !== '' ? parseFloat(rawLat) : null;
        var lng  = rawLng !== '' ? parseFloat(rawLng) : null;
        monitorFlyTo(lat, lng, btn.dataset.name || '');
    });

    // Called by the delegated listener above
    function monitorFlyTo(lat, lng, name) {
        var sidePanel    = document.getElementById('mapSidePanel');
        var wasCollapsed = sidePanel && sidePanel.classList.contains('map-panel-collapsed');

        // Open the panel if it was collapsed
        if (wasCollapsed) toggleMapPanel();

        // Flash the panel header to draw the user's eye toward the map
        var hdr = document.querySelector('#mapSidePanel .map-panel-hdr');
        if (hdr) {
            hdr.classList.add('map-panel-flash');
            setTimeout(function () { hdr.classList.remove('map-panel-flash'); }, 900);
        }

        // No coordinates saved for this project — just open the panel
        if (lat === null || lng === null || !monitorMap) return;

        // Wait for panel open + map resize before panning
        setTimeout(function () {
            monitorMap.panTo({ lat: lat, lng: lng });
            monitorMap.setZoom(15);

            PROJECT_LOCATIONS.forEach(function (proj) {
                if (proj._marker && Math.abs(proj.lat - lat) < 0.0001 && Math.abs(proj.lng - lng) < 0.0001) {
                    if (openInfo) openInfo.close();

                    proj._marker.setAnimation(google.maps.Animation.BOUNCE);
                    setTimeout(function () { proj._marker.setAnimation(null); }, 1500);

                    setTimeout(function () {
                        proj._info.open(monitorMap, proj._marker);
                        openInfo = proj._info;
                    }, 400);
                }
            });
        }, wasCollapsed ? 320 : 0);
    }
}());
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= $mapsKey ?>&callback=initMonitorMap" async defer></script>
<?php endif; ?>

<!-- -- SortableJS â€” drag-to-reorder/reassign resource pills -- -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var dragging       = false;
    var dragY          = 0;
    var rAfId          = null;
    var scrollEl       = document.scrollingElement || document.documentElement;
    var pendingTransfer = null;

    // ── Transfer confirm modal handlers ──────────────────────────────
    var overlay     = document.getElementById('dndConfirmOverlay');
    var cancelBtn   = document.getElementById('dndCancelBtn');
    var confirmBtn  = document.getElementById('dndConfirmBtn');

    function closeModal() {
        overlay.classList.remove('open');
    }

    cancelBtn && cancelBtn.addEventListener('click', function () {
        // Revert pill to its original zone
        if (pendingTransfer) {
            var t = pendingTransfer;
            var ref = t.fromZone.children[t.oldIndex] || null;
            t.fromZone.insertBefore(t.pill, ref);
        }
        pendingTransfer = null;
        closeModal();
    });

    // Close on backdrop click
    overlay && overlay.addEventListener('click', function (e) {
        if (e.target === overlay) cancelBtn.click();
    });

    confirmBtn && confirmBtn.addEventListener('click', function () {
        if (!pendingTransfer) return;
        var t = pendingTransfer;
        confirmBtn.disabled = true;

        fetch('<?= base_url('requests/store') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>',
            },
            body: JSON.stringify({
                type:                     'drag_drop',
                category:                 t.cat,
                resource_name:            t.resName,
                resource_id:              t.resId,
                unit_id:                  t.unitId,
                source_project_id:        t.srcPid,
                source_project_name:      t.srcPname,
                destination_project_id:   t.destPid,
                destination_project_name: t.destPname,
                quantity:                 1,
            }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                var toast = document.getElementById('dndToast');
                if (toast) {
                    toast.textContent = 'Transfer request created.';
                    toast.classList.add('show');
                    setTimeout(function () { toast.classList.remove('show'); }, 3000);
                }
            }
        })
        .catch(function (err) { console.error('DnD request failed', err); })
        .finally(function () {
            confirmBtn.disabled = false;
            pendingTransfer = null;
            closeModal();
        });
    });

    function updateY(e) { if (dragging) dragY = e.clientY; }
    document.addEventListener('mousemove',   updateY, true);
    document.addEventListener('pointermove', updateY, true);

    function edgeScrollLoop() {
        if (!dragging) return;
        var ZONE = 80, MAX = 20;
        var dTop = dragY;
        var dBot = window.innerHeight - dragY;
        if (dTop < ZONE)
            scrollEl.scrollTop += dTop > 0 ? -Math.round((1 - dTop / ZONE) * MAX) : -MAX;
        else if (dBot < ZONE)
            scrollEl.scrollTop += dBot > 0 ?  Math.round((1 - dBot / ZONE) * MAX) :  MAX;
        rAfId = requestAnimationFrame(edgeScrollLoop);
    }

    ['equipment', 'tools', 'manpower'].forEach(function (cat) {
        var zones = document.querySelectorAll('.db-res-drop-zone[data-cat="' + cat + '"]');

        zones.forEach(function (zone) {
            new Sortable(zone, {
                group:           { name: 'res-' + cat, pull: true, put: ['res-' + cat] },
                handle:          '.db-pill-icons',
                draggable:       '.db-pill',
                filter:          '.db-empty',
                animation:       150,
                ghostClass:      'db-pill-ghost',
                dragClass:       'db-pill-drag',
                scroll:          false,
                // forceFallback disables HTML5 native DnD and uses pointer/mouse events
                // instead â€” this is required for mousemove/pointermove to fire during drag,
                // which is what our edge-scroll and wheel-scroll tracking depends on.
                forceFallback:   true,
                fallbackTolerance: 3,

                onStart: function (evt) {
                    dragging = true;
                    dragY    = evt.originalEvent ? evt.originalEvent.clientY : window.innerHeight / 2;
                    document.documentElement.style.scrollBehavior = 'auto';
                    document.querySelectorAll('.db-res-group[data-cat="' + cat + '"]').forEach(function (g) {
                        g.classList.add('open');
                    });
                    zones.forEach(function (z) {
                        z.classList.add(z === zone ? 'dnd-active' : 'dnd-drop-zone');
                    });
                    rAfId = requestAnimationFrame(edgeScrollLoop);
                },

                onEnd: function () {
                    dragging = false;
                    if (rAfId) { cancelAnimationFrame(rAfId); rAfId = null; }
                    document.documentElement.style.scrollBehavior = '';
                    zones.forEach(function (z) {
                        z.classList.remove('dnd-active', 'dnd-drop-zone');
                    });
                },

                onAdd: function (evt) {
                    var destCard = evt.to.closest('.db-project-card');
                    var srcCard  = evt.from.closest('.db-project-card');

                    var pill      = evt.item;
                    var resName   = pill.dataset.resName  || (pill.querySelector('.db-pill-name') || {}).textContent || '';
                    var resId     = pill.dataset.resId    || '';
                    var unitId    = pill.dataset.unitId   || '';
                    var destPid   = (evt.to.dataset.pid)  || '';
                    var destPname = destCard ? (destCard.dataset.pname || '') : '';
                    var srcPid    = srcCard  ? (srcCard.dataset.pid   || '') : '';
                    var srcPname  = srcCard  ? (srcCard.dataset.pname || '') : '';

                    if (!destPid || destPid === srcPid) return;

                    // Store context for the confirm/cancel handlers
                    pendingTransfer = {
                        cat: cat, pill: pill,
                        resName: resName.trim(), resId: resId, unitId: unitId,
                        srcPid: srcPid, srcPname: srcPname,
                        destPid: destPid, destPname: destPname,
                        fromZone: evt.from, oldIndex: evt.oldIndex,
                    };

                    // Populate and show the modal
                    document.getElementById('dndResLabel').textContent  = resName.trim();
                    document.getElementById('dndSrcLabel').textContent  = srcPname  || '—';
                    document.getElementById('dndDestLabel').textContent = destPname || '—';
                    overlay.classList.add('open');
                },
            });
        });
    });
});

// Restore right map panel state (like sidebar)
(function () {
    var panel = document.getElementById('mapSidePanel');
    if (!panel) return;
    var collapsed = localStorage.getItem('pz_map_collapsed') === 'true';
    if (collapsed) {
        panel.classList.add('map-panel-collapsed');
        document.body.classList.add('map-panel-collapsed');
    } else {
        document.body.classList.add('map-panel-open');
    }
}());

function toggleMapPanel() {
    var panel = document.getElementById('mapSidePanel');
    if (!panel) return;
    var isNowCollapsed = panel.classList.toggle('map-panel-collapsed');
    document.body.classList.toggle('map-panel-open',     !isNowCollapsed);
    document.body.classList.toggle('map-panel-collapsed',  isNowCollapsed);
    localStorage.setItem('pz_map_collapsed', isNowCollapsed);
    // Trigger map resize after the slide animation
    if (!isNowCollapsed) {
        setTimeout(function () {
            if (window.pzMonitorMap) google.maps.event.trigger(window.pzMonitorMap, 'resize');
        }, 280);
    }
}
</script>

<?= $this->endSection() ?>
