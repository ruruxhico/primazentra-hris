<?php

namespace App\Controllers;

use App\Models\Model_ResourceRequests;
use App\Models\Model_Projects;
use App\Models\Model_Resources;
use App\Models\Model_Manpower;
use Google\Cloud\Core\Timestamp;

class ResourceRequests extends BaseController
{
    protected $requestModel;
    protected $projectModel;
    protected $resourceModel;
    protected $manpowerModel;

    public function __construct()
    {
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
        $allowed = ['Admin', 'Operations Manager', 'Project Manager'];
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

        $updates = [
            'status'                       => $bypassSecond ? 'Approved' : 'Pending Secondary',
            'approved_by'                  => $uid,
            'approved_by_name'             => $user,
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
        $allowed = ['Admin', 'Operations Manager', 'Project Manager'];
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

        $req = $this->requestModel->getById($id);
        if (!$req || $req['status'] !== 'Pending Secondary') {
            return redirect()->to('requests/' . $id)->with('error', 'Request is not awaiting secondary approval.');
        }

        try {
            $this->requestModel->update($id, [
                'status'                    => 'Approved',
                'secondary_approved_by'     => session()->get('userId') ?? '',
                'secondary_approved_by_name'=> session()->get('userName') ?? '',
                'secondary_approved_at'     => new Timestamp(new \DateTime()),
            ]);
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
            if (!in_array($r['status'] ?? '', ['Pending', 'Pending Secondary'])) continue;
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
}
