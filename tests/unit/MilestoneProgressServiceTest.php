<?php

use App\Libraries\MilestoneProgressService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit tests for MilestoneProgressService::computeCurve() and sampleAt().
 *
 * @internal
 */
final class MilestoneProgressServiceTest extends CIUnitTestCase
{
    private MilestoneProgressService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new MilestoneProgressService();
    }

    // ── computeCurve ──────────────────────────────────────────────────────────

    public function testAllDatedMilestones(): void
    {
        $milestones = [
            ['order' => 1, 'weight' => 30, 'targetDate' => '2025-03-31'],
            ['order' => 2, 'weight' => 40, 'targetDate' => '2025-06-30'],
            ['order' => 3, 'weight' => 30, 'targetDate' => '2025-09-30'],
        ];

        $result = $this->svc->computeCurve($milestones, '2025-01-01', '2025-09-30');

        // start anchor + 3 dated milestones
        $this->assertCount(4, $result['controlPoints']);
        $this->assertSame(0, $result['undatedCount']);
        $this->assertTrue($result['hasTerminalAnchor']);
        $this->assertNull($result['warning']);

        $this->assertEqualsWithDelta(0.0,   $result['controlPoints'][0]['cumWeight'], 0.001);
        $this->assertEqualsWithDelta(30.0,  $result['controlPoints'][1]['cumWeight'], 0.001);
        $this->assertEqualsWithDelta(70.0,  $result['controlPoints'][2]['cumWeight'], 0.001);
        $this->assertEqualsWithDelta(100.0, $result['controlPoints'][3]['cumWeight'], 0.001);
    }

    public function testOneUndatedMiddleMilestoneAbsorbed(): void
    {
        $milestones = [
            ['order' => 1, 'weight' => 30, 'targetDate' => '2025-03-31'],
            ['order' => 2, 'weight' => 20, 'targetDate' => null],          // absorbed
            ['order' => 3, 'weight' => 50, 'targetDate' => '2025-09-30'],
        ];

        $result = $this->svc->computeCurve($milestones, '2025-01-01', '2025-09-30');

        // start + M1 + M3 (M2 is absorbed into M3's ramp)
        $this->assertCount(3, $result['controlPoints']);
        $this->assertSame(1, $result['undatedCount']);
        $this->assertTrue($result['hasTerminalAnchor']);

        $this->assertEqualsWithDelta(30.0,  $result['controlPoints'][1]['cumWeight'], 0.001);
        $this->assertEqualsWithDelta(100.0, $result['controlPoints'][2]['cumWeight'], 0.001);
    }

    public function testConsecutiveUndatedMilestonesAbsorbed(): void
    {
        $milestones = [
            ['order' => 1, 'weight' => 20, 'targetDate' => '2025-03-31'],
            ['order' => 2, 'weight' => 15, 'targetDate' => null],
            ['order' => 3, 'weight' => 15, 'targetDate' => null],
            ['order' => 4, 'weight' => 50, 'targetDate' => '2025-09-30'],
        ];

        $result = $this->svc->computeCurve($milestones, '2025-01-01', '2025-09-30');

        // start + M1 + M4; M2 and M3 absorbed
        $this->assertCount(3, $result['controlPoints']);
        $this->assertSame(2, $result['undatedCount']);
        $this->assertTrue($result['hasTerminalAnchor']);

        $this->assertEqualsWithDelta(20.0,  $result['controlPoints'][1]['cumWeight'], 0.001);
        $this->assertEqualsWithDelta(100.0, $result['controlPoints'][2]['cumWeight'], 0.001);
    }

    public function testUndatedFirstMilestoneAbsorbedIntoRampFromStart(): void
    {
        $milestones = [
            ['order' => 1, 'weight' => 25, 'targetDate' => null],         // undated first
            ['order' => 2, 'weight' => 75, 'targetDate' => '2025-09-30'],
        ];

        $result = $this->svc->computeCurve($milestones, '2025-01-01', null);

        // start + M2 only (M1 weight rolled into M2's cumulative)
        $this->assertCount(2, $result['controlPoints']);
        $this->assertSame(1, $result['undatedCount']);
        $this->assertTrue($result['hasTerminalAnchor']);

        // cumWeight at M2 = 25 + 75 = 100
        $this->assertEqualsWithDelta(100.0, $result['controlPoints'][1]['cumWeight'], 0.001);
    }

    public function testUndatedFinalMilestoneFallsBackToProjectEndDate(): void
    {
        $milestones = [
            ['order' => 1, 'weight' => 60, 'targetDate' => '2025-06-30'],
            ['order' => 2, 'weight' => 40, 'targetDate' => null],         // undated final
        ];

        $result = $this->svc->computeCurve($milestones, '2025-01-01', '2025-12-31');

        // start + M1 + project-end fallback
        $this->assertCount(3, $result['controlPoints']);
        $this->assertSame(1, $result['undatedCount']);
        $this->assertTrue($result['hasTerminalAnchor']);
        $this->assertNotNull($result['warning']);

        $this->assertSame('2025-12-31', $result['controlPoints'][2]['date']);
        $this->assertEqualsWithDelta(100.0, $result['controlPoints'][2]['cumWeight'], 0.001);
    }

    public function testUndatedFinalWithNoProjectEndDateSurfacesWarning(): void
    {
        $milestones = [
            ['order' => 1, 'weight' => 60, 'targetDate' => '2025-06-30'],
            ['order' => 2, 'weight' => 40, 'targetDate' => null],
        ];

        $result = $this->svc->computeCurve($milestones, '2025-01-01', null);

        $this->assertFalse($result['hasTerminalAnchor']);
        $this->assertNotNull($result['warning']);
        $this->assertStringContainsString('100%', $result['warning']);
    }

    // ── sampleAt ─────────────────────────────────────────────────────────────

    public function testSampleBeforeProjectStartReturnsZero(): void
    {
        $cps = [
            ['date' => '2025-01-01', 'cumWeight' => 0.0],
            ['date' => '2025-12-31', 'cumWeight' => 100.0],
        ];
        $this->assertEqualsWithDelta(0.0, $this->svc->sampleAt($cps, '2024-06-01'), 0.001);
    }

    public function testSampleAfterFinalPointReturnsFinalWeight(): void
    {
        $cps = [
            ['date' => '2025-01-01', 'cumWeight' => 0.0],
            ['date' => '2025-12-31', 'cumWeight' => 100.0],
        ];
        $this->assertEqualsWithDelta(100.0, $this->svc->sampleAt($cps, '2026-06-01'), 0.001);
    }

    public function testSampleAtExactMidpointIsLinearlyInterpolated(): void
    {
        // Two-point curve: 0% on day 0, 100% on day 100
        $start = '2025-01-01';
        $end   = date('Y-m-d', strtotime($start) + 100 * 86400);
        $mid   = date('Y-m-d', strtotime($start) + 50 * 86400);

        $cps = [
            ['date' => $start, 'cumWeight' => 0.0],
            ['date' => $end,   'cumWeight' => 100.0],
        ];

        $this->assertEqualsWithDelta(50.0, $this->svc->sampleAt($cps, $mid), 0.1);
    }

    public function testSampleWithMultipleSegmentsPicksCorrectOne(): void
    {
        $cps = [
            ['date' => '2025-01-01', 'cumWeight' => 0.0],
            ['date' => '2025-04-01', 'cumWeight' => 30.0],
            ['date' => '2025-07-01', 'cumWeight' => 70.0],
            ['date' => '2025-10-01', 'cumWeight' => 100.0],
        ];

        // Exact control point
        $this->assertEqualsWithDelta(70.0, $this->svc->sampleAt($cps, '2025-07-01'), 0.001);

        // Between second and third segment: should be between 30 and 70
        $mid = '2025-05-15';
        $val = $this->svc->sampleAt($cps, $mid);
        $this->assertGreaterThan(30.0, $val);
        $this->assertLessThan(70.0, $val);
    }
}
