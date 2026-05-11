<?php

namespace App\Controllers;

use App\Models\Model_Projects;
use App\Models\Model_Resources;
use App\Models\Model_Users;

class Dashboard extends BaseController
{
    protected $projectModel;
    protected $resourceModel;
    protected $userModel;

    public function __construct()
    {
        $this->projectModel = new Model_Projects();
        $this->resourceModel = new Model_Resources();
        $this->userModel = new Model_Users();
    }

    /**
     * Dashboard main page
     */
    public function index()
    {
        try {
            // Check if user is logged in
            if (!session()->get('isLoggedIn')) {
                return redirect()->to('auth/login');
            }

            // Get user info from session
            $userName = session()->get('userName') ?? 'User';
            $role = session()->get('role') ?? 'User';

            // Get project data
            $activeProjects = $this->projectModel->getActiveProjects();
            $projectCount = count($activeProjects);
            $blockers = $this->projectModel->getProjectsWithBlockers();

            // Get resource data
            $totalResources = $this->resourceModel->countResources();
            $availableResources = $this->resourceModel->getAvailableResources();
            $deployedResources = $this->resourceModel->getDeployedResources();
            $tools = $this->resourceModel->getTools();

            // Get staff count
            $staffCount = $this->userModel->countStaff();

            // Prepare data for view
            $data = [
                'userName' => $userName,
                'role' => $role,
                'projectCount' => $projectCount,
                'resourceCount' => $totalResources,
                'staffCount' => $staffCount,
                'blockers' => $blockers,
                'tools' => $tools,
                'availableResources' => $availableResources,
                'deployedResources' => $deployedResources,
                'activeProjects' => $activeProjects,
            ];

            return view('dashboard/index', $data);
        } catch (\Exception $e) {
            log_message('error', 'Dashboard::index - ' . $e->getMessage());
            return view('errors/html', [
                'message' => 'Unable to load dashboard: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get project details
     */
    public function getProjectDetails($projectId)
    {
        try {
            $project = $this->projectModel->getProjectById($projectId);
            
            if (!$project) {
                return json_encode(['error' => 'Project not found']);
            }

            return json_encode($project);
        } catch (\Exception $e) {
            log_message('error', 'Dashboard::getProjectDetails - ' . $e->getMessage());
            return json_encode(['error' => $e->getMessage()]);
        }
    }
}
