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
            $allEquipment = $this->model->getEquipment();
            $allTools = $this->model->getTools();
            $manpower = $this->model->getManpower();
            
            // Combine equipment and tools for the view
            $equipment = array_merge($allEquipment, $allTools);

            return view('resources/resources_view', [
                'equipment' => $equipment,
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
        return view('resources/resources_form', [
            'resource' => null,
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
            $data = [
                'name' => $this->request->getPost('name'),
                'category' => $this->request->getPost('category'),
                'status' => $this->request->getPost('status'),
                'location' => [
                    'address' => $this->request->getPost('location_address'),
                    'type' => $this->request->getPost('location_type'),
                    'geopoint' => $this->request->getPost('geopoint')
                ]
            ];

            // Handle conditional fields based on category
            $category = $this->request->getPost('category');

            if ($category === 'Manpower') {
                $data['manpower_details'] = [
                    'manpower_id' => $this->request->getPost('manpower_id'),
                    'role' => $this->request->getPost('manpower_role'),
                    'specialization' => $this->request->getPost('specialization'),
                    'contact_number' => $this->request->getPost('contact_number'),
                    'assigned_project_id' => $this->request->getPost('assigned_project_id')
                ];
            } elseif (in_array($category, ['Equipment', 'Tools'])) {
                $data['asset_details'] = [
                    'total_quantity' => $this->request->getPost('total_quantity'),
                    'available_quantity' => $this->request->getPost('available_quantity'),
                    'condition_summary' => json_decode($this->request->getPost('condition_summary'), true),
                    'model_number' => $this->request->getPost('model_number')
                ];
            }

            $resourceId = $this->model->createResource($data);

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

            return view('resources/resources_form', [
                'resource' => $resource,
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
            $data = [
                'name' => $this->request->getPost('name'),
                'category' => $this->request->getPost('category'),
                'status' => $this->request->getPost('status'),
                'location' => [
                    'address' => $this->request->getPost('location_address'),
                    'type' => $this->request->getPost('location_type'),
                    'geopoint' => $this->request->getPost('geopoint')
                ]
            ];

            $category = $this->request->getPost('category');

            if ($category === 'Manpower') {
                $data['manpower_details'] = [
                    'manpower_id' => $this->request->getPost('manpower_id'),
                    'role' => $this->request->getPost('manpower_role'),
                    'specialization' => $this->request->getPost('specialization'),
                    'contact_number' => $this->request->getPost('contact_number'),
                    'assigned_project_id' => $this->request->getPost('assigned_project_id')
                ];
            } elseif (in_array($category, ['Equipment', 'Tools'])) {
                $data['asset_details'] = [
                    'total_quantity' => $this->request->getPost('total_quantity'),
                    'available_quantity' => $this->request->getPost('available_quantity'),
                    'condition_summary' => json_decode($this->request->getPost('condition_summary'), true),
                    'model_number' => $this->request->getPost('model_number')
                ];
            }

            $this->model->updateResource($id, $data);

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
            $this->model->deleteResource($id);
            return redirect()->to('resources')->with('success', 'Resource deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting resource: ' . $e->getMessage());
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

            if (!$resource) {
                return redirect()->back()->with('error', 'Resource not found');
            }

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
}