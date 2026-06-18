<?php

namespace App\Controllers;

use App\Libraries\FirestoreService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Dompdf\Dompdf;
use Dompdf\Options;

class Export extends BaseController
{
    protected FirestoreService $firestore;

    public function __construct()
    {
        $this->firestore = new FirestoreService();
    }

    public function index()
    {
        $module = strtolower(trim((string) ($this->request->getGet('module') ?? '')));
        $format = strtolower(trim((string) ($this->request->getGet('format') ?? 'xlsx')));

        [$headers, $rows, $title] = match ($module) {
            'projects'  => $this->getProjectsData(),
            'equipment' => $this->getEquipmentData(),
            'tools'     => $this->getToolsData(),
            'manpower'  => $this->getManpowerData(),
            'staff'     => $this->getStaffData(),
            default     => [[], [], 'Export'],
        };

        if (empty($headers)) {
            return redirect()->back()->with('error', 'Unknown module: ' . $module);
        }

        return match ($format) {
            'xlsx'  => $this->exportXlsx($headers, $rows, $title),
            'pdf'   => $this->exportPdf($headers, $rows, $title),
            'print' => $this->exportPrint($headers, $rows, $title),
            default => redirect()->back()->with('error', 'Invalid format'),
        };
    }

    // ── Data fetchers ────────────────────────────────────────────────────────

    private function getProjectsData(): array
    {
        $headers = ['Project Name', 'Type', 'Location', 'Status', 'Progress (%)', 'Start Date', 'End Date', 'Budget', 'Project Manager', 'Site Engineer'];
        $rows = [];
        $db = $this->firestore->getDb();

        foreach ($db->collection('project')->documents() as $doc) {
            if (!$doc->exists()) continue;
            $p = $doc->data();
            $loc = is_array($p['location'] ?? null) ? ($p['location']['address'] ?? '') : ($p['location'] ?? '');
            $rows[] = [
                $p['project_name'] ?? '',
                $p['project_type'] ?? '',
                $loc,
                $p['status'] ?? '',
                number_format((float) ($p['overall_progress'] ?? $p['completion_percent'] ?? 0), 2),
                $this->normDate($p['start_date'] ?? ''),
                $this->normDate($p['end_date'] ?? ''),
                $p['budget'] ?? 0,
                $this->resolveUserName($p['personnel']['project_manager_id'] ?? $p['project_manager_id'] ?? null),
                $this->resolveUserName($p['personnel']['site_engineer_id'] ?? $p['site_engineer_id'] ?? null),
            ];
        }

        return [$headers, $rows, 'Projects'];
    }

    private function getEquipmentData(): array
    {
        $headers = ['Name', 'Model/Code', 'Status', 'Total Units', 'Available Units', 'Location Type', 'Location Address', 'Next Availability'];
        $rows = [];
        $db = $this->firestore->getDb();

        foreach ($db->collection('resource')->documents() as $doc) {
            if (!$doc->exists()) continue;
            $r = $doc->data();
            if (($r['category'] ?? '') !== 'Equipment') continue;
            $loc = is_array($r['location'] ?? null) ? $r['location'] : [];
            $ad = is_array($r['asset_details'] ?? null) ? $r['asset_details'] : [];
            $rows[] = [
                $r['name'] ?? '',
                $r['model_code'] ?? ($ad['model_number'] ?? ''),
                $r['status'] ?? '',
                $ad['total_quantity'] ?? 0,
                $ad['available_quantity'] ?? 0,
                $loc['type'] ?? '',
                $loc['address'] ?? '',
                $this->normDate($r['next_availability'] ?? ''),
            ];
        }

        return [$headers, $rows, 'Equipment'];
    }

    private function getToolsData(): array
    {
        $headers = ['Name', 'Model/Code', 'Status', 'Total Units', 'Available Units', 'Location Type', 'Location Address', 'Next Availability'];
        $rows = [];
        $db = $this->firestore->getDb();

        foreach ($db->collection('resource')->documents() as $doc) {
            if (!$doc->exists()) continue;
            $r = $doc->data();
            if (($r['category'] ?? '') !== 'Tools') continue;
            $loc = is_array($r['location'] ?? null) ? $r['location'] : [];
            $ad = is_array($r['asset_details'] ?? null) ? $r['asset_details'] : [];
            $rows[] = [
                $r['name'] ?? '',
                $r['model_code'] ?? ($ad['model_number'] ?? ''),
                $r['status'] ?? '',
                $ad['total_quantity'] ?? 0,
                $ad['available_quantity'] ?? 0,
                $loc['type'] ?? '',
                $loc['address'] ?? '',
                $this->normDate($r['next_availability'] ?? ''),
            ];
        }

        return [$headers, $rows, 'Tools'];
    }

    private function getManpowerData(): array
    {
        $headers = ['Employee ID', 'First Name', 'Last Name', 'Role', 'Specialization', 'Status', 'Contact Number', 'Skills'];
        $rows = [];
        $db = $this->firestore->getDb();

        foreach ($db->collection('manpower')->documents() as $doc) {
            if (!$doc->exists()) continue;
            $m = $doc->data();
            $md = is_array($m['manpower_details'] ?? null) ? $m['manpower_details'] : [];
            $skills = is_array($m['skills'] ?? ($md['skills'] ?? null)) ? implode(', ', $m['skills'] ?? $md['skills']) : '';
            $rows[] = [
                $m['employee_id'] ?? ($md['manpower_id'] ?? ''),
                $m['first_name'] ?? ($md['first_name'] ?? ''),
                $m['last_name'] ?? ($md['last_name'] ?? ''),
                $m['role'] ?? ($md['role'] ?? ''),
                $m['specialization'] ?? ($md['specialization'] ?? ''),
                $m['status'] ?? ($md['status'] ?? ''),
                $m['contact_number'] ?? ($md['contact_number'] ?? ''),
                $skills,
            ];
        }

        return [$headers, $rows, 'Manpower'];
    }

    private function getStaffData(): array
    {
        $headers = ['Employee ID', 'First Name', 'Last Name', 'Email', 'Role', 'Status'];
        $rows = [];
        $db = $this->firestore->getDb();

        foreach ($db->collection('users')->documents() as $doc) {
            if (!$doc->exists()) continue;
            $s = $doc->data();
            $rows[] = [
                $s['employee_id'] ?? '',
                $s['first_name'] ?? '',
                $s['last_name'] ?? '',
                $s['email'] ?? '',
                $s['role'] ?? '',
                $s['status'] ?? '',
            ];
        }

        return [$headers, $rows, 'Staff'];
    }

    // ── Exporters ────────────────────────────────────────────────────────────

    private function exportXlsx(array $headers, array $rows, string $title)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($title);

        // Header row
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $col++;
        }

        // Header styling
        $lastCol = chr(ord('A') + count($headers) - 1);
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1A3C5E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']]],
        ]);

        // Data rows
        foreach ($rows as $i => $row) {
            $rowNum = $i + 2;
            $col = 'A';
            foreach ($row as $cell) {
                $sheet->setCellValue($col . $rowNum, $cell);
                $col++;
            }
            // Alternating row color
            if ($i % 2 === 0) {
                $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF5F8FB');
            }
        }

        // Auto width
        for ($c = 'A'; $c <= $lastCol; $c++) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $writer = new XlsxWriter($spreadsheet);
        $filename = strtolower($title) . '_' . date('Ymd') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    private function exportPdf(array $headers, array $rows, string $title)
    {
        $html = $this->buildPrintHtml($headers, $rows, $title, forPdf: true);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = strtolower($title) . '_' . date('Ymd') . '.pdf';
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }

    private function exportPrint(array $headers, array $rows, string $title)
    {
        return $this->response->setBody(
            $this->buildPrintHtml($headers, $rows, $title, forPdf: false)
        )->setContentType('text/html');
    }

    private function buildPrintHtml(array $headers, array $rows, string $title, bool $forPdf): string
    {
        $th = '';
        foreach ($headers as $h) {
            $th .= '<th>' . esc($h) . '</th>';
        }

        $tbody = '';
        foreach ($rows as $i => $row) {
            $bg = $i % 2 === 0 ? '#fff' : '#f5f8fb';
            $tbody .= "<tr style=\"background:{$bg}\">";
            foreach ($row as $cell) {
                $tbody .= '<td>' . esc((string) $cell) . '</td>';
            }
            $tbody .= '</tr>';
        }

        $printBtn = $forPdf ? '' : '<button onclick="window.print()" style="margin-bottom:16px;padding:8px 20px;background:#1a3c5e;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:14px;">Print</button>';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>{$title}</title>
<style>
  body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; color: #222; }
  h2 { color: #1a3c5e; margin-bottom: 4px; }
  p.generated { color: #888; font-size: 10px; margin-bottom: 12px; }
  table { border-collapse: collapse; width: 100%; }
  th { background: #1a3c5e; color: #fff; padding: 6px 8px; text-align: left; white-space: nowrap; }
  td { padding: 5px 8px; border-bottom: 1px solid #e0e0e0; }
  @media print { button { display: none !important; } }
</style>
</head>
<body>
{$printBtn}
<h2>{$title}</h2>
<p class="generated">Generated: {$this->now()}</p>
<table>
  <thead><tr>{$th}</tr></thead>
  <tbody>{$tbody}</tbody>
</table>
</body>
</html>
HTML;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function resolveUserName($userId): string
    {
        if (empty($userId)) return '';
        $id = is_string($userId) ? preg_replace('#^.*/#', '', $userId) : '';
        if ($id === '') return '';
        try {
            $doc = $this->firestore->getDb()->collection('users')->document($id)->snapshot();
            if ($doc->exists()) {
                $d = $doc->data();
                $name = trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? ''));
                return $name ?: ($d['email'] ?? $id);
            }
        } catch (\Throwable $e) {}
        return $id;
    }

    private function normDate($val): string
    {
        if (empty($val)) return '';
        if (is_object($val) && method_exists($val, 'get')) {
            try { return $val->get()->format('Y-m-d'); } catch (\Throwable $e) {}
        }
        if (is_numeric($val)) return date('Y-m-d', (int) $val);
        if (is_string($val)) return substr($val, 0, 10);
        return '';
    }

    private function now(): string
    {
        return date('F j, Y g:i A');
    }
}
