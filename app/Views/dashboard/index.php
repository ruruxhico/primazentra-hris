<?= $this->extend('layouts/main') ?>

<?= $this->section('head') ?>
<link rel="stylesheet" href="<?= asset_url('css/dashboard-summary.css') ?>?v=<?= filemtime(ROOTPATH . 'public/css/dashboard-summary.css') ?>">
<style>
.mp-type-chips{display:flex;flex-wrap:wrap;gap:.45rem;margin-top:.1rem;}
.mp-type-chip{padding:.3rem .85rem;border-radius:999px;border:1.5px solid #C8A951;background:#fff;color:#7a5c1e;font-size:.82rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;cursor:pointer;transition:background .13s,color .13s;}
.mp-type-chip.selected{background:#C8A951;color:#fff;}
.mp-type-chip:hover:not(.selected){background:#fdf3d8;}
</style>
<?php if (!empty($googleMapsBrowserKey)): ?>
<script>
window.__monitorMapKey = '<?= esc($googleMapsBrowserKey ?? '') ?>';
</script>
<?php endif; ?>
<meta name="csrf-name" content="<?= csrf_token() ?>">
<meta name="csrf-hash" content="<?= csrf_hash() ?>">
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

<!-- Resource Request Modal (PM → project card REQUEST button) -->
<div id="reqModalOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9500;display:none;align-items:center;justify-content:center;">
    <div id="reqModalBox" style="background:#fff;border-radius:14px;padding:1.6rem 1.5rem 1.25rem;width:min(380px,92vw);box-shadow:0 8px 32px rgba(0,0,0,.22);position:relative;">
        <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:1.1rem;">
            <span class="material-symbols-outlined" style="font-size:1.5rem;color:#8a6420;">add_circle</span>
            <span style="font-size:1.05rem;font-weight:700;color:#2c1a00;">Request Resources</span>
        </div>
        <div id="reqModalProjName" style="font-size:.83rem;font-weight:600;color:#8a6420;letter-spacing:.04em;margin-bottom:1rem;padding:.35rem .6rem;background:#fdf7e9;border-radius:6px;border:1px solid #e8d89a;"></div>

        <div style="margin-bottom:.85rem;">
            <label style="display:block;font-size:.8rem;font-weight:600;color:#5a4020;margin-bottom:.35rem;text-transform:uppercase;letter-spacing:.04em;">Resource Category</label>
            <select id="reqCategory" style="width:100%;padding:.52rem .7rem;border:1px solid #d5c68a;border-radius:7px;font-size:.93rem;background:#fdf9f0;color:#2c1a00;appearance:auto;">
                <option value="">Choose category</option>
                <option value="Manpower">Manpower</option>
                <option value="Equipment">Equipment</option>
                <option value="Tools">Tools</option>
            </select>
        </div>

        <div id="reqManpowerTypeGroup" style="display:none;margin-bottom:.85rem;">
            <label style="display:block;font-size:.8rem;font-weight:600;color:#5a4020;margin-bottom:.35rem;text-transform:uppercase;letter-spacing:.04em;">Manpower Type</label>
            <div class="mp-type-chips" id="reqManpowerTypeChips" data-target="reqManpowerType">
                <button type="button" class="mp-type-chip" data-value="Foreman">Foreman</button>
                <button type="button" class="mp-type-chip" data-value="Mason">Mason</button>
                <button type="button" class="mp-type-chip" data-value="Electrician">Electrician</button>
                <button type="button" class="mp-type-chip" data-value="Labor/Helper">Labor/Helper</button>
            </div>
            <input type="hidden" id="reqManpowerType" value="">
        </div>

        <div id="reqModelGroup" style="display:none;margin-bottom:.85rem;">
            <label style="display:block;font-size:.8rem;font-weight:600;color:#5a4020;margin-bottom:.35rem;text-transform:uppercase;letter-spacing:.04em;">Select Model</label>
            <select id="reqModel" style="width:100%;padding:.52rem .7rem;border:1px solid #d5c68a;border-radius:7px;font-size:.93rem;background:#fdf9f0;color:#2c1a00;appearance:auto;">
                <option value="">Choose model</option>
            </select>
        </div>

        <div style="margin-bottom:.85rem;">
            <label id="reqQtyLabel" style="display:block;font-size:.8rem;font-weight:600;color:#5a4020;margin-bottom:.35rem;text-transform:uppercase;letter-spacing:.04em;">How many are needed?</label>
            <input type="number" id="reqQty" min="1" value="1"
                   style="width:100%;padding:.52rem .7rem;border:1px solid #d5c68a;border-radius:7px;font-size:.93rem;background:#fdf9f0;color:#2c1a00;box-sizing:border-box;">
        </div>

        <div id="reqModalError" style="display:none;margin-bottom:.75rem;padding:.5rem .75rem;background:#fee2e2;border:1px solid #fca5a5;border-radius:6px;font-size:.85rem;color:#991b1b;"></div>

        <div style="display:flex;gap:.55rem;justify-content:flex-end;">
            <button id="reqCancelBtn" style="padding:.5rem 1.1rem;border:1px solid #d5c68a;border-radius:7px;background:#fdf9f0;color:#5a4020;font-size:.9rem;font-weight:600;cursor:pointer;">Cancel</button>
            <button id="reqSubmitBtn" style="padding:.5rem 1.25rem;border:none;border-radius:7px;background:#8a6420;color:#fff;font-size:.9rem;font-weight:600;cursor:pointer;">Submit Request</button>
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

    <?php
    $displayBlockers = !empty($mlBlockers) ? $mlBlockers : [];
    $activeList      = !empty($displayBlockers) ? $displayBlockers : $blockers;
    $isManual        = empty($displayBlockers);

    $blockerRows = [];
    foreach ($activeList as $bl) {
        if ($isManual) {
            $endTs = is_numeric($bl['end_date'] ?? '') ? (int)$bl['end_date'] : @strtotime((string)($bl['end_date'] ?? ''));
            $dd    = ($endTs && $endTs < time()) ? (int)floor((time() - $endTs) / 86400) : 0;
            $blockerRows[] = [
                'name'        => strtoupper($bl['project_name'] ?? 'Project'),
                'status'      => ucwords((string)($bl['reason'] ?? $bl['blocker_reason'] ?? (($bl['status'] ?? '') === 'Halted' ? 'Project Halted' : 'Low Progress'))),
                'delay'       => $dd . 'd delay',
                'delayc'      => $dd > 0 ? 'pz-delay-warn' : 'pz-delay-ok',
                'reason_html' => '',
            ];
        } else {
            $delay     = (int)($bl['estimated_delay_days'] ?? 0);
            $predicted = strtolower((string)($bl['predicted'] ?? ''));
            $reason    = (string)($bl['reason'] ?? '');

            // Build colored reason HTML
            $reasonHtml = '';
            if ($reason !== '') {
                if ($predicted === 'low progress'
                    && preg_match('/^([\d.]+%\s*Done)\s+([\d.]+%\s*Behind)(.*)?$/iu', $reason, $m)) {
                    $seNote     = trim(ltrim(trim($m[3] ?? ''), "\u{2014}-"));
                    $reasonHtml = '<span class="pz-reason-ok">'   . esc(trim($m[1])) . '</span>'
                                . ' <span class="pz-reason-warn">' . esc(trim($m[2])) . '</span>';
                    if ($seNote !== '') {
                        $reasonHtml .= '<br><small class="pz-reason-se">' . esc($seNote) . '</small>';
                    }
                } else {
                    $reasonHtml = '<span class="pz-reason-text">' . esc($reason) . '</span>';
                }
            }

            $blockerRows[] = [
                'name'        => strtoupper($bl['project_name'] ?? 'Project'),
                'status'      => ucwords((string)($bl['predicted'] ?? 'Risk Detected')),
                'delay'       => $delay . 'd delay',
                'delayc'      => $delay > 0 ? 'pz-delay-warn' : 'pz-delay-ok',
                'reason_html' => $reasonHtml,
            ];
        }
    }

    $usingMcdaTools = !empty($availableToolsMcda);
    $toolsDisplay   = $usingMcdaTools ? $availableToolsMcda : $availableTools;
    ?>

    <!-- ── PREDICTED BLOCKERS ── -->
    <?php
        $bPageSize = 3;              // rows per column per page
        $bTotal    = count($blockerRows);
        $bPages    = $bTotal > 0 ? (int)ceil($bTotal / ($bPageSize * 2)) : 1;
        // pre-chunk rows into pages of 6 (left 3 + right 3)
        $bChunks   = array_chunk($blockerRows, $bPageSize * 2);
    ?>
    <div class="pz-sc">
        <div class="pz-sc-head">Predicted Blockers</div>
        <?php if (empty($blockerRows)): ?>
            <p class="pz-sc-empty">No predicted blockers — all projects on track.</p>
        <?php else: ?>

        <?php foreach ($bChunks as $pi => $chunk): ?>
        <div class="pz-blocker-page" data-bpage="<?= $pi ?>" <?= $pi > 0 ? 'style="display:none"' : '' ?>>
            <div class="pz-sc-cols">
                <!-- Left half -->
                <div class="pz-sc-half">
                    <table class="pz-sc-tbl">
                        <thead><tr><th>Project</th><th>Status</th><th>Reason</th><th class="pz-sc-r">Delay</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($chunk, 0, $bPageSize) as $row): ?>
                        <tr>
                            <td class="pz-sc-name" title="<?= esc($row['name']) ?>"><?= esc($row['name']) ?></td>
                            <td><?= esc($row['status']) ?></td>
                            <td class="pz-sc-reason"><?= $row['reason_html'] ?></td>
                            <td class="pz-sc-r <?= $row['delayc'] ?>"><?= esc($row['delay']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (count($chunk) > $bPageSize): ?>
                <div class="pz-sc-divider"></div>
                <!-- Right half -->
                <div class="pz-sc-half">
                    <table class="pz-sc-tbl">
                        <thead><tr><th>Project</th><th>Status</th><th>Reason</th><th class="pz-sc-r">Delay</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($chunk, $bPageSize) as $row): ?>
                        <tr>
                            <td class="pz-sc-name" title="<?= esc($row['name']) ?>"><?= esc($row['name']) ?></td>
                            <td><?= esc($row['status']) ?></td>
                            <td class="pz-sc-reason"><?= $row['reason_html'] ?></td>
                            <td class="pz-sc-r <?= $row['delayc'] ?>"><?= esc($row['delay']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if ($bPages > 1): ?>
        <div class="pz-sc-nav">
            <button class="pz-sc-nav-btn" id="bPrev" onclick="bNav(-1)" disabled>
                <span class="material-symbols-outlined">chevron_left</span>
            </button>
            <span class="pz-sc-nav-label" id="bLabel">1 / <?= $bPages ?></span>
            <button class="pz-sc-nav-btn" id="bNext" onclick="bNav(1)" <?= $bPages <= 1 ? 'disabled' : '' ?>>
                <span class="material-symbols-outlined">chevron_right</span>
            </button>
        </div>
        <script>
        (function(){
            var cur=0, total=<?= $bPages ?>;
            window.bNav = function(d){
                document.querySelectorAll('.pz-blocker-page[data-bpage="'+cur+'"]').forEach(function(el){ el.style.display='none'; });
                cur = Math.max(0, Math.min(total-1, cur+d));
                document.querySelectorAll('.pz-blocker-page[data-bpage="'+cur+'"]').forEach(function(el){ el.style.display=''; });
                document.getElementById('bLabel').textContent = (cur+1)+' / '+total;
                document.getElementById('bPrev').disabled = cur===0;
                document.getElementById('bNext').disabled = cur===total-1;
            };
        })();
        </script>
        <?php endif; ?>

        <?php endif; ?>
    </div>

    <hr style="border:none;border-top:2px solid #e8d9a8;margin:1.5rem 0;">

    <!-- ── AVAILABLE TOOLS  +  AVAILABLE TO DEPLOY (side by side) ── -->
    <div class="pz-sc-row">

        <!-- Available Tools -->
        <?php
            $tPageSize = 3;
            $tTotal    = count($toolsDisplay);
            $tPages    = $tTotal > 0 ? (int)ceil($tTotal / $tPageSize) : 1;
            $tChunks   = array_chunk($toolsDisplay, $tPageSize);
        ?>
        <div class="pz-sc">
            <div class="pz-sc-head">
                Available Resources<?php if ($usingMcdaTools): ?> <span style="font-size:.6rem;color:#1e6b45;font-weight:700;">AI</span><?php endif; ?>
            </div>
            <?php if (empty($toolsDisplay)): ?>
                <p class="pz-sc-empty">No resources available.</p>
            <?php else: ?>

            <?php foreach ($tChunks as $pi => $chunk): ?>
            <div class="pz-tools-page" data-tpage="<?= $pi ?>" <?= $pi > 0 ? 'style="display:none"' : '' ?>>
                <table class="pz-sc-tbl">
                    <thead><tr>
                        <th>Name</th>
                        <th style="width:3rem;text-align:center">Qty</th>
                        <?php if ($usingMcdaTools): ?><th>Next Site</th><?php endif; ?>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($chunk as $t): ?>
                    <tr>
                        <td class="pz-sc-name" title="<?= esc(strtoupper($t['name'] ?? '')) ?>"><?= esc(strtoupper($t['name'] ?? 'N/A')) ?></td>
                        <td style="text-align:center"><?= esc((string)($t['qty'] ?? $t['available_quantity'] ?? $t['asset_details']['available_quantity'] ?? $t['available_units_display'] ?? '0')) ?></td>
                        <?php if ($usingMcdaTools): ?>
                        <td style="color:#777;font-size:.75rem"><?= esc($t['next_deployment'] ?? '—') ?></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>

            <?php if ($tPages > 1): ?>
            <div class="pz-sc-nav">
                <button class="pz-sc-nav-btn" id="tPrev" onclick="tNav(-1)" disabled>
                    <span class="material-symbols-outlined">chevron_left</span>
                </button>
                <span class="pz-sc-nav-label" id="tLabel">1 / <?= $tPages ?></span>
                <button class="pz-sc-nav-btn" id="tNext" onclick="tNav(1)">
                    <span class="material-symbols-outlined">chevron_right</span>
                </button>
            </div>
            <script>
            (function(){
                var cur=0, total=<?= $tPages ?>;
                window.tNav = function(d){
                    document.querySelectorAll('.pz-tools-page[data-tpage="'+cur+'"]').forEach(function(el){ el.style.display='none'; });
                    cur = Math.max(0, Math.min(total-1, cur+d));
                    document.querySelectorAll('.pz-tools-page[data-tpage="'+cur+'"]').forEach(function(el){ el.style.display=''; });
                    document.getElementById('tLabel').textContent = (cur+1)+' / '+total;
                    document.getElementById('tPrev').disabled = cur===0;
                    document.getElementById('tNext').disabled = cur===total-1;
                };
            })();
            </script>
            <?php endif; ?>

            <?php endif; ?>
        </div>

        <!-- Available to Deploy -->
        <?php
            $dPageSize = 3;
            $dTotal    = count($availableManpower);
            $dPages    = $dTotal > 0 ? (int)ceil($dTotal / $dPageSize) : 1;
            $dChunks   = array_chunk($availableManpower, $dPageSize);
        ?>
        <div class="pz-sc">
            <div class="pz-sc-head">Available to Deploy</div>
            <?php if (empty($availableManpower)): ?>
                <p class="pz-sc-empty">No manpower available.</p>
            <?php else: ?>

            <?php foreach ($dChunks as $pi => $chunk): ?>
            <div class="pz-deploy-page" data-dpage="<?= $pi ?>" <?= $pi > 0 ? 'style="display:none"' : '' ?>>
                <table class="pz-sc-tbl">
                    <thead><tr><th>Name</th><th>Role</th></tr></thead>
                    <tbody>
                    <?php foreach ($chunk as $mp): ?>
                    <tr>
                        <td class="pz-sc-name" title="<?= esc(strtoupper($mp['name'] ?? '')) ?>"><?= esc(strtoupper($mp['name'] ?? 'N/A')) ?></td>
                        <td><?= esc(strtoupper($mp['manpower_details']['role'] ?? $mp['role'] ?? 'Field Worker')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>

            <?php if ($dPages > 1): ?>
            <div class="pz-sc-nav">
                <button class="pz-sc-nav-btn" id="dPrev" onclick="dNav(-1)" disabled>
                    <span class="material-symbols-outlined">chevron_left</span>
                </button>
                <span class="pz-sc-nav-label" id="dLabel">1 / <?= $dPages ?></span>
                <button class="pz-sc-nav-btn" id="dNext" onclick="dNav(1)">
                    <span class="material-symbols-outlined">chevron_right</span>
                </button>
            </div>
            <script>
            (function(){
                var cur=0, total=<?= $dPages ?>;
                window.dNav = function(d){
                    document.querySelectorAll('.pz-deploy-page[data-dpage="'+cur+'"]').forEach(function(el){ el.style.display='none'; });
                    cur = Math.max(0, Math.min(total-1, cur+d));
                    document.querySelectorAll('.pz-deploy-page[data-dpage="'+cur+'"]').forEach(function(el){ el.style.display=''; });
                    document.getElementById('dLabel').textContent = (cur+1)+' / '+total;
                    document.getElementById('dPrev').disabled = cur===0;
                    document.getElementById('dNext').disabled = cur===total-1;
                };
            })();
            </script>
            <?php endif; ?>

            <?php endif; ?>
        </div>

    </div><!-- /pz-sc-row -->

    <!-- ── RESOURCE UTILIZATION ── -->
    <?php
    $util    = $utilizationStats ?? ['equipment' => ['total'=>0,'deployed'=>0,'pct'=>0], 'tools' => ['total'=>0,'deployed'=>0,'pct'=>0], 'combined' => ['total'=>0,'deployed'=>0,'pct'=>0]];
    $eqPct   = (int)($util['equipment']['pct'] ?? 0);
    $tlPct   = (int)($util['tools']['pct']     ?? 0);
    $combPct = (int)($util['combined']['pct']  ?? 0);
    ?>
    <div class="pz-sc pz-util-card">
        <div class="pz-util-head">
            <span class="material-symbols-outlined pz-util-head-icon">bar_chart</span>
            Resource Utilization
        </div>
        <div class="pz-util-bars">
            <div class="pz-util-row">
                <span class="pz-util-label">Equipment</span>
                <div class="pz-util-track">
                    <div class="pz-util-fill <?= $eqPct >= 80 ? 'pz-util-high' : ($eqPct >= 40 ? 'pz-util-mid' : 'pz-util-low') ?>"
                         style="width:<?= $eqPct ?>%"></div>
                </div>
                <span class="pz-util-pct"><?= $eqPct ?>% <span class="pz-util-frac">(<?= $util['equipment']['deployed'] ?> / <?= $util['equipment']['total'] ?>)</span></span>
            </div>
            <div class="pz-util-row">
                <span class="pz-util-label">Tools</span>
                <div class="pz-util-track">
                    <div class="pz-util-fill <?= $tlPct >= 80 ? 'pz-util-high' : ($tlPct >= 40 ? 'pz-util-mid' : 'pz-util-low') ?>"
                         style="width:<?= $tlPct ?>%"></div>
                </div>
                <span class="pz-util-pct"><?= $tlPct ?>% <span class="pz-util-frac">(<?= $util['tools']['deployed'] ?> / <?= $util['tools']['total'] ?>)</span></span>
            </div>
            <div class="pz-util-row pz-util-combined">
                <span class="pz-util-label">Combined</span>
                <div class="pz-util-track">
                    <div class="pz-util-fill <?= $combPct >= 80 ? 'pz-util-high' : ($combPct >= 40 ? 'pz-util-mid' : 'pz-util-low') ?>"
                         style="width:<?= $combPct ?>%"></div>
                </div>
                <span class="pz-util-pct"><?= $combPct ?>% <span class="pz-util-frac">(<?= $util['combined']['deployed'] ?> / <?= $util['combined']['total'] ?>)</span></span>
            </div>
        </div>
        <div class="pz-util-footer">
            <a href="<?= base_url('export?module=utilization&format=xlsx') ?>" class="pz-util-dl">
                <span class="material-symbols-outlined">download</span> Download full report
            </a>
        </div>
    </div>

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
                        <button type="button" class="db-filter-pill db-filter-pill--warning" data-status="DELAYED">Delayed</button>
                        <button type="button" class="db-filter-pill db-filter-pill--danger" data-status="PAST_DEADLINE">Past Deadline</button>
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
                 data-status="<?= esc($proj['_status'] ?? 'ONGOING') ?>"
                 data-delay="<?= (int)($proj['_ml_delay_days'] ?? 0) ?>">
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

<!-- ── Report Modal (shared for Emergency + Daily Progress) ──────────── -->
<div id="reportModal" class="rpt-overlay" role="dialog" aria-modal="true">
    <div class="rpt-modal">
        <div class="rpt-modal-hdr">
            <button class="rpt-hdr-back" id="rptHdrBack" title="Back" onclick="rptNavBack()">
                <span class="material-symbols-outlined">arrow_back</span>
            </button>
            <span class="rpt-modal-title" id="rptModalTitle">Report</span>
            <button class="rpt-hdr-close" onclick="closeReportModal()" title="Close">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="rpt-modal-body">
            <!-- Calendar state -->
            <div id="rptViewCal">
                <div id="rptCalEl"></div>
                <div class="rpt-legend">
                    <span class="rpt-legend-item"><span class="rpt-dot filled"></span>Has report</span>
                    <span class="rpt-legend-item"><span class="rpt-dot outline"></span>Today</span>
                </div>
                <div id="rptSummaryBtns" style="display:none;margin-top:1rem;display:flex;flex-direction:column;gap:.5rem;">
                    <button class="rpt-summary-btn" onclick="showProgressSummary('overall')">
                        <span class="material-symbols-outlined">table_chart</span> Overall Progress Summary
                    </button>
                </div>
            </div>
            <!-- Detail state -->
            <div id="rptViewDetail" style="display:none">
                <div id="rptDetailEl"></div>
            </div>
            <!-- New report form state -->
            <div id="rptViewForm" style="display:none">
                <div id="rptFormEl"></div>
            </div>
            <!-- Summary state -->
            <div id="rptViewSummary" style="display:none">
                <div id="rptSummaryEl"></div>
            </div>
        </div>
        <div class="rpt-modal-footer" id="rptFooter"></div>
    </div>
</div>

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
    $cardId      = 'card-' . preg_replace('/[^a-zA-Z0-9]/', '', (string)($p['id'] ?? uniqid()));
    $lat         = $p['_lat'] ?? null;
    $lng         = $p['_lng'] ?? null;
    $isPastDl    = !empty($p['_past_deadline']);
    $mlDelay     = (int)($p['_ml_delay_days'] ?? 0);
    $cardClasses = 'db-project-card';
    if ($isPastDl)      $cardClasses .= ' db-card--past-deadline';
    elseif ($mlDelay > 0) $cardClasses .= ' db-card--delayed';

    ob_start();
    ?>
    <div class="<?= $cardClasses ?>" id="<?= $cardId ?>" data-pid="<?= esc($p['id'] ?? '') ?>" data-pname="<?= esc(strtoupper($p['project_name'] ?? '')) ?>">
        <?php if ($isPastDl || $mlDelay > 0): ?>
        <div class="db-card-delay-banner <?= $isPastDl ? 'db-card-delay-banner--deadline' : '' ?>">
            <span class="material-symbols-outlined">schedule</span>
            <?= $isPastDl ? 'Past Deadline' : "Delayed ~{$mlDelay}d (ML)" ?>
        </div>
        <?php endif; ?>

        <!-- Project name -->
        <?php if ($context === 'monitor'): ?>
        <a href="<?= base_url('projects/show/' . esc($p['id'] ?? '')) ?>" class="db-project-name-box db-project-name-link">
            <?= $name ?>
            <span class="material-symbols-outlined">open_in_new</span>
        </a>
        <?php else: ?>
        <div class="db-project-name-box"><?= $name ?></div>
        <?php endif; ?>

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
                            $resName   = is_array($res) ? ($res['name']    ?? '') : $res;
                            $resId     = is_array($res) ? ($res['id']      ?? '') : '';
                            $resUnitId = is_array($res) ? ($res['unit_id'] ?? '') : '';
                            $isPending = is_array($res) && !empty($res['pending']);
                            $isTransit = is_array($res) && !empty($res['transit']);
                            $reqId     = is_array($res) ? (string)($res['req_id'] ?? '') : '';
                            $pillClass = 'db-pill';
                            if ($isPending)      $pillClass .= ' db-pill--pending';
                            elseif ($isTransit)  $pillClass .= ' db-pill--transit';
                        ?>
                        <div class="<?= $pillClass ?>"
                             data-res-name="<?= esc($resName) ?>"
                             data-res-id="<?= esc($resId) ?>"
                             data-unit-id="<?= esc($resUnitId) ?>"
                             <?= $isPending ? 'data-pending="1" data-req-id="' . esc($reqId) . '"' : '' ?>>
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
            <a href="<?= base_url('messages/project/' . esc($p['id'] ?? '')) ?>"
               class="db-icon-btn" title="Messages" style="text-decoration:none">
                <span class="material-symbols-outlined db-icon-chat">chat_bubble</span>
            </a>
            <?php if ($context === 'monitor'): ?>
            <button class="db-icon-btn db-icon-emergency"
                    data-pid="<?= esc($p['id'] ?? '') ?>"
                    data-pname="<?= $name ?>"
                    onclick="openReportModal('emergency',this.dataset.pid,this.dataset.pname)"
                    title="Emergency Reports">
                <span class="material-symbols-outlined">warning</span>
            </button>
            <button class="db-icon-btn db-icon-report"
                    data-pid="<?= esc($p['id'] ?? '') ?>"
                    data-pname="<?= $name ?>"
                    onclick="openReportModal('daily',this.dataset.pid,this.dataset.pname)"
                    title="Progress Reports">
                <span class="material-symbols-outlined">description</span>
            </button>
            <button class="db-btn-locate"
                    data-lat="<?= $lat !== null ? (float)$lat : '' ?>"
                    data-lng="<?= $lng !== null ? (float)$lng : '' ?>"
                    data-name="<?= esc(strtoupper($p['project_name'] ?? '')) ?>"
                    title="Focus on map">Locate Site</button>
            <?php if (pz_is('Project Manager')): ?>
            <button class="db-btn-request"
                    data-pid="<?= esc($p['id'] ?? '') ?>"
                    data-pname="<?= esc(strtoupper($p['project_name'] ?? '')) ?>"
                    onclick="openRequestModal(this.dataset.pid, this.dataset.pname)"
                    title="Request Resources">Request</button>
            <?php endif; ?>
            <?php else: ?>
            <span class="material-symbols-outlined db-icon-warn" title="Warnings">warning</span>
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

// ── Report Modal ─────────────────────────────────────────────────────────
(function () {
    // Permissions injected from PHP
    var _base = '<?= rtrim(base_url(), '/') ?>';
    var CAN_CREATE_EMERGENCY  = <?= pz_can('create_emergency_report')  ? 'true' : 'false' ?>;
    var CAN_RESOLVE_EMERGENCY = <?= pz_can('resolve_emergency_report') ? 'true' : 'false' ?>;
    var CAN_EDIT_PROGRESS     = <?= pz_can('edit_progress_report')     ? 'true' : 'false' ?>;

    var _type  = ''; // 'emergency' | 'daily'
    var _pid   = '';
    var _pname = '';
    var _dates = [];  // YYYY-MM-DD strings visible on calendar
    var _idMap = {};  // date → docId (emergency reports only; auto-generated IDs)
    var _calMo = new Date(); // currently displayed calendar month

    var _state = 'cal'; // 'cal' | 'detail' | 'form' | 'summary'
    var _summaryType = ''; // 'weekly' | 'overall'
    var _summaryData = null; // cached per modal-open

    window.openReportModal = function (type, pid, pname) {
        _type  = type;
        _pid   = pid;
        _pname = pname;
        _summaryData = null;

        var overlay = document.getElementById('reportModal');
        overlay.setAttribute('data-type', type);
        document.getElementById('rptModalTitle').textContent =
            (type === 'emergency' ? 'Emergency Reports' : 'Progress Reports') + ' — ' + pname;

        _calMo = new Date(); _calMo.setDate(1);
        _dates = []; _idMap = {};
        setState('cal');
        overlay.classList.add('open');
        fetchDates();
    };

    window.closeReportModal = function () {
        document.getElementById('reportModal').classList.remove('open');
    };

    window.rptNavBack = function () {
        if (_state === 'cal') { closeReportModal(); }
        else {
            document.getElementById('rptModalTitle').textContent =
                (_type === 'emergency' ? 'Emergency Reports' : 'Progress Reports') + ' — ' + _pname;
            setState('cal');
        }
    };

    function setState(s) {
        _state = s;
        document.getElementById('rptViewCal').style.display     = (s === 'cal')     ? '' : 'none';
        document.getElementById('rptViewDetail').style.display  = (s === 'detail')  ? '' : 'none';
        document.getElementById('rptViewForm').style.display    = (s === 'form')    ? '' : 'none';
        document.getElementById('rptViewSummary').style.display = (s === 'summary') ? '' : 'none';
        var modal = document.querySelector('#reportModal .rpt-modal');
        if (modal) modal.classList.toggle('wide', s === 'summary');
        var sb = document.getElementById('rptSummaryBtns');
        if (sb) sb.style.display = (s === 'cal' && _type === 'daily') ? 'flex' : 'none';
        renderFooter();
    }

    function renderFooter() {
        var f   = document.getElementById('rptFooter');
        var html = '';
        if (_state !== 'cal') {
            html += '<button class="rpt-btn-back" onclick="rptNavBack()"><span class="material-symbols-outlined">arrow_back</span>Back</button>';
        }
        if (_state === 'cal') {
            var canCreate = (_type === 'emergency' && CAN_CREATE_EMERGENCY) ||
                            (_type === 'daily'     && CAN_EDIT_PROGRESS);
            if (canCreate) {
                html += '<button class="rpt-btn-new" onclick="showNewReportForm()"><span class="material-symbols-outlined">add</span>New Report</button>';
            }
        }
        if (_state === 'form') {
            html += '<button class="rpt-btn-submit" onclick="submitReport()">Submit</button>';
        }
        if (_state === 'summary') {
            html += '<button class="rpt-btn-print" onclick="printProgressSummary()"><span class="material-symbols-outlined">picture_as_pdf</span>Download PDF</button>';
        }
        f.innerHTML = html;
    }

    window.printProgressSummary = function () {
        var fromId = _summaryType === 'weekly' ? 'rptWkFrom' : 'rptMsFrom';
        var toId   = _summaryType === 'weekly' ? 'rptWkTo'   : 'rptMsTo';
        var fromEl = document.getElementById(fromId);
        var toEl   = document.getElementById(toId);

        var params = new URLSearchParams({
            type:      _summaryType,
            date_from: fromEl ? (fromEl.value || '') : '',
            date_to:   toEl   ? (toEl.value   || '') : '',
        });
        window.location.href = _base + '/projects/' + _pid + '/summary-pdf?' + params.toString();
    };

    function fetchDates() {
        var url = _base + '/projects/' + _pid +
                  (_type === 'emergency' ? '/emergency-reports/json' : '/daily-reports/json');
        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                _dates = []; _idMap = {};
                if (_type === 'emergency') {
                    // Mobile uses auto-generated doc IDs; list returns [{id, date}]
                    (d.reports || []).forEach(function (r) {
                        if (r.date) { _dates.push(r.date); _idMap[r.date] = r.id; }
                    });
                } else {
                    _dates = d.dates || [];
                }
                renderCal();
            })
            .catch(function () { _dates = []; renderCal(); });
    }

    function renderCal() {
        var year  = _calMo.getFullYear();
        var month = _calMo.getMonth();
        var today = new Date(); today.setHours(0,0,0,0);
        var monthLabel = _calMo.toLocaleString('default', { month: 'long', year: 'numeric' });

        var html = '<div class="rpt-cal-nav">';
        html += '<button onclick="rptPrevMo()"><span class="material-symbols-outlined">chevron_left</span></button>';
        html += '<span class="rpt-cal-month">' + monthLabel + '</span>';
        html += '<button onclick="rptNextMo()"><span class="material-symbols-outlined">chevron_right</span></button>';
        html += '</div>';

        html += '<div class="rpt-cal-grid">';
        ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(function (d) {
            html += '<div class="rpt-dow">' + d + '</div>';
        });
        var firstDay = new Date(year, month, 1).getDay();
        for (var i = 0; i < firstDay; i++) html += '<div></div>';
        var days = new Date(year, month + 1, 0).getDate();
        for (var d = 1; d <= days; d++) {
            var ds = year + '-' + pad(month + 1) + '-' + pad(d);
            var hasRpt = _dates.indexOf(ds) !== -1;
            var isTd   = new Date(year, month, d).getTime() === today.getTime();
            var cls    = 'rpt-cal-day' + (hasRpt ? ' has-report' : '') + (isTd ? ' is-today' : '');
            if (hasRpt) {
                html += '<div class="' + cls + '" onclick="viewReport(\'' + ds + '\')">' + d + '</div>';
            } else {
                html += '<div class="' + cls + '">' + d + '</div>';
            }
        }
        html += '</div>';
        document.getElementById('rptCalEl').innerHTML = html;
    }

    window.rptPrevMo = function () { _calMo.setMonth(_calMo.getMonth() - 1); renderCal(); };
    window.rptNextMo = function () { _calMo.setMonth(_calMo.getMonth() + 1); renderCal(); };

    window.viewReport = function (dateStr) {
        // Emergency reports use auto-generated Firestore IDs; daily reports use YYYY-MM-DD as doc ID
        var segment = (_type === 'emergency' && _idMap[dateStr]) ? _idMap[dateStr] : dateStr;
        var url = _base + '/projects/' + _pid +
                  (_type === 'emergency' ? '/emergency-reports/' : '/daily-reports/') + segment;
        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) { renderDetail(d, dateStr); setState('detail'); })
            .catch(function () { showToast('Failed to load report.', 'error'); });
    };

    function renderDetail(d, dateStr) {
        var fmtDate = new Date(dateStr + 'T00:00:00').toLocaleDateString('en-US',
            { year:'numeric', month:'long', day:'numeric' });
        var html = '';
        if (_type === 'emergency') {
            // Status badge
            if (d.resolved) {
                html += '<div class="rpt-detail-badge" style="background:#f0fdf4;color:#15803d;border-color:#bbf7d0;">' +
                        '<span class="material-symbols-outlined" style="font-size:16px">check_circle</span>Resolved — ' + h(_pname) + '</div>';
            } else {
                html += '<div class="rpt-detail-badge"><span class="material-symbols-outlined">warning</span>Emergency — ' + h(_pname) + '</div>';
            }
            // Show Filed timestamp if available, otherwise fall back to date only
            if (d.created_at) {
                html += '<div class="rpt-detail-date"><span class="material-symbols-outlined">schedule</span>Filed: ' + h(d.created_at) + '</div>';
            } else {
                html += '<div class="rpt-detail-date"><span class="material-symbols-outlined">calendar_today</span>Date: ' + fmtDate + '</div>';
            }
            // Resolved timestamp
            if (d.resolved && d.resolved_at) {
                html += '<div class="rpt-detail-date" style="color:#15803d;"><span class="material-symbols-outlined" style="color:#15803d;">task_alt</span>Resolved: ' + h(d.resolved_at) +
                        (d.resolved_by_name ? ' by ' + h(d.resolved_by_name) : '') + '</div>';
            }
            // Causes
            var causes = d.causes || [];
            html += '<div class="rpt-detail-section"><strong>Cause(s)</strong>';
            if (causes.length) {
                html += '<ul class="rpt-cause-list">';
                causes.forEach(function (c) {
                    var label = h(c);
                    if (c === 'Other' && d.other_cause) label += ': ' + h(d.other_cause);
                    html += '<li><span class="material-symbols-outlined rpt-cause-icon">report</span>' + label + '</li>';
                });
                html += '</ul>';
            } else {
                html += '<p style="color:#888;font-style:italic">No cause recorded.</p>';
            }
            html += '</div>';
            // Notes
            html += '<div class="rpt-detail-section"><strong>Notes</strong>';
            html += d.notes ? '<p>' + h(d.notes) + '</p>' : '<p style="color:#888;font-style:italic">No notes recorded.</p>';
            html += '</div>';
            // Filed by + resolve button
            if (d.filed_by) html += '<div class="rpt-detail-meta">Filed by: ' + h(d.filed_by) + '</div>';
            if (!d.resolved && CAN_RESOLVE_EMERGENCY) {
                html += '<button type="button" class="rpt-resolve-btn" id="rptResolveBtn">' +
                        '<span class="material-symbols-outlined">check_circle</span> Mark as Resolved</button>';
            }
        } else {
            // Date header
            html += '<div class="rpt-daily-date">' + fmtDate + '</div>';

            // Weather
            if (d.weather) {
                html += '<div class="rpt-daily-row">Weather: ' + h(d.weather) + '</div>';
            }

            // Manpower
            var crew = d.manpower || [];
            if (crew.length) {
                html += '<div class="rpt-daily-section-title">Manpower</div><ul class="rpt-daily-crew">';
                crew.forEach(function (m) {
                    html += '<li>' + h(m.name);
                    if (m.external) html += ' <span class="rpt-daily-ext">(not part of assignment)</span>';
                    html += '</li>';
                });
                html += '</ul>';
            } else if (d.manpower_display) {
                html += '<div class="rpt-daily-section-title">Manpower</div>';
                html += '<div class="rpt-daily-row">' + h(d.manpower_display) + '</div>';
            }

            // Total Completion
            if (d.progress_percent !== null && d.progress_percent !== undefined) {
                html += '<div class="rpt-daily-completion">Total Completion: ' + d.progress_percent + '%</div>';
            }

            // Milestone Progress
            var ms = d.milestone_progress || [];
            if (ms.length) {
                html += '<hr class="rpt-daily-divider">';
                html += '<div class="rpt-daily-section-title">Milestone Progress</div>';
                ms.forEach(function (m) {
                    var label    = h(m.name) + (m.unit ? ' (' + h(m.unit) + ')' : '');
                    var todayVal = (m.today >= 0 ? '+' : '') + m.today;
                    var pct      = Math.min(100, Math.max(0, parseFloat(m.percent) || 0));
                    html += '<div class="rpt-daily-milestone">';
                    html += '<div class="rpt-daily-ms-name">' + label + '</div>';
                    html += '<div class="rpt-daily-ms-meta">Today: ' + todayVal +
                            ' &bull; Total: ' + m.total +
                            ' &bull; ' + m.percent + '%</div>';
                    html += '<div class="rpt-daily-ms-bar-wrap"><div class="rpt-daily-ms-bar" style="width:' + pct + '%"></div></div>';
                    var imgs = m.image_urls || [];
                    if (imgs.length) {
                        html += '<div class="rpt-ms-photos">';
                        imgs.forEach(function (url, idx) {
                            html += '<img src="' + h(url) + '" class="rpt-ms-thumb" loading="lazy"' +
                                    ' data-imgs=\'' + JSON.stringify(imgs).replace(/'/g, '&#39;') + '\'' +
                                    ' data-idx="' + idx + '">';
                        });
                        html += '</div>';
                    }
                    html += '</div>';
                });
            }

            // Notes
            if (d.description) {
                html += '<div class="rpt-daily-section-title" style="margin-top:.8rem">Notes</div>';
                html += '<div class="rpt-daily-row">' + h(d.description) + '</div>';
            }

            // Remarks (mobile field)
            if (d.remarks) {
                html += '<div class="rpt-daily-section-title" style="margin-top:.8rem">Remarks</div>';
                html += '<div class="rpt-daily-row">' + h(d.remarks) + '</div>';
            }

            if (d.filed_by) html += '<div class="rpt-detail-meta" style="margin-top:.6rem">Filed by: ' + h(d.filed_by) + '</div>';
        }
        document.getElementById('rptDetailEl').innerHTML = html;

        // Wire up photo thumbnails (lightbox)
        document.getElementById('rptDetailEl').querySelectorAll('.rpt-ms-thumb').forEach(function (img) {
            img.addEventListener('click', function () {
                try {
                    pzOpenLightbox(JSON.parse(img.getAttribute('data-imgs')), parseInt(img.getAttribute('data-idx'), 10) || 0);
                } catch (e) {}
            });
        });

        // Wire up resolve button if present
        var resolveBtn = document.getElementById('rptResolveBtn');
        if (resolveBtn) {
            resolveBtn.addEventListener('click', function () {
                resolveBtn.disabled = true;
                resolveBtn.textContent = 'Resolving…';
                var resolveDocId = d.id;
                fetch(_base + '/projects/' + _pid + '/emergency-reports/' + resolveDocId + '/resolve', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new URLSearchParams({ '<?= csrf_token() ?>': '<?= csrf_hash() ?>' })
                })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.success) {
                        var detailUrl = _base + '/projects/' + _pid + '/emergency-reports/' + resolveDocId;
                        fetch(detailUrl, { credentials: 'same-origin' })
                            .then(function (r) { return r.json(); })
                            .then(function (fresh) { renderDetail(fresh, dateStr); fetchDates(); })
                            .catch(function () { location.reload(); });
                    } else {
                        alert(res.error || 'Failed to resolve.');
                        resolveBtn.disabled = false;
                        resolveBtn.innerHTML = '<span class="material-symbols-outlined">check_circle</span> Mark as Resolved';
                    }
                })
                .catch(function () {
                    alert('Network error. Please try again.');
                    resolveBtn.disabled = false;
                    resolveBtn.innerHTML = '<span class="material-symbols-outlined">check_circle</span> Mark as Resolved';
                });
            });
        }
    }

    window.showNewReportForm = function () {
        var today = new Date().toISOString().slice(0, 10);
        var todayDisp = today.slice(8,10) + '/' + today.slice(5,7) + '/' + today.slice(0,4);
        var html  = '<div class="rpt-form-title">' +
                    (_type === 'emergency' ? 'Emergency' : 'Progress Report') +
                    ' for ' + h(_pname) + '</div>';
        html += '<form id="rptNewForm">';
        html += '<div class="rpt-form-group"><label>Date *</label>' +
                '<div class="date-picker" id="rptDatePicker">' +
                '<input type="text" class="date-display" placeholder="DD/MM/YYYY" value="' + todayDisp + '" readonly>' +
                '<input type="hidden" name="date" value="' + today + '">' +
                '<button type="button" class="date-toggle"><span class="material-symbols-outlined">calendar_month</span></button>' +
                '<div class="calendar-popup" aria-hidden="true"></div>' +
                '</div></div>';

        if (_type === 'emergency') {
            var causes = ['Adverse Weather','Material Shortage','Equipment/Tool Breakdown',
                          'Labor Shortage','Onsite Accidents','Other'];
            html += '<div class="rpt-form-group"><label>Cause *</label><div class="rpt-causes">';
            causes.forEach(function (c) {
                html += '<label class="rpt-cause-item">' +
                        '<input type="checkbox" name="causes[]" value="' + h(c) + '">' +
                        '<span>' + h(c) + '</span></label>';
            });
            html += '</div></div>';
        } else {
            html += '<div class="rpt-form-group"><label>Progress %</label>' +
                    '<input type="number" name="progress_percent" min="0" max="100" step="1" placeholder="0–100"></div>';
            html += '<div class="rpt-form-group"><label>Description</label>' +
                    '<textarea name="description" rows="4" placeholder="Describe the day\'s progress..."></textarea></div>';
        }

        if (_type === 'emergency') {
            html += '<div class="rpt-form-group"><label>Notes</label>' +
                    '<textarea name="notes" rows="4" placeholder="Describe the emergency specifics..." autocapitalize="off" autocorrect="off"></textarea></div>';
        }
        html += '</form>';
        document.getElementById('rptFormEl').innerHTML = html;
        var dpWrapper = document.getElementById('rptDatePicker');
        if (dpWrapper && window.pzInitDatePicker) {
            window.pzInitDatePicker(dpWrapper, {
                getMax: function () { return new Date().toISOString().slice(0, 10); }
            });
        }
        setState('form');
    };

    window.submitReport = function () {
        var form = document.getElementById('rptNewForm');
        if (!form) return;
        if (_type === 'emergency') {
            var checked = form.querySelectorAll('input[name="causes[]"]:checked');
            if (!checked.length) {
                var causesBox = form.querySelector('.rpt-causes');
                if (causesBox) {
                    causesBox.style.outline = '2px solid #c0392b';
                    causesBox.style.borderRadius = '6px';
                    setTimeout(function () { causesBox.style.outline = ''; }, 2000);
                }
                return;
            }
        }
        var fd = new FormData(form);
        var csrfName = (document.querySelector('meta[name="csrf-name"]') || {}).content;
        var csrfHash = (document.querySelector('meta[name="csrf-hash"]') || {}).content;
        if (csrfName && csrfHash) fd.append(csrfName, csrfHash);

        var url = _base + '/projects/' + _pid +
                  (_type === 'emergency' ? '/emergency-reports' : '/daily-reports');
        fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.success) {
                    var label = _type === 'emergency' ? 'Emergency Filed!' : 'Report Submitted!';
                    var sub   = _type === 'emergency' ? 'The emergency has been recorded.' : 'Your progress report has been saved.';
                    var icon  = _type === 'emergency' ? 'warning' : 'check_circle';
                    var color = _type === 'emergency' ? '#c0392b' : '#15803d';
                    var fe = document.getElementById('rptFormEl');
                    if (fe) fe.innerHTML =
                        '<div style="text-align:center;padding:2.5rem 1rem;">' +
                        '<span class="material-symbols-outlined" style="font-size:3rem;color:' + color + ';display:block;margin-bottom:.6rem">' + icon + '</span>' +
                        '<div style="font-size:1.05rem;font-weight:700;color:' + color + ';margin-bottom:.3rem">' + label + '</div>' +
                        '<div style="font-size:.85rem;color:#666;">' + sub + '</div>' +
                        '</div>';
                    fetchDates();
                    setTimeout(function () { setState('cal'); }, 1800);
                } else {
                    var fe2 = document.getElementById('rptFormEl');
                    var errBox = fe2 && fe2.querySelector('.rpt-form-error');
                    if (!errBox) {
                        errBox = document.createElement('div');
                        errBox.className = 'rpt-form-error';
                        errBox.style.cssText = 'color:#c0392b;font-size:.85rem;padding:.5rem .75rem;background:#fdecea;border-radius:6px;margin-bottom:.75rem;';
                        if (fe2) fe2.insertBefore(errBox, fe2.firstChild);
                    }
                    errBox.textContent = d.error || 'Failed to save report.';
                }
            })
            .catch(function () {
                var fe3 = document.getElementById('rptFormEl');
                var errBox2 = document.createElement('div');
                errBox2.style.cssText = 'color:#c0392b;font-size:.85rem;padding:.5rem .75rem;background:#fdecea;border-radius:6px;margin-bottom:.75rem;';
                errBox2.textContent = 'Network error. Please try again.';
                if (fe3) fe3.insertBefore(errBox2, fe3.firstChild);
            });
    };

    // Helper: HTML-escape a string
    function h(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str || '')));
        return div.innerHTML;
    }

    function pad(n) { return String(n).padStart(2, '0'); }

    // ── Summary (Weekly / Overall) ────────────────────────────────────────
    window.showProgressSummary = function (type) {
        _summaryType = type;
        if (_summaryData) { _renderSummary(); return; }
        fetch(_base + '/projects/' + _pid + '/progress-summary', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) { _summaryData = d; _renderSummary(); })
            .catch(function () {
                document.getElementById('rptSummaryEl').innerHTML =
                    '<div class="rpt-sum-empty">Unable to load summary data.</div>';
                setState('summary');
            });
    };

    function pzOpenLightbox(urls, startIdx) {
        if (!urls || !urls.length) return;
        var idx = startIdx || 0;
        var lb  = document.getElementById('pzLightboxOverlay');
        if (!lb) {
            lb = document.createElement('div');
            lb.id = 'pzLightboxOverlay';
            lb.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.88);z-index:9999;display:flex;align-items:center;justify-content:center;';
            lb.innerHTML =
                '<button id="pzLbPrev" style="position:absolute;left:1rem;font-size:2.5rem;color:#fff;background:none;border:none;cursor:pointer;padding:.5rem;">&#8249;</button>' +
                '<img id="pzLbImg" style="max-width:90vw;max-height:86vh;border-radius:6px;box-shadow:0 4px 32px rgba(0,0,0,.6);">' +
                '<button id="pzLbNext" style="position:absolute;right:1rem;font-size:2.5rem;color:#fff;background:none;border:none;cursor:pointer;padding:.5rem;">&#8250;</button>' +
                '<button id="pzLbClose" style="position:absolute;top:.8rem;right:1.2rem;font-size:1.8rem;color:#fff;background:none;border:none;cursor:pointer;line-height:1;">&#215;</button>' +
                '<div id="pzLbCount" style="position:absolute;bottom:1rem;left:50%;transform:translateX(-50%);color:#ccc;font-size:.82rem;"></div>';
            document.body.appendChild(lb);
            document.getElementById('pzLbClose').addEventListener('click', function () { lb.style.display = 'none'; });
            lb.addEventListener('click', function (e) { if (e.target === lb) lb.style.display = 'none'; });
            document.getElementById('pzLbPrev').addEventListener('click', function () { show(idx - 1); });
            document.getElementById('pzLbNext').addEventListener('click', function () { show(idx + 1); });
            document.addEventListener('keydown', function (e) {
                if (lb.style.display === 'none') return;
                if (e.key === 'ArrowLeft')  show(idx - 1);
                if (e.key === 'ArrowRight') show(idx + 1);
                if (e.key === 'Escape')     lb.style.display = 'none';
            });
        }
        function show(i) {
            idx = ((i % urls.length) + urls.length) % urls.length;
            document.getElementById('pzLbImg').src = urls[idx];
            document.getElementById('pzLbCount').textContent = (idx + 1) + ' / ' + urls.length;
            document.getElementById('pzLbPrev').style.display = urls.length > 1 ? '' : 'none';
            document.getElementById('pzLbNext').style.display = urls.length > 1 ? '' : 'none';
        }
        lb.style.display = 'flex';
        show(idx);
    }

    function _dateDiffDays(a, b) {
        return Math.round((new Date(b + 'T00:00:00') - new Date(a + 'T00:00:00')) / 86400000);
    }
    function _computePlannedPct(milestones, startDate, endDate, reportDate) {
        var points = [{ date: startDate, pct: 0 }];
        var cumW = 0;
        milestones.forEach(function(m) {
            cumW += m.weight_percent;
            if (m.target_date) points.push({ date: m.target_date, pct: Math.min(100, cumW) });
        });
        if (points[points.length-1].pct < 100 && endDate) points.push({ date: endDate, pct: 100 });
        if (!reportDate || reportDate <= points[0].date) return 0;
        if (reportDate >= points[points.length-1].date) return points[points.length-1].pct;
        for (var i = 0; i < points.length - 1; i++) {
            if (points[i].date <= reportDate && reportDate < points[i+1].date) {
                var span = _dateDiffDays(points[i].date, points[i+1].date);
                var t    = span > 0 ? _dateDiffDays(points[i].date, reportDate) / span : 0;
                return points[i].pct + t * (points[i+1].pct - points[i].pct);
            }
        }
        return 0;
    }
    function _pct(v, dec)  { return typeof v === 'number' ? v.toFixed(dec !== undefined ? dec : 2) + ' %' : '—'; }
    function _vari(v, dec) {
        if (typeof v !== 'number') return '<span class="muted">—</span>';
        var cls = v < -0.005 ? 'neg' : (v > 0.005 ? 'pos' : '');
        return '<span class="' + cls + '">' + (v >= 0 ? '+' : '') + v.toFixed(dec !== undefined ? dec : 2) + ' %</span>';
    }
    function _fmtDate(ds) {
        if (!ds) return '—';
        return new Date(ds + 'T00:00:00').toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' });
    }
    function _fmtWeekDate(ds) {
        if (!ds) return '';
        var dt = new Date(ds + 'T00:00:00');
        var day = String(dt.getDate()).padStart(2, '0');
        var mon = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][dt.getMonth()];
        var yr  = String(dt.getFullYear()).slice(2);
        return day + '-' + mon + '-' + yr;
    }

    function _mkDp(id, val) {
        var disp = val ? val.slice(8,10) + '/' + val.slice(5,7) + '/' + val.slice(0,4) : '';
        return '<div class="date-picker" id="' + id + 'Picker" style="flex-shrink:0;">' +
               '<input type="text" class="date-display" placeholder="DD/MM/YYYY"' + (disp ? ' value="' + disp + '"' : '') + ' readonly style="width:105px;padding:.28rem .4rem;font-size:.8rem;">' +
               '<input type="hidden" id="' + id + '" value="' + (val || '') + '">' +
               '<button type="button" class="date-toggle" style="padding:.28rem .4rem;"><span class="material-symbols-outlined" style="font-size:1rem;">calendar_month</span></button>' +
               '<div class="calendar-popup" aria-hidden="true"></div>' +
               '</div>';
    }

    function _renderSummary() {
        var d = _summaryData || {};
        var html = '';
        var reportDate      = d.reportDate      || new Date().toISOString().slice(0,10);
        var overallProgress = typeof d.overallProgress === 'number' ? d.overallProgress : 0;
        var todayFmt = new Date().toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' });

        if (_summaryType === 'weekly') {
            var targets = d.weeklyTargets || [];

            // If no saved weekly_targets but project has start/end dates,
            // generate week slots with S-curve targets (mirrors mobile behavior)
            var scurveFallback = false;
            var hasValidDates  = d.startDate && d.endDate && d.startDate < d.endDate;
            if (!targets.length && hasValidDates) {
                var _s  = new Date(d.startDate + 'T00:00:00');
                var _e  = new Date(d.endDate   + 'T00:00:00');
                var _n  = Math.max(1, Math.ceil((_e - _s) / (7 * 24 * 3600 * 1000)));
                for (var _i = 0; _i < _n; _i++) {
                    var _ws = new Date(_s.getTime() + _i * 7 * 24 * 3600 * 1000);
                    targets.push({
                        week:           _i + 1,
                        week_start:     _ws.toISOString().slice(0, 10),
                        target_percent: Math.round((1 - Math.cos(Math.PI * (_i + 1) / _n)) / 2 * 10000) / 100,
                        actual_percent: null,
                    });
                }
                scurveFallback = true;
            }

            // Show only weeks that have already started (mirror mobile behaviour)
            var _today = new Date(); _today.setHours(0, 0, 0, 0);
            var allTargets = targets.slice();
            targets = targets.filter(function(wt) {
                if (!wt.week_start) return true;
                return new Date(wt.week_start + 'T00:00:00') <= _today;
            });

            if (!d.startDate || !d.endDate) {
                html += '<div class="rpt-sum-empty">No project dates set. Add a start and end date to the project to generate the weekly target table.</div>';
            } else if (d.startDate >= d.endDate) {
                html += '<div class="rpt-sum-empty">Project end date is before the start date. Please correct the project schedule.</div>';
            } else if (!targets.length && allTargets.length) {
                html += '<div class="rpt-sum-empty">Project has not started yet — weekly summary will appear once the start date is reached (' + d.startDate + ').</div>';
            } else if (!targets.length) {
                html += '<div class="rpt-sum-empty">No weekly data available yet.</div>';
            } else {
                var wkDefaultFrom = d.startDate || '';
                var wkDefaultTo   = d.endDate   || '';

                html += '<div class="rpt-sum-overall-hdr">';
                html += '<div class="rpt-sum-overall-title">Cumulative Weekly Progress Target Summary</div>';
                html += '<div class="rpt-sum-overall-sub" id="rptWkHdrDateRange">' + _fmtDate(wkDefaultFrom) + ' → ' + _fmtDate(wkDefaultTo) + '</div>';
                html += '<div class="rpt-sum-overall-badge">' + targets.length + ' week' + (targets.length !== 1 ? 's' : '') + ' tracked</div>';
                html += '</div>';

                if (scurveFallback) {
                    html += '<div style="font-size:.76rem;color:#8C701C;background:#fffbee;border:1px solid #e8d9a8;border-radius:6px;padding:.4rem .75rem;margin-bottom:.75rem;">Targets computed via S-curve (no saved schedule). Save a milestone schedule to use custom targets.</div>';
                }

                html += '<div class="rpt-no-print" style="display:flex;align-items:center;gap:.5rem;margin-bottom:.8rem;flex-wrap:nowrap;">' +
                        '<label style="font-size:.78rem;font-weight:600;color:#5a4020;white-space:nowrap;">From</label>' +
                        _mkDp('rptWkFrom', wkDefaultFrom) +
                        '<span style="color:#8a6420;font-weight:700;">&#8594;</span>' +
                        '<label style="font-size:.78rem;font-weight:600;color:#5a4020;white-space:nowrap;">To</label>' +
                        _mkDp('rptWkTo', wkDefaultTo) +
                        '<button onclick="applyWkDateFilter()" style="padding:.3rem .8rem;border:none;border-radius:6px;background:#8a6420;color:#fff;font-size:.82rem;font-weight:600;cursor:pointer;">Apply</button>' +
                        '<button onclick="clearWkDateFilter()" style="padding:.3rem .8rem;border:1px solid #d5c68a;border-radius:6px;background:#fdf9f0;color:#5a4020;font-size:.82rem;font-weight:600;cursor:pointer;">Clear</button>' +
                        '<span id="rptWkFilterMsg" style="font-size:.76rem;margin-left:.1rem;white-space:nowrap;"></span>' +
                        '</div>';

                html += '<table class="rpt-sum-table" id="rptWkTable">';
                html += '<thead><tr>' +
                        '<th>Week No.</th>' +
                        '<th class="num">Running Cumulative %</th>' +
                        '<th class="num">Cumulative Target %</th>' +
                        '<th class="num">Variance (+/–)</th>' +
                        '</tr></thead><tbody>';

                targets.forEach(function(wt, i) {
                    var weekNum   = (wt.week !== undefined && wt.week !== null) ? wt.week : (i + 1);
                    var weekDate  = wt.week_start ? _fmtWeekDate(wt.week_start) : '';
                    var weekLabel = 'Week ' + weekNum + (weekDate ? ' (' + weekDate + ')' : '');

                    var hasActual = wt.actual_percent !== null && wt.actual_percent !== undefined;
                    var actualPct = hasActual ? Number(wt.actual_percent) : null;
                    var targetPct = Number(wt.target_percent || 0);
                    var variance  = hasActual ? (actualPct - targetPct) : null;

                    html += '<tr data-week-start="' + (wt.week_start || '') + '">';
                    html += '<td>' + h(weekLabel) + '</td>';
                    html += '<td class="num">' + (actualPct !== null ? _pct(actualPct, 2) : '') + '</td>';
                    html += '<td class="num">' + _pct(targetPct, 2) + '</td>';
                    html += '<td class="num">' + (variance !== null ? _vari(variance, 2) : '') + '</td>';
                    html += '</tr>';
                });

                html += '</tbody></table>';
            }

        } else {
            var milestones  = d.milestones || [];
            var totalMs     = milestones.length;
            var scheduledMs = milestones.filter(function(m){ return m.target_date; }).length;
            var plannedPct  = d.startDate ? _computePlannedPct(milestones, d.startDate, d.endDate, reportDate) : null;
            var variance    = plannedPct !== null ? overallProgress - plannedPct : null;

            html += '<div class="rpt-sum-overall-hdr">';
            html += '<div class="rpt-sum-overall-title">General Progress Accomplishment %</div>';
            html += '<div class="rpt-sum-overall-sub">As of ' + todayFmt + '</div>';
            if (totalMs > 0) html += '<div class="rpt-sum-overall-badge">' + scheduledMs + ' of ' + totalMs + ' milestone' + (totalMs !== 1 ? 's' : '') + ' scheduled</div>';
            html += '</div>';

            html += '<table class="rpt-sum-table" style="margin-bottom:.8rem"><thead><tr style="background:#b8d4e8">' +
                    '<th>ITEM</th>' +
                    '<th class="num">PLANNED %<br><small style="font-weight:400;text-transform:none">(scheduled scope)</small></th>' +
                    '<th class="num">ACTUAL %</th><th class="num">VARIANCE (+/–)</th>' +
                    '</tr></thead><tbody><tr>' +
                    '<td style="font-weight:700">PROGRESS TO DATE</td>' +
                    '<td class="num">' + (plannedPct !== null ? _pct(plannedPct, 1) : '—') + '</td>' +
                    '<td class="num">' + _pct(overallProgress, 2) + '</td>' +
                    '<td class="num">' + (variance !== null ? _vari(variance, 2) : '—') + '</td>' +
                    '</tr></tbody></table>';

            html += '<div class="rpt-sum-ms-heading">MILESTONE SUMMARY</div>';

            // Date range filter row
            html += '<div class="rpt-no-print" style="display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;flex-wrap:nowrap;">' +
                    '<label style="font-size:.78rem;font-weight:600;color:#5a4020;white-space:nowrap;">From</label>' +
                    _mkDp('rptMsFrom', '') +
                    '<span style="color:#8a6420;font-weight:700;">&#8594;</span>' +
                    '<label style="font-size:.78rem;font-weight:600;color:#5a4020;white-space:nowrap;">To</label>' +
                    _mkDp('rptMsTo', '') +
                    '<button onclick="applyMsDateFilter()" style="padding:.3rem .8rem;border:none;border-radius:6px;background:#8a6420;color:#fff;font-size:.82rem;font-weight:600;cursor:pointer;">Apply</button>' +
                    '<button onclick="clearMsDateFilter()" style="padding:.3rem .8rem;border:1px solid #d5c68a;border-radius:6px;background:#fdf9f0;color:#5a4020;font-size:.82rem;font-weight:600;cursor:pointer;">Clear</button>' +
                    '<span id="rptMsFilterMsg" style="font-size:.76rem;margin-left:.1rem;white-space:nowrap;"></span>' +
                    '</div>';

            if (!milestones.length) {
                html += '<div class="rpt-sum-empty">No milestones defined.</div>';
            } else {
                html += '<table class="rpt-sum-table" id="rptMsTable"><thead><tr style="background:#b8d4e8">' +
                        '<th>MILESTONE</th><th>TARGET DATE</th><th>DATE OF COMPLETION</th>' +
                        '<th class="num">PLANNED %</th><th class="num">ACTUAL %</th>' +
                        '<th class="num">VARIANCE</th><th>STATUS</th>' +
                        '</tr></thead><tbody>';
                milestones.forEach(function(m) {
                    var stLow  = (m.status || '').toLowerCase();
                    var isDone = stLow === 'completed';
                    var isIP   = stLow === 'in progress';
                    var stLbl  = isDone ? 'Completed' : (isIP ? 'In Progress' : 'Not Started');
                    var stCls  = isDone ? 'rpt-sum-status-done' : (isIP ? 'rpt-sum-status-inprogress' : 'rpt-sum-status-pending');
                    var hasTD     = !!m.target_date;
                    var tdPassed  = hasTD && m.target_date <= reportDate;
                    var actualPct = m.completion_percent * m.weight_percent / 100;
                    var plannedR  = hasTD ? m.weight_percent : null;
                    var varRow    = (tdPassed && plannedR !== null) ? actualPct - plannedR : null;
                    var completionDate = m.actual_completion_date ? _fmtDate(m.actual_completion_date) : '<span class="muted">—</span>';
                    html += '<tr data-target-date="' + (m.target_date || '') + '"><td>' + h(m.milestone_name) + '</td><td>' + _fmtDate(m.target_date) + '</td>' +
                            '<td>' + completionDate + '</td>' +
                            '<td class="num">' + (plannedR !== null ? _pct(plannedR, 1) : '—') + '</td>' +
                            '<td class="num">' + _pct(actualPct, 1) + '</td>' +
                            '<td class="num">' + (varRow !== null ? _vari(varRow, 2) : '<span class="muted">—</span>') + '</td>' +
                            '<td><span class="rpt-sum-status ' + stCls + '">' + stLbl + '</span></td></tr>';
                });
                html += '</tbody></table>';
            }
        }

        document.getElementById('rptSummaryEl').innerHTML = html;
        ['rptWkFromPicker','rptWkToPicker','rptMsFromPicker','rptMsToPicker'].forEach(function(pid) {
            var dp = document.getElementById(pid);
            if (dp && window.pzInitDatePicker) window.pzInitDatePicker(dp, {
                getMin: function() { return d.startDate || ''; },
                getMax: function() { return d.endDate   || ''; }
            });
        });
        setState('summary');
        document.getElementById('rptModalTitle').textContent =
            (_summaryType === 'weekly' ? 'Weekly Summary' : 'Overall Progress') + ' — ' + _pname;
    }

    function _rptSetMsg(elId, text, isError) {
        var el = document.getElementById(elId);
        if (!el) return;
        el.textContent  = text;
        el.style.color      = isError ? '#c0392b' : '#2e7d32';
        el.style.fontWeight = '600';
    }

    function _rptEmptyRow(colspan, msg) {
        var tr = document.createElement('tr');
        tr.id  = 'rptEmptyRow';
        var td = document.createElement('td');
        td.colSpan      = colspan;
        td.textContent  = msg;
        td.style.cssText = 'text-align:center;color:#999;font-style:italic;padding:12px;';
        tr.appendChild(td);
        return tr;
    }

    window.applyMsDateFilter = function () {
        var from  = (document.getElementById('rptMsFrom') || {}).value || '';
        var to    = (document.getElementById('rptMsTo')   || {}).value || '';
        var table = document.getElementById('rptMsTable');
        if (!table) return;

        if (!from && !to) { _rptSetMsg('rptMsFilterMsg', 'Select at least one date.', true); return; }
        if (from && to && from > to) { _rptSetMsg('rptMsFilterMsg', 'From must be before To.', true); return; }

        var tbody = table.querySelector('tbody');
        var old   = tbody.querySelector('#rptEmptyRow');
        if (old) old.remove();

        var total = 0, visible = 0;
        table.querySelectorAll('tbody tr').forEach(function (row) {
            var td   = row.dataset.targetDate || '';
            var show = true;
            if (from && td && td < from) show = false;
            if (to   && td && td > to)   show = false;
            if (!td && (from || to))     show = false;
            row.style.display = show ? '' : 'none';
            total++;
            if (show) visible++;
        });

        if (visible === 0) {
            tbody.appendChild(_rptEmptyRow(3, 'No records match the selected date range.'));
            _rptSetMsg('rptMsFilterMsg', 'No results.', true);
        } else {
            _rptSetMsg('rptMsFilterMsg', 'Showing ' + visible + ' of ' + total + ' rows.', false);
        }
    };

    window.clearMsDateFilter = function () {
        ['rptMsFromPicker','rptMsToPicker'].forEach(function(pid) {
            var dp = document.getElementById(pid);
            if (!dp) return;
            var h = dp.querySelector('input[type="hidden"]');
            var d = dp.querySelector('.date-display');
            if (h) h.value = '';
            if (d) d.value = '';
        });
        var table = document.getElementById('rptMsTable');
        if (table) {
            var old = table.querySelector('#rptEmptyRow');
            if (old) old.remove();
            table.querySelectorAll('tbody tr').forEach(function (r) { r.style.display = ''; });
        }
        _rptSetMsg('rptMsFilterMsg', '', false);
    };

    window.applyWkDateFilter = function () {
        var from  = (document.getElementById('rptWkFrom') || {}).value || '';
        var to    = (document.getElementById('rptWkTo')   || {}).value || '';
        var table = document.getElementById('rptWkTable');
        if (!table) return;

        if (!from && !to) { _rptSetMsg('rptWkFilterMsg', 'Select at least one date.', true); return; }
        if (from && to && from > to) { _rptSetMsg('rptWkFilterMsg', 'From must be before To.', true); return; }

        var tbody = table.querySelector('tbody');
        var old   = tbody.querySelector('#rptEmptyRow');
        if (old) old.remove();

        var total = 0, visible = 0;
        table.querySelectorAll('tbody tr').forEach(function (row) {
            var ws   = row.dataset.weekStart || '';
            var show = true;
            if (from && ws && ws < from) show = false;
            if (to   && ws && ws > to)   show = false;
            if (!ws && (from || to))     show = false;
            row.style.display = show ? '' : 'none';
            total++;
            if (show) visible++;
        });

        var fmt = function(s) { return s ? new Date(s + 'T00:00:00').toLocaleDateString('en-US', {month:'short', day:'2-digit', year:'numeric'}) : '—'; };
        var hdr = document.getElementById('rptWkHdrDateRange');
        if (hdr) hdr.textContent = fmt(from) + ' → ' + fmt(to);

        if (visible === 0) {
            tbody.appendChild(_rptEmptyRow(4, 'No records match the selected date range.'));
            _rptSetMsg('rptWkFilterMsg', 'No results.', true);
        } else {
            _rptSetMsg('rptWkFilterMsg', 'Showing ' + visible + ' of ' + total + ' rows.', false);
        }
    };

    window.clearWkDateFilter = function () {
        ['rptWkFromPicker','rptWkToPicker'].forEach(function(pid) {
            var dp = document.getElementById(pid);
            if (!dp) return;
            var h = dp.querySelector('input[type="hidden"]');
            var d = dp.querySelector('.date-display');
            if (h) h.value = '';
            if (d) d.value = '';
        });
        var table = document.getElementById('rptWkTable');
        if (table) {
            var old = table.querySelector('#rptEmptyRow');
            if (old) old.remove();
            table.querySelectorAll('tbody tr').forEach(function (r) { r.style.display = ''; });
        }
        _rptSetMsg('rptWkFilterMsg', '', false);
    };

    // Close on overlay click
    document.addEventListener('DOMContentLoaded', function () {
        var overlay = document.getElementById('reportModal');
        if (overlay) overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeReportModal();
        });
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
<script src="<?= asset_url('js/Sortable.min.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var dragging        = false;
    var dragY           = 0;
    var rAfId           = null;
    var scrollEl        = document.scrollingElement || document.documentElement;
    var pendingTransfer = null;

    // ── Toast helper ─────────────────────────────────────────────────
    function showToast(msg) {
        var toast = document.getElementById('dndToast');
        if (!toast) return;
        toast.textContent = msg;
        toast.classList.add('show');
        clearTimeout(toast._hideTimer);
        toast._hideTimer = setTimeout(function () { toast.classList.remove('show'); }, 3500);
    }

    // ── Transfer confirm modal handlers ──────────────────────────────
    var overlay    = document.getElementById('dndConfirmOverlay');
    var cancelBtn  = document.getElementById('dndCancelBtn');
    var confirmBtn = document.getElementById('dndConfirmBtn');

    function closeModal() { overlay.classList.remove('open'); }

    cancelBtn && cancelBtn.addEventListener('click', function () {
        if (pendingTransfer) {
            var t = pendingTransfer;
            t.fromZone.insertBefore(t.pill, t.fromZone.children[t.oldIndex] || null);
            initResGroupPager(t.fromZone.closest('.db-res-group'));
        }
        pendingTransfer = null;
        closeModal();
    });

    overlay && overlay.addEventListener('click', function (e) {
        if (e.target === overlay) cancelBtn.click();
    });

    confirmBtn && confirmBtn.addEventListener('click', function () {
        if (!pendingTransfer) return;
        var t = pendingTransfer;


        pendingTransfer = null;

        // 1. Close modal immediately — no waiting for the network call
        closeModal();

        // 2. Revert pill back to source zone
        t.fromZone.insertBefore(t.pill, t.fromZone.children[t.oldIndex] || null);
        initResGroupPager(t.fromZone.closest('.db-res-group'));

        // 3. Optimistically mark as pending (gray, locked from drag)
        t.pill.classList.add('db-pill--pending');
        t.pill.dataset.pending = '1';

        // 4. Submit transfer request
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
                manpower_type:            t.manpowerType || null,
            }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                if (data.id) t.pill.dataset.reqId = data.id;
                showToast('Transfer request submitted — awaiting approval.');
            } else {
                // Server rejected — roll back pending state
                t.pill.classList.remove('db-pill--pending');
                delete t.pill.dataset.pending;
                showToast('Could not submit transfer request.');
            }
        })
        .catch(function () {
            t.pill.classList.remove('db-pill--pending');
            delete t.pill.dataset.pending;
            showToast('Network error — transfer request not submitted.');
        })
        .finally(function () { confirmBtn.disabled = false; });
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

    // Pending pill resolution is now handled by the Firestore onSnapshot listener
    // in the firebase-scripts section below — no polling needed.

    // ── Resource group pager (10 pills per page) ─────────────────────
    var DB_RES_PAGE = 10;

    function initResGroupPager(groupEl) {
        if (!groupEl) return;
        var zone = groupEl.querySelector('.db-res-drop-zone');
        if (!zone) return;

        var old = groupEl.querySelector('.db-res-pager');
        if (old) old.remove();

        var pills = Array.from(zone.querySelectorAll(':scope > .db-pill'));
        pills.forEach(function(p) { p.style.display = ''; });

        if (pills.length <= DB_RES_PAGE) return;

        var pages = Math.ceil(pills.length / DB_RES_PAGE);
        var cur   = 0;

        function renderPage() {
            var all = Array.from(zone.querySelectorAll(':scope > .db-pill'));
            var pg  = Math.ceil(all.length / DB_RES_PAGE);
            all.forEach(function(p, i) {
                p.style.display = (i >= cur * DB_RES_PAGE && i < (cur + 1) * DB_RES_PAGE) ? '' : 'none';
            });
            lbl.textContent  = (cur + 1) + ' / ' + pg;
            prevBtn.disabled = cur === 0;
            nextBtn.disabled = cur >= pg - 1;
        }

        var nav     = document.createElement('div');
        nav.className = 'db-res-pager';

        var prevBtn = document.createElement('button');
        prevBtn.type = 'button'; prevBtn.className = 'db-res-pager-btn';
        prevBtn.innerHTML = '<span class="material-symbols-outlined">chevron_left</span>';

        var lbl = document.createElement('span');
        lbl.className = 'db-res-pager-label';

        var nextBtn = document.createElement('button');
        nextBtn.type = 'button'; nextBtn.className = 'db-res-pager-btn';
        nextBtn.innerHTML = '<span class="material-symbols-outlined">chevron_right</span>';

        prevBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (cur > 0) { cur--; renderPage(); }
        });
        nextBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            var pg = Math.ceil(zone.querySelectorAll(':scope > .db-pill').length / DB_RES_PAGE);
            if (cur < pg - 1) { cur++; renderPage(); }
        });

        nav.appendChild(prevBtn);
        nav.appendChild(lbl);
        nav.appendChild(nextBtn);

        var body = groupEl.querySelector('.db-res-group-body');
        if (body) body.appendChild(nav);

        renderPage();
    }

    document.querySelectorAll('.db-res-group').forEach(initResGroupPager);

    ['equipment', 'tools', 'manpower'].forEach(function (cat) {
        var zones = document.querySelectorAll('.db-res-drop-zone[data-cat="' + cat + '"]');

        zones.forEach(function (zone) {
            new Sortable(zone, {
                group:           { name: 'res-' + cat, pull: true, put: ['res-' + cat] },
                handle:          '.db-pill-icons',
                draggable:       '.db-pill',
                filter:          '.db-empty, .db-pill--pending',
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
                        // Temporarily show all pills so Sortable can place drops correctly
                        g.querySelectorAll('.db-res-drop-zone > .db-pill').forEach(function(p) {
                            p.style.display = '';
                        });
                    });
                    zones.forEach(function (z) {
                        z.classList.add(z === zone ? 'dnd-active' : 'dnd-drop-zone');
                    });
                    rAfId = requestAnimationFrame(edgeScrollLoop);
                },

                onEnd: function (evt) {
                    dragging = false;
                    if (rAfId) { cancelAnimationFrame(rAfId); rAfId = null; }
                    document.documentElement.style.scrollBehavior = '';
                    zones.forEach(function (z) {
                        z.classList.remove('dnd-active', 'dnd-drop-zone');
                    });
                    // Re-apply pagers after drag (pills may have moved between zones)
                    [evt.from, evt.to].forEach(function(z) {
                        if (z) initResGroupPager(z.closest('.db-res-group'));
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

// ── ML Blockers carousel ─────────────────────────────────────────────
(function () {
    var currentSlide = 0;
    var totalSlides  = <?= count($slidePages ?? []) ?>;

    window.moveMlSlide = function (dir) {
        currentSlide = (currentSlide + dir + totalSlides) % totalSlides;
        updateCarousel();
    };
    window.goToMlSlide = function (idx) {
        currentSlide = idx;
        updateCarousel();
    };
    function updateCarousel() {
        if (totalSlides <= 1) return;
        var track = document.getElementById('mlCarouselTrack');
        if (track) track.style.transform = 'translateX(-' + (currentSlide * 100) + '%)';
        document.querySelectorAll('.db-dot').forEach(function (d, i) {
            d.classList.toggle('active', i === currentSlide);
        });
    }
    if (totalSlides > 1) {
        setInterval(function () { window.moveMlSlide(1); }, 6000);
    }
}());
</script>

<script>
document.addEventListener('click', function(e) {
    var chip = e.target.closest('.mp-type-chip');
    if (!chip) return;
    var container = chip.closest('.mp-type-chips');
    if (!container) return;
    container.querySelectorAll('.mp-type-chip').forEach(function(c){c.classList.remove('selected');});
    chip.classList.add('selected');
    var hidden = document.getElementById(container.dataset.target);
    if (hidden) hidden.value = chip.dataset.value;
});
</script>

<?= $this->endSection() ?>

<?php $this->section('firebase-scripts') ?>
<script>
(function () {
  // Block the global page-aware listener — dashboard owns its own listeners
  window._pzPageListeners = true;

  var CAT_ICONS = { equipment: 'construction', tools: 'handyman', manpower: 'engineering' };

  function buildPill(res, cat) {
    var cls  = 'db-pill' + (res.transit ? ' db-pill--transit' : '');
    var name = (res.name  || '').toUpperCase();
    var icon = CAT_ICONS[cat] || 'category';
    return '<div class="' + cls + '"'
         + ' data-res-name="' + name + '"'
         + ' data-res-id="'   + (res.ref_id  || '') + '"'
         + ' data-unit-id="'  + (res.unit_id || '') + '">'
         + '<span class="db-pill-icons">'
         + '<span class="material-symbols-outlined">drag_indicator</span>'
         + '<span class="material-symbols-outlined">' + icon + '</span>'
         + '</span>'
         + '<span class="db-pill-name">' + name + '</span>'
         + '</div>';
  }

  function flashCard(card) {
    card.style.transition = 'outline .15s';
    card.style.outline    = '2px solid #3b82f6';
    setTimeout(function () { card.style.outline = ''; }, 1000);
  }

  function pzUpdateCard(docId, data) {
    var card = document.querySelector('.db-project-card[data-pid="' + docId + '"]');
    if (!card) return;

    // ── Progress bar ──────────────────────────────────────────────────────
    var prog = Math.min(100, Math.max(0, parseFloat(data.overall_progress || 0)));
    var fill = card.querySelector('.db-progress-fill');
    var pct  = card.querySelector('.db-progress-pct');
    if (fill) fill.style.width = Math.round(prog) + '%';
    if (pct)  pct.textContent  = Math.round(prog) + '%';

    // ── Resource pills ────────────────────────────────────────────────────
    var resources = Array.isArray(data.resources) ? data.resources : [];
    // Deduplicate by ref_id+unit_id — mobile may write without unit_id while
    // the web writes unit_id:'', causing arrayUnion to keep both entries.
    var _resSeen = {};
    resources = resources.filter(function (r) {
      var k = (r.ref_id || '') + '|' + (r.unit_id || '');
      if (_resSeen[k]) return false;
      _resSeen[k] = true;
      return true;
    });
    var grps = { equipment: [], tools: [], manpower: [] };
    resources.forEach(function (r) {
      var cat = (r.category || '').toLowerCase();
      if (grps[cat]) grps[cat].push(r);
    });

    var changed = false;
    ['equipment', 'tools', 'manpower'].forEach(function (cat) {
      var zone = card.querySelector('.db-res-drop-zone[data-cat="' + cat + '"]');
      if (!zone) return;
      var items = grps[cat];

      // Update accordion count badge
      var grpEl = card.querySelector('.db-res-group[data-cat="' + cat + '"]');
      var cnt   = grpEl ? grpEl.querySelector('.db-res-group-count') : null;
      if (cnt && parseInt(cnt.textContent, 10) !== items.length) {
        cnt.textContent = items.length;
        changed = true;
      }

      // Compare and re-render only if content actually changed
      var newHtml = items.length === 0
        ? '<p class="db-empty" style="font-size:0.8rem;padding:0.25rem 0;margin:0;">None assigned</p>'
        : items.map(function (r) { return buildPill(r, cat); }).join('');

      if (zone.innerHTML.trim() !== newHtml.trim()) {
        zone.innerHTML = newHtml;
        changed = true;
      }
    });

    // Brief blue outline flash to signal this card just updated
    if (changed) flashCard(card);
  }

  var _ready = false;
  function setup(db) {
    if (_ready) return;
    _ready = true;

    // ── Detect Philippine region from address (mirrors PHP detectRegion) ──
    function detectRegion(addr) {
      var a = (addr || '').toLowerCase();
      var map = {
        'LUZON':    ['manila','quezon','caloocan','makati','taguig','pasig','marikina','laguna','cavite','batangas','pampanga','bulacan','rizal','baguio','ilocos','nueva ecija','tarlac','zambales','bataan','aurora','palawan','bicol','albay','camarines','cagayan'],
        'VISAYAS':  ['cebu','iloilo','bacolod','tacloban','dumaguete','ormoc','leyte','samar','bohol','negros','panay','aklan','antique','capiz','biliran'],
        'MINDANAO': ['davao','cagayan de oro','zamboanga','cotabato','butuan','iligan','general santos','bukidnon','misamis','surigao','agusan','maguindanao'],
      };
      for (var region in map) {
        var kws = map[region];
        for (var i = 0; i < kws.length; i++) {
          if (a.indexOf(kws[i]) !== -1) return region;
        }
      }
      return 'LUZON';
    }

    // ── Build a dashboard card for a newly-added project ──────────────────
    function buildDashCard(id, data) {
      var name    = (data.project_name || 'Project').toUpperCase().replace(/</g, '&lt;');
      var prog    = Math.min(100, Math.max(0, parseFloat(data.overall_progress || 0)));
      var cardId  = 'card-' + id.replace(/[^a-zA-Z0-9]/g, '');
      var base    = window.pzBaseUrl || '';

      function fmtDate(v) {
        if (!v) return 'N/A';
        if (v && typeof v.toDate === 'function') v = v.toDate();
        if (v instanceof Date) return v.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }).toUpperCase();
        if (typeof v === 'number') return new Date(v * 1000).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }).toUpperCase();
        return String(v).toUpperCase();
      }

      function resGroup(cat, icon, label) {
        return '<div class="db-res-group open" data-cat="' + cat + '">'
             + '<div class="db-res-group-hdr" onclick="dbToggleGroup(this.parentElement)">'
             + '<span class="material-symbols-outlined db-res-chevron">chevron_right</span>'
             + '<span class="material-symbols-outlined">' + icon + '</span>'
             + label + ' <span class="db-res-group-count">0</span></div>'
             + '<div class="db-res-group-body"><div class="db-res-drop-zone" data-cat="' + cat + '" data-proj="' + cardId + '" data-pid="' + id + '">'
             + '<p class="db-empty" style="font-size:.8rem;padding:.25rem 0;margin:0">None assigned</p>'
             + '</div></div></div>';
      }

      return '<div class="db-project-card pz-row-new" id="' + cardId + '" data-pid="' + id + '" data-pname="' + name + '">'
           + '<a href="' + base + 'projects/show/' + id + '" class="db-project-name-box db-project-name-link">'
           + name + ' <span class="material-symbols-outlined">open_in_new</span></a>'
           + '<div class="db-progress"><div class="db-progress-fill" style="width:' + prog + '%"></div>'
           + '<span class="db-progress-pct">' + Math.round(prog) + '%</span></div>'
           + '<div class="db-dates">'
           + '<div class="db-date-row"><span class="db-date-label">Start Date:</span><span class="db-date-val">' + fmtDate(data.start_date) + '</span></div>'
           + '<div class="db-date-row"><span class="db-date-label">End Date:</span><span class="db-date-val">' + fmtDate(data.end_date) + '</span></div>'
           + '</div>'
           + '<div class="db-res-accordion">'
           + resGroup('equipment', 'construction', 'Equipment')
           + resGroup('tools',     'handyman',     'Tools')
           + resGroup('manpower',  'engineering',  'Manpower')
           + '</div>'
           + '<div class="db-card-footer">'
           + '<a href="' + base + 'messages/project/' + id + '" class="db-icon-btn" title="Messages" style="text-decoration:none"><span class="material-symbols-outlined db-icon-chat">chat_bubble</span></a>'
           + '<button class="db-icon-btn db-icon-emergency" data-pid="' + id + '" data-pname="' + name + '" onclick="openReportModal(\'emergency\',this.dataset.pid,this.dataset.pname)" title="Emergency Reports"><span class="material-symbols-outlined">warning</span></button>'
           + '<button class="db-icon-btn db-icon-report" data-pid="' + id + '" data-pname="' + name + '" onclick="openReportModal(\'daily\',this.dataset.pid,this.dataset.pname)" title="Progress Reports"><span class="material-symbols-outlined">description</span></button>'
           + '<button class="db-btn-locate" data-lat="" data-lng="" data-name="' + name + '" title="Focus on map">Locate Site</button>'
           + '</div>'
           + '</div>';
    }

    // ── Ensure a region section exists, return its card row div ──────────
    function ensureRegionRow(region) {
      var existing = document.querySelector('.db-monitor-region[data-region-group="' + region + '"]');
      if (existing) return existing.querySelector('.row');
      var container = document.querySelector('.db-monitor-region')?.parentElement
                   || document.getElementById('dbNoResults')?.parentElement;
      if (!container) return null;
      var div = document.createElement('div');
      div.className = 'db-monitor-region';
      div.setAttribute('data-region-group', region);
      div.innerHTML = '<h3 class="db-region-title">' + region + '</h3><div class="row g-3 mb-2"></div>';
      container.insertBefore(div, document.getElementById('dbNoResults'));
      return div.querySelector('.row');
    }

    var _projInit = false;
    db.collection('project').onSnapshot(function (snap) {
      if (!_projInit) { _projInit = true; return; }
      snap.docChanges().forEach(function (chg) {
        if (chg.type === 'modified') { pzUpdateCard(chg.doc.id, chg.doc.data()); return; }

        if (chg.type === 'added') {
          if (document.querySelector('.db-project-card[data-pid="' + chg.doc.id + '"]')) return;
          var data    = chg.doc.data();
          var addr    = (data.location && data.location.address) ? data.location.address : '';
          var region  = detectRegion(addr);
          var row     = ensureRegionRow(region);
          if (!row) return;
          var col = document.createElement('div');
          col.className   = 'col-xl-6 col-12 db-monitor-col';
          col.dataset.region = region;
          col.dataset.client = data.client_name || '';
          col.dataset.type   = data.project_type || '';
          var rawS = (data.status || '').toUpperCase();
          col.dataset.status = (rawS === 'PAST DEADLINE') ? 'PAST_DEADLINE' : (rawS === 'DELAYED' ? 'DELAYED' : (rawS === 'DONE' || rawS === 'COMPLETED' ? 'DONE' : 'ONGOING'));
          col.dataset.delay  = '0';
          col.innerHTML = buildDashCard(chg.doc.id, data);
          row.appendChild(col);
          // Hide the "no results" message if visible
          var noRes = document.getElementById('dbNoResults');
          if (noRes) noRes.style.display = 'none';
          return;
        }

        if (chg.type === 'removed') {
          var card = document.querySelector('.db-project-card[data-pid="' + chg.doc.id + '"]');
          if (!card) return;
          card.style.transition = 'opacity .5s, transform .5s';
          card.style.opacity    = '0';
          card.style.transform  = 'scale(.97)';
          setTimeout(function () {
            var col = card.closest('.db-monitor-col');
            if (col) col.remove(); else card.remove();
          }, 500);
        }
      });
    }, function (err) {
      console.warn('[PZ] Dashboard project listener:', err.message);
    });

    // Refresh resource pills for one project card by fetching fresh server data.
    function pzRefreshCard(pid) {
      if (!pid) return;
      var card = document.querySelector('.db-project-card[data-pid="' + pid + '"]');
      if (!card) return;
      var base = window.pzBaseUrl || '';
      fetch(base + '/dashboard/card-refresh/' + encodeURIComponent(pid), { credentials: 'same-origin' })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
          if (!data) return;
          var changed = false;
          ['equipment', 'tools', 'manpower'].forEach(function (cat) {
            var zone  = card.querySelector('.db-res-drop-zone[data-cat="' + cat + '"]');
            var grpEl = card.querySelector('.db-res-group[data-cat="' + cat + '"]');
            var cnt   = grpEl ? grpEl.querySelector('.db-res-group-count') : null;
            var items = data[cat] || [];
            if (cnt) cnt.textContent = items.length;
            var icon = cat === 'equipment' ? 'construction' : cat === 'tools' ? 'handyman' : 'engineering';
            var newHtml = items.length === 0
              ? '<p class="db-empty" style="font-size:0.8rem;padding:0.25rem 0;margin:0;">None assigned</p>'
              : items.map(function (r) { return buildPill(r, cat); }).join('');
            if (zone && zone.innerHTML.trim() !== newHtml.trim()) {
              zone.innerHTML = newHtml;
              changed = true;
            }
          });
          if (changed) flashCard(card);
        })
        .catch(function (e) { console.warn('[PZ] cardRefresh error for ' + pid, e); });
    }

    var _reqInit = false;
    db.collection('resource_requests').onSnapshot(function (snap) {
      if (!_reqInit) { _reqInit = true; return; }
      snap.docChanges().forEach(function (chg) {
        if (chg.type !== 'modified') return;
        var d  = chg.doc.data();
        var s  = d.status || '';

        // Update inline badge (e.g. on Requests page if open)
        var el = document.querySelector('[data-req-id="' + chg.doc.id + '"][data-req-status]');
        if (el && window.pzBadgeClass) {
          el.innerHTML = '<span class="pz-badge ' + window.pzBadgeClass(s) + '">' + s.toUpperCase() + '</span>';
        }

        // When a transfer is resolved, refresh both affected project cards immediately
        var resolved = ['completed', 'rejected', 'cancelled'];
        if (resolved.indexOf(s.toLowerCase()) !== -1) {
          var srcPid  = d.source_project_id      || d.from_project_id || '';
          var dstPid  = d.destination_project_id || d.to_project_id   || '';
          if (srcPid) pzRefreshCard(srcPid);
          if (dstPid && dstPid !== srcPid) pzRefreshCard(dstPid);
        }
      });
    }, function (err) {
      console.warn('[PZ] Dashboard request listener:', err.message);
    });
  }

  var _dashSetupStarted = false;
  function tryStartDashboardListeners() {
    if (_dashSetupStarted || !window.pzFirebaseDb) return;
    _dashSetupStarted = true;
    setup(window.pzFirebaseDb);
  }
  firebase.auth().onAuthStateChanged(function (user) {
    if (user) tryStartDashboardListeners();
  });
  // Safety net: start anyway if the auth-state callback is slow/never fires,
  // mirroring the notification bell's fallback below. setup() itself is a
  // no-op if listeners are already attached.
  setTimeout(tryStartDashboardListeners, 4000);
}());
</script>

<!-- ── Resource Request Modal (PM project card REQUEST button) ────────── -->
<script>
(function () {
    var CATALOG = <?= (function() use ($allResources) {
        $seen = [];
        $out  = [];
        foreach (($allResources ?? []) as $r) {
            $cat  = strtolower(trim((string)($r['category'] ?? '')));
            $name = strtoupper(trim((string)($r['name'] ?? '')));
            if ($name === '' || !in_array($cat, ['equipment', 'tools'])) continue;
            $key  = $cat . '|' . $name;
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $out[] = ['id' => $r['id'] ?? '', 'name' => $name, 'category' => $cat];
        }
        usort($out, fn($a, $b) => strcmp($a['name'], $b['name']));
        return json_encode(array_values($out));
    })() ?>;

    var ALL_PROJECTS = <?= json_encode(array_values(array_map(function($p) {
        return [
            'id'   => $p['id'] ?? '',
            'name' => strtoupper(trim($p['project_name'] ?? '')),
            'lat'  => (float)($p['_lat'] ?? 0),
            'lng'  => (float)($p['_lng'] ?? 0),
        ];
    }, $activeProjects ?? []))) ?>;

    var overlay            = document.getElementById('reqModalOverlay');
    var categoryEl         = document.getElementById('reqCategory');
    var modelGroup         = document.getElementById('reqModelGroup');
    var modelEl            = document.getElementById('reqModel');
    var manpowerTypeGroup  = document.getElementById('reqManpowerTypeGroup');
    var manpowerTypeEl     = document.getElementById('reqManpowerType');
    var qtyEl              = document.getElementById('reqQty');
    var submitBtn          = document.getElementById('reqSubmitBtn');
    var cancelBtn          = document.getElementById('reqCancelBtn');
    var errBox             = document.getElementById('reqModalError');
    var projName           = document.getElementById('reqModalProjName');

    var _pid = '', _pname = '';
    var _csrfName = document.querySelector('meta[name="csrf-name"]').content;
    var _csrfHash = document.querySelector('meta[name="csrf-hash"]').content;

    window.openRequestModal = function (pid, pname) {
        _pid   = pid;
        _pname = pname;
        projName.textContent = pname;
        categoryEl.value = '';
        modelEl.innerHTML = '<option value="">Choose model</option>';
        modelGroup.style.display = 'none';
        manpowerTypeEl.value = '';
        document.querySelectorAll('#reqManpowerTypeChips .mp-type-chip').forEach(function(c){c.classList.remove('selected');});
        manpowerTypeGroup.style.display = 'none';
        qtyEl.value = '1';
        errBox.style.display = 'none';
        submitBtn.disabled = false;
        overlay.style.display = 'flex';
    };

    cancelBtn.addEventListener('click', function () { overlay.style.display = 'none'; });
    overlay.addEventListener('click', function (e) { if (e.target === overlay) overlay.style.display = 'none'; });

    categoryEl.addEventListener('change', function () {
        var cat = this.value.toLowerCase();
        modelEl.innerHTML = '<option value="">Choose model</option>';
        errBox.style.display = 'none';

        if (cat === 'equipment' || cat === 'tools') {
            CATALOG.filter(function (r) { return r.category === cat; })
                   .forEach(function (r) {
                       var opt = document.createElement('option');
                       opt.value       = r.id;
                       opt.textContent = r.name;
                       modelEl.appendChild(opt);
                   });
            modelGroup.style.display = 'block';
            manpowerTypeGroup.style.display = 'none';
            manpowerTypeEl.value = '';
            document.querySelectorAll('#reqManpowerTypeChips .mp-type-chip').forEach(function(c){c.classList.remove('selected');});
        } else if (cat === 'manpower') {
            modelGroup.style.display = 'none';
            manpowerTypeEl.value = '';
            document.querySelectorAll('#reqManpowerTypeChips .mp-type-chip').forEach(function(c){c.classList.remove('selected');});
            manpowerTypeGroup.style.display = 'block';
        } else {
            modelGroup.style.display = 'none';
            manpowerTypeGroup.style.display = 'none';
        }
    });

    submitBtn.addEventListener('click', function () {
        errBox.style.display = 'none';
        var cat = categoryEl.value;
        var qty = parseInt(qtyEl.value, 10) || 1;
        if (!cat) { showErr('Please select a resource category.'); return; }

        var modelId = '', modelName = '', manpowerType = '';
        if (cat.toLowerCase() === 'equipment' || cat.toLowerCase() === 'tools') {
            modelId = modelEl.value;
            if (!modelId) { showErr('Please select a model.'); return; }
            modelName = (modelEl.options[modelEl.selectedIndex] || {}).text || '';
        } else if (cat.toLowerCase() === 'manpower') {
            manpowerType = manpowerTypeEl.value;
            if (!manpowerType) { showErr('Please select a manpower type.'); return; }
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting…';

        fetch('<?= base_url('requests/store') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                [_csrfName]: _csrfHash,
            },
            body: JSON.stringify({
                request_kind:             'manual',
                resource_category:        cat,
                resource_name:            modelName || manpowerType,
                requested_model_id:       modelId   || null,
                requested_model_name:     modelName || null,
                manpower_type:            manpowerType || null,
                destination_project_id:   _pid,
                destination_project_name: _pname,
                requested_quantity:       qty,
            }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                overlay.style.display = 'none';
                showToast('Resource request submitted — awaiting approval.');
            } else {
                showErr(data.error || 'Failed to submit request.');
            }
        })
        .catch(function () { showErr('Network error — please try again.'); })
        .finally(function () {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Request';
        });
    });

    function showErr(msg) { errBox.textContent = msg; errBox.style.display = 'block'; }
}());
</script>
<?php $this->endSection() ?>
