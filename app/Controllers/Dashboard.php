<?php

namespace App\Controllers;

use App\Models\Model_Projects;
use App\Models\Model_Resources;
use App\Models\Model_Users;
use App\Models\Model_Manpower;
use App\Models\Model_ResourceRequests;

class Dashboard extends BaseController
{
    protected $projectModel;
    protected $resourceModel;
    protected $userModel;
    protected $manpowerModel;
    protected $requestModel;

    public function __construct()
    {
        $this->projectModel  = new Model_Projects();
        $this->resourceModel = new Model_Resources();
        $this->userModel     = new Model_Users();
        $this->manpowerModel = new Model_Manpower();
        $this->requestModel  = new Model_ResourceRequests();
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
                    if (!in_array($r['status'] ?? '', ['Pending', 'Pending Secondary'])) continue;
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

            // ── Resources ───────────────────────────────────────────
            $totalResources     = $this->resourceModel->countResources();
            $availableResources = $this->resourceModel->getAvailableResources();
            $deployedResources  = $this->resourceModel->getDeployedResources();
            $availableTools     = $this->resourceModel->getAvailableResources();

            // ── Manpower ─────────────────────────────────────────────
            $allManpower = [];
            try {
                $allManpower = $this->manpowerModel->getAllManpower();
            } catch (\Throwable $e) {
                log_message('warning', 'Dashboard: manpower fetch failed - ' . $e->getMessage());
            }
            $availableManpower = array_values(array_filter($allManpower, function ($m) {
                return ($m['status'] ?? '') === 'Available';
            }));

            // ── Build project → assigned-resource maps ────────────────
            // Must be built BEFORE the activeProjects loop so enrichProject sees them.

            // Manpower: assigned_project_id is a plain string doc ID
            $manpowerByProject = [];
            foreach ($allManpower as $m) {
                $apid = trim((string)($m['assigned_project_id'] ?? ''));
                if ($apid === '') continue;
                $fn   = trim((string)($m['first_name'] ?? ''));
                $ln   = trim((string)($m['last_name']  ?? ''));
                $name = strtoupper($fn . ($fn && $ln ? ' ' : '') . $ln) ?: strtoupper($m['name'] ?? '');
                if ($name === '') continue;
                $manpowerByProject[$apid][] = ['name' => $name, 'id' => $m['id'], 'unit_id' => ''];
            }

            // Equipment + Tools: assigned_project_id on the items subcollection
            $equipmentByProject = [];
            $toolsByProject     = [];
            try {
                foreach ($this->resourceModel->getAllResources() as $res) {
                    $resId    = $res['id'];
                    $resName  = strtoupper(trim((string)($res['name'] ?? '')));
                    $category = $res['category'] ?? '';

                    foreach ($this->resourceModel->getResourceItems($resId) as $item) {
                        $apid = $item['assigned_project_id'] ?? null;
                        $itemPid = $apid instanceof \Google\Cloud\Firestore\DocumentReference
                            ? $apid->id()
                            : trim((string)($apid ?? ''));
                        if ($itemPid === '') continue;

                        $unitId = $item['__id'] ?? $item['id'] ?? '';
                        $pill   = ['name' => $resName, 'id' => $resId, 'unit_id' => $unitId];

                        if ($category === 'Tools') {
                            $toolsByProject[$itemPid][] = $pill;
                        } else {
                            $equipmentByProject[$itemPid][] = $pill;
                        }
                    }
                }
            } catch (\Throwable $e) {
                log_message('warning', 'Dashboard: resource item scan failed - ' . $e->getMessage());
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
                'availableTools'      => $availableTools,
                'availableResources'  => $availableResources,
                'deployedResources'   => $deployedResources,
                'availableManpower'   => $availableManpower,
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
                'blockers'         => [],
                'availableTools'   => [],
                'availableResources'  => [],
                'deployedResources'   => [],
                'availableManpower'   => [],
                'errorMessage'     => 'Unable to load dashboard right now.',
            ]);
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

        // Source 1: inline resources field (written by mobile drag-and-drop)
        if (!empty($p['resources']) && is_array($p['resources'])) {
            foreach ($p['resources'] as $res) {
                $cat  = strtolower(trim((string)($res['category'] ?? 'tools')));
                $name = strtoupper(trim((string)($res['name'] ?? '')));
                if ($name === '') continue;
                $pill = [
                    'name'    => $name,
                    'id'      => (string)($res['ref_id']  ?? ''),
                    'unit_id' => (string)($res['unit_id'] ?? ''),
                ];
                if ($cat === 'manpower')      $manpower[]  = $pill;
                elseif ($cat === 'equipment') $equipment[] = $pill;
                else                          $tools[]     = $pill;
            }
        }

        // Source 2: web-assigned resources (assigned_project_id on manpower/item docs)
        // Deduplicate by name+unit_id to avoid double-counting
        $seen = [];
        $merge = function (array &$target, array $incoming) use (&$seen): void {
            foreach ($incoming as $pill) {
                $key = ($pill['id'] ?? '') . '|' . ($pill['unit_id'] ?? '') . '|' . strtolower($pill['name'] ?? '');
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                $target[] = $pill;
            }
        };

        // Seed seen set with pills already added from Source 1
        foreach ($equipment as $pill) $seen[($pill['id']??'').'|'.($pill['unit_id']??'').'|'.strtolower($pill['name']??'')] = true;
        foreach ($tools     as $pill) $seen[($pill['id']??'').'|'.($pill['unit_id']??'').'|'.strtolower($pill['name']??'')] = true;
        foreach ($manpower  as $pill) $seen[($pill['id']??'').'|'.($pill['unit_id']??'').'|'.strtolower($pill['name']??'')] = true;

        $merge($equipment, $assignedResources['equipment'] ?? []);
        $merge($tools,     $assignedResources['tools']     ?? []);
        $merge($manpower,  $assignedResources['manpower']  ?? []);

        $p['_equipment']    = $equipment;
        $p['_tools']        = $tools;
        $p['_manpower']     = $manpower;
        $p['_client']       = strtoupper(trim((string)($p['client_name'] ?? '')));
        $p['_project_type'] = strtoupper(trim((string)($p['project_type'] ?? '')));
        $rawStatus          = strtoupper(trim((string)($p['status'] ?? '')));
        $p['_status']       = in_array($rawStatus, ['COMPLETED', 'DONE']) ? 'DONE' : 'ONGOING';

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
