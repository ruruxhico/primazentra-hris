<?php

namespace App\Controllers;

use App\Libraries\FirestoreService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class Import extends BaseController
{
    protected FirestoreService $firestore;

    // Maps module → [headers, notes row values]
    private const TEMPLATES = [
        'projects' => [
            'headers' => ['Project Name*', 'Project Type*', 'Client', 'Status', 'Overall Progress (%)', 'Budget (PHP)', 'Region', 'Site Address', 'Latitude', 'Longitude', 'Project Manager', 'Site Engineer', 'Start Date (MM/DD/YYYY)', 'End Date (MM/DD/YYYY)', 'Created At (MM/DD/YYYY)', 'Updated At (MM/DD/YYYY)'],
            'notes'   => ['e.g. Tower A', 'BUILD-TO-SUIT / RETROFITTING / COLOCATION / CONVERSION', 'e.g. DITO Telecommunity', 'Planned / On-going / Completed / Past Deadline / Archived (defaults to Planned)', 'Numeric 0-100, e.g. 25 (defaults to 0)', 'Numeric, e.g. 500000', 'Luzon / Visayas / Mindanao (auto if blank)', 'Full address', 'e.g. 14.5995', 'e.g. 120.9842', 'Full name, e.g. Juan dela Cruz', 'Full name, e.g. Maria Santos', 'e.g. 01/01/2025', 'e.g. 12/31/2025', 'Optional — defaults to import time if blank, e.g. 01/01/2025', 'Optional — defaults to import time if blank, e.g. 01/01/2025'],
        ],
        'equipment' => [
            'headers' => ['Model Name*', 'Total Quantity*', 'Available Quantity', 'Condition: New', 'Condition: Functional', 'Condition: Worn', 'Created At (MM/DD/YYYY)', 'Updated At (MM/DD/YYYY)'],
            'notes'   => ['e.g. Concrete Mixer', 'Numeric, e.g. 10', 'Numeric, defaults to Total Quantity if blank', 'Count of new-condition units, e.g. 3', 'Count of functional-condition units, e.g. 5', 'Count of worn-condition units, e.g. 2', 'Optional — defaults to import time if blank, e.g. 01/01/2025', 'Optional — defaults to import time if blank, e.g. 01/01/2025'],
        ],
        'tools' => [
            'headers' => ['Model Name*', 'Total Quantity*', 'Available Quantity', 'Condition: New', 'Condition: Functional', 'Condition: Worn', 'Created At (MM/DD/YYYY)', 'Updated At (MM/DD/YYYY)'],
            'notes'   => ['e.g. Drill Machine', 'Numeric, e.g. 10', 'Numeric, defaults to Total Quantity if blank', 'Count of new-condition units, e.g. 4', 'Count of functional-condition units, e.g. 6', 'Count of worn-condition units, e.g. 0', 'Optional — defaults to import time if blank, e.g. 01/01/2025', 'Optional — defaults to import time if blank, e.g. 01/01/2025'],
        ],
        'manpower' => [
            'headers' => ['Employee ID', 'First Name*', 'Middle Name', 'Last Name', 'Full Name', 'Role*', 'Specialization', 'Skills', 'Status', 'Assigned Project', 'Location Address', 'Location Type', 'Created At (MM/DD/YYYY)', 'Updated At (MM/DD/YYYY)'],
            'notes'   => ['Optional — auto-generated if blank, e.g. MP-A1B2C3D4', 'e.g. Juan', 'e.g. Reyes (optional)', 'e.g. dela Cruz', 'Optional — auto-built from First/Middle/Last if blank', 'FIELD WORKER / FOREMAN', 'BUILD-TO-SUIT / RETROFITTING / COLOCATION / CONVERSION', 'Semicolon-separated, e.g. Welding; Masonry', 'AVAILABLE / DEPLOYED / ON-LEAVE (defaults to AVAILABLE)', 'Optional — exact project name to assign, e.g. Tower A', 'Full address (optional)', 'Warehouse / Project Site / Other (optional)', 'Optional — defaults to import time if blank, e.g. 01/01/2025', 'Optional — defaults to import time if blank, e.g. 01/01/2025'],
        ],
        'resource_units' => [
            'headers' => ['Serial Number', 'Condition', 'Status', 'Brand', 'Location Address', 'Location Type', 'Created At (MM/DD/YYYY)', 'Updated At (MM/DD/YYYY)'],
            'notes'   => ['Optional — auto-generated if blank', 'New / Functional / Worn / Broken (defaults to New if blank)', 'In-Storage / In-Use / Repair / Transfer Pending (defaults to In-Storage)', 'Optional, e.g. Makita', 'Full address (optional)', 'Warehouse / Project Site / Other (optional)', 'Optional — defaults to import time if blank, e.g. 01/01/2025', 'Optional — defaults to import time if blank, e.g. 01/01/2025'],
        ],
    ];

    public function __construct()
    {
        $this->firestore = FirestoreService::getInstance();
    }

    // GET /import/template?module=projects&format=xlsx|csv
    public function template()
    {
        $module = strtolower(trim((string) ($this->request->getGet('module') ?? '')));
        $format = strtolower(trim((string) ($this->request->getGet('format') ?? 'xlsx')));

        if (!isset(self::TEMPLATES[$module])) {
            return redirect()->back()->with('error', 'Unknown module for template');
        }

        $tpl      = self::TEMPLATES[$module];
        $filename = $module . '_import_template_' . date('Ymd');

        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            header('Cache-Control: max-age=0');
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel compatibility
            fputcsv($out, $tpl['headers']);
            fclose($out);
            exit;
        }

        // XLSX
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(ucfirst($module) . ' Import');

        $colCount = count($tpl['headers']);
        $lastCol  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

        // Instructions row 1
        $sheet->setCellValue('A1', '* Required fields. Do not modify column headers. Delete this row before importing.');
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['italic' => true, 'color' => ['argb' => 'FF666666']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF3CD']],
        ]);

        $coord = fn(int $c, int $r): string =>
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $r;

        // Notes row 2
        $col = 1;
        foreach ($tpl['notes'] as $note) {
            $sheet->setCellValue($coord($col++, 2), $note);
        }
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
            'font' => ['italic' => true, 'color' => ['argb' => 'FF888888']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0F0F0']],
        ]);

        // Header row 3
        $col = 1;
        foreach ($tpl['headers'] as $h) {
            $sheet->setCellValue($coord($col++, 3), $h);
        }
        $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1A3C5E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Sample row 4 — mark date columns as text so Excel won't reformat them
        $sheet->setCellValue('A4', '-- sample, delete this row --');
        // 1-indexed column positions of MM/DD/YYYY fields, per module
        $dateColumns = [
            'projects'  => [13, 14, 15, 16], // Start Date, End Date, Created At, Updated At
            'equipment' => [7, 8],           // Created At, Updated At
            'tools'     => [7, 8],           // Created At, Updated At
            'manpower'  => [13, 14],         // Created At, Updated At
            'resource_units' => [7, 8],      // Created At, Updated At
        ];
        foreach ($dateColumns[$module] ?? [] as $dateCol) {
            $sheet->getStyle($coord($dateCol, 4))
                ->getNumberFormat()
                ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
            $sheet->getCell($coord($dateCol, 4))->setValueExplicit(
                'e.g. 01/15/2025',
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );
        }

        for ($c = 1; $c <= $colCount; $c++) {
            $sheet->getColumnDimension(
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c)
            )->setAutoSize(true);
        }

        $writer = new XlsxWriter($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    // POST /import/process
    public function process()
    {
        $module = strtolower(trim((string) ($this->request->getPost('module') ?? '')));
        if (!isset(self::TEMPLATES[$module])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unknown module for import.']);
        }
        $file = $this->request->getFile('import_file');

        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return $this->response->setJSON(['success' => false, 'message' => 'No valid file uploaded.']);
        }

        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Only .xlsx, .xls, or .csv files are accepted.']);
        }

        try {
            if ($ext === 'csv') {
                $rows = $this->parseCsv($file->getTempName());
            } else {
                $spreadsheet = IOFactory::load($file->getTempName());
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray(null, true, true, false);
            }
        } catch (\Throwable $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Could not read file: ' . $e->getMessage()]);
        }

        // Find header row: the first row whose cells match this module's known
        // template headers exactly (case-insensitive, '*' stripped). Matching
        // against the actual expected headers — rather than counting '*' markers —
        // works regardless of how many fields a given template marks as required.
        $expectedHeaders = array_map(
            static fn($h) => strtolower(trim(rtrim((string) $h, '*'))),
            self::TEMPLATES[$module]['headers']
        );
        $headerRowIdx = null;
        foreach ($rows as $i => $row) {
            $rowNormalized = array_map(
                static fn($cell) => strtolower(trim(rtrim((string) ($cell ?? ''), '*'))),
                array_slice(array_values($row), 0, count($expectedHeaders))
            );
            if ($rowNormalized === $expectedHeaders) {
                $headerRowIdx = $i;
                break;
            }
        }

        if ($headerRowIdx === null) {
            return $this->response->setJSON(['success' => false, 'message' => 'Could not find header row. Ensure columns match the template.']);
        }

        $dataRows = array_values(array_filter(
            array_slice($rows, $headerRowIdx + 1),
            static function (array $row): bool {
                $first = trim((string)($row[0] ?? ''));
                // Drop the template sample row (starts with '--')
                if (str_starts_with($first, '--')) return false;
                // Drop fully-empty rows (Excel trailing blank rows, etc.)
                foreach ($row as $cell) {
                    if (trim((string)($cell ?? '')) !== '') return true;
                }
                return false;
            }
        ));

        [$imported, $skipped, $error] = match ($module) {
            'projects'       => $this->importProjects($dataRows),
            'equipment'      => $this->importResources($dataRows, 'Equipment'),
            'tools'          => $this->importResources($dataRows, 'Tools'),
            'manpower'       => $this->importManpower($dataRows),
            'resource_units' => $this->importResourceUnits(
                $dataRows,
                trim((string)($this->request->getPost('resource_id') ?? ''))
            ),
            default          => [0, 0, 'Unknown module'],
        };

        if ($error) {
            return $this->response->setJSON(['success' => false, 'message' => $error]);
        }

        return $this->response->setJSON([
            'success'  => true,
            'imported' => $imported,
            'skipped'  => $skipped,
            'message'  => "Imported {$imported} record(s)" . ($skipped ? ", skipped {$skipped} empty/invalid row(s)." : '.'),
        ]);
    }

    // ── Importers ────────────────────────────────────────────────────────────

    // Columns (0-indexed): 0:Project Name* | 1:Project Type* | 2:Client | 3:Status
    // 4:Overall Progress (%) | 5:Budget (PHP) | 6:Region | 7:Site Address | 8:Lat | 9:Lng
    // 10:Project Manager | 11:Site Engineer | 12:Start Date | 13:End Date
    // 14:Created At (optional) | 15:Updated At (optional)
    private function importProjects(array $rows): array
    {
        $db = $this->firestore->getDb();
        $imported = 0;
        $skipped  = 0;
        $now = time();

        // Pre-load users for PM/SE name resolution
        $usersMap = [];
        foreach ($db->collection('users')->documents() as $udoc) {
            if (!$udoc->exists()) continue;
            $u = $udoc->data();
            $fullName = strtolower(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')));
            if ($fullName !== '') $usersMap[$fullName] = $udoc->id();
        }

        foreach ($rows as $row) {
            $name = trim((string) ($row[0] ?? ''));
            $type = strtoupper(trim((string) ($row[1] ?? '')));
            if ($name === '' || $type === '') { $skipped++; continue; }

            $client   = trim((string) ($row[2] ?? ''));
            $status   = trim((string) ($row[3] ?? '')) ?: 'Planned';
            $progress = is_numeric($row[4] ?? '') ? min(100, max(0, (float) $row[4])) : 0;
            $budget   = is_numeric($row[5] ?? '') ? (float) $row[5] : 0;
            $region   = trim((string) ($row[6] ?? ''));
            $address  = trim((string) ($row[7] ?? ''));
            $lat      = is_numeric($row[8] ?? '') ? (float) $row[8] : null;
            $lng      = is_numeric($row[9] ?? '') ? (float) $row[9] : null;
            $pmName   = strtolower(trim((string) ($row[10] ?? '')));
            $seName   = strtolower(trim((string) ($row[11] ?? '')));

            $pmId = !empty($pmName) ? ($usersMap[$pmName] ?? null) : null;
            $seId = !empty($seName) ? ($usersMap[$seName] ?? null) : null;

            // Optional: preserve original created/updated timestamps (e.g. data migration).
            // Falls back to import time if the cell is blank or unparseable.
            $createdAt = $this->parseDateCell($row[14] ?? '');
            $updatedAt = $this->parseDateCell($row[15] ?? '');
            $createdTs = $createdAt !== '' ? (strtotime($createdAt) ?: $now) : $now;
            $updatedTs = $updatedAt !== '' ? (strtotime($updatedAt) ?: $now) : $now;

            // Build a real Firestore GeoPoint (matches web-created projects)
            $geopoint = ($lat !== null && $lng !== null)
                ? new \Google\Cloud\Core\GeoPoint($lat, $lng)
                : null;

            $db->collection('project')->newDocument()->set([
                'project_name'       => $name,
                'project_type'       => $type,
                'client_name'        => $client,
                'status'             => $status,
                'budget'             => $budget,
                'start_date'         => $this->parseDateCell($row[12] ?? ''),
                'end_date'           => $this->parseDateCell($row[13] ?? ''),
                'overall_progress'   => $progress,
                'completion_percent' => $progress,
                'project_manager_id' => $pmId,
                'site_engineer_id'   => $seId,
                'personnel'          => ['project_manager_id' => $pmId, 'site_engineer_id' => $seId],
                'location'           => [
                    'address'  => $address,
                    'geopoint' => $geopoint,
                ],
                'region'     => $region,
                'created_at' => $createdTs,
                'updated_at' => $updatedTs,
            ]);
            $imported++;
        }

        return [$imported, $skipped, null];
    }

    // Columns (0-indexed): 0:Serial Number | 1:Brand | 2:Condition
    // Columns: Serial Number, Condition, Status, Brand, Location Address,
    // Location Type, Created At (optional), Updated At (optional)
    private function importResourceUnits(array $rows, string $resourceId): array
    {
        if ($resourceId === '') return [0, 0, 'No resource ID provided.'];

        $db = $this->firestore->getDb();

        $parentSnap = $db->collection('resource')->document($resourceId)->snapshot();
        if (!$parentSnap->exists()) return [0, 0, 'Resource not found.'];

        $imported = 0;
        $skipped  = 0;
        $now = time();

        foreach ($rows as $row) {
            $serial    = trim((string)($row[0] ?? ''));
            $condition = trim((string)($row[1] ?? ''));
            $status    = trim((string)($row[2] ?? ''));
            $brand     = trim((string)($row[3] ?? ''));
            $locAddr   = trim((string)($row[4] ?? ''));
            $locType   = trim((string)($row[5] ?? ''));

            if ($serial === '' && $condition === '' && $status === '' && $brand === '') {
                $skipped++;
                continue;
            }

            if ($serial === '') {
                try {
                    $serial = 'SN-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
                } catch (\Exception $e) {
                    $serial = 'SN-' . strtoupper(substr(md5(uniqid()), 0, 8));
                }
            }

            $validConditions = ['New', 'Functional', 'Worn', 'Broken'];
            if ($condition === '' || !in_array($condition, $validConditions, true)) {
                $condition = 'New';
            }

            $validStatuses = ['In-Storage', 'In-Use', 'Repair', 'Transfer Pending'];
            if ($status === '' || !in_array($status, $validStatuses, true)) {
                $status = 'In-Storage';
            }

            // Optional: preserve original created/updated timestamps (e.g. data migration).
            $createdAt = $this->parseDateCell($row[6] ?? '');
            $updatedAt = $this->parseDateCell($row[7] ?? '');
            $createdTs = $createdAt !== '' ? (strtotime($createdAt) ?: $now) : $now;
            $updatedTs = $updatedAt !== '' ? (strtotime($updatedAt) ?: $now) : $now;

            $db->collection('resource')->document($resourceId)->collection('items')->newDocument()->set([
                'serial_number' => $serial,
                'brand'         => $brand,
                'condition'     => $condition,
                'status'        => $status,
                'location'      => ['type' => $locType, 'address' => $locAddr],
                'created_at'    => $createdTs,
                'updated_at'    => $updatedTs,
            ]);
            $imported++;
        }

        return [$imported, $skipped, null];
    }

    private function parseCsv(string $path): array
    {
        $rows   = [];
        $handle = fopen($path, 'r');
        if (!$handle) return $rows;

        // Strip UTF-8 BOM if present (written by our own CSV export)
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);
        return $rows;
    }

    private function parseDateCell(mixed $val): string
    {
        if ($val === null || $val === '') return '';

        // Excel serial number (PhpSpreadsheet returns floats for unformatted date cells)
        if (is_float($val) || (is_numeric($val) && !str_contains((string)$val, '/'))) {
            $numeric = (float)$val;
            if ($numeric > 1000) { // sanity: real date serials are > 1000
                try {
                    $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($numeric);
                    return $date->format('Y-m-d');
                } catch (\Throwable) {}
            }
        }

        $str = trim((string)$val);
        if ($str === '') return '';

        // MM/DD/YYYY
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $str, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[1], (int)$m[2]);
        }
        // Already YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $str)) return substr($str, 0, 10);

        return $str;
    }

    // Columns: Model Name, Total Quantity, Available Quantity, Condition: New,
    // Condition: Functional, Condition: Worn, Created At (optional), Updated At (optional)
    private function importResources(array $rows, string $category): array
    {
        $db = $this->firestore->getDb();
        $imported = 0;
        $skipped  = 0;
        $now = time();

        $conditionCols = [3 => 'New', 4 => 'Functional', 5 => 'Worn'];

        foreach ($rows as $row) {
            $name  = trim((string) ($row[0] ?? ''));
            $total = is_numeric($row[1] ?? '') ? max(0, (int) $row[1]) : 0;
            if ($name === '') { $skipped++; continue; }

            $available = is_numeric($row[2] ?? '') ? max(0, (int) $row[2]) : $total;

            $counts = [];
            foreach ($conditionCols as $col => $label) {
                $counts[$label] = is_numeric($row[$col] ?? '') ? max(0, (int) $row[$col]) : 0;
            }

            // Optional: preserve original created/updated timestamps (e.g. data migration).
            $createdAt = $this->parseDateCell($row[6] ?? '');
            $updatedAt = $this->parseDateCell($row[7] ?? '');
            $createdTs = $createdAt !== '' ? (strtotime($createdAt) ?: $now) : $now;
            $updatedTs = $updatedAt !== '' ? (strtotime($updatedAt) ?: $now) : $now;

            $db->collection('resource')->newDocument()->set([
                'name'              => $name,
                'category'          => $category,
                'status'            => $available > 0 ? 'Available' : 'Unavailable',
                'location'          => ['type' => '', 'address' => ''],
                'next_availability' => '',
                'asset_details'     => [
                    'total_quantity'     => $total,
                    'available_quantity' => $available,
                    'condition_summary'  => $counts,
                ],
                'created_at' => $createdTs,
                'updated_at' => $updatedTs,
            ]);

            $imported++;
        }

        return [$imported, $skipped, null];
    }

    // Columns: Employee ID, First Name, Middle Name, Last Name, Full Name, Role,
    // Specialization, Skills, Status, Assigned Project, Location Address,
    // Location Type, Created At (optional), Updated At (optional)
    private function importManpower(array $rows): array
    {
        $db = $this->firestore->getDb();
        $imported = 0;
        $skipped  = 0;
        $now = time();

        // Pre-load projects for "Assigned Project" name resolution
        $projectsMap = [];
        foreach ($db->collection('project')->documents() as $pdoc) {
            if (!$pdoc->exists()) continue;
            $pname = strtolower(trim((string) ($pdoc->data()['project_name'] ?? '')));
            if ($pname !== '') $projectsMap[$pname] = $pdoc->id();
        }

        foreach ($rows as $row) {
            $empIdInput  = trim((string) ($row[0] ?? ''));
            $firstName   = trim((string) ($row[1] ?? ''));
            $middleName  = trim((string) ($row[2] ?? ''));
            $lastName    = trim((string) ($row[3] ?? ''));
            $fullNameIn  = trim((string) ($row[4] ?? ''));
            $role        = trim((string) ($row[5] ?? ''));
            if ($firstName === '' || $role === '') { $skipped++; continue; }

            $fullName  = $fullNameIn !== ''
                ? $fullNameIn
                : trim($firstName . ($middleName !== '' ? ' ' . $middleName : '') . ' ' . $lastName);

            $skillsRaw = trim((string) ($row[7] ?? ''));
            $skills    = $skillsRaw !== ''
                ? array_values(array_filter(array_map('trim', explode(';', $skillsRaw))))
                : [];

            if ($empIdInput !== '') {
                $empId = $empIdInput;
            } else {
                try {
                    $empId = 'MP-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
                } catch (\Exception $e) {
                    $empId = 'MP-' . strtoupper(substr(md5(uniqid()), 0, 8));
                }
            }

            $assignedProjectName = strtolower(trim((string) ($row[9] ?? '')));
            $assignedProjectId   = $assignedProjectName !== ''
                ? ($projectsMap[$assignedProjectName] ?? null)
                : null;

            // Optional: preserve original created/updated timestamps (e.g. data migration).
            $createdAt = $this->parseDateCell($row[12] ?? '');
            $updatedAt = $this->parseDateCell($row[13] ?? '');
            $createdTs = $createdAt !== '' ? (strtotime($createdAt) ?: $now) : $now;
            $updatedTs = $updatedAt !== '' ? (strtotime($updatedAt) ?: $now) : $now;

            $db->collection('manpower')->newDocument()->set([
                'name'                => $fullName,
                'employee_id'         => $empId,
                'first_name'          => $firstName,
                'middle_name'         => $middleName !== '' ? $middleName : null,
                'last_name'           => $lastName,
                'role'                => $role,
                'specialization'      => trim((string) ($row[6] ?? '')),
                'skills'              => $skills,
                'status'              => trim((string) ($row[8] ?? '')) ?: 'AVAILABLE',
                'category'            => 'Manpower',
                'assigned_project_id' => $assignedProjectId,
                'location'            => [
                    'address' => trim((string) ($row[10] ?? '')),
                    'type'    => trim((string) ($row[11] ?? '')),
                ],
                'created_at' => $createdTs,
                'updated_at' => $updatedTs,
            ]);
            $imported++;
        }

        return [$imported, $skipped, null];
    }
}
