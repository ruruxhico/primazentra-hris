<?php

namespace App\Controllers;

use App\Libraries\EmployeeValidator;
use App\Libraries\FirestoreService;
use App\Libraries\HrisApiException;
use App\Libraries\HrisClient;
use App\Libraries\PayPeriod;
use Google\Cloud\Core\Timestamp;

/**
 * Employee master data. The main app owns the `employees` collection;
 * the Attendance and Payroll apps read it only through /api/v1/employees.
 */
class Employees extends BaseController
{
    private const COLLECTION = 'employees';

    protected FirestoreService $firestore;

    public function __construct()
    {
        $this->firestore = FirestoreService::getInstance();
        helper('access');
    }

    public function index()
    {
        if (! pz_can('view_employees')) {
            return redirect()->to('dashboard');
        }

        $employees = [];
        try {
            foreach ($this->firestore->getDb()->collection(self::COLLECTION)->documents() as $doc) {
                if ($doc->exists()) {
                    $employees[] = $doc->data();
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Employees::index ' . $e->getMessage());
            session()->setFlashdata('error', 'Could not load employees.');
        }
        usort($employees, static fn ($a, $b) => strcmp($a['employee_id'] ?? '', $b['employee_id'] ?? ''));

        // Live figures from the other two apps; the page still works if either is down.
        $period     = PayPeriod::containing(date('Y-m-d'));
        $attendance = null;
        try {
            $body       = HrisClient::attendance()->get('api/v1/dtr', ['from' => $period->start, 'to' => $period->end]);
            $attendance = [];
            foreach (array_merge($body['data'] ?? [], $body['pending'] ?? []) as $r) {
                $a = &$attendance[$r['employee_id']];
                $a ??= ['approved' => 0, 'pending' => 0, 'absent' => 0, 'late' => 0];
                $a[$r['approved'] ? 'approved' : 'pending']++;
                $a['absent'] += $r['is_absent'] ? 1 : 0;
                $a['late']   += $r['late_minutes'];
                unset($a);
            }
        } catch (HrisApiException $e) {
            log_message('warning', 'Employees: attendance unavailable: ' . $e->getMessage());
        }

        $latestPay = null;
        $latestRun = null;
        try {
            $runs = HrisClient::payroll()->get('api/v1/runs')['data'] ?? [];
            $latestPay = [];
            if ($runs !== []) {
                $latestRun = HrisClient::payroll()->get('api/v1/runs/' . $runs[0]['period_id'])['data'];
                foreach ($latestRun['details'] as $d) {
                    $latestPay[$d['employee_id']] = $d['gross_pay'];
                }
            }
        } catch (HrisApiException $e) {
            log_message('warning', 'Employees: payroll unavailable: ' . $e->getMessage());
        }

        return view('employees/index', compact('employees', 'period', 'attendance', 'latestPay', 'latestRun'));
    }

    public function create()
    {
        if (! pz_can('manage_employees')) {
            return redirect()->to('employees');
        }

        if ($this->request->getMethod() === 'POST') {
            $employee = EmployeeValidator::normalise($this->request->getPost());
            $errors   = EmployeeValidator::validate($employee);
            if ($errors) {
                return redirect()->back()->withInput()->with('error', implode(' ', $errors));
            }

            try {
                $db  = $this->firestore->getDb();
                $ids = [];
                foreach ($db->collection(self::COLLECTION)->documents() as $doc) {
                    $ids[] = $doc->id();
                }
                $id  = EmployeeValidator::nextEmployeeId($ids);
                $now = new Timestamp(new \DateTime());

                $db->collection(self::COLLECTION)->document($id)->set($employee + [
                    'employee_id'    => $id,
                    'schema_version' => 1,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                    'created_by'     => session()->get('userId'),
                ]);
            } catch (\Throwable $e) {
                log_message('error', 'Employees::create ' . $e->getMessage());

                return redirect()->back()->withInput()->with('error', 'Could not save the employee.');
            }

            $this->notifyChanged($id);

            return redirect()->to('employees')->with('success', "Employee {$id} added.");
        }

        return view('employees/form', ['pageTitle' => 'Add Employee', 'employee' => null]);
    }

    public function update(string $id)
    {
        if (! pz_can('manage_employees')) {
            return redirect()->to('employees');
        }

        $ref  = $this->firestore->getDb()->collection(self::COLLECTION)->document($id);
        $snap = $ref->snapshot();
        if (! $snap->exists()) {
            return redirect()->to('employees')->with('error', 'Employee not found.');
        }

        if ($this->request->getMethod() === 'POST') {
            $employee = EmployeeValidator::normalise($this->request->getPost());
            $errors   = EmployeeValidator::validate($employee);
            if ($errors) {
                return redirect()->back()->withInput()->with('error', implode(' ', $errors));
            }

            try {
                $ref->set($employee + [
                    'updated_at' => new Timestamp(new \DateTime()),
                    'updated_by' => session()->get('userId'),
                ], ['merge' => true]);
            } catch (\Throwable $e) {
                log_message('error', 'Employees::update ' . $e->getMessage());

                return redirect()->back()->withInput()->with('error', 'Could not save the employee.');
            }

            $this->notifyChanged($id);

            return redirect()->to('employees')->with('success', "Employee {$id} updated.");
        }

        return view('employees/form', ['pageTitle' => 'Edit Employee', 'employee' => $snap->data()]);
    }

    /** One employee with their live attendance (hris-attendance) and payslips (hris-payroll). */
    public function show(string $id)
    {
        if (! pz_can('view_employees')) {
            return redirect()->to('dashboard');
        }

        $snap = $this->firestore->getDb()->collection(self::COLLECTION)->document($id)->snapshot();
        if (! $snap->exists()) {
            return redirect()->to('employees')->with('error', 'Employee not found.');
        }

        $period     = PayPeriod::containing(date('Y-m-d'));
        $dtr        = null;
        $payslips   = null;
        $dtrError   = null;
        $payError   = null;

        try {
            $body = HrisClient::attendance()->get('api/v1/dtr', ['from' => $period->start, 'to' => $period->end, 'employee_id' => $id]);
            $dtr  = array_merge($body['data'] ?? [], $body['pending'] ?? []);
            usort($dtr, static fn ($a, $b) => strcmp($a['date'], $b['date']));
        } catch (HrisApiException $e) {
            $dtrError = $e->getMessage();
        }

        try {
            $payslips = HrisClient::payroll()->get('api/v1/employees/' . rawurlencode($id) . '/payslips')['data'] ?? [];
        } catch (HrisApiException $e) {
            $payError = $e->getMessage();
        }

        return view('employees/show', [
            'employee' => $snap->data(), 'period' => $period,
            'dtr' => $dtr, 'dtrError' => $dtrError, 'payslips' => $payslips, 'payError' => $payError,
        ]);
    }

    /**
     * Payroll drafts depend on salary and schedule, so hris-payroll marks them
     * out of date. Attendance reads employees live and needs no event.
     */
    private function notifyChanged(string $employeeId): void
    {
        if (! HrisClient::payroll()->notify('employee.changed', ['employee_id' => $employeeId])) {
            session()->setFlashdata('warning', 'Payroll could not be notified of this change right now. It will still be picked up before any payroll is finalized.');
        }
    }
}
