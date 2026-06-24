<?php

namespace App\Controllers;

use App\Libraries\ArchiveService;
use App\Libraries\FirestoreService;
use App\Libraries\MilestoneProgressService;
use App\Libraries\StorageService;
use App\Libraries\TrashService;
use App\Models\Model_Users;
use App\Services\NotificationService;
use Config\GoogleMaps;
use Google\Cloud\Core\Timestamp;

class Projects extends BaseController
{
    protected FirestoreService $firestore;
    protected Model_Users $usersModel;

    public function __construct()
    {
        $this->firestore = FirestoreService::getInstance();
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

                try {
                    $projectData = $doc->data();
                    $projectData['id'] = $doc->id();
                    $projects[] = $this->enrichProjectForView($projectData);
                } catch (\Throwable $te) {
                    log_message('error', 'Projects::index enrich [' . $doc->id() . '] - ' . $te->getMessage());
                }
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
            // Delayed/Past Deadline always bubble to the top; secondary sort by requested column
            $urgencyWeight = function (array $p): int {
                $s = strtolower($p['status'] ?? '');
                if ($s === 'past deadline') return 0;
                if ($s === 'delayed')       return 1;
                return 2;
            };
            if ($filterSort === 'progress_desc') {
                usort($projects, function ($a, $b) use ($urgencyWeight) {
                    $uw = $urgencyWeight($a) <=> $urgencyWeight($b);
                    return $uw !== 0 ? $uw : (float)($b['overall_progress'] ?? 0) <=> (float)($a['overall_progress'] ?? 0);
                });
            } elseif ($filterSort === 'progress_asc') {
                usort($projects, function ($a, $b) use ($urgencyWeight) {
                    $uw = $urgencyWeight($a) <=> $urgencyWeight($b);
                    return $uw !== 0 ? $uw : (float)($a['overall_progress'] ?? 0) <=> (float)($b['overall_progress'] ?? 0);
                });
            } else {
                usort($projects, fn($a, $b) => $urgencyWeight($a) <=> $urgencyWeight($b));
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
        } catch (\Throwable $e) {
            log_message('error', 'Projects::index - ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());

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
                $now = new Timestamp(new \DateTime());
                $projectData['created_at'] = $now;
                $projectData['updated_at'] = $now;

                log_message('info', 'Projects::create - POST received. Preparing to write project: ' . ($projectData['project_name'] ?? '(no name)'));

                $this->firestore->getDb()
                    ->collection('project')
                    ->document($projectId)
                    ->set($projectData);

                $this->notifyAssigned(
                    $projectId,
                    (string)($projectData['project_name'] ?? ''),
                    (string)($projectData['project_manager_id'] ?? ''),
                    (string)($projectData['site_engineer_id']   ?? '')
                );

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
                $projectData['created_at'] = $existing['created_at'] ?? new Timestamp(new \DateTime());
                $projectData['updated_at'] = new Timestamp(new \DateTime());

                // Preserve terminal statuses set outside the form
                $existingStatus = (string)($existing['status'] ?? '');
                if (in_array($existingStatus, ['Completed', 'Archived'], true)) {
                    $projectData['status'] = $existingStatus;
                }

                // If user did not opt to add/update tasks now, preserve existing overall progress
                $addTasksNow = (bool) $this->request->getPost('add_tasks_now');
                if (!$addTasksNow) {
                    $projectData['overall_progress'] = $existing['overall_progress'] ?? $existing['completion_percent'] ?? 0;
                }

                log_message('info', 'Projects::update - POST received for project id: ' . $id . '; name: ' . ($projectData['project_name'] ?? '(no name)'));

                $this->firestore->getDb()
                    ->collection('project')
                    ->document($id)
                    ->set($projectData);

                log_message('info', 'Projects::update - Firestore set() completed for id: ' . $id);

                // Notify only if PM or SE assignment changed
                $newPmId = (string)($projectData['project_manager_id'] ?? '');
                $newSeId = (string)($projectData['site_engineer_id']   ?? '');
                $oldPmId = (string)($existing['pm_id_normalized'] ?? '');
                $oldSeId = (string)($existing['se_id_normalized'] ?? '');
                $this->notifyAssigned(
                    $id,
                    (string)($projectData['project_name'] ?? ''),
                    $newPmId !== $oldPmId ? $newPmId : '',
                    $newSeId !== $oldSeId ? $newSeId : ''
                );

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

    public function deleted(): string|object
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        $items = (new TrashService($this->firestore))->getTrashItems('project');
        return view('shared/module_deleted', [
            'pageTitle'  => 'Deleted Projects',
            'activePage' => 'projects',
            'userName'   => session()->get('userName') ?? 'User',
            'role'       => session()->get('role') ?? 'Staff',
            'module'     => 'project',
            'title'      => 'Deleted Projects',
            'backUrl'    => base_url('projects'),
            'items'      => $items,
        ]);
    }

    public function archived(): string|object
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        $items = (new ArchiveService($this->firestore))->getArchivedItems('project');
        return view('shared/module_archived', [
            'pageTitle'  => 'Archived Projects',
            'activePage' => 'projects',
            'userName'   => session()->get('userName') ?? 'User',
            'role'       => session()->get('role') ?? 'Staff',
            'module'     => 'project',
            'title'      => 'Archived Projects',
            'backUrl'    => base_url('projects'),
            'items'      => $items,
        ]);
    }

    public function archive(): object
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        try {
            $id = trim((string)($this->request->getPost('id') ?? ''));
            if (!$id) return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing id']);

            $snap = $this->firestore->getDb()->collection('project')->document($id)->snapshot();
            $data = $snap->exists() ? $snap->data() : [];
            $name = (string)($data['project_name'] ?? $id);

            (new ArchiveService($this->firestore))->moveToArchive('project', $id, $name, $data, [], session()->get('userName'));
            $this->firestore->getDb()->collection('project')->document($id)->delete();

            return $this->response->setJSON(['success' => true, 'name' => $name]);
        } catch (\Throwable $e) {
            log_message('error', 'Projects::archive - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Archive failed']);
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

                $this->releaseProjectResources($id);
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

                $this->releaseProjectResources($id);
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
                    $weight = (float) ($m['weight_percent'] ?? $m['weight'] ?? 0);
                    $isDone = in_array(strtolower((string)($m['status'] ?? '')), ['done', 'completed'], true)
                              || !empty($m['actual_completion_date']) || !empty($m['actualCompletionDate']);

                    $cumPlanned += $weight;
                    if ($isDone) $cumActual += $weight;

                    $dated = !empty($m['target_date']) || !empty($m['targetDate']);
                    $milestoneSummary[] = [
                        'name'        => (string) ($m['milestone_name'] ?? $m['name'] ?? ''),
                        'target_date' => $dated ? (string) ($m['target_date'] ?? $m['targetDate']) : null,
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

    // ── Progress Summary JSON (for modal Weekly / Overall buttons) ────────

    public function progressSummaryJson(string $id)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        try {
            $db   = $this->firestore->getDb();
            $snap = $db->collection('project')->document($id)->snapshot();
            if (!$snap->exists()) {
                return $this->response->setStatusCode(404)->setJSON(['error' => 'Not found']);
            }

            $p = $snap->data();

            $baselineType    = (string)($p['baselineType']    ?? 'weekly');
            $overallProgress = (float)($p['overall_progress'] ?? 0);
            $startDate       = $this->psTsToDate($p['start_date'] ?? null);
            $endDate         = $this->psTsToDate($p['end_date']   ?? null);

            // weekly_targets array (only present when schedule has been saved on mobile)
            $weeklyTargets = [];
            foreach ((array)($p['weekly_targets'] ?? []) as $wt) {
                if (!is_array($wt)) continue;
                $weeklyTargets[] = [
                    'week'           => isset($wt['week']) ? (int)$wt['week'] : null,
                    'week_start'     => $this->psTsToDate($wt['week_start'] ?? null),
                    'target_percent' => (float)($wt['target_percent'] ?? 0),
                    'actual_percent' => isset($wt['actual_percent']) ? (float)$wt['actual_percent'] : null,
                ];
            }

            // milestones subcollection — use canonical schema field names
            $milestones = [];
            foreach ($db->collection('project')->document($id)->collection('milestones')->documents() as $mDoc) {
                if (!$mDoc->exists()) continue;
                $m = $mDoc->data();
                $milestones[] = [
                    'id'                 => $mDoc->id(),
                    'milestone_name'     => (string)($m['milestone_name']    ?? ''),
                    'order'              => (int)($m['order']                ?? 0),
                    'weight_percent'     => (float)($m['weight_percent']     ?? 0),
                    'target_quantity'    => (float)($m['target_quantity']    ?? 0),
                    'completed_quantity' => (float)($m['completed_quantity'] ?? 0),
                    'completion_percent' => (float)($m['completion_percent'] ?? 0),
                    'status'             => (string)($m['status']            ?? 'Not Started'),
                    'target_date'        => $this->psTsToDate($m['target_date'] ?? null),
                    'unit'               => (string)($m['unit']              ?? ''),
                ];
            }
            usort($milestones, static fn($a, $b) => $a['order'] <=> $b['order']);

            return $this->response->setJSON([
                'baselineType'    => $baselineType,
                'overallProgress' => $overallProgress,
                'startDate'       => $startDate,
                'endDate'         => $endDate,
                'weeklyTargets'   => $weeklyTargets,
                'milestones'      => $milestones,
                'reportDate'      => date('Y-m-d'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Projects::progressSummaryJson - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Server error']);
        }
    }

    // ── Project Schedule / Milestone Management ───────────────────────────

    public function schedule(string $id)
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('auth/login');
        }
        if (!pz_can('manage_milestones')) {
            return redirect()->to('projects')->with('error', 'You do not have permission to manage the schedule.');
        }

        try {
            $project = $this->getProjectById($id);
            if (!$project) {
                return redirect()->to('projects')->with('error', 'Project not found.');
            }

            if ($this->request->getMethod() === 'post') {
                // Preserve status / actual_completion_date from existing milestones (matched by order)
                $existing       = $this->getMilestonesOrdered($id);
                $existByOrder   = [];
                foreach ($existing as $m) {
                    $existByOrder[(int)($m['order'] ?? 0)] = $m;
                }

                $rawMs      = $this->request->getPost('milestones') ?? [];
                $milestones = [];
                $order      = 1;

                foreach ($rawMs as $m) {
                    $name = trim((string)($m['name'] ?? ''));
                    if ($name === '') continue;
                    $ex = $existByOrder[$order] ?? [];
                    $milestones[] = [
                        'milestone_name'         => $name,
                        'order'                  => $order,
                        'weight_percent'         => (float)($m['weight'] ?? 0),
                        'target_quantity'        => max(1, (int)($m['qty'] ?? 1)),
                        'completed_quantity'     => (int)($ex['completed_quantity'] ?? 0),
                        'unit'                   => trim((string)($m['unit'] ?? 'pcs')) ?: 'pcs',
                        'target_date'            => trim((string)($m['target_date'] ?? '')) ?: null,
                        'actual_completion_date' => $ex['actual_completion_date'] ?? null,
                        'status'                 => $ex['status'] ?? 'Pending',
                    ];
                    $order++;
                }

                if (!empty($milestones)) {
                    $total = round(array_sum(array_column($milestones, 'weight_percent')), 1);
                    if (abs($total - 100.0) > 0.5) {
                        return redirect()->back()->withInput()
                            ->with('error', 'Milestone weights must sum to 100 %. Current total: ' . number_format($total, 1) . ' %.');
                    }
                }

                $ref = $this->firestore->getDb()
                    ->collection('project')->document($id)->collection('milestones');
                foreach ($ref->documents() as $doc) {
                    if ($doc->exists()) $ref->document($doc->id())->delete();
                }
                foreach ($milestones as $m) {
                    $ref->document(bin2hex(random_bytes(8)))->set($m);
                }

                return redirect()->to("projects/{$id}/schedule")->with('success', 'Schedule saved successfully.');
            }

            $milestones    = $this->getMilestonesOrdered($id);
            $startDate     = (string)($project['start_date_value'] ?? '');
            $endDate       = (string)($project['end_date_value']   ?? '');
            $weeklyTargets = [];

            if (!empty($milestones) && $startDate !== '') {
                $svc           = new MilestoneProgressService();
                $curve         = $svc->computeCurve($milestones, $startDate, $endDate ?: null);
                $weeklyTargets = $svc->weeklyTargets($curve['controlPoints'], $startDate, date('Y-m-d'));
            }

            return view('projects/project_schedule', [
                'pageTitle'     => 'Project Schedule',
                'activePage'    => 'projects',
                'project'       => $project,
                'projectId'     => $id,
                'milestones'    => $milestones,
                'weeklyTargets' => $weeklyTargets,
                'startDate'     => $startDate,
                'endDate'       => $endDate,
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Projects::schedule - ' . $e->getMessage());
            return redirect()->to('projects')->with('error', 'Unable to load project schedule.');
        }
    }

    private function psTsToDate(mixed $value): ?string
    {
        if ($value === null) return null;
        if ($value instanceof \Google\Cloud\Core\Timestamp) {
            try {
                return \DateTimeImmutable::createFromInterface($value->get())
                    ->setTimezone(new \DateTimeZone('Asia/Manila'))
                    ->format('Y-m-d');
            } catch (\Throwable) { return null; }
        }
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value, $m)) {
            return $m[0];
        }
        return null;
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
                $rawApid = $m['assigned_project_id'] ?? '';
                $apid = $rawApid instanceof \Google\Cloud\Firestore\DocumentReference
                    ? $rawApid->id()
                    : trim((string)$rawApid);
                if ($apid === $id) {
                    $assignedManpower[]          = ['id' => $mpId, 'name' => $name];
                    $assignedManpowerIds[$mpId]  = 'mp:' . $mpId;
                }
            }
            usort($allManpower, fn($a, $b) => strcasecmp($a['name'], $b['name']));

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
                    $assignedEqGroups[] = ['id' => $resId, 'name' => $resName, 'count' => $assignedCount];
                }
            }
            usort($allEquipment, fn($a, $b) => strcasecmp($a['name'], $b['name']));
            usort($allTools,     fn($a, $b) => strcasecmp($a['name'], $b['name']));

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

            $retName     = '';
            $retCategory = '';
            if ($type === 'manpower') {
                $db->collection('manpower')->document($refId)->set([
                    'assigned_project_id' => $id,
                    'status'              => 'Deployed',
                ], ['merge' => true]);

                // Sync to project.resources so mobile sees the assignment
                $mpData = $db->collection('manpower')->document($refId)->snapshot()->data() ?? [];
                $fn = strtoupper(trim((string)($mpData['first_name'] ?? '')));
                $ln = strtoupper(trim((string)($mpData['last_name']  ?? '')));
                $retName = $fn . ($fn && $ln ? ' ' : '') . $ln ?: strtoupper($mpData['name'] ?? '');
                if ($retName !== '') {
                    $db->collection('project')->document($id)->set(
                        ['resources' => \Google\Cloud\Firestore\FieldValue::arrayUnion([[
                            'name' => $retName, 'ref_id' => $refId, 'unit_id' => '', 'category' => 'manpower',
                        ]])],
                        ['merge' => true]
                    );
                }
                $retCategory = 'Manpower';
            } else {
                $db->collection('resource')->document($refId)->collection('items')->document($unitId)->set([
                    'assigned_project_id' => $db->collection('project')->document($id),
                    'status'              => 'In-Use',
                ], ['merge' => true]);

                // Sync to project.resources so mobile sees the assignment
                $resData = $db->collection('resource')->document($refId)->snapshot()->data() ?? [];
                $retName  = strtoupper(trim((string)($resData['name'] ?? '')));
                $retCategory = ucfirst(strtolower(trim((string)($resData['category'] ?? 'Equipment'))));
                if ($retName !== '') {
                    $db->collection('project')->document($id)->set(
                        ['resources' => \Google\Cloud\Firestore\FieldValue::arrayUnion([[
                            'name' => $retName, 'ref_id' => $refId, 'unit_id' => $unitId, 'category' => $retCategory,
                        ]])],
                        ['merge' => true]
                    );
                }
            }

            $assignmentId = $type === 'manpower' ? 'mp:' . $refId : 'unit:' . $refId . ':' . $unitId;
            return $this->response->setJSON([
                'success'       => true,
                'csrf_hash'     => csrf_hash(),
                'assignment_id' => $assignmentId,
                'name'          => $retName,
                'category'      => $retCategory,
            ]);
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
                $this->removeFromProjectResources($db, $id, $mpId, '');
            } elseif (str_starts_with($assignmentId, 'unit:')) {
                [, $resId, $unitId] = explode(':', $assignmentId, 3);
                $db->collection('resource')->document($resId)->collection('items')->document($unitId)->set([
                    'assigned_project_id' => null,
                    'status'              => 'In-Storage',
                ], ['merge' => true]);
                $this->removeFromProjectResources($db, $id, $resId, $unitId);
            } else {
                return $this->response->setJSON(['success' => false, 'message' => 'Invalid assignment ID']);
            }

            return $this->response->setJSON(['success' => true, 'csrf_hash' => csrf_hash()]);
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
            $db            = $this->firestore->getDb();
            $resourceModel = new \App\Models\Model_Resources();
            $items         = $resourceModel->getResourceItems($resourceId);

            // Cache project-existence checks so we only hit Firestore once per
            // unique foreign project ID (handles orphaned refs from deleted projects).
            $projectExistsCache = [];

            $out = [];
            foreach ($items as $item) {
                $unitId  = $item['__id'] ?? $item['id'] ?? '';
                $apid    = $item['assigned_project_id'] ?? null;
                $itemPid = $apid instanceof \Google\Cloud\Firestore\DocumentReference
                    ? $apid->id()
                    : (string)($apid ?? '');

                // If this item points to a project that is not the current one,
                // verify that project still exists. If it was deleted, treat the
                // item as unassigned so it can be reassigned immediately.
                if ($itemPid !== '' && $itemPid !== $id) {
                    if (!array_key_exists($itemPid, $projectExistsCache)) {
                        try {
                            $projectExistsCache[$itemPid] = $db
                                ->collection('project')->document($itemPid)
                                ->snapshot()->exists();
                        } catch (\Throwable) {
                            $projectExistsCache[$itemPid] = true; // assume exists on error
                        }
                    }
                    if (!$projectExistsCache[$itemPid]) {
                        $itemPid = ''; // orphaned reference — project is gone
                    }
                }

                $isHere = ($itemPid === $id);

                $out[] = [
                    'id'                       => $unitId,
                    'serial_number'            => $item['serial_number'] ?? ($item['unit_name'] ?? $unitId),
                    'status'                   => $item['status'] ?? 'Unknown',
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

                $rawApid2 = $m['assigned_project_id'] ?? '';
                $apid = $rawApid2 instanceof \Google\Cloud\Firestore\DocumentReference
                    ? $rawApid2->id()
                    : trim((string)$rawApid2);
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

                $mn = strtoupper(trim((string)($m['middle_name'] ?? '')));
                $fn = strtoupper(trim((string)($m['first_name']  ?? '')));
                $ln = strtoupper(trim((string)($m['last_name']   ?? '')));
                $mpName = implode(' ', array_filter([$fn, $mn, $ln], fn($p) => $p !== ''))
                       ?: strtoupper($m['name'] ?? '');
                $candidates[] = ['id' => $m['id'], 'name' => $mpName];
            }

            if (empty($candidates)) {
                return $this->response->setJSON(['success' => false, 'message' => 'No available workers match the selected filters.']);
            }

            shuffle($candidates);
            $toAssign = array_slice($candidates, 0, $qty);

            $resourceEntries = [];
            foreach ($toAssign as $cand) {
                $db->collection('manpower')->document($cand['id'])->set([
                    'assigned_project_id' => $id,
                    'status'              => 'Deployed',
                ], ['merge' => true]);
                if ($cand['name'] !== '') {
                    $resourceEntries[] = [
                        'name' => $cand['name'], 'ref_id' => $cand['id'], 'unit_id' => '', 'category' => 'manpower',
                    ];
                }
            }
            if (!empty($resourceEntries)) {
                $db->collection('project')->document($id)->set(
                    ['resources' => \Google\Cloud\Firestore\FieldValue::arrayUnion($resourceEntries)],
                    ['merge' => true]
                );
            }

            return $this->response->setJSON([
                'success'   => true,
                'assigned'  => count($toAssign),
                'csrf_hash' => csrf_hash(),
                'workers'   => $toAssign,
            ]);
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

            // Fetch resource name/category once for the project.resources sync
            $resData  = $db->collection('resource')->document($resourceId)->snapshot()->data() ?? [];
            $resName  = strtoupper(trim((string)($resData['name'] ?? '')));
            $category = ucfirst(strtolower(trim((string)($resData['category'] ?? 'Equipment'))));

            $projectExistsCache = [];
            $resourceEntries = [];
            foreach ($items as $item) {
                if ($assigned >= $quantity) break;

                $status = $item['status'] ?? 'Available';
                if (!in_array($status, ['Available', 'In-Storage'], true)) continue;

                $apid    = $item['assigned_project_id'] ?? null;
                $itemPid = $apid instanceof \Google\Cloud\Firestore\DocumentReference
                    ? $apid->id()
                    : (string)($apid ?? '');

                // Orphaned reference guard: if the assigned project no longer
                // exists treat this item as available.
                if ($itemPid !== '') {
                    if (!array_key_exists($itemPid, $projectExistsCache)) {
                        try {
                            $projectExistsCache[$itemPid] = $db
                                ->collection('project')->document($itemPid)
                                ->snapshot()->exists();
                        } catch (\Throwable) {
                            $projectExistsCache[$itemPid] = true;
                        }
                    }
                    if (!$projectExistsCache[$itemPid]) {
                        $itemPid = '';
                    }
                }
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

                if ($resName !== '') {
                    $resourceEntries[] = [
                        'name' => $resName, 'ref_id' => $resourceId, 'unit_id' => $unitId, 'category' => $category,
                    ];
                }
            }

            if ($assigned === 0) {
                return $this->response->setJSON(['success' => false, 'message' => 'No units available at warehouse for this model.']);
            }

            if (!empty($resourceEntries)) {
                $db->collection('project')->document($id)->set(
                    ['resources' => \Google\Cloud\Firestore\FieldValue::arrayUnion($resourceEntries)],
                    ['merge' => true]
                );
            }

            return $this->response->setJSON(['success' => true, 'assigned' => $assigned, 'csrf_hash' => csrf_hash()]);
        } catch (\Throwable $e) {
            log_message('error', 'Projects::bulkAssignEquipment - ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to bulk-assign equipment']);
        }
    }

    private function releaseProjectResources(string $projectId): void
    {
        $db         = $this->firestore->getDb();
        $projectRef = $db->collection('project')->document($projectId);
        $deleteField = \Google\Cloud\Firestore\FieldValue::deleteField();

        // ── Manpower ─────────────────────────────────────────────────────────
        try {
            $mpDocs = $db->collection('manpower')
                ->where('assigned_project_id', '=', $projectId)
                ->documents();
            foreach ($mpDocs as $doc) {
                if (!$doc->exists()) continue;
                $doc->reference()->update([
                    ['path' => 'assigned_project_id', 'value' => $deleteField],
                    ['path' => 'status',              'value' => 'Available'],
                ]);
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Projects::releaseProjectResources manpower - ' . $e->getMessage());
        }

        // ── Equipment / Tools ─────────────────────────────────────────────────
        // Primary: collectionGroup query by DocumentReference (fast, needs index)
        $releasedPaths = [];
        try {
            $itemDocs = $db->collectionGroup('items')
                ->where('assigned_project_id', '=', $projectRef)
                ->documents();
            foreach ($itemDocs as $doc) {
                if (!$doc->exists()) continue;
                $doc->reference()->update([
                    ['path' => 'assigned_project_id', 'value' => $deleteField],
                    ['path' => 'status',              'value' => 'In-Storage'],
                ]);
                $releasedPaths[$doc->name()] = true;
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Projects::releaseProjectResources equipment (ref) - ' . $e->getMessage());
        }

        // Secondary: collectionGroup query by plain string ID (legacy storage format)
        try {
            $itemDocs2 = $db->collectionGroup('items')
                ->where('assigned_project_id', '=', $projectId)
                ->documents();
            foreach ($itemDocs2 as $doc) {
                if (!$doc->exists() || isset($releasedPaths[$doc->name()])) continue;
                $doc->reference()->update([
                    ['path' => 'assigned_project_id', 'value' => $deleteField],
                    ['path' => 'status',              'value' => 'In-Storage'],
                ]);
                $releasedPaths[$doc->name()] = true;
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Projects::releaseProjectResources equipment (str) - ' . $e->getMessage());
        }

        // Fallback: full scan across all resource subcollections.
        // Catches items that slipped past failed index queries or were stored in
        // any other format. Also fixes items already stuck from past deletes.
        try {
            foreach ($db->collection('resource')->documents() as $resDoc) {
                if (!$resDoc->exists()) continue;
                foreach ($resDoc->reference()->collection('items')->documents() as $itemDoc) {
                    if (!$itemDoc->exists() || isset($releasedPaths[$itemDoc->name()])) continue;
                    $apid = $itemDoc->data()['assigned_project_id'] ?? null;
                    $pid  = $apid instanceof \Google\Cloud\Firestore\DocumentReference
                        ? $apid->id()
                        : (string)($apid ?? '');
                    if ($pid !== $projectId) continue;
                    $itemDoc->reference()->update([
                        ['path' => 'assigned_project_id', 'value' => $deleteField],
                        ['path' => 'status',              'value' => 'In-Storage'],
                    ]);
                }
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Projects::releaseProjectResources equipment (scan) - ' . $e->getMessage());
        }
    }

    private function removeFromProjectResources($db, string $projectId, string $refId, string $unitId): void
    {
        try {
            $snap    = $db->collection('project')->document($projectId)->snapshot();
            $current = (array)($snap->data()['resources'] ?? []);
            $filtered = array_values(array_filter($current, function ($r) use ($refId, $unitId) {
                return !((string)($r['ref_id'] ?? '') === $refId && (string)($r['unit_id'] ?? '') === $unitId);
            }));
            if (count($filtered) !== count($current)) {
                $db->collection('project')->document($projectId)->update([
                    ['path' => 'resources', 'value' => $filtered],
                ]);
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Projects::removeFromProjectResources - ' . $e->getMessage());
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

        $db    = $this->firestore->getDb();
        $pmId  = trim((string)($this->request->getPost('project_manager_id') ?? ''));
        $seId  = trim((string)($this->request->getPost('site_engineer_id')   ?? ''));
        $pmRef = $pmId !== '' ? $db->collection('users')->document($pmId) : null;
        $seRef = $seId !== '' ? $db->collection('users')->document($seId) : null;

        $lat = $this->normalizeNumberOrNull($this->request->getPost('location_lat'));
        $lng = $this->normalizeNumberOrNull($this->request->getPost('location_lng'));
        $geopoint = ($lat !== null && $lng !== null)
            ? new \Google\Cloud\Core\GeoPoint((float)$lat, (float)$lng)
            : null;

        $startTs = $rawStart !== '' ? new \Google\Cloud\Core\Timestamp(new \DateTime($rawStart)) : null;
        $endTs   = $rawEnd   !== '' ? new \Google\Cloud\Core\Timestamp(new \DateTime($rawEnd))   : null;

        return [
            'project_name'         => $projectName,
            'project_type'         => $projectType,
            'client_name'          => trim((string) $this->request->getPost('client_name')),
            'status'               => $status,
            'budget'               => $budget,
            'start_date'           => $startTs,
            'end_date'             => $endTs,
            'overall_progress'     => $overallProgress,
            'project_manager_id'   => $pmRef,
            'site_engineer_id'     => $seRef,
            'project_manager_name' => $this->resolveUserName($pmId),
            'site_engineer_name'   => $this->resolveUserName($seId),
            'delay_ping_days'      => max(0, (int)($this->request->getPost('delay_ping_days') ?: 0)),
            'personnel' => [
                'project_manager_id' => $pmRef,
                'site_engineer_id'   => $seRef,
            ],
            'location' => [
                'address'  => trim((string) $this->request->getPost('location_address')),
                'geopoint' => $geopoint,
            ],
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
            $taskNow = new Timestamp(new \DateTime());
            $task['created_at'] = $task['created_at'] ?? $taskNow;
            $task['updated_at'] = $taskNow;

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
                'milestone_name'       => $name,
                'order'                => $order++,
                'weight_percent'       => (float) ($phase['weight'] ?? 0),
                'target_quantity'      => 1,
                'completed_quantity'   => 0,
                'unit'                 => 'pcs',
                'target_date'          => $targetDate !== '' ? $targetDate : null,
                'actual_completion_date' => null,
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
                'milestone_name'         => (string) ($m['milestone_name'] ?? $m['name'] ?? ''),
                'order'                  => (int)    ($m['order']          ?? 0),
                'weight_percent'         => (float)  ($m['weight_percent'] ?? $m['weight'] ?? 0),
                'target_quantity'        => max(1, (int)($m['target_quantity'] ?? 1)),
                'completed_quantity'     => (int)   ($m['completed_quantity'] ?? 0),
                'unit'                   => (string) ($m['unit'] ?? 'pcs') ?: 'pcs',
                'target_date'            => $m['target_date'] ?? $m['targetDate'] ?? null,
                'actual_completion_date' => $m['actual_completion_date'] ?? $m['actualCompletionDate'] ?? null,
                'status'                 => (string) ($m['status'] ?? 'Pending'),
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
            $taskNow = new Timestamp(new \DateTime());
            $task['created_at'] = $task['created_at'] ?? $taskNow;
            $task['updated_at'] = $taskNow;
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

        $rawGeo = $location['geopoint'] ?? null;
        $geoLat = '';
        $geoLng = '';
        if ($rawGeo instanceof \Google\Cloud\Core\GeoPoint) {
            $geoLat = (string) $rawGeo->latitude();
            $geoLng = (string) $rawGeo->longitude();
        } elseif (is_array($rawGeo)) {
            $geoLat = (string) ($rawGeo['lat'] ?? '');
            $geoLng = (string) ($rawGeo['lng'] ?? '');
        }

        $project['location'] = $location;
        $project['location_label'] = trim((string) ($location['address'] ?? ''));
        $project['project_manager_name'] = $this->resolveUserName($personnel['project_manager_id'] ?? null);
        $project['site_engineer_name'] = $this->resolveUserName($personnel['site_engineer_id'] ?? null);
        $project['progress_label'] = number_format((float) ($project['overall_progress'] ?? 0), 2);
        $project['start_date_value'] = $this->normalizeDateValue($project['start_date'] ?? '');
        $project['end_date_value'] = $this->normalizeDateValue($project['end_date'] ?? '');
        $project['location_address_value'] = (string) ($location['address'] ?? '');
        $project['location_lat_value'] = $geoLat;
        $project['location_lng_value'] = $geoLng;

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

        if ($value instanceof \Google\Cloud\Core\Timestamp) {
            try {
                return \DateTimeImmutable::createFromInterface($value->get())
                    ->setTimezone(new \DateTimeZone('Asia/Manila'))
                    ->format('Y-m-d');
            } catch (\Throwable) { return ''; }
        }

        if (is_numeric($value)) {
            return date('Y-m-d', (int) $value);
        }

        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            return '';
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

    private function notifyAssigned(string $projectId, string $projectName, string $pmUid, string $seUid): void
    {
        try {
            $svc  = new NotificationService();
            $sent = [];
            foreach (array_filter([$pmUid, $seUid]) as $uid) {
                if (in_array($uid, $sent, true)) continue;
                $svc->send($uid, 'projectAssigned', 'Assigned to Project',
                    "You have been assigned to {$projectName}",
                    ['project_id' => $projectId]);
                $sent[] = $uid;
            }
        } catch (\Throwable $e) {
            log_message('error', 'Projects::notifyAssigned - ' . $e->getMessage());
        }
    }
}
