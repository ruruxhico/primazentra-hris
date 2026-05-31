<?php

namespace App\Controllers;

use App\Libraries\FirestoreService;
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

            $perPage = config('Pager')->perPage;
            $currentPage = max(1, (int) ($this->request->getGet('page') ?? 1));
            $total = count($projects);

            $pager = service('pager');
            $pager->store('projects', $currentPage, $perPage, $total);

            $offset = ($currentPage - 1) * $perPage;
            $pagedProjects = array_slice($projects, $offset, $perPage);

            $data = [
                'pageTitle' => 'Projects',
                'activePage' => 'projects',
                'projects' => $pagedProjects,
                'pagerOffset' => $offset,
                'userName' => session()->get('userName') ?? 'User',
                'role' => session()->get('role') ?? 'Staff'
            ];

            return view('projects/projects_view', $data);
        } catch (\Exception $e) {
            log_message('error', 'Projects::index - ' . $e->getMessage());

            $data = [
                'pageTitle' => 'Projects',
                'activePage' => 'projects',
                'projects' => [],
                'pagerOffset' => 0,
                'userName' => session()->get('userName') ?? 'User',
                'role' => session()->get('role') ?? 'Staff',
                'error' => 'Unable to fetch projects'
            ];

            return view('projects/projects_view', $data);
        }
    }

    public function create()
    {
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
                    $flatTasks = $this->normalizePhases($this->request->getPost('phases') ?? []);
                    $this->writeTasksFlat($projectId, $flatTasks);
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
            'phases' => [],   // JS auto-fills from project type selection
            'projectManagers' => $this->usersModel->getUsersByRole(['PM', 'Project Manager']),
            'siteEngineers' => $this->usersModel->getUsersByRole(['SE', 'Site Engineer']),
            'userName' => session()->get('userName') ?? 'User',
            'role' => session()->get('role') ?? 'Staff',
            'googleMapsBrowserKey' => (new GoogleMaps())->browserKey,
        ]);
    }

    public function update($id)
    {
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
                    $flatTasks = $this->normalizePhases($this->request->getPost('phases') ?? []);
                    $this->writeTasksFlat($id, $flatTasks);
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
        // Support POST delete via AJAX and GET fallback with segment
        try {
            if (strtolower($this->request->getMethod()) === 'post') {
                $id = $this->request->getPost('id');
                if (!$id) {
                    return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing id']);
                }

                $this->deleteProjectTasks($id);
                $this->firestore->getDb()->collection('project')->document($id)->delete();

                return $this->response->setJSON(['success' => true]);
            }

            // GET fallback when id passed as parameter
            if ($id) {
                $this->deleteProjectTasks($id);
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

            return view('projects/projects_show', [
                'pageTitle' => 'Project Detail',
                'activePage' => 'projects',
                'project' => $project,
                'projectId' => $id,
                'userName' => session()->get('userName') ?? 'User',
                'role' => session()->get('role') ?? 'Staff'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Projects::show - ' . $e->getMessage());
            return redirect()->to('projects')->with('error', 'Unable to fetch project');
        }
    }

    private function buildProjectPayload(): array
    {
        $projectName = trim((string) $this->request->getPost('project_name'));
        $projectType = (string) $this->request->getPost('project_type');
        $status = (string) ($this->request->getPost('status') ?: 'Planned');
        $budget = (float) ($this->request->getPost('budget') ?: 0);

        $tasks = $this->normalizePhases($this->request->getPost('phases') ?? []);
        $overallProgress = $this->calculateOverallProgress($tasks);

        return [
            'project_name' => $projectName,
            'project_type' => $projectType,
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

            if (empty($validTasks)) {
                // Phase with no tasks: store as a single phase-level entry
                $flat[] = [
                    'id'                 => bin2hex(random_bytes(8)),
                    'phase_name'         => $phaseName,
                    'task_name'          => '',
                    'weight_percent'     => $phaseWeight,
                    'qty'                => 0,
                    'unit'               => 'pcs',
                    'completion_percent' => 0.0,
                    'status'             => 'Pending',
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
                $phases[] = ['name' => $phaseName, 'weight' => 0.0, 'tasks' => []];
            }

            $pIdx = $phaseMap[$phaseName];
            $phases[$pIdx]['weight'] += (float)($task['weight_percent'] ?? 0);

            $taskName = trim((string)($task['task_name'] ?? ''));
            if ($taskName !== '') {
                $phases[$pIdx]['tasks'][] = [
                    'id'                 => $task['id'] ?? '',
                    'task_name'          => $taskName,
                    'qty'                => $task['qty'] ?? 0,
                    'unit'               => $task['unit'] ?? 'pcs',
                    'completion_percent' => $task['completion_percent'] ?? 0,
                    'status'             => $task['status'] ?? 'Pending',
                ];
            }
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
        // getUserById returns ['id' => ..., 'data' => [...]] — unwrap the data array
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
}
