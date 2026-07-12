<?php

namespace App\Controllers;

use App\Libraries\FirestoreService;
use App\Services\AuditLogService;
use Google\Cloud\Firestore\DocumentReference;

class AuditLogs extends BaseController
{
    private AuditLogService $audit;

    public function __construct()
    {
        $this->audit = new AuditLogService();
    }

    private function guard(): ?object
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }
        if (!pz_can('view_audit_logs')) {
            return redirect()->to('/dashboard')->with('error', 'Access restricted.');
        }
        return null;
    }

    public function index()
    {
        if ($blocked = $this->guard()) return $blocked;

        $role = (string) (session()->get('role') ?? '');

        $filters = [
            'module'     => trim((string) ($this->request->getGet('module') ?? '')),
            'action'     => trim((string) ($this->request->getGet('action') ?? '')),
            'actor_role' => trim((string) ($this->request->getGet('actor_role') ?? '')),
            'status'     => trim((string) ($this->request->getGet('status') ?? '')),
            'q'          => trim((string) ($this->request->getGet('q') ?? '')),
        ];

        $dateFrom = trim((string) ($this->request->getGet('date_from') ?? ''));
        $dateTo   = trim((string) ($this->request->getGet('date_to') ?? ''));
        if ($dateFrom !== '') $filters['date_from'] = strtotime($dateFrom . ' 00:00:00');
        if ($dateTo   !== '') $filters['date_to']   = strtotime($dateTo   . ' 23:59:59');

        $pageTitle   = 'Audit Logs';
        $pageSub     = 'Full trail of create, update, delete, and access-control actions across every role.';
        $scopeNotice = null;
        $availableModules = ['project', 'manpower', 'resource', 'staff', 'request', 'report', 'auth'];

        $scanLimit = 1000;

        if ($role === 'Operations Manager') {
            // Staff records (hiring, roles, credential resets) are Admin-only —
            // an OM sees everything else so a single-admin system still has a
            // second set of eyes on activity, without exposing HR-adjacent data.
            $filters['exclude_modules'] = ['staff'];
            if ($filters['module'] === 'staff') $filters['module'] = '';
            $availableModules = array_values(array_diff($availableModules, ['staff']));
            $pageSub = 'Full trail across every role, excluding staff-record changes (Administrator only).';
        } elseif ($role === 'Project Manager') {
            $projectIds = $this->managedProjectIds();

            if (empty($projectIds)) {
                return view('audit_logs/index', [
                    'pageTitle' => 'Project Audit Trail',
                    'pageSub'   => 'You are not currently assigned as manager on any project.',
                    'userName'  => session()->get('userName') ?? 'User',
                    'role'      => $role,
                    'items'     => [],
                    'scopeNotice'      => null,
                    'availableModules' => ['project', 'report'],
                    'filters'   => $this->filtersForView($filters, $dateFrom, $dateTo),
                ]);
            }

            unset($filters['module']);
            $filters['modules']    = ['project', 'report'];
            $filters['target_ids'] = $projectIds;
            $availableModules      = ['project', 'report'];
            $scanLimit   = 3000; // narrower result set (own projects only) — safe to widen the scan
            $pageTitle   = 'Project Audit Trail';
            $pageSub     = 'History of what changed on the project(s) you manage.';
            $scopeNotice = 'Showing activity for ' . count($projectIds) . ' project(s) you manage.';
        }

        $items = $this->audit->query(array_filter($filters, fn($v) => $v !== '' && $v !== null), $scanLimit);

        return view('audit_logs/index', [
            'pageTitle' => $pageTitle,
            'pageSub'   => $pageSub,
            'userName'  => session()->get('userName') ?? 'User',
            'role'      => $role,
            'items'     => $items,
            'scopeNotice'      => $scopeNotice,
            'availableModules' => $availableModules,
            'filters'   => $this->filtersForView($filters, $dateFrom, $dateTo),
        ]);
    }

    private function filtersForView(array $filters, string $dateFrom, string $dateTo): array
    {
        return [
            'module'     => $filters['module'] ?? '',
            'action'     => $filters['action'] ?? '',
            'actor_role' => $filters['actor_role'] ?? '',
            'status'     => $filters['status'] ?? '',
            'q'          => $filters['q'] ?? '',
            'date_from'  => $dateFrom,
            'date_to'    => $dateTo,
        ];
    }

    /** Firestore doc IDs of projects where project_manager_id resolves to the current session user. */
    private function managedProjectIds(): array
    {
        $uid = (string) (session()->get('userId') ?? '');
        if ($uid === '') return [];

        $db  = FirestoreService::getInstance()->getDb();
        $ids = [];
        foreach ($db->collection('project')->documents() as $doc) {
            if (!$doc->exists()) continue;
            $d     = $doc->data();
            $pmRaw = $d['project_manager_id'] ?? null;
            $pmUid = $pmRaw instanceof DocumentReference ? $pmRaw->id() : (string) ($pmRaw ?? '');
            if ($pmUid === $uid) $ids[] = $doc->id();
        }
        return $ids;
    }
}
