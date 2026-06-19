<?php

namespace App\Controllers;

use App\Models\Model_Manpower;
use App\Models\Model_Resources;

class Manpower extends BaseController
{
    protected $manpowerModel;
    protected $resourceModel;

    public function __construct()
    {
        $this->manpowerModel = new Model_Manpower();
        $this->resourceModel = new Model_Resources();
    }

    public function create()
    {
        return view('resources/manpower_form', [
            'pageTitle' => 'Add Manpower',
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
            'projects' => $this->getProjectOptions(),
            'projectSites' => $this->getProjectSiteOptions(),
            'userName' => session()->get('userName') ?? 'User',
            'role' => session()->get('role') ?? 'User'
        ]);
    }

    public function store()
    {
        if (! $this->validate('manpower')) {
            return redirect()->back()->withInput()->with('error', implode('; ', $this->validator->getErrors()));
        }

        try {
            $data = $this->buildPayloadFromPost();
            $id = $this->manpowerModel->createManpower($data);
            return redirect()->to('resources')->with('success', 'Manpower created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Error creating manpower: ' . $e->getMessage());
        }
    }

    public function edit($id = null)
    {
        try {
            if (!$id) return redirect()->to('resources')->with('error', 'Missing id');

            $doc = $this->manpowerModel->getManpowerById($id);
            if (!$doc) return redirect()->to('resources')->with('error', 'Manpower not found');

            // Map flattened fields to view-compatible structure
            $resource = [
                'id' => $id,
                'category' => 'Manpower',
                'status' => $doc['status'] ?? 'Available',
                'location' => is_array($doc['location'] ?? null) ? $doc['location'] : ['address' => '', 'type' => ''],
                'manpower_details' => [
                        'first_name' => $doc['first_name'] ?? '',
                        'middle_name' => $doc['middle_name'] ?? '',
                        'last_name' => $doc['last_name'] ?? '',
                        'manpower_id' => $doc['employee_id'] ?? ($doc['manpower_details']['manpower_id'] ?? ''),
                        'status' => $doc['status'] ?? '',
                        'role' => $doc['role'] ?? '',
                        'specialization' => $doc['specialization'] ?? '',
                        'contact_number' => $doc['contact_number'] ?? '',
                        'assigned_project_id' => $doc['assigned_project_id'] ?? null,
                        'skills' => is_array($doc['skills'] ?? null) ? $doc['skills'] : [],
                        'current_site_deployment' => '',
                    ],
                'created_at' => $doc['created_at'] ?? null,
                'updated_at' => $doc['updated_at'] ?? null,
            ];
            // Prefer project name when assigned_project_id is present
            $assignedPid = $doc['assigned_project_id'] ?? null;
            if (!empty($assignedPid)) {
                try {
                    $projSnap = $this->resourceModel->getFirestoreInstance()->getDb()->collection('project')->document($assignedPid)->snapshot();
                    if ($projSnap->exists()) {
                        $projData = $projSnap->data();
                        $resource['manpower_details']['current_site_deployment'] = trim((string)($projData['project_name'] ?? $projData['location']['address'] ?? ''));
                    } else {
                        $resource['manpower_details']['current_site_deployment'] = $doc['location']['address'] ?? '';
                    }
                } catch (\Exception $e) {
                    $resource['manpower_details']['current_site_deployment'] = $doc['location']['address'] ?? '';
                }
            } else {
                $resource['manpower_details']['current_site_deployment'] = $doc['location']['address'] ?? '';
            }

            return view('resources/manpower_form', [
                'pageTitle' => 'Edit Manpower',
                'resource' => $resource,
                'projects' => $this->getProjectOptions(),
                'projectSites' => $this->getProjectSiteOptions(),
                'userName' => session()->get('userName') ?? 'User',
                'role' => session()->get('role') ?? 'User'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error loading manpower: ' . $e->getMessage());
        }
    }

    public function update($id = null)
    {
        if (! $this->validate('manpower')) {
            return redirect()->back()->withInput()->with('error', implode('; ', $this->validator->getErrors()));
        }

        try {
            if (!$id) return redirect()->to('resources')->with('error', 'Missing id');
            $existing = $this->manpowerModel->getManpowerById($id);
            if (!$existing) return redirect()->to('resources')->with('error', 'Manpower not found');

            $data = $this->buildPayloadFromPost();
            $data['created_at'] = $existing['created_at'] ?? (new \Google\Cloud\Core\Timestamp(new \DateTime()));
            $data['updated_at'] = new \Google\Cloud\Core\Timestamp(new \DateTime());

            $this->manpowerModel->updateManpower($id, $data);

            return redirect()->to('resources')->with('success', 'Manpower updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Error updating manpower: ' . $e->getMessage());
        }
    }

    public function delete($id = null)
    {
        try {
            if (strtolower($this->request->getMethod()) === 'post') {
                $id = $this->request->getPost('id') ?? $id;
                if (!$id) return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing id']);
                $this->manpowerModel->deleteManpower($id);
                return $this->response->setJSON(['success' => true, 'message' => 'Manpower deleted']);
            }

            if ($id) {
                $this->manpowerModel->deleteManpower($id);
                return redirect()->to('resources')->with('success', 'Manpower deleted successfully!');
            }

            return redirect()->to('resources')->with('error', 'Invalid delete request');
        } catch (\Exception $e) {
            if (strtolower($this->request->getMethod()) === 'post') {
                return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Error deleting manpower']);
            }
            return redirect()->back()->with('error', 'Error deleting manpower: ' . $e->getMessage());
        }
    }

    private function buildPayloadFromPost(): array
    {
        $firstName = trim((string) $this->request->getPost('first_name'));
        $middleName = trim((string) $this->request->getPost('middle_name'));
        $lastName = trim((string) $this->request->getPost('last_name'));
        $fullName = trim($firstName . ' ' . $lastName);
        $manpowerId = trim((string) $this->request->getPost('manpower_id'));

        if ($manpowerId === '') {
            try {
                $manpowerId = 'MP-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            } catch (\Exception $e) {
                $manpowerId = 'MP-UNKNOWN';
            }
        }

        $data = [
            'name' => $fullName !== '' ? $fullName : trim((string) $this->request->getPost('name')),
            'category' => 'Manpower',
            'status' => trim((string) ($this->request->getPost('status') ?: 'Available')),
            'next_availability' => trim((string) $this->request->getPost('next_availability')) ?: null,
            'employee_id' => $manpowerId,
            'first_name' => $firstName,
            'middle_name' => $middleName !== '' ? $middleName : null,
            'last_name' => $lastName,
            'role' => trim((string) $this->request->getPost('manpower_role')),
            'specialization' => trim((string) $this->request->getPost('specialization')),
            'contact_number' => trim((string) $this->request->getPost('contact_number')),
            'assigned_project_id' => trim((string) $this->request->getPost('assigned_project_id')) ?: null,
        ];

        $rawSkills = $this->request->getPost('skills') ?? [];
        $data['skills'] = array_values(array_filter(array_map('trim', is_array($rawSkills) ? $rawSkills : [])));

        // location: if assigned_project_id present and no explicit site provided, try to resolve
        $site = trim((string) $this->request->getPost('current_site_deployment'));
        if (($site === '' || $site === null) && !empty($data['assigned_project_id'])) {
            try {
                $projSnap = $this->resourceModel->getFirestoreInstance()->getDb()->collection('project')->document($data['assigned_project_id'])->snapshot();
                if ($projSnap->exists()) {
                    $projData = $projSnap->data();
                    $site = trim((string)($projData['location']['address'] ?? ''));
                }
            } catch (\Exception $e) {
                // ignore
            }
        }

        if ($site !== '') {
            $data['location'] = [
                'address' => $site,
                'type' => trim((string) $this->request->getPost('location_type')) ?: 'Project Site',
            ];
        }

        return $data;
    }

    private function getProjectSiteOptions(): array
    {
        $sites = [];
        $seen = [];

        foreach ($this->resourceModel->getFirestoreInstance()->getDb()->collection('project')->documents() as $doc) {
            if (!$doc->exists()) continue;
            $data = $doc->data();
            $address = trim((string) ($data['location']['address'] ?? ''));
            if ($address === '' || isset($seen[$address])) continue;
            $seen[$address] = true;
            $projectName = trim((string) ($data['project_name'] ?? 'Project'));
            $sites[] = [
                'value' => $address,
                'label' => $projectName !== '' ? $projectName . ' - ' . $address : $address,
            ];
        }

        return $sites;
    }

    private function getProjectOptions(): array
    {
        $projects = [];
        foreach ($this->resourceModel->getFirestoreInstance()->getDb()->collection('project')->documents() as $doc) {
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
}
