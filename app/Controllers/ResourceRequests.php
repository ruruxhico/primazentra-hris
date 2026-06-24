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
        } catch (\Throwable $e) {
            log_message('error', 'ResourceRequests::index - ' . $e->getMessage());
            $requests = [];
        }

        $perPage     = config('Pager')->perPage;
        $currentPage = max(1, (int) ($this->request->getGet('page') ?? 1));
        $total       = count($requests);
        $pager       = service('pager');
        $pager->store('requests', $currentPage, $perPage, $total);
        $requests = array_slice($requests, ($currentPage - 1) * $perPage, $perPage);

        $pagerOffset   = ($currentPage - 1) * $perPage;
        $totalPages    = $total > 0 ? (int)ceil($total / $perPage) : 1;

        return view('requests/index', [
            'pageTitle'       => 'Resource Requests & Transfers',
            'activePage'      => 'resources',
            'requests'        => $requests,
            'requestsTotal'   => $total,
            'perPage'         => $perPage,
            'pagerOffset'     => $pagerOffset,
            'requestsTotalPages' => $totalPages,
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

        $projects = [];
        try {
            $raw = $this->projectModel->getAllProjects();
            foreach ($raw as $p) {
                $geopoint = $p['location']['geopoint'] ?? [];
                $projects[] = [
                    'id'    => $p['id'],
                    'label' => strtoupper(trim($p['project_name'] ?? $p['id'])),
                    'lat'   => isset($geopoint['lat']) ? (float)$geopoint['lat'] : null,
                    'lng'   => isset($geopoint['lng']) ? (float)$geopoint['lng'] : null,
                ];
            }
        } catch (\Throwable $e) {
            log_message('warning', 'ResourceRequests::show projects - ' . $e->getMessage());
        }

        $recommendedBest    = null;
        $recommendedNearest = null;

        if (in_array(strtolower($req['status'] ?? ''), ['pending', 'pending secondary approval'])) {
            try {
                $mlService = new MLServiceClient();
                $destPid   = $req['destination_project_id'] ?? '';
                $siteLat   = 14.5995;
                $siteLng   = 120.9842;

                if ($destPid) {
                    foreach ($projects as $proj) {
                        if ($proj['id'] === $destPid) {
                            $siteLat = (float)($proj['lat'] ?? $siteLat);
                            $siteLng = (float)($proj['lng'] ?? $siteLng);
                            break;
                        }
                    }
                }

                $releasedPayload = [];
                foreach ($projects as $proj) {
                    if ($proj['id'] !== $destPid) {
                        $releasedPayload[] = [
                            'project_id'     => $proj['id'],
                            'project_name'   => $proj['label'],
                            'lat'            => (float)($proj['lat'] ?? 14.5995),
                            'lng'            => (float)($proj['lng'] ?? 120.9842),
                            'tool_category'  => $req['resource_category'] ?? 'Equipment',
                            'units_released' => 1,
                        ];
                    }
                }

                if (!empty($releasedPayload)) {
                    $mlCfg = new \Config\MLService();
                    $result = $mlService->predictToolAvailability([
                        'tool_category'  => $req['resource_category'] ?? 'Equipment',
                        'site_lat'       => $siteLat,
                        'site_lng'       => $siteLng,
                        'radius_km'      => 500,
                        'weights'        => [
                            'distance_weight'     => $mlCfg->mcdaDistanceWeight,
                            'availability_weight' => $mlCfg->mcdaAvailabilityWeight,
                        ],
                        'released_tools' => $releasedPayload,
                    ]);

                    $siteList = $result['optimized_tools'] ?? $result['predictions'] ?? (isset($result[0]) ? $result : []);
                    if (!empty($siteList) && !isset($result['error'])) {
                        $highestScore   = -1;
                        $lowestDistance = 999999;
                        foreach ($siteList as $site) {
                            $score    = (float)($site['score'] ?? $site['overall_score'] ?? 0);
                            $distance = (float)($site['distance_km'] ?? $site['distance'] ?? 999);
                            $pid      = $site['project_id'] ?? '';
                            if ($score > $highestScore) {
                                $highestScore   = $score;
                                $recommendedBest = ['project_id' => $pid, 'project_name' => $site['project_name'] ?? 'Unknown', 'score' => round($score, 2), 'distance_km' => round($distance, 1)];
                            }
                            if ($distance < $lowestDistance) {
                                $lowestDistance     = $distance;
                                $recommendedNearest = ['project_id' => $pid, 'project_name' => $site['project_name'] ?? 'Unknown', 'distance_km' => round($distance, 1)];
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                log_message('warning', 'ResourceRequests::show MCDA - ' . $e->getMessage());
            }
        }

        $currentRole        = session()->get('role') ?? '';
        $canAcceptDelivery  = $currentRole === 'Site Engineer'
                           && strtolower($req['status'] ?? '') === 'in-transit';

        return view('requests/show', [
            'pageTitle'           => 'Request Details',
            'activePage'          => 'resources',
            'request'             => $req,
            'projects'            => $projects,
            'role'                => $currentRole,
            'recommended_best'    => $recommendedBest,
            'recommended_nearest' => $recommendedNearest,
            'canAcceptDelivery'   => $canAcceptDelivery,
        ]);
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

        $data = [
            'request_kind'           => $requestKind,
            'resource_category'      => $cat,
            'resource_name'          => $resName,
            'requested_model_id'     => $modelId ?: null,
            'requested_model_name'   => $resName ?: null,
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
            return redirect()->to('requests')->with('success', 'Request rejected.');
        } catch (\Throwable $e) {
            log_message('error', 'ResourceRequests::reject - ' . $e->getMessage());
            return redirect()->to('requests/' . $id)->with('error', 'Failed to reject request.');
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
                $mpId   = $unitId ?: $modelId; // manpower doc ID is in source_resource_id
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

                $this->requestModel->update($reqId, [
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
                ]);
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

            return redirect()->to('requests/' . $id)->with('success', 'Delivery accepted — resource is now active at the destination project.');
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
