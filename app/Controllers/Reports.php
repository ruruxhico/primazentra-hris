<?php

namespace App\Controllers;

use App\Libraries\FirestoreService;
use Google\Cloud\Core\Timestamp;

class Reports extends BaseController
{
    protected FirestoreService $firestore;

    public function __construct()
    {
        $this->firestore = new FirestoreService();
        helper('access');
    }

    // ── Emergency Reports ──────────────────────────────────────────────────

    /**
     * GET JSON — list of emergency reports with {id, date} pairs.
     * Mobile stores auto-generated doc IDs; date is a Firestore Timestamp field.
     */
    public function emergencyList(string $projectId)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        try {
            $docs = $this->firestore->getDb()
                ->collection('project')->document($projectId)
                ->collection('emergency_reports')->documents();

            $reports = [];
            foreach ($docs as $doc) {
                if (!$doc->exists()) continue;
                $data = $doc->data();

                // Try canonical field first, then common mobile alternatives, then created_at
                $date = $this->timestampToDate($data['date']           ?? null)
                     ?? $this->timestampToDate($data['report_date']    ?? null)
                     ?? $this->timestampToDate($data['emergency_date'] ?? null)
                     ?? $this->timestampToDate($data['created_at']     ?? null);

                if ($date) {
                    $reports[] = ['id' => $doc->id(), 'date' => $date];
                } else {
                    log_message('warning', 'emergencyList: doc ' . $doc->id() . ' has no parseable date field. Keys: ' . implode(', ', array_keys($data)));
                }
            }

            // Sort by date ascending
            usort($reports, fn($a, $b) => strcmp($a['date'], $b['date']));

            return $this->response->setJSON(['reports' => $reports]);
        } catch (\Throwable $e) {
            log_message('error', 'Reports::emergencyList - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Server error']);
        }
    }

    /** GET JSON — single emergency report fetched by its auto-generated doc ID. */
    public function emergencyShow(string $projectId, string $docId)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        try {
            $doc = $this->firestore->getDb()
                ->collection('project')->document($projectId)
                ->collection('emergency_reports')->document($docId)->snapshot();

            if (!$doc->exists()) {
                return $this->response->setStatusCode(404)->setJSON(['error' => 'Not found']);
            }

            $d = $doc->data();
            $date = $this->timestampToDate($d['date']           ?? null)
                 ?? $this->timestampToDate($d['report_date']    ?? null)
                 ?? $this->timestampToDate($d['emergency_date'] ?? null)
                 ?? $this->timestampToDate($d['created_at']     ?? null);
            return $this->response->setJSON([
                'id'          => $doc->id(),
                'date'        => $date,
                'causes'      => (array)($d['causes']      ?? $d['cause'] ?? []),
                'other_cause' => (string)($d['other_cause'] ?? $d['otherCause'] ?? ''),
                'notes'       => (string)($d['notes']       ?? $d['description'] ?? ''),
                'filed_by'    => (string)($d['created_by_name'] ?? $d['filed_by'] ?? $d['createdByName'] ?? ''),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Reports::emergencyShow - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Server error']);
        }
    }

    /** POST — create an emergency report using .add() to match mobile schema. */
    public function emergencyCreate(string $projectId)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        if (!pz_can('create_emergency_report')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Forbidden']);
        }

        $date   = trim((string)($this->request->getPost('date') ?? ''));
        $causes = $this->request->getPost('causes') ?? [];
        $notes  = trim((string)($this->request->getPost('notes') ?? ''));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid date format']);
        }

        if (empty($causes)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'At least one cause is required']);
        }

        $causesList  = array_values((array)$causes);
        $otherCause  = in_array('Other', $causesList, true)
                       ? trim((string)($this->request->getPost('other_cause') ?? ''))
                       : '';
        $dateTs      = new Timestamp(new \DateTime($date));
        $now         = new Timestamp(new \DateTime());

        try {
            $this->firestore->getDb()
                ->collection('project')->document($projectId)
                ->collection('emergency_reports')->add([
                    'date'            => $dateTs,
                    'causes'          => $causesList,
                    'other_cause'     => $otherCause,
                    'notes'           => $notes,
                    'created_by_name' => (string)(session()->get('userName') ?? ''),
                    'created_by_uid'  => (string)(session()->get('userId')   ?? ''),
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);

            return $this->response->setJSON(['success' => true]);
        } catch (\Throwable $e) {
            log_message('error', 'Reports::emergencyCreate - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Server error']);
        }
    }

    // ── Daily Progress Reports ─────────────────────────────────────────────

    /** GET JSON — list of dates with daily progress reports (doc IDs are YYYY-MM-DD). */
    public function dailyList(string $projectId)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        try {
            $docs = $this->firestore->getDb()
                ->collection('project')->document($projectId)
                ->collection('daily_progress_reports')->documents();

            $dates = [];
            foreach ($docs as $doc) {
                if ($doc->exists()) $dates[] = $doc->id(); // doc ID = YYYY-MM-DD
            }
            sort($dates);

            return $this->response->setJSON(['dates' => $dates]);
        } catch (\Throwable $e) {
            log_message('error', 'Reports::dailyList - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Server error']);
        }
    }

    /** GET JSON — single daily progress report by date (YYYY-MM-DD doc ID). */
    public function dailyShow(string $projectId, string $date)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        try {
            $doc = $this->firestore->getDb()
                ->collection('project')->document($projectId)
                ->collection('daily_progress_reports')->document($date)->snapshot();

            if (!$doc->exists()) {
                return $this->response->setStatusCode(404)->setJSON(['error' => 'Not found']);
            }

            $d = $doc->data();
            $progress = $d['total_completion_percent']
                      ?? $d['progress_percent']
                      ?? $d['overall_progress']
                      ?? $d['completion_percent']
                      ?? null;

            return $this->response->setJSON([
                'date'             => $doc->id(),
                'progress_percent' => $progress !== null ? (float)$progress : null,
                'weather'          => (string)($d['weather']           ?? ''),
                'manpower_display' => (string)($d['manpower_display']  ?? ''),
                'description'      => (string)($d['description']       ?? $d['notes'] ?? ''),
                'filed_by'         => (string)($d['created_by_name']   ?? $d['filed_by'] ?? ''),
                'photos'           => (array)($d['photos']             ?? []),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Reports::dailyShow - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Server error']);
        }
    }

    /** POST — create / overwrite a daily progress report (Admin only on web). */
    public function dailyCreate(string $projectId)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        if (!pz_can('edit_progress_report')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Forbidden']);
        }

        $date        = trim((string)($this->request->getPost('date') ?? ''));
        $progress    = $this->request->getPost('progress_percent');
        $description = trim((string)($this->request->getPost('description') ?? ''));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid date format']);
        }

        $now = new Timestamp(new \DateTime());

        try {
            $this->firestore->getDb()
                ->collection('project')->document($projectId)
                ->collection('daily_progress_reports')->document($date)->set([
                    'report_date'             => new Timestamp(new \DateTime($date)),
                    'total_completion_percent' => $progress !== null ? (float)$progress : null,
                    'description'              => $description,
                    'created_by_name'          => (string)(session()->get('userName') ?? ''),
                    'created_by_uid'           => (string)(session()->get('userId')   ?? ''),
                    'created_at'               => $now,
                    'updated_at'               => $now,
                ], ['merge' => true]);

            return $this->response->setJSON(['success' => true]);
        } catch (\Throwable $e) {
            log_message('error', 'Reports::dailyCreate - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Server error']);
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /** Convert a Firestore Timestamp or string to YYYY-MM-DD (Manila time), or null on failure. */
    private function timestampToDate(mixed $value): ?string
    {
        if ($value === null) return null;

        if ($value instanceof Timestamp) {
            // Timestamp::get() always returns DateTimeImmutable in UTC.
            // Mobile stores dates at midnight Manila time (UTC+8), so we must
            // interpret the timestamp in Manila timezone to get the correct local date.
            try {
                $dt = \DateTimeImmutable::createFromInterface($value->get())
                      ->setTimezone(new \DateTimeZone('Asia/Manila'));
                return $dt->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value, $m)) {
            return $m[0];
        }

        return null;
    }
}
