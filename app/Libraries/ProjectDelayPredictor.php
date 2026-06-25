<?php

namespace App\Libraries;

use App\Libraries\FirestoreService;
use App\Libraries\MLServiceClient;
use Google\Cloud\Core\Timestamp;

/**
 * Computes next_availability for resources deployed at a project,
 * and provides ML insights (blocker type + delay days) for display.
 *
 * Formula: project.end_date + ML-predicted delay days = next_availability
 */
class ProjectDelayPredictor
{
    private FirestoreService $firestore;
    private MLServiceClient  $ml;

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
        $this->ml        = new MLServiceClient();
    }

    /**
     * Predict next_availability date for resources at $projectId.
     * Writes the result to all resource items at the project via writeToProjectItems().
     *
     * @param string $projectId
     * @param array  $reportData  Optional overrides from a freshly-submitted report:
     *                            ['progress_percent', 'workers_present', 'workers_planned', 'site_access_issues']
     * @return string|null  Y-m-d date string, or null on failure
     */
    public function predict(string $projectId, array $reportData = []): ?string
    {
        $built = $this->buildFeatures($projectId, $reportData);
        if (!$built) return null;

        ['features' => $features, 'end_date' => $endDate] = $built;

        try {
            $result    = $this->ml->predictDelay($features);
            if (isset($result['error']) || empty($result)) return null;

            $delayDays = (int) round((float)(
                $result['predicted_delay_days'] ?? $result['delay_days'] ?? $result['prediction'] ?? 0
            ));

            $predicted = clone $endDate;
            if ($delayDays > 0) $predicted->modify("+{$delayDays} days");

            return $predicted->format('Y-m-d');
        } catch (\Throwable $e) {
            log_message('warning', 'ProjectDelayPredictor::predict - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Return ML blocker classification + delay days for display in the progress report module.
     * Does NOT write to Firestore — read-only, safe to call on page load.
     *
     * @return array{blocker: string|null, delay_days: int}
     */
    public function getInsights(string $projectId): array
    {
        $result = ['blocker' => null, 'delay_days' => 0];
        $built  = $this->buildFeatures($projectId);
        if (!$built) return $result;

        ['features' => $features] = $built;

        try {
            $blockerRes = $this->ml->predictBlockers($features);
            if (!isset($blockerRes['error'])) {
                $raw = $blockerRes['blocker_type']
                    ?? $blockerRes['predicted_blocker']
                    ?? $blockerRes['prediction']
                    ?? null;
                if ($raw && !in_array(strtolower((string)$raw), ['unknown', 'none', 'no blocker', ''], true)) {
                    $result['blocker'] = (string)$raw;
                }
            }
        } catch (\Throwable $e) {
            log_message('warning', 'ProjectDelayPredictor::getInsights blockers - ' . $e->getMessage());
        }

        try {
            $delayRes = $this->ml->predictDelay($features);
            if (!isset($delayRes['error'])) {
                $result['delay_days'] = (int) round((float)(
                    $delayRes['predicted_delay_days'] ?? $delayRes['delay_days'] ?? $delayRes['prediction'] ?? 0
                ));
            }
        } catch (\Throwable $e) {
            log_message('warning', 'ProjectDelayPredictor::getInsights delay - ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Write the predicted next_availability date to every resource deployed at $projectId.
     * Uses the project's denormalized resources[] array — no collection-group scan needed.
     */
    public function writeToProjectItems(string $projectId, string $date): void
    {
        try {
            $db   = $this->firestore->getDb();
            $now  = new Timestamp(new \DateTime());
            $snap = $db->collection('project')->document($projectId)->snapshot();
            if (!$snap->exists()) return;

            foreach ((array)($snap->data()['resources'] ?? []) as $entry) {
                $refId  = (string)($entry['ref_id']  ?? '');
                $unitId = (string)($entry['unit_id'] ?? '');
                $cat    = strtolower((string)($entry['category'] ?? ''));
                if ($refId === '') continue;

                try {
                    if ($cat === 'manpower') {
                        $db->collection('manpower')->document($refId)->update([
                            ['path' => 'next_availability', 'value' => $date],
                            ['path' => 'updated_at',        'value' => $now],
                        ]);
                    } elseif ($unitId !== '') {
                        $db->collection('resource')->document($refId)
                            ->collection('items')->document($unitId)->update([
                                ['path' => 'next_availability', 'value' => $date],
                                ['path' => 'updated_at',        'value' => $now],
                            ]);
                    }
                } catch (\Throwable $e) {
                    log_message('warning', "ProjectDelayPredictor::writeToProjectItems [{$refId}/{$unitId}] - " . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            log_message('warning', 'ProjectDelayPredictor::writeToProjectItems - ' . $e->getMessage());
        }
    }

    // ── Internal ─────────────────────────────────────────────────────────────

    /**
     * Build the ML feature array for a project.
     * Returns ['features' => [...], 'end_date' => DateTime] or null if project not found / no end date.
     */
    private function buildFeatures(string $projectId, array $overrides = []): ?array
    {
        try {
            $db          = $this->firestore->getDb();
            $projectSnap = $db->collection('project')->document($projectId)->snapshot();
            if (!$projectSnap->exists()) return null;
            $project = $projectSnap->data();

            // ── Date features ──────────────────────────────────────────────
            $now       = new \DateTime('now', new \DateTimeZone('Asia/Manila'));
            $endDate   = $this->tsToDateTime($project['end_date']   ?? null);
            $startDate = $this->tsToDateTime($project['start_date'] ?? null);
            if (!$endDate) return null;

            $daysElapsed   = $startDate ? max(0, (int)$startDate->diff($now)->days) : 0;
            $daysRemaining = max(0, (int)$now->diff($endDate)->days);
            $totalDays     = max(1, $daysElapsed + $daysRemaining);
            $plannedPct    = min(100.0, round(($daysElapsed / $totalDays) * 100, 2));

            // ── Progress ───────────────────────────────────────────────────
            $progressPct = (isset($overrides['progress_percent']) && $overrides['progress_percent'] !== null)
                           ? (float)$overrides['progress_percent']
                           : (float)($project['overall_progress'] ?? 0);

            // ── Workers & site access (override or fetch latest report) ────
            $workersPresent   = array_key_exists('workers_present',    $overrides) ? (int)$overrides['workers_present']    : -1;
            $workersPlanned   = array_key_exists('workers_planned',    $overrides) ? (int)$overrides['workers_planned']    : -1;
            $siteAccessIssues = array_key_exists('site_access_issues', $overrides) ? (int)$overrides['site_access_issues'] : -1;

            if ($workersPresent < 0 || $workersPlanned < 0 || $siteAccessIssues < 0) {
                try {
                    $rDocs = $db->collection('project')->document($projectId)
                        ->collection('daily_progress_reports')
                        ->orderBy('report_date', 'DESCENDING')
                        ->limit(1)->documents();
                    foreach ($rDocs as $rDoc) {
                        if (!$rDoc->exists()) continue;
                        $rd = $rDoc->data();
                        if ($workersPresent   < 0) $workersPresent   = count((array)($rd['manpower'] ?? []));
                        if ($workersPlanned   < 0) $workersPlanned   = (int)($rd['workers_planned']    ?? $workersPresent);
                        if ($siteAccessIssues < 0) $siteAccessIssues = (int)($rd['site_access_issues'] ?? 0);
                    }
                } catch (\Throwable $e) {
                    log_message('warning', 'ProjectDelayPredictor: could not fetch daily report - ' . $e->getMessage());
                }
            }

            // ── Equipment utilization from project.resources[] ─────────────
            // Counts deployed (non-transit) equipment/tool items vs total assigned to project.
            $equipTotal  = 0;
            $equipActive = 0;
            foreach ((array)($project['resources'] ?? []) as $r) {
                if (strtolower((string)($r['category'] ?? '')) === 'manpower') continue;
                $equipTotal++;
                if (empty($r['transit'])) $equipActive++; // transit = still in transit, not yet active
            }
            $equipUtilization = $equipTotal > 0 ? round($equipActive / $equipTotal, 2) : 1.0;

            // ── Emergency features ─────────────────────────────────────────
            $emergencyCount       = 0;
            $daysSinceLastBlocker = 999;
            try {
                $eDocs = $db->collection('project')->document($projectId)
                    ->collection('emergency_reports')->documents();
                foreach ($eDocs as $eDoc) {
                    if (!$eDoc->exists()) continue;
                    $emergencyCount++;
                    $eDate = $this->tsToDateTime($eDoc->data()['created_at'] ?? null)
                          ?? $this->tsToDateTime($eDoc->data()['date']       ?? null);
                    if ($eDate) {
                        $diff = (int)$eDate->diff($now)->days;
                        if ($diff < $daysSinceLastBlocker) $daysSinceLastBlocker = $diff;
                    }
                }
            } catch (\Throwable $e) {
                log_message('warning', 'ProjectDelayPredictor: could not count emergencies - ' . $e->getMessage());
            }

            return [
                'features' => [
                    'project_id'              => $projectId,
                    'progress_percent'        => $progressPct,
                    'planned_progress_percent'=> $plannedPct,
                    'days_elapsed'            => $daysElapsed,
                    'days_remaining'          => $daysRemaining,
                    'workers_present'         => max(0, $workersPresent),
                    'workers_planned'         => max(1, $workersPlanned),
                    'equipment_utilization'   => $equipUtilization,
                    'weather_severity'        => 0,
                    'site_access_issues'      => max(0, $siteAccessIssues),
                    'avg_progress_3d'         => $progressPct,
                    'progress_trend_3d'       => 0.0,
                    'consecutive_low_days'    => 0,
                    'budget_utilization'      => 0.0,
                    'cost_variance_ratio'     => 0.0,
                    'active_blocker_count'    => $emergencyCount,
                    'days_since_last_blocker' => $daysSinceLastBlocker,
                ],
                'end_date' => $endDate,
            ];
        } catch (\Throwable $e) {
            log_message('warning', 'ProjectDelayPredictor::buildFeatures - ' . $e->getMessage());
            return null;
        }
    }

    private function tsToDateTime(mixed $ts): ?\DateTime
    {
        if ($ts instanceof \Google\Cloud\Core\Timestamp) {
            return \DateTime::createFromInterface($ts->get());
        }
        if (is_string($ts) && $ts !== '') {
            try { return new \DateTime($ts); } catch (\Throwable) { return null; }
        }
        return null;
    }
}
