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
            'notes'   => ['e.g. Tower A', 'Full address', 'e.g. 14.5995', 'e.g. 120.9842', 'Luzon / Visayas / Mindanao (auto if blank)', 'e.g. DITO Telecommunity', 'BUILD-TO-SUIT / RETROFITTING / COLOCATION / CONVERSION', 'Numeric, e.g. 500000', 'Full name, e.g. Juan dela Cruz', 'Full name, e.g. Maria Santos', 'PLANNED / ON-GOING / COMPLETED / HALTED (defaults to PLANNED)', 'e.g. 01/01/2025', 'e.g. 12/31/2025'],
        ],
        'equipment' => [
            'headers' => ['Name*', 'Model/Code', 'Status*', 'Total Units*', 'Location Type', 'Location Address', 'Next Availability (YYYY-MM-DD)'],
            'notes'   => ['e.g. Concrete Mixer', 'e.g. CM-2000', 'Available / In Use / Under Maintenance / Unavailable', 'Numeric, e.g. 5', 'Warehouse / Project Site / Other', 'Full address', 'e.g. 2025-06-01'],
        ],
        'tools' => [
            'headers' => ['Name*', 'Model/Code', 'Status*', 'Total Units*', 'Location Type', 'Location Address', 'Next Availability (YYYY-MM-DD)'],
            'notes'   => ['e.g. Drill Machine', 'e.g. DR-100', 'Available / In Use / Under Maintenance / Unavailable', 'Numeric, e.g. 10', 'Warehouse / Project Site / Other', 'Full address', 'e.g. 2025-06-01'],
        ],
        'manpower' => [
            'headers' => ['First Name*', 'Middle Name', 'Last Name*', 'Role*', 'Specialization', 'Skills (semicolon-separated)', 'Status*'],
            'notes'   => ['e.g. Juan', 'e.g. Reyes (optional)', 'e.g. dela Cruz', 'e.g. Carpenter / Electrician / Mason', 'e.g. Structural works', 'e.g. Welding; Masonry', 'Available / Deployed / On Leave'],
        ],
    ];

    public function __construct()
    {
        $this->firestore = new FirestoreService();
    }

    // GET /import/template?module=projects
    public function template()
    {
        $module = strtolower(trim((string) ($this->request->getGet('module') ?? '')));

        if (!isset(self::TEMPLATES[$module])) {
            return redirect()->back()->with('error', 'Unknown module for template');
        }

        $tpl = self::TEMPLATES[$module];
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(ucfirst($module) . ' Import');

        // Instructions row 1
        $colCount = count($tpl['headers']);
        $lastCol = chr(ord('A') + $colCount - 1);
        $sheet->setCellValue('A1', '* Required fields. Do not modify column headers. Delete this row before importing.');
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['italic' => true, 'color' => ['argb' => 'FF666666']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF3CD']],
        ]);

        // Notes row 2
        $col = 'A';
        foreach ($tpl['notes'] as $note) {
            $sheet->setCellValue($col . '2', $note);
            $col++;
        }
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
            'font' => ['italic' => true, 'color' => ['argb' => 'FF888888']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0F0F0']],
        ]);

        // Header row 3
        $col = 'A';
        foreach ($tpl['headers'] as $h) {
            $sheet->setCellValue($col . '3', $h);
            $col++;
        }
        $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1A3C5E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Sample row 4
        $sheet->setCellValue('A4', '-- sample, delete this row --');

        for ($c = 'A'; $c <= $lastCol; $c++) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $writer = new XlsxWriter($spreadsheet);
        $filename = $module . '_import_template_' . date('Ymd') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
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

        // Find header row (first row containing a required marker like '*')
        $headerRowIdx = null;
        foreach ($rows as $i => $row) {
            foreach ($row as $cell) {
                if (str_contains((string) ($cell ?? ''), '*')) {
                    $headerRowIdx = $i;
                    break 2;
                }
            }
        }

        if ($headerRowIdx === null) {
            return $this->response->setJSON(['success' => false, 'message' => 'Could not find header row. Ensure columns match the template.']);
        }

        $dataRows = array_slice($rows, $headerRowIdx + 1);

        [$imported, $skipped, $error] = match ($module) {
            'projects'  => $this->importProjects($dataRows),
            'equipment' => $this->importResources($dataRows, 'Equipment'),
            'tools'     => $this->importResources($dataRows, 'Tools'),
            'manpower'  => $this->importManpower($dataRows),
            default     => [0, 0, 'Unknown module'],
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

    // Columns (0-indexed per template):
    // 0:Project Name* | 1:Site Address | 2:Latitude | 3:Longitude | 4:Region | 5:Client
    // 6:Project Type* | 7:Budget | 8:Project Manager | 9:Site Engineer | 10:Status
    // 11:Start Date (MM/DD/YYYY) | 12:End Date (MM/DD/YYYY)
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
            $type = trim((string) ($row[6] ?? ''));
            if ($name === '' || $type === '') { $skipped++; continue; }

            $address = trim((string) ($row[1] ?? ''));
            $lat     = is_numeric($row[2] ?? '') ? (float) $row[2] : null;
            $lng     = is_numeric($row[3] ?? '') ? (float) $row[3] : null;
            $region  = trim((string) ($row[4] ?? ''));
            $client  = trim((string) ($row[5] ?? ''));
            $status  = trim((string) ($row[10] ?? '')) ?: 'PLANNED';
            $pmName  = strtolower(trim((string) ($row[8] ?? '')));
            $seName  = strtolower(trim((string) ($row[9] ?? '')));

            $pmId = !empty($pmName) ? ($usersMap[$pmName] ?? null) : null;
            $seId = !empty($seName) ? ($usersMap[$seName] ?? null) : null;

            $db->collection('project')->newDocument()->set([
                'project_name'       => $name,
                'project_type'       => $type,
                'client_name'        => $client,
                'status'             => $status,
                'budget'             => is_numeric($row[7] ?? '') ? (float) $row[7] : 0,
                'start_date'         => $this->parseDateMDY((string) ($row[11] ?? '')),
                'end_date'           => $this->parseDateMDY((string) ($row[12] ?? '')),
                'overall_progress'   => 0,
                'completion_percent' => 0,
                'project_manager_id' => $pmId,
                'site_engineer_id'   => $seId,
                'personnel'          => ['project_manager_id' => $pmId, 'site_engineer_id' => $seId],
                'location'           => [
                    'address'  => $address,
                    'geopoint' => ['lat' => $lat, 'lng' => $lng],
                ],
                'region'     => $region,
                'created_at' => $now,
                'updated_at' => $now,
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

    private function parseDateMDY(string $val): string
    {
        $val = trim($val);
        if ($val === '') return '';
        // Accept MM/DD/YYYY → convert to YYYY-MM-DD
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $val, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[1], (int)$m[2]);
        }
        // Already YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $val)) return substr($val, 0, 10);
        return $val;
    }

    private function importResources(array $rows, string $category): array
    {
        // Columns: Name, ModelCode, Status, TotalUnits, LocType, LocAddress, NextAvail
        $db = $this->firestore->getDb();
        $imported = 0;
        $skipped  = 0;
        $now = time();

        foreach ($rows as $row) {
            $name = trim((string) ($row[0] ?? ''));
            if ($name === '') { $skipped++; continue; }

            $total = is_numeric($row[3] ?? '') ? (int) $row[3] : 0;
            $db->collection('resource')->newDocument()->set([
                'name'             => $name,
                'category'         => $category,
                'model_code'       => trim((string) ($row[1] ?? '')),
                'status'           => trim((string) ($row[2] ?? '')) ?: 'Available',
                'location'         => [
                    'type'    => trim((string) ($row[4] ?? '')),
                    'address' => trim((string) ($row[5] ?? '')),
                ],
                'next_availability' => trim((string) ($row[6] ?? '')),
                'asset_details'    => [
                    'model_number'       => trim((string) ($row[1] ?? '')),
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
                'status'         => trim((string) ($row[6] ?? '')) ?: 'Available',
                'category'       => 'Manpower',
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
            $imported++;
        }

        return [$imported, $skipped, null];
    }
}
