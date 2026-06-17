<?php

namespace App\Libraries;

class MilestoneProgressService
{
    /**
     * Build the planned progress curve from a set of milestones.
     *
     * Milestones must carry at least: order (int), weight (float), targetDate (string|null).
     * The curve starts at (projectStart, 0%) and ends at the last dated milestone (or
     * projectEnd as a fallback) at 100%.  Undated milestones are NOT control points —
     * their weight is absorbed into the ramp up to the next dated milestone.
     *
     * @return array {
     *   controlPoints:     [['date'=>'Y-m-d', 'cumWeight'=>float], …],
     *   undatedCount:      int,
     *   totalMilestones:   int,
     *   hasTerminalAnchor: bool,
     *   warning:           string|null,
     * }
     */
    public function computeCurve(array $milestones, string $projectStart, ?string $projectEnd): array
    {
        usort($milestones, static fn($a, $b) => (int)($a['order'] ?? 0) <=> (int)($b['order'] ?? 0));

        $undatedCount  = 0;
        $cumWeight     = 0.0;
        $controlPoints = [['date' => $projectStart, 'cumWeight' => 0.0]];

        foreach ($milestones as $m) {
            $cumWeight += (float)($m['weight'] ?? 0);

            if (empty($m['targetDate'])) {
                $undatedCount++;
            } else {
                $controlPoints[] = [
                    'date'      => (string) $m['targetDate'],
                    'cumWeight' => round($cumWeight, 4),
                ];
            }
        }

        $last              = end($controlPoints);
        $hasTerminalAnchor = ($last !== false) && abs($last['cumWeight'] - 100.0) < 0.5;
        $warning           = null;

        if (!$hasTerminalAnchor) {
            if ($projectEnd !== null && $projectEnd !== '') {
                $controlPoints[]   = ['date' => $projectEnd, 'cumWeight' => 100.0];
                $hasTerminalAnchor = true;
                $warning = 'The final milestone has no target date; using project end date as the 100% anchor.';
            } else {
                $warning = 'No terminal anchor: the final milestone is undated and no project end date is set. The planned curve cannot reach 100%.';
            }
        }

        return [
            'controlPoints'     => $controlPoints,
            'undatedCount'      => $undatedCount,
            'totalMilestones'   => count($milestones),
            'hasTerminalAnchor' => $hasTerminalAnchor,
            'warning'           => $warning,
        ];
    }

    /**
     * Sample the planned progress curve at any given date.
     * Returns a float in [0, 100].
     */
    public function sampleAt(array $controlPoints, string $date): float
    {
        $ts = strtotime($date);
        $n  = count($controlPoints);

        if ($n === 0) return 0.0;

        if ($ts <= strtotime($controlPoints[0]['date'])) {
            return (float) $controlPoints[0]['cumWeight'];
        }

        if ($ts >= strtotime($controlPoints[$n - 1]['date'])) {
            return (float) $controlPoints[$n - 1]['cumWeight'];
        }

        for ($i = 1; $i < $n; $i++) {
            $t1 = strtotime($controlPoints[$i - 1]['date']);
            $t2 = strtotime($controlPoints[$i]['date']);
            $w1 = (float) $controlPoints[$i - 1]['cumWeight'];
            $w2 = (float) $controlPoints[$i]['cumWeight'];

            if ($ts >= $t1 && $ts <= $t2) {
                if ($t2 === $t1) return $w2;
                $frac = ($ts - $t1) / ($t2 - $t1);
                return round($w1 + $frac * ($w2 - $w1), 4);
            }
        }

        return (float) $controlPoints[$n - 1]['cumWeight'];
    }

    /**
     * Planned daily rate (percentage-points per calendar day) at a given date.
     * Returns the slope of the interpolated curve at that point.
     */
    public function dailyRateAt(array $controlPoints, string $date): float
    {
        $ts = strtotime($date);
        $n  = count($controlPoints);

        if ($n < 2) return 0.0;

        for ($i = 1; $i < $n; $i++) {
            $t1 = strtotime($controlPoints[$i - 1]['date']);
            $t2 = strtotime($controlPoints[$i]['date']);

            if ($ts >= $t1 && $ts <= $t2) {
                $days = ($t2 - $t1) / 86400;
                if ($days <= 0) return 0.0;
                $slope = ((float)$controlPoints[$i]['cumWeight'] - (float)$controlPoints[$i - 1]['cumWeight']) / $days;
                return round($slope, 6);
            }
        }

        // After the last segment: use the slope of the final segment
        $t1   = strtotime($controlPoints[$n - 2]['date']);
        $t2   = strtotime($controlPoints[$n - 1]['date']);
        $days = ($t2 - $t1) / 86400;
        if ($days <= 0) return 0.0;
        return round(((float)$controlPoints[$n - 1]['cumWeight'] - (float)$controlPoints[$n - 2]['cumWeight']) / $days, 6);
    }

    /**
     * Build weekly target rows from project start to the report date (inclusive).
     * Week boundaries are Sundays.
     *
     * @return array  Each element: ['weekEnd'=>'Y-m-d', 'target'=>float, 'hasMilestone'=>bool]
     */
    public function weeklyTargets(array $controlPoints, string $projectStart, string $reportDate): array
    {
        // Map each dated milestone to the Sunday ending that week
        $milestoneWeekEnds = [];
        foreach ($controlPoints as $cp) {
            if ((float) $cp['cumWeight'] <= 0.0) continue;
            $ts  = strtotime($cp['date']);
            $dow = (int) date('w', $ts);
            $sun = $ts + ((7 - $dow) % 7) * 86400;
            $milestoneWeekEnds[date('Y-m-d', $sun)] = true;
        }

        $rows    = [];
        $weekEnd = $this->nextSunday($projectStart);
        $limitTs = strtotime($reportDate);

        while (strtotime($weekEnd) <= $limitTs) {
            $rows[] = [
                'weekEnd'      => $weekEnd,
                'target'       => $this->sampleAt($controlPoints, $weekEnd),
                'hasMilestone' => isset($milestoneWeekEnds[$weekEnd]),
            ];
            $weekEnd = date('Y-m-d', strtotime($weekEnd) + 7 * 86400);
        }

        return $rows;
    }

    /**
     * Returns the next Sunday strictly after the given date.
     * (If the date is already a Sunday, the following Sunday is returned,
     * ensuring a full first week from the start date.)
     */
    private function nextSunday(string $date): string
    {
        $ts  = strtotime($date);
        $dow = (int) date('w', $ts);
        $add = ($dow === 0) ? 7 : (7 - $dow);
        return date('Y-m-d', $ts + $add * 86400);
    }
}
