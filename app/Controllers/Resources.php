<?php

namespace App\Controllers;

use App\Models\Model_Resources;

class Resources extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new Model_Resources();
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

                if ($category === 'Manpower') {
                    $manpower[] = $normalized;
                } elseif ($category === 'Tools') {
                    $tools[] = $normalized;
                } else {
                    $equipment[] = $normalized;
                }
            }

            return view('resources/resources_view', [
                'equipment' => $equipment,
                'tools' => $tools,
                'manpower' => $manpower,
                'userName' => session()->get('userName') ?? 'User',
                'role' => session()->get('role') ?? 'User'
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
            return view('resources/manpower_form', [
                'resource' => [
                    'category' => 'Manpower',
                    'status' => 'Available',
                    'next_availability' => '',
                    'location' => [
                        'address' => '',
                        'type' => ''
                    ],
                    'manpower_details' => []
                ],
                'userName' => session()->get('userName') ?? 'User',
                'role' => session()->get('role') ?? 'User'
            ]);
        }

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

            $resourceId = $this->model->createResource($data);

            if (in_array($data['category'] ?? '', ['Equipment', 'Tools'], true)) {
                $this->syncResourceItems($resourceId, $this->request->getPost('items') ?? []);
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

            if (!$resource) {
                return redirect()->back()->with('error', 'Resource not found');
            }

            $resource = $this->normalizeResourceForView($resource);
            $category = (string) ($resource['category'] ?? '');
            $view = $category === 'Manpower'
                ? 'resources/manpower_form'
                : ($category === 'Equipment'
                    ? 'resources/equipments_form'
                    : ($category === 'Tools' ? 'resources/tools_form' : 'resources/resources_form'));

            return view($view, [
                'resource' => $resource,
                'items' => $this->model->getResourceItems($id),
                'projectSites' => $this->getProjectSiteOptions(),
                'warehouseLocations' => $this->getWarehouseLocationOptions(),
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
            $existing = $this->model->getResourceById($id);
            if (!$existing) {
                return redirect()->to('resources')->with('error', 'Resource not found');
            }

            $data = $this->buildResourcePayload();
            $data['created_at'] = $existing['created_at'] ?? time();
            $data['updated_at'] = time();

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

                $this->deleteResourceItems($id);
                $this->model->deleteResource($id);

                return $this->response->setJSON(['success' => true, 'message' => 'Resource deleted']);
            }

            if ($id) {
                $this->deleteResourceItems($id);
                $this->model->deleteResource($id);
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

            if (!$resource) {
                return redirect()->to('resources')->with('error', 'Resource not found');
            }

            $resource = $this->normalizeResourceForView($resource);

            return view('resources/resources_show', [
                'resource' => $resource,
                'items' => $this->model->getResourceItems($id),
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

            return view('resources/resource_items', [
                'resource' => $resource,
                'items' => $items,
                'userName' => session()->get('userName') ?? 'User',
                'role' => session()->get('role') ?? 'User'
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
            'status' => trim((string) ($this->request->getPost('status') ?: 'Available')),
            'next_availability' => trim((string) $this->request->getPost('next_availability')),
            'location' => [
                'address' => trim((string) $this->request->getPost('location_address')),
                'type' => trim((string) $this->request->getPost('location_type')),
            ],
        ];

        if ($category === 'Manpower') {
            $firstName = trim((string) $this->request->getPost('first_name'));
            $lastName = trim((string) $this->request->getPost('last_name'));
            $fullName = trim($firstName . ' ' . $lastName);
            $manpowerId = trim((string) $this->request->getPost('manpower_id'));

            if ($manpowerId === '') {
                $manpowerId = 'MP-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            }

            if ($fullName !== '') {
                $data['name'] = $fullName;
            }

            $data['manpower_details'] = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'manpower_id' => $manpowerId,
                'status' => trim((string) ($this->request->getPost('status') ?: $this->request->getPost('manpower_status'))),
                'role' => trim((string) $this->request->getPost('manpower_role')),
                'specialization' => trim((string) $this->request->getPost('specialization')),
                'current_site_deployment' => trim((string) $this->request->getPost('current_site_deployment')),
                'contact_number' => trim((string) $this->request->getPost('contact_number')),
                'assigned_project_id' => trim((string) $this->request->getPost('current_site_deployment')),
            ];
        }

        if (in_array($category, ['Equipment', 'Tools'], true)) {
            $addSpecificUnitsNow = (bool) $this->request->getPost('add_specific_units_now');
            $data['asset_details'] = [
                'model_number' => trim((string) $this->request->getPost('model_number')),
                'total_quantity' => (int) ($this->request->getPost('total_quantity') ?: 0),
                'available_quantity' => (int) ($this->request->getPost('available_quantity') ?: 0),
                'status' => trim((string) $this->request->getPost('asset_status')),
                'assigned_project_id' => trim((string) $this->request->getPost('asset_assigned_project_id')),
                'condition_summary' => $this->buildConditionSummary($items),
                'add_specific_units_now' => $addSpecificUnitsNow,
            ];
            $data['items'] = $items;
        }

        return $data;
    }

    private function normalizeItems(array $rawItems): array
    {
        $items = [];

        foreach ($rawItems as $item) {
            $unitId = trim((string) ($item['unit_id'] ?? ''));
            $serialNumber = trim((string) ($item['serial_number'] ?? ''));
            $condition = trim((string) ($item['condition'] ?? ''));
            $status = trim((string) ($item['status'] ?? ''));
            $address = trim((string) ($item['address'] ?? ''));
            $locationType = trim((string) ($item['location_type'] ?? ''));

            if ($unitId === '' && $serialNumber === '' && $condition === '' && $status === '') {
                continue;
            }

            $items[] = [
                'id' => trim((string) ($item['id'] ?? '')) ?: bin2hex(random_bytes(8)),
                'unit_id' => $unitId,
                'serial_number' => $serialNumber,
                'condition' => $condition,
                'status' => $status,
                'location' => [
                    'address' => $address,
                    'type' => $locationType,
                ],
                'assigned_to' => trim((string) ($item['assigned_to'] ?? '')),
            ];
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

        foreach ($itemsRef->documents() as $existingItem) {
            if ($existingItem->exists()) {
                $itemsRef->document($existingItem->id())->delete();
            }
        }

        foreach ($items as $item) {
            $itemId = $item['id'];
            $item['created_at'] = $item['created_at'] ?? time();
            $item['updated_at'] = time();
            $itemsRef->document($itemId)->set($item);
        }

        // Dual-write: persist a simplified items array on the parent document for compatibility
        $docRef = $this->model->getFirestoreInstance()->getDb()
            ->collection('resource')
            ->document($resourceId);

        try {
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
        // If top-level status is missing, inherit from asset_details.status when available
        if (empty($resource['status']) && !empty($resource['asset_details']['status'])) {
            $resource['status'] = (string) $resource['asset_details']['status'];
        }

        // Populate name from manpower details or from model_number when missing
        if (trim((string) ($resource['name'] ?? '')) === '') {
            $fullName = trim((string) ($resource['manpower_details']['first_name'] ?? '') . ' ' . (string) ($resource['manpower_details']['last_name'] ?? ''));
            if ($fullName !== '') {
                $resource['name'] = $fullName;
            } elseif (!empty($resource['asset_details']['model_number'])) {
                $resource['name'] = (string) $resource['asset_details']['model_number'];
            }
        }

        // Ensure condition summary exists; if not, try to derive it from items subcollection or document items
        $conditionSummary = $resource['asset_details']['condition_summary'] ?? [];
        if (!is_array($conditionSummary) || $conditionSummary === []) {
            $derivedItems = [];
            if (!empty($resource['id'])) {
                try {
                    $derivedItems = $this->model->getResourceItems($resource['id']);
                } catch (\Exception $e) {
                    $derivedItems = [];
                }
            }

            if (empty($derivedItems) && !empty($resource['items']) && is_array($resource['items'])) {
                $derivedItems = $resource['items'];
            }

            $conditionSummary = $this->buildConditionSummary($derivedItems);
            $resource['asset_details']['condition_summary'] = $conditionSummary;
        }

        $resource['condition_label'] = $this->getPrimaryConditionLabel($resource['asset_details']['condition_summary'] ?? []);

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