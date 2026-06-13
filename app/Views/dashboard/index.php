<?= $this->extend('layouts/main') ?>

<?= $this->section('head') ?>
<?php if (!empty($googleMapsBrowserKey)): ?>
<script>
window.__monitorMapKey = '<?= esc($googleMapsBrowserKey ?? '') ?>';
</script>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

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

    <!-- -- Filter bar -- -->
    <div class="db-filter-bar">
        <span class="db-filter-label">Region</span>
        <div class="db-filter-pills" id="dbRegionPills">
            <button type="button" class="db-filter-pill active" data-region="ALL">All</button>
            <button type="button" class="db-filter-pill" data-region="LUZON">Luzon</button>
            <button type="button" class="db-filter-pill" data-region="VISAYAS">Visayas</button>
            <button type="button" class="db-filter-pill" data-region="MINDANAO">Mindanao</button>
        </div>

        <?php if (!empty($monitorClients)): ?>
        <div class="db-filter-divider"></div>
        <span class="db-filter-label">Client</span>
        <select class="db-filter-client-sel" id="dbClientFilter">
            <option value="ALL">All Clients</option>
            <?php foreach ($monitorClients as $cl): ?>
            <option value="<?= esc($cl) ?>"><?= esc($cl) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
    </div>

    <!-- -- Project card groups -- -->
    <?php foreach ($projectsByRegion as $region => $regionProjects): ?>
    <div class="db-monitor-region" data-region-group="<?= esc($region) ?>">
        <h3 class="db-region-title"><?= esc($region) ?></h3>
        <div class="row g-3 mb-2">
            <?php foreach ($regionProjects as $proj): ?>
            <div class="col-xl-6 col-12 db-monitor-col"
                 data-region="<?= esc($proj['_region']) ?>"
                 data-client="<?= esc($proj['_client'] ?? '') ?>">
                <?php echo db_render_card($proj, 'monitor'); ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <p class="db-monitor-no-results" id="dbNoResults">No projects match the selected filters.</p>

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
    <div class="db-project-card" id="<?= $cardId ?>">

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
                    <div class="db-res-drop-zone" data-cat="<?= $cat ?>" data-proj="<?= $cardId ?>">
                        <?php foreach ($grp['items'] as $resName): ?>
                        <div class="db-pill">
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
            <?php if ($context === 'monitor' && $lat !== null && $lng !== null): ?>
            <button class="db-btn-locate"
                    onclick="monitorFlyTo(<?= (float)$lat ?>, <?= (float)$lng ?>, <?= json_encode($name) ?>)"
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

// -- Monitor filters ----------------------------------------
(function () {
    var activeRegion = 'ALL';
    var activeClient = 'ALL';

    function applyFilters() {
        var anyVisible = false;
        document.querySelectorAll('.db-monitor-region').forEach(function (group) {
            var groupRegion = group.dataset.regionGroup;
            var groupVisible = false;
            group.querySelectorAll('.db-monitor-col').forEach(function (col) {
                var show = (activeRegion === 'ALL' || col.dataset.region === activeRegion) &&
                           (activeClient === 'ALL' || col.dataset.client === activeClient);
                col.style.display = show ? '' : 'none';
                if (show) groupVisible = true;
            });
            group.style.display = groupVisible ? '' : 'none';
            if (groupVisible) anyVisible = true;
        });
        var noResults = document.getElementById('dbNoResults');
        if (noResults) noResults.style.display = anyVisible ? 'none' : 'block';
    }

    document.addEventListener('DOMContentLoaded', function () {
        var regionPills = document.getElementById('dbRegionPills');
        if (regionPills) {
            regionPills.addEventListener('click', function (e) {
                var pill = e.target.closest('.db-filter-pill');
                if (!pill) return;
                regionPills.querySelectorAll('.db-filter-pill').forEach(function (p) { p.classList.remove('active'); });
                pill.classList.add('active');
                activeRegion = pill.dataset.region;
                applyFilters();
            });
        }
        var clientSel = document.getElementById('dbClientFilter');
        if (clientSel) {
            clientSel.addEventListener('change', function () {
                activeClient = this.value;
                applyFilters();
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

        PROJECT_LOCATIONS.forEach(function (proj) {
            if (proj.lat == null || proj.lng == null) return;

            var pos = { lat: parseFloat(proj.lat), lng: parseFloat(proj.lng) };
            var marker = new google.maps.Marker({
                position: pos,
                map: monitorMap,
                title: proj.name,
                animation: google.maps.Animation.DROP,
            });

            var info = new google.maps.InfoWindow({
                content: '<div style="max-width:200px"><strong>' + proj.name + '</strong>' +
                         (proj.addr ? '<br><small>' + proj.addr + '</small>' : '') + '</div>',
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

    // Called by monitor "Locate Site" buttons
    window.monitorFlyTo = function (lat, lng, name) {
        if (!monitorMap) return;
        // Auto-expand the right panel if collapsed
        var sidePanel = document.getElementById('mapSidePanel');
        if (sidePanel && sidePanel.classList.contains('map-panel-collapsed')) {
            toggleMapPanel();
        }

        monitorMap.panTo({ lat: lat, lng: lng });
        monitorMap.setZoom(15);

        // Open matching info window
        PROJECT_LOCATIONS.forEach(function (proj) {
            if (proj._marker && Math.abs(proj.lat - lat) < 0.0001 && Math.abs(proj.lng - lng) < 0.0001) {
                if (openInfo) openInfo.close();
                proj._info.open(monitorMap, proj._marker);
                openInfo = proj._info;
            }
        });
    };
}());
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= $mapsKey ?>&callback=initMonitorMap" async defer></script>
<?php endif; ?>

<!-- -- SortableJS â€” drag-to-reorder/reassign resource pills -- -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var dragging = false;
    var dragY    = 0;
    var rAfId    = null;
    var scrollEl = document.scrollingElement || document.documentElement;

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
                    var card = evt.to.closest('.db-project-card');
                    if (card) {
                        card.classList.add('dnd-received');
                        setTimeout(function () { card.classList.remove('dnd-received'); }, 600);
                    }
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
