<?php

namespace App\Controllers;

use App\Libraries\FirestoreService;
use App\Models\Model_Users;
use App\Services\NotificationService;
use Google\Cloud\Core\Timestamp;

class Reports extends BaseController
{
    protected FirestoreService $firestore;

    public function __construct()
    {
        $this->firestore = FirestoreService::getInstance();
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
                'id'               => $doc->id(),
                'date'             => $date,
                'causes'           => (array)($d['causes']      ?? $d['cause'] ?? []),
                'other_cause'      => (string)($d['other_cause'] ?? $d['otherCause'] ?? ''),
                'notes'            => (string)($d['notes']       ?? $d['description'] ?? ''),
                'filed_by'         => (string)($d['created_by_name'] ?? $d['filed_by'] ?? $d['createdByName'] ?? ''),
                'created_at'       => $this->timestampToFmt($d['created_at'] ?? null),
                'resolved'         => (bool)($d['resolved'] ?? false),
                'resolved_by_name' => (string)($d['resolved_by_name'] ?? ''),
                'resolved_at'      => $this->timestampToFmt($d['resolved_at'] ?? null),
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

        $filerName = (string)(session()->get('userName') ?? 'Someone');

        try {
            $db         = $this->firestore->getDb();
            $projectRef = $db->collection('project')->document($projectId);

            $ref = $projectRef->collection('emergency_reports')->add([
                    'date'            => $dateTs,
                    'causes'          => $causesList,
                    'other_cause'     => $otherCause,
                    'notes'           => $notes,
                    'created_by_name' => $filerName,
                    'created_by_uid'  => (string)(session()->get('userId') ?? ''),
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);

            // Flag the project so the MCDA excludes it from sourcing candidates
            $projectRef->update([['path' => 'has_active_emergency', 'value' => true]]);

            $reportId = $ref->id();

            // Resolve project name for the notification body
            $projectName = $projectId;
            try {
                $snap = $this->firestore->getDb()->collection('project')->document($projectId)->snapshot();
                if ($snap->exists()) {
                    $projectName = (string)($snap->data()['project_name'] ?? $projectId);
                }
            } catch (\Throwable $e) { /* keep fallback */ }

            $this->notifyEmergency($projectId, $reportId, $projectName, $filerName);

            return $this->response->setJSON(['success' => true]);
        } catch (\Throwable $e) {
            log_message('error', 'Reports::emergencyCreate - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Server error']);
        }
    }

    /** POST — mark an emergency report as resolved. */
    public function emergencyResolve(string $projectId, string $docId)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        if (!pz_can('resolve_emergency_report')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Forbidden']);
        }

        try {
            $db  = $this->firestore->getDb();
            $now = new Timestamp(new \DateTime());

            $db->collection('project')->document($projectId)
                ->collection('emergency_reports')->document($docId)
                ->update([
                    ['path' => 'resolved',          'value' => true],
                    ['path' => 'resolved_by_name',  'value' => (string)(session()->get('userName') ?? '')],
                    ['path' => 'resolved_by_uid',   'value' => (string)(session()->get('userId')   ?? '')],
                    ['path' => 'resolved_at',        'value' => $now],
                    ['path' => 'updated_at',         'value' => $now],
                ]);

            // Clear has_active_emergency if no unresolved reports remain
            $remaining = $db->collection('project')->document($projectId)
                ->collection('emergency_reports')->documents();
            $hasUnresolved = false;
            foreach ($remaining as $doc) {
                if (!$doc->exists()) continue;
                if (!($doc->data()['resolved'] ?? false)) { $hasUnresolved = true; break; }
            }
            if (!$hasUnresolved) {
                $db->collection('project')->document($projectId)->update([
                    ['path' => 'has_active_emergency', 'value' => false],
                ]);
            }

            return $this->response->setJSON(['success' => true]);
        } catch (\Throwable $e) {
            log_message('error', 'Reports::emergencyResolve - ' . $e->getMessage());
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

    /**
     * GET JSON — everything the report form needs for a given project+date:
     * fresh milestones, assigned manpower, and existing report data (if any).
     */
    public function dailyFormData(string $projectId, string $date)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        try {
            $db = $this->firestore->getDb();

            // ── Milestones (fresh, ordered by `order`) ──────────────────────
            $milestones = [];
            foreach ($db->collection('project')->document($projectId)
                        ->collection('milestones')->documents() as $mDoc) {
                if (!$mDoc->exists()) continue;
                $m = $mDoc->data();
                $milestones[] = [
                    'id'                 => $mDoc->id(),
                    'name'               => (string)($m['milestone_name'] ?? $m['name'] ?? ''),
                    'unit'               => (string)($m['unit'] ?? ''),
                    'target_quantity'    => (float)($m['target_quantity']    ?? 0),
                    'completed_quantity' => (float)($m['completed_quantity'] ?? 0),
                    'completion_percent' => (float)($m['completion_percent'] ?? 0),
                    'weight_percent'     => (float)($m['weight_percent'] ?? $m['weight'] ?? 0),
                    'order'              => (int)($m['order'] ?? 0),
                    'status'             => (string)($m['status'] ?? 'Not Started'),
                ];
            }
            usort($milestones, fn($a, $b) => $a['order'] <=> $b['order']);

            // Locked until the milestone before it (by order) is Completed —
            // consumed by the report form (and mobile) to disable progress entry.
            foreach ($milestones as $idx => &$ms) {
                $ms['locked'] = $idx > 0 && ($milestones[$idx - 1]['status'] ?? 'Not Started') !== 'Completed';
            }
            unset($ms);

            // ── Assigned manpower ────────────────────────────────────────────
            $manpower = [];
            foreach ($db->collection('manpower')->documents() as $mpDoc) {
                if (!$mpDoc->exists()) continue;
                $m   = $mpDoc->data();
                $md  = is_array($m['manpower_details'] ?? null) ? $m['manpower_details'] : [];
                $rawApid = $m['assigned_project_id'] ?? $md['assigned_project_id'] ?? null;
                $apid = $rawApid instanceof \Google\Cloud\Firestore\DocumentReference
                    ? $rawApid->id()
                    : trim((string)($rawApid ?? ''));
                if ($apid !== $projectId) continue;

                $fn = trim((string)($m['first_name'] ?? $md['first_name'] ?? ''));
                $ln = trim((string)($m['last_name']  ?? $md['last_name']  ?? ''));
                $manpower[] = [
                    'id'   => $mpDoc->id(),
                    'name' => $fn . ($fn && $ln ? ' ' : '') . $ln,
                    'role' => (string)($m['role'] ?? $md['role'] ?? ''),
                ];
            }
            usort($manpower, fn($a, $b) => strcasecmp($a['name'], $b['name']));

            // ── Existing report for this date ────────────────────────────────
            $existing = null;
            $reportSnap = $db->collection('project')->document($projectId)
                             ->collection('daily_progress_reports')->document($date)->snapshot();
            if ($reportSnap->exists()) {
                $r = $reportSnap->data();
                // Build milestone amounts map: id → amount_done_today + image_urls
                $msAmounts = [];
                foreach ((array)($r['milestone_progress'] ?? []) as $mp) {
                    if (!is_array($mp)) continue;
                    $mid = (string)($mp['milestone_id'] ?? '');
                    if ($mid !== '') {
                        $msAmounts[$mid] = [
                            'amount_done_today' => (float)($mp['amount_done_today'] ?? 0),
                            'image_urls'        => array_values(array_filter(
                                (array)($mp['image_urls'] ?? []), 'is_string'
                            )),
                        ];
                    }
                }
                // Manpower IDs from the existing report
                $existingMpIds = [];
                foreach ((array)($r['manpower'] ?? []) as $mp) {
                    if (is_array($mp) && isset($mp['id'])) $existingMpIds[] = $mp['id'];
                }
                $existing = [
                    'weather'        => (string)($r['weather']     ?? ''),
                    'remarks'        => (string)($r['remarks']     ?? ''),
                    'description'    => (string)($r['description'] ?? $r['notes'] ?? ''),
                    'manpower_ids'   => $existingMpIds,
                    'ms_amounts'     => $msAmounts,
                ];
            }

            return $this->response->setJSON([
                'milestones' => $milestones,
                'manpower'   => $manpower,
                'existing'   => $existing,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Reports::dailyFormData - ' . $e->getMessage());
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

            // Normalize manpower array: [{name, external?}]
            $manpowerArr = [];
            foreach ((array)($d['manpower'] ?? []) as $mp) {
                if (!is_array($mp)) continue;
                $manpowerArr[] = [
                    'name'     => (string)($mp['name'] ?? ''),
                    'external' => !empty($mp['external']),
                ];
            }

            // Normalize milestone_progress array
            $milestoneProgress = [];
            foreach ((array)($d['milestone_progress'] ?? []) as $ms) {
                if (!is_array($ms)) continue;
                $milestoneProgress[] = [
                    'milestone_id' => (string)($ms['milestone_id'] ?? ''),
                    'name'         => (string)($ms['milestone_name'] ?? ''),
                    'unit'         => (string)($ms['unit'] ?? ''),
                    'today'        => (float)($ms['amount_done_today']        ?? 0),
                    'total'        => (float)($ms['completed_quantity_after'] ?? 0),
                    'percent'      => (float)($ms['completion_percent_after'] ?? 0),
                    'image_urls'   => array_values(array_filter((array)($ms['image_urls'] ?? []), 'is_string')),
                ];
            }

            return $this->response->setJSON([
                'date'               => $doc->id(),
                'progress_percent'   => $progress !== null ? (float)$progress : null,
                'weather'            => (string)($d['weather']          ?? ''),
                'remarks'            => (string)($d['remarks']          ?? ''),
                'manpower'           => $manpowerArr,
                'manpower_display'   => (string)($d['manpower_display'] ?? ''),
                'milestone_progress' => $milestoneProgress,
                'description'        => (string)($d['description']      ?? $d['notes'] ?? ''),
                'filed_by'           => (string)($d['created_by_name']  ?? $d['filed_by'] ?? ''),
                'photos'             => (array)($d['photos']            ?? []),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Reports::dailyShow - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Server error']);
        }
    }

    /** POST — create / update a daily progress report (atomic Firestore transaction). */
    public function dailyCreate(string $projectId)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }
        if (!pz_can('edit_progress_report')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Forbidden']);
        }

        // ── Parse POST ───────────────────────────────────────────────────────
        $date        = trim((string)($this->request->getPost('date')        ?? ''));
        $weather     = trim((string)($this->request->getPost('weather')     ?? ''));
        $remarks     = trim((string)($this->request->getPost('remarks')     ?? ''));
        $description = trim((string)($this->request->getPost('description') ?? ''));
        $rawMs       = (array)($this->request->getPost('milestone_progress') ?? []);
        $rawManpower = (array)($this->request->getPost('manpower')           ?? []);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid date format']);
        }
        if (empty($rawManpower)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'At least one worker must be selected']);
        }

        $filerName = (string)(session()->get('userName') ?? 'Someone');
        $filerUid  = (string)(session()->get('userId')   ?? '');
        $now       = new Timestamp(new \DateTime());
        $db        = $this->firestore->getDb();

        // ── Pre-reads (outside transaction) ──────────────────────────────────

        // Milestones — need target_quantity, weight_percent, name, unit, status,
        //              actual_completion_date for delta math and validation
        $milestoneSnaps = [];       // id → snapshot
        $milestoneRefs  = [];       // id → DocumentReference
        $milestonesRef  = $db->collection('project')->document($projectId)->collection('milestones');
        foreach ($milestonesRef->documents() as $mDoc) {
            if (!$mDoc->exists()) continue;
            $milestoneSnaps[$mDoc->id()] = $mDoc;
            $milestoneRefs[$mDoc->id()]  = $milestonesRef->document($mDoc->id());
        }

        // Existing report — for "logged" baseline (amount_done_today per milestone)
        $reportRef    = $db->collection('project')->document($projectId)
                           ->collection('daily_progress_reports')->document($date);
        $existingSnap = $reportRef->snapshot();
        $isUpdate     = $existingSnap->exists();

        $loggedAmounts   = [];  // milestone_id → previously saved amount_done_today
        $loggedImageUrls = [];  // milestone_id → image_urls array (mobile-uploaded, must not be wiped)
        if ($isUpdate) {
            foreach ((array)($existingSnap->data()['milestone_progress'] ?? []) as $lm) {
                if (!is_array($lm)) continue;
                $mid = (string)($lm['milestone_id'] ?? '');
                if ($mid === '') continue;
                $loggedAmounts[$mid]   = (float)($lm['amount_done_today'] ?? 0);
                $loggedImageUrls[$mid] = array_values(array_filter(
                    (array)($lm['image_urls'] ?? []), 'is_string'
                ));
            }
        }

        // Submitted amounts: milestone_id → amount_done_today from the form
        $submittedAmounts = [];
        foreach ($rawMs as $ms) {
            if (!is_array($ms)) continue;
            $mid = trim((string)($ms['milestone_id'] ?? ''));
            if ($mid === '') continue;
            $submittedAmounts[$mid] = max(0.0, (float)($ms['amount_done_today'] ?? 0));
        }

        // Order/status lookup, used below to enforce milestone sequencing
        // (a milestone only accepts progress once the one before it is Completed).
        $orderByMid    = [];
        $statusByOrder = [];
        foreach ($milestoneSnaps as $mid => $mSnap) {
            $mData = $mSnap->data();
            $ord   = (int)($mData['order'] ?? 0);
            $orderByMid[$mid]    = $ord;
            $statusByOrder[$ord] = (string)($mData['status'] ?? 'Not Started');
        }

        // ── Server-side validation: unclamped overall progress ≤ 100.5% ──────
        $totalWeight        = 0.0;
        $unclampedWeightSum = 0.0;
        $blockedBySequence  = [];
        foreach ($milestoneSnaps as $mid => $mSnap) {
            $mData     = $mSnap->data();
            $weight    = (float)($mData['weight_percent'] ?? $mData['weight'] ?? 0);
            $targetQty = (float)($mData['target_quantity']    ?? 0);
            $currentQty = (float)($mData['completed_quantity'] ?? 0);

            $logged    = $loggedAmounts[$mid]    ?? 0.0;
            $submitted = $submittedAmounts[$mid] ?? $logged;
            $delta     = $submitted - $logged;
            $newQty    = $currentQty + $delta;

            if ($delta > 0.001) {
                $prevOrder = ($orderByMid[$mid] ?? 0) - 1;
                if ($prevOrder >= 1 && ($statusByOrder[$prevOrder] ?? 'Not Started') !== 'Completed') {
                    $blockedBySequence[] = (string)($mData['milestone_name'] ?? $mid);
                }
            }

            $totalWeight        += $weight;
            $unclampedWeightSum += $weight * ($targetQty > 0 ? $newQty / $targetQty * 100.0 : 0.0);
        }
        if (!empty($blockedBySequence)) {
            return $this->response->setStatusCode(400)->setJSON([
                'error' => 'Cannot log progress for ' . implode(', ', $blockedBySequence) . ' — the previous milestone must be completed first.',
            ]);
        }
        $unclampedOverall = $totalWeight > 0 ? $unclampedWeightSum / $totalWeight : 0.0;
        if ($unclampedOverall > 100.5) {
            return $this->response->setStatusCode(400)->setJSON([
                'error' => 'Overall project completion cannot exceed 100%. Please review the milestone quantities.',
            ]);
        }

        // ── Manpower array ────────────────────────────────────────────────────
        $manpowerArr   = [];
        $manpowerNames = [];
        foreach ($rawManpower as $mp) {
            if (!is_array($mp)) continue;
            $mpName = trim((string)($mp['name'] ?? ''));
            $mpId   = trim((string)($mp['id']   ?? ''));
            if ($mpName === '') continue;
            $entry = ['id' => $mpId, 'name' => $mpName];
            if (!empty($mp['external'])) $entry['external'] = true;
            $manpowerArr[]   = $entry;
            $manpowerNames[] = $mpName;
        }
        $manpowerDisplay = implode(', ', $manpowerNames);

        // ── Project metadata for post-write steps ─────────────────────────────
        $projectRef   = $db->collection('project')->document($projectId);
        $projectSnap  = $projectRef->snapshot();
        $projectData  = $projectSnap->exists() ? $projectSnap->data() : [];
        $projectName  = (string)($projectData['project_name'] ?? $projectId);
        $pmUid        = $this->extractUid(
            $projectData['personnel']['project_manager_id'] ?? $projectData['project_manager_id'] ?? null
        );
        $baselineType = (string)($projectData['baselineType'] ?? '');
        $startDate    = null;
        if ($baselineType === 'weekly') {
            $sdRaw = $projectData['start_date'] ?? null;
            if ($sdRaw instanceof Timestamp) {
                $startDate = $sdRaw->get()->setTimezone(new \DateTimeZone('Asia/Manila'))->format('Y-m-d');
            } elseif (is_string($sdRaw)) {
                $startDate = substr($sdRaw, 0, 10);
            }
        }

        // ── Atomic transaction ────────────────────────────────────────────────
        try {
            $db->runTransaction(function ($txn) use (
                $milestoneSnaps, $milestoneRefs,
                $loggedAmounts, $loggedImageUrls, $submittedAmounts,
                $isUpdate, $date, $weather, $remarks, $description,
                $manpowerArr, $manpowerDisplay,
                $filerName, $filerUid, $now,
                $reportRef, $projectRef,
                $baselineType, $startDate
            ) {
                // Re-read completed_quantity inside transaction for serializability
                $freshQty = [];
                foreach ($milestoneRefs as $mid => $mRef) {
                    $snap = $txn->snapshot($mRef);
                    $freshQty[$mid] = $snap->exists() ? (float)($snap->data()['completed_quantity'] ?? 0) : 0.0;
                }

                $milestoneProgressEntries = [];
                $totalWeight        = 0.0;
                $clampedWeightSum   = 0.0;

                foreach ($milestoneSnaps as $mid => $mSnap) {
                    $mData     = $mSnap->data();
                    $targetQty = (float)($mData['target_quantity']   ?? 0);
                    $weight    = (float)($mData['weight_percent']    ?? $mData['weight'] ?? 0);
                    $mName     = (string)($mData['milestone_name']   ?? $mData['name']   ?? '');
                    $mUnit     = (string)($mData['unit'] ?? '');
                    $oldStatus = (string)($mData['status'] ?? 'Not Started');

                    $logged    = $loggedAmounts[$mid]    ?? 0.0;
                    $submitted = $submittedAmounts[$mid] ?? $logged;
                    $delta     = $submitted - $logged;

                    $currentQty = $freshQty[$mid];
                    $newQty     = $currentQty + $delta;
                    $newPct     = $targetQty > 0 ? min(100.0, $newQty / $targetQty * 100.0) : 0.0;

                    $totalWeight      += $weight;
                    $clampedWeightSum += $weight * $newPct;

                    if (abs($delta) < 0.00001) continue; // skip no-op milestones

                    // Derive new status
                    if ($newPct >= 100.0) {
                        $newStatus = 'Completed';
                    } elseif ($newQty > 0) {
                        $newStatus = 'In Progress';
                    } else {
                        $newStatus = 'Not Started';
                    }

                    $wasCompleted  = in_array(strtolower($oldStatus), ['completed', 'done'], true);
                    $nowCompleted  = ($newStatus === 'Completed');

                    $updateFields = [
                        ['path' => 'completed_quantity', 'value' => $newQty],
                        ['path' => 'completion_percent', 'value' => $newPct],
                        ['path' => 'status',             'value' => $newStatus],
                        ['path' => 'updated_at',         'value' => $now],
                    ];
                    if (!$wasCompleted && $nowCompleted) {
                        $updateFields[] = ['path' => 'actual_completion_date', 'value' => $now];
                    } elseif ($wasCompleted && !$nowCompleted) {
                        $updateFields[] = ['path' => 'actual_completion_date', 'value' => null];
                    }
                    $txn->update($milestoneRefs[$mid], $updateFields);

                    $entry = [
                        'milestone_id'             => $mid,
                        'milestone_name'           => $mName,
                        'unit'                     => $mUnit,
                        'weight_percent'           => $weight,
                        'amount_done_today'        => $submitted,
                        'completed_quantity_after' => $newQty,
                        'completion_percent_after' => $newPct,
                    ];
                    if (!empty($loggedImageUrls[$mid])) {
                        $entry['image_urls'] = $loggedImageUrls[$mid];
                    }
                    $milestoneProgressEntries[] = $entry;
                }

                $overallProgress = $totalWeight > 0
                    ? round(min(100.0, $clampedWeightSum / $totalWeight), 2)
                    : 0.0;

                // Report document payload
                $reportPayload = [
                    'report_date'              => new Timestamp(new \DateTime($date)),
                    'weather'                  => $weather,
                    'remarks'                  => $remarks,
                    'description'              => $description,
                    'manpower'                 => $manpowerArr,
                    'manpower_display'         => $manpowerDisplay,
                    'total_completion_percent' => $overallProgress,
                    'created_by_name'          => $filerName,
                    'created_by_uid'           => $filerUid,
                    'updated_at'               => $now,
                ];
                if (!$isUpdate) {
                    $reportPayload['created_at'] = $now;
                }
                if (!empty($milestoneProgressEntries)) {
                    $reportPayload['milestone_progress'] = $milestoneProgressEntries;
                }
                $txn->set($reportRef, $reportPayload, ['merge' => true]);

                // Project-level updates
                $projectUpdates = [
                    ['path' => 'overall_progress',      'value' => $overallProgress],
                    ['path' => 'latest_daily_progress', 'value' => $reportPayload],
                    ['path' => 'updated_at',            'value' => $now],
                ];
                if ($baselineType === 'weekly' && $startDate !== null) {
                    $diffDays  = (int)(new \DateTime($startDate))->diff(new \DateTime($date))->days;
                    $weekIdx   = (int)floor($diffDays / 7);
                    $projectUpdates[] = [
                        'path'  => 'weekly_targets.' . $weekIdx . '.actual_percent',
                        'value' => $overallProgress,
                    ];
                }
                $txn->update($projectRef, $projectUpdates);
            });
        } catch (\Throwable $e) {
            log_message('error', 'Reports::dailyCreate transaction - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Failed to save report. Please try again.']);
        }

        // ── Post-commit side effects (non-critical) ───────────────────────────
        $this->notifyProgress($projectId, $projectName, $filerName, $filerUid, $pmUid, $isUpdate);

        try {
            $predictor = new \App\Libraries\ProjectDelayPredictor($this->firestore);
            $nextAvail = $predictor->predict($projectId, ['progress_percent' => null]);
            if ($nextAvail) {
                $predictor->writeToProjectItems($projectId, $nextAvail);
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Reports::dailyCreate predictor - ' . $e->getMessage());
        }

        return $this->response->setJSON(['success' => true]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /** Convert a Firestore Timestamp to a human-readable string in Manila time (e.g. "Jun 26, 2026 2:45 PM"). */
    private function timestampToFmt(mixed $value): string
    {
        if (!$value instanceof Timestamp) return '';
        try {
            return $value->get()
                ->setTimezone(new \DateTimeZone('Asia/Manila'))
                ->format('M j, Y g:i A');
        } catch (\Throwable) {
            return '';
        }
    }

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

    private function notifyProgress(
        string $projectId,
        string $projectName,
        string $filerName,
        string $filerUid,
        string $pmUid,
        bool   $isUpdate
    ): void {
        try {
            $verb  = $isUpdate ? 'updated' : 'submitted';
            $title = $isUpdate ? 'Progress Report Updated' : 'Progress Report Submitted';
            $body  = "{$filerName} {$verb} a progress report for {$projectName}";
            $meta  = ['project_id' => $projectId];

            $svc   = new NotificationService();
            $sent  = [];

            // Role-based recipients: Administrators and Operations Managers
            $users = (new Model_Users())->getUsersByRole(['Administrator', 'Operations Manager']);
            foreach ($users as $user) {
                $uid = (string)($user['id'] ?? '');
                if (!$uid || $uid === $filerUid || in_array($uid, $sent, true)) continue;
                $svc->send($uid, 'progressReported', $title, $body, $meta);
                $sent[] = $uid;
            }

            // Also notify the project's assigned PM individually
            if ($pmUid && $pmUid !== $filerUid && !in_array($pmUid, $sent, true)) {
                $svc->send($pmUid, 'progressReported', $title, $body, $meta);
                $sent[] = $pmUid;
            }
        } catch (\Throwable $e) {
            log_message('error', 'Reports::notifyProgress - ' . $e->getMessage());
        }
    }

    /** Extract a plain user ID from a value that may be a DocumentReference, a path string, or a bare ID. */
    private function extractUid(mixed $value): string
    {
        if ($value === null) return '';
        if ($value instanceof \Google\Cloud\Firestore\DocumentReference) {
            return $value->id();
        }
        if (is_string($value)) {
            // Strip leading path segments (e.g. "users/abc123" → "abc123")
            return ltrim(strrchr($value, '/'), '/') ?: $value;
        }
        return '';
    }

    private function notifyEmergency(string $projectId, string $reportId, string $projectName, string $filerName): void
    {
        try {
            $users = (new Model_Users())->getUsersByRole(['Administrator', 'Operations Manager']);
            $svc   = new NotificationService();
            foreach ($users as $user) {
                $uid = (string)($user['id'] ?? '');
                if (!$uid) continue;
                $svc->send(
                    $uid,
                    'emergencyCreated',
                    'Emergency Report Filed',
                    "{$filerName} filed an emergency report on {$projectName}",
                    ['project_id' => $projectId, 'report_id' => $reportId]
                );
            }
        } catch (\Throwable $e) {
            log_message('error', 'Reports::notifyEmergency - ' . $e->getMessage());
        }
    }
}
