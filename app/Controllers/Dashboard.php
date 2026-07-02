<?php

namespace App\Controllers;

use App\Libraries\FirestoreService;
use App\Libraries\MLServiceClient;
use App\Models\Model_Projects;
use App\Models\Model_Resources;
use App\Models\Model_Users;
use App\Models\Model_Manpower;
use App\Models\Model_ResourceRequests;
use Config\Services;

class Dashboard extends BaseController
{
    protected $projectModel;
    protected $resourceModel;
    protected $userModel;
    protected $manpowerModel;
    protected $requestModel;
    protected FirestoreService $firestore;

    public function __construct()
    {
        $this->projectModel  = new Model_Projects();
        $this->resourceModel = new Model_Resources();
        $this->userModel     = new Model_Users();
        $this->manpowerModel = new Model_Manpower();
        $this->requestModel  = new Model_ResourceRequests();
        $this->firestore     = FirestoreService::getInstance();
    }

    public function index()
    {
        try {
            if (!session()->get('isLoggedIn')) {
                return redirect()->to('auth/login');
            }

            $userName = session()->get('userName') ?? 'User';
            $role     = session()->get('role') ?? 'User';

            // ── Projects ────────────────────────────────────────────
            $rawProjects = $this->projectModel->getActiveProjects();

            // Build lookup of pending transfer requests so pills can be marked on load
            $pendingMap     = []; // "pid::resId"        => reqId
            $pendingNameMap = []; // "pid::lower(name)"  => reqId
            try {
                foreach ($this->requestModel->getAll([]) as $r) {
                    if (!in_array($r['status'] ?? '', ['Pending', 'Pending Secondary', 'Pending Secondary Approval'])) continue;
                    $srcPid = $r['source_project_id'] ?? '';
                    if (!$srcPid) continue;
                    $rid   = $r['resource_id'] ?? '';
                    $rname = strtolower(trim($r['resource_name'] ?? ''));
                    $reqId = $r['id'];
                    if ($rid)   $pendingMap[$srcPid . '::' . $rid]         = $reqId;
                    if ($rname) $pendingNameMap[$srcPid . '::' . $rname]   = $reqId;
                }
            } catch (\Throwable $e) {
                log_message('warning', 'Dashboard: pending requests - ' . $e->getMessage());
            }

            // ── Resources — single fetch, derive everything in PHP ───
            $allResources       = $this->resourceModel->getAllResources();
            $totalResources     = count($allResources);
            $availableResources = array_values(array_filter($allResources, fn($r) => ($r['status'] ?? '') === 'Available'));
            $deployedResources  = array_values(array_filter($allResources, fn($r) => ($r['status'] ?? '') === 'Deployed'));
            $availableTools     = $availableResources; // same data, no extra call

            // ── Utilization stats (equipment + tools) ────────────────
            // Uses asset_details on the model doc: deployed = total − available (no subcollection query needed)
            $eqT = 0; $eqD = 0; $tlT = 0; $tlD = 0;
            foreach ($allResources as $r) {
                $cat  = strtolower((string)($r['category'] ?? ''));
                $ad   = is_array($r['asset_details'] ?? null) ? $r['asset_details'] : [];
                $tot  = (int)($r['total_quantity'] ?? $ad['total_quantity'] ?? 0);
                $avl  = (int)($r['available_quantity'] ?? $ad['available_quantity'] ?? 0);
                $dep  = max(0, $tot - $avl);
                if ($cat === 'equipment') { $eqT += $tot; $eqD += $dep; }
                elseif ($cat === 'tools') { $tlT += $tot; $tlD += $dep; }
            }
            $utilizationStats = [
                'equipment' => ['total' => $eqT, 'deployed' => $eqD, 'pct' => $eqT > 0 ? round($eqD / $eqT * 100) : 0],
                'tools'     => ['total' => $tlT, 'deployed' => $tlD, 'pct' => $tlT > 0 ? round($tlD / $tlT * 100) : 0],
                'combined'  => [
                    'total'    => $eqT + $tlT,
                    'deployed' => $eqD + $tlD,
                    'pct'      => ($eqT + $tlT) > 0 ? round(($eqD + $tlD) / ($eqT + $tlT) * 100) : 0,
                ],
            ];

            // ── Manpower ─────────────────────────────────────────────
            $allManpower = [];
            try {
                $allManpower = $this->manpowerModel->getAllManpower();
            } catch (\Throwable $e) {
                log_message('warning', 'Dashboard: manpower fetch failed - ' . $e->getMessage());
            }
            $availableManpower = array_values(array_filter($allManpower, function ($m) {
                return strtoupper($m['status'] ?? '') === 'AVAILABLE';
            }));

            // ── Build project → assigned-resource maps ────────────────
            // Must be built BEFORE the activeProjects loop so enrichProject sees them.

            $manpowerByProject = [];
            foreach ($allManpower as $m) {
                $rawApid = $m['assigned_project_id'] ?? '';
                $apid = $rawApid instanceof \Google\Cloud\Firestore\DocumentReference
                    ? $rawApid->id()
                    : trim((string)$rawApid);
                if ($apid === '') continue;
                $fn   = trim((string)($m['first_name'] ?? ''));
                $ln   = trim((string)($m['last_name']  ?? ''));
                $name = strtoupper($fn . ($fn && $ln ? ' ' : '') . $ln) ?: strtoupper($m['name'] ?? '');
                if ($name === '') continue;
                $mpStatus  = strtolower(trim((string)($m['status'] ?? '')));
                $isTransit = in_array($mpStatus, ['in-transit', 'transfer pending']);
                $manpowerByProject[$apid][] = ['name' => $name, 'id' => $m['id'], 'unit_id' => '', 'transit' => $isTransit];
            }

            // Equipment + Tools: reuse $allResources — no second getAllResources() call
            $equipmentByProject = [];
            $toolsByProject     = [];
            foreach ($allResources as $res) {
                try {
                    $resId    = $res['id'];
                    $resName  = strtoupper(trim((string)($res['name'] ?? '')));
                    $category = strtolower(trim((string)($res['category'] ?? '')));

                    foreach ($this->resourceModel->getResourceItems($resId) as $item) {
                        $apid = $item['assigned_project_id'] ?? null;
                        $itemPid = $apid instanceof \Google\Cloud\Firestore\DocumentReference
                            ? $apid->id()
                            : trim((string)($apid ?? ''));
                        if ($itemPid === '') continue;

                        $unitId    = $item['__id'] ?? $item['id'] ?? '';
                        $itemStatus = strtolower(trim((string)($item['status'] ?? '')));
                        $isTransit  = in_array($itemStatus, ['in-transit', 'transfer pending']);
                        $pill       = ['name' => $resName, 'id' => $resId, 'unit_id' => $unitId, 'transit' => $isTransit];

                        if ($category === 'tools') {
                            $toolsByProject[$itemPid][] = $pill;
                        } else {
                            $equipmentByProject[$itemPid][] = $pill;
                        }
                    }
                } catch (\Throwable $e) {
                    log_message('warning', 'Dashboard: resource item scan failed for ' . ($res['id'] ?? '?') . ' - ' . $e->getMessage());
                }
            }

            $activeProjects = [];
            foreach ($rawProjects as $p) {
                $pid = $p['id'] ?? '';
                $assignedResources = [
                    'equipment' => $equipmentByProject[$pid] ?? [],
                    'tools'     => $toolsByProject[$pid]     ?? [],
                    'manpower'  => $manpowerByProject[$pid]  ?? [],
                ];
                $enriched = $this->enrichProject($p, $assignedResources);
                $pid = $enriched['id'] ?? '';
                foreach (['_equipment', '_tools', '_manpower'] as $key) {
                    $enriched[$key] = array_map(function (array $pill) use ($pid, $pendingMap, $pendingNameMap): array {
                        $rid   = $pill['id'] ?? '';
                        $rname = strtolower($pill['name'] ?? '');
                        $reqId = ($rid   !== '' && isset($pendingMap[$pid . '::' . $rid]))
                               ? $pendingMap[$pid . '::' . $rid]
                               : (($rname !== '' && isset($pendingNameMap[$pid . '::' . $rname]))
                                  ? $pendingNameMap[$pid . '::' . $rname]
                                  : null);
                        $pill['pending'] = $reqId !== null;
                        $pill['req_id']  = $reqId ?? '';
                        return $pill;
                    }, $enriched[$key]);
                }
                $activeProjects[] = $enriched;
            }

            // ── For Project Manager: monitor only shows their assigned projects ──
            $uid = (string)(session()->get('userId') ?? '');
            $monitorProjects = pz_is('Project Manager')
                ? array_values(array_filter($activeProjects, function ($p) use ($uid) {
                    return ($p['_pm_uid'] ?? '') === $uid || ($p['_se_uid'] ?? '') === $uid;
                }))
                : $activeProjects;

            // ── Group projects by Philippine island group ────────────
            $projectsByRegion = [];
            foreach ($monitorProjects as $p) {
                $projectsByRegion[$p['_region']][] = $p;
            }
            $regionOrder = ['LUZON' => 0, 'VISAYAS' => 1, 'MINDANAO' => 2];
            uksort($projectsByRegion, function ($a, $b) use ($regionOrder) {
                return ($regionOrder[$a] ?? 99) - ($regionOrder[$b] ?? 99);
            });

            // ── Blockers: low progress or halted ────────────────────
            $blockers = array_values(array_filter($activeProjects, function ($p) {
                return ($p['_progress'] < 30) || (($p['status'] ?? '') === 'Halted');
            }));

            // ── ML Predicted Blockers & Delays ───────────────────────
            // Single batch call replaces per-project loop to avoid N×timeout penalty.
            $mlBlockers  = [];
            $mlAvailable = true;
            try {
                $mlService = new MLServiceClient();
                // Probe availability with first project only; skip rest if service is down.
                foreach ($activeProjects as $p) {
                    if (!$mlAvailable) break;
                    try {
                        // Compute real equipment_utilization from project.resources[]
                        // Mirrors ProjectDelayPredictor::buildFeatures() — deployed (non-transit)
                        // non-manpower entries ÷ total non-manpower entries at this project.
                        $projRes   = (array)($p['resources'] ?? []);
                        $eqTotal   = 0; $eqActive = 0;
                        foreach ($projRes as $_r) {
                            if (strtolower((string)($_r['category'] ?? '')) === 'manpower') continue;
                            $eqTotal++;
                            if (empty($_r['transit'])) $eqActive++;
                        }
                        $eqUtil = $eqTotal > 0 ? round($eqActive / $eqTotal, 2) : 1.0;

                        // Compute days from actual project dates — stored fields may be
                        // stale or negative (past-deadline), which blows up the ML base calc.
                        $mlNow         = time();
                        $startTs       = $this->toTimestamp($p['start_date'] ?? null);
                        $endTs         = $this->toTimestamp($p['end_date']   ?? null);
                        $totalDuration = ($startTs && $endTs && $endTs > $startTs)
                            ? max(1, (int)(($endTs - $startTs) / 86400))
                            : 0;
                        $daysElapsed   = $startTs
                            ? max(0, (int)(($mlNow - $startTs) / 86400))
                            : max(0, (int)($p['days_elapsed'] ?? 0));
                        // Cap to planned duration: ML was trained on in-timeline projects.
                        // Stale/abandoned projects with 5000+ elapsed days return nonsense.
                        if ($totalDuration > 0) {
                            $daysElapsed = min($daysElapsed, $totalDuration);
                        }
                        $daysRemaining = $endTs
                            ? max(0, (int)(($endTs - $mlNow) / 86400))   // clamp — never negative
                            : max(0, (int)($p['days_remaining'] ?? 0));

                        $progress = (float)($p['_progress'] ?? 0);

                        // Linear expected-progress-by-now, mirrors ProjectDelayPredictor::buildFeatures().
                        $totalDays  = $totalDuration > 0 ? $totalDuration : max(1, $daysElapsed + $daysRemaining);
                        $plannedPct = min(100.0, round(($daysElapsed / $totalDays) * 100, 2));

                        // Skip new/uninitialised projects — ML predictions would be noise
                        if ($daysElapsed === 0 && $progress === 0.0) {
                            $mlBlockers[] = [
                                'project_id'               => (string)($p['id'] ?? ''),
                                'project_name'             => $p['project_name'] ?? 'Unknown Project',
                                'predicted'                => 'No Blocker',
                                'estimated_delay_days'     => 0,
                                'estimated_completion_date'=> null,
                                'delay_predicted'          => false,
                                'risk_level'               => 'low',
                                'reason'                   => '',
                            ];
                            continue;
                        }

                        // Active emergency flags — derived from unresolved emergency_reports
                        // causes (SE-filed), not stored on the project doc itself.
                        $emergFlags = $this->_computeEmergencyFlags((string)($p['id'] ?? ''));

                        $features = [
                            'project_id'                    => (string)($p['id'] ?? ''),
                            'progress_percent'              => $progress,
                            'planned_progress_percent'      => $plannedPct,
                            'days_elapsed'                  => $daysElapsed,
                            'days_remaining'                => $daysRemaining,
                            'workers_present'               => (int)($p['workers_present'] ?? 0),
                            'workers_planned'               => (int)($p['workers_planned'] ?? 1),
                            'equipment_utilization'         => $eqUtil,
                            'weather_severity'              => $emergFlags['weather'],
                            'site_access_issues'            => (int)($p['site_access_issues'] ?? 0),
                            'avg_progress_3d'               => (float)($p['avg_progress_3d'] ?? 0),
                            'progress_trend_3d'             => (float)($p['progress_trend_3d'] ?? 0),
                            'consecutive_low_days'          => (int)($p['consecutive_low_days'] ?? 0),
                            'budget_utilization'            => (float)($p['budget_utilization'] ?? 0),
                            'cost_variance_ratio'           => (float)($p['cost_variance_ratio'] ?? 0),
                            'active_blocker_count'          => (int)($p['active_blocker_count'] ?? 0),
                            'days_since_last_blocker'       => (int)($p['days_since_last_blocker'] ?? 999),
                            'emergency_equipment_shortage'  => $emergFlags['equipment'],
                            'emergency_labor_shortage'      => $emergFlags['labor'],
                            'emergency_onsite_accidents'    => $emergFlags['onsite'],
                        ];

                        $blockerPrediction = $mlService->predictBlockers($features);

                        // If we got an error response, stop trying subsequent projects
                        if (isset($blockerPrediction['error'])) {
                            $mlAvailable = false;
                            break;
                        }

                        $delayPrediction = method_exists($mlService, 'predictDelay')
                            ? $mlService->predictDelay($features)
                            : null;

                        $rawBlocker       = $blockerPrediction['blocker_type'] ?? $blockerPrediction['predicted_blocker'] ?? $blockerPrediction['prediction'] ?? 'No Blocker';
                        $predictedBlocker = ($rawBlocker === 'Unknown') ? 'No Blocker' : $rawBlocker;

                        $estimatedDelay = 0;
                        if ($delayPrediction && !isset($delayPrediction['error'])) {
                            $estimatedDelay = $delayPrediction['estimated_delay_days']
                                           ?? $delayPrediction['predicted_delay_days']
                                           ?? $delayPrediction['delay_days']
                                           ?? $delayPrediction['prediction']
                                           ?? 0;
                        } elseif ($blockerPrediction) {
                            $estimatedDelay = $blockerPrediction['estimated_delay_days']
                                           ?? $blockerPrediction['predicted_delay_days']
                                           ?? $blockerPrediction['delay_days']
                                           ?? 0;
                        }

                        // Cap: a delay larger than the whole project duration is not actionable.
                        $maxDelay       = $totalDuration > 0 ? $totalDuration : 730;
                        $estimatedDelay = min((float)$estimatedDelay, (float)$maxDelay);

                        $seReason = $this->_fetchBlockerReason((string)($p['id'] ?? ''), $predictedBlocker, $features);

                        $mlBlockers[] = [
                            'project_id'               => (string)($p['id'] ?? ''),
                            'project_name'             => $p['project_name'] ?? 'Unknown Project',
                            'predicted'                => $predictedBlocker,
                            'estimated_delay_days'     => (int) round((float) $estimatedDelay),
                            'estimated_completion_date'=> $delayPrediction['estimated_completion_date'] ?? $blockerPrediction['estimated_completion_date'] ?? null,
                            'delay_predicted'          => (bool)($delayPrediction['delay_predicted'] ?? $blockerPrediction['delay_predicted'] ?? ($estimatedDelay > 0)),
                            'risk_level'               => $delayPrediction['risk_level'] ?? $blockerPrediction['risk_level'] ?? 'low',
                            'reason'                   => $seReason,
                        ];
                    } catch (\Throwable $e) {
                        // First failure means service is down — abort all remaining calls
                        $mlAvailable = false;
                        log_message('warning', 'Dashboard: ML unavailable, skipping remaining projects - ' . $e->getMessage());
                    }
                }
            } catch (\Throwable $e) {
                log_message('warning', 'Dashboard: ML service init failed - ' . $e->getMessage());
            }

            // ── Annotate projects with ML delay data; notify if threshold met ──────
            $mlDelayById = [];
            foreach ($mlBlockers as $bl) {
                $pid = $bl['project_id'] ?? '';
                if ($pid !== '') {
                    $mlDelayById[$pid] = (int)($bl['estimated_delay_days'] ?? 0);
                }
            }

            // Also treat past-deadline projects as needing notification
            $notifSvc = new \App\Services\NotificationService();
            $db       = $this->firestore->getDb();
            $nowTs    = time();

            foreach ($activeProjects as &$ap) {
                $pid       = (string)($ap['id'] ?? '');
                $mlDelay   = $mlDelayById[$pid] ?? 0;
                $threshold = max(0, (int)($ap['delay_ping_days'] ?? 0));

                // Mark delayed if ML says so and meets threshold
                $isDelayed       = ($mlDelay > 0) && ($threshold === 0 || $mlDelay >= $threshold);
                $isPastDeadline  = $ap['_past_deadline'] ?? false;

                if ($isDelayed) {
                    $ap['_ml_delay_days'] = $mlDelay;
                    $ap['_status']        = 'DELAYED';
                }

                // Send ping notification (max once every 24h per project)
                if ($isDelayed || $isPastDeadline) {
                    try {
                        $snap     = $db->collection('project')->document($pid)->snapshot();
                        $lastPing = $snap->exists() ? (int)($snap->data()['delay_last_notified_at'] ?? 0) : 0;

                        if (($nowTs - $lastPing) >= 86400) {
                            $title  = $isPastDeadline ? 'Project Past Deadline' : 'Project Delay Alert';
                            $msgTxt = $isPastDeadline
                                ? ($ap['project_name'] ?? 'A project') . ' has passed its deadline.'
                                : ($ap['project_name'] ?? 'A project') . " is predicted to be delayed by {$mlDelay} day(s).";

                            // Notify admins and OMs
                            $targets = (new \App\Models\Model_Users())
                                ->getUsersByRole(['Administrator', 'Operations Manager']);
                            foreach ($targets as $t) {
                                $uid = (string)($t['id'] ?? '');
                                if ($uid) {
                                    $notifSvc->send($uid, 'projectDelay', $title, $msgTxt, ['project_id' => $pid]);
                                }
                            }

                            // Notify the assigned PM
                            $pmUid = (string)($ap['_pm_uid'] ?? '');
                            if ($pmUid) {
                                $notifSvc->send($pmUid, 'projectDelay', $title, $msgTxt, ['project_id' => $pid]);
                            }

                            // Stamp the project so we don't re-ping for 24h
                            $db->collection('project')->document($pid)->update([
                                ['path' => 'delay_last_notified_at', 'value' => $nowTs],
                            ]);
                        }
                    } catch (\Throwable $e) {
                        log_message('error', 'Dashboard: delay notification failed for ' . $pid . ' - ' . $e->getMessage());
                    }
                }
            }
            unset($ap);

            // Re-sort each region: Past Deadline first, then Delayed, then others
            $urgency = function (array $p): int {
                if ($p['_past_deadline'] ?? false) return 0;
                if (($p['_ml_delay_days'] ?? 0) > 0) return 1;
                return 2;
            };
            foreach ($projectsByRegion as &$regionArr) {
                usort($regionArr, function ($a, $b) use ($urgency) {
                    return $urgency($a) <=> $urgency($b);
                });
            }
            unset($regionArr);

            // ── FastAPI MCDA Tool Availability (optional, falls back to Firestore data) ──
            $availableToolsMcda = [];
            try {
                // Use first geolocated project as the reference site; fall back to PH centre
                $siteLat = 14.5995;
                $siteLng = 120.9842;
                foreach ($activeProjects as $_ap) {
                    if ($_ap['_lat'] !== null && $_ap['_lng'] !== null) {
                        $siteLat = (float)$_ap['_lat'];
                        $siteLng = (float)$_ap['_lng'];
                        break;
                    }
                }

                $releasedToolsPayload = [];
                foreach ($activeProjects as $p) {
                    $toolCount = count($p['_tools'] ?? []);
                    if ($p['_lat'] !== null && $p['_lng'] !== null && $toolCount >= 1) {
                        $releasedToolsPayload[] = [
                            'project_id'    => (string)($p['id'] ?? ''),
                            'project_name'  => $p['project_name'] ?? 'Unknown',
                            'lat'           => (float)$p['_lat'],
                            'lng'           => (float)$p['_lng'],
                            'tool_category' => 'Construction Equipment',
                            'units_released'=> $toolCount,
                        ];
                    }
                }

                $mcdaPayload = [
                    'tool_category'  => 'Construction Equipment',
                    'site_lat'       => $siteLat,
                    'site_lng'       => $siteLng,
                    'radius_km'      => 50,
                    'weights'        => null,
                    'released_tools' => !empty($releasedToolsPayload) ? $releasedToolsPayload : null,
                ];

                $fastApiResp = Services::curlrequest()->request('POST', 'http://127.0.0.1:8000/predict/tool-availability', [
                    'headers'         => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                    'json'            => $mcdaPayload,
                    'timeout'         => 2,
                    'connect_timeout' => 1,
                ]);

                if ($fastApiResp->getStatusCode() === 200) {
                    $apiData   = json_decode($fastApiResp->getBody(), true);
                    $toolsList = $apiData['optimized_tools'] ?? $apiData['tools'] ?? $apiData['predictions'] ?? [];
                    if (is_array($toolsList)) {
                        // Urgency-based next deployment: project with lowest progress gets tools first
                        $nextDeploymentSite = 'Unassigned Depot';
                        $highestUrgency     = -1;
                        foreach ($activeProjects as $proj) {
                            $urgency = 100 - $proj['_progress'];
                            if ($urgency > $highestUrgency) {
                                $highestUrgency     = $urgency;
                                $nextDeploymentSite = $proj['project_name'] ?? 'Unknown';
                            }
                        }
                        foreach ($toolsList as $key => $value) {
                            $toolName = isset($value['name']) ? $value['name'] : (!is_array($value) ? $key : 'UNKNOWN');
                            $qty      = isset($value['qty'])  ? (int)$value['qty']  : (!is_array($value) ? (int)$value : 0);
                            $availableToolsMcda[] = [
                                'name'            => strtoupper(str_replace('_', ' ', $toolName)),
                                'qty'             => $qty,
                                'next_deployment' => $nextDeploymentSite,
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                log_message('warning', 'Dashboard: MCDA tool availability failed - ' . $e->getMessage());
            }

            // ── Staff ────────────────────────────────────────────────
            $staffCount = $this->userModel->countStaff();

            // Build a flat list of project locations for the monitor map JS
            $projectLocations = [];
            foreach ($monitorProjects as $p) {
                if ($p['_lat'] !== null && $p['_lng'] !== null) {
                    $projectLocations[] = [
                        'id'   => $p['id'] ?? '',
                        'name' => $p['project_name'] ?? '',
                        'addr' => $p['location']['address'] ?? '',
                        'lat'  => $p['_lat'],
                        'lng'  => $p['_lng'],
                        'type' => $p['_project_type'] ?? '',
                    ];
                }
            }

            $data = [
                'pageTitle'           => 'Dashboard',
                'activePage'          => 'dashboard',
                'userName'            => $userName,
                'role'                => $role,
                'projectCount'        => count($activeProjects),
                'resourceCount'       => $totalResources,
                'staffCount'          => $staffCount,
                'activeProjects'      => $activeProjects,
                'projectsByRegion'    => $projectsByRegion,
                'projectLocations'    => $projectLocations,
                'blockers'            => $blockers,
                'mlBlockers'          => $mlBlockers,
                'availableTools'      => $availableTools,
                'availableToolsMcda'  => $availableToolsMcda,
                'availableResources'  => $availableResources,
                'deployedResources'   => $deployedResources,
                'allResources'        => $allResources,
                'availableManpower'   => $availableManpower,
                'utilizationStats'    => $utilizationStats,
                'googleMapsBrowserKey' => (new \Config\GoogleMaps())->browserKey,
            ];

            return view('dashboard/index', $data);

        } catch (\Exception $e) {
            log_message('error', 'Dashboard::index - ' . $e->getMessage());

            return view('dashboard/index', [
                'pageTitle'        => 'Dashboard',
                'activePage'       => 'dashboard',
                'userName'         => session()->get('userName') ?? 'User',
                'role'             => session()->get('role') ?? 'User',
                'projectCount'     => 0,
                'resourceCount'    => 0,
                'staffCount'       => 0,
                'activeProjects'   => [],
                'projectsByRegion' => [],
                'blockers'            => [],
                'mlBlockers'          => [],
                'availableTools'      => [],
                'availableToolsMcda'  => [],
                'availableResources'  => [],
                'deployedResources'   => [],
                'allResources'        => [],
                'availableManpower'   => [],
                'errorMessage'        => 'Unable to load dashboard right now.',
            ]);
        }
    }

    // ── Live polling fingerprint ──────────────────────────────────────

    public function live(): object
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }
        try {
            // Lightweight: only reads project docs (no subcollection items enumeration)
            $projects = $this->projectModel->getActiveProjects();
            $parts    = [];
            foreach ($projects as $p) {
                $resCount = count((array)($p['resources'] ?? []));
                $parts[]  = ($p['id'] ?? '') . ':' . $resCount . ':' . (int)($p['overall_progress'] ?? 0);
            }

            // Include in-flight request statuses
            $activeStatuses = ['Pending', 'Pending Secondary', 'Pending Secondary Approval', 'Approved', 'In-Transit'];
            $reqParts = [];
            foreach ($this->requestModel->getAll([]) as $r) {
                if (in_array($r['status'] ?? '', $activeStatuses)) {
                    $reqParts[] = ($r['id'] ?? '') . ':' . ($r['status'] ?? '');
                }
            }
            sort($reqParts);
            $parts = array_merge($parts, $reqParts);

            return $this->response->setJSON(['version' => md5(implode('|', $parts))]);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard::live - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Failed']);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function enrichProject(array $p, array $assignedResources = []): array
    {
        // Normalise the location field so downstream helpers always get a plain array
        $p['location'] = $this->normaliseLocation($p['location'] ?? null);

        $p['_progress']  = min(100, max(0, (float)($p['overall_progress'] ?? $p['completion_percent'] ?? 0)));
        $p['_start_fmt'] = $this->fmtDate($p['start_date'] ?? '');
        $p['_end_fmt']   = $this->fmtDate($p['end_date'] ?? '');
        $p['_region']    = $this->detectRegion($p);
        [$p['_lat'], $p['_lng']] = $this->extractLatLng($p);
        $p['_maps_url']  = $this->mapsUrl($p);

        // Resource pills — merge mobile-app inline resources with web-assigned resources.
        // PM/SE are intentionally excluded; only real resources are shown.
        $equipment = [];
        $tools     = [];
        $manpower  = [];

        // Source 1: inline resources field (written by mobile drag-and-drop / transfer sync)
        if (!empty($p['resources']) && is_array($p['resources'])) {
            foreach ($p['resources'] as $res) {
                $cat  = strtolower(trim((string)($res['category'] ?? 'tools')));
                $name = strtoupper(trim((string)($res['name'] ?? '')));
                if ($name === '') continue;
                $pill = [
                    'name'    => $name,
                    'id'      => (string)($res['ref_id']  ?? ''),
                    'unit_id' => (string)($res['unit_id'] ?? ''),
                    'transit' => !empty($res['transit']),
                ];
                if ($cat === 'manpower')      $manpower[]  = $pill;
                elseif ($cat === 'equipment') $equipment[] = $pill;
                else                          $tools[]     = $pill;
            }
        }

        // Source 2: web-assigned resources (assigned_project_id on manpower/item docs)
        // Deduplicate by id+unit_id (resource identity) to avoid double-counting the same
        // physical resource when its name differs between sources (e.g. with vs. without middle name).
        $seen = [];
        $merge = function (array &$target, array $incoming) use (&$seen): void {
            foreach ($incoming as $pill) {
                $key = ($pill['id'] ?? '') . '|' . ($pill['unit_id'] ?? '');
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                $target[] = $pill;
            }
        };

        // Seed seen set with pills already added from Source 1
        foreach ($equipment as $pill) $seen[($pill['id']??'').'|'.($pill['unit_id']??'')] = true;
        foreach ($tools     as $pill) $seen[($pill['id']??'').'|'.($pill['unit_id']??'')] = true;
        foreach ($manpower  as $pill) $seen[($pill['id']??'').'|'.($pill['unit_id']??'')] = true;

        $merge($equipment, $assignedResources['equipment'] ?? []);
        $merge($tools,     $assignedResources['tools']     ?? []);
        $merge($manpower,  $assignedResources['manpower']  ?? []);

        $p['_equipment']    = $equipment;
        $p['_tools']        = $tools;
        $p['_manpower']     = $manpower;
        $p['_client']       = strtoupper(trim((string)($p['client_name'] ?? '')));
        $p['_project_type'] = strtoupper(trim((string)($p['project_type'] ?? '')));
        $rawStatus          = strtoupper(trim((string)($p['status'] ?? '')));
        $p['_past_deadline'] = ($rawStatus === 'PAST DEADLINE');
        $p['_ml_delay_days'] = 0;   // filled after ML loop
        if (in_array($rawStatus, ['COMPLETED', 'DONE'])) {
            $p['_status'] = 'DONE';
        } elseif ($p['_past_deadline']) {
            $p['_status'] = 'PAST_DEADLINE';
        } else {
            $p['_status'] = 'ONGOING';
        }

        // Normalize PM/SE UIDs for role-based monitor filtering
        $pmRaw = $p['personnel']['project_manager_id'] ?? $p['project_manager_id'] ?? null;
        $seRaw = $p['personnel']['site_engineer_id']   ?? $p['site_engineer_id']   ?? null;
        $p['_pm_uid'] = $this->extractPersonnelUid($pmRaw);
        $p['_se_uid'] = $this->extractPersonnelUid($seRaw);

        return $p;
    }

    private function extractPersonnelUid(mixed $value): string
    {
        if ($value === null) return '';
        if (is_object($value) && method_exists($value, 'id')) {
            try { return (string)$value->id(); } catch (\Throwable $e) {}
        }
        $str = is_string($value) ? $value : '';
        // strip "users/" path prefix if present
        return preg_replace('#^.+/#', '', trim($str)) ?: trim($str);
    }

    // ── Normalise a raw Firestore location value into a plain PHP array ──
    private function normaliseLocation($raw): array
    {
        if (!$raw) return [];

        // Already a plain array (most common case from CI4 form saves)
        if (is_array($raw)) {
            // Geopoint inside may still be a GeoPoint object — flatten it
            if (isset($raw['geopoint'])) {
                $raw['geopoint'] = $this->flattenGeoPoint($raw['geopoint']);
            }
            return $raw;
        }

        // Firestore GeoPoint stored directly as the location field
        if ($raw instanceof \Google\Cloud\Core\GeoPoint) {
            return [
                'address'  => '',
                'geopoint' => ['lat' => $raw->latitude(), 'lng' => $raw->longitude()],
            ];
        }

        // String address
        if (is_string($raw)) {
            return ['address' => $raw, 'geopoint' => []];
        }

        return [];
    }

    private function flattenGeoPoint($geopoint): array
    {
        if ($geopoint instanceof \Google\Cloud\Core\GeoPoint) {
            return ['lat' => $geopoint->latitude(), 'lng' => $geopoint->longitude()];
        }
        return is_array($geopoint) ? $geopoint : [];
    }

    /**
     * Checks a project's unresolved emergency_reports for specific cause labels and
     * returns the 0/1 flags the ML service expects. "Material Shortage" and
     * "Equipment/Tool Breakdown" both set emergency_equipment_shortage — the ML schema
     * has no separate slot for material shortages.
     */
    private function _computeEmergencyFlags(string $projectId): array
    {
        $flags = ['equipment' => 0, 'labor' => 0, 'onsite' => 0, 'weather' => 0];
        if ($projectId === '') return $flags;

        try {
            $docs = $this->firestore->getDb()
                ->collection('project')->document($projectId)
                ->collection('emergency_reports')->documents();

            foreach ($docs as $doc) {
                if (!$doc->exists()) continue;
                $d = $doc->data();
                if (!empty($d['resolved'])) continue;

                $causes = array_map('strval', (array)($d['causes'] ?? []));
                if (in_array('Material Shortage', $causes, true) || in_array('Equipment/Tool Breakdown', $causes, true)) {
                    $flags['equipment'] = 1;
                }
                if (in_array('Labor Shortage', $causes, true))    $flags['labor']  = 1;
                if (in_array('Onsite Accidents', $causes, true))  $flags['onsite'] = 1;
                // Escalated to an emergency report — treat as top severity (typhoon/storm tier),
                // distinct from routine daily-report weather notes.
                if (in_array('Adverse Weather', $causes, true))   $flags['weather'] = 2;
            }
        } catch (\Throwable $e) {
            log_message('warning', '_computeEmergencyFlags: ' . $e->getMessage());
        }

        return $flags;
    }

    private function _fetchBlockerReason(string $projectId, string $blockerType, array $features = []): string
    {
        // ── Low Progress: "X% C  Y% Behind [— SE note]" ────────────────────────
        if ($blockerType === 'Low Progress') {
            $actual = round((float)($features['progress_percent'] ?? 0), 1);
            $behind = round(max(0, (float)($features['planned_progress_percent'] ?? 0) - $actual), 1);
            $msg    = "{$actual}% Done";
            if ($behind > 0) $msg .= "  {$behind}% Behind";

            // Append latest SE daily-report description if available
            if ($projectId !== '') {
                try {
                    $docs = $this->firestore->getDb()
                        ->collection('project')->document($projectId)
                        ->collection('daily_progress_reports')
                        ->orderBy('report_date', 'DESC')
                        ->limit(1)->documents();
                    foreach ($docs as $doc) {
                        if (!$doc->exists()) break;
                        $desc = trim((string)($doc->data()['description'] ?? $doc->data()['notes'] ?? ''));
                        if ($desc !== '') $msg .= ' — ' . $desc;
                        break;
                    }
                } catch (\Throwable $e) {}
            }
            return $msg;
        }

        if ($projectId === '') return '';

        $parts = [];

        try {
            $db = $this->firestore->getDb();

            // ── Labor Shortage: worker headcount from features ─────────────────
            if ($blockerType === 'Labor Shortage') {
                $present   = (int)($features['workers_present'] ?? 0);
                $needed    = (int)($features['workers_planned']  ?? 0);
                $shortfall = max(0, $needed - $present);
                if ($needed > 0) {
                    $parts[] = "Workers: {$present} present of {$needed} needed"
                             . ($shortfall > 0 ? " ({$shortfall} short)" : '');
                }
            }

            // ── Emergency-type blockers: pull latest UNRESOLVED matching report ──
            // Cause labels match the SE's checkbox list — must stay in sync with
            // _computeEmergencyFlags(). Types with no dedicated checkbox (e.g.
            // Project Halted) fall back to accepting any unresolved report.
            $emergCauseMap = [
                'Labor Shortage'    => ['Labor Shortage'],
                'Onsite Accident'   => ['Onsite Accidents'],
                'Material Shortage' => ['Material Shortage', 'Equipment/Tool Breakdown'],
                'Resource Shortage' => ['Material Shortage', 'Equipment/Tool Breakdown'],
                'Weather Delay'     => ['Adverse Weather'],
                'Project Halted'    => [],
            ];
            if (array_key_exists($blockerType, $emergCauseMap)) {
                $wantedCauses = $emergCauseMap[$blockerType];
                try {
                    $emergDocs = $db->collection('project')->document($projectId)
                        ->collection('emergency_reports')
                        ->orderBy('created_at', 'DESC')
                        ->documents();

                    foreach ($emergDocs as $doc) {
                        if (!$doc->exists()) continue;
                        $d = $doc->data();
                        if (!empty($d['resolved'])) continue;

                        $causes = array_map('strval', (array)($d['causes'] ?? []));
                        if (!empty($wantedCauses) && !array_intersect($wantedCauses, $causes)) continue;

                        // Cause label (e.g. "Onsite Accidents") is already shown in the Status
                        // column — only append the SE's own free-text description here.
                        $notes = trim((string)($d['notes'] ?? $d['description'] ?? ''));
                        $other = trim((string)($d['other_cause'] ?? $d['otherCause'] ?? ''));
                        if ($notes !== '')      $parts[] = $notes;
                        elseif ($other !== '')  $parts[] = $other;
                        break;
                    }
                } catch (\Throwable $e) {
                    log_message('warning', '_fetchBlockerReason: emergency - ' . $e->getMessage());
                }
            }

            // ── "Other" cause: no ML feature slot to influence classification, but
            // still surface the SE's note if nothing else has been found yet ────────
            if (empty($parts)) {
                try {
                    $otherDocs = $db->collection('project')->document($projectId)
                        ->collection('emergency_reports')
                        ->orderBy('created_at', 'DESC')
                        ->documents();

                    foreach ($otherDocs as $doc) {
                        if (!$doc->exists()) continue;
                        $d = $doc->data();
                        if (!empty($d['resolved'])) continue;

                        $causes = array_map('strval', (array)($d['causes'] ?? []));
                        if (!in_array('Other', $causes, true)) continue;

                        $notes = trim((string)($d['notes'] ?? $d['description'] ?? ''));
                        $other = trim((string)($d['other_cause'] ?? $d['otherCause'] ?? ''));
                        if ($notes !== '')      $parts[] = $notes;
                        elseif ($other !== '')  $parts[] = $other;
                        break;
                    }
                } catch (\Throwable $e) {
                    log_message('warning', '_fetchBlockerReason: other-cause - ' . $e->getMessage());
                }
            }

            // ── Weather Delay + fallback: use latest daily SE report ─────────────
            $needsDaily = $blockerType === 'Weather Delay'
                       || (empty($parts) && $blockerType !== 'Labor Shortage');
            if ($needsDaily) {
                try {
                    $dailyDocs = $db->collection('project')->document($projectId)
                        ->collection('daily_progress_reports')
                        ->orderBy('report_date', 'DESC')
                        ->limit(1)->documents();

                    foreach ($dailyDocs as $doc) {
                        if (!$doc->exists()) break;
                        $d    = $doc->data();
                        $desc = trim((string)($d['description'] ?? $d['notes'] ?? ''));
                        if ($desc !== '') $parts[] = $desc;
                        break;
                    }
                } catch (\Throwable $e) {
                    log_message('warning', '_fetchBlockerReason: daily report - ' . $e->getMessage());
                }
            }

        } catch (\Throwable $e) {
            log_message('warning', '_fetchBlockerReason: ' . $e->getMessage());
        }

        return implode(' | ', array_filter($parts));
    }

    private function extractLatLng(array $p): array
    {
        $geopoint = $p['location']['geopoint'] ?? [];
        $lat = isset($geopoint['lat']) ? (float)$geopoint['lat'] : null;
        $lng = isset($geopoint['lng']) ? (float)$geopoint['lng'] : null;
        return [$lat, $lng];
    }

    private function detectRegion(array $p): string
    {
        $explicit = strtoupper(trim((string)($p['region'] ?? '')));
        if ($explicit !== '') return $explicit;

        $addr = strtolower((string)($p['location']['address'] ?? ''));

        $map = [
            'LUZON'    => ['manila','quezon','caloocan','makati','taguig','pasig','marikina','laguna','cavite','batangas','pampanga','bulacan','rizal','baguio','ilocos','nueva ecija','tarlac','zambales','bataan','aurora','palawan','bicol','albay','camarines','cagayan'],
            'VISAYAS'  => ['cebu','iloilo','bacolod','tacloban','dumaguete','ormoc','leyte','samar','bohol','negros','panay','aklan','antique','capiz','biliran'],
            'MINDANAO' => ['davao','cagayan de oro','zamboanga','cotabato','butuan','iligan','general santos','bukidnon','misamis','surigao','agusan','maguindanao'],
        ];

        foreach ($map as $region => $keywords) {
            foreach ($keywords as $kw) {
                if (strpos($addr, $kw) !== false) return $region;
            }
        }

        return 'LUZON';
    }

    private function toTimestamp($val): ?int
    {
        if (!$val) return null;
        if ($val instanceof \Google\Cloud\Core\Timestamp) {
            try { return $val->get()->getTimestamp(); } catch (\Throwable $e) { return null; }
        }
        if (is_numeric($val)) return (int)$val;
        $ts = strtotime((string)$val);
        return $ts ?: null;
    }

    private function fmtDate($val): string
    {
        if (!$val) return 'N/A';

        // Firestore Timestamp object
        if ($val instanceof \Google\Cloud\Core\Timestamp) {
            try {
                return strtoupper(date('F j, Y', $val->get()->getTimestamp()));
            } catch (\Throwable $e) {
                return 'N/A';
            }
        }

        if (is_numeric($val)) return strtoupper(date('F j, Y', (int)$val));
        $ts = strtotime((string)$val);
        return $ts ? strtoupper(date('F j, Y', $ts)) : strtoupper((string)$val);
    }

    private function mapsUrl(array $p): string
    {
        [$lat, $lng] = $this->extractLatLng($p);
        $addr = $p['location']['address'] ?? $p['project_name'] ?? '';
        if ($lat !== null && $lng !== null) {
            return "https://www.google.com/maps?q={$lat},{$lng}";
        }
        return 'https://www.google.com/maps/search/' . urlencode((string)$addr);
    }
}
