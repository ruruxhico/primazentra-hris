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
        $this->projectModel  = new Model_Projects();
        $this->resourceModel = new Model_Resources();
        $this->userModel     = new Model_Users();
        $this->manpowerModel = new Model_Manpower();
    }

    public function index()
    {
        try {
            if (!session()->get('isLoggedIn')) {
                return redirect()->to('auth/login');
            }

            $userName = session()->get('userName') ?? 'User';
            $role     = session()->get('role') ?? 'User';

            // ── Projects ────────────────────────────────────────────
            $rawProjects = $this->projectModel->getActiveProjects();
            $activeProjects = [];
            foreach ($rawProjects as $p) {
                $activeProjects[] = $this->enrichProject($p);
            }

            // ── Group projects by Philippine island group ────────────
            $projectsByRegion = [];
            foreach ($activeProjects as $p) {
                $projectsByRegion[$p['_region']][] = $p;
            }
            ksort($projectsByRegion);

            // ── Blockers: low progress or halted ────────────────────
            $blockers = array_values(array_filter($activeProjects, function ($p) {
                return ($p['_progress'] < 30) || (($p['status'] ?? '') === 'Halted');
            }));

            // ── Resources ───────────────────────────────────────────
            $totalResources    = $this->resourceModel->countResources();
            $availableResources = $this->resourceModel->getAvailableResources();
            $deployedResources  = $this->resourceModel->getDeployedResources();
            $availableTools     = $this->resourceModel->getAvailableResources();

            // ── Manpower ─────────────────────────────────────────────
            $allManpower = [];
            try {
                $allManpower = $this->manpowerModel->getAllManpower();
            } catch (\Throwable $e) {
                log_message('warning', 'Dashboard: manpower fetch failed - ' . $e->getMessage());
            }
            $availableManpower = array_values(array_filter($allManpower, function ($m) {
                return ($m['status'] ?? '') === 'Available';
            }));

            // ── Staff ────────────────────────────────────────────────
            $staffCount = $this->userModel->countStaff();

            $data = [
                'pageTitle'        => 'Dashboard',
                'activePage'       => 'dashboard',
                'userName'         => $userName,
                'role'             => $role,
                'projectCount'     => count($activeProjects),
                'resourceCount'    => $totalResources,
                'staffCount'       => $staffCount,
                'activeProjects'   => $activeProjects,
                'projectsByRegion' => $projectsByRegion,
                'blockers'         => $blockers,
                'availableTools'   => $availableTools,
                'availableResources'  => $availableResources,
                'deployedResources'   => $deployedResources,
                'availableManpower'   => $availableManpower,
            ];

            return view('dashboard/index', $data);

        } catch (\Exception $e) {
            log_message('error', 'Dashboard::index - ' . $e->getMessage());

            return view('dashboard/index', [
                'pageTitle'        => 'Dashboard',
                'activePage'       => 'dashboard',
                'userName'         => session()->get('userName') ?? 'User',
                'role'             => session()->get('role') ?? 'User',
                'projectCount'     => 0,
                'resourceCount'    => 0,
                'staffCount'       => 0,
                'activeProjects'   => [],
                'projectsByRegion' => [],
                'blockers'         => [],
                'availableTools'   => [],
                'availableResources'  => [],
                'deployedResources'   => [],
                'availableManpower'   => [],
                'errorMessage'     => 'Unable to load dashboard right now.',
            ]);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function enrichProject(array $p): array
    {
        $p['_progress']  = min(100, max(0, (float)($p['overall_progress'] ?? $p['completion_percent'] ?? 0)));
        $p['_start_fmt'] = $this->fmtDate($p['start_date'] ?? '');
        $p['_end_fmt']   = $this->fmtDate($p['end_date'] ?? '');
        $p['_region']    = $this->detectRegion($p);
        $p['_maps_url']  = $this->mapsUrl($p);

        // Resource pills: prefer project-embedded list, fall back to personnel
        $tools    = [];
        $manpower = [];

        if (!empty($p['resources']) && is_array($p['resources'])) {
            foreach ($p['resources'] as $res) {
                $cat  = strtolower($res['category'] ?? 'tool');
                $name = strtoupper(trim((string)($res['name'] ?? '')));
                if ($name === '') continue;
                if ($cat === 'manpower') $manpower[] = $name;
                else $tools[] = $name;
            }
        }

        if (empty($manpower)) {
            $pm = trim((string)($p['project_manager_name'] ?? ''));
            $se = trim((string)($p['site_engineer_name']   ?? ''));
            if ($pm && $pm !== 'N/A') $manpower[] = strtoupper($pm);
            if ($se && $se !== 'N/A') $manpower[] = strtoupper($se);
        }

        $p['_tools']    = $tools;
        $p['_manpower'] = $manpower;

        return $p;
    }

    private function detectRegion(array $p): string
    {
        $explicit = strtoupper(trim((string)($p['region'] ?? '')));
        if ($explicit !== '') return $explicit;

        $addr = strtolower((string)($p['location']['address'] ?? $p['location_label'] ?? ''));

        $map = [
            'LUZON'    => ['manila','quezon','caloocan','makati','taguig','pasig','marikina','laguna','cavite','batangas','pampanga','bulacan','rizal','baguio','ilocos','nueva ecija','tarlac','zambales','bataan','aurora','palawan','bicol','albay','camarines','cagayan'],
            'VISAYAS'  => ['cebu','iloilo','bacolod','tacloban','dumaguete','ormoc','leyte','samar','bohol','negros','panay','aklan','antique','capiz','biliran'],
            'MINDANAO' => ['davao','cagayan de oro','zamboanga','cotabato','butuan','iligan','general santos','bukidnon','misamis','surigao','agusan','maguindanao'],
        ];

        foreach ($map as $region => $keywords) {
            foreach ($keywords as $kw) {
                if (strpos($addr, $kw) !== false) return $region;
            }
        }

        return 'LUZON';
    }

    private function fmtDate($val): string
    {
        if (!$val) return 'N/A';
        if (is_numeric($val)) return strtoupper(date('F j, Y', (int)$val));
        $ts = strtotime((string)$val);
        return $ts ? strtoupper(date('F j, Y', $ts)) : strtoupper((string)$val);
    }

    private function mapsUrl(array $p): string
    {
        $lat  = $p['location']['geopoint']['lat'] ?? null;
        $lng  = $p['location']['geopoint']['lng'] ?? null;
        $addr = $p['location']['address'] ?? $p['project_name'] ?? '';
        if ($lat && $lng) return "https://www.google.com/maps?q={$lat},{$lng}";
        return 'https://www.google.com/maps/search/' . urlencode((string)$addr);
    }
}
