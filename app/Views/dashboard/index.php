<?= $this->extend('layouts/main') ?>

<?= $this->section('head') ?>
<style>
/* ── Dashboard theme variables ────────────────────────── */
:root {
    --db-gold:      #C8A951;
    --db-gold-dark: #8C701C;
    --db-orange:    #C47A0F;
    --db-cream:     #FBF6E8;
    --db-border:    #E8D9A8;
    --db-text:      #1a1a1a;
}
html { scroll-behavior: smooth; }

/* ── Page-level wrapper ───────────────────────────────── */
.db-root { display: flex; flex-direction: column; gap: 0; width: 100%; }

/* ── Section ──────────────────────────────────────────── */
.db-section { padding-bottom: 2rem; }
.db-section + .db-section {
    border-top: 3px solid var(--db-gold);
    padding-top: 2rem;
}

/* ── Region header ────────────────────────────────────── */
.db-region-title {
    font-weight: 800;
    font-size: 1.25rem;
    color: var(--db-text);
    letter-spacing: 1px;
    text-transform: uppercase;
    margin: 1.5rem 0 0.75rem;
    padding-bottom: 0.35rem;
    border-bottom: 2px solid var(--db-gold);
}

/* ── Summary date header ──────────────────────────────── */
.db-today {
    font-size: 0.85rem;
    font-weight: 500;
    color: #555;
    margin-bottom: 0;
}
.db-today-date {
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--db-text);
    margin: 0 0 0.75rem;
    line-height: 1.2;
}

/* ── Info cards row — full-width flex ─────────────────── */
.db-info-row {
    display: flex;
    gap: 0.5rem;
    width: 100%;
    margin-bottom: 0.5rem;
    align-items: flex-start;
}
.db-info-col {
    flex: 1 1 0;
    min-width: 0;
}

/* ── Info cards (Blockers / Tools / Deploy) ───────────── */
.db-info-card {
    background: var(--db-cream);
    border: 2px solid var(--db-gold);
    border-radius: 10px;
    padding: 0.5rem 0.7rem;
}
.db-info-title {
    text-align: center;
    font-weight: 800;
    font-size: 0.75rem;
    color: var(--db-gold-dark);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.45rem;
    padding-bottom: 0.3rem;
    border-bottom: 1px solid var(--db-border);
}

/* Blocker items — horizontal wrapping chips */
.db-blockers-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
    align-content: flex-start;
}
.db-blocker-box {
    border: 1.5px solid var(--db-text);
    border-radius: 999px;
    padding: 0.15rem 0.55rem;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    white-space: nowrap;
}
.db-blocker-name {
    font-weight: 800;
    font-size: 0.65rem;
    color: var(--db-gold-dark);
    text-transform: uppercase;
}
.db-blocker-line {
    font-size: 0.62rem;
    font-weight: 600;
    color: var(--db-gold-dark);
}
.db-blocker-sep { color: var(--db-border); font-size: 0.6rem; }

/* Info table (tools / manpower) */
.db-info-tbl {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.72rem;
}
.db-info-tbl thead th {
    background: var(--db-gold);
    color: var(--db-text);
    font-weight: 700;
    text-transform: uppercase;
    padding: 0.18rem 0.4rem;
    text-align: center;
}
.db-info-tbl tbody td {
    padding: 0.18rem 0.4rem;
    text-align: center;
    font-weight: 600;
    color: var(--db-text);
    border-bottom: 1px solid var(--db-border);
}
.db-info-tbl tbody tr:nth-child(odd)  td { background: #fff; }
.db-info-tbl tbody tr:nth-child(even) td { background: var(--db-cream); }

/* ── Project Card ─────────────────────────────────────── */
.db-project-card {
    background: #fff;
    border: 1.5px solid #E8E0D0;
    border-radius: 16px;
    padding: 1rem 0.9rem;
    display: flex;
    flex-direction: column;
    height: 100%;
    box-shadow: 0 2px 10px rgba(0,0,0,0.07);
    transition: box-shadow .2s;
}
.db-project-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.12); }

.db-project-name-box {
    border: 2px solid var(--db-text);
    border-radius: 8px;
    padding: 0.45rem 0.6rem;
    text-align: center;
    font-weight: 800;
    font-size: 0.75rem;
    color: var(--db-text);
    text-transform: uppercase;
    letter-spacing: 0.3px;
    line-height: 1.3;
    min-height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.7rem;
}

/* Progress bar */
.db-progress {
    position: relative;
    height: 26px;
    border-radius: 999px;
    background: var(--db-text);
    overflow: hidden;
    margin-bottom: 0.7rem;
}
.db-progress-fill {
    position: absolute;
    inset: 0 auto 0 0;
    background: #F2C94C;
    border-radius: 999px;
    min-width: 28px;
    transition: width .6s ease;
}
.db-progress-pct {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #fff;
    font-weight: 700;
    font-size: 0.82rem;
    z-index: 1;
    white-space: nowrap;
}

/* Dates */
.db-dates { margin-bottom: 0.65rem; }
.db-date-row {
    display: flex;
    gap: 6px;
    align-items: baseline;
    flex-wrap: wrap;
    margin-bottom: 2px;
}
.db-date-label {
    font-weight: 800;
    font-size: 0.7rem;
    color: var(--db-text);
    text-transform: uppercase;
    white-space: nowrap;
}
.db-date-val {
    font-weight: 700;
    font-size: 0.7rem;
    color: var(--db-orange);
    text-transform: uppercase;
}

/* Resource pills */
.db-resources { flex: 1; margin-bottom: 0.65rem; overflow: hidden; }
.db-pill {
    display: flex;
    align-items: center;
    border: 1.5px solid var(--db-text);
    border-radius: 999px;
    padding: 3px 10px 3px 6px;
    margin-bottom: 5px;
    gap: 5px;
    font-weight: 700;
    font-size: 0.73rem;
    text-transform: uppercase;
    color: var(--db-text);
    background: #fff;
    line-height: 1;
}
.db-pill-icons { display: flex; gap: 1px; align-items: center; flex-shrink: 0; }
.db-pill-icons .material-symbols-outlined { font-size: 13px !important; line-height: 1; }
.db-pill-name { flex: 1; line-height: 1; }

/* Hidden extra resources (expand) */
.db-more { display: none; }
.db-card-expanded .db-more { display: block; }
.db-btn-expand .material-symbols-outlined { transition: transform .2s ease; }
.db-card-expanded .db-btn-expand .material-symbols-outlined { transform: rotate(180deg); }

/* Card footer */
.db-card-footer {
    display: flex;
    align-items: center;
    gap: 7px;
    padding-top: 0.55rem;
    border-top: 1px solid #EDE6D8;
    margin-top: auto;
    flex-wrap: nowrap;
}
.db-icon-chat { color: var(--db-orange); font-size: 20px !important; cursor: pointer; }
.db-icon-warn { color: #E53935;          font-size: 20px !important; cursor: pointer; }
.db-btn-locate {
    margin-left: auto;
    background: var(--db-orange);
    color: #fff;
    border: none;
    border-radius: 999px;
    padding: 5px 13px;
    font-weight: 700;
    font-size: 0.7rem;
    text-transform: uppercase;
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    letter-spacing: 0.4px;
    transition: background .15s;
}
.db-btn-locate:hover { background: #A8630A; color: #fff; text-decoration: none; }
.db-btn-expand {
    background: transparent;
    border: none;
    padding: 0 2px;
    cursor: pointer;
    color: var(--db-text);
    display: flex;
    align-items: center;
    flex-shrink: 0;
}

/* Empty state */
.db-empty {
    color: #aaa;
    font-size: 0.82rem;
    text-align: center;
    padding: 0.75rem 0;
    font-style: italic;
    margin: 0;
}

/* Bootstrap conflict patches */
.db-info-card h1,.db-info-card h2,.db-info-card h3,
.db-info-card h4,.db-info-card h5 { margin: 0; }
.db-project-card h1,.db-project-card h2,
.db-project-card h3,.db-project-card h4 { margin: 0; }

/* ── Drag-and-drop resource pills ──────────────────────── */
.db-pill { cursor: default; user-select: none; }
.db-pill-icons { cursor: grab; }
.db-pill-icons:active { cursor: grabbing; }
/* ghost: where the pill was */
.db-pill-ghost {
    opacity: 0.35;
    border-style: dashed !important;
    background: #FBF6E8 !important;
}
/* dragging: the floating clone */
.db-pill-drag {
    box-shadow: 0 6px 18px rgba(0,0,0,0.18) !important;
    transform: scale(1.03);
    background: #fff !important;
    border-color: var(--db-gold) !important;
    z-index: 9999;
}
/* subtle highlight on the SOURCE container while dragging */
.db-resources.dnd-active {
    background: rgba(200,169,81,0.06);
    border-radius: 8px;
    outline: 2px dashed rgba(200,169,81,0.4);
    outline-offset: 3px;
}
/* highlight on OTHER containers as potential drop targets */
.db-resources.dnd-drop-zone {
    background: rgba(200,169,81,0.04);
    border-radius: 8px;
    outline: 2px dashed rgba(200,169,81,0.22);
    outline-offset: 3px;
}
/* flash the card that just received a drop */
.db-project-card.dnd-received {
    box-shadow: 0 0 0 3px var(--db-gold) !important;
    transition: box-shadow .05s;
}

/* ── Right map sidebar panel (mirrors left sidebar) ───── */
.main-content {
    transition: margin-left 0.25s ease, margin-right 0.25s ease;
}
body.map-panel-open     .main-content { margin-right: 380px; }
body.map-panel-collapsed .main-content { margin-right: 52px; }

#mapSidePanel {
    position: fixed;
    right: 0;
    top: 0;
    height: 100vh;
    width: 380px;
    background: var(--db-cream);
    border-left: 2px solid var(--db-gold);
    z-index: 1035;
    display: flex;
    flex-direction: column;
    transition: width 0.25s ease;
    overflow: hidden;
    box-shadow: -4px 0 20px rgba(0,0,0,0.10);
}
#mapSidePanel.map-panel-collapsed { width: 52px; }

.map-panel-hdr {
    background: var(--db-gold-dark);
    color: #fff;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 11px 10px 11px 14px;
    cursor: pointer;
    user-select: none;
    white-space: nowrap;
    overflow: hidden;
    flex-shrink: 0;
    min-height: 44px;
}
.map-panel-hdr .material-symbols-outlined { font-size: 18px !important; flex-shrink: 0; }
.map-panel-hdr-label {
    flex: 1;
    font-weight: 700;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.map-panel-toggle {
    background: transparent;
    border: none;
    color: #fff;
    cursor: pointer;
    padding: 0;
    display: flex;
    align-items: center;
    flex-shrink: 0;
}
.map-panel-toggle .material-symbols-outlined {
    font-size: 20px !important;
    transition: transform 0.25s ease;
}
#mapSidePanel.map-panel-collapsed .map-panel-hdr-label { display: none; }
#mapSidePanel.map-panel-collapsed .map-panel-toggle .material-symbols-outlined {
    transform: rotate(180deg);
}
#mapSidePanel.map-panel-collapsed #monitorMap { display: none; }

#monitorMap { flex: 1; width: 100%; min-height: 0; }

/* Responsive: overlay on small screens, no content push */
@media (max-width: 991px) {
    body.map-panel-open .main-content,
    body.map-panel-collapsed .main-content { margin-right: 0 !important; }
    #mapSidePanel { z-index: 1045; box-shadow: -8px 0 30px rgba(0,0,0,0.2); }
    #mapSidePanel:not(.map-panel-collapsed) { width: min(380px, 85vw); }
}
</style>
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

<!-- ═══════════════════════════════════════════════════════════
     SUMMARY SECTION
     ═══════════════════════════════════════════════════════════ -->
<section id="summary" class="db-section">

    <p class="db-today">Today,</p>
    <p class="db-today-date"><?= date('F j, Y') ?></p>

    <div class="db-info-row">

        <!-- BLOCKERS ─────────────────────────────────────── -->
        <div class="db-info-col">
            <div class="db-info-card">
                <div class="db-info-title">Blockers</div>
                <?php if (empty($blockers)): ?>
                    <p class="db-empty">No blockers — all projects on track.</p>
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
                        <span class="db-blocker-sep">·</span>
                        <span class="db-blocker-line"><?= $delayDays ?>d delay</span>
                        <?php endif; ?>
                        <span class="db-blocker-sep">·</span>
                        <span class="db-blocker-line"><?= esc(ucwords((string)$reason)) ?></span>
                    </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- AVAILABLE TOOLS ──────────────────────────────── -->
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
                                <td><?= esc((string)($t['asset_details']['available_quantity'] ?? $t['available_units_display'] ?? '—')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- AVAILABLE TO DEPLOY ──────────────────────────── -->
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


<!-- ═══════════════════════════════════════════════════════════
     MONITOR SECTION  —  cards (left) + sticky map (right)
     ═══════════════════════════════════════════════════════════ -->
<section id="monitor" class="db-section">

    <?php if (empty($projectsByRegion)): ?>
        <p class="db-empty">No active projects to monitor.</p>
    <?php else: ?>
        <?php foreach ($projectsByRegion as $region => $regionProjects): ?>
        <h3 class="db-region-title"><?= esc($region) ?></h3>
        <div class="row g-3 mb-2">
            <?php foreach ($regionProjects as $proj): ?>
            <div class="col-xl-6 col-12">
                <?php echo db_render_card($proj, 'monitor'); ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</section><!-- #monitor -->

</div><!-- .db-root -->

<!-- ── Right map sidebar panel ───────────────────────────── -->
<div id="mapSidePanel">
    <div class="map-panel-hdr" onclick="toggleMapPanel()">
        <span class="material-symbols-outlined">map</span>
        <span class="map-panel-hdr-label">Project Sites</span>
        <button class="map-panel-toggle" onclick="event.stopPropagation(); toggleMapPanel()" title="Toggle map panel">
            <span class="material-symbols-outlined">chevron_right</span>
        </button>
    </div>
    <div id="monitorMap"></div>
</div>

<?php
/**
 * Render a single project card (shared by Summary and Monitor sections).
 */
function db_render_card(array $p, string $context = 'summary'): string
{
    $name     = esc(strtoupper($p['project_name'] ?? 'Project'));
    $progress = min(100, max(0, (float)$p['_progress']));
    $startFmt = esc($p['_start_fmt']);
    $endFmt   = esc($p['_end_fmt']);
    $tools    = (array)($p['_tools']    ?? []);
    $manpower = (array)($p['_manpower'] ?? []);
    $mapsUrl  = esc($p['_maps_url']);
    $cardId   = 'card-' . preg_replace('/[^a-zA-Z0-9]/', '', (string)($p['id'] ?? uniqid()));
    $lat      = $p['_lat'] ?? null;
    $lng      = $p['_lng'] ?? null;

    $toolSet  = array_flip($tools);   // for O(1) lookup
    $allRes   = array_merge($tools, $manpower);
    $visible  = array_slice($allRes, 0, 3);
    $extra    = array_slice($allRes, 3);
    $hasExtra = count($extra) > 0;

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

        <!-- Resource pills -->
        <div class="db-resources">
            <?php foreach ($visible as $resName): ?>
            <div class="db-pill">
                <span class="db-pill-icons">
                    <span class="material-symbols-outlined">drag_indicator</span>
                    <span class="material-symbols-outlined"><?= isset($toolSet[$resName]) ? 'construction' : 'engineering' ?></span>
                </span>
                <span class="db-pill-name"><?= esc($resName) ?></span>
            </div>
            <?php endforeach; ?>

            <?php if ($hasExtra): ?>
            <div class="db-more">
                <?php foreach ($extra as $resName): ?>
                <div class="db-pill">
                    <span class="db-pill-icons">
                        <span class="material-symbols-outlined">drag_indicator</span>
                        <span class="material-symbols-outlined"><?= isset($toolSet[$resName]) ? 'construction' : 'engineering' ?></span>
                    </span>
                    <span class="db-pill-name"><?= esc($resName) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (empty($allRes)): ?>
            <p class="db-empty">No resources assigned</p>
            <?php endif; ?>
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
            <?php if ($hasExtra): ?>
            <button class="db-btn-expand"
                    onclick="dbCardToggle('<?= $cardId ?>', this)"
                    title="Show more / less">
                <span class="material-symbols-outlined">keyboard_double_arrow_down</span>
            </button>
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

<!-- ── SortableJS — drag-to-reorder/reassign resource pills ── -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var allContainers = document.querySelectorAll('.db-resources');

    allContainers.forEach(function (container) {
        new Sortable(container, {
            group:       { name: 'project-resources', pull: true, put: true },
            handle:      '.db-pill-icons',
            draggable:   '.db-pill',
            filter:      '.db-empty',
            animation:   150,
            ghostClass:  'db-pill-ghost',
            dragClass:   'db-pill-drag',

            onStart: function () {
                container.classList.add('dnd-active');
                allContainers.forEach(function (c) {
                    if (c !== container) c.classList.add('dnd-drop-zone');
                });
            },

            onEnd: function () {
                allContainers.forEach(function (c) {
                    c.classList.remove('dnd-active', 'dnd-drop-zone');
                });
            },

            onAdd: function (evt) {
                // Flash the receiving card to confirm the drop
                var card = evt.to.closest('.db-project-card');
                if (card) {
                    card.classList.add('dnd-received');
                    setTimeout(function () { card.classList.remove('dnd-received'); }, 600);
                }
            },
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
