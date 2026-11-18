<?php

use App\Libraries\EmployeeValidator as V;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Employee master data rules (M4 Integration Point 2).
 *
 * @internal
 */
final class EmployeeValidatorTest extends CIUnitTestCase
{
    private static function valid(array $over = []): array
    {
        return V::normalise($over + ['first_name' => 'Ana', 'last_name' => 'Cruz', 'position' => 'Mason',
            'schedule_type' => '8hrs', 'monthly_salary' => '20000']);
    }

    public function testValidEmployeePasses(): void
    {
        $e = self::valid();

        $this->assertSame([], V::validate($e));
        $this->assertSame(20000.0, $e['monthly_salary']);
        $this->assertSame('active', $e['status']);
    }

    public function testSalaryMustBePositive(): void
    {
        $this->assertArrayHasKey('monthly_salary', V::validate(self::valid(['monthly_salary' => '0'])));
        $this->assertArrayHasKey('monthly_salary', V::validate(self::valid(['monthly_salary' => '-5'])));
        $this->assertArrayHasKey('monthly_salary', V::validate(self::valid(['monthly_salary' => 'abc'])));
    }

    public function testScheduleMustBeEightOrNinePointSixHours(): void
    {
        $this->assertArrayHasKey('schedule_type', V::validate(self::valid(['schedule_type' => '10hrs'])));
        $this->assertSame([], V::validate(self::valid(['schedule_type' => '9.6hrs'])));
    }

    public function testNamesAreRequired(): void
    {
        $e = V::validate(self::valid(['first_name' => ' ', 'last_name' => '']));

        $this->assertArrayHasKey('first_name', $e);
        $this->assertArrayHasKey('last_name', $e);
    }

    public function testNextEmployeeIdFollowsHighestExisting(): void
    {
        $this->assertSame('EMP001', V::nextEmployeeId([]));
        $this->assertSame('EMP008', V::nextEmployeeId(['EMP001', 'EMP007', 'other']));
    }
}
