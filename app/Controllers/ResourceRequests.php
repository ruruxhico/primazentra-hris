<?php

namespace App\Controllers;

use App\Libraries\ArchiveService;
use App\Libraries\FirestoreService;
use App\Libraries\MLServiceClient;
use App\Libraries\TrashService;
use App\Models\Model_ResourceRequests;
use App\Models\Model_Projects;
use App\Models\Model_Resources;
use App\Models\Model_Manpower;
use App\Models\Model_Users;
use App\Services\NotificationService;
use Google\Cloud\Core\Timestamp;

class ResourceRequests extends BaseController
{
    protected $requestModel;
    protected $projectModel;
    protected $resourceModel;
    protected $manpowerModel;

    protected FirestoreService $firestore;

    public function __construct()
    {
        $this->firestore     = FirestoreService::getInstance();
        $this->requestModel  = new Model_ResourceRequests();
        $this->projectModel  = new Model_Projects();
        $this->resourceModel = new Model_Resources();
        $this->manpowerModel = new Model_Manpower();
    }

    // ── List ──────────────────────────────────────────────────────────────

    public function index()
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');

        $status   = $this->request->getGet('status')   ?? '';
        $category = $this->request->getGet('category') ?? '';
        $search   = $this->request->getGet('search')   ?? '';

        $filters = array_filter([
            'status'   => $status,
            'category' => $category,
            'search'   => $search,
        ]);

        try {
            $requests = $this->requestModel->getAll($filters);
            $requests = $this->_sweepTerminalRequests($requests);
        } catch (\Throwable $e) {
            log_message('error', 'ResourceRequests::index - ' . $e->getMessage());
            $requests = [];
        }

        $total = count($requests);
        return view('requests/index', [
            'pageTitle'       => 'Resource Requests & Transfers',
            'activePage'      => 'resources',
            'requests'        => $requests,
            'requestsTotal'   => $total,
            'filterStatus'    => $status,
            'filterCategory'  => $category,
            'filterSearch'    => $search,
            'role'            => session()->get('role') ?? '',
        ]);
    }

    // ── Detail ────────────────────────────────────────────────────────────

    public function show($id)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');

        try {
            $req = $this->requestModel->getById($id);
        } catch (\Throwable $e) {
            log_message('error', 'ResourceRequests::show - ' . $e->getMessage());
            $req = null;
        }

        if (!$req) {
            return redirect()->to('requests')->with('error', 'Request not found.');
        }

        // Resolve resource_name when it is missing (request stored model doc ID instead of name).
        if (empty($req['resource_name']) || empty($req['requested_model_name'])) {
            $modelDocId = trim($req['requested_model_id'] ?? $req['resource_id'] ?? '');
            if ($modelDocId !== '') {
                try {
                    $modelSnap = $this->firestore->getDb()->collection('resource')->document($modelDocId)->snapshot();
                    if ($modelSnap->exists()) {
                        $resolvedName = strtoupper(trim((string)($modelSnap->data()['name'] ?? '')));
                        if ($resolvedName !== '') {
                            $req['resource_name']         = $req['resource_name']         ?: $resolvedName;
                            $req['requested_model_name']  = $req['requested_model_name']  ?: $resolvedName;
                        }
                    }
                } catch (\Throwable) {}
            }
        }

        // Fetch unit serial number for the header display.
        // Fast path: unit_serial stored on request doc (new requests + backfilled on approval).
        // Fallback: reserved_resources[0] → item doc lookup for legacy requests.
        $req['_unit_serial'] = strtoupper(trim((string)($req['unit_serial'] ?? '')));
        $reserved0 = is_array($req['reserved_resources'] ?? null) ? ($req['reserved_resources'][0] ?? null) : null;
        if ($reserved0 !== null) {
            // reserved_resources entry has display_name = serial_number ?: unitDocId.
            // Fetch the actual serial_number from the item doc using the stored resource_id + model_id.
            $rrUnitId  = trim((string)($reserved0['resource_id'] ?? ''));
            $rrModelId = trim((string)($reserved0['model_id']    ?? ''));
            if ($rrUnitId !== '' && $rrModelId !== '') {
                try {
                    $snap = $this->firestore->getDb()
                        ->collection('resource')->document($rrModelId)
                        ->collection('items')->document($rrUnitId)->snapshot();
                    if ($snap->exists()) {
                        $req['_unit_serial'] = strtoupper(trim((string)($snap->data()['serial_number'] ?? '')));
                    }
                } catch (\Throwable) {}
            }
        }
        if ($req['_unit_serial'] === '') {
            // Pre-approval: try source_resource_id (web DnD) or unit_id (mobile)
            $unitDocId  = trim($req['source_resource_id'] ?? $req['unit_id'] ?? '');
            $modelDocId = trim($req['requested_model_id'] ?? $req['resource_id'] ?? '');
            if ($unitDocId !== '' && $modelDocId !== '') {
                try {
                    $snap = $this->firestore->getDb()
                        ->collection('resource')->document($modelDocId)
                        ->collection('items')->document($unitDocId)->snapshot();
                    if ($snap->exists()) {
                        $req['_unit_serial'] = strtoupper(trim((string)($snap->data()['serial_number'] ?? '')));
                    }
                } catch (\Throwable) {}
            }
        }

        // One collection-group query to find every project that has ≥1 emergency report
        // (mobile writes to the subcollection only; the project-level flag is unreliable)
        $projectsWithEmergency = [];
        try {
            $emergencyDocs = $this->firestore->getDb()
                ->collectionGroup('emergency_reports')->documents();
            foreach ($emergencyDocs as $doc) {
                if (!$doc->exists()) continue;
                if ($doc->data()['resolved'] ?? false) continue; // skip resolved emergencies
                $parts   = explode('/', $doc->name());
                $projIdx = array_search('project', $parts);
                if ($projIdx !== false && isset($parts[$projIdx + 1])) {
                    $projectsWithEmergency[$parts[$projIdx + 1]] = true;
                }
            }
        } catch (\Throwable $e) {
            log_message('warning', 'ResourceRequests::show emergencyCheck - ' . $e->getMessage());
        }

        $projects = [];
        try {
            $raw = $this->projectModel->getAllProjects();
            foreach ($raw as $p) {
                $geoRaw = $p['location']['geopoint'] ?? null;
                if ($geoRaw instanceof \Google\Cloud\Core\GeoPoint) {
                    $lat = $geoRaw->latitude();
                    $lng = $geoRaw->longitude();
                } elseif (is_array($geoRaw)) {
                    $lat = isset($geoRaw['lat']) ? (float)$geoRaw['lat'] : null;
                    $lng = isset($geoRaw['lng']) ? (float)$geoRaw['lng'] : null;
                } else {
                    $lat = null;
                    $lng = null;
                }
                $projects[] = [
                    'id'            => $p['id'],
                    'label'         => strtoupper(trim($p['project_name'] ?? $p['id'])),
                    'lat'           => $lat,
                    'lng'           => $lng,
                    'status'        => (string)($p['status'] ?? ''),
                    'has_emergency' => isset($projectsWithEmergency[$p['id']]),
                    // kept for ML feature building
                    'progress'      => (float)($p['overall_progress'] ?? 0),
                    'start_date'    => $p['start_date'] ?? null,
                    'end_date'      => $p['end_date']   ?? null,
                    'resources'     => $p['resources']  ?? [],
                ];
            }
        } catch (\Throwable $e) {
            log_message('warning', 'ResourceRequests::show projects - ' . $e->getMessage());
        }

        $recommendedSites   = [];
        $recommendedBest    = null;
        $recommendedNearest = null;
        $mcdaExcludedSites  = [];
        $mcdaMlAvailable    = false;

        $requestedModelId = $req['requested_model_id'] ?? '';
        $reqCategory      = strtolower($req['resource_category'] ?? '');

        // SE-released units per project (status='Released' on mobile — display only)
        $releasedUnitsByProject  = [];
        // Allocatable units per project (not transfer-locked / not pending transfer)
        $availableUnitsByProject = [];

        if ($requestedModelId && in_array($reqCategory, ['equipment', 'tools'])) {
            try {
                $allItems = $this->firestore->getDb()
                    ->collection('resource')->document($requestedModelId)
                    ->collection('items')->documents();
                foreach ($allItems as $item) {
                    if (!$item->exists()) continue;
                    $d    = $item->data();
                    $asgn = $d['assigned_project_id'] ?? null;
                    $pid  = $asgn instanceof \Google\Cloud\Firestore\DocumentReference
                        ? $asgn->id()
                        : (is_string($asgn) ? (ltrim(strrchr($asgn, '/'), '/') ?: $asgn) : null);
                    if (!$pid) continue;
                    // SE-released count (for display)
                    if (($d['status'] ?? '') === 'Released') {
                        $releasedUnitsByProject[$pid] = ($releasedUnitsByProject[$pid] ?? 0) + 1;
                    }
                    // Allocatable: not locked in an active transfer
                    if (!($d['transfer_locked'] ?? false)) {
                        $availableUnitsByProject[$pid] = ($availableUnitsByProject[$pid] ?? 0) + 1;
                    }
                }
            } catch (\Throwable $e) {
                log_message('warning', 'ResourceRequests::show unitCount equipment/tools - ' . $e->getMessage());
            }
        } elseif ($reqCategory === 'manpower') {
            try {
                $allMp = $this->firestore->getDb()->collection('manpower')->documents();
                foreach ($allMp as $item) {
                    if (!$item->exists()) continue;
                    $d    = $item->data();
                    $asgn = $d['assigned_project_id'] ?? null;
                    $pid  = $asgn instanceof \Google\Cloud\Firestore\DocumentReference
                        ? $asgn->id()
                        : (is_string($asgn) ? (ltrim(strrchr($asgn, '/'), '/') ?: $asgn) : null);
                    if (!$pid) continue;
                    $st = strtolower($d['status'] ?? '');
                    // SE-released (manpower uses Transfer Pending as the lock signal)
                    if ($st === 'released') {
                        $releasedUnitsByProject[$pid] = ($releasedUnitsByProject[$pid] ?? 0) + 1;
                    }
                    // Allocatable: deployed or available (not pending transfer, not on-leave, not archived)
                    if (in_array($st, ['available', 'deployed'])) {
                        $availableUnitsByProject[$pid] = ($availableUnitsByProject[$pid] ?? 0) + 1;
                    }
                }
            } catch (\Throwable $e) {
                log_message('warning', 'ResourceRequests::show unitCount manpower - ' . $e->getMessage());
            }
        }

        if (in_array(strtolower($req['status'] ?? ''), ['pending', 'pending secondary approval'])) {
            try {
                $destPid = $req['destination_project_id'] ?? '';
                $siteLat = 14.5995;
                $siteLng = 120.9842;

                $candidates = [];
                foreach ($projects as $proj) {
                    if ($proj['lat'] === null || $proj['lng'] === null) continue;
                    if ($proj['id'] === $destPid) {
                        $siteLat = (float)$proj['lat'];
                        $siteLng = (float)$proj['lng'];
                    } elseif ($proj['status'] === 'On-going' && !$proj['has_emergency']) {
                        $candidates[] = [
                            'project_id'      => $proj['id'],
                            'project_name'    => $proj['label'],
                            'lat'             => (float)$proj['lat'],
                            'lng'             => (float)$proj['lng'],
                            'units_released'  => $releasedUnitsByProject[$proj['id']]  ?? 0,
                            'units_available' => $availableUnitsByProject[$proj['id']] ?? 0,
                            'progress'        => $proj['progress']   ?? 0.0,
                            'start_date'      => $proj['start_date'] ?? null,
                            'end_date'        => $proj['end_date']   ?? null,
                            'resources'       => $proj['resources']  ?? [],
                        ];
                    }
                }

                if (!empty($candidates)) {
                    $mcdaResult = $this->computeMcda($candidates, $siteLat, $siteLng, $requestedModelId, $reqCategory);
                    foreach ($mcdaResult['ranked'] as $s) {
                        $recommendedSites[] = [
                            'project_id'         => $s['project_id'],
                            'project_name'       => $s['project_name'],
                            'distance_km'        => $s['distance_km'],
                            'eta_min'            => $s['travel_time_min'],
                            'traffic_label'      => $s['traffic_label'],
                            'traffic_score'      => $s['traffic_score'],
                            'units_available'    => $s['units_available'],
                            'units_released'     => $s['units_released'],
                            'next_available_min' => $s['next_available_min'],
                            'ml_delay_days'      => $s['ml_delay_days'],
                            'ml_blocker'         => $s['ml_blocker'],
                            'wsm_score'          => $s['wsm_score'],
                        ];
                    }
                    $recommendedBest        = $recommendedSites[0] ?? null;
                    $recommendedNearest     = $recommendedBest;
                    $mcdaExcludedSites      = $mcdaResult['excluded']      ?? [];
                    $mcdaMlAvailable        = $mcdaResult['ml_available']  ?? false;
                }
            } catch (\Throwable $e) {
                log_message('warning', 'ResourceRequests::show MCDA - ' . $e->getMessage());
            }
        }

        $currentRole        = session()->get('role') ?? '';
        $canAcceptDelivery  = $currentRole === 'Site Engineer'
                           && strtolower($req['status'] ?? '') === 'in-transit';

        return view('requests/show', [
            'pageTitle'             => 'Request Details',
            'activePage'            => 'resources',
            'request'               => $req,
            'projects'              => $projects,
            'role'                  => $currentRole,
            'recommended_sites'     => $recommendedSites,
            'recommended_best'      => $recommendedBest,
            'recommended_nearest'   => $recommendedNearest,
            'mcda_excluded_sites'   => $mcdaExcludedSites   ?? [],
            'mcda_ml_available'     => $mcdaMlAvailable     ?? false,
            'released_by_project'   => $releasedUnitsByProject,
            'canAcceptDelivery'     => $canAcceptDelivery,
        ]);
    }

    // ── MCDA Source Recommendation ────────────────────────────────────────

    public function recommendSource()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthenticated']);
        }

        $body     = $this->request->getJSON(true) ?: $this->request->getPost();
        $category = trim($body['category'] ?? '');
        $destPid  = trim($body['destination_project_id'] ?? '');
        $modelId  = trim($body['model_id'] ?? '');

        if (!$category || !$destPid) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing category or destination_project_id']);
        }

        try {
            // One collection-group query for emergency status (subcollection is authoritative)
            $withEmergency = [];
            try {
                $eDocs = $this->firestore->getDb()
                    ->collectionGroup('emergency_reports')->documents();
                foreach ($eDocs as $doc) {
                    if (!$doc->exists()) continue;
                    if ($doc->data()['resolved'] ?? false) continue; // skip resolved emergencies
                    $parts   = explode('/', $doc->name());
                    $projIdx = array_search('project', $parts);
                    if ($projIdx !== false && isset($parts[$projIdx + 1])) {
                        $withEmergency[$parts[$projIdx + 1]] = true;
                    }
                }
            } catch (\Throwable $e) {
                log_message('warning', 'ResourceRequests::recommendSource emergencyCheck - ' . $e->getMessage());
            }

            $catLower          = strtolower($category);
            $releasedByProject  = [];   // SE-released (display)
            $availableByProject = [];   // not transfer-locked (MCDA criterion)

            if ($modelId !== '' && in_array($catLower, ['equipment', 'tools'])) {
                try {
                    $allItems = $this->firestore->getDb()
                        ->collection('resource')->document($modelId)
                        ->collection('items')->documents();
                    foreach ($allItems as $item) {
                        if (!$item->exists()) continue;
                        $d    = $item->data();
                        $asgn = $d['assigned_project_id'] ?? null;
                        $pid  = $asgn instanceof \Google\Cloud\Firestore\DocumentReference
                            ? $asgn->id()
                            : (is_string($asgn) ? (ltrim(strrchr($asgn, '/'), '/') ?: $asgn) : null);
                        if (!$pid) continue;
                        if (($d['status'] ?? '') === 'Released') {
                            $releasedByProject[$pid] = ($releasedByProject[$pid] ?? 0) + 1;
                        }
                        if (!($d['transfer_locked'] ?? false)) {
                            $availableByProject[$pid] = ($availableByProject[$pid] ?? 0) + 1;
                        }
                    }
                } catch (\Throwable $e) {
                    log_message('warning', 'ResourceRequests::recommendSource unitCount equipment/tools - ' . $e->getMessage());
                }
            } elseif ($catLower === 'manpower') {
                try {
                    $allMp = $this->firestore->getDb()->collection('manpower')->documents();
                    foreach ($allMp as $item) {
                        if (!$item->exists()) continue;
                        $d    = $item->data();
                        $asgn = $d['assigned_project_id'] ?? null;
                        $pid  = $asgn instanceof \Google\Cloud\Firestore\DocumentReference
                            ? $asgn->id()
                            : (is_string($asgn) ? (ltrim(strrchr($asgn, '/'), '/') ?: $asgn) : null);
                        if (!$pid) continue;
                        $st = strtolower($d['status'] ?? '');
                        if ($st === 'released') {
                            $releasedByProject[$pid] = ($releasedByProject[$pid] ?? 0) + 1;
                        }
                        if (in_array($st, ['available', 'deployed'])) {
                            $availableByProject[$pid] = ($availableByProject[$pid] ?? 0) + 1;
                        }
                    }
                } catch (\Throwable $e) {
                    log_message('warning', 'ResourceRequests::recommendSource unitCount manpower - ' . $e->getMessage());
                }
            }

            $allProjects = $this->projectModel->getAllProjects();
            $destLat = 14.5995; $destLng = 120.9842;
            $candidates = [];

            foreach ($allProjects as $p) {
                $geoRaw = $p['location']['geopoint'] ?? null;
                if ($geoRaw instanceof \Google\Cloud\Core\GeoPoint) {
                    $lat = $geoRaw->latitude(); $lng = $geoRaw->longitude();
                } elseif (is_array($geoRaw) && (isset($geoRaw['lat']) || isset($geoRaw['latitude']))) {
                    $lat = (float)($geoRaw['lat'] ?? $geoRaw['latitude']);
                    $lng = (float)($geoRaw['lng'] ?? $geoRaw['longitude']);
                } else { $lat = null; $lng = null; }

                if ($p['id'] === $destPid) {
                    if ($lat !== null) { $destLat = $lat; $destLng = $lng; }
                } elseif (($p['status'] ?? '') === 'On-going' && !isset($withEmergency[$p['id']]) && $lat !== null) {
                    $candidates[] = [
                        'project_id'      => $p['id'],
                        'project_name'    => strtoupper(trim($p['project_name'] ?? $p['id'])),
                        'lat'             => $lat,
                        'lng'             => $lng,
                        'units_released'  => $releasedByProject[$p['id']]  ?? 0,
                        'units_available' => $availableByProject[$p['id']] ?? 0,
                        'progress'        => (float)($p['overall_progress'] ?? 0),
                        'start_date'      => $p['start_date'] ?? null,
                        'end_date'        => $p['end_date']   ?? null,
                        'resources'       => $p['resources']  ?? [],
                    ];
                }
            }

            if (empty($candidates)) {
                return $this->response->setJSON(['recommendations' => [], 'best' => null]);
            }

            $mcdaResult = $this->computeMcda($candidates, $destLat, $destLng, $modelId, $catLower);

            $recommendations = array_map(fn($s) => [
                'project_id'         => $s['project_id'],
                'project_name'       => $s['project_name'],
                'distance_km'        => $s['distance_km'],
                'travel_time_min'    => $s['travel_time_min'],
                'traffic_label'      => $s['traffic_label'],
                'traffic_score'      => $s['traffic_score'],
                'units_available'    => $s['units_available'],
                'units_released'     => $s['units_released'],
                'next_available_min' => $s['next_available_min'],
                'ml_delay_days'      => $s['ml_delay_days'],
                'ml_blocker'         => $s['ml_blocker'],
                'wsm_score'          => $s['wsm_score'],
            ], $mcdaResult['ranked']);

            return $this->response->setJSON([
                'recommendations'   => $recommendations,
                'best'              => $recommendations[0] ?? null,
                'excluded_count'    => count($mcdaResult['excluded']),
                'ml_available'      => $mcdaResult['ml_available'],
            ]);
        } catch (\Throwable $e) {
            log_message('warning', 'ResourceRequests::recommendSource - ' . $e->getMessage());
            return $this->response->setJSON(['recommendations' => [], 'best' => null]);
        }
    }

    // ── MCDA: 5-criterion WSM + ML risk pre-filter ───────────────────────
    // Step 0  : ML delay/blocker prediction — exclude at-risk projects
    // Step 1  : Next availability from Firestore item timestamps
    // Step 2  : Google Maps Distance Matrix API (live traffic) / Haversine fallback
    // Step 3  : Traffic labels
    // Step 4  : Weighted Sum Model (distance 0.25, travel_time 0.20, traffic 0.25,
    //           availability 0.20, next_available 0.10)
    // Returns : ['ranked'=>[...], 'excluded'=>[...], 'ml_available'=>bool]

    private function computeMcda(
        array  $candidates,
        float  $destLat,
        float  $destLng,
        string $modelId,
        string $catLower
    ): array {
        $nowTs   = time();
        $nowDt   = new \DateTime('now', new \DateTimeZone('Asia/Manila'));

        // ── Step 0: ML delay/blocker filter ─────────────────────────────
        $mlService   = new \App\Libraries\MLServiceClient();
        $mlAvailable = true;
        $excluded    = [];
        $passed      = [];

        foreach ($candidates as $c) {
            $endDate   = $this->tsToDate($c['end_date']   ?? null);
            $startDate = $this->tsToDate($c['start_date'] ?? null);

            $daysElapsed   = $startDate ? max(0, (int)$startDate->diff($nowDt)->days) : 0;
            $daysRemaining = $endDate   ? max(0, (int)$nowDt->diff($endDate)->days)   : 30;
            $totalDays     = max(1, $daysElapsed + $daysRemaining);
            $plannedPct    = min(100.0, round(($daysElapsed / $totalDays) * 100, 2));

            $projRes  = (array)($c['resources'] ?? []);
            $eqTotal  = 0; $eqActive = 0;
            foreach ($projRes as $_r) {
                if (strtolower((string)($_r['category'] ?? '')) === 'manpower') continue;
                $eqTotal++;
                if (empty($_r['transit'])) $eqActive++;
            }
            $eqUtil = $eqTotal > 0 ? round($eqActive / $eqTotal, 2) : 1.0;

            $features = [
                'project_id'               => $c['project_id'],
                'progress_percent'         => (float)($c['progress'] ?? 0),
                'planned_progress_percent' => $plannedPct,
                'days_elapsed'             => $daysElapsed,
                'days_remaining'           => $daysRemaining,
                'workers_present'          => 0,
                'workers_planned'          => 1,
                'equipment_utilization'    => $eqUtil,
                'weather_severity'         => 0,
                'site_access_issues'       => 0,
                'avg_progress_3d'          => (float)($c['progress'] ?? 0),
                'progress_trend_3d'        => 0.0,
                'consecutive_low_days'     => 0,
                'budget_utilization'       => 0.0,
                'cost_variance_ratio'      => 0.0,
                'active_blocker_count'     => 0,
                'days_since_last_blocker'  => 999,
            ];

            $mlDelayDays = 0;
            $mlBlocker   = null;

            if ($mlAvailable) {
                try {
                    $delayRes = $mlService->predictDelay($features);
                    if (isset($delayRes['error'])) {
                        $mlAvailable = false;
                    } else {
                        $mlDelayDays = (int)round((float)(
                            $delayRes['predicted_delay_days'] ?? $delayRes['delay_days'] ?? $delayRes['prediction'] ?? 0
                        ));
                    }
                } catch (\Throwable $e) { $mlAvailable = false; }

                if ($mlAvailable) {
                    try {
                        $blockerRes = $mlService->predictBlockers($features);
                        if (!isset($blockerRes['error'])) {
                            $raw = $blockerRes['blocker_type'] ?? $blockerRes['predicted_blocker'] ?? $blockerRes['prediction'] ?? '';
                            $noBlocker = in_array(strtolower((string)$raw), ['no blocker', 'unknown', 'none', ''], true);
                            $mlBlocker = $noBlocker ? null : (string)$raw;
                        }
                    } catch (\Throwable $e) { /* blocker prediction optional */ }
                }
            }

            $c['ml_delay_days'] = $mlDelayDays;
            $c['ml_blocker']    = $mlBlocker;

            // Exclude if ML predicts delay OR a meaningful blocker
            // Fallback heuristic when ML is down: progress significantly behind schedule
            $isAtRisk = $mlAvailable
                ? ($mlDelayDays > 0 || $mlBlocker !== null)
                : ((float)($c['progress'] ?? 0) < 30.0 && $daysRemaining < 30);

            if ($isAtRisk) {
                $excluded[] = $c;
            } else {
                $passed[] = $c;
            }
        }

        // If ML filter removes everything, fall back to using all candidates
        if (empty($passed) && !empty($excluded)) {
            $passed   = $excluded;
            $excluded = [];
        }

        $candidates = $passed;
        $now        = $nowTs;

        // ── Filter: exclude sites with no allocatable units ───────────────
        $withUnits = [];
        foreach ($candidates as $c) {
            if (($c['units_available'] ?? 0) > 0) {
                $withUnits[] = $c;
            } else {
                $excluded[] = array_merge($c, ['exclude_reason' => 'No allocatable units']);
            }
        }
        $candidates = $withUnits;

        if (empty($candidates)) {
            return ['ranked' => [], 'excluded' => $excluded, 'ml_available' => $mlAvailable];
        }

        // ── Step 1: Next availability per candidate (Firestore items) ────
        foreach ($candidates as &$c) {
            $nextTs = null;
            try {
                if ($modelId !== '' && in_array($catLower, ['equipment', 'tools'])) {
                    $items = $this->firestore->getDb()
                        ->collection('resource')->document($modelId)->collection('items')
                        ->documents();
                    foreach ($items as $item) {
                        if (!$item->exists()) continue;
                        $d   = $item->data();
                        $asgn = $d['assigned_project_id'] ?? null;
                        $pid  = $asgn instanceof \Google\Cloud\Firestore\DocumentReference
                            ? $asgn->id()
                            : (is_string($asgn) ? (ltrim(strrchr($asgn, '/'), '/') ?: $asgn) : null);
                        if ($pid !== $c['project_id']) continue;
                        $na = $d['next_availability'] ?? null;
                        if ($na instanceof \Google\Cloud\Core\Timestamp) {
                            $ts = $na->get()->getTimestamp();
                            if ($nextTs === null || $ts < $nextTs) $nextTs = $ts;
                        }
                    }
                } elseif ($catLower === 'manpower') {
                    $items = $this->firestore->getDb()->collection('manpower')->documents();
                    foreach ($items as $item) {
                        if (!$item->exists()) continue;
                        $d   = $item->data();
                        $asgn = $d['assigned_project_id'] ?? null;
                        $pid  = $asgn instanceof \Google\Cloud\Firestore\DocumentReference
                            ? $asgn->id()
                            : (is_string($asgn) ? (ltrim(strrchr($asgn, '/'), '/') ?: $asgn) : null);
                        if ($pid !== $c['project_id']) continue;
                        $na = $d['next_availability'] ?? null;
                        if ($na instanceof \Google\Cloud\Core\Timestamp) {
                            $ts = $na->get()->getTimestamp();
                            if ($nextTs === null || $ts < $nextTs) $nextTs = $ts;
                        }
                    }
                }
            } catch (\Throwable $e) {
                log_message('warning', 'MCDA nextAvailability - ' . $e->getMessage());
            }
            $c['next_available_min'] = ($nextTs !== null && $nextTs > $now)
                ? (int)round(($nextTs - $now) / 60)
                : 0;
        }
        unset($c);

        // ── Step 2: Google Maps Distance Matrix API (batch, live traffic) ─
        $mapsKey = (new \Config\GoogleMaps())->serverKey;
        if ($mapsKey !== '') {
            try {
                $origins = implode('|', array_map(fn($c) => $c['lat'] . ',' . $c['lng'], $candidates));
                $url     = 'https://maps.googleapis.com/maps/api/distancematrix/json?' . http_build_query([
                    'origins'        => $origins,
                    'destinations'   => $destLat . ',' . $destLng,
                    'departure_time' => 'now',
                    'traffic_model'  => 'best_guess',
                    'key'            => $mapsKey,
                ]);
                $gmData = json_decode(file_get_contents($url), true) ?? [];
                $rows   = $gmData['rows'] ?? [];

                foreach ($candidates as $i => &$c) {
                    $el = $rows[$i]['elements'][0] ?? [];
                    if (($el['status'] ?? '') === 'OK') {
                        $durSec        = (int)($el['duration']['value'] ?? 0);
                        $durTrafficSec = (int)($el['duration_in_traffic']['value'] ?? $durSec);
                        $c['distance_km']     = round((int)($el['distance']['value'] ?? 0) / 1000, 1);
                        $c['travel_time_min'] = max(1, (int)round($durSec / 60));
                        $c['traffic_score']   = $durTrafficSec > 0
                            ? min(1.0, round($durSec / $durTrafficSec, 3))
                            : 1.0;
                    } else {
                        $c['distance_km']     = round($this->haversine($c['lat'], $c['lng'], $destLat, $destLng), 1);
                        $c['travel_time_min'] = max(1, (int)round($c['distance_km'] / 60 * 60));
                        $c['traffic_score']   = 1.0;
                    }
                }
                unset($c);
            } catch (\Throwable $e) {
                log_message('warning', 'MCDA GoogleMaps - ' . $e->getMessage());
                foreach ($candidates as &$c) {
                    $c['distance_km']     = round($this->haversine($c['lat'], $c['lng'], $destLat, $destLng), 1);
                    $c['travel_time_min'] = max(1, (int)round($c['distance_km'] / 60 * 60));
                    $c['traffic_score']   = 1.0;
                }
                unset($c);
            }
        } else {
            foreach ($candidates as &$c) {
                $c['distance_km']     = round($this->haversine($c['lat'], $c['lng'], $destLat, $destLng), 1);
                $c['travel_time_min'] = max(1, (int)round($c['distance_km'] / 60 * 60));
                $c['traffic_score']   = 1.0;
            }
            unset($c);
        }

        foreach ($candidates as &$c) {
            if ($c['traffic_score'] >= 0.85)     $c['traffic_label'] = 'Light';
            elseif ($c['traffic_score'] >= 0.65) $c['traffic_label'] = 'Moderate';
            else                                  $c['traffic_label'] = 'Heavy';
        }
        unset($c);

        // ── Step 3: WSM — normalize each criterion then apply weights ────
        // availability uses units_available (not transfer-locked) as the count
        // of units that can actually be allocated from each source project.
        $w = ['distance' => 0.25, 'travel_time' => 0.25, 'traffic' => 0.15,
              'availability' => 0.20, 'next_available' => 0.15];

        $dMin   = min(array_column($candidates, 'distance_km'));
        $dMax   = max(array_column($candidates, 'distance_km'));
        $tMin   = min(array_column($candidates, 'travel_time_min'));
        $tMax   = max(array_column($candidates, 'travel_time_min'));
        $aMin   = min(array_column($candidates, 'units_available'));
        $aMax   = max(array_column($candidates, 'units_available'));
        $nMin   = min(array_column($candidates, 'next_available_min'));
        $nMax   = max(array_column($candidates, 'next_available_min'));
        $trkMin = min(array_column($candidates, 'traffic_score'));
        $trkMax = max(array_column($candidates, 'traffic_score'));

        $norm = fn($v, $lo, $hi) => ($hi > $lo) ? ($v - $lo) / ($hi - $lo) : 0.5;

        foreach ($candidates as &$c) {
            $c['wsm_score'] = round(
                $w['distance']       * (1 - $norm($c['distance_km'],        $dMin,   $dMax))   +
                $w['travel_time']    * (1 - $norm($c['travel_time_min'],    $tMin,   $tMax))   +
                $w['traffic']        * $norm($c['traffic_score'],           $trkMin, $trkMax)  +
                $w['availability']   * $norm($c['units_available'],         $aMin,   $aMax)    +
                $w['next_available'] * (1 - $norm($c['next_available_min'], $nMin,   $nMax)),
                4
            );
        }
        unset($c);

        usort($candidates, fn($a, $b) => $b['wsm_score'] <=> $a['wsm_score']);

        return [
            'ranked'       => $candidates,
            'excluded'     => $excluded,
            'ml_available' => $mlAvailable,
        ];
    }

    private function tsToDate(mixed $ts): ?\DateTime
    {
        if ($ts instanceof \Google\Cloud\Core\Timestamp) {
            return \DateTime::createFromInterface($ts->get());
        }
        if (is_string($ts) && $ts !== '') {
            try { return new \DateTime($ts); } catch (\Throwable $e) { return null; }
        }
        return null;
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R   = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a   = sin($dLat / 2) ** 2
             + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $R * 2 * asin(sqrt($a));
    }

    // ── Create (drag-and-drop or API) ─────────────────────────────────────

    public function store()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->jsonError('Unauthenticated', 401);
        }

        $body = $this->request->getJSON(true) ?: $this->request->getPost();

        $category      = trim($body['resource_category'] ?? $body['category'] ?? '');
        $resName       = trim($body['resource_name'] ?? $body['requested_model_name'] ?? '');
        $sourcePid     = trim($body['source_project_id'] ?? $body['source_site_id'] ?? '');
        $sourceName    = trim($body['source_project_name'] ?? $body['source_site_name'] ?? '');
        $destPid       = trim($body['destination_project_id'] ?? $body['destination_site_id'] ?? '');
        $destName      = trim($body['destination_project_name'] ?? $body['destination_site_name'] ?? '');
        $requestKind   = trim($body['request_kind'] ?? $body['type'] ?? 'manual');
        $qty           = max(1, (int)($body['requested_quantity'] ?? $body['quantity'] ?? 1));
        $modelId       = trim($body['requested_model_id'] ?? $body['resource_id'] ?? '');
        $sourceResId   = trim($body['source_resource_id'] ?? $body['unit_id'] ?? '');
        $sourceResCode = trim($body['source_resource_code'] ?? '');
        $manpowerType  = trim($body['manpower_type'] ?? '');

        if (!$category || !$destPid) {
            return $this->jsonError('Missing required fields.');
        }

        $user = session()->get('userName')   ?? 'Unknown';
        $role = session()->get('role')       ?? 'Unknown';
        $uid  = session()->get('userId')     ?? '';

        // Resolve project names if not provided
        if (!$sourceName && $sourcePid) {
            try {
                $p = $this->projectModel->getProjectById($sourcePid);
                $sourceName = strtoupper(trim($p['project_name'] ?? $sourcePid));
            } catch (\Throwable $e) { $sourceName = $sourcePid; }
        }
        if (!$destName && $destPid) {
            try {
                $p = $this->projectModel->getProjectById($destPid);
                $destName = strtoupper(trim($p['project_name'] ?? $destPid));
            } catch (\Throwable $e) { $destName = $destPid; }
        }

        $cat = ucfirst(strtolower($category));

        // Resolve serial number for single-unit DnD transfers
        $unitSerial = '';
        if ($sourceResId !== '' && $modelId !== '') {
            try {
                $itemSnap = $this->firestore->getDb()
                    ->collection('resource')->document($modelId)
                    ->collection('items')->document($sourceResId)->snapshot();
                if ($itemSnap->exists()) {
                    $unitSerial = strtoupper(trim((string)($itemSnap->data()['serial_number'] ?? '')));
                }
            } catch (\Throwable) {}
        }

        $data = [
            'request_kind'           => $requestKind,
            'resource_category'      => $cat,
            'resource_name'          => $resName,
            'requested_model_id'     => $modelId ?: null,
            'requested_model_name'   => $resName ?: null,
            'unit_serial'            => $unitSerial ?: null,
            'manpower_type'          => $manpowerType ?: null,
            'source_resource_id'     => $sourceResId ?: null,
            'source_resource_code'   => $sourceResCode ?: null,
            'requested_quantity'     => $qty,
            'source_project_id'      => $sourcePid ?: null,
            'source_site_id'         => $sourcePid ?: null,
            'source_site_name'       => $sourcePid ? $sourceName : null,
            'destination_project_id' => $destPid,
            'destination_site_id'    => $destPid,
            'destination_site_name'  => $destName,
            'requested_by_uid'       => $uid,
            'requested_by_name'      => $user,
            'requested_by_role'      => $role,
        ];

        try {
            $id = $this->requestModel->create($data);

            $this->notifyUsersWithRoles(
                ['Administrator', 'Operations Manager', 'Project Manager'],
                'toolRequested',
                'New Resource Request',
                "{$user} requested {$qty}x {$resName} for {$destName}",
                ['request_id' => $id],
                excludeUserId: $uid
            );

            audit_log('request.create', 'request', $id, "{$cat}: {$resName} → {$destName}", [
                'resource_category' => $cat,
                'requested_quantity' => $qty,
            ]);

            return $this->response->setJSON(['success' => true, 'id' => $id]);
        } catch (\Throwable $e) {
            log_message('error', 'ResourceRequests::store - ' . $e->getMessage());
            return $this->jsonError('Failed to create request.');
        }
    }

    // ── Approve ───────────────────────────────────────────────────────────

    public function approve($id)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');

        $role = session()->get('role') ?? '';
        $allowed = ['Administrator', 'Operations Manager', 'Project Manager'];
        if (!in_array($role, $allowed)) {
            return redirect()->to('requests/' . $id)->with('error', 'You do not have permission to approve requests.');
        }

        $sourcePid    = trim($this->request->getPost('approved_source_project_id') ?? '');
        $sourceName   = trim($this->request->getPost('approved_source_project_name') ?? '');
        $bypassSecond = (bool)$this->request->getPost('bypass_secondary');

        $req = $this->requestModel->getById($id);
        if (!$req) return redirect()->to('requests')->with('error', 'Request not found.');

        if ($req['status'] !== 'Pending') {
            return redirect()->to('requests/' . $id)->with('error', 'Request is no longer pending.');
        }

        // Self-approval block: requester cannot approve their own request (Admin exempt)
        $uid  = session()->get('userId')   ?? '';
        $user = session()->get('userName') ?? '';
        $requesterUid = (string)($req['requested_by_uid'] ?? '');
        if ($uid && $requesterUid && $uid === $requesterUid && $role !== 'Administrator') {
            return redirect()->to('requests/' . $id)->with('error', 'You cannot approve a request you submitted.');
        }

        // Resolve source project name if missing
        if ($sourcePid && !$sourceName) {
            try {
                $p = $this->projectModel->getProjectById($sourcePid);
                $sourceName = strtoupper(trim($p['project_name'] ?? $sourcePid));
            } catch (\Throwable $e) { $sourceName = $sourcePid; }
        }

        // Admin bypass only allowed for Administrator role
        if ($bypassSecond && $role !== 'Administrator') {
            $bypassSecond = false;
        }

        // Resolve final source values (prefer submitted, fall back to what's stored on the request)
        $finalSrcPid  = $sourcePid ?: ($req['source_project_id'] ?? $req['source_site_id'] ?? '');
        $finalSrcName = $sourceName ?: ($req['source_site_name'] ?? '');

        try {
            $resName       = (string)($req['requested_model_name'] ?? $req['resource_name'] ?? 'resource');
            $requesterUid  = (string)($req['requested_by_uid'] ?? '');
            $requesterName = (string)($req['requested_by_name'] ?? 'Someone');
            $svc           = new NotificationService();

            if ($bypassSecond) {
                // Admin bypass: write source then finalize directly to In-Transit
                $this->requestModel->update($id, [
                    'source_project_id' => $finalSrcPid,
                    'source_site_id'    => $finalSrcPid,
                    'source_site_name'  => $finalSrcName,
                ]);

                $this->_finalizeTransfer(
                    array_merge($req, [
                        'source_project_id' => $finalSrcPid,
                        'source_site_id'    => $finalSrcPid,
                        'source_site_name'  => $finalSrcName,
                    ]),
                    $uid, $user, $role, true
                );

                if ($requesterUid) {
                    $svc->send($requesterUid, 'requestApproved', 'Request Approved',
                        'Your request has been fully approved.', ['request_id' => $id]);
                }
            } else {
                // Primary approval — write primary_approval nested object, no resource locking yet
                $this->requestModel->update($id, [
                    'status'          => 'Pending Secondary Approval',
                    'approval_stage'  => 'Secondary',
                    'primary_approval' => [
                        'uid'         => $uid,
                        'name'        => $user,
                        'role'        => $role,
                        'approved_at' => (new \DateTime())->format('c'),
                    ],
                    'source_project_id' => $finalSrcPid,
                    'source_site_id'    => $finalSrcPid,
                    'source_site_name'  => $finalSrcName,
                ]);

                if ($requesterUid) {
                    $svc->send($requesterUid, 'requestApproved', 'Request Approved',
                        'Your request has been approved (1/2) — awaiting secondary.',
                        ['request_id' => $id]);
                }

                $this->notifyUsersWithRoles(
                    ['Administrator', 'Operations Manager', 'Project Manager'],
                    'secondaryApproval',
                    'Secondary Approval Needed',
                    "A resource request from {$requesterName} is awaiting your approval.",
                    ['request_id' => $id],
                    excludeUserId: $uid,
                    excludeRole: $role
                );
            }

            audit_log('request.approve', 'request', $id, $resName, [
                'bypass_secondary' => $bypassSecond,
            ]);

            $msg = $bypassSecond ? 'Request approved.' : 'Request approved — awaiting secondary approval.';
            return redirect()->to('requests/' . $id)->with('success', $msg);
        } catch (\Throwable $e) {
            log_message('error', 'ResourceRequests::approve - ' . $e->getMessage());
            return redirect()->to('requests/' . $id)->with('error', 'Failed to approve request.');
        }
    }

    // ── Reject ────────────────────────────────────────────────────────────

    public function reject($id)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');

        $role    = session()->get('role') ?? '';
        $allowed = ['Administrator', 'Operations Manager', 'Project Manager'];
        if (!in_array($role, $allowed)) {
            return redirect()->to('requests/' . $id)->with('error', 'Not permitted.');
        }

        $req = $this->requestModel->getById($id);
        if (!$req || $req['status'] === 'Rejected') {
            return redirect()->to('requests')->with('error', 'Cannot reject this request.');
        }

        try {
            $this->requestModel->update($id, [
                'status'          => 'Rejected',
                'transfer_state'  => 'Rejected',
                'transfer_locked' => false,
                'rejected_by_uid' => session()->get('userId')   ?? '',
                'rejected_by_name'=> session()->get('userName') ?? '',
                'rejected_by_role'=> $role,
                'rejected_at'     => new Timestamp(new \DateTime()),
            ]);
            audit_log('request.reject', 'request', $id, $req['requested_model_name'] ?? $req['resource_name'] ?? null);
            $this->_autoArchiveRequest($id);
            return redirect()->to('requests')->with('success', 'Request rejected.');
        } catch (\Throwable $e) {
            log_message('error', 'ResourceRequests::reject - ' . $e->getMessage());
            return redirect()->to('requests/' . $id)->with('error', 'Failed to reject request.');
        }
    }

    // ── Cancel (original requester only, while pending) ──────────────────

    public function cancel($id)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');

        $req = $this->requestModel->getById($id);
        if (!$req) return redirect()->to('requests')->with('error', 'Request not found.');

        $uid          = session()->get('userId') ?? '';
        $requesterUid = (string)($req['requested_by_uid'] ?? '');
        if (!$uid || $uid !== $requesterUid) {
            return redirect()->to('requests/' . $id)->with('error', 'You can only cancel your own requests.');
        }

        $status = strtolower($req['status'] ?? '');
        if (!in_array($status, ['pending', 'pending secondary approval'], true)) {
            return redirect()->to('requests/' . $id)->with('error', 'This request can no longer be cancelled.');
        }

        try {
            $this->requestModel->update($id, [
                'status'            => 'Cancelled',
                'transfer_state'    => 'Cancelled',
                'cancelled_by_uid'  => $uid,
                'cancelled_by_name' => session()->get('userName') ?? '',
                'cancelled_at'      => new Timestamp(new \DateTime()),
            ]);
            audit_log('request.cancel', 'request', $id, $req['requested_model_name'] ?? $req['resource_name'] ?? null);
            $this->_autoArchiveRequest($id);
            return redirect()->to('requests')->with('success', 'Request cancelled successfully.');
        } catch (\Throwable $e) {
            log_message('error', 'ResourceRequests::cancel - ' . $e->getMessage());
            return redirect()->to('requests/' . $id)->with('error', 'Failed to cancel request.');
        }
    }

    // ── Secondary approve ─────────────────────────────────────────────────

    public function approveSecondary($id)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');

        $currentRole = session()->get('role') ?? '';
        $allowed = ['Administrator', 'Operations Manager', 'Project Manager'];
        if (!in_array($currentRole, $allowed)) {
            return redirect()->to('requests/' . $id)->with('error', 'You do not have permission to approve requests.');
        }

        $req = $this->requestModel->getById($id);
        if (!$req || $req['status'] !== 'Pending Secondary Approval') {
            return redirect()->to('requests/' . $id)->with('error', 'Request is not awaiting secondary approval.');
        }

        // Self-approval block: requester cannot approve their own request (Admin exempt)
        $uid  = session()->get('userId')   ?? '';
        $user = session()->get('userName') ?? '';
        $requesterUid = (string)($req['requested_by_uid'] ?? '');
        if ($uid && $requesterUid && $uid === $requesterUid && $currentRole !== 'Administrator') {
            return redirect()->to('requests/' . $id)->with('error', 'You cannot approve a request you submitted.');
        }

        // Role must differ from the primary approver (read from primary_approval nested object)
        $primaryRole = $req['primary_approval']['role'] ?? $req['approved_by_role'] ?? '';
        if ($primaryRole && $currentRole === $primaryRole) {
            return redirect()->to('requests/' . $id)->with('error', "A {$primaryRole} already approved this request. A different role must provide the secondary approval.");
        }

        try {

            $this->_finalizeTransfer($req, $uid, $user, $currentRole, false);

            $requesterUid = (string)($req['requested_by_uid'] ?? '');
            if ($requesterUid) {
                (new NotificationService())->send(
                    $requesterUid,
                    'requestApproved',
                    'Request Approved',
                    'Your request has been fully approved.',
                    ['request_id' => $id]
                );
            }

            audit_log('request.approve_secondary', 'request', $id, $req['requested_model_name'] ?? $req['resource_name'] ?? null);

            return redirect()->to('requests/' . $id)->with('success', 'Request fully approved.');
        } catch (\Throwable $e) {
            log_message('error', 'ResourceRequests::approveSecondary - ' . $e->getMessage());
            return redirect()->to('requests/' . $id)->with('error', 'Failed.');
        }
    }

    // ── Pending JSON (for dashboard polling) ─────────────────────────────

    public function pendingJson()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON([]);
        }

        try {
            $all = $this->requestModel->getAll([]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([]);
        }

        $result = [];
        foreach ($all as $r) {
            if (!in_array($r['status'] ?? '', ['Pending', 'Pending Secondary Approval'])) continue;
            $result[] = [
                'id'            => $r['id'],
                'status'        => $r['status'],
                'category'      => strtolower($r['resource_category'] ?? ''),
                'resource_id'   => $r['resource_id']            ?? '',
                'resource_name' => $r['resource_name']          ?? '',
                'source_pid'    => $r['source_project_id']      ?? '',
                'dest_pid'      => $r['destination_project_id'] ?? '',
                'dest_pname'    => $r['destination_site_name']  ?? '',
            ];
        }

        return $this->response->setJSON($result);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function jsonError(string $msg, int $code = 400)
    {
        return $this->response->setStatusCode($code)->setJSON(['success' => false, 'error' => $msg]);
    }

    private function notifyUsersWithRoles(
        array  $roles,
        string $type,
        string $title,
        string $body,
        array  $metadata = [],
        string $excludeUserId = '',
        string $excludeRole   = ''
    ): void {
        try {
            $users = (new Model_Users())->getUsersByRole($roles);
            $svc   = new NotificationService();
            foreach ($users as $user) {
                $uid      = (string)($user['id']   ?? '');
                $userRole = (string)($user['role'] ?? '');
                if (!$uid) continue;
                if ($uid === $excludeUserId) continue;
                if ($excludeRole !== '' && $userRole === $excludeRole) continue;
                $svc->send($uid, $type, $title, $body, $metadata);
            }
        } catch (\Throwable $e) {
            log_message('error', 'ResourceRequests::notifyUsersWithRoles - ' . $e->getMessage());
        }
    }

    /**
     * Final-approval handler — Parts A–D of the transfer spec.
     * Builds reserved_resources, locks resource documents, updates project arrays,
     * and flips the request to In-Transit.
     */
    private function _finalizeTransfer(
        array  $req,
        string $approverUid  = '',
        string $approverName = '',
        string $approverRole = '',
        bool   $bypassSecond = false
    ): void {
        $reqId     = trim($req['id'] ?? '');
        $category  = strtolower(trim($req['resource_category'] ?? $req['category'] ?? ''));
        // For equipment/tools: requested_model_id = resource model doc ID
        // For manpower:        source_resource_id  = manpower doc ID
        $modelId   = trim($req['requested_model_id'] ?? $req['resource_id'] ?? '');
        $unitId    = trim($req['source_resource_id'] ?? $req['unit_id'] ?? '');
        $srcPid    = trim($req['source_project_id'] ?? $req['source_site_id'] ?? '');
        $srcName   = strtoupper(trim($req['source_site_name'] ?? ''));
        $destPid   = trim($req['destination_project_id'] ?? $req['destination_site_id'] ?? '');
        $destName  = trim($req['destination_site_name'] ?? '');
        $modelName = trim($req['requested_model_name'] ?? $req['resource_name'] ?? '');
        $qty       = max(1, (int)($req['requested_quantity'] ?? 1));
        $nowIso    = (new \DateTime())->format('c');

        if (!$destPid) return;

        $db  = $this->firestore->getDb();
        $now = new Timestamp(new \DateTime());

        try {
            // ── Part A: Build reserved_resources ───────────────────────────
            $reservedResources = [];

            $lockFields = [
                ['path' => 'transfer_state',               'value' => 'Transferring'],
                ['path' => 'transfer_locked',              'value' => true],
                ['path' => 'transfer_request_id',          'value' => $reqId],
                ['path' => 'transfer_source_site_id',      'value' => $srcPid],
                ['path' => 'transfer_source_site_name',    'value' => $srcName],
                ['path' => 'transfer_destination_site_id',   'value' => $destPid],
                ['path' => 'transfer_destination_site_name', 'value' => strtoupper($destName)],
                ['path' => 'transfer_locked_at',           'value' => $now],
                ['path' => 'status',                       'value' => 'Transfer Pending'],
                ['path' => 'updated_at',                   'value' => $now],
            ];

            if ($category === 'manpower') {
                $mpId = $unitId ?: $modelId; // manpower doc ID is in source_resource_id

                $mpIds = [];
                if ($mpId !== '') {
                    // Specific worker (drag-drop, or a resolved model id)
                    $mpIds[] = $mpId;
                } else {
                    // Type-based request (e.g. "Request Manpower" modal) — no specific
                    // worker was chosen at creation time. Auto-select up to $qty
                    // AVAILABLE workers whose role/skills match the requested type.
                    $manpowerType = strtoupper(trim((string)($req['manpower_type'] ?? '')));
                    if ($manpowerType !== '') {
                        foreach ($db->collection('manpower')->documents() as $mpDoc) {
                            if (count($mpIds) >= $qty) break;
                            if (!$mpDoc->exists()) continue;
                            $d = $mpDoc->data();
                            if (strtoupper(trim((string)($d['status'] ?? ''))) !== 'AVAILABLE') continue;
                            $dRole   = strtoupper(trim((string)($d['role'] ?? '')));
                            $dSkills = array_map('strtoupper', (array)($d['skills'] ?? []));
                            $matches = $manpowerType === 'FOREMAN'
                                ? $dRole === 'FOREMAN'
                                : in_array($manpowerType, $dSkills, true);
                            if (!$matches) continue;
                            $mpIds[] = $mpDoc->id();
                        }
                    }
                }

                foreach ($mpIds as $mpId) {
                    $mpData = $db->collection('manpower')->document($mpId)->snapshot()->data() ?? [];
                    $fn     = strtoupper(trim((string)($mpData['first_name']  ?? '')));
                    $mn     = strtoupper(trim((string)($mpData['middle_name'] ?? '')));
                    $ln     = strtoupper(trim((string)($mpData['last_name']   ?? '')));
                    $mpName = implode(' ', array_filter([$fn, $mn, $ln], fn($p) => $p !== ''))
                           ?: strtoupper(trim((string)($mpData['name'] ?? '')));
                    $empId  = strtolower(trim((string)($mpData['employee_id'] ?? '')));

                    // match_tokens: employee_id + full name (lower) + first+last (lower, if differs)
                    $fullLower      = strtolower($mpName);
                    $firstLastLower = strtolower(implode(' ', array_filter([$fn, $ln], fn($p) => $p !== '')));
                    $tokens = array_values(array_unique(array_filter(
                        [$empId, $fullLower, $firstLastLower !== $fullLower ? $firstLastLower : ''],
                        fn($t) => $t !== ''
                    )));

                    $reservedResources[] = [
                        'kind'                  => 'manpower',
                        'resource_id'           => $mpId,
                        'model_id'              => null,
                        'equipment_type'        => '',
                        'source_site_id'        => $srcPid,
                        'source_site_name'      => $srcName,
                        'destination_site_id'   => $destPid,
                        'destination_site_name' => $destName,
                        'display_name'          => $mpName,
                        'match_tokens'          => $tokens,
                        'scanned'               => false,
                        'scanned_at'            => null,
                        'scanned_by'            => null,
                    ];

                    // Part C: lock manpower doc + move assigned_project_id immediately
                    $db->collection('manpower')->document($mpId)->update(array_merge($lockFields, [
                        ['path' => 'assigned_project_id', 'value' => $destPid],
                        ['path' => 'location', 'value' => [
                            'address' => strtoupper($destName),
                            'type'    => 'PROJECT SITE',
                        ]],
                    ]));

                    // Part D (manpower): sync project.resources arrays
                    // Remove from source project immediately so it doesn't show in both places
                    if ($srcPid) {
                        $srcSnap    = $db->collection('project')->document($srcPid)->snapshot();
                        $srcCurrent = (array)($srcSnap->data()['resources'] ?? []);
                        $srcFiltered = array_values(array_filter($srcCurrent, fn($r) =>
                            (string)($r['ref_id'] ?? '') !== $mpId
                        ));
                        if (count($srcFiltered) !== count($srcCurrent)) {
                            $db->collection('project')->document($srcPid)->update([
                                ['path' => 'resources', 'value' => $srcFiltered],
                            ]);
                        }
                    }

                    // Add to destination project with transit flags (mirrors equipment pattern)
                    $destSnap    = $db->collection('project')->document($destPid)->snapshot();
                    $destCurrent = (array)($destSnap->data()['resources'] ?? []);
                    $destCurrent = array_values(array_filter($destCurrent, fn($r) =>
                        (string)($r['ref_id'] ?? '') !== $mpId && (string)($r['unit_id'] ?? '') !== $mpId
                    ));
                    $destCurrent[] = [
                        'ref_id'              => $mpId,
                        'unit_id'             => '',
                        'name'                => $mpName,
                        'category'            => 'manpower',
                        'transit'             => true,
                        'transit_incoming'    => true,
                        'transfer_request_id' => $reqId,
                    ];
                    $db->collection('project')->document($destPid)->update([
                        ['path' => 'resources', 'value' => array_values($destCurrent)],
                    ]);
                }

            } elseif (in_array($category, ['equipment', 'tools'])) {
                $resData  = $db->collection('resource')->document($modelId)->snapshot()->data() ?? [];
                $resLabel = strtoupper(trim((string)($resData['name'] ?? $modelName)));
                $resCat   = ucfirst(strtolower($category));
                $itemsRef = $db->collection('resource')->document($modelId)->collection('items');

                $unitLockFields = array_merge($lockFields, [
                    // Do NOT set assigned_project_id yet — that happens on scan confirmation
                ]);

                if ($unitId !== '') {
                    // Specific unit (drag-drop)
                    $itemData = $itemsRef->document($unitId)->snapshot()->data() ?? [];
                    $sn       = strtolower(trim((string)($itemData['serial_number'] ?? '')));
                    $tokens   = array_values(array_filter([$sn], fn($t) => $t !== ''));

                    $reservedResources[] = [
                        'kind'                  => 'unit',
                        'resource_id'           => $unitId,
                        'model_id'              => $modelId,
                        'equipment_type'        => $resLabel,
                        'source_site_id'        => $srcPid,
                        'source_site_name'      => $srcName,
                        'destination_site_id'   => $destPid,
                        'destination_site_name' => $destName,
                        'display_name'          => $sn ?: $unitId,
                        'match_tokens'          => $tokens,
                        'scanned'               => false,
                        'scanned_at'            => null,
                        'scanned_by'            => null,
                    ];

                    // Part C: lock this unit
                    $itemsRef->document($unitId)->update($unitLockFields);

                } else {
                    // Bulk: select $qty In-Storage units
                    $moved = 0;
                    foreach ($itemsRef->documents() as $itemDoc) {
                        if (!$itemDoc->exists() || $moved >= $qty) break;
                        if ((string)($itemDoc->data()['status'] ?? '') !== 'In-Storage') continue;

                        $iId    = $itemDoc->id();
                        $sn     = strtolower(trim((string)($itemDoc->data()['serial_number'] ?? '')));
                        $tokens = array_values(array_filter([$sn], fn($t) => $t !== ''));

                        $reservedResources[] = [
                            'kind'                  => 'unit',
                            'resource_id'           => $iId,
                            'model_id'              => $modelId,
                            'equipment_type'        => $resLabel,
                            'source_site_id'        => $srcPid,
                            'source_site_name'      => $srcName,
                            'destination_site_id'   => $destPid,
                            'destination_site_name' => $destName,
                            'display_name'          => $sn ?: $iId,
                            'match_tokens'          => $tokens,
                            'scanned'               => false,
                            'scanned_at'            => null,
                            'scanned_by'            => null,
                        ];

                        // Part C: lock this unit
                        $itemDoc->reference()->update($unitLockFields);
                        $moved++;
                    }
                }

                // Part D: update project.resources arrays (units only)
                $unitIds = array_column($reservedResources, 'resource_id');

                // Source project: set transit = true on matching unit entries
                if ($srcPid && !empty($unitIds)) {
                    $srcSnap    = $db->collection('project')->document($srcPid)->snapshot();
                    $srcCurrent = (array)($srcSnap->data()['resources'] ?? []);
                    $srcChanged = false;
                    $srcUpdated = array_map(function ($r) use ($unitIds, &$srcChanged) {
                        if (in_array((string)($r['unit_id'] ?? ''), $unitIds, true) && empty($r['transit'])) {
                            $r['transit'] = true;
                            $srcChanged   = true;
                        }
                        return $r;
                    }, $srcCurrent);
                    if ($srcChanged) {
                        $db->collection('project')->document($srcPid)->update([
                            ['path' => 'resources', 'value' => $srcUpdated],
                        ]);
                    }
                }

                // Destination project: remove stale entry, append with transit + transit_incoming flags
                if (!empty($reservedResources)) {
                    $destSnap    = $db->collection('project')->document($destPid)->snapshot();
                    $destCurrent = (array)($destSnap->data()['resources'] ?? []);
                    foreach ($reservedResources as $rr) {
                        $destCurrent = array_values(array_filter(
                            $destCurrent,
                            fn($r) => (string)($r['unit_id'] ?? '') !== $rr['resource_id']
                        ));
                        $destCurrent[] = [
                            'name'                => $resLabel,
                            'category'            => $resCat,
                            'unit_id'             => $rr['resource_id'],
                            'ref_id'              => $modelId,
                            'transit'             => true,
                            'transit_incoming'    => true,
                            'transfer_request_id' => $reqId,
                        ];
                    }
                    $db->collection('project')->document($destPid)->update([
                        ['path' => 'resources', 'value' => array_values($destCurrent)],
                    ]);
                }
            }

            // ── Part B: Update request document ────────────────────────────
            if ($reqId) {
                $existingHistory = is_array($req['approval_history'] ?? null) ? $req['approval_history'] : [];
                $existingHistory[] = [
                    'uid'              => $approverUid,
                    'name'             => $approverName,
                    'role'             => $approverRole,
                    'approved_at'      => $nowIso,
                    'bypass_secondary' => $bypassSecond,
                ];

                // Backfill unit_serial for single-unit requests created before this field existed
                $approvedSerial = '';
                if (count($reservedResources) === 1) {
                    $rr0 = $reservedResources[0];
                    $rr0Sn = strtoupper(trim((string)($rr0['display_name'] ?? '')));
                    // display_name is serial ?: unitDocId; skip if it looks like a raw Firestore ID
                    if ($rr0Sn !== '' && strlen($rr0Sn) < 30) {
                        $approvedSerial = $rr0Sn;
                    }
                }

                $approveUpdate = [
                    'status'             => 'In-Transit',
                    'approval_stage'     => 'Complete',
                    'transfer_state'     => 'In-Transit',
                    'transfer_locked'    => true,
                    'source_project_id'  => $srcPid,
                    'source_site_id'     => $srcPid,
                    'source_site_name'   => $srcName,
                    'reserved_resources' => $reservedResources,
                    'approved_at'        => $now,
                    'approved_by_uid'    => $approverUid,
                    'approved_by_name'   => $approverName,
                    'approved_by_role'   => $approverRole,
                    'approval_history'   => $existingHistory,
                ];
                if ($approvedSerial !== '' && empty($req['unit_serial'])) {
                    $approveUpdate['unit_serial'] = $approvedSerial;
                }
                $this->requestModel->update($reqId, $approveUpdate);
            }
        } catch (\Throwable $e) {
            log_message('error', 'ResourceRequests::_finalizeTransfer - ' . $e->getMessage());
            throw $e; // re-throw so callers can show an error
        }
    }

    public function acceptDelivery(string $id): object
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('auth/login');
        }

        $role = session()->get('role') ?? '';
        if ($role !== 'Site Engineer') {
            return redirect()->to('requests/' . $id)->with('error', 'Only Site Engineers can accept delivery.');
        }

        $req = $this->requestModel->getById($id);
        if (!$req) {
            return redirect()->to('requests')->with('error', 'Request not found.');
        }
        if (strtolower($req['status'] ?? '') !== 'in-transit') {
            return redirect()->to('requests/' . $id)->with('error', 'This request is not awaiting delivery acceptance.');
        }

        $category = strtolower(trim($req['resource_category'] ?? ''));
        // resource_id is not stored on the request; manpower/model ID is in requested_model_id
        $resId    = trim($req['resource_id'] ?? $req['requested_model_id'] ?? '');
        // unit_id is stored as source_resource_id (drag-drop sends it as unit_id, store() maps it)
        $unitId   = trim($req['unit_id'] ?? $req['source_resource_id'] ?? '');
        $srcPid   = trim($req['source_site_id'] ?? $req['source_project_id'] ?? '');
        $destPid  = trim($req['destination_project_id'] ?? '');

        if (!$resId) {
            return redirect()->to('requests/' . $id)->with('error', 'Invalid request data.');
        }

        $db  = $this->firestore->getDb();
        $now = new Timestamp(new \DateTime());

        try {
            // ── Verification step ──────────────────────────────────────────
            if ($category === 'manpower') {
                $mpData     = $db->collection('manpower')->document($resId)->snapshot()->data() ?? [];
                $mn         = strtolower(trim((string)($mpData['middle_name'] ?? '')));
                $fn         = strtolower(trim((string)($mpData['first_name']  ?? '')));
                $ln         = strtolower(trim((string)($mpData['last_name']   ?? '')));
                $storedName = implode(' ', array_filter([$fn, $mn, $ln], fn($p) => $p !== ''))
                           ?: strtolower(trim((string)($mpData['name'] ?? '')));
                $input      = strtolower(trim($this->request->getPost('confirm_name') ?? ''));

                if ($input === '' || $input !== $storedName) {
                    return redirect()->to('requests/' . $id)
                        ->with('error', 'Name does not match. Please verify the worker\'s full name and try again.');
                }
            } elseif (in_array($category, ['equipment', 'tools'])) {
                $itemsRef = $db->collection('resource')->document($resId)->collection('items');
                $inputSN  = strtolower(trim($this->request->getPost('serial_number') ?? ''));

                if ($inputSN === '') {
                    return redirect()->to('requests/' . $id)
                        ->with('error', 'Please enter the serial number or scan the QR/barcode to confirm receipt.');
                }

                if ($unitId !== '') {
                    $itemData = $itemsRef->document($unitId)->snapshot()->data() ?? [];
                    $storedSN = strtolower(trim((string)($itemData['serial_number'] ?? '')));
                    if ($storedSN === '' || $inputSN !== $storedSN) {
                        return redirect()->to('requests/' . $id)
                            ->with('error', 'Serial number does not match. Please verify and try again.');
                    }
                } else {
                    // Bulk: verify that the entered SN belongs to one of the in-transit items
                    $matched = false;
                    foreach ($itemsRef->where('transfer_request_id', '=', $id)->documents() as $itemDoc) {
                        if (!$itemDoc->exists()) continue;
                        $storedSN = strtolower(trim((string)($itemDoc->data()['serial_number'] ?? '')));
                        if ($storedSN !== '' && $inputSN === $storedSN) { $matched = true; break; }
                    }
                    if (!$matched) {
                        return redirect()->to('requests/' . $id)
                            ->with('error', 'Serial number does not match any item in this transfer. Please verify and try again.');
                    }
                }
            }

            // ── Update item / manpower status (arrival) ───────────────────
            $acceptedUnits = [];
            $destName      = trim($req['destination_site_name'] ?? '');

            if ($category === 'manpower') {
                $db->collection('manpower')->document($resId)->update([
                    ['path' => 'status',                 'value' => 'DEPLOYED'],
                    ['path' => 'transfer_request_id',    'value' => null],
                    ['path' => 'transfer_state',         'value' => 'Completed'],
                    ['path' => 'transfer_completed_at',  'value' => $now],
                    ['path' => 'assigned_project_id',    'value' => $destPid],
                    ['path' => 'location',               'value' => [
                        'address' => strtoupper($destName),
                        'type'    => 'PROJECT SITE',
                    ]],
                    ['path' => 'updated_at', 'value' => $now],
                ]);
                $acceptedUnits[] = ['ref_id' => $resId, 'unit_id' => ''];
            } elseif (in_array($category, ['equipment', 'tools'])) {
                $itemsRef = $db->collection('resource')->document($resId)->collection('items');

                $itemArrivalFields = [
                    ['path' => 'status',                 'value' => 'In-Use'],
                    ['path' => 'transfer_request_id',    'value' => null],
                    ['path' => 'transfer_state',         'value' => 'Completed'],
                    ['path' => 'transfer_locked',        'value' => false],
                    ['path' => 'transfer_completed_at',  'value' => $now],
                    ['path' => 'released_at',            'value' => null],
                    ['path' => 'released_by',            'value' => null],
                    ['path' => 'assigned_project_id',    'value' => $destPid],
                    ['path' => 'location',               'value' => [
                        'address' => $destName,
                        'type'    => 'Project Site',
                    ]],
                    ['path' => 'updated_at', 'value' => $now],
                ];

                if ($unitId !== '') {
                    $itemsRef->document($unitId)->update($itemArrivalFields);
                    $acceptedUnits[] = ['ref_id' => $resId, 'unit_id' => $unitId];
                } else {
                    foreach ($itemsRef->where('transfer_request_id', '=', $id)->documents() as $itemDoc) {
                        if (!$itemDoc->exists()) continue;
                        $itemDoc->reference()->update($itemArrivalFields);
                        $acceptedUnits[] = ['ref_id' => $resId, 'unit_id' => $itemDoc->id()];
                    }
                }
            }

            // ── Sync project.resources: remove from source, fix dest entry ──
            if (!empty($acceptedUnits)) {
                if ($category === 'manpower') {
                    $mpId = $acceptedUnits[0]['ref_id'];

                    // SOURCE: remove any entry for this manpower (by ref_id)
                    if ($srcPid) {
                        $srcSnap    = $db->collection('project')->document($srcPid)->snapshot();
                        $srcCurrent = (array)($srcSnap->data()['resources'] ?? []);
                        $srcFiltered = array_values(array_filter($srcCurrent, fn($r) =>
                            (string)($r['ref_id'] ?? '') !== $mpId
                        ));
                        if (count($srcFiltered) !== count($srcCurrent)) {
                            $db->collection('project')->document($srcPid)->update([
                                ['path' => 'resources', 'value' => $srcFiltered],
                            ]);
                        }
                    }

                    // DEST: _finalizeTransfer added a transit placeholder with unit_id=mpId.
                    // Replace it (and any stale ref-only entry) with the canonical entry.
                    if ($destPid) {
                        $mpDoc  = $db->collection('manpower')->document($mpId)->snapshot()->data() ?? [];
                        $fn     = strtoupper(trim((string)($mpDoc['first_name']  ?? '')));
                        $mn     = strtoupper(trim((string)($mpDoc['middle_name'] ?? '')));
                        $ln     = strtoupper(trim((string)($mpDoc['last_name']   ?? '')));
                        $mpName = implode(' ', array_filter([$fn, $mn, $ln], fn($p) => $p !== ''))
                               ?: strtoupper(trim((string)($mpDoc['name'] ?? $mpId)));

                        $destSnap    = $db->collection('project')->document($destPid)->snapshot();
                        $destCurrent = (array)($destSnap->data()['resources'] ?? []);
                        $destFiltered = array_values(array_filter($destCurrent, function ($r) use ($mpId) {
                            // remove transit placeholder (unit_id=mpId) and any existing ref-only entry
                            if ((string)($r['unit_id'] ?? '') === $mpId) return false;
                            if ((string)($r['ref_id'] ?? '') === $mpId && (string)($r['unit_id'] ?? '') === '') return false;
                            return true;
                        }));
                        $destFiltered[] = ['ref_id' => $mpId, 'unit_id' => '', 'name' => $mpName, 'category' => 'manpower'];

                        $db->collection('project')->document($destPid)->update([
                            ['path' => 'resources', 'value' => array_values($destFiltered)],
                        ]);
                    }
                } else {
                    // Equipment / tools: key = ref_id|unit_id
                    $acceptedKeys = [];
                    foreach ($acceptedUnits as $au) {
                        $acceptedKeys[$au['ref_id'] . '|' . $au['unit_id']] = true;
                    }

                    if ($srcPid) {
                        $srcSnap     = $db->collection('project')->document($srcPid)->snapshot();
                        $srcCurrent  = (array)($srcSnap->data()['resources'] ?? []);
                        $srcFiltered = array_values(array_filter($srcCurrent, function ($r) use ($acceptedKeys) {
                            $k = (string)($r['ref_id'] ?? '') . '|' . (string)($r['unit_id'] ?? '');
                            return !isset($acceptedKeys[$k]);
                        }));
                        if (count($srcFiltered) !== count($srcCurrent)) {
                            $db->collection('project')->document($srcPid)->update([
                                ['path' => 'resources', 'value' => $srcFiltered],
                            ]);
                        }
                    }

                    if ($destPid) {
                        $destSnap    = $db->collection('project')->document($destPid)->snapshot();
                        $destCurrent = (array)($destSnap->data()['resources'] ?? []);
                        $destUpdated = array_map(function ($r) use ($acceptedKeys) {
                            $k = (string)($r['ref_id'] ?? '') . '|' . (string)($r['unit_id'] ?? '');
                            if (isset($acceptedKeys[$k])) {
                                unset($r['transit']);
                                unset($r['transit_incoming']);
                            }
                            return $r;
                        }, $destCurrent);
                        $db->collection('project')->document($destPid)->update([
                            ['path' => 'resources', 'value' => array_values($destUpdated)],
                        ]);
                    }
                }
            }

            $this->requestModel->update($id, [
                'status'          => 'Completed',
                'transfer_state'  => 'Completed',
                'transfer_locked' => false,
                'accepted_by'      => session()->get('userId')   ?? '',
                'accepted_by_name' => session()->get('userName') ?? '',
                'accepted_at'      => $now,
                'updated_at'       => $now,
            ]);
            audit_log('request.accept_delivery', 'request', $id, $req['requested_model_name'] ?? $req['resource_name'] ?? null);
            $this->_autoArchiveRequest($id);

            return redirect()->to('requests')->with('success', 'Delivery accepted — resource is now active at the destination project.');
        } catch (\Throwable $e) {
            log_message('error', 'ResourceRequests::acceptDelivery - ' . $e->getMessage());
            return redirect()->to('requests/' . $id)->with('error', 'Failed to accept delivery. Please try again.');
        }
    }

    public function deleted(): string|object
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        $items = (new TrashService($this->firestore))->getTrashItems('request');
        return view('shared/module_deleted', [
            'pageTitle'  => 'Deleted Requests',
            'activePage' => 'requests',
            'userName'   => session()->get('userName') ?? 'User',
            'role'       => session()->get('role') ?? 'Staff',
            'module'     => 'request',
            'title'      => 'Deleted Requests',
            'backUrl'    => base_url('requests'),
            'items'      => $items,
        ]);
    }

    public function archived(): string|object
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        $items = (new ArchiveService($this->firestore))->getArchivedItems('request');
        return view('shared/module_archived', [
            'pageTitle'  => 'Archived Requests',
            'activePage' => 'requests',
            'userName'   => session()->get('userName') ?? 'User',
            'role'       => session()->get('role') ?? 'Staff',
            'module'     => 'request',
            'title'      => 'Archived Requests',
            'backUrl'    => base_url('requests'),
            'items'      => $items,
        ]);
    }

    public function archive(): object
    {
        if (!session()->get('isLoggedIn')) return $this->response->setStatusCode(401)->setJSON(['success' => false]);
        helper('access');
        if (!pz_can('archive_request')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Access denied.']);
        }
        try {
            $ids = (array)($this->request->getPost('ids') ?? []);
            if (empty($ids)) return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'No IDs provided']);
            $db      = $this->firestore->getDb();
            $service = new ArchiveService($this->firestore);
            $user    = session()->get('userName') ?? 'System';
            foreach ($ids as $id) {
                $id   = trim((string)$id);
                if ($id === '') continue;
                $snap = $db->collection('resource_requests')->document($id)->snapshot();
                if (!$snap->exists()) continue;
                $data = $snap->data();
                $name = 'REQ-' . strtoupper(substr($id, 0, 6));
                $service->moveToArchive('request', $id, $name, $data, [], $user);
                $db->collection('resource_requests')->document($id)->delete();
            }
            return $this->response->setJSON(['success' => true]);
        } catch (\Throwable $e) {
            log_message('error', 'ResourceRequests::archive - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Archive failed']);
        }
    }

    /**
     * Sweeps any requests already in a terminal state (Completed/Rejected/Cancelled) into the
     * archive — a safety net for requests that reached that status before auto-archiving existed,
     * or through any path that doesn't already call _autoArchiveRequest(). Status is preserved as-is.
     */
    private function _sweepTerminalRequests(array $requests): array
    {
        $terminal = ['completed', 'rejected', 'cancelled'];
        $kept = [];
        foreach ($requests as $r) {
            if (in_array(strtolower($r['status'] ?? ''), $terminal, true)) {
                $this->_autoArchiveRequest($r['id']);
                continue;
            }
            $kept[] = $r;
        }
        return $kept;
    }

    /** Auto-archives a request once it reaches a terminal state (Completed/Cancelled/Rejected). */
    private function _autoArchiveRequest(string $id): void
    {
        try {
            $db   = $this->firestore->getDb();
            $snap = $db->collection('resource_requests')->document($id)->snapshot();
            if (!$snap->exists()) return;

            $data = $snap->data();
            $name = 'REQ-' . strtoupper(substr($id, 0, 6));
            (new ArchiveService($this->firestore))->moveToArchive(
                'request', $id, $name, $data, [], session()->get('userName') ?? 'System'
            );
            $db->collection('resource_requests')->document($id)->delete();
        } catch (\Throwable $e) {
            log_message('warning', 'ResourceRequests::_autoArchiveRequest - ' . $e->getMessage());
        }
    }

    public function delete(): object
    {
        if (!session()->get('isLoggedIn')) return $this->response->setStatusCode(401)->setJSON(['success' => false]);
        helper('access');
        if (!pz_can('delete_request')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Access denied.']);
        }
        try {
            $ids = (array)($this->request->getPost('ids') ?? []);
            if (empty($ids)) return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'No IDs provided']);
            $db      = $this->firestore->getDb();
            $service = new TrashService($this->firestore);
            $user    = session()->get('userName') ?? 'System';
            foreach ($ids as $id) {
                $id   = trim((string)$id);
                if ($id === '') continue;
                $snap = $db->collection('resource_requests')->document($id)->snapshot();
                if (!$snap->exists()) continue;
                $data = $snap->data();
                $name = 'REQ-' . strtoupper(substr($id, 0, 6));
                $service->moveToTrash('request', $id, $name, $data, [], $user);
                $db->collection('resource_requests')->document($id)->delete();
            }
            return $this->response->setJSON(['success' => true]);
        } catch (\Throwable $e) {
            log_message('error', 'ResourceRequests::delete - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Delete failed']);
        }
    }
}
