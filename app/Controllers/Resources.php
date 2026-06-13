<?php

namespace App\Controllers;

use App\Models\Model_Resources;
use App\Models\Model_Manpower;
use Config\GoogleMaps;

class Resources extends BaseController
{
    protected $model;
    protected $manpowerModel;

    public function __construct()
    {
        $this->model = new Model_Resources();
        $this->manpowerModel = new Model_Manpower();
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

            // — Read and apply equipment/tools sort filters —
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

            // — Read and apply manpower filters —
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

            return view('resources/resources_view', [
                'equipment'             => $equipment,
                'tools'                 => $tools,
                'manpower'              => $manpower,
                'activeEquipFilters'    => ['eq_sort'    => $eqSort],
                'activeToolFilters'     => ['tools_sort' => $toolsSort],
                'activeManpowerFilters' => ['mp_role' => $mpRole, 'mp_spec' => $mpSpec, 'mp_status' => $mpStatus],
                'activeTab'             => $this->request->getGet('tab') ?? 'equipment',
                'userName'           => session()->get('userName') ?? 'User',
                'role'               => session()->get('role') ?? 'User',
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
        $category = $this->request->getGet('category') ?? '';

        if ($category === 'Manpower') {
            return redirect()->to('manpower/create');
        }

        $mapsKey = (new GoogleMaps())->browserKey;

        if ($category === 'Equipment') {
            return view('resources/equipments_form', [
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
                'mapsKey' => $mapsKey,
                'userName' => session()->get('userName') ?? 'User',
                'role' => session()->get('role') ?? 'User'
            ]);
        }

        if ($category === 'Tools') {
            return view('resources/tools_form', [
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
                'mapsKey' => $mapsKey,
                'userName' => session()->get('userName') ?? 'User',
                'role' => session()->get('role') ?? 'User'
            ]);
        }

        return view('resources/resources_form', [
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
        try {
            $data = $this->buildResourcePayload();

            if (($data['category'] ?? '') === 'Manpower') {
                $resourceId = $this->manpowerModel->createManpower($data);
                // manpower has no items
            } else {
                $resourceId = $this->model->createResource($data);
                if (in_array($data['category'] ?? '', ['Equipment', 'Tools'], true)) {
                    $this->syncResourceItems($resourceId, $this->request->getPost('items') ?? []);
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

            return view($view, [
                'resource' => $resource,
                'items' => $isManpower ? [] : $this->model->getResourceItems($id),
                'projectSites' => $this->getProjectSiteOptions(),
                'projects' => $this->getProjectOptions(),
                'warehouseLocations' => $this->getWarehouseLocationOptions(),
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
                } elseif ($category === 'Tools') {
                    $this->syncResourceItems($id, $this->request->getPost('items') ?? []);
                } else {
                    $this->deleteResourceItems($id);
                }
            }

            return redirect()->to('resources')->with('success', 'Resource updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error updating resource: ' . $e->getMessage());
        }
    }

    /**
     * Delete a resource
     */
    public function delete($id = null)
    {
        try {
            if (strtolower($this->request->getMethod()) === 'post') {
                $id = $this->request->getPost('id') ?? $id;

                if (!$id) {
                    return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing id']);
                }

                // delete from resource or manpower collection depending where it exists
                $res = $this->model->getResourceById($id);
                if ($res) {
                    $this->deleteResourceItems($id);
                    $this->model->deleteResource($id);
                } else {
                    $this->manpowerModel->deleteManpower($id);
                }

                return $this->response->setJSON(['success' => true, 'message' => 'Resource deleted']);
            }

            if ($id) {
                $res = $this->model->getResourceById($id);
                if ($res) {
                    $this->deleteResourceItems($id);
                    $this->model->deleteResource($id);
                } else {
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

            return view('resources/resources_show', [
                'resource' => $resource,
                'items' => $isManpower ? [] : $this->model->getResourceItems($id),
                'userName' => session()->get('userName') ?? 'User',
                'role' => session()->get('role') ?? 'User'
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

            // Ensure derived fields (like name from manpower_details) are present
            $resource = $this->normalizeResourceForView($resource);

            // — Read unit-level filters —
            $filterBrand     = trim((string) ($this->request->getGet('brand')     ?? ''));
            $filterModelNo   = trim((string) ($this->request->getGet('model_no')  ?? ''));
            $filterCondition = trim((string) ($this->request->getGet('condition') ?? ''));
            $filterStatus    = trim((string) ($this->request->getGet('status')    ?? ''));
            $filterLocType   = trim((string) ($this->request->getGet('loc_type')  ?? ''));

            // — Apply filters —
            if ($filterBrand !== '') {
                $items = array_values(array_filter($items, fn($i) =>
                    str_contains(strtolower((string) ($i['brand'] ?? '')), strtolower($filterBrand))));
            }
            if ($filterModelNo !== '') {
                $items = array_values(array_filter($items, fn($i) =>
                    str_contains(strtolower((string) ($i['model_number'] ?? '')), strtolower($filterModelNo))));
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

            return view('resources/resource_items', [
                'resource'      => $resource,
                'items'         => $items,
                'activeFilters' => [
                    'brand'     => $filterBrand,
                    'model_no'  => $filterModelNo,
                    'condition' => $filterCondition,
                    'status'    => $filterStatus,
                    'loc_type'  => $filterLocType,
                ],
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
        try {
            if (!$resourceId) {
                return redirect()->back()->with('error', 'Missing resource id');
            }

            $itemData = [
                'unit_id' => $this->request->getPost('unit_id'),
                'serial_number' => $this->request->getPost('serial_number'),
                'condition' => $this->request->getPost('condition'),
                'status' => $this->request->getPost('status'),
                'assigned_to' => $this->request->getPost('assigned_to')
            ];

            $this->model->createResourceItem($resourceId, $itemData);

            return redirect()->to('resources/viewItems/' . $resourceId)->with('success', 'Item added successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error adding item: ' . $e->getMessage());
        }
    }

    private function buildResourcePayload(): array
    {
        $category = (string) $this->request->getPost('category');
        $items = $this->normalizeItems($this->request->getPost('items') ?? []);

        $data = [
            'name' => trim((string) $this->request->getPost('name')),
            'category' => $category,
            'location' => [
                'address' => trim((string) $this->request->getPost('location_address')),
                'type' => trim((string) $this->request->getPost('location_type')),
            ],
        ];

        if ($category === 'Manpower') {
            $firstName = trim((string) $this->request->getPost('first_name'));
            $middleName = trim((string) $this->request->getPost('middle_name'));
            $lastName = trim((string) $this->request->getPost('last_name'));
            $fullName = trim($firstName . ' ' . $lastName);
            $manpowerId = trim((string) $this->request->getPost('manpower_id'));

            if ($manpowerId === '') {
                $manpowerId = 'MP-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            }

            if ($fullName !== '') {
                $data['name'] = $fullName;
            }

            // Flattened manpower schema (new collection)
            $data['employee_id'] = $manpowerId;
            $data['first_name'] = $firstName;
            if ($middleName !== '') $data['middle_name'] = $middleName;
            $data['last_name'] = $lastName;
            $data['status'] = trim((string) ($this->request->getPost('status') ?: $this->request->getPost('manpower_status')));
            $data['role'] = trim((string) $this->request->getPost('manpower_role'));
            $data['specialization'] = trim((string) $this->request->getPost('specialization'));
            $data['contact_number'] = trim((string) $this->request->getPost('contact_number'));
            $data['assigned_project_id'] = trim((string) $this->request->getPost('assigned_project_id')) ?: null;
            $data['next_availability'] = trim((string) $this->request->getPost('next_availability')) ?: null;
            $rawSkills = $this->request->getPost('skills') ?? [];
            $data['skills'] = array_values(array_filter(array_map('trim', is_array($rawSkills) ? $rawSkills : [])));

            // For manpower, if current site/deployment provided, use it as top-level location
            $site = trim((string) $this->request->getPost('current_site_deployment'));
            // If user selected a project but didn't provide a site string, fetch project address
            if (($site === '' || $site === null) && !empty($data['assigned_project_id'])) {
                try {
                    $projSnap = $this->model->getFirestoreInstance()->getDb()->collection('project')->document($data['assigned_project_id'])->snapshot();
                    if ($projSnap->exists()) {
                        $projData = $projSnap->data();
                        $site = trim((string)($projData['location']['address'] ?? ''));
                    }
                } catch (\Exception $e) {
                    // ignore and fall back
                }
            }

            if ($site !== '') {
                $data['location'] = [
                    'address' => $site,
                    'type' => trim((string) $this->request->getPost('location_type')) ?: 'Project Site',
                ];
            }
        }

        if (in_array($category, ['Equipment', 'Tools'], true)) {
            $addSpecificUnitsNow = (bool) $this->request->getPost('add_specific_units_now');
            $modelCode = trim((string) $this->request->getPost('model_code'));
            
            // For Equipment/Tools, use spec fields directly
            $data['model_code'] = $modelCode;
            $data['model_number'] = trim((string) $this->request->getPost('model_number'));
            $data['total_quantity'] = (int) ($this->request->getPost('total_quantity') ?: 0);
            $data['available_quantity'] = (int) ($this->request->getPost('available_quantity') ?: 0);
            $data['condition_summary'] = $this->buildConditionSummary($items);
            $data['running_serial_count'] = (int) ($this->request->getPost('running_serial_count') ?: 0);
            
            // Keep asset_details for backward compatibility with existing views/forms
            $data['asset_details'] = [
                'model_number' => $data['model_number'],
                'total_quantity' => $data['total_quantity'],
                'available_quantity' => $data['available_quantity'],
                'condition_summary' => $data['condition_summary'],
            ];
            $data['items'] = $items;
        }

        // Optional geopoint support (latitude/longitude) for map integration
        $lat = $this->request->getPost('location_geopoint_lat');
        $lng = $this->request->getPost('location_geopoint_lng');
        if ($lat !== null && $lng !== null && is_numeric($lat) && is_numeric($lng)) {
            $data['location']['geopoint'] = ['lat' => (float) $lat, 'lng' => (float) $lng];
        }

        return $data;
    }

    private function normalizeItems(array $rawItems): array
    {
        $items = [];

        foreach ($rawItems as $item) {
            // New spec fields
            $serialNumber = trim((string) ($item['serial_number'] ?? ''));
            $brand        = trim((string) ($item['brand'] ?? ''));
            $modelNumber  = trim((string) ($item['model_number'] ?? ''));
            $condition = trim((string) ($item['condition'] ?? ''));
            $status = trim((string) ($item['status'] ?? 'In-Storage'));
            $address = trim((string) ($item['address'] ?? ''));
            $locationType = trim((string) ($item['location_type'] ?? ''));
            $assignedProjectId = trim((string) ($item['assigned_project_id'] ?? '')) ?: null;
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
            $itemData = [
                'id'           => trim((string) ($item['id'] ?? '')) ?: bin2hex(random_bytes(8)),
                'serial_number' => $serialNumber,
                'brand'        => $brand,
                'model_number' => $modelNumber,
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
        $items = $this->normalizeItems($rawItems);
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
            $itemId = $item['id'];

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

        // Dual-write: persist a simplified items array on the parent document for compatibility
        $docRef = $this->model->getFirestoreInstance()->getDb()
            ->collection('resource')
            ->document($resourceId);

        try {
            // Ensure parent document items use Firestore Timestamp objects for timestamps
            foreach ($items as &$pi) {
                if (isset($pi['created_at']) && is_numeric($pi['created_at'])) {
                    $dt = new \DateTime();
                    $dt->setTimestamp((int) $pi['created_at']);
                    $pi['created_at'] = new \Google\Cloud\Core\Timestamp($dt);
                } elseif (empty($pi['created_at'])) {
                    $pi['created_at'] = new \Google\Cloud\Core\Timestamp(new \DateTime());
                }

                $pi['updated_at'] = new \Google\Cloud\Core\Timestamp(new \DateTime());
            }

            unset($pi);

            $docRef->set(['items' => $items], ['merge' => true]);
        } catch (\Exception $e) {
            // swallow - best-effort write
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

    private function normalizeResourceForView(array $resource): array
    {
        $resource['location'] = is_array($resource['location'] ?? null) ? $resource['location'] : ['address' => '', 'type' => ''];
        $resource['created_at_value'] = $this->formatDateValue($resource['created_at'] ?? '');
        $resource['updated_at_value'] = $this->formatDateValue($resource['updated_at'] ?? '');
        $resource['next_availability_value'] = $this->normalizeDateValue($resource['next_availability'] ?? '');
        $resource['category_label'] = (string) ($resource['category'] ?? '');
        $resource['asset_details'] = is_array($resource['asset_details'] ?? null) ? $resource['asset_details'] : [];
        $resource['manpower_details'] = is_array($resource['manpower_details'] ?? null) ? $resource['manpower_details'] : [];
        $resource['total_quantity_display'] = (int) ($resource['asset_details']['total_quantity'] ?? 0);
        $resource['available_units_display'] = (int) ($resource['asset_details']['available_quantity'] ?? 0);

        // Ensure model_code is populated for Equipment/Tools
        if (empty($resource['model_code']) && in_array($resource['category'] ?? '', ['Equipment', 'Tools'], true)) {
            $resource['model_code'] = (string) ($resource['asset_details']['model_number'] ?? '');
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
                'contact_number' => $resource['contact_number'] ?? ($resource['manpower_details']['contact_number'] ?? ''),
                'assigned_project_id' => $resource['assigned_project_id'] ?? ($resource['manpower_details']['assigned_project_id'] ?? null),
                'skills' => is_array($resource['skills'] ?? null) ? $resource['skills'] : ($resource['manpower_details']['skills'] ?? []),
                'current_site_deployment' => '',
            ];
            // Prefer showing project name when assigned_project_id exists
            $assignedPid = $resource['assigned_project_id'] ?? ($resource['manpower_details']['assigned_project_id'] ?? null);
            if (!empty($assignedPid)) {
                try {
                    $projSnap = $this->model->getFirestoreInstance()->getDb()->collection('project')->document($assignedPid)->snapshot();
                    if ($projSnap->exists()) {
                        $projData = $projSnap->data();
                        $resource['manpower_details']['current_site_deployment'] = trim((string)($projData['project_name'] ?? $projData['location']['address'] ?? ''));
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