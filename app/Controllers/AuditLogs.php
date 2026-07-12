<?php

namespace App\Controllers;

use App\Services\AuditLogService;
use Google\Cloud\Core\Timestamp;

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
            return redirect()->to('/dashboard')->with('error', 'Access restricted to administrators.');
        }
        return null;
    }

    public function index()
    {
        if ($blocked = $this->guard()) return $blocked;

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

        $items = $this->audit->query(array_filter($filters, fn($v) => $v !== '' && $v !== null));

        return view('audit_logs/index', [
            'pageTitle' => 'Audit Logs',
            'userName'  => session()->get('userName') ?? 'User',
            'role'      => session()->get('role') ?? 'User',
            'items'     => $items,
            'filters'   => [
                'module'     => $filters['module'],
                'action'     => $filters['action'],
                'actor_role' => $filters['actor_role'],
                'status'     => $filters['status'],
                'q'          => $filters['q'],
                'date_from'  => $dateFrom,
                'date_to'    => $dateTo,
            ],
        ]);
    }
}
