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
.db-root { display: flex; flex-direction: column; gap: 0; }

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
    font-size: 1rem;
    font-weight: 500;
    color: #555;
    margin-bottom: 0;
}
.db-today-date {
    font-size: 1.7rem;
    font-weight: 800;
    color: var(--db-text);
    margin: 0 0 1.25rem;
    line-height: 1.2;
}

/* ── Info cards (Blockers / Tools / Deploy) ───────────── */
.db-info-card {
    background: var(--db-cream);
    border: 2px solid var(--db-gold);
    border-radius: 16px;
    padding: 1.25rem 1rem;
    height: 100%;
    min-height: 260px;
}
.db-info-title {
    text-align: center;
    font-weight: 800;
    font-size: 1rem;
    color: var(--db-gold-dark);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 1rem;
}

/* Blocker item */
.db-blocker-box {
    border: 2px solid var(--db-text);
    border-radius: 10px;
    padding: 0.55rem 0.8rem;
    margin-bottom: 0.7rem;
}
.db-blocker-name {
    font-weight: 800;
    font-size: 0.76rem;
    color: var(--db-gold-dark);
    text-transform: uppercase;
    text-align: center;
    line-height: 1.3;
    margin-bottom: 0.3rem;
}
.db-blocker-line {
    font-size: 0.76rem;
    font-weight: 700;
    color: var(--db-gold-dark);
    margin: 0;
    line-height: 1.6;
}

/* Info table (tools / manpower) */
.db-info-tbl {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.78rem;
}
.db-info-tbl thead th {
    background: var(--db-gold);
    color: var(--db-text);
    font-weight: 700;
    text-transform: uppercase;
    padding: 0.3rem 0.5rem;
    text-align: center;
}
.db-info-tbl tbody td {
    padding: 0.3rem 0.5rem;
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
</style>
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

    <div class="row g-3 mb-2">

        <!-- BLOCKERS ─────────────────────────────────────── -->
        <div class="col-xl-4 col-md-12">
            <div class="db-info-card">
                <div class="db-info-title">Blockers</div>
                <?php if (empty($blockers)): ?>
                    <p class="db-empty">No blockers — all projects on track.</p>
                <?php else: ?>
                    <?php foreach (array_slice($blockers, 0, 4) as $bl):
                        $endTs     = is_numeric($bl['end_date'] ?? '') ? (int)$bl['end_date'] : @strtotime((string)($bl['end_date'] ?? ''));
                        $delayDays = ($endTs && $endTs < time()) ? (int)floor((time() - $endTs) / 86400) : 0;
                        $reason    = $bl['reason'] ?? $bl['blocker_reason'] ?? (($bl['status'] ?? '') === 'Halted' ? 'Project Halted' : 'Low Progress');
                    ?>
                    <div class="db-blocker-box">
                        <div class="db-blocker-name"><?= esc(strtoupper($bl['project_name'] ?? 'Project')) ?></div>
                        <?php if ($delayDays > 0): ?>
                        <p class="db-blocker-line">POSSIBLE DELAY/S: <?= $delayDays ?> Day<?= $delayDays > 1 ? 's' : '' ?></p>
                        <?php endif; ?>
                        <p class="db-blocker-line">REASON: <?= esc(ucwords((string)$reason)) ?></p>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- AVAILABLE TOOLS ──────────────────────────────── -->
        <div class="col-xl-4 col-md-12">
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
        <div class="col-xl-4 col-md-12">
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

    </div><!-- /row info cards -->

    <!-- Project cards by region ─────────────────────────── -->
    <?php if (empty($projectsByRegion)): ?>
        <p class="db-empty mt-3">No active projects.</p>
    <?php else: ?>
        <?php foreach ($projectsByRegion as $region => $regionProjects): ?>
        <h3 class="db-region-title"><?= esc($region) ?></h3>
        <div class="row g-3 mb-2">
            <?php foreach ($regionProjects as $proj): ?>
            <div class="col-xl-4 col-md-6 col-12">
                <?php echo db_render_card($proj); ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</section><!-- #summary -->


<!-- ═══════════════════════════════════════════════════════════
     MONITOR SECTION
     ═══════════════════════════════════════════════════════════ -->
<section id="monitor" class="db-section">

    <?php if (empty($projectsByRegion)): ?>
        <p class="db-empty">No active projects to monitor.</p>
    <?php else: ?>
        <?php foreach ($projectsByRegion as $region => $regionProjects): ?>
        <h3 class="db-region-title"><?= esc($region) ?></h3>
        <div class="row g-3 mb-2">
            <?php foreach ($regionProjects as $proj): ?>
            <div class="col-xl-4 col-md-6 col-12">
                <?php echo db_render_card($proj); ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</section><!-- #monitor -->

</div><!-- .db-root -->

<?php
/**
 * Render a single project card (shared by Summary and Monitor sections).
 */
function db_render_card(array $p): string
{
    $name     = esc(strtoupper($p['project_name'] ?? 'Project'));
    $progress = min(100, max(0, (float)$p['_progress']));
    $startFmt = esc($p['_start_fmt']);
    $endFmt   = esc($p['_end_fmt']);
    $tools    = (array)($p['_tools']    ?? []);
    $manpower = (array)($p['_manpower'] ?? []);
    $mapsUrl  = esc($p['_maps_url']);
    $cardId   = 'card-' . preg_replace('/[^a-zA-Z0-9]/', '', (string)($p['id'] ?? uniqid()));

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
            <a href="<?= $mapsUrl ?>" class="db-btn-locate" target="_blank" rel="noopener">Locate Site</a>
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

<?= $this->endSection() ?>
