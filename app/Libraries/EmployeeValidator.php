<?php

namespace App\Libraries;

/**
 * Validation rules for employee master data (Integration Point 2 in the
 * M4 integration specification). Pure PHP so it can be unit tested without
 * Firestore.
 */
class EmployeeValidator
{
    public const SCHEDULE_TYPES = ['8hrs', '9.6hrs'];
    public const STATUSES       = ['active', 'inactive'];

    /**
     * Normalises raw form/API input into the stored shape.
     */
    public static function normalise(array $input): array
    {
        return [
            'first_name'     => trim((string) ($input['first_name'] ?? '')),
            'last_name'      => trim((string) ($input['last_name'] ?? '')),
            'position'       => trim((string) ($input['position'] ?? '')),
            'schedule_type'  => trim((string) ($input['schedule_type'] ?? '')),
            'monthly_salary' => is_numeric($input['monthly_salary'] ?? null) ? round((float) $input['monthly_salary'], 2) : null,
            'status'         => trim((string) ($input['status'] ?? 'active')) ?: 'active',
        ];
    }

    /**
     * @return array<string, string> field => error message; empty when valid
     */
    public static function validate(array $employee): array
    {
        $errors = [];

        if (($employee['first_name'] ?? '') === '') {
            $errors['first_name'] = 'First name is required.';
        }
        if (($employee['last_name'] ?? '') === '') {
            $errors['last_name'] = 'Last name is required.';
        }
        if (! in_array($employee['schedule_type'] ?? '', self::SCHEDULE_TYPES, true)) {
            $errors['schedule_type'] = 'Schedule must be 8hrs or 9.6hrs.';
        }
        $salary = $employee['monthly_salary'] ?? null;
        if ((! is_float($salary) && ! is_int($salary)) || $salary <= 0) {
            $errors['monthly_salary'] = 'Monthly salary must be greater than 0.';
        }
        if (! in_array($employee['status'] ?? '', self::STATUSES, true)) {
            $errors['status'] = 'Status must be active or inactive.';
        }

        return $errors;
    }

    /**
     * Next sequential ID, e.g. ["EMP001","EMP007"] -> "EMP008".
     *
     * @param list<string> $existingIds
     */
    public static function nextEmployeeId(array $existingIds): string
    {
        $max = 0;
        foreach ($existingIds as $id) {
            if (preg_match('/^EMP(\d+)$/', $id, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return sprintf('EMP%03d', $max + 1);
    }
}
