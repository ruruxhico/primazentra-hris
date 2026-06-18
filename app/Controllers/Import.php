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
            'headers' => ['Project Name*', 'Type*', 'Location Address', 'Status*', 'Start Date (YYYY-MM-DD)', 'End Date (YYYY-MM-DD)', 'Budget'],
            'notes'   => ['e.g. Tower A', 'Residential / Commercial / Industrial', 'Full address', 'Planned / On Going / Completed / Halted', 'e.g. 2025-01-01', 'e.g. 2025-12-31', 'Numeric, e.g. 500000'],
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
            'headers' => ['First Name*', 'Last Name*', 'Role*', 'Specialization', 'Status*', 'Contact Number', 'Skills (comma-separated)'],
            'notes'   => ['e.g. Juan', 'e.g. dela Cruz', 'e.g. Carpenter / Electrician / Mason', 'e.g. Structural works', 'Available / Deployed / On Leave', 'e.g. 09171234567', 'e.g. Welding, Masonry'],
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
        if (!in_array($ext, ['xlsx', 'xls'], true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Only .xlsx or .xls files are accepted.']);
        }

        try {
            $spreadsheet = IOFactory::load($file->getTempName());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);
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

    private function importProjects(array $rows): array
    {
        // Columns (0-indexed): Name, Type, Location, Status, StartDate, EndDate, Budget
        $db = $this->firestore->getDb();
        $imported = 0;
        $skipped  = 0;
        $now = time();

        foreach ($rows as $row) {
            $name = trim((string) ($row[0] ?? ''));
            $type = trim((string) ($row[1] ?? ''));
            if ($name === '' || $type === '') { $skipped++; continue; }

            $status = trim((string) ($row[3] ?? '')) ?: 'Planned';
            $db->collection('project')->newDocument()->set([
                'project_name'       => $name,
                'project_type'       => $type,
                'location'           => ['address' => trim((string) ($row[2] ?? ''))],
                'status'             => $status,
                'start_date'         => trim((string) ($row[4] ?? '')),
                'end_date'           => trim((string) ($row[5] ?? '')),
                'budget'             => is_numeric($row[6] ?? '') ? (float) $row[6] : 0,
                'overall_progress'   => 0,
                'completion_percent' => 0,
                'personnel'          => ['project_manager_id' => null, 'site_engineer_id' => null],
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);
            $imported++;
        }

        return [$imported, $skipped, null];
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
        // Columns: FirstName, LastName, Role, Specialization, Status, Contact, Skills
        $db = $this->firestore->getDb();
        $imported = 0;
        $skipped  = 0;
        $now = time();

        foreach ($rows as $row) {
            $firstName = trim((string) ($row[0] ?? ''));
            $lastName  = trim((string) ($row[1] ?? ''));
            $role      = trim((string) ($row[2] ?? ''));
            if ($firstName === '' || $role === '') { $skipped++; continue; }

            $skillsRaw = trim((string) ($row[6] ?? ''));
            $skills = $skillsRaw !== '' ? array_map('trim', explode(',', $skillsRaw)) : [];

            $db->collection('manpower')->newDocument()->set([
                'first_name'      => $firstName,
                'last_name'       => $lastName,
                'role'            => $role,
                'specialization'  => trim((string) ($row[3] ?? '')),
                'status'          => trim((string) ($row[4] ?? '')) ?: 'Available',
                'contact_number'  => trim((string) ($row[5] ?? '')),
                'skills'          => $skills,
                'category'        => 'Manpower',
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
            $imported++;
        }

        return [$imported, $skipped, null];
    }
}
