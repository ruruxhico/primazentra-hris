<?php

namespace App\Controllers;

use App\Libraries\FirestoreService;
use App\Models\Model_Users;

class Projects extends BaseController
{
    protected FirestoreService $firestore;
    protected Model_Users $usersModel;

    public function __construct() {
        $this->firestore = new FirestoreService();
        $this->usersModel = new Model_Users();
    }

    public function index()
    {
        try {
            // Fetch projects from Firestore
            $projectsCollection = $this->firestore->getDb()->collection('projects');
            $projects = [];
            
            foreach ($projectsCollection->documents() as $doc) {
                if ($doc->exists()) {
                    $projectData = $doc->data();
                    $projectData['id'] = $doc->id();
                    $projects[] = $projectData;
                }
            }

            $data = [
                'pageTitle' => 'Projects',
                'activePage' => 'projects',
                'projects' => $projects,
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
                'userName' => session()->get('userName') ?? 'User',
                'role' => session()->get('role') ?? 'Staff',
                'error' => 'Unable to fetch projects'
            ];

            return view('projects/projects_view', $data);
        }
    }

    public function create()
    {
        if ($this->request->getMethod() === 'post') {
            try {
                $data = [
                    'project_name' => $this->request->getPost('project_name'),
                    'project_type' => $this->request->getPost('project_type'),
                    'location' => $this->request->getPost('location'),
                    'status' => $this->request->getPost('status') ?? 'Planned',
                    'project_manager_id' => $this->request->getPost('project_manager_id'),
                    'site_engineer_id' => $this->request->getPost('site_engineer_id'),
                    'start_date' => $this->request->getPost('start_date'),
                    'end_date' => $this->request->getPost('end_date'),
                    'budget' => (int)$this->request->getPost('budget') ?? 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                $collection = $this->firestore->getDb()->collection('projects');
                $collection->add($data);

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
            'projectManagers' => $this->usersModel->getUsersByRole('PM'),
            'siteEngineers' => $this->usersModel->getUsersByRole('SE'),
            'userName' => session()->get('userName') ?? 'User',
            'role' => session()->get('role') ?? 'Staff'
        ]);
    }

    public function update($id)
    {
        try {
            if ($this->request->getMethod() === 'post') {
                $data = [
                    'project_name' => $this->request->getPost('project_name'),
                    'project_type' => $this->request->getPost('project_type'),
                    'location' => $this->request->getPost('location'),
                    'status' => $this->request->getPost('status'),
                    'project_manager_id' => $this->request->getPost('project_manager_id'),
                    'site_engineer_id' => $this->request->getPost('site_engineer_id'),
                    'start_date' => $this->request->getPost('start_date'),
                    'end_date' => $this->request->getPost('end_date'),
                    'budget' => (int)$this->request->getPost('budget') ?? 0,
                    'updated_at' => now()
                ];

                $this->firestore->getDb()->collection('projects')->document($id)->update($data);

                return redirect()->to('projects')->with('success', 'Project updated successfully!');
            }

            $project = $this->firestore->getDb()->collection('projects')->document($id)->snapshot();
            
            if (!$project->exists()) {
                return redirect()->to('projects')->with('error', 'Project not found');
            }

            return view('projects/projects_form', [
                'pageTitle' => 'Edit Project',
                'activePage' => 'projects',
                'project' => $project->data(),
                'projectId' => $id,
                'projectManagers' => $this->usersModel->getUsersByRole('PM'),
                'siteEngineers' => $this->usersModel->getUsersByRole('SE'),
                'userName' => session()->get('userName') ?? 'User',
                'role' => session()->get('role') ?? 'Staff'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Projects::update - ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update project');
        }
    }

    public function delete($id)
    {
        try {
            $this->firestore->getDb()->collection('projects')->document($id)->delete();
            return redirect()->to('projects')->with('success', 'Project deleted successfully!');
        } catch (\Exception $e) {
            log_message('error', 'Projects::delete - ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete project');
        }
    }
}
