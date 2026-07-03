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
            'headers' => ['Project Name*', 'Site Address', 'Latitude', 'Longitude', 'Region', 'Client', 'Project Type*', 'Budget', 'Project Manager', 'Site Engineer', 'Status', 'Start Date (MM/DD/YYYY)', 'End Date (MM/DD/YYYY)'],
            'notes'   => ['e.g. Tower A', 'Full address', 'e.g. 14.5995', 'e.g. 120.9842', 'Luzon / Visayas / Mindanao (auto if blank)', 'e.g. DITO Telecommunity', 'BUILD-TO-SUIT / RETROFITTING / COLOCATION / CONVERSION', 'Numeric, e.g. 500000', 'Full name, e.g. Juan dela Cruz', 'Full name, e.g. Maria Santos', 'Planned / On-going / Completed / Past Deadline / Archived (defaults to Planned)', 'e.g. 01/01/2025', 'e.g. 12/31/2025'],
        ],
        'equipment' => [
            'headers' => ['Name*', 'Status*', 'Total Units*', 'Location Type', 'Location Address', 'Next Availability (YYYY-MM-DD)'],
            'notes'   => ['e.g. Concrete Mixer', 'Available / In Use / Under Maintenance / Unavailable', 'Numeric, e.g. 5', 'Warehouse / Project Site / Other', 'Full address', 'e.g. 2025-06-01'],
        ],
        'tools' => [
            'headers' => ['Name*', 'Status*', 'Total Units*', 'Location Type', 'Location Address', 'Next Availability (YYYY-MM-DD)'],
            'notes'   => ['e.g. Drill Machine', 'Available / In Use / Under Maintenance / Unavailable', 'Numeric, e.g. 10', 'Warehouse / Project Site / Other', 'Full address', 'e.g. 2025-06-01'],
        ],
        'manpower' => [
            'headers' => ['First Name*', 'Middle Name', 'Last Name*', 'Role*', 'Specialization', 'Skills (semicolon-separated)', 'Status*'],
            'notes'   => ['e.g. Juan', 'e.g. Reyes (optional)', 'e.g. dela Cruz', 'FIELD WORKER / FOREMAN', 'BUILD-TO-SUIT / RETROFITTING / COLOCATION / CONVERSION', 'e.g. Welding; Masonry', 'AVAILABLE / DEPLOYED / ON-LEAVE'],
        ],
        'resource_units' => [
            'headers' => ['Serial Number', 'Brand', 'Condition'],
            'notes'   => ['Optional — auto-generated if blank', 'Optional, e.g. Makita', 'New / Functional / Worn / Broken (defaults to New if blank)'],
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
        if ($module === 'projects') {
            // Force date columns (L=12, M=13) to text format so MM/DD/YYYY stays intact
            foreach ([12, 13] as $dateCol) {
                $sheet->getStyle($coord($dateCol, 4))
                    ->getNumberFormat()
                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
                $sheet->getCell($coord($dateCol, 4))->setValueExplicit(
                    'e.g. 01/15/2025',
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                );
            }
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

        // Find header row: first row where at least 2 cells contain '*'.
        // This skips the single-cell instruction row ("* Required fields…") at the top
        // of the XLSX template which also contains a '*' but is not the header row.
        $headerRowIdx = null;
        foreach ($rows as $i => $row) {
            $starCount = 0;
            foreach ($row as $cell) {
                if (str_contains((string) ($cell ?? ''), '*')) {
                    $starCount++;
                }
            }
            if ($starCount >= 2) {
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

    // Columns (0-indexed): 0:Project Name* | 1:Site Address | 2:Lat | 3:Lng | 4:Region
    // 5:Client | 6:Project Type* | 7:Budget | 8:Project Manager | 9:Site Engineer
    // 10:Status | 11:Start Date | 12:End Date
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
            $type = strtoupper(trim((string) ($row[6] ?? '')));
            if ($name === '' || $type === '') { $skipped++; continue; }

            $address = trim((string) ($row[1] ?? ''));
            $lat     = is_numeric($row[2] ?? '') ? (float) $row[2] : null;
            $lng     = is_numeric($row[3] ?? '') ? (float) $row[3] : null;
            $region  = trim((string) ($row[4] ?? ''));
            $client  = trim((string) ($row[5] ?? ''));
            $budget  = is_numeric($row[7] ?? '') ? (float) $row[7] : 0;
            $pmName  = strtolower(trim((string) ($row[8] ?? '')));
            $seName  = strtolower(trim((string) ($row[9] ?? '')));
            $status  = trim((string) ($row[10] ?? '')) ?: 'Planned';

            $pmId = !empty($pmName) ? ($usersMap[$pmName] ?? null) : null;
            $seId = !empty($seName) ? ($usersMap[$seName] ?? null) : null;

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
                'start_date'         => $this->parseDateCell($row[11] ?? ''),
                'end_date'           => $this->parseDateCell($row[12] ?? ''),
                'overall_progress'   => 0,
                'completion_percent' => 0,
                'project_manager_id' => $pmId,
                'site_engineer_id'   => $seId,
                'personnel'          => ['project_manager_id' => $pmId, 'site_engineer_id' => $seId],
                'location'           => [
                    'address'  => $address,
                    'geopoint' => $geopoint,
                ],
                'region'     => $region,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $imported++;
        }

        return [$imported, $skipped, null];
    }

    // Columns (0-indexed): 0:Serial Number | 1:Brand | 2:Condition
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
            $brand     = trim((string)($row[1] ?? ''));
            $condition = trim((string)($row[2] ?? ''));

            if ($serial === '' && $brand === '' && $condition === '') {
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

            $db->collection('resource')->document($resourceId)->collection('items')->newDocument()->set([
                'serial_number' => $serial,
                'brand'         => $brand,
                'condition'     => $condition,
                'status'        => 'In-Storage',
                'location'      => ['type' => '', 'address' => ''],
                'created_at'    => $now,
                'updated_at'    => $now,
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

    private function importResources(array $rows, string $category): array
    {
        // Columns: Name, Status, TotalUnits, LocType, LocAddress, NextAvail
        $db = $this->firestore->getDb();
        $imported = 0;
        $skipped  = 0;
        $now = time();

        foreach ($rows as $row) {
            $name = trim((string) ($row[0] ?? ''));
            if ($name === '') { $skipped++; continue; }

            $total = is_numeric($row[2] ?? '') ? (int) $row[2] : 0;
            $db->collection('resource')->newDocument()->set([
                'name'             => $name,
                'category'         => $category,
                'status'           => trim((string) ($row[1] ?? '')) ?: 'Available',
                'location'         => [
                    'type'    => trim((string) ($row[3] ?? '')),
                    'address' => trim((string) ($row[4] ?? '')),
                ],
                'next_availability' => trim((string) ($row[5] ?? '')),
                'asset_details'    => [
                    'total_quantity'     => $total,
                    'available_quantity' => $total,
                    'condition_summary'  => [],
                ],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $imported++;
        }

        return [$imported, $skipped, null];
    }

    private function importManpower(array $rows): array
    {
        // Columns: FirstName, MiddleName, LastName, Role, Specialization, Skills, Status
        $db = $this->firestore->getDb();
        $imported = 0;
        $skipped  = 0;
        $now = time();

        foreach ($rows as $row) {
            $firstName  = trim((string) ($row[0] ?? ''));
            $middleName = trim((string) ($row[1] ?? ''));
            $lastName   = trim((string) ($row[2] ?? ''));
            $role       = trim((string) ($row[3] ?? ''));
            if ($firstName === '' || $role === '') { $skipped++; continue; }

            $fullName  = trim($firstName . ($middleName !== '' ? ' ' . $middleName : '') . ' ' . $lastName);
            $skillsRaw = trim((string) ($row[5] ?? ''));
            $skills    = $skillsRaw !== ''
                ? array_values(array_filter(array_map('trim', explode(';', $skillsRaw))))
                : [];

            try {
                $empId = 'MP-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            } catch (\Exception $e) {
                $empId = 'MP-' . strtoupper(substr(md5(uniqid()), 0, 8));
            }

            $db->collection('manpower')->newDocument()->set([
                'name'           => $fullName,
                'employee_id'    => $empId,
                'first_name'     => $firstName,
                'middle_name'    => $middleName !== '' ? $middleName : null,
                'last_name'      => $lastName,
                'role'           => $role,
                'specialization' => trim((string) ($row[4] ?? '')),
                'skills'         => $skills,
                'status'         => trim((string) ($row[6] ?? '')) ?: 'AVAILABLE',
                'category'       => 'Manpower',
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
            $imported++;
        }

        return [$imported, $skipped, null];
    }
}
