<?php

namespace App\Controllers;

use App\Libraries\ArchiveService;
use App\Libraries\FirestoreService;
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

        return view('requests/index', [
            'pageTitle'      => 'Resource Requests & Transfers',
            'activePage'     => 'resources',
            'requests'       => $requests,
            'filterStatus'   => $status,
            'filterCategory' => $category,
            'filterSearch'   => $search,
            'role'           => session()->get('role') ?? '',
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
                $projects[] = [
                    'id'    => $p['id'],
                    'label' => strtoupper(trim($p['project_name'] ?? $p['id'])),
                ];
            }
        } catch (\Throwable $e) {
            log_message('warning', 'ResourceRequests::show projects - ' . $e->getMessage());
        }

        return view('requests/show', [
            'pageTitle'  => 'Request Details',
            'activePage' => 'resources',
            'request'    => $req,
            'projects'   => $projects,
            'role'       => session()->get('role') ?? '',
        ]);
    }

    // ── Create (drag-and-drop or API) ─────────────────────────────────────

    public function store()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->jsonError('Unauthenticated', 401);
        }

        $body = $this->request->getJSON(true) ?: $this->request->getPost();

        $category    = trim($body['category']    ?? '');
        $resName     = trim($body['resource_name'] ?? '');
        $sourcePid   = trim($body['source_project_id']      ?? '');
        $sourceName  = trim($body['source_project_name']    ?? '');
        $destPid     = trim($body['destination_project_id'] ?? '');
        $destName    = trim($body['destination_project_name'] ?? '');
        $type        = trim($body['type']        ?? 'drag_drop');
        $qty         = max(1, (int)($body['quantity'] ?? 1));
        $model       = trim($body['model']       ?? '');
        $resId       = trim($body['resource_id'] ?? '');
        $unitId      = trim($body['unit_id']     ?? '');

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

        $data = [
            'type'                   => $type,
            'category'               => ucfirst(strtolower($category)),
            'resource_name'          => $resName,
            'resource_id'            => $resId,
            'unit_id'                => $unitId,
            'quantity'               => $qty,
            'model'                  => $model,
            'source_project_id'      => $sourcePid ?: null,
            'source_site_name'       => $sourcePid ? $sourceName : 'To be assigned',
            'destination_project_id' => $destPid,
            'destination_site_name'  => $destName,
            'requested_by'           => $uid,
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

        // Resolve source project name if missing
        if ($sourcePid && !$sourceName) {
            try {
                $p = $this->projectModel->getProjectById($sourcePid);
                $sourceName = strtoupper(trim($p['project_name'] ?? $sourcePid));
            } catch (\Throwable $e) { $sourceName = $sourcePid; }
        }

        $uid  = session()->get('userId')   ?? '';
        $user = session()->get('userName') ?? '';

        // Admin bypass only allowed for Administrator role
        if ($bypassSecond && $role !== 'Administrator') {
            $bypassSecond = false;
        }

        $updates = [
            'status'                       => $bypassSecond ? 'Approved' : 'Pending Secondary',
            'approved_by'                  => $uid,
            'approved_by_name'             => $user,
            'approved_by_role'             => $role,
            'approved_at'                  => new Timestamp(new \DateTime()),
            'bypass_secondary'             => $bypassSecond,
            'approved_source_project_id'   => $sourcePid ?: ($req['source_project_id'] ?? ''),
            'approved_source_project_name' => $sourceName ?: ($req['source_site_name'] ?? ''),
        ];

        // If source was already known (drag-and-drop), use it
        if (!$sourcePid && !empty($req['source_project_id'])) {
            $updates['approved_source_project_id']   = $req['source_project_id'];
            $updates['approved_source_project_name'] = $req['source_site_name'] ?? '';
            $updates['status'] = $bypassSecond ? 'Approved' : 'Pending Secondary';
        }

        try {
            $this->requestModel->update($id, $updates);

            $resName     = (string)($req['resource_name'] ?? 'resource');
            $requesterUid = (string)($req['requested_by'] ?? '');
            $requesterName = (string)($req['requested_by_name'] ?? 'Someone');

            $svc = new NotificationService();

            if ($bypassSecond) {
                // Admin bypass — fully approved in one step; move the resource immediately
                $this->_finalizeTransfer(array_merge($req, $updates));

                if ($requesterUid) {
                    $svc->send(
                        $requesterUid,
                        'requestApproved',
                        'Request Approved',
                        'Your request has been fully approved.',
                        ['request_id' => $id]
                    );
                }
            } else {
                // Primary approved, secondary still needed — notify requester (1/2)
                if ($requesterUid) {
                    $svc->send(
                        $requesterUid,
                        'requestApproved',
                        'Request Approved',
                        'Your request has been approved (1/2) — awaiting secondary.',
                        ['request_id' => $id]
                    );
                }

                // Notify secondary approvers (all roles except the primary approver's role)
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
                'rejected_by'     => session()->get('userId') ?? '',
                'rejected_by_name'=> session()->get('userName') ?? '',
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
        if (!$req || $req['status'] !== 'Pending Secondary') {
            return redirect()->to('requests/' . $id)->with('error', 'Request is not awaiting secondary approval.');
        }

        $primaryRole = $req['approved_by_role'] ?? '';
        if ($primaryRole && $currentRole === $primaryRole) {
            return redirect()->to('requests/' . $id)->with('error', "A {$primaryRole} already approved this request. A different role must provide the secondary approval.");
        }

        try {
            $this->requestModel->update($id, [
                'status'                      => 'Approved',
                'secondary_approved_by'       => session()->get('userId') ?? '',
                'secondary_approved_by_name'  => session()->get('userName') ?? '',
                'secondary_approved_by_role'  => $currentRole,
                'secondary_approved_at'       => new Timestamp(new \DateTime()),
            ]);

            // Move the resource to the destination project now that it's fully approved
            $this->_finalizeTransfer($req);

            $requesterUid = (string)($req['requested_by'] ?? '');
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
            if (!in_array($r['status'] ?? '', ['Pending', 'Pending Secondary', 'Pending Secondary Approval'])) continue;
            $result[] = [
                'id'            => $r['id'],
                'status'        => $r['status'],
                'category'      => strtolower($r['category'] ?? ''),
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
     * After final approval, update the resource's assigned_project_id and status
     * so the dashboard immediately reflects the transfer.
     */
    private function _finalizeTransfer(array $req): void
    {
        $category = strtolower(trim($req['category'] ?? ''));
        $resId    = trim($req['resource_id'] ?? '');
        $unitId   = trim($req['unit_id']     ?? '');
        $destPid  = trim($req['destination_project_id'] ?? '');
        $destName = trim($req['destination_site_name']  ?? '');

        if (!$resId || !$destPid) return;

        $db  = $this->firestore->getDb();
        $now = new Timestamp(new \DateTime());

        try {
            if ($category === 'manpower') {
                $db->collection('manpower')->document($resId)->update([
                    ['path' => 'assigned_project_id', 'value' => $destPid],
                    ['path' => 'status',              'value' => 'DEPLOYED'],
                    ['path' => 'location',            'value' => [
                        'address' => strtoupper($destName),
                        'type'    => 'PROJECT SITE',
                    ]],
                    ['path' => 'updated_at', 'value' => $now],
                ]);
            } elseif (in_array($category, ['equipment', 'tools'])) {
                $itemsRef = $db->collection('resource')->document($resId)->collection('items');

                if ($unitId !== '') {
                    // Single specific unit
                    $itemsRef->document($unitId)->update([
                        ['path' => 'assigned_project_id', 'value' => $destPid],
                        ['path' => 'status',              'value' => 'In-Use'],
                        ['path' => 'location',            'value' => [
                            'address' => $destName,
                            'type'    => 'Project Site',
                        ]],
                        ['path' => 'transfer_locked',     'value' => false],
                        ['path' => 'updated_at',          'value' => $now],
                    ]);
                } else {
                    // Bulk: move $qty In-Storage items to the destination
                    $qty   = max(1, (int)($req['quantity'] ?? 1));
                    $moved = 0;
                    foreach ($itemsRef->documents() as $itemDoc) {
                        if (!$itemDoc->exists() || $moved >= $qty) break;
                        $itemStatus = (string)($itemDoc->data()['status'] ?? '');
                        if ($itemStatus === 'In-Storage') {
                            $itemDoc->reference()->update([
                                ['path' => 'assigned_project_id', 'value' => $destPid],
                                ['path' => 'status',              'value' => 'In-Use'],
                                ['path' => 'location',            'value' => [
                                    'address' => $destName,
                                    'type'    => 'Project Site',
                                ]],
                                ['path' => 'transfer_locked', 'value' => false],
                                ['path' => 'updated_at',      'value' => $now],
                            ]);
                            $moved++;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'ResourceRequests::_finalizeTransfer - ' . $e->getMessage());
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
}
