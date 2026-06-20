<?php

namespace App\Controllers;

use App\Libraries\FirestoreService;
use App\Libraries\MilestoneProgressService;
use App\Libraries\StorageService;
use App\Libraries\TrashService;
use App\Models\Model_Users;
use Config\GoogleMaps;

class Projects extends BaseController
{
    protected FirestoreService $firestore;
    protected Model_Users $usersModel;

    public function __construct()
    {
        $this->firestore = new FirestoreService();
        $this->usersModel = new Model_Users();
    }

    public function index()
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');

        helper('access');
        if (!pz_can('view_projects')) return redirect()->to('dashboard');

        try {
            $projects = [];

            foreach ($this->firestore->getDb()->collection('project')->documents() as $doc) {
                if (!$doc->exists()) {
                    continue;
                }

                $projectData = $doc->data();
                $projectData['id'] = $doc->id();
                $projects[] = $this->enrichProjectForView($projectData);
            }

            // PM: show only their own projects by default; toggle via ?view_all=1
            $uid       = (string) session()->get('userId');
            $isViewAll = trim((string)($this->request->getGet('view_all') ?? '')) === '1';
            if (pz_is('Project Manager') && !$isViewAll) {
                $projects = array_values(array_filter($projects, fn($p) =>
                    ($p['pm_id_normalized'] ?? '') === $uid));
            }

            // â€” Build PM / SE option lists from actual project data â€”
            $pmOptions = [];
            $seOptions = [];
            foreach ($projects as $p) {
                $pmId   = $p['pm_id_normalized'] ?? '';
                $pmName = $p['project_manager_name'] ?? '';
                if ($pmId && $pmName !== 'N/A' && !isset($pmOptions[$pmId])) {
                    $pmOptions[$pmId] = $pmName;
                }
                $seId   = $p['se_id_normalized'] ?? '';
                $seName = $p['site_engineer_name'] ?? '';
                if ($seId && $seName !== 'N/A' && !isset($seOptions[$seId])) {
                    $seOptions[$seId] = $seName;
                }
            }
            asort($pmOptions);
            asort($seOptions);

            // â€” Build client options from all projects (before filtering) â€”
            $clientOptions = [];
            foreach ($projects as $p) {
                $cn = strtoupper(trim((string)($p['client_name'] ?? '')));
                if ($cn !== '') $clientOptions[$cn] = $cn;
            }
            ksort($clientOptions);

            // â€” Read filters from GET â€”
            $filterStatus = trim((string) ($this->request->getGet('status') ?? ''));
            $filterRegion = trim((string) ($this->request->getGet('region') ?? ''));
            $filterPm     = trim((string) ($this->request->getGet('pm_id') ?? ''));
            $filterSe     = trim((string) ($this->request->getGet('se_id') ?? ''));
            $filterType   = trim((string) ($this->request->getGet('type') ?? ''));
            $filterClient = trim((string) ($this->request->getGet('client') ?? ''));
            $filterSort   = trim((string) ($this->request->getGet('sort') ?? ''));

            // â€” Apply filters â€”
            if ($filterStatus !== '') {
                $projects = array_values(array_filter($projects, fn($p) =>
                    strcasecmp((string) ($p['status'] ?? ''), $filterStatus) === 0));
            }
            if ($filterRegion !== '') {
                $projects = array_values(array_filter($projects, fn($p) =>
                    $this->resolveRegion((string) ($p['location_label'] ?? '')) === $filterRegion));
            }
            if ($filterPm !== '') {
                $projects = array_values(array_filter($projects, fn($p) =>
                    ($p['pm_id_normalized'] ?? '') === $filterPm));
            }
            if ($filterSe !== '') {
                $projects = array_values(array_filter($projects, fn($p) =>
                    ($p['se_id_normalized'] ?? '') === $filterSe));
            }
            if ($filterType !== '') {
                $projects = array_values(array_filter($projects, fn($p) =>
                    strcasecmp((string) ($p['project_type'] ?? ''), $filterType) === 0));
            }
            if ($filterClient !== '') {
                $projects = array_values(array_filter($projects, fn($p) =>
                    strcasecmp(trim((string)($p['client_name'] ?? '')), $filterClient) === 0));
            }

            // â€” Sort â€”
            if ($filterSort === 'progress_desc') {
                usort($projects, fn($a, $b) =>
                    (float) ($b['overall_progress'] ?? 0) <=> (float) ($a['overall_progress'] ?? 0));
            } elseif ($filterSort === 'progress_asc') {
                usort($projects, fn($a, $b) =>
                    (float) ($a['overall_progress'] ?? 0) <=> (float) ($b['overall_progress'] ?? 0));
            }

            // â€” Paginate â€”
            $perPage     = config('Pager')->perPage;
            $currentPage = max(1, (int) ($this->request->getGet('page') ?? 1));
            $total       = count($projects);

            $pager = service('pager');
            $pager->store('projects', $currentPage, $perPage, $total);

            $offset        = ($currentPage - 1) * $perPage;
            $pagedProjects = array_slice($projects, $offset, $perPage);

            $trashService    = new TrashService($this->firestore);
            $recentlyDeleted = $trashService->getTrashItems('project');

            $data = [
                'pageTitle'       => 'Projects',
                'activePage'      => 'projects',
                'projects'        => $pagedProjects,
                'projectsTotal'   => $total,
                'perPage'         => $perPage,
                'pagerOffset'     => $offset,
                'pmOptions'       => $pmOptions,
                'seOptions'       => $seOptions,
                'clientOptions'   => $clientOptions,
                'activeFilters'   => [
                    'status' => $filterStatus,
                    'region' => $filterRegion,
                    'pm_id'  => $filterPm,
                    'se_id'  => $filterSe,
                    'type'   => $filterType,
                    'client' => $filterClient,
                    'sort'   => $filterSort,
                ],
                'recentlyDeleted' => $recentlyDeleted,
                'userName'        => session()->get('userName') ?? 'User',
                'role'            => session()->get('role') ?? 'Staff',
                'isPm'            => pz_is('Project Manager'),
                'isViewAll'       => $isViewAll,
            ];

            return view('projects/projects_view', $data);
        } catch (\Exception $e) {
            log_message('error', 'Projects::index - ' . $e->getMessage());

            $data = [
                'pageTitle'       => 'Projects',
                'activePage'      => 'projects',
                'projects'        => [],
                'pagerOffset'     => 0,
                'pmOptions'       => [],
                'seOptions'       => [],
                'activeFilters'   => [],
                'recentlyDeleted' => [],
                'userName'        => session()->get('userName') ?? 'User',
                'role'            => session()->get('role') ?? 'Staff',
                'error'           => 'Unable to fetch projects',
            ];

            return view('projects/projects_view', $data);
        }
    }

    public function create()
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        helper('access');
        if (!pz_can('create_project')) return redirect()->to('dashboard')->with('error', 'Access denied.');

        if (strtolower($this->request->getMethod()) === 'post') {
            // validate input using Config\Validation 'projects' group
            if (! $this->validate('projects')) {
                return redirect()->back()->withInput()->with('error', implode('; ', $this->validator->getErrors()));
            }

            try {
                $projectId = bin2hex(random_bytes(8));
                $projectData = $this->buildProjectPayload();
                $projectData['created_at'] = time();
                $projectData['updated_at'] = time();

                log_message('info', 'Projects::create - POST received. Preparing to write project: ' . ($projectData['project_name'] ?? '(no name)'));

                $this->firestore->getDb()
                    ->collection('project')
                    ->document($projectId)
                    ->set($projectData);

                // verify write
                try {
                    $snapshot = $this->firestore->getDb()->collection('project')->document($projectId)->snapshot();
                    if ($snapshot->exists()) {
                        log_message('info', 'Projects::create - Firestore write verified. ID: ' . $projectId);
                    } else {
                        log_message('error', 'Projects::create - Firestore write verification failed for ID: ' . $projectId);
                    }
                } catch (\Throwable $t) {
                    log_message('error', 'Projects::create - verification error: ' . $t->getMessage());
                }

                if ($this->request->getPost('add_tasks_now')) {
                    $rawPhases   = $this->request->getPost('phases') ?? [];
                    $weightError = $this->validateMilestoneWeights($rawPhases);
                    if ($weightError) {
                        return redirect()->back()->withInput()->with('error', $weightError);
                    }
                    $flatTasks = $this->normalizePhases($rawPhases);
                    $this->writeTasksFlat($projectId, $flatTasks);
                    $this->writeMilestonesFlat($projectId, $this->normalizeToMilestones($rawPhases));
                }

                if ($this->request->getPost('add_resources_now')) {
                    return redirect()->to('projects/' . $projectId . '/resources')
                        ->with('success', 'Project created! Now assign resources.');
                }

                return redirect()->to('projects')->with('success', 'Project created successfully!');
            } catch (\Exception $e) {
                log_message('error', 'Projects::create - ' . $e->getMessage());
                return redirect()->back()->withInput()->with('error', 'Failed to create project');
            }
        }

        return view('projects/projects_form', [
            'pageTitle' => 'Add Project',
            'activePage' => 'projects',
            'project' => [],
            'phases' => [],
            'projectManagers' => $this->usersModel->getUsersByRole(['PM', 'Project Manager']),
            'siteEngineers' => $this->usersModel->getUsersByRole(['SE', 'Site Engineer']),
            'existingClients' => $this->getExistingClientNames(),
            'userName' => session()->get('userName') ?? 'User',
            'role' => session()->get('role') ?? 'Staff',
            'googleMapsBrowserKey' => (new GoogleMaps())->browserKey,
        ]);
    }

    public function update($id)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        helper('access');
        if (!pz_can('edit_project')) return redirect()->to('projects')->with('error', 'Access denied — only Administrators and Operations Managers may edit projects.');

        try {
                if (strtolower($this->request->getMethod()) === 'post') {
                // validate using Config\Validation 'projects' group
                if (! $this->validate('projects')) {
                    return redirect()->back()->withInput()->with('error', implode('; ', $this->validator->getErrors()));
                }
                $existing = $this->getProjectById($id);

                if (!$existing) {
                    return redirect()->to('projects')->with('error', 'Project not found');
                }

                $projectData = $this->buildProjectPayload();
                $projectData['created_at'] = $existing['created_at'] ?? time();
                $projectData['updated_at'] = time();

                // Preserve terminal statuses set outside the form
                $existingStatus = (string)($existing['status'] ?? '');
                if (in_array($existingStatus, ['Completed', 'Archived'], true)) {
                    $projectData['status'] = $existingStatus;
                }

                // If user did not opt to add/update tasks now, preserve existing overall progress
                $addTasksNow = (bool) $this->request->getPost('add_tasks_now');
                if (!$addTasksNow) {
                    $projectData['overall_progress'] = $existing['overall_progress'] ?? $existing['completion_percent'] ?? 0;
                    $projectData['completion_percent'] = $projectData['overall_progress'];
                }

                log_message('info', 'Projects::update - POST received for project id: ' . $id . '; name: ' . ($projectData['project_name'] ?? '(no name)'));

                $this->firestore->getDb()
                    ->collection('project')
                    ->document($id)
                    ->set($projectData);

                log_message('info', 'Projects::update - Firestore set() completed for id: ' . $id);

                if ($addTasksNow) {
                    $rawPhases   = $this->request->getPost('phases') ?? [];
                    $weightError = $this->validateMilestoneWeights($rawPhases);
                    if ($weightError) {
                        return redirect()->back()->withInput()->with('error', $weightError);
                    }
                    $flatTasks = $this->normalizePhases($rawPhases);
                    $this->writeTasksFlat($id, $flatTasks);
                    $this->writeMilestonesFlat($id, $this->normalizeToMilestones($rawPhases));
                }

                return redirect()->to('projects')->with('success', 'Project updated successfully!');
            }

            $project = $this->getProjectById($id);

            if (!$project) {
                return redirect()->to('projects')->with('error', 'Project not found');
            }

            return view('projects/projects_form', [
                'pageTitle' => 'Edit Project',
                'activePage' => 'projects',
                'project' => $project,
                'projectId' => $id,
                'phases' => $this->getProjectPhases($id),
                'projectManagers' => $this->usersModel->getUsersByRole(['PM', 'Project Manager']),
                'siteEngineers' => $this->usersModel->getUsersByRole(['SE', 'Site Engineer']),
                'existingClients' => $this->getExistingClientNames(),
                'userName' => session()->get('userName') ?? 'User',
                'role' => session()->get('role') ?? 'Staff',
                'googleMapsBrowserKey' => (new GoogleMaps())->browserKey,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Projects::update - ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update project');
        }
    }

    public function delete($id = null)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        helper('access');
        if (!pz_can('delete_project')) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Access denied.']);
            }
            return redirect()->to('projects')->with('error', 'Access denied.');
        }

        // Support POST delete via AJAX and GET fallback with segment
        try {
            if (strtolower($this->request->getMethod()) === 'post') {
                $id = $this->request->getPost('id');
                if (!$id) {
                    return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing id']);
                }

                // Snapshot project data before deletion
                $snap = $this->firestore->getDb()->collection('project')->document($id)->snapshot();
                $data = $snap->exists() ? $snap->data() : [];
                $name = (string) ($data['project_name'] ?? $id);

                // Read tasks subcollection with __id preserved
                $tasks = [];
                foreach ($this->firestore->getDb()->collection('project')->document($id)->collection('tasks')->documents() as $tDoc) {
                    if ($tDoc->exists()) $tasks[] = ['__id' => $tDoc->id()] + $tDoc->data();
                }
                // Read milestones subcollection with __id preserved
                $milestones = [];
                foreach ($this->firestore->getDb()->collection('project')->document($id)->collection('milestones')->documents() as $mDoc) {
                    if ($mDoc->exists()) $milestones[] = ['__id' => $mDoc->id()] + $mDoc->data();
                }

                // Move to trash before hard-deleting
                (new TrashService($this->firestore))->moveToTrash(
                    'project', $id, $name, $data,
                    ['tasks' => $tasks, 'milestones' => $milestones],
                    session()->get('userName')
                );

                $this->deleteProjectTasks($id);
                $this->deleteProjectMilestones($id);
                $this->firestore->getDb()->collection('project')->document($id)->delete();

                return $this->response->setJSON(['success' => true]);
            }

            // GET fallback when id passed as parameter
            if ($id) {
                // Snapshot project data before deletion
                $snap = $this->firestore->getDb()->collection('project')->document($id)->snapshot();
                $data = $snap->exists() ? $snap->data() : [];
                $name = (string) ($data['project_name'] ?? $id);

                // Read tasks subcollection with __id preserved
                $tasks = [];
                foreach ($this->firestore->getDb()->collection('project')->document($id)->collection('tasks')->documents() as $tDoc) {
                    if ($tDoc->exists()) $tasks[] = ['__id' => $tDoc->id()] + $tDoc->data();
                }
                // Read milestones subcollection with __id preserved
                $milestones = [];
                foreach ($this->firestore->getDb()->collection('project')->document($id)->collection('milestones')->documents() as $mDoc) {
                    if ($mDoc->exists()) $milestones[] = ['__id' => $mDoc->id()] + $mDoc->data();
                }

                // Move to trash before hard-deleting
                (new TrashService($this->firestore))->moveToTrash(
                    'project', $id, $name, $data,
                    ['tasks' => $tasks, 'milestones' => $milestones],
                    session()->get('userName')
                );

                $this->deleteProjectTasks($id);
                $this->deleteProjectMilestones($id);
                $this->firestore->getDb()->collection('project')->document($id)->delete();
                return redirect()->to('projects')->with('success', 'Project deleted successfully!');
            }

            return redirect()->to('projects')->with('error', 'Invalid request');
        } catch (\Exception $e) {
            log_message('error', 'Projects::delete - ' . $e->getMessage());
            if (strtolower($this->request->getMethod()) === 'post') {
                return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Failed to delete project']);
            }
            return redirect()->back()->with('error', 'Failed to delete project');
        }
    }

    public function show($id)
    {
        try {
            $project = $this->getProjectById($id);
            if (!$project) {
                return redirect()->to('projects')->with('error', 'Project not found');
            }

            $milestones   = $this->getMilestonesOrdered($id);
            $startDate    = $project['start_date_value'] ?? '';
            $endDate      = $project['end_date_value']   ?? '';
            $reportDate   = date('Y-m-d');
            $actualToDate = (float) ($project['overall_progress'] ?? 0);

            $curveData        = null;
            $plannedToDate    = null;
            $variance         = null;
            $ewd              = null;
            $weeklyTargets    = [];
            $milestoneSummary = [];

            if (!empty($milestones)) {
                // Build per-milestone summary rows (always, even without a curve)
                $svc        = new MilestoneProgressService();
                $cumPlanned = 0.0;
                $cumActual  = 0.0;

                foreach ($milestones as $m) {
                    $weight = (float) ($m['weight'] ?? 0);
                    $isDone = in_array(strtolower((string)($m['status'] ?? '')), ['done', 'completed'], true)
                              || !empty($m['actualCompletionDate']);

                    $cumPlanned += $weight;
                    if ($isDone) $cumActual += $weight;

                    $dated = !empty($m['targetDate']);
                    $milestoneSummary[] = [
                        'name'       => (string) ($m['name']       ?? ''),
                        'targetDate' => $dated ? (string) $m['targetDate'] : null,
                        'plannedPct' => $dated ? round($cumPlanned, 2) : null,
                        'actualPct'  => round($cumActual, 2),
                        'variance'   => $dated ? round($cumActual - $cumPlanned, 2) : null,
                        'status'     => (string) ($m['status'] ?? 'Pending'),
                    ];
                }

                // Curve and time-based metrics only when a project start date exists
                if ($startDate !== '') {
                    $curveData     = $svc->computeCurve($milestones, $startDate, $endDate ?: null);
                    $plannedToDate = $svc->sampleAt($curveData['controlPoints'], $reportDate);
                    $variance      = round($actualToDate - $plannedToDate, 2);
                    $weeklyTargets = $svc->weeklyTargets($curveData['controlPoints'], $startDate, $reportDate);

                    $dailyRate = $svc->dailyRateAt($curveData['controlPoints'], $reportDate);
                    if ($dailyRate > 0) {
                        $ewd = round($variance / $dailyRate, 1);
                    }
                }
            }

            return view('projects/projects_show', [
                'pageTitle'        => 'Project Detail',
                'activePage'       => 'projects',
                'project'          => $project,
                'projectId'        => $id,
                'milestones'       => $milestones,
                'curveData'        => $curveData,
                'plannedToDate'    => $plannedToDate,
                'actualToDate'     => $actualToDate,
                'variance'         => $variance,
                'ewd'              => $ewd,
                'weeklyTargets'    => $weeklyTargets,
                'milestoneSummary' => $milestoneSummary,
                'reportDate'       => $reportDate,
                'userName'         => session()->get('userName') ?? 'User',
                'role'             => session()->get('role') ?? 'Staff',
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Projects::show - ' . $e->getMessage());
            return redirect()->to('projects')->with('error', 'Unable to fetch project');
        }
    }

    // ── Project Resources ─────────────────────────────────────────────────

    public function resources(string $id)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');

        try {
            $project = $this->getProjectById($id);
            if (!$project) return redirect()->to('projects')->with('error', 'Project not found');

            $db            = $this->firestore->getDb();
            $manpowerModel = new \App\Models\Model_Manpower();
            $resourceModel = new \App\Models\Model_Resources();

            // ── Manpower ──
            $allManpower        = [];
            $assignedManpower   = [];
            $assignedManpowerIds = [];

            foreach ($manpowerModel->getAllManpower() as $m) {
                $mpId  = $m['id'];
                $fn    = trim((string)($m['first_name'] ?? ''));
                $ln    = trim((string)($m['last_name']  ?? ''));
                $name  = $fn . ($fn && $ln ? ' ' : '') . $ln;
                $allManpower[] = [
                    'id'             => $mpId,
                    'first_name'     => $fn,
                    'last_name'      => $ln,
                    'name'           => $name,
                    'role'           => $m['role'] ?? ($m['manpower_details']['role'] ?? ''),
                    'specialization' => $m['specialization'] ?? ($m['manpower_details']['specialization'] ?? ''),
                    'skills'         => $m['skills'] ?? ($m['manpower_details']['skills'] ?? []),
                    'status'         => $m['status'] ?? 'Available',
                    'current_site'   => $m['current_site_deployment'] ?? ($m['current_site'] ?? ''),
                ];
                $apid = (string)($m['assigned_project_id'] ?? '');
                if ($apid === $id) {
                    $assignedManpower[]          = ['name' => $name];
                    $assignedManpowerIds[$mpId]  = 'mp:' . $mpId;
                }
            }

            // ── Equipment & Tools ──
            $allEquipment     = [];
            $allTools         = [];
            $resourceItemStats = [];
            $assignedEqGroups  = [];

            foreach ($resourceModel->getAllResources() as $res) {
                $resId    = $res['id'];
                $resName  = $res['name'] ?? '';
                $category = $res['category'] ?? '';

                $total         = 0;
                $available     = 0;
                $assignedCount = 0;

                foreach ($resourceModel->getResourceItems($resId) as $item) {
                    $total++;
                    $status = $item['status'] ?? 'Available';
                    if (in_array($status, ['Available', 'In-Storage'], true)) $available++;
                    $apid = $item['assigned_project_id'] ?? null;
                    $itemPid = $apid instanceof \Google\Cloud\Firestore\DocumentReference
                        ? $apid->id()
                        : (string)($apid ?? '');
                    if ($itemPid === $id) $assignedCount++;
                }

                $resourceItemStats[$resId] = ['total' => $total, 'available' => $available, 'assigned' => $assignedCount];
                $entry = ['id' => $resId, 'name' => $resName];

                if ($category === 'Tools') {
                    $allTools[] = $entry;
                } else {
                    $allEquipment[] = $entry;
                }

                if ($assignedCount > 0) {
                    $assignedEqGroups[] = ['name' => $resName, 'count' => $assignedCount];
                }
            }

            return view('projects/project_resources', [
                'pageTitle'           => 'Project Resources — ' . strtoupper($project['project_name'] ?? ''),
                'activePage'          => 'projects',
                'userName'            => session()->get('userName') ?? 'User',
                'role'                => session()->get('role') ?? 'Staff',
                'projectId'           => $id,
                'project'             => $project,
                'allManpower'         => $allManpower,
                'allEquipment'        => $allEquipment,
                'allTools'            => $allTools,
                'assignedManpower'    => $assignedManpower,
                'assignedEqGroups'    => $assignedEqGroups,
                'assignedManpowerIds' => $assignedManpowerIds,
                'resourceItemStats'   => $resourceItemStats,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Projects::resources - ' . $e->getMessage());
            return redirect()->to('projects')->with('error', 'Failed to load resources');
        }
    }

    public function assignResource(string $id)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthenticated']);
        }

        $type   = trim((string)($this->request->getPost('type')    ?? ''));
        $refId  = trim((string)($this->request->getPost('ref_id')  ?? ''));
        $unitId = trim((string)($this->request->getPost('unit_id') ?? ''));

        try {
            $db = $this->firestore->getDb();

            if ($type === 'manpower') {
                $db->collection('manpower')->document($refId)->set([
                    'assigned_project_id' => $id,
                    'status'              => 'Deployed',
                ], ['merge' => true]);
            } else {
                $db->collection('resource')->document($refId)->collection('items')->document($unitId)->set([
                    'assigned_project_id' => $db->collection('project')->document($id),
                    'status'              => 'In-Use',
                ], ['merge' => true]);
            }

            return $this->response->setJSON(['success' => true]);
        } catch (\Throwable $e) {
            log_message('error', 'Projects::assignResource - ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to assign resource']);
        }
    }

    public function unassignResource(string $id)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthenticated']);
        }

        $assignmentId = trim((string)($this->request->getPost('assignment_id') ?? ''));

        try {
            $db = $this->firestore->getDb();

            if (str_starts_with($assignmentId, 'mp:')) {
                $mpId = substr($assignmentId, 3);
                $db->collection('manpower')->document($mpId)->set([
                    'assigned_project_id' => null,
                    'status'              => 'Available',
                ], ['merge' => true]);
            } elseif (str_starts_with($assignmentId, 'unit:')) {
                [, $resId, $unitId] = explode(':', $assignmentId, 3);
                $db->collection('resource')->document($resId)->collection('items')->document($unitId)->set([
                    'assigned_project_id' => null,
                    'status'              => 'In-Storage',
                ], ['merge' => true]);
            } else {
                return $this->response->setJSON(['success' => false, 'message' => 'Invalid assignment ID']);
            }

            return $this->response->setJSON(['success' => true]);
        } catch (\Throwable $e) {
            log_message('error', 'Projects::unassignResource - ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to unassign']);
        }
    }

    public function resourceUnits(string $id, string $resourceId)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false]);
        }

        try {
            $resourceModel = new \App\Models\Model_Resources();
            $items = $resourceModel->getResourceItems($resourceId);

            $out = [];
            foreach ($items as $item) {
                $unitId = $item['__id'] ?? $item['id'] ?? '';
                $apid   = $item['assigned_project_id'] ?? null;
                $itemPid = $apid instanceof \Google\Cloud\Firestore\DocumentReference
                    ? $apid->id()
                    : (string)($apid ?? '');
                $isHere  = ($itemPid === $id);

                $out[] = [
                    'id'                      => $unitId,
                    'serial_number'           => $item['serial_number'] ?? ($item['unit_name'] ?? $unitId),
                    'status'                  => $item['status'] ?? 'Unknown',
                    'assigned_to_this_project' => $isHere,
                    'assignment_id'            => $isHere ? 'unit:' . $resourceId . ':' . $unitId : null,
                ];
            }

            return $this->response->setJSON(['success' => true, 'items' => $out]);
        } catch (\Throwable $e) {
            log_message('error', 'Projects::resourceUnits - ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to load units']);
        }
    }

    public function bulkAssignManpower(string $id)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthenticated']);
        }

        $role   = trim((string)($this->request->getPost('role')           ?? ''));
        $spec   = trim((string)($this->request->getPost('specialization') ?? ''));
        $skill  = trim((string)($this->request->getPost('skill')          ?? ''));
        $qty    = max(1, (int)($this->request->getPost('quantity')        ?? 1));

        try {
            $db            = $this->firestore->getDb();
            $manpowerModel = new \App\Models\Model_Manpower();
            $candidates    = [];

            foreach ($manpowerModel->getAllManpower() as $m) {
                $mpStatus = $m['status'] ?? 'Available';
                if ($mpStatus !== 'Available') continue;

                $apid = (string)($m['assigned_project_id'] ?? '');
                if ($apid !== '' && $apid !== 'null') continue;

                if ($role !== '') {
                    $mpRole = strtolower($m['role'] ?? ($m['manpower_details']['role'] ?? ''));
                    if ($mpRole !== strtolower($role)) continue;
                }
                if ($spec !== '') {
                    $mpSpec = strtolower($m['specialization'] ?? ($m['manpower_details']['specialization'] ?? ''));
                    if ($mpSpec !== strtolower($spec)) continue;
                }
                if ($skill !== '') {
                    $mpSkills = (array)($m['skills'] ?? ($m['manpower_details']['skills'] ?? []));
                    $found = false;
                    foreach ($mpSkills as $s) {
                        if (stripos((string)$s, $skill) !== false) { $found = true; break; }
                    }
                    if (!$found) continue;
                }

                $candidates[] = $m['id'];
            }

            if (empty($candidates)) {
                return $this->response->setJSON(['success' => false, 'message' => 'No available workers match the selected filters.']);
            }

            shuffle($candidates);
            $toAssign = array_slice($candidates, 0, $qty);

            foreach ($toAssign as $mpId) {
                $db->collection('manpower')->document($mpId)->set([
                    'assigned_project_id' => $id,
                    'status'              => 'Deployed',
                ], ['merge' => true]);
            }

            return $this->response->setJSON(['success' => true, 'assigned' => count($toAssign)]);
        } catch (\Throwable $e) {
            log_message('error', 'Projects::bulkAssignManpower - ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to bulk-assign manpower']);
        }
    }

    public function bulkAssignEquipment(string $id)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthenticated']);
        }

        $resourceId = trim((string)($this->request->getPost('resource_id') ?? ''));
        if ($resourceId === '') {
            return $this->response->setJSON(['success' => false, 'message' => 'Missing resource ID']);
        }

        $brand    = strtolower(trim((string)($this->request->getPost('brand')    ?? '')));
        $quantity = max(1, (int)($this->request->getPost('quantity') ?? 9999));

        try {
            $db            = $this->firestore->getDb();
            $resourceModel = new \App\Models\Model_Resources();
            $items         = $resourceModel->getResourceItems($resourceId);
            $assigned      = 0;

            foreach ($items as $item) {
                if ($assigned >= $quantity) break;

                $status = $item['status'] ?? 'Available';
                if (!in_array($status, ['Available', 'In-Storage'], true)) continue;

                $apid = $item['assigned_project_id'] ?? null;
                $itemPid = $apid instanceof \Google\Cloud\Firestore\DocumentReference
                    ? $apid->id()
                    : (string)($apid ?? '');
                if ($itemPid !== '') continue;

                if ($brand !== '') {
                    $itemBrand = strtolower((string)($item['brand'] ?? $item['make'] ?? $item['manufacturer'] ?? ''));
                    if ($itemBrand === '' || stripos($itemBrand, $brand) === false) continue;
                }

                $unitId = $item['__id'] ?? $item['id'] ?? '';
                if ($unitId === '') continue;

                $db->collection('resource')->document($resourceId)->collection('items')->document($unitId)->set([
                    'assigned_project_id' => $db->collection('project')->document($id),
                    'status'              => 'In-Use',
                ], ['merge' => true]);
                $assigned++;
            }

            if ($assigned === 0) {
                return $this->response->setJSON(['success' => false, 'message' => 'No units available at warehouse for this model.']);
            }

            return $this->response->setJSON(['success' => true, 'assigned' => $assigned]);
        } catch (\Throwable $e) {
            log_message('error', 'Projects::bulkAssignEquipment - ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to bulk-assign equipment']);
        }
    }

    private function buildProjectPayload(): array
    {
        $projectName = trim((string) $this->request->getPost('project_name'));
        $projectType = (string) $this->request->getPost('project_type');
        $budget = (float) ($this->request->getPost('budget') ?: 0);

        $rawStart = $this->normalizeDateValue($this->request->getPost('start_date'));
        $rawEnd   = $this->normalizeDateValue($this->request->getPost('end_date'));
        $today    = date('Y-m-d');
        if ($rawStart === '' || $rawStart > $today) {
            $status = 'Planned';
        } elseif ($rawEnd !== '' && $rawEnd < $today) {
            $status = 'Past Deadline';
        } else {
            $status = 'On-going';
        }

        $tasks = $this->normalizePhases($this->request->getPost('phases') ?? []);
        $overallProgress = $this->calculateOverallProgress($tasks);

        return [
            'project_name' => $projectName,
            'project_type' => $projectType,
            'client_name'  => trim((string) $this->request->getPost('client_name')),
            'status' => $status,
            'budget' => $budget,
            'start_date' => $this->normalizeDateValue($this->request->getPost('start_date')),
            'end_date' => $this->normalizeDateValue($this->request->getPost('end_date')),
            'overall_progress' => $overallProgress,
            'completion_percent' => $overallProgress,
            'project_manager_id' => $this->request->getPost('project_manager_id'),
            'site_engineer_id' => $this->request->getPost('site_engineer_id'),
            'personnel' => [
                'project_manager_id' => $this->request->getPost('project_manager_id'),
                'site_engineer_id' => $this->request->getPost('site_engineer_id')
            ],
            'location' => [
                'address' => trim((string) $this->request->getPost('location_address')),
                'geopoint' => [
                    'lat' => $this->normalizeNumberOrNull($this->request->getPost('location_lat')),
                    'lng' => $this->normalizeNumberOrNull($this->request->getPost('location_lng'))
                ]
            ]
        ];
    }

    private function getProjectById(string $id): ?array
    {
        $snapshot = $this->firestore->getDb()->collection('project')->document($id)->snapshot();

        if (!$snapshot->exists()) {
            return null;
        }

        $project = $snapshot->data();
        $project['id'] = $snapshot->id();

        return $this->enrichProjectForView($project);
    }

    private function getProjectTasks(string $projectId): array
    {
        $tasks = [];

        foreach ($this->firestore->getDb()->collection('project')->document($projectId)->collection('tasks')->documents() as $doc) {
            if (!$doc->exists()) {
                continue;
            }

            $task = $doc->data();
            $task['id'] = $doc->id();
            $tasks[] = $task;
        }

        return $tasks ?: $this->defaultTaskRows();
    }

    private function syncProjectTasks(string $projectId, array $rawTasks): void
    {
        $tasks = $this->normalizeTasks($rawTasks);
        $tasksRef = $this->firestore->getDb()->collection('project')->document($projectId)->collection('tasks');

        foreach ($tasksRef->documents() as $existingTask) {
            if ($existingTask->exists()) {
                $tasksRef->document($existingTask->id())->delete();
            }
        }

        foreach ($tasks as $task) {
            $taskId = $task['id'] ?? bin2hex(random_bytes(8));
            $task['created_at'] = $task['created_at'] ?? time();
            $task['updated_at'] = time();

            $tasksRef->document($taskId)->set($task);
        }
    }

    // â”€â”€ Milestone helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /** Convert raw POST phases into the milestone schema for Firestore. */
    private function normalizeToMilestones(array $rawPhases): array
    {
        $milestones = [];
        $order      = 1;

        foreach ($rawPhases as $phase) {
            $name = trim((string) ($phase['name'] ?? ''));
            if ($name === '') continue;

            $targetDate = trim((string) ($phase['site_date'] ?? ''));

            $milestones[] = [
                'name'                 => $name,
                'order'                => $order++,
                'weight'               => (float) ($phase['weight'] ?? 0),
                'targetDate'           => $targetDate !== '' ? $targetDate : null,
                'actualCompletionDate' => null,
                'status'               => 'Pending',
            ];
        }

        return $milestones;
    }

    /** Validate that non-empty milestone weights sum to 100 (Â±0.5%). Returns an error string or null. */
    private function validateMilestoneWeights(array $rawPhases): ?string
    {
        $total = 0.0;
        foreach ($rawPhases as $phase) {
            if (trim((string) ($phase['name'] ?? '')) === '') continue;
            $total += (float) ($phase['weight'] ?? 0);
        }
        $total = round($total, 1);
        if (abs($total - 100.0) > 0.5) {
            return 'Milestone weights must sum to 100%. Current total: ' . number_format($total, 1) . '%.';
        }
        return null;
    }

    /** Write milestone documents (full replace) to projects/{id}/milestones. */
    private function writeMilestonesFlat(string $projectId, array $milestones): void
    {
        $ref = $this->firestore->getDb()
            ->collection('project')->document($projectId)->collection('milestones');

        foreach ($ref->documents() as $existing) {
            if ($existing->exists()) $ref->document($existing->id())->delete();
        }

        foreach ($milestones as $m) {
            $docId = bin2hex(random_bytes(8));
            $ref->document($docId)->set([
                'name'                 => (string) ($m['name']   ?? ''),
                'order'                => (int)    ($m['order']  ?? 0),
                'weight'               => (float)  ($m['weight'] ?? 0),
                'targetDate'           => $m['targetDate'] ?: null,
                'actualCompletionDate' => $m['actualCompletionDate'] ?? null,
                'status'               => (string) ($m['status'] ?? 'Pending'),
            ]);
        }
    }

    /** Delete all milestones for a project. */
    private function deleteProjectMilestones(string $projectId): void
    {
        $ref = $this->firestore->getDb()
            ->collection('project')->document($projectId)->collection('milestones');

        foreach ($ref->documents() as $doc) {
            if ($doc->exists()) $ref->document($doc->id())->delete();
        }
    }

    /** Load milestones ordered by the `order` field. */
    private function getMilestonesOrdered(string $projectId): array
    {
        $ref        = $this->firestore->getDb()
            ->collection('project')->document($projectId)->collection('milestones');
        $milestones = [];

        foreach ($ref->documents() as $doc) {
            if ($doc->exists()) {
                $data       = $doc->data();
                $data['id'] = $doc->id();
                $milestones[] = $data;
            }
        }

        usort($milestones, static fn($a, $b) => (int)($a['order'] ?? 0) <=> (int)($b['order'] ?? 0));

        return $milestones;
    }

    private function deleteProjectTasks(string $projectId): void
    {
        $tasksRef = $this->firestore->getDb()->collection('project')->document($projectId)->collection('tasks');

        foreach ($tasksRef->documents() as $task) {
            if ($task->exists()) {
                $tasksRef->document($task->id())->delete();
            }
        }
    }

    /**
     * Convert phases[index][name|weight|tasks[...]] POST data into
     * the flat task array that Firestore stores.
     * Each phase's weight is divided equally across its tasks so the
     * total always sums to 100 % (matching calculateOverallProgress).
     */
    private function normalizePhases(array $rawPhases): array
    {
        $flat = [];

        foreach ($rawPhases as $phase) {
            $phaseName   = trim((string)($phase['name']   ?? ''));
            $phaseWeight = (float)($phase['weight'] ?? 0);
            $rawTasks    = $phase['tasks'] ?? [];

            if ($phaseName === '') continue;

            // Filter to non-empty tasks only
            $validTasks = array_values(array_filter($rawTasks, function ($t) {
                return trim((string)($t['task_name'] ?? '')) !== '';
            }));

            $phaseQty      = max(0, (int)($phase['qty']      ?? 1));
            $phaseUnit     = trim((string)($phase['unit']     ?? 'pcs')) ?: 'pcs';
            $phaseSiteDate = isset($phase['site_date']) ? trim((string)$phase['site_date']) : '';

            if (empty($validTasks)) {
                // Phase with no tasks: store as a single phase-level entry
                $flat[] = [
                    'id'                 => bin2hex(random_bytes(8)),
                    'phase_name'         => $phaseName,
                    'task_name'          => '',
                    'weight_percent'     => $phaseWeight,
                    'qty'                => $phaseQty,
                    'unit'               => $phaseUnit,
                    'completion_percent' => 0.0,
                    'status'             => 'Pending',
                    'phase_qty'          => $phaseQty,
                    'phase_unit'         => $phaseUnit,
                    'phase_site_date'    => $phaseSiteDate,
                ];
                continue;
            }

            // Distribute phase weight evenly across its tasks
            $count         = count($validTasks);
            $perTaskWeight = round($phaseWeight / $count, 4);

            foreach ($validTasks as $task) {
                $flat[] = [
                    'id'                 => trim((string)($task['id'] ?? '')) ?: bin2hex(random_bytes(8)),
                    'phase_name'         => $phaseName,
                    'task_name'          => trim((string)($task['task_name'] ?? '')),
                    'weight_percent'     => $perTaskWeight,
                    'qty'                => max(0, (int)($task['qty'] ?? 0)),
                    'unit'               => trim((string)($task['unit'] ?? 'pcs')) ?: 'pcs',
                    'completion_percent' => (float)($task['completion_percent'] ?? 0),
                    'status'             => $this->normalizeTaskStatus((string)($task['status'] ?? 'Pending')),
                    'phase_qty'          => $phaseQty,
                    'phase_unit'         => $phaseUnit,
                    'phase_site_date'    => $phaseSiteDate,
                ];
            }
        }

        return $flat;
    }

    /**
     * Group the flat task array (from Firestore) back into a
     * phases array that the form view expects.
     */
    private function groupTasksIntoPhases(array $tasks): array
    {
        $phases   = [];
        $phaseMap = [];

        foreach ($tasks as $task) {
            $phaseName = trim((string)($task['phase_name'] ?? 'General'));

            if (!isset($phaseMap[$phaseName])) {
                $phaseMap[$phaseName] = count($phases);
                $phases[] = [
                    'name'      => $phaseName,
                    'weight'    => 0.0,
                    'tasks'     => [],
                    'qty'       => isset($task['phase_qty'])      ? $task['phase_qty']      : ($task['qty'] ?? 1),
                    'unit'      => isset($task['phase_unit'])     ? $task['phase_unit']     : ($task['unit'] ?? 'pcs'),
                    'site_date' => isset($task['phase_site_date']) ? $task['phase_site_date'] : '',
                ];
            }

            $pIdx = $phaseMap[$phaseName];
            $phases[$pIdx]['weight'] += (float)($task['weight_percent'] ?? 0);
        }

        // Round accumulated weights to 1 decimal
        foreach ($phases as &$p) {
            $p['weight'] = round($p['weight'], 1);
        }

        return $phases;
    }

    /** Load saved tasks and return them as a phase-grouped array. */
    private function getProjectPhases(string $projectId): array
    {
        $tasks = [];

        foreach ($this->firestore->getDb()->collection('project')->document($projectId)->collection('tasks')->documents() as $doc) {
            if ($doc->exists()) {
                $data       = $doc->data();
                $data['id'] = $doc->id();
                $tasks[]    = $data;
            }
        }

        return $this->groupTasksIntoPhases($tasks);
    }

    /** Write an already-normalised flat task array to Firestore. */
    private function writeTasksFlat(string $projectId, array $flatTasks): void
    {
        $ref = $this->firestore->getDb()->collection('project')->document($projectId)->collection('tasks');

        foreach ($ref->documents() as $existing) {
            if ($existing->exists()) $ref->document($existing->id())->delete();
        }

        foreach ($flatTasks as $task) {
            $taskId = $task['id'] ?? bin2hex(random_bytes(8));
            $task['created_at'] = $task['created_at'] ?? time();
            $task['updated_at'] = time();
            $ref->document($taskId)->set($task);
        }
    }

    private function normalizeTasks(array $rawTasks): array
    {
        $tasks = [];

        foreach ($rawTasks as $task) {
            $taskName = trim((string) ($task['task_name'] ?? ''));
            $phaseName = trim((string) ($task['phase_name'] ?? ''));

            if ($taskName === '' && $phaseName === '') {
                continue;
            }

            $tasks[] = [
                'id' => trim((string) ($task['id'] ?? '')) ?: bin2hex(random_bytes(8)),
                'task_name' => $taskName,
                'phase_name' => $phaseName,
                'weight_percent' => $this->normalizeNumberOrNull($task['weight_percent'] ?? 0) ?? 0,
                'completion_percent' => $this->normalizeNumberOrNull($task['completion_percent'] ?? 0) ?? 0,
                'status' => $this->normalizeTaskStatus((string) ($task['status'] ?? 'Pending'))
            ];
        }

        return $tasks;
    }

    private function calculateOverallProgress(array $tasks): float
    {
        if (empty($tasks)) {
            return 0.0;
        }

        $totalWeight = 0.0;
        $weightedCompletion = 0.0;

        foreach ($tasks as $task) {
            $weight = (float) ($task['weight_percent'] ?? 0);
            $completion = (float) ($task['completion_percent'] ?? 0);
            $totalWeight += $weight;
            $weightedCompletion += ($weight * $completion);
        }

        if ($totalWeight <= 0) {
            return 0.0;
        }

        return round($weightedCompletion / $totalWeight, 2);
    }

    private function enrichProjectForView(array $project): array
    {
        $personnel = is_array($project['personnel'] ?? null) ? $project['personnel'] : [];
        if (!$personnel) {
            $personnel = [
                'project_manager_id' => $project['project_manager_id'] ?? null,
                'site_engineer_id' => $project['site_engineer_id'] ?? null
            ];
        }

        $location = is_array($project['location'] ?? null) ? $project['location'] : [];
        if (!$location && isset($project['location']) && is_string($project['location'])) {
            $location = ['address' => $project['location']];
        }

        $geopoint = is_array($location['geopoint'] ?? null) ? $location['geopoint'] : [];

        $project['location'] = $location;
        $project['location_label'] = trim((string) ($location['address'] ?? ''));
        $project['project_manager_name'] = $this->resolveUserName($personnel['project_manager_id'] ?? null);
        $project['site_engineer_name'] = $this->resolveUserName($personnel['site_engineer_id'] ?? null);
        $project['progress_label'] = number_format((float) ($project['overall_progress'] ?? $project['completion_percent'] ?? 0), 2);
        $project['start_date_value'] = $this->normalizeDateValue($project['start_date'] ?? '');
        $project['end_date_value'] = $this->normalizeDateValue($project['end_date'] ?? '');
        $project['location_address_value'] = (string) ($location['address'] ?? '');
        $project['location_lat_value'] = (string) ($geopoint['lat'] ?? '');
        $project['location_lng_value'] = (string) ($geopoint['lng'] ?? '');

        $rawPmId = $this->normalizeReferenceValue($personnel['project_manager_id'] ?? null);
        $rawSeId = $this->normalizeReferenceValue($personnel['site_engineer_id'] ?? null);
        $project['pm_id_normalized'] = preg_replace('#^.*/#', '', $rawPmId) ?: $rawPmId;
        $project['se_id_normalized'] = preg_replace('#^.*/#', '', $rawSeId) ?: $rawSeId;

        return $project;
    }

    private function resolveUserName($userId): string
    {
        $rawUserId = $this->normalizeReferenceValue($userId);

        if ($rawUserId === '') {
            return 'N/A';
        }
        $normalizedId = preg_replace('#^.*/#', '', $rawUserId) ?: $rawUserId;

        $raw = $this->usersModel->getUserById($normalizedId);
        // getUserById returns ['id' => ..., 'data' => [...]] â€” unwrap the data array
        $user = is_array($raw['data'] ?? null) ? $raw['data'] : $raw;

        // Backward compatibility: older records may store email in *_id fields.
        if (!$user && filter_var($rawUserId, FILTER_VALIDATE_EMAIL)) {
            $userByEmail = $this->usersModel->getUserByEmail($rawUserId);
            $user = is_array($userByEmail['data'] ?? null) ? $userByEmail['data'] : null;
        }

        if (!$user) {
            return $rawUserId;
        }

        $firstName = trim((string) ($user['first_name'] ?? ''));
        $lastName = trim((string) ($user['last_name'] ?? ''));
        $fullName = trim($firstName . ' ' . $lastName);

        if ($fullName !== '') {
            return $fullName;
        }

        return (string) ($user['name'] ?? $user['full_name'] ?? $user['email'] ?? $rawUserId);
    }

    private function normalizeReferenceValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
            return trim((string) $value);
        }

        if (is_object($value)) {
            if (method_exists($value, 'id')) {
                try {
                    $id = $value->id();
                    if (is_string($id) || is_int($id) || is_float($id) || is_bool($id)) {
                        return trim((string) $id);
                    }
                } catch (\Throwable) {
                    // Fall through to other extraction strategies.
                }
            }

            if (method_exists($value, 'path')) {
                try {
                    $path = $value->path();
                    if (is_string($path) || is_int($path) || is_float($path) || is_bool($path)) {
                        return trim((string) $path);
                    }
                } catch (\Throwable) {
                    // Ignore and continue.
                }
            }

            if (method_exists($value, '__toString')) {
                try {
                    return trim((string) $value);
                } catch (\Throwable) {
                    return '';
                }
            }

            return '';
        }

        return trim((string) $value);
    }

    private function normalizeDateValue($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_numeric($value)) {
            return date('Y-m-d', (int) $value);
        }

        $timestamp = strtotime((string) $value);

        return $timestamp ? date('Y-m-d', $timestamp) : '';
    }

    private function normalizeNumberOrNull($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function normalizeTaskStatus(string $status): string
    {
        $allowed = ['Pending', 'In Progress', 'Done'];

        return in_array($status, $allowed, true) ? $status : 'Pending';
    }

    private function defaultTaskRows(): array
    {
        return [[
            'id' => '',
            'task_name' => '',
            'phase_name' => '',
            'weight_percent' => '',
            'completion_percent' => '',
            'status' => 'Pending'
        ]];
    }

    private function getExistingClientNames(): array
    {
        $clients = [];
        foreach ($this->firestore->getDb()->collection('project')->documents() as $doc) {
            if (!$doc->exists()) continue;
            $cn = strtoupper(trim((string)($doc->data()['client_name'] ?? '')));
            if ($cn !== '') $clients[$cn] = true;
        }
        ksort($clients);
        return array_keys($clients);
    }

    private function resolveRegion(string $address): string
    {
        $addr = strtolower($address);

        $luzon = [
            'metro manila','ncr','manila','quezon city','makati','pasig','taguig',
            'mandaluyong','marikina','caloocan','las piÃ±as','muntinlupa','navotas',
            'paraÃ±aque','pasay','san juan','valenzuela','pateros',
            'ilocos norte','ilocos sur','la union','pangasinan',
            'benguet','baguio','ifugao','kalinga','mountain province','apayao','abra',
            'cagayan','isabela','nueva vizcaya','quirino','batanes',
            'nueva ecija','pampanga','tarlac','zambales','bulacan','bataan','aurora',
            'cavite','laguna','batangas','rizal','quezon province',
            'marinduque','occidental mindoro','oriental mindoro','palawan','romblon',
            'albay','camarines norte','camarines sur','catanduanes','masbate','sorsogon',
        ];

        $visayas = [
            'aklan','antique','capiz','guimaras','iloilo','negros occidental','bacolod',
            'bohol','cebu','negros oriental','siquijor','dumaguete',
            'biliran','eastern samar','leyte','northern samar','samar','southern leyte','tacloban',
            'boracay',
        ];

        $mindanao = [
            'zamboanga del norte','zamboanga del sur','zamboanga sibugay','zamboanga city',
            'basilan','lanao del norte','misamis occidental','misamis oriental','iligan','cagayan de oro',
            'bukidnon','camiguin','lanao del sur',
            'maguindanao','sultan kudarat','cotabato','north cotabato',
            'davao del norte','davao del sur','davao occidental','davao oriental','davao city',
            'compostela valley','tagum','digos',
            'agusan del norte','agusan del sur','dinagat','surigao del norte','surigao del sur','butuan',
            'general santos','sarangani','south cotabato',
            'tawi-tawi','sulu','basilan',
        ];

        foreach ($luzon    as $kw) { if (str_contains($addr, $kw)) return 'LUZON';    }
        foreach ($visayas  as $kw) { if (str_contains($addr, $kw)) return 'VISAYAS';  }
        foreach ($mindanao as $kw) { if (str_contains($addr, $kw)) return 'MINDANAO'; }

        return '';
    }
}
