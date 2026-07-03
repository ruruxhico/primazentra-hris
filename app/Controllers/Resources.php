<?php

namespace App\Controllers;

use App\Libraries\ArchiveService;
use App\Models\Model_Resources;
use App\Models\Model_Manpower;
use App\Libraries\FirestoreService;
use App\Libraries\MLServiceClient;
use App\Libraries\TrashService;
use Config\GoogleMaps;

class Resources extends BaseController
{
    protected $model;
    protected $manpowerModel;
    protected FirestoreService $firestore;

    public function __construct()
    {
        $this->model = new Model_Resources();
        $this->manpowerModel = new Model_Manpower();
        $this->firestore = FirestoreService::getInstance();
    }

    /**
     * Display all resources
     */
    public function index()
    {
        try {
            $resources = $this->model->getAllResources();

            $equipment = [];
            $tools = [];
            $manpower = [];

            foreach ($resources as $resource) {
                $normalized = $this->normalizeResourceForView($resource);
                $category = $normalized['category'] ?? '';

                if ($category === 'Tools') {
                    $tools[] = $normalized;
                } else {
                    $equipment[] = $normalized;
                }
            }

            // Fetch manpower from separate collection
            try {
                $manpowerDocs = $this->manpowerModel->getAllManpower();
                foreach ($manpowerDocs as $m) {
                    if (empty($m['category'])) $m['category'] = 'Manpower';
                    $manpower[] = $this->normalizeResourceForView($m);
                }
            } catch (\Exception $e) {
                // fail gracefully; leave manpower empty
            }

            // Default: alphabetical by name
            usort($equipment, fn($a, $b) => strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? '')));
            usort($tools,     fn($a, $b) => strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? '')));

            // â€” Read and apply equipment/tools sort filters â€”
            $eqSort    = trim((string) ($this->request->getGet('eq_sort')    ?? ''));
            $toolsSort = trim((string) ($this->request->getGet('tools_sort') ?? ''));

            if ($eqSort === 'total_desc') {
                usort($equipment, fn($a, $b) => ($b['total_quantity_display'] ?? 0) <=> ($a['total_quantity_display'] ?? 0));
            } elseif ($eqSort === 'avail_desc') {
                usort($equipment, fn($a, $b) => ($b['available_units_display'] ?? 0) <=> ($a['available_units_display'] ?? 0));
            }

            if ($toolsSort === 'total_desc') {
                usort($tools, fn($a, $b) => ($b['total_quantity_display'] ?? 0) <=> ($a['total_quantity_display'] ?? 0));
            } elseif ($toolsSort === 'avail_desc') {
                usort($tools, fn($a, $b) => ($b['available_units_display'] ?? 0) <=> ($a['available_units_display'] ?? 0));
            }

            // â€” Read and apply manpower filters â€”
            $mpRole   = trim((string) ($this->request->getGet('mp_role')   ?? ''));
            $mpSpec   = trim((string) ($this->request->getGet('mp_spec')   ?? ''));
            $mpStatus = trim((string) ($this->request->getGet('mp_status') ?? ''));

            if ($mpRole !== '') {
                $manpower = array_values(array_filter($manpower, fn($m) =>
                    strcasecmp((string)($m['manpower_details']['role'] ?? $m['role'] ?? ''), $mpRole) === 0));
            }
            if ($mpSpec !== '') {
                $manpower = array_values(array_filter($manpower, fn($m) =>
                    strcasecmp((string)($m['manpower_details']['specialization'] ?? $m['specialization'] ?? ''), $mpSpec) === 0));
            }
            if ($mpStatus !== '') {
                $manpower = array_values(array_filter($manpower, fn($m) =>
                    strcasecmp((string)($m['status'] ?? ''), $mpStatus) === 0));
            }

            $trashService = new TrashService($this->firestore);
            $recentlyDeletedResources = $trashService->getTrashItems('resource');
            $recentlyDeletedManpower  = $trashService->getTrashItems('manpower');

            return view('resources/resources_view', [
                'pageTitle'                 => 'Resources',
                'equipment'                 => $equipment,
                'eqTotal'                   => count($equipment),
                'tools'                     => $tools,
                'toolsTotal'                => count($tools),
                'manpower'                  => $manpower,
                'mpTotal'                   => count($manpower),
                'activeEquipFilters'        => ['eq_sort'    => $eqSort],
                'activeToolFilters'         => ['tools_sort' => $toolsSort],
                'activeManpowerFilters'     => ['mp_role' => $mpRole, 'mp_spec' => $mpSpec, 'mp_status' => $mpStatus],
                'activeTab'                 => $this->request->getGet('tab') ?? 'equipment',
                'recentlyDeletedResources'  => $recentlyDeletedResources,
                'recentlyDeletedManpower'   => $recentlyDeletedManpower,
                'userName'                  => session()->get('userName') ?? 'User',
                'role'                      => session()->get('role') ?? 'User',
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error loading resources: ' . $e->getMessage());
        }
    }

    /**
     * Show create resource form
     */
    public function create()
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        helper('access');
        if (!pz_can('add_edit_resources')) return redirect()->to('resources')->with('error', 'Access denied.');

        $category = $this->request->getGet('category') ?? '';
        $mapsKey = (new GoogleMaps())->browserKey;

        if ($category === 'Manpower') {
            return redirect()->to('manpower/create');
        }

        if ($category === 'Equipment') {
            return view('resources/equipments_form', [
                'pageTitle' => 'Add Equipment',
                'resource' => [
                    'category' => 'Equipment',
                    'status' => 'Available',
                    'next_availability' => '',
                    'location' => [
                        'address' => '',
                        'type' => ''
                    ],
                    'asset_details' => []
                ],
                'items' => [],
                'projectSites' => $this->getProjectSiteOptions(),
                'warehouseLocations' => $this->getWarehouseLocationOptions(),
                'savedWarehouseLocations' => $this->getSavedWarehouseLocations(),
                'defaultWarehouseAddress' => $this->getDefaultWarehouseAddress(),
                'mapsKey' => $mapsKey,
                'userName' => session()->get('userName') ?? 'User',
                'role' => session()->get('role') ?? 'User'
            ]);
        }

        if ($category === 'Tools') {
            return view('resources/tools_form', [
                'pageTitle' => 'Add Tools',
                'resource' => [
                    'category' => 'Tools',
                    'status' => 'Available',
                    'next_availability' => '',
                    'location' => [
                        'address' => '',
                        'type' => ''
                    ],
                    'asset_details' => []
                ],
                'items' => [],
                'projectSites' => $this->getProjectSiteOptions(),
                'warehouseLocations' => $this->getWarehouseLocationOptions(),
                'savedWarehouseLocations' => $this->getSavedWarehouseLocations(),
                'defaultWarehouseAddress' => $this->getDefaultWarehouseAddress(),
                'mapsKey' => $mapsKey,
                'userName' => session()->get('userName') ?? 'User',
                'role' => session()->get('role') ?? 'User'
            ]);
        }

        return view('resources/resources_form', [
            'pageTitle' => 'Add Resource',
            'resource' => [
                'category' => $category,
                'status' => 'Available',
                'next_availability' => '',
                'location' => [
                    'address' => '',
                    'type' => ''
                ]
            ],
            'userName' => session()->get('userName') ?? 'User',
            'role' => session()->get('role') ?? 'User'
        ]);
    }

    /**
     * Store a new resource
     */
    public function store()
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        helper('access');
        if (!pz_can('add_edit_resources')) return redirect()->to('resources')->with('error', 'Access denied.');

        try {
            $data = $this->buildResourcePayload();

            if (($data['category'] ?? '') === 'Manpower') {
                $resourceId = $this->manpowerModel->createManpower($data);
                // manpower has no items
            } else {
                $resourceId = $this->model->createResource($data);
                if (in_array($data['category'] ?? '', ['Equipment', 'Tools'], true)) {
                    $this->syncResourceItems($resourceId, $this->request->getPost('items') ?? []);
                    $this->syncResourceCounts($resourceId);
                }
            }

            return redirect()->to('resources')->with('success', 'Resource created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error creating resource: ' . $e->getMessage());
        }
    }

    /**
     * Show edit resource form
     */
    public function edit($id = null)
    {
        try {
            $resource = $this->model->getResourceById($id);
            $isManpower = false;

            if (!$resource) {
                // try manpower collection
                $resource = $this->manpowerModel->getManpowerById($id);
                $isManpower = $resource ? true : false;
            }

            if (!$resource) {
                return redirect()->back()->with('error', 'Resource not found');
            }

            $resource = $this->normalizeResourceForView($resource);
            $category = (string) ($resource['category'] ?? '');
            if ($category === 'Manpower') {
                return redirect()->to('manpower/edit/' . $id);
            }

            $view = $category === 'Equipment'
                ? 'resources/equipments_form'
                : ($category === 'Tools' ? 'resources/tools_form' : 'resources/resources_form');

            $editTitle = $category === 'Equipment' ? 'Edit Equipment' : ($category === 'Tools' ? 'Edit Tools' : 'Edit Resource');
            return view($view, [
                'pageTitle' => $editTitle,
                'resource' => $resource,
                'items' => $isManpower ? [] : $this->groupItemsForEdit($this->sortItemsBySerial($this->model->getResourceItems($id))),
                'projectSites' => $this->getProjectSiteOptions(),
                'projects' => $this->getProjectOptions(),
                'warehouseLocations' => $this->getWarehouseLocationOptions(),
                'savedWarehouseLocations' => $this->getSavedWarehouseLocations(),
                'defaultWarehouseAddress' => $this->getDefaultWarehouseAddress(),
                'mapsKey' => (new GoogleMaps())->browserKey,
                'userName' => session()->get('userName') ?? 'User',
                'role' => session()->get('role') ?? 'User'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error loading resource: ' . $e->getMessage());
        }
    }

    /**
     * Update a resource
     */
    public function update($id = null)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        helper('access');
        if (!pz_can('add_edit_resources')) return redirect()->to('resources')->with('error', 'Access denied.');

        try {
            // Try resource collection first, then manpower
            $existing = $this->model->getResourceById($id);
            $isManpower = false;
            if (!$existing) {
                $existing = $this->manpowerModel->getManpowerById($id);
                $isManpower = $existing ? true : false;
            }

            if (!$existing) {
                return redirect()->to('resources')->with('error', 'Resource not found');
            }

            // Model name edit (preserve_items=1): strict partial update — only patches name and
            // updated_at; never reads or writes the items subcollection so units are untouched.
            // Uses ->update() (field-level merge), NOT ->set() which would overwrite the whole doc.
            $preserveItems = (bool) $this->request->getPost('preserve_items');
            if (!$isManpower && $preserveItems) {
                $newName = strtoupper(trim((string) $this->request->getPost('name')));
                $this->firestore->getDb()
                    ->collection('resource')->document($id)
                    ->update([
                        ['path' => 'name',       'value' => $newName],
                        ['path' => 'updated_at', 'value' => new \Google\Cloud\Core\Timestamp(new \DateTime())],
                    ]);
                return redirect()->to('resources')->with('success', 'Resource updated successfully!');
            }

            $data = $this->buildResourcePayload();

            // Preserve created_at where possible
            $data['created_at'] = $existing['created_at'] ?? (new \Google\Cloud\Core\Timestamp(new \DateTime()));
            $data['updated_at'] = new \Google\Cloud\Core\Timestamp(new \DateTime());

            if ($isManpower) {
                $this->manpowerModel->updateManpower($id, $data);
            } else {
                $this->model->updateResource($id, $data);

                $category = (string) ($data['category'] ?? '');
                $addSpecificUnitsNow = (bool) $this->request->getPost('add_specific_units_now');

                if ($category === 'Equipment') {
                    if ($addSpecificUnitsNow) {
                        $this->syncResourceItems($id, $this->request->getPost('items') ?? []);
                    } else {
                        $this->deleteResourceItems($id);
                    }
                    $this->syncResourceCounts($id);
                } elseif ($category === 'Tools') {
                    $this->syncResourceItems($id, $this->request->getPost('items') ?? []);
                    $this->syncResourceCounts($id);
                } else {
                    $this->deleteResourceItems($id);
                }
            }

            return redirect()->to('resources')->with('success', 'Resource updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error updating resource: ' . $e->getMessage());
        }
    }

    public function deleted(): string|object
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        $tab   = $this->request->getGet('tab') ?? 'equipment';
        $trash = new TrashService($this->firestore);

        if ($tab === 'manpower') {
            $items  = $trash->getTrashItems('manpower');
            $module = 'manpower';
            $title  = 'Deleted Manpower';
        } else {
            $all      = $trash->getTrashItems('resource');
            $category = $tab === 'tools' ? 'Tools' : 'Equipment';
            $items    = array_values(array_filter($all, fn($r) =>
                strcasecmp((string)($r['data']['category'] ?? ''), $category) === 0
            ));
            $module = 'resource';
            $title  = 'Deleted ' . $category;
        }

        return view('shared/module_deleted', [
            'pageTitle'  => $title,
            'activePage' => 'resources',
            'userName'   => session()->get('userName') ?? 'User',
            'role'       => session()->get('role') ?? 'Staff',
            'module'     => $module,
            'title'      => $title,
            'backUrl'    => base_url('resources?tab=' . $tab),
            'tab'        => $tab,
            'items'      => $items,
        ]);
    }

    public function archived(): string|object
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        $tab    = $this->request->getGet('tab') ?? 'equipment';
        $arch   = new ArchiveService($this->firestore);

        if ($tab === 'manpower') {
            $items  = $arch->getArchivedItems('manpower');
            $module = 'manpower';
            $title  = 'Archived Manpower';
        } else {
            $all      = $arch->getArchivedItems('resource');
            $category = $tab === 'tools' ? 'Tools' : 'Equipment';
            $items    = array_values(array_filter($all, fn($r) =>
                strcasecmp((string)($r['data']['category'] ?? ''), $category) === 0
            ));
            $module = 'resource';
            $title  = 'Archived ' . $category;
        }

        return view('shared/module_archived', [
            'pageTitle'  => $title,
            'activePage' => 'resources',
            'userName'   => session()->get('userName') ?? 'User',
            'role'       => session()->get('role') ?? 'Staff',
            'module'     => $module,
            'title'      => $title,
            'backUrl'    => base_url('resources?tab=' . $tab),
            'tab'        => $tab,
            'items'      => $items,
        ]);
    }

    public function archive(): object
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        helper('access');
        if (!pz_can('add_edit_resources')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Access denied.']);
        }
        try {
            $id = trim((string)($this->request->getPost('id') ?? ''));
            if (!$id) return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing id']);

            $res = $this->model->getResourceById($id);
            if ($res) {
                $rawSnap = $this->firestore->getDb()->collection('resource')->document($id)->snapshot();
                $rawData = $rawSnap->exists() ? $rawSnap->data() : $res;
                $name    = (string)($rawData['name'] ?? $id);

                // Snapshot items subcollection so they restore with the record
                $items = [];
                foreach ($this->firestore->getDb()->collection('resource')->document($id)->collection('items')->documents() as $itemDoc) {
                    if ($itemDoc->exists()) {
                        $items[] = ['__id' => $itemDoc->id()] + $itemDoc->data();
                    }
                }

                (new ArchiveService($this->firestore))->moveToArchive(
                    'resource', $id, $name, $rawData,
                    ['items' => $items],
                    session()->get('userName')
                );

                $this->deleteResourceItems($id);
                $this->model->deleteResource($id);
            } else {
                $mpData = $this->manpowerModel->getManpowerById($id);
                if ($mpData) {
                    $mpName = ($mpData['name'] ?? '') ?: implode(' ', array_filter([trim((string)($mpData['first_name'] ?? '')), trim((string)($mpData['middle_name'] ?? '')), trim((string)($mpData['last_name'] ?? ''))], fn($p) => $p !== '')) ?: $id;
                    (new ArchiveService($this->firestore))->moveToArchive(
                        'manpower', $id, $mpName, $mpData, [],
                        session()->get('userName')
                    );
                }
                $this->manpowerModel->deleteManpower($id);
            }

            return $this->response->setJSON(['success' => true]);
        } catch (\Throwable $e) {
            log_message('error', 'Resources::archive - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Archive failed']);
        }
    }

    /**
     * Delete a resource (soft-delete via TrashService before hard-delete)
     */
    public function delete($id = null)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        helper('access');
        if (!pz_can('add_edit_resources')) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Access denied.']);
            }
            return redirect()->to('resources')->with('error', 'Access denied.');
        }

        try {
            if (strtolower($this->request->getMethod()) === 'post') {
                $id = $this->request->getPost('id') ?? $id;

                if (!$id) {
                    return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing id']);
                }

                // delete from resource or manpower collection depending where it exists
                $res = $this->model->getResourceById($id);
                if ($res) {
                    // Block deletion if any items are currently In-Use
                    $inUseCount = 0;
                    foreach ($this->firestore->getDb()->collection('resource')->document($id)->collection('items')->documents() as $itemDoc) {
                        if ($itemDoc->exists() && strtolower((string)($itemDoc->data()['status'] ?? '')) === 'in-use') {
                            $inUseCount++;
                        }
                    }
                    if ($inUseCount > 0) {
                        return $this->response->setStatusCode(409)->setJSON([
                            'success' => false,
                            'message' => "Cannot delete: {$inUseCount} unit(s) are currently In-Use.",
                        ]);
                    }

                    // Snapshot raw Firestore data
                    $rawSnap = $this->firestore->getDb()->collection('resource')->document($id)->snapshot();
                    $rawData = $rawSnap->exists() ? $rawSnap->data() : $res;
                    $name    = (string) ($rawData['name'] ?? $id);

                    // Read items subcollection with __id preserved
                    $items = [];
                    foreach ($this->firestore->getDb()->collection('resource')->document($id)->collection('items')->documents() as $itemDoc) {
                        if ($itemDoc->exists()) {
                            $items[] = ['__id' => $itemDoc->id()] + $itemDoc->data();
                        }
                    }

                    (new TrashService($this->firestore))->moveToTrash(
                        'resource', $id, $name, $rawData,
                        ['items' => $items],
                        session()->get('userName')
                    );

                    $this->cleanupDeletedFromProjects($id);
                    $this->deleteResourceItems($id);
                    $this->model->deleteResource($id);
                } else {
                    $mpData = $this->manpowerModel->getManpowerById($id);
                    if ($mpData) {
                        $mpName = ($mpData['name'] ?? '') ?: implode(' ', array_filter([trim((string)($mpData['first_name'] ?? '')), trim((string)($mpData['middle_name'] ?? '')), trim((string)($mpData['last_name'] ?? ''))], fn($p) => $p !== '')) ?: $id;
                        (new TrashService($this->firestore))->moveToTrash(
                            'manpower', $id, $mpName, $mpData, [],
                            session()->get('userName')
                        );
                    }
                    $this->cleanupDeletedFromProjects($id);
                    $this->manpowerModel->deleteManpower($id);
                }

                return $this->response->setJSON(['success' => true, 'message' => 'Resource deleted']);
            }

            if ($id) {
                $res = $this->model->getResourceById($id);
                if ($res) {
                    $rawSnap = $this->firestore->getDb()->collection('resource')->document($id)->snapshot();
                    $rawData = $rawSnap->exists() ? $rawSnap->data() : $res;
                    $name    = (string) ($rawData['name'] ?? $id);

                    $items = [];
                    foreach ($this->firestore->getDb()->collection('resource')->document($id)->collection('items')->documents() as $itemDoc) {
                        if ($itemDoc->exists()) {
                            $items[] = ['__id' => $itemDoc->id()] + $itemDoc->data();
                        }
                    }

                    (new TrashService($this->firestore))->moveToTrash(
                        'resource', $id, $name, $rawData,
                        ['items' => $items],
                        session()->get('userName')
                    );

                    $this->cleanupDeletedFromProjects($id);
                    $this->deleteResourceItems($id);
                    $this->model->deleteResource($id);
                } else {
                    $mpData = $this->manpowerModel->getManpowerById($id);
                    if ($mpData) {
                        $mpName = ($mpData['name'] ?? '') ?: implode(' ', array_filter([trim((string)($mpData['first_name'] ?? '')), trim((string)($mpData['middle_name'] ?? '')), trim((string)($mpData['last_name'] ?? ''))], fn($p) => $p !== '')) ?: $id;
                        (new TrashService($this->firestore))->moveToTrash(
                            'manpower', $id, $mpName, $mpData, [],
                            session()->get('userName')
                        );
                    }
                    $this->cleanupDeletedFromProjects($id);
                    $this->manpowerModel->deleteManpower($id);
                }
                return redirect()->to('resources')->with('success', 'Resource deleted successfully!');
            }

            return redirect()->to('resources')->with('error', 'Invalid delete request');
        } catch (\Exception $e) {
            if (strtolower($this->request->getMethod()) === 'post') {
                return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Error deleting resource']);
            }
            return redirect()->back()->with('error', 'Error deleting resource: ' . $e->getMessage());
        }
    }

    public function show($id = null)
    {
        try {
            $resource = $this->model->getResourceById($id);
            $isManpower = false;
            if (!$resource) {
                $resource = $this->manpowerModel->getManpowerById($id);
                $isManpower = $resource ? true : false;
            }

            if (!$resource) {
                return redirect()->to('resources')->with('error', 'Resource not found');
            }

            $resource = $this->normalizeResourceForView($resource);

            // For equipment/tools: auto-heal any items whose assigned_project_id
            // references a project that no longer exists (deleted projects).
            $items = [];
            if (!$isManpower) {
                $db                 = $this->firestore->getDb();
                $deleteField        = \Google\Cloud\Firestore\FieldValue::deleteField();
                $projectExistsCache = [];
                $projectNameCache   = [];

                foreach ($this->model->getResourceItems($id) as $item) {
                    $apid = $item['assigned_project_id'] ?? null;
                    $pid  = $apid instanceof \Google\Cloud\Firestore\DocumentReference
                        ? $apid->id()
                        : (string)($apid ?? '');

                    if ($pid !== '') {
                        if (!array_key_exists($pid, $projectExistsCache)) {
                            try {
                                $ps = $db->collection('project')->document($pid)->snapshot();
                                $projectExistsCache[$pid] = $ps->exists();
                                $projectNameCache[$pid]   = $ps->exists()
                                    ? trim((string)($ps->data()['project_name'] ?? ''))
                                    : '';
                            } catch (\Throwable) {
                                $projectExistsCache[$pid] = true;
                                $projectNameCache[$pid]   = '';
                            }
                        }

                        if (!$projectExistsCache[$pid]) {
                            // Auto-heal Firestore — clear orphaned reference and reset status
                            $unitId = $item['__id'] ?? $item['id'] ?? '';
                            if ($unitId !== '') {
                                try {
                                    $db->collection('resource')->document($id)
                                       ->collection('items')->document($unitId)
                                       ->update([
                                           ['path' => 'assigned_project_id', 'value' => $deleteField],
                                           ['path' => 'status',              'value' => 'In-Storage'],
                                       ]);
                                } catch (\Throwable $e) {
                                    log_message('warning', 'Resources::show auto-heal - ' . $e->getMessage());
                                }
                            }
                            unset($item['assigned_project_id']);
                            $item['status']      = 'In-Storage';
                            $item['assigned_to'] = '';
                        } else {
                            // Show project name in the Assigned To column
                            $item['assigned_to'] = $projectNameCache[$pid] ?: $pid;
                        }
                    }

                    $items[] = $item;
                }
            }

            usort($items, fn($a, $b) => strnatcasecmp(
                (string)($a['serial_number'] ?? ''),
                (string)($b['serial_number'] ?? '')
            ));

            return view('resources/resources_show', [
                'pageTitle' => 'Resource Detail',
                'resource'  => $resource,
                'items'     => $items,
                'userName'  => session()->get('userName') ?? 'User',
                'role'      => session()->get('role') ?? 'User',
            ]);
        } catch (\Exception $e) {
            return redirect()->to('resources')->with('error', 'Error loading resource: ' . $e->getMessage());
        }
    }

    /**
     * View resource items (for Equipment/Tools)
     */
    public function viewItems($resourceId = null)
    {
        try {
            $resource = $this->model->getResourceById($resourceId);
            $items = $this->model->getResourceItems($resourceId);

            // If no items found in the subcollection, fall back to items stored on the document (legacy/mobile flows)
            if (empty($items) && !empty($resource['items']) && is_array($resource['items'])) {
                $items = $this->normalizeItems($resource['items']);
            }

            if (!$resource) {
                return redirect()->back()->with('error', 'Resource not found');
            }

            // Enrich items with assigned project names for the Location column
            $items = $this->enrichItemsWithProjectNames($items);

            // Ensure derived fields (like name from manpower_details) are present
            $resource = $this->normalizeResourceForView($resource);

            // â€” Read unit-level filters â€”
            $filterBrand     = trim((string) ($this->request->getGet('brand')     ?? ''));
            $filterCondition = trim((string) ($this->request->getGet('condition') ?? ''));
            $filterStatus    = trim((string) ($this->request->getGet('status')    ?? ''));
            $filterLocType   = trim((string) ($this->request->getGet('loc_type')  ?? ''));

            // â€” Apply filters â€”
            if ($filterBrand !== '') {
                $items = array_values(array_filter($items, fn($i) =>
                    str_contains(strtolower((string) ($i['brand'] ?? '')), strtolower($filterBrand))));
            }
            if ($filterCondition !== '') {
                $items = array_values(array_filter($items, fn($i) =>
                    strcasecmp((string) ($i['condition'] ?? ''), $filterCondition) === 0));
            }
            if ($filterStatus !== '') {
                $items = array_values(array_filter($items, fn($i) =>
                    strcasecmp((string) ($i['status'] ?? ''), $filterStatus) === 0));
            }
            if ($filterLocType !== '') {
                $items = array_values(array_filter($items, fn($i) =>
                    strcasecmp((string) ($i['location']['type'] ?? ''), $filterLocType) === 0));
            }

            // Always sort units by serial number in ascending natural order
            usort($items, fn($a, $b) => strnatcasecmp(
                (string)($a['serial_number'] ?? ''),
                (string)($b['serial_number'] ?? '')
            ));

            $itemsTotal = count($items);
            $items = $this->attachNextAvailablePredictions($items, (string)($resource['name'] ?? 'Resource'), (string)($resource['category'] ?? ''));

            return view('resources/resource_items', [
                'pageTitle'               => 'Resource Items',
                'resource'                => $resource,
                'resourceId'              => $resourceId,
                'items'                   => $items,
                'itemsTotal'              => $itemsTotal,
                'activeFilters'           => [
                    'brand'     => $filterBrand,
                    'condition' => $filterCondition,
                    'status'    => $filterStatus,
                    'loc_type'  => $filterLocType,
                ],
                'defaultWarehouseAddress'  => $this->getDefaultWarehouseAddress(),
                'savedWarehouseLocations'  => $this->getSavedWarehouseLocations(),
                'warehouseLocations'       => $this->getWarehouseLocationOptions(),
                'mapsKey'                  => (new GoogleMaps())->browserKey,
                'userName' => session()->get('userName') ?? 'User',
                'role'     => session()->get('role') ?? 'User',
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error loading resource items: ' . $e->getMessage());
        }
    }

    /**
     * Add resource item
     */
    public function addItem($resourceId = null)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        helper('access');
        if (!pz_can('add_edit_resources')) return redirect()->to('resources')->with('error', 'Access denied.');

        try {
            if (!$resourceId) {
                return redirect()->back()->with('error', 'Missing resource id');
            }

            $serial  = trim((string)($this->request->getPost('serial_number')    ?? ''));
            $brand   = trim((string)($this->request->getPost('brand')            ?? ''));
            $cond    = trim((string)($this->request->getPost('condition')        ?? ''));
            $status  = trim((string)($this->request->getPost('status')           ?? ''));
            $locType = strtoupper(trim((string)($this->request->getPost('location_type')    ?? '')));
            $locAddr = trim((string)($this->request->getPost('location_address') ?? ''));
            $qty     = max(1, (int)($this->request->getPost('quantity') ?? 1));

            if ($cond === '' || $status === '') {
                return redirect()->back()->with('error', 'Condition and status are required.');
            }

            $base = [
                'brand_name'  => $brand,
                'condition'   => $cond,
                'status'      => $status,
                'location'    => ['type' => $locType, 'address' => $locAddr],
            ];

            for ($i = 0; $i < $qty; $i++) {
                $now = time();
                $itemSerial = $this->_deriveSerial($serial, $i);
                $this->model->createResourceItem($resourceId, array_merge($base, [
                    'serial_number' => $itemSerial,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]));
            }

            $this->syncResourceCounts($resourceId);
            $msg = $qty > 1 ? "{$qty} units added successfully!" : 'Unit added successfully!';
            return redirect()->to('resources/viewItems/' . $resourceId)->with('success', $msg);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error adding item: ' . $e->getMessage());
        }
    }

    private function _deriveSerial(string $base, int $offset): string
    {
        if ($base === '') return '';
        if (preg_match('/^(.*-)(\d+)$/', $base, $m)) {
            // base ends with -NNN: keep first item as-is, increment from there
            if ($offset === 0) return $base;
            $n = (int)$m[2] + $offset;
            return $m[1] . str_pad($n, strlen($m[2]), '0', STR_PAD_LEFT);
        }
        // base has no trailing number: append -001, -002, … for all items
        return $base . '-' . str_pad($offset + 1, 3, '0', STR_PAD_LEFT);
    }

    public function updateItem($resourceId = null, $itemId = null)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        helper('access');
        if (!pz_can('add_edit_resources')) return redirect()->to('resources')->with('error', 'Access denied.');

        try {
            if (!$resourceId || !$itemId) {
                return redirect()->back()->with('error', 'Missing resource or item id');
            }

            $serial   = strtoupper(trim((string)($this->request->getPost('serial_number') ?? '')));
            $brand    = strtoupper(trim((string)($this->request->getPost('brand')         ?? '')));
            $cond     = trim((string)($this->request->getPost('condition')     ?? ''));
            $status   = trim((string)($this->request->getPost('status')        ?? ''));
            $locType  = strtoupper(trim((string)($this->request->getPost('location_type') ?? '')));
            $locAddr  = trim((string)($this->request->getPost('location_address') ?? ''));

            if ($serial === '' || $cond === '' || $status === '') {
                return redirect()->back()->with('error', 'Serial number, condition, and status are required.');
            }

            $data = [
                'serial_number' => $serial,
                'condition'     => $cond,
                'status'        => $status,
            ];
            if ($brand   !== '') $data['brand_name']   = $brand;
            if ($locType !== '' || $locAddr !== '') {
                $data['location'] = ['type' => $locType, 'address' => $locAddr];
            }

            $this->firestore->getDb()
                ->collection('resource')->document($resourceId)
                ->collection('items')->document($itemId)
                ->set($data, ['merge' => true]);

            $this->syncResourceCounts($resourceId);
            return redirect()->to('resources/viewItems/' . $resourceId)->with('success', 'Unit updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error updating unit: ' . $e->getMessage());
        }
    }

    public function deleteItem($resourceId = null, $itemId = null)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        helper('access');
        if (!pz_can('add_edit_resources')) return redirect()->to('resources')->with('error', 'Access denied.');

        try {
            if (!$resourceId || !$itemId) {
                return redirect()->back()->with('error', 'Missing resource or item id');
            }

            $db   = $this->firestore->getDb();
            $snap = $db->collection('resource')->document($resourceId)->collection('items')->document($itemId)->snapshot();
            if ($snap->exists()) {
                $this->cleanProjectResourceEntry($db, $resourceId, $itemId, $snap->data()['assigned_project_id'] ?? null);
            }
            $db->collection('resource')->document($resourceId)->collection('items')->document($itemId)->delete();

            $this->syncResourceCounts($resourceId);
            return redirect()->to('resources/viewItems/' . $resourceId)->with('success', 'Unit deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting unit: ' . $e->getMessage());
        }
    }

    public function archiveItem($resourceId = null, $itemId = null)
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        helper('access');
        if (!pz_can('add_edit_resources')) return $this->response->setJSON(['success' => false, 'message' => 'Access denied.']);

        try {
            $db   = $this->firestore->getDb();
            $snap = $db->collection('resource')->document($resourceId)->collection('items')->document($itemId)->snapshot();
            if (!$snap->exists()) return $this->response->setJSON(['success' => false, 'message' => 'Unit not found.']);

            $data = $snap->data();
            $data['id']          = $itemId;
            $data['resource_id'] = $resourceId;

            $name = ($data['serial_number'] ?? '') ?: $itemId;

            $this->cleanProjectResourceEntry($db, $resourceId, $itemId, $data['assigned_project_id'] ?? null);

            $trashService = new \App\Libraries\TrashService($this->firestore);
            $trashService->moveToTrash('resource_item', $itemId, $name, $data, [], session()->get('userName') ?? 'System');

            $db->collection('resource')->document($resourceId)->collection('items')->document($itemId)->delete();

            $this->syncResourceCounts($resourceId);
            return $this->response->setJSON(['success' => true]);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function itemsDeleted($resourceId = null): string|object
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        $resource = $this->model->getResourceById($resourceId);
        if (!$resource) return redirect()->to('resources')->with('error', 'Resource not found.');

        $trash = new \App\Libraries\TrashService($this->firestore);
        $all   = $trash->getTrashItems('resource_item');
        $items = array_values(array_filter($all, fn($r) => ($r['data']['resource_id'] ?? '') === $resourceId));

        return view('shared/module_deleted', [
            'pageTitle'  => ($resource['name'] ?? 'Resource') . ' — Deleted Units',
            'activePage' => 'resources',
            'userName'   => session()->get('userName') ?? 'User',
            'role'       => session()->get('role') ?? 'Staff',
            'module'     => 'resource_item',
            'title'      => ($resource['name'] ?? 'Resource') . ' — Deleted Units',
            'backUrl'    => base_url('resources/viewItems/' . $resourceId),
            'items'      => $items,
        ]);
    }

    public function itemsArchived($resourceId = null): string|object
    {
        if (!session()->get('isLoggedIn')) return redirect()->to('auth/login');
        $resource = $this->model->getResourceById($resourceId);
        if (!$resource) return redirect()->to('resources')->with('error', 'Resource not found.');

        $arch  = new \App\Libraries\ArchiveService($this->firestore);
        $all   = $arch->getArchivedItems('resource_item');
        $items = array_values(array_filter($all, fn($r) => ($r['data']['resource_id'] ?? '') === $resourceId));

        return view('shared/module_archived', [
            'pageTitle'  => ($resource['name'] ?? 'Resource') . ' — Archived Units',
            'activePage' => 'resources',
            'userName'   => session()->get('userName') ?? 'User',
            'role'       => session()->get('role') ?? 'Staff',
            'module'     => 'resource_item',
            'title'      => ($resource['name'] ?? 'Resource') . ' — Archived Units',
            'backUrl'    => base_url('resources/viewItems/' . $resourceId),
            'items'      => $items,
        ]);
    }

    public function bulkDeleteItems($resourceId = null)
    {
        if (!session()->get('isLoggedIn')) return $this->response->setJSON(['success' => false]);
        helper('access');
        if (!pz_can('add_edit_resources')) return $this->response->setJSON(['success' => false]);

        $ids = $this->request->getPost('ids') ?? [];
        $db  = $this->firestore->getDb();
        $deleted = 0;
        foreach ((array)$ids as $itemId) {
            try {
                $snap = $db->collection('resource')->document($resourceId)->collection('items')->document($itemId)->snapshot();
                if ($snap->exists()) {
                    $this->cleanProjectResourceEntry($db, $resourceId, $itemId, $snap->data()['assigned_project_id'] ?? null);
                }
                $db->collection('resource')->document($resourceId)->collection('items')->document($itemId)->delete();
                $deleted++;
            } catch (\Throwable) {}
        }
        $this->syncResourceCounts($resourceId);
        return $this->response->setJSON(['success' => true, 'deleted' => $deleted]);
    }

    public function bulkArchiveItems($resourceId = null)
    {
        if (!session()->get('isLoggedIn')) return $this->response->setJSON(['success' => false]);
        helper('access');
        if (!pz_can('add_edit_resources')) return $this->response->setJSON(['success' => false]);

        $ids  = $this->request->getPost('ids') ?? [];
        $db   = $this->firestore->getDb();
        $trashService = new \App\Libraries\TrashService($this->firestore);
        $done = 0;
        foreach ((array)$ids as $itemId) {
            try {
                $snap = $db->collection('resource')->document($resourceId)->collection('items')->document($itemId)->snapshot();
                if (!$snap->exists()) continue;
                $data = $snap->data(); $data['id'] = $itemId; $data['resource_id'] = $resourceId;
                $name = ($data['serial_number'] ?? '') ?: $itemId;
                $this->cleanProjectResourceEntry($db, $resourceId, $itemId, $data['assigned_project_id'] ?? null);
                $trashService->moveToTrash('resource_item', $itemId, $name, $data, [], session()->get('userName') ?? 'System');
                $db->collection('resource')->document($resourceId)->collection('items')->document($itemId)->delete();
                $done++;
            } catch (\Throwable) {}
        }
        $this->syncResourceCounts($resourceId);
        return $this->response->setJSON(['success' => true, 'archived' => $done]);
    }

    private function syncResourceCounts(string $resourceId): void
    {
        try {
            $items     = $this->model->getResourceItems($resourceId);
            $total     = count($items);
            $available = $this->countAvailableUnits($items);
            $this->firestore->getDb()
                ->collection('resource')->document($resourceId)
                ->update([
                    ['path' => 'total_quantity',     'value' => $total],
                    ['path' => 'available_quantity',  'value' => $available],
                ]);
        } catch (\Throwable $e) {
            log_message('error', 'syncResourceCounts(' . $resourceId . '): ' . $e->getMessage());
        }
    }

    private function cleanProjectResourceEntry($db, string $resourceId, string $unitId, $assignedProjectId): void
    {
        $pid = $assignedProjectId instanceof \Google\Cloud\Firestore\DocumentReference
            ? $assignedProjectId->id()
            : trim((string)($assignedProjectId ?? ''));
        if ($pid === '') return;
        try {
            $snap    = $db->collection('project')->document($pid)->snapshot();
            if (!$snap->exists()) return;
            $current  = (array)($snap->data()['resources'] ?? []);
            $filtered = array_values(array_filter($current, function ($r) use ($resourceId, $unitId) {
                return !((string)($r['ref_id'] ?? '') === $resourceId && (string)($r['unit_id'] ?? '') === $unitId);
            }));
            if (count($filtered) !== count($current)) {
                $db->collection('project')->document($pid)->update([
                    ['path' => 'resources', 'value' => $filtered],
                ]);
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Resources::cleanProjectResourceEntry - ' . $e->getMessage());
        }
    }

    private function buildResourcePayload(): array
    {
        $category = (string) $this->request->getPost('category');
        $items = $this->normalizeItems($this->request->getPost('items') ?? []);

        $data = [
            'name'     => strtoupper(trim((string) $this->request->getPost('name'))),
            'category' => $category,
        ];

        if ($category === 'Manpower') {
            $firstName  = trim((string) $this->request->getPost('first_name'));
            $middleName = trim((string) $this->request->getPost('middle_name'));
            $lastName   = trim((string) $this->request->getPost('last_name'));
            // Full name includes middle name — used as scan match token in mobile
            $nameParts = array_filter([$firstName, $middleName, $lastName], fn($p) => $p !== '');
            $fullName  = implode(' ', $nameParts);
            $manpowerId = trim((string) $this->request->getPost('manpower_id'));

            if ($manpowerId === '') {
                $manpowerId = 'MP-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            }

            if ($fullName !== '') {
                $data['name'] = $fullName;
            }

            // category is not in the mobile manpower schema
            unset($data['category']);

            // Flattened manpower schema
            $data['employee_id']   = $manpowerId;
            $data['first_name']    = $firstName;
            if ($middleName !== '') $data['middle_name'] = $middleName;
            $data['last_name']     = $lastName;
            $data['status']        = trim((string) ($this->request->getPost('status') ?: $this->request->getPost('manpower_status')));
            $data['role']          = trim((string) $this->request->getPost('manpower_role'));
            $data['specialization'] = trim((string) $this->request->getPost('specialization'));
            // contact_number dropped from mobile schema — not written
            $data['assigned_project_id'] = trim((string) $this->request->getPost('assigned_project_id')) ?: null;
            $data['next_availability']   = trim((string) $this->request->getPost('next_availability')) ?: null;
            $rawSkills = $this->request->getPost('skills') ?? [];
            $data['skills'] = array_values(array_filter(array_map('trim', is_array($rawSkills) ? $rawSkills : [])));
            if (strtoupper($data['role']) === 'FOREMAN') {
                $data['skills'] = ['FOREMAN'];
            }

            // Workers always get a location; default to WAREHOUSE — mobile stamps real values on transfer arrival
            $site         = trim((string) $this->request->getPost('current_site_deployment'));
            $locationType = strtoupper(trim((string) $this->request->getPost('location_type'))) ?: 'WAREHOUSE';
            $data['location'] = [
                'address'  => $site,
                'type'     => $locationType,
                'geopoint' => null,
            ];
        }

        if (in_array($category, ['Equipment', 'Tools'], true)) {
            $addSpecificUnitsNow = (bool) $this->request->getPost('add_specific_units_now');
            $modelCode = trim((string) $this->request->getPost('model_code'));
            
            // For Equipment/Tools, use spec fields directly
            $data['model_code'] = $modelCode;
            $data['total_quantity'] = (int) ($this->request->getPost('total_quantity') ?: 0);
            $data['available_quantity'] = (int) ($this->request->getPost('available_quantity') ?: 0);
            $data['condition_summary'] = $this->buildConditionSummary($items);
            $data['running_serial_count'] = (int) ($this->request->getPost('running_serial_count') ?: 0);
            // asset_details nested map intentionally not written — mobile reads top-level fields
            // items embedded array intentionally not written — items subcollection is source of truth
        }

        return $data;
    }

    private function normalizeItems(array $rawItems): array
    {
        $items = [];

        foreach ($rawItems as $item) {
            // New spec fields
            $serialNumber = strtoupper(trim((string) ($item['serial_number'] ?? '')));
            $brand        = strtoupper(trim((string) ($item['brand_name'] ?? $item['brand'] ?? '')));
            $condition = trim((string) ($item['condition'] ?? ''));
            $status = trim((string) ($item['status'] ?? 'In-Storage'));
            $address = trim((string) ($item['address'] ?? ''));
            $locationType = strtoupper(trim((string) ($item['location_type'] ?? '')));
            $rawAssignedProjId = $item['assigned_project_id'] ?? null;
            $assignedProjectId = $rawAssignedProjId instanceof \Google\Cloud\Firestore\DocumentReference
                ? $rawAssignedProjId->id()
                : (trim((string)($rawAssignedProjId ?? '')) ?: null);
            $nextAvailability = trim((string) ($item['next_availability'] ?? '')) ?: null;

            // Fallback: legacy unit_id support for backward compatibility
            $unitId = trim((string) ($item['unit_id'] ?? ''));

            if ($serialNumber === '' && $unitId === '' && $condition === '' && $status === '') {
                continue;
            }

            // Use serial_number if available, otherwise generate from unit_id
            if ($serialNumber === '' && $unitId !== '') {
                $serialNumber = $unitId;
            }

            // Build item with new spec structure
            // __docId is used as the Firestore document ID and is stripped before writing
            $itemData = [
                '__docId'      => trim((string) ($item['id'] ?? '')) ?: bin2hex(random_bytes(8)),
                'serial_number' => $serialNumber,
                'brand_name'   => $brand,
                'condition'    => $condition,
                'status'       => $status,
                'assigned_project_id' => $assignedProjectId,
                'next_availability'   => $nextAvailability,
                'location' => [
                    'address' => $address,
                    'type'    => $locationType,
                ],
            ];

            // Handle geopoint if provided
            $lat = $item['location_geopoint_lat'] ?? $item['geopoint_lat'] ?? null;
            $lng = $item['location_geopoint_lng'] ?? $item['geopoint_lng'] ?? null;
            if ($lat !== null && $lng !== null && is_numeric($lat) && is_numeric($lng)) {
                $itemData['location']['geopoint'] = [
                    'lat' => (float) $lat,
                    'lng' => (float) $lng
                ];
            }

            $items[] = $itemData;
        }

        return $items;
    }

    private function enrichItemsWithProjectNames(array $items): array
    {
        static $validStatuses = ['In-Storage', 'In-Use', 'Repair', 'Transfer Pending'];

        $db = $this->firestore->getDb();
        $cache = [];
        foreach ($items as &$item) {
            // Normalize statuses written by the mobile app to the web's known values
            $rawStatus = $item['status'] ?? '';
            if (!in_array($rawStatus, $validStatuses, true)) {
                $apid = $item['assigned_project_id'] ?? null;
                $hasPid = $apid instanceof \Google\Cloud\Firestore\DocumentReference
                    ? true
                    : (trim((string)($apid ?? '')) !== '');
                $item['status'] = $hasPid ? 'In-Use' : 'In-Storage';
            }

            $apid = $item['assigned_project_id'] ?? null;
            $pid  = $apid instanceof \Google\Cloud\Firestore\DocumentReference
                ? $apid->id()
                : trim((string)($apid ?? ''));
            if ($pid === '') {
                $item['assigned_to'] = '';
                continue;
            }
            if (!array_key_exists($pid, $cache)) {
                try {
                    $snap     = $db->collection('project')->document($pid)->snapshot();
                    $cache[$pid] = $snap->exists()
                        ? trim((string)($snap->data()['project_name'] ?? '')) ?: $pid
                        : '';
                } catch (\Throwable) {
                    $cache[$pid] = '';
                }
            }
            $item['assigned_to'] = $cache[$pid];
        }
        unset($item);
        return $items;
    }

    /**
     * Predicts when currently in-use items will free up, via the ML service's
     * /predict/next-available-tools endpoint. Skips items already released or
     * in transit (those already have a definitive availability state). Fails
     * silently (items keep their default "—" availability) if the ML call errors.
     */
    private function attachNextAvailablePredictions(array $items, string $resourceName, string $resourceCategory): array
    {
        $db = $this->firestore->getDb();

        // Collect in-use items that still need a prediction
        $candidates = [];
        foreach ($items as $idx => $item) {
            $status = strtolower(str_replace(['-', ' '], '', (string)($item['status'] ?? '')));
            if ($status !== 'inuse') continue;
            if (!empty($item['transfer_locked'])) continue;
            if (($item['released_at'] ?? null) !== null) continue;

            $apid = $item['assigned_project_id'] ?? null;
            $pid  = $apid instanceof \Google\Cloud\Firestore\DocumentReference ? $apid->id() : trim((string)($apid ?? ''));
            if ($pid === '') continue;

            $candidates[$idx] = $pid;
        }
        if (empty($candidates)) return $items;

        try {
            // Real project data: progress + linear expected-progress-by-now, same
            // formula used on the dashboard (ProjectDelayPredictor::buildFeatures()).
            $now             = time();
            $projectsPayload = [];
            $projectCache    = [];
            $activeCount     = 0;

            foreach ($db->collection('project')->documents() as $doc) {
                if (!$doc->exists()) continue;
                $d      = $doc->data();
                $status = strtoupper(trim((string)($d['status'] ?? '')));
                if (!in_array($status, ['COMPLETED', 'DONE'], true)) $activeCount++;
            }

            $hasActiveBlocker = [];
            foreach (array_unique($candidates) as $pid) {
                $snap = $db->collection('project')->document($pid)->snapshot();
                if (!$snap->exists()) continue;
                $d = $snap->data();

                $startTs       = $this->toTimestamp($d['start_date'] ?? null);
                $endTs         = $this->toTimestamp($d['end_date']   ?? null);
                $totalDuration = ($startTs && $endTs && $endTs > $startTs) ? max(1, (int)(($endTs - $startTs) / 86400)) : 0;
                $daysElapsed   = $startTs ? max(0, (int)(($now - $startTs) / 86400)) : 0;
                if ($totalDuration > 0) $daysElapsed = min($daysElapsed, $totalDuration);
                $daysRemaining = $endTs ? max(0, (int)(($endTs - $now) / 86400)) : 30;
                $totalDays     = $totalDuration > 0 ? $totalDuration : max(1, $daysElapsed + $daysRemaining);
                $plannedPct    = min(100.0, round(($daysElapsed / $totalDays) * 100, 2));

                // Prefer the latest SE daily report over the project doc's overall_progress —
                // that field is only touched by manual project edits, not by report submissions,
                // so relying on it means this prediction would never move when an SE files a report.
                $progress = null;
                try {
                    $latestReport = $db->collection('project')->document($pid)
                        ->collection('daily_progress_reports')
                        ->orderBy('report_date', 'DESC')->limit(1)->documents();
                    foreach ($latestReport as $rDoc) {
                        if (!$rDoc->exists()) break;
                        $rp = $rDoc->data()['total_completion_percent'] ?? null;
                        if ($rp !== null) $progress = (float)$rp;
                        break;
                    }
                } catch (\Throwable $e) {
                    log_message('warning', 'Resources::attachNextAvailablePredictions daily report - ' . $e->getMessage());
                }
                if ($progress === null) {
                    $progress = (float)($d['overall_progress'] ?? $d['completion_percent'] ?? 0);
                }
                $progress = min(100, max(0, $progress));

                $projectCache[$pid]    = ['name' => (string)($d['project_name'] ?? $pid), 'progress' => $progress];
                $projectsPayload[$pid] = [
                    'id'                  => $pid,
                    'name'                => (string)($d['project_name'] ?? $pid),
                    'progress_percent'    => $progress,
                    'planned_progress'    => $plannedPct,
                    'days_remaining'      => $daysRemaining,
                    'concurrent_projects' => $activeCount,
                ];

                // Ground-truth check (unresolved emergency report) OR the ML's own
                // blocker classification (e.g. "Low Progress" needs no filed report at all).
                $hasActiveBlocker[$pid] = $this->_projectHasActiveBlocker($pid)
                    || $this->_projectHasPredictedBlocker($pid, $progress, $plannedPct, $daysElapsed, $daysRemaining, $d);
            }
            if (empty($projectsPayload)) return $items;

            $toolsPayload = [];
            foreach ($candidates as $idx => $pid) {
                if (!isset($projectsPayload[$pid])) continue;
                $item = $items[$idx];
                $toolsPayload[] = [
                    'id'                  => (string)($item['id'] ?? ''),
                    'name'                => $resourceName . ' — ' . (string)($item['serial_number'] ?? ''),
                    'tool_type'           => strtolower($resourceCategory) ?: 'equipment',
                    'assigned_project_id' => $pid,
                ];
            }
            if (empty($toolsPayload)) return $items;

            $mlService = new MLServiceClient();
            $result    = $mlService->predictNextAvailableTools([
                'projects' => array_values($projectsPayload),
                'tools'    => $toolsPayload,
                'season'   => (int)date('n'),
            ]);
            if (isset($result['error']) || empty($result['tools'])) return $items;

            $byToolId = [];
            foreach ($result['tools'] as $t) {
                $byToolId[(string)($t['tool_id'] ?? '')] = $t;
            }

            foreach ($candidates as $idx => $pid) {
                $toolId = (string)($items[$idx]['id'] ?? '');
                if (!isset($byToolId[$toolId])) continue;
                $t = $byToolId[$toolId];
                $daysFree = (int)($t['estimated_days_free'] ?? 0);
                // A tool can't still be "in use" past its own project's deadline — cap the
                // raw model output to the project's real remaining timeline (same rationale
                // as the Dashboard's delay-days cap: the model doesn't self-limit to this).
                $daysRemainingForProject = (int)($projectsPayload[$pid]['days_remaining'] ?? $daysFree);
                $daysFree = min($daysFree, $daysRemainingForProject);
                $items[$idx]['_predicted_days_free']       = $daysFree;
                $items[$idx]['_predicted_near_completion'] = !empty($t['near_completion']);
                $items[$idx]['_predicted_available_date']  = date('m/d/Y', $now + ($daysFree * 86400));
                // The ML model has no concept of blockers — it only reasons about progress
                // vs. timeline. Flag separately so the view can warn the estimate may slip.
                $items[$idx]['_predicted_has_blocker']     = $hasActiveBlocker[$pid] ?? false;
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Resources::attachNextAvailablePredictions - ' . $e->getMessage());
        }

        return $items;
    }

    /** True if the project has any unresolved emergency_reports entry (material/labor/onsite/weather/etc). */
    private function _projectHasActiveBlocker(string $pid): bool
    {
        if ($pid === '') return false;
        try {
            $docs = $this->firestore->getDb()
                ->collection('project')->document($pid)
                ->collection('emergency_reports')->documents();
            foreach ($docs as $doc) {
                if (!$doc->exists()) continue;
                if (empty($doc->data()['resolved'])) return true;
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Resources::_projectHasActiveBlocker - ' . $e->getMessage());
        }
        return false;
    }

    /**
     * Checks a project's unresolved emergency_reports for specific cause labels and
     * returns the 0/1 flags the ML service expects. Mirrors Dashboard.php's version —
     * kept in sync manually since the two controllers don't share a base class for this.
     */
    private function _computeEmergencyFlags(string $pid): array
    {
        $flags = ['equipment' => 0, 'labor' => 0, 'onsite' => 0, 'weather' => 0];
        if ($pid === '') return $flags;

        try {
            $docs = $this->firestore->getDb()
                ->collection('project')->document($pid)
                ->collection('emergency_reports')->documents();

            foreach ($docs as $doc) {
                if (!$doc->exists()) continue;
                $d = $doc->data();
                if (!empty($d['resolved'])) continue;

                $causes = array_map('strval', (array)($d['causes'] ?? []));
                if (in_array('Material Shortage', $causes, true) || in_array('Equipment/Tool Breakdown', $causes, true)) {
                    $flags['equipment'] = 1;
                }
                if (in_array('Labor Shortage', $causes, true))   $flags['labor']   = 1;
                if (in_array('Onsite Accidents', $causes, true)) $flags['onsite']  = 1;
                if (in_array('Adverse Weather', $causes, true))  $flags['weather'] = 2;
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Resources::_computeEmergencyFlags - ' . $e->getMessage());
        }

        return $flags;
    }

    /**
     * Calls the ML /predict/blockers classification for the project — catches cases
     * like "Low Progress" that have no filed emergency report at all, unlike
     * _projectHasActiveBlocker() which only sees confirmed SE-reported incidents.
     * Same feature shape as Dashboard.php's ML loop, built from data already on hand.
     */
    private function _projectHasPredictedBlocker(string $pid, float $progress, float $plannedPct, int $daysElapsed, int $daysRemaining, array $projectDoc): bool
    {
        if ($pid === '') return false;
        try {
            $eqTotal = 0; $eqActive = 0;
            foreach ((array)($projectDoc['resources'] ?? []) as $r) {
                if (strtolower((string)($r['category'] ?? '')) === 'manpower') continue;
                $eqTotal++;
                if (empty($r['transit'])) $eqActive++;
            }
            $eqUtil = $eqTotal > 0 ? round($eqActive / $eqTotal, 2) : 1.0;

            $emergFlags = $this->_computeEmergencyFlags($pid);

            $features = [
                'project_id'                    => $pid,
                'progress_percent'              => $progress,
                'planned_progress_percent'      => $plannedPct,
                'days_elapsed'                  => $daysElapsed,
                'days_remaining'                => $daysRemaining,
                'workers_present'               => (int)($projectDoc['workers_present'] ?? 0),
                'workers_planned'               => (int)($projectDoc['workers_planned'] ?? 1),
                'equipment_utilization'         => $eqUtil,
                'weather_severity'              => $emergFlags['weather'],
                'site_access_issues'            => (int)($projectDoc['site_access_issues'] ?? 0),
                'avg_progress_3d'               => $progress,
                'progress_trend_3d'             => 0.0,
                'consecutive_low_days'          => 0,
                'budget_utilization'            => 0.0,
                'cost_variance_ratio'           => 0.0,
                'active_blocker_count'          => (int)($projectDoc['active_blocker_count'] ?? 0),
                'days_since_last_blocker'       => (int)($projectDoc['days_since_last_blocker'] ?? 999),
                'emergency_equipment_shortage'  => $emergFlags['equipment'],
                'emergency_labor_shortage'      => $emergFlags['labor'],
                'emergency_onsite_accidents'    => $emergFlags['onsite'],
            ];

            $result = (new MLServiceClient())->predictBlockers($features);
            if (isset($result['error'])) return false;

            $raw = $result['blocker_type'] ?? $result['predicted_blocker'] ?? $result['prediction'] ?? 'No Blocker';
            return !in_array(strtolower((string)$raw), ['no blocker', 'unknown', ''], true);
        } catch (\Throwable $e) {
            log_message('warning', 'Resources::_projectHasPredictedBlocker - ' . $e->getMessage());
            return false;
        }
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

    private function buildConditionSummary(array $items): array
    {
        $summary = [];

        foreach ($items as $item) {
            $condition = trim((string) ($item['condition'] ?? ''));
            if ($condition === '') {
                continue;
            }

            $summary[$condition] = ($summary[$condition] ?? 0) + 1;
        }

        return $summary;
    }

    private function syncResourceItems(string $resourceId, array $rawItems): void
    {
        // Expand items whose qty stepper is > 1 into individual item entries
        $expandedRaw = [];
        foreach ($rawItems as $item) {
            $qty    = max(1, (int)($item['quantity'] ?? 1));
            $serial = trim((string)($item['serial_number'] ?? ''));
            for ($i = 0; $i < $qty; $i++) {
                $copy = $item;
                $copy['serial_number'] = $this->_deriveSerial($serial, $i);
                $copy['quantity'] = 1;
                $expandedRaw[] = $copy;
            }
        }

        $items = $this->normalizeItems($expandedRaw);
        $itemsRef = $this->model->getFirestoreInstance()->getDb()
            ->collection('resource')
            ->document($resourceId)
            ->collection('items');

        // Delete existing items
        foreach ($itemsRef->documents() as $existingItem) {
            if ($existingItem->exists()) {
                $itemsRef->document($existingItem->id())->delete();
            }
        }

        // Get resource to access model_code for serial number generation
        $resource = $this->model->getResourceById($resourceId);
        $modelCode = $resource['model_code'] ?? '';

        // Sync items to subcollection
        foreach ($items as $item) {
            $itemId = $item['__docId'];
            unset($item['__docId']);

            // Generate serial number if empty and model_code is available
            if (empty($item['serial_number']) && !empty($modelCode)) {
                $item['serial_number'] = $this->model->generateNextSerialNumber($resourceId, $modelCode);
            }

            // Normalize created_at to Firestore Timestamp if numeric, otherwise create now
            if (isset($item['created_at']) && is_numeric($item['created_at'])) {
                $dt = new \DateTime();
                $dt->setTimestamp((int) $item['created_at']);
                $item['created_at'] = new \Google\Cloud\Core\Timestamp($dt);
            } elseif (empty($item['created_at'])) {
                $item['created_at'] = new \Google\Cloud\Core\Timestamp(new \DateTime());
            }

            // Always set updated_at to now as Firestore Timestamp
            $item['updated_at'] = new \Google\Cloud\Core\Timestamp(new \DateTime());

            $itemsRef->document($itemId)->set($item);
        }
    }

    private function cleanupDeletedFromProjects(string $id): void
    {
        $db = $this->firestore->getDb();
        $projectIds = [];

        // Equipment/tools: collect project IDs from items subcollection
        try {
            foreach ($db->collection('resource')->document($id)->collection('items')->documents() as $itemDoc) {
                if (!$itemDoc->exists()) continue;
                $apid = $itemDoc->data()['assigned_project_id'] ?? null;
                $apid = $apid instanceof \Google\Cloud\Firestore\DocumentReference ? $apid->id() : (string)($apid ?? '');
                if ($apid !== '') $projectIds[$apid] = true;
            }
        } catch (\Throwable $e) {}

        // Manpower: collect project ID from the manpower doc itself
        try {
            $mpSnap = $db->collection('manpower')->document($id)->snapshot();
            if ($mpSnap->exists()) {
                $apid = $mpSnap->data()['assigned_project_id'] ?? null;
                $apid = $apid instanceof \Google\Cloud\Firestore\DocumentReference ? $apid->id() : (string)($apid ?? '');
                if ($apid !== '') $projectIds[$apid] = true;
            }
        } catch (\Throwable $e) {}

        foreach (array_keys($projectIds) as $projectId) {
            try {
                $projSnap = $db->collection('project')->document($projectId)->snapshot();
                if (!$projSnap->exists()) continue;
                $resources = (array)($projSnap->data()['resources'] ?? []);
                $filtered  = array_values(array_filter($resources, function ($r) use ($id) {
                    $refId = (string)($r['ref_id'] ?? $r['id'] ?? '');
                    return $refId !== $id;
                }));
                if (count($filtered) !== count($resources)) {
                    $db->collection('project')->document($projectId)->update([
                        ['path' => 'resources', 'value' => $filtered],
                    ]);
                }
            } catch (\Throwable $e) {
                log_message('warning', 'Resources::cleanupDeletedFromProjects - ' . $e->getMessage());
            }
        }
    }

    private function deleteResourceItems(string $resourceId): void
    {
        $itemsRef = $this->model->getFirestoreInstance()->getDb()
            ->collection('resource')
            ->document($resourceId)
            ->collection('items');

        foreach ($itemsRef->documents() as $item) {
            if ($item->exists()) {
                $itemsRef->document($item->id())->delete();
            }
        }

        // Also clear the items array on the parent document
        try {
            $this->model->getFirestoreInstance()->getDb()
                ->collection('resource')
                ->document($resourceId)
                ->set(['items' => []], ['merge' => true]);
        } catch (\Exception $e) {
            // ignore
        }
    }

    private function sortItemsBySerial(array $items): array
    {
        usort($items, fn($a, $b) => strnatcasecmp(
            (string) ($a['serial_number'] ?? ''),
            (string) ($b['serial_number'] ?? '')
        ));
        return $items;
    }

    private function groupItemsForEdit(array $items): array
    {
        $groups  = [];
        $prevKey = null;

        foreach ($items as $item) {
            $loc = is_array($item['location'] ?? null) ? $item['location'] : [];
            $key = implode("\x00", [
                strtolower(trim((string)($item['brand_name'] ?? $item['brand'] ?? ''))),
                strtolower(trim((string)($item['condition'] ?? ''))),
                strtolower(trim((string)($item['status'] ?? ''))),
                strtolower(trim((string)($loc['type'] ?? ''))),
                trim((string)($loc['address'] ?? '')),
            ]);

            if ($prevKey !== null && $key === $prevKey) {
                $groups[count($groups) - 1]['quantity']++;
            } else {
                $copy             = $item;
                $copy['quantity'] = 1;
                $groups[]         = $copy;
                $prevKey          = $key;
            }
        }

        return $groups;
    }

    private function normalizeResourceForView(array $resource): array
    {
        $resource['location'] = is_array($resource['location'] ?? null) ? $resource['location'] : ['address' => '', 'type' => ''];
        $resource['created_at_value'] = $this->formatDateValue($resource['created_at'] ?? '');
        $resource['updated_at_value'] = $this->formatDateValue($resource['updated_at'] ?? '');
        $resource['next_availability_value'] = $this->normalizeDateValue($resource['next_availability'] ?? '');
        $resource['category_label'] = (string) ($resource['category'] ?? '');
        $resource['asset_details'] = is_array($resource['asset_details'] ?? null) ? $resource['asset_details'] : [];
        $resource['manpower_details'] = is_array($resource['manpower_details'] ?? null) ? $resource['manpower_details'] : [];
        $resource['total_quantity_display'] = (int) ($resource['total_quantity'] ?? $resource['asset_details']['total_quantity'] ?? 0);
        $resource['available_units_display'] = max(0, (int) ($resource['available_quantity'] ?? $resource['asset_details']['available_quantity'] ?? 0));

        // Ensure model_code is populated for Equipment/Tools
        if (empty($resource['model_code']) && in_array($resource['category'] ?? '', ['Equipment', 'Tools'], true)) {
            $resource['model_code'] = '';
        }

        $derivedItems = [];
        if (in_array($resource['category'] ?? '', ['Equipment', 'Tools'], true)) {
            if (!empty($resource['items']) && is_array($resource['items'])) {
                $derivedItems = $resource['items'];
            } elseif (!empty($resource['id'])) {
                try {
                    $derivedItems = $this->model->getResourceItems($resource['id']);
                } catch (\Exception $e) {
                    $derivedItems = [];
                }
            }

            if (!empty($derivedItems)) {
                $resource['total_quantity_display'] = count($derivedItems);
                $resource['available_units_display'] = $this->countAvailableUnits($derivedItems);
                $resource['asset_details']['total_quantity'] = $resource['total_quantity_display'];
                $resource['asset_details']['available_quantity'] = $resource['available_units_display'];
            }
        }

        // Ensure condition_summary exists for Equipment/Tools
        $conditionSummary = $resource['condition_summary'] ?? $resource['asset_details']['condition_summary'] ?? [];
        if (!is_array($conditionSummary) || empty($conditionSummary)) {
            $conditionSummary = $this->buildConditionSummary($derivedItems);
        }
        
        $resource['condition_summary'] = $conditionSummary;
        
        // Update asset_details with correct condition_summary
        if (!empty($resource['asset_details'])) {
            $resource['asset_details']['condition_summary'] = $conditionSummary;
        }

        // If document uses flattened manpower fields (new `manpower` collection), map them
        // into `manpower_details` so views and forms continue to work.
        if (empty($resource['manpower_details']) && (isset($resource['first_name']) || isset($resource['employee_id']) || isset($resource['last_name']))) {
            $resource['manpower_details'] = [
                'first_name' => $resource['first_name'] ?? '',
                'middle_name' => $resource['middle_name'] ?? '',
                'last_name' => $resource['last_name'] ?? '',
                'manpower_id' => $resource['employee_id'] ?? ($resource['manpower_details']['manpower_id'] ?? ''),
                'status' => $resource['status'] ?? ($resource['manpower_details']['status'] ?? ''),
                'role' => $resource['role'] ?? ($resource['manpower_details']['role'] ?? ''),
                'specialization' => $resource['specialization'] ?? ($resource['manpower_details']['specialization'] ?? ''),
                'assigned_project_id' => (function($v){ return $v instanceof \Google\Cloud\Firestore\DocumentReference ? $v->id() : ($v ?: null); })($resource['assigned_project_id'] ?? ($resource['manpower_details']['assigned_project_id'] ?? null)),
                'skills' => is_array($resource['skills'] ?? null) ? $resource['skills'] : ($resource['manpower_details']['skills'] ?? []),
                'current_site_deployment' => '',
            ];
            // Prefer showing project name when assigned_project_id exists
            $rawAssignedPid = $resource['assigned_project_id'] ?? ($resource['manpower_details']['assigned_project_id'] ?? null);
            $assignedPid = $rawAssignedPid instanceof \Google\Cloud\Firestore\DocumentReference
                ? $rawAssignedPid->id()
                : (trim((string)($rawAssignedPid ?? '')) ?: null);
            if (!empty($assignedPid)) {
                try {
                    $projSnap = $this->model->getFirestoreInstance()->getDb()->collection('project')->document($assignedPid)->snapshot();
                    if ($projSnap->exists()) {
                        $projData = $projSnap->data();
                        $projName = trim((string)($projData['project_name'] ?? $projData['location']['address'] ?? ''));
                        $resource['manpower_details']['current_site_deployment']  = $projName;
                        $resource['manpower_details']['assigned_project_name']    = $projName;
                    } else {
                        $resource['manpower_details']['current_site_deployment'] = $resource['location']['address'] ?? ($resource['manpower_details']['current_site_deployment'] ?? '');
                    }
                } catch (\Exception $e) {
                    $resource['manpower_details']['current_site_deployment'] = $resource['location']['address'] ?? ($resource['manpower_details']['current_site_deployment'] ?? '');
                }
            } else {
                $resource['manpower_details']['current_site_deployment'] = $resource['location']['address'] ?? ($resource['manpower_details']['current_site_deployment'] ?? '');
            }
        }

        // Backward-compat: older records stored the specialization value inside skills[].
        // Lift it into the specialization field so both columns display correctly.
        if (!empty($resource['manpower_details']) && is_array($resource['manpower_details'])
            && empty($resource['manpower_details']['specialization'])
        ) {
            $specEnum  = ['BUILD-TO-SUIT', 'RETROFITTING', 'COLOCATION', 'CONVERSION'];
            $skillsArr = is_array($resource['manpower_details']['skills'] ?? null) ? $resource['manpower_details']['skills'] : [];
            foreach ($skillsArr as $k => $s) {
                if (in_array(strtoupper(trim((string)$s)), $specEnum, true)) {
                    $resource['manpower_details']['specialization'] = strtoupper(trim((string)$s));
                    unset($skillsArr[$k]);
                    $resource['manpower_details']['skills'] = array_values($skillsArr);
                    break;
                }
            }
        }

        // Populate name from manpower details or from model_code when missing
        if (trim((string) ($resource['name'] ?? '')) === '') {
            $fullName = trim((string) ($resource['manpower_details']['first_name'] ?? '') . ' ' . (string) ($resource['manpower_details']['last_name'] ?? ''));
            if ($fullName !== '') {
                $resource['name'] = $fullName;
            } elseif (!empty($resource['model_code'])) {
                $resource['name'] = (string) $resource['model_code'];
            }
        }

        $resource['condition_label'] = $this->getPrimaryConditionLabel($conditionSummary);

        // Populate manpower-specific fallbacks so Manpower records render consistently
        if (!empty($resource['manpower_details']) && is_array($resource['manpower_details'])) {
            $mp = $resource['manpower_details'];
            if (empty($resource['status']) && !empty($mp['status'])) {
                $resource['status'] = (string) $mp['status'];
            }

            if (empty($resource['location']['address']) && !empty($mp['current_site_deployment'])) {
                $resource['location']['address'] = (string) $mp['current_site_deployment'];
            }

            if (empty($resource['manpower_details']['manpower_id'])) {
                try {
                    $resource['manpower_details']['manpower_id'] = 'MP-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
                } catch (\Exception $e) {
                    $resource['manpower_details']['manpower_id'] = 'MP-UNKNOWN';
                }
            }

            // Resolve assigned_project_name if not already set
            if (empty($resource['manpower_details']['assigned_project_name'])) {
                $rawPid = $resource['manpower_details']['assigned_project_id'] ?? $resource['assigned_project_id'] ?? null;
                $pid = $rawPid instanceof \Google\Cloud\Firestore\DocumentReference
                    ? $rawPid->id()
                    : (trim((string)($rawPid ?? '')) ?: null);
                if (!empty($pid)) {
                    try {
                        $ps = $this->model->getFirestoreInstance()->getDb()->collection('project')->document($pid)->snapshot();
                        if ($ps->exists()) {
                            $resource['manpower_details']['assigned_project_name'] = trim((string)($ps->data()['project_name'] ?? ''));
                        }
                    } catch (\Exception $e) {}
                }
            }
        }

        return $resource;
    }

    private function countAvailableUnits(array $items): int
    {
        $count = 0;

        foreach ($items as $item) {
            $status = strtolower(trim((string) ($item['status'] ?? '')));
            $condition = strtolower(trim((string) ($item['condition'] ?? '')));

            if ($status === '' && in_array($condition, ['functional', 'new'], true)) {
                $count++;
                continue;
            }

            if (in_array($status, ['in-storage', 'available'], true)) {
                $count++;
            }
        }

        return $count;
    }

    private function getProjectSiteOptions(): array
    {
        $sites = [];
        $seen = [];

        foreach ($this->model->getFirestoreInstance()->getDb()->collection('project')->documents() as $doc) {
            if (!$doc->exists()) {
                continue;
            }

            $data = $doc->data();
            $address = trim((string) ($data['location']['address'] ?? ''));
            if ($address === '' || isset($seen[$address])) {
                continue;
            }

            $seen[$address] = true;
            $projectName = trim((string) ($data['project_name'] ?? 'Project'));
            $sites[] = [
                'value' => $address,
                'label' => $projectName !== '' ? $projectName . ' - ' . $address : $address,
            ];
        }

        return $sites;
    }

    /**
     * Return a list of projects for selects: id => project_name
     */
    private function getProjectOptions(): array
    {
        $projects = [];

        foreach ($this->model->getFirestoreInstance()->getDb()->collection('project')->documents() as $doc) {
            if (!$doc->exists()) continue;
            $data = $doc->data();
            $projects[] = [
                'value' => $doc->id(),
                'label' => trim((string)($data['project_name'] ?? $doc->id())),
                'address' => trim((string)($data['location']['address'] ?? '')),
            ];
        }

        return $projects;
    }

    private function getWarehouseLocationOptions(): array
    {
        $warehouses = [];
        $seen = [];

        foreach ($this->model->getAllResources() as $resource) {
            $location = is_array($resource['location'] ?? null) ? $resource['location'] : [];
            $type = trim((string) ($location['type'] ?? ''));
            $address = trim((string) ($location['address'] ?? ''));

            if ($type !== 'Warehouse' || $address === '' || isset($seen[$address])) {
                continue;
            }

            $seen[$address] = true;
            $warehouses[] = [
                'value' => $address,
                'label' => $address,
            ];
        }

        return $warehouses;
    }

    private function getDefaultWarehouseAddress(): string
    {
        try {
            $doc = $this->firestore->getDb()
                ->collection('app_settings')
                ->document('warehouse')
                ->snapshot();
            if ($doc->exists()) {
                return trim((string) ($doc->data()['default_address'] ?? ''));
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Resources::getDefaultWarehouseAddress - ' . $e->getMessage());
        }
        return '';
    }

    private function getSavedWarehouseLocations(): array
    {
        try {
            $doc = $this->firestore->getDb()
                ->collection('app_settings')
                ->document('warehouse')
                ->snapshot();
            if ($doc->exists()) {
                $locs = $doc->data()['saved_locations'] ?? [];
                return is_array($locs) ? array_values($locs) : [];
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Resources::getSavedWarehouseLocations - ' . $e->getMessage());
        }
        return [];
    }

    public function saveWarehouseLocation()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON([]);
        }
        $name    = trim((string)($this->request->getPost('name') ?? ''));
        $address = trim((string)($this->request->getPost('address') ?? ''));
        if ($address === '') {
            return $this->response->setJSON(['success' => false, 'message' => 'Address is required']);
        }
        try {
            $docRef = $this->firestore->getDb()->collection('app_settings')->document('warehouse');
            $snap   = $docRef->snapshot();
            $existing = $snap->exists() ? ($snap->data()['saved_locations'] ?? []) : [];
            if (!is_array($existing)) $existing = [];
            // Remove any existing entry with same address (upsert)
            $existing = array_values(array_filter($existing, fn($l) => ($l['address'] ?? '') !== $address));
            $existing[] = ['name' => $name !== '' ? $name : $address, 'address' => $address];
            $docRef->set(['saved_locations' => $existing], ['merge' => true]);
            return $this->response->setJSON(['success' => true, 'saved_locations' => $existing]);
        } catch (\Exception $e) {
            log_message('error', 'Resources::saveWarehouseLocation - ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to save']);
        }
    }

    public function deleteWarehouseLocation()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON([]);
        }
        $address = trim((string)($this->request->getPost('address') ?? ''));
        try {
            $docRef = $this->firestore->getDb()->collection('app_settings')->document('warehouse');
            $snap   = $docRef->snapshot();
            $existing = $snap->exists() ? ($snap->data()['saved_locations'] ?? []) : [];
            if (!is_array($existing)) $existing = [];
            $existing = array_values(array_filter($existing, fn($l) => ($l['address'] ?? '') !== $address));
            $docRef->set(['saved_locations' => $existing], ['merge' => true]);
            return $this->response->setJSON(['success' => true, 'saved_locations' => $existing]);
        } catch (\Exception $e) {
            log_message('error', 'Resources::deleteWarehouseLocation - ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to delete']);
        }
    }

    public function saveWarehouseDefault()
    {
        try {
            $address = trim((string) ($this->request->getPost('warehouse_default_address') ?? ''));
            if ($address === '') {
                return $this->response->setJSON(['success' => false, 'message' => 'Address cannot be empty']);
            }
            $this->firestore->getDb()
                ->collection('app_settings')
                ->document('warehouse')
                ->set(['default_address' => $address, 'updated_at' => time()], ['merge' => true]);
            return $this->response->setJSON(['success' => true, 'address' => $address]);
        } catch (\Exception $e) {
            log_message('error', 'Resources::saveWarehouseDefault - ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to save']);
        }
    }

    private function getPrimaryConditionLabel($summary): string
    {
        if (!is_array($summary) || $summary === []) {
            return 'N/A';
        }

        $keys = array_keys($summary);
        return (string) ($keys[0] ?? 'N/A');
    }

    private function normalizeDateValue($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_numeric($value)) {
            return date('Y-m-d\TH:i', (int) $value);
        }

        $timestamp = strtotime((string) $value);

        return $timestamp ? date('Y-m-d\TH:i', $timestamp) : (string) $value;
    }

    private function formatDateValue($value): string
    {
        if ($value === null || $value === '') {
            return 'N/A';
        }

        if (is_object($value) && method_exists($value, 'get')) {
            try {
                $value = $value->get();
            } catch (\Throwable $e) {
                return 'N/A';
            }
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_numeric($value)) {
            return date('Y-m-d H:i:s', (int) $value);
        }

        $timestamp = strtotime((string) $value);

        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : (string) $value;
    }
}