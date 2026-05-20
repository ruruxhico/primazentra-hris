<?php

namespace App\Controllers;

use App\Models\Model_Projects;
use App\Models\Model_Resources;
use App\Models\Model_Users;
use App\Models\Model_Manpower;

class Dashboard extends BaseController
{
    protected $projectModel;
    protected $resourceModel;
    protected $userModel;
    protected $manpowerModel;

    public function __construct()
    {
        $this->projectModel = new Model_Projects();
        $this->resourceModel = new Model_Resources();
        $this->userModel = new Model_Users();
        $this->manpowerModel = new Model_Manpower();
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

            // Get manpower separately (manpower collection)
            $allManpower = [];
            try {
                $allManpower = $this->manpowerModel->getAllManpower();
            } catch (\Exception $e) {
                $allManpower = [];
            }

            // Filter available manpower
            $availableManpower = array_values(array_filter($allManpower, function ($m) {
                return (isset($m['status']) && $m['status'] === 'Available');
            }));

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
                'availableManpower' => $availableManpower,
                'activeProjects' => $activeProjects,
            ];

            return view('dashboard/index', $data);
        } catch (\Exception $e) {
            log_message('error', 'Dashboard::index - ' . $e->getMessage());

            return view('dashboard/index', [
                'userName' => session()->get('userName') ?? 'User',
                'role' => session()->get('role') ?? 'User',
                'projectCount' => 0,
                'resourceCount' => 0,
                'staffCount' => 0,
                'blockers' => [],
                'tools' => [],
                'availableResources' => [],
                'deployedResources' => [],
                'availableManpower' => [],
                'activeProjects' => [],
                'errorMessage' => 'Unable to load dashboard right now. Please try again later.',
            ]);
        }
    }
}
