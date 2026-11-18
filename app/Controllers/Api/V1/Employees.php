<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Libraries\FirestoreService;

/**
 * GET /api/v1/employees[?status=active]
 * GET /api/v1/employees/{employee_id}
 *
 * Read-only view of employee master data for hris-attendance and
 * hris-payroll. Contract v1: fields listed in PUBLIC_FIELDS. New fields
 * may be added; existing ones are never renamed or removed within v1.
 */
class Employees extends BaseController
{
    private const PUBLIC_FIELDS = [
        'employee_id', 'first_name', 'last_name', 'position',
        'schedule_type', 'monthly_salary', 'status',
    ];

    public function index()
    {
        $status = (string) $this->request->getGet('status');

        try {
            $out = [];
            foreach (FirestoreService::getInstance()->getDb()->collection('employees')->documents() as $doc) {
                if (! $doc->exists()) {
                    continue;
                }
                $e = self::toPublic($doc->data());
                if ($status === '' || $e['status'] === $status) {
                    $out[] = $e;
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'API employees index: ' . $e->getMessage());

            return $this->error(503, 'unavailable', 'Employee data is temporarily unavailable.');
        }

        usort($out, static fn ($a, $b) => strcmp($a['employee_id'], $b['employee_id']));

        return $this->response->setJSON(['api_version' => 'v1', 'count' => count($out), 'data' => $out]);
    }

    public function show(string $id)
    {
        try {
            $snap = FirestoreService::getInstance()->getDb()->collection('employees')->document($id)->snapshot();
        } catch (\Throwable $e) {
            log_message('error', 'API employees show: ' . $e->getMessage());

            return $this->error(503, 'unavailable', 'Employee data is temporarily unavailable.');
        }

        if (! $snap->exists()) {
            return $this->error(404, 'not_found', "Employee {$id} not found.");
        }

        return $this->response->setJSON(['api_version' => 'v1', 'data' => self::toPublic($snap->data())]);
    }

    private static function toPublic(array $d): array
    {
        $e = [];
        foreach (self::PUBLIC_FIELDS as $f) {
            $e[$f] = $d[$f] ?? null;
        }
        $e['monthly_salary'] = (float) ($e['monthly_salary'] ?? 0);
        $e['status']         = (string) ($e['status'] ?? 'active');

        return $e;
    }

    private function error(int $status, string $code, string $message)
    {
        return $this->response->setStatusCode($status)->setJSON(['error' => ['code' => $code, 'message' => $message]]);
    }
}
