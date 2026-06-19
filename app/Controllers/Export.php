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
        $module  = strtolower(trim((string) ($this->request->getGet('module') ?? '')));
        $format  = strtolower(trim((string) ($this->request->getGet('format') ?? 'xlsx')));
        $subtype = strtolower(trim((string) ($this->request->getGet('subtype') ?? 'models')));

        [$headers, $rows, $title] = match ($module) {
            'projects'  => $this->getProjectsData(),
            'equipment' => $this->getResourceData('Equipment', $subtype),
            'tools'     => $this->getResourceData('Tools', $subtype),
            'manpower'  => $this->getManpowerData(),
            'staff'     => $this->getStaffData(),
            default     => [[], [], 'Export'],
        };

        if (empty($headers)) {
            return redirect()->back()->with('error', 'Unknown module: ' . $module);
        }

        return match ($format) {
            'xlsx'  => $this->exportXlsx($headers, $rows, $title),
            'csv'   => $this->exportCsv($headers, $rows, $title),
            'pdf'   => $this->exportPdf($headers, $rows, $title),
            'print' => $this->exportPrint($headers, $rows, $title),
            default => redirect()->back()->with('error', 'Invalid format'),
        };
    }

    // ── Data fetchers ────────────────────────────────────────────────────────

    private function getProjectsData(): array
    {
        $headers = [
            'Project ID', 'Project Name', 'Project Type', 'Client', 'Status',
            'Overall Progress (%)', 'Budget (PHP)', 'Region', 'Site Address',
            'Latitude', 'Longitude', 'Project Manager', 'Site Engineer',
            'Start Date (MM/DD/YYYY)', 'End Date (MM/DD/YYYY)', 'Created At', 'Updated At',
        ];
        $rows = [];
        $db = $this->firestore->getDb();

        foreach ($db->collection('project')->documents() as $doc) {
            if (!$doc->exists()) continue;
            $p   = $doc->data();
            $loc = is_array($p['location'] ?? null) ? $p['location'] : [];
            $geo = is_array($loc['geopoint'] ?? null) ? $loc['geopoint'] : [];
            $addr = $loc['address'] ?? '';
            $rows[] = [
                $doc->id(),
                $p['project_name'] ?? '',
                $p['project_type'] ?? '',
                $p['client_name'] ?? '',
                $p['status'] ?? '',
                number_format((float) ($p['overall_progress'] ?? $p['completion_percent'] ?? 0), 2),
                $p['budget'] ?? 0,
                $this->resolveRegion($addr),
                $addr,
                $geo['lat'] ?? '',
                $geo['lng'] ?? '',
                $this->resolveUserName($p['personnel']['project_manager_id'] ?? $p['project_manager_id'] ?? null),
                $this->resolveUserName($p['personnel']['site_engineer_id'] ?? $p['site_engineer_id'] ?? null),
                $this->formatDateMDY($p['start_date'] ?? ''),
                $this->formatDateMDY($p['end_date'] ?? ''),
                $this->formatDateMDY($p['created_at'] ?? ''),
                $this->formatDateMDY($p['updated_at'] ?? ''),
            ];
        }

        return [$headers, $rows, 'Projects'];
    }

    private function getResourceData(string $category, string $subtype): array
    {
        return $subtype === 'units'
            ? $this->getResourceUnitsData($category)
            : $this->getResourceModelsData($category);
    }

    private function getResourceModelsData(string $category): array
    {
        $headers = [
            'Model ID', 'Model Code', 'Model Name',
            'Total Quantity', 'Available Quantity',
            'Condition: New', 'Condition: Functional', 'Condition: Worn', 'Condition: Broken',
            'Created At (MM/DD/YYYY)', 'Updated At (MM/DD/YYYY)',
        ];
        $rows = [];
        $db = $this->firestore->getDb();

        foreach ($db->collection('resource')->documents() as $doc) {
            if (!$doc->exists()) continue;
            $r = $doc->data();
            if (($r['category'] ?? '') !== $category) continue;

            $ad = is_array($r['asset_details'] ?? null) ? $r['asset_details'] : [];
            $cs = is_array($r['condition_summary'] ?? null)
                ? $r['condition_summary']
                : (is_array($ad['condition_summary'] ?? null) ? $ad['condition_summary'] : []);

            $rows[] = [
                $doc->id(),
                $r['model_code'] ?? '',
                $r['name'] ?? '',
                $r['total_quantity'] ?? ($ad['total_quantity'] ?? 0),
                $r['available_quantity'] ?? ($ad['available_quantity'] ?? 0),
                $cs['New'] ?? 0,
                $cs['Functional'] ?? 0,
                $cs['Worn'] ?? 0,
                $cs['Broken'] ?? 0,
                $this->formatDateMDY($r['created_at'] ?? ''),
                $this->formatDateMDY($r['updated_at'] ?? ''),
            ];
        }

        return [$headers, $rows, $category . ' Models'];
    }

    private function getResourceUnitsData(string $category): array
    {
        $headers = [
            'Model ID', 'Model Code', 'Model Name',
            'Unit ID', 'Serial Number', 'Condition', 'Status', 'Brand', 'Model Number',
            'Location Address', 'Location Type', 'Assigned Project ID',
            'Created At (MM/DD/YYYY)', 'Updated At (MM/DD/YYYY)',
        ];
        $rows = [];
        $db = $this->firestore->getDb();

        foreach ($db->collection('resource')->documents() as $doc) {
            if (!$doc->exists()) continue;
            $r = $doc->data();
            if (($r['category'] ?? '') !== $category) continue;

            $modelId   = $doc->id();
            $modelCode = $r['model_code'] ?? '';
            $modelName = $r['name'] ?? '';

            $itemDocs = $db->collection('resource')->document($modelId)->collection('items')->documents();
            $hasItems = false;

            foreach ($itemDocs as $itemDoc) {
                if (!$itemDoc->exists()) continue;
                $item     = $itemDoc->data();
                $hasItems = true;
                $loc      = is_array($item['location'] ?? null) ? $item['location'] : [];

                $rows[] = [
                    $modelId,
                    $modelCode,
                    $modelName,
                    $itemDoc->id(),
                    $item['serial_number'] ?? '',
                    $item['condition'] ?? '',
                    $item['status'] ?? '',
                    $item['brand'] ?? ($item['brand_name'] ?? ''),
                    $item['model_number'] ?? '',
                    $loc['address'] ?? ($item['location_address'] ?? ''),
                    $loc['type'] ?? ($item['location_type'] ?? ''),
                    $item['assigned_project_id'] ?? '',
                    $this->formatDateMDY($item['created_at'] ?? ''),
                    $this->formatDateMDY($item['updated_at'] ?? ''),
                ];
            }

            // Row for model with no units yet
            if (!$hasItems) {
                $rows[] = array_merge([$modelId, $modelCode, $modelName], array_fill(0, 11, ''));
            }
        }

        return [$headers, $rows, $category . ' — All Units'];
    }

    private function getManpowerData(): array
    {
        $headers = [
            'Employee ID', 'First Name', 'Middle Name', 'Last Name', 'Full Name',
            'Role', 'Specialization', 'Skills', 'Status',
            'Assigned Project ID', 'Location Address', 'Location Type',
            'Created At (MM/DD/YYYY)', 'Updated At (MM/DD/YYYY)',
        ];
        $rows = [];
        $db = $this->firestore->getDb();

        foreach ($db->collection('manpower')->documents() as $doc) {
            if (!$doc->exists()) continue;
            $m  = $doc->data();
            $md = is_array($m['manpower_details'] ?? null) ? $m['manpower_details'] : [];
            $loc = is_array($m['location'] ?? null) ? $m['location'] : [];

            $firstName  = $m['first_name']  ?? ($md['first_name']  ?? '');
            $middleName = $m['middle_name'] ?? ($md['middle_name'] ?? '');
            $lastName   = $m['last_name']   ?? ($md['last_name']   ?? '');
            $fullName   = trim($firstName . ($middleName !== '' ? ' ' . $middleName : '') . ' ' . $lastName);

            $skillsArr = $m['skills'] ?? ($md['skills'] ?? []);
            $skills = is_array($skillsArr) ? implode('; ', array_filter(array_map('trim', $skillsArr))) : (string) $skillsArr;

            $rows[] = [
                $m['employee_id'] ?? ($md['manpower_id'] ?? ''),
                $firstName,
                $middleName,
                $lastName,
                $fullName,
                $m['role']           ?? ($md['role']           ?? ''),
                $m['specialization'] ?? ($md['specialization'] ?? ''),
                $skills,
                $m['status']         ?? ($md['status']         ?? ''),
                $m['assigned_project_id'] ?? ($md['assigned_project_id'] ?? ''),
                $loc['address'] ?? '',
                $loc['type']    ?? '',
                $this->formatDateMDY($m['created_at'] ?? ''),
                $this->formatDateMDY($m['updated_at'] ?? ''),
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

    private function exportCsv(array $headers, array $rows, string $title)
    {
        $filename = strtolower(str_replace([' ', '—', '/'], ['_', '-', '-'], $title)) . '_' . date('Ymd') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM so Excel opens it correctly
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, array_map('strval', $row));
        }
        fclose($out);
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

        // Dynamic single-page sizing so all rows fit without splitting.
        // Width = A4 landscape (297 mm). Height = estimated from row/column count.
        $ptPerMm     = 2.8346;
        $pageWidthMm = 297;
        $colCount    = count($headers);
        $rowCount    = count($rows);
        // More columns → cells wrap more → rows taller
        $mmPerRow    = $colCount > 10 ? 26 : ($colCount > 6 ? 20 : 14);
        $totalHeightMm = 55               // logo header + table header row
                       + ($rowCount * $mmPerRow)
                       + 20;             // footer + bottom margin
        $pageHeightMm = max(210, $totalHeightMm); // never shorter than A4 height
        $dompdf->setPaper([0, 0, $pageWidthMm * $ptPerMm, $pageHeightMm * $ptPerMm]);
        $dompdf->render();

        $filename = strtolower(str_replace(' ', '_', $title)) . '_' . date('Ymd') . '.pdf';
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
        $statusColIdxs = [];
        $progressColIdxs = [];
        foreach ($headers as $i => $h) {
            $hl = strtolower($h);
            if (str_contains($hl, 'status'))   $statusColIdxs[$i]   = true;
            if (str_contains($hl, 'progress')) $progressColIdxs[$i] = true;
        }

        $th = '';
        foreach ($headers as $h) {
            $th .= '<th>' . esc($h) . '</th>';
        }

        $tbody = '';
        foreach ($rows as $i => $row) {
            $rowClass = $i % 2 === 0 ? '' : ' alt';
            $tbody .= "<tr class=\"r{$rowClass}\">";
            foreach ($row as $ci => $cell) {
                $cellStr = (string) $cell;
                if (isset($statusColIdxs[$ci]) && $cellStr !== '') {
                    $tbody .= '<td>' . $this->statusBadge($cellStr) . '</td>';
                } elseif (isset($progressColIdxs[$ci])) {
                    $pct = min(100, max(0, (float) $cellStr));
                    $color = $pct >= 100 ? '#2e7d32' : ($pct >= 50 ? '#1a3c5e' : '#8C701C');
                    $tbody .= '<td><div class="prog-wrap"><div class="prog-bar"><div class="prog-fill" style="width:' . $pct . '%;background:' . $color . '"></div></div><span class="prog-lbl">' . esc(number_format($pct, 2)) . '%</span></div></td>';
                } else {
                    $tbody .= '<td>' . esc($cellStr) . '</td>';
                }
            }
            $tbody .= '</tr>';
        }

        $count    = count($rows);
        $printBtn = $forPdf ? '' : '<button class="btn-print" onclick="window.print()">&#128438; Print</button>';
        $now      = $this->now();
        // Compact overrides injected directly for PDF (Dompdf ignores @media print)
        $pdfCss = $forPdf ? '
body{padding:8px;font-size:8px}
.pz-card{box-shadow:none;border-radius:0}
.pz-header{padding:8px 12px 7px;border-bottom-width:2px}
.pz-logo-img{height:44px}
.pz-meta h1{font-size:10px}
.pz-meta p{font-size:7.5px}
.tbl-wrap{overflow:visible}
table{min-width:auto;width:100%;table-layout:fixed}
th{font-size:7px;padding:4px 5px;white-space:normal;word-break:break-word}
td{font-size:7px;padding:3px 5px;max-width:none;word-break:break-word}
.bdg{font-size:6.5px;padding:1px 5px}
.prog-wrap{min-width:60px}
.prog-lbl{font-size:6.5px}
.pz-footer{font-size:7px;margin-top:6px}
' : '';
        $faviconUrl = asset_url('favicon.ico');
        $logoPath   = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'PrimaSolution.png';
        $logoTag  = file_exists($logoPath)
            ? '<img src="data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) . '" alt="Prima-Solution Development and Construction Corp." class="pz-logo-img">'
            : '<span class="pz-logo-txt">PRIMA<span class="pz-logo-sub">-SOLUTION</span></span>';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$title} — PrimaZentra</title>
<link rel="icon" type="image/x-icon" href="{$faviconUrl}">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,Helvetica,sans-serif;font-size:11px;color:#222;background:#f0f4f8;padding:20px}
/* ── Page card ── */
.pz-card{background:#fff;border-radius:8px;box-shadow:0 2px 16px rgba(26,60,94,.12)}
/* ── Header: white letterhead area ── */
.pz-header{display:flex;align-items:center;justify-content:space-between;background:#fff;padding:14px 20px 12px;border-bottom:3px solid #1a3c5e;page-break-after:avoid;break-after:avoid}
.pz-logo-img{height:70px;object-fit:contain;display:block}
.pz-logo-txt{font-size:22px;font-weight:900;letter-spacing:1px;color:#1a3c5e}
.pz-logo-sub{color:#C9A84C}
.pz-meta{text-align:right}
.pz-meta h1{font-size:14px;font-weight:700;color:#1a3c5e;text-transform:uppercase;letter-spacing:.5px}
.pz-meta p{font-size:9px;color:#7a8fa6;margin-top:3px}
.btn-print{background:#1a3c5e;color:#fff;border:none;padding:8px 18px;border-radius:6px;font-weight:700;font-size:11px;cursor:pointer;letter-spacing:.3px;margin-left:16px}
.btn-print:hover{background:#0f2540}
.pz-header-right{display:flex;align-items:center}
/* ── Table ── */
.tbl-wrap{overflow-x:auto}
table{border-collapse:collapse;width:100%;min-width:max-content}
thead{display:table-header-group}
thead tr{background:#1a3c5e}
th{color:#fff;padding:9px 11px;text-align:left;font-size:9.5px;font-weight:700;white-space:nowrap;border-right:1px solid rgba(255,255,255,.12)}
th:last-child{border-right:none}
tbody tr{page-break-inside:avoid;break-inside:avoid}
tbody tr.r td{background:#fff}
tbody tr.r.alt td{background:#f5f8fb}
tbody tr:hover td{background:#eaf1f8}
td{padding:7px 11px;border-bottom:1px solid #e4eaf0;border-right:1px solid #f0f4f8;font-size:10px;color:#2c3e50;vertical-align:middle;word-break:break-word;max-width:180px}
td:last-child{border-right:none}
/* ── Status badges ── */
.bdg{display:inline-block;padding:2px 10px;border-radius:10px;font-size:8.5px;font-weight:700;white-space:nowrap;letter-spacing:.2px}
.bdg-ongoing{background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7}
.bdg-planned{background:#e3f2fd;color:#1565c0;border:1px solid #90caf9}
.bdg-completed{background:#ede7f6;color:#4527a0;border:1px solid #ce93d8}
.bdg-halted{background:#fce4ec;color:#c62828;border:1px solid #ef9a9a}
.bdg-available{background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7}
.bdg-inuse{background:#fff3e0;color:#e65100;border:1px solid #ffcc80}
.bdg-maintenance{background:#fff8e1;color:#f57f17;border:1px solid #ffe082}
.bdg-unavailable{background:#fce4ec;color:#c62828;border:1px solid #ef9a9a}
.bdg-deployed{background:#e8eaf6;color:#283593;border:1px solid #9fa8da}
.bdg-onleave{background:#f3e5f5;color:#6a1b9a;border:1px solid #ce93d8}
.bdg-instorage{background:#e0f2f1;color:#00695c;border:1px solid #80cbc4}
.bdg-default{background:#eceff1;color:#546e7a;border:1px solid #b0bec5}
/* ── Progress ── */
.prog-wrap{display:flex;align-items:center;gap:5px;min-width:90px}
.prog-bar{flex:1;height:6px;background:#e0e0e0;border-radius:3px;overflow:hidden}
.prog-fill{height:100%;border-radius:3px}
.prog-lbl{font-size:9px;color:#555;white-space:nowrap;min-width:32px;text-align:right}
/* ── Footer ── */
.pz-footer{margin-top:10px;font-size:9px;color:#8a9ab0;display:flex;justify-content:space-between;padding:0 4px}
/* ── Browser print ── */
@page{size:A4 landscape;margin:8mm 6mm}
@media print{
  body{background:#fff;padding:0;font-size:9px}
  .btn-print{display:none!important}
  .pz-card{box-shadow:none;border-radius:0}
  .tbl-wrap{overflow:visible}
  .pz-header{-webkit-print-color-adjust:exact;print-color-adjust:exact}
  thead tr{-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .bdg{-webkit-print-color-adjust:exact;print-color-adjust:exact}
  th,td{font-size:7.5px;padding:4px 6px}
  table{min-width:auto;width:100%;table-layout:fixed}
  td{max-width:none;word-break:break-all}
  .pz-footer{font-size:8px}
  tbody tr:hover td{background:inherit}
}
{$pdfCss}
</style>
</head>
<body>
<div class="pz-card">
  <div class="pz-header">
    <div>{$logoTag}</div>
    <div class="pz-header-right">
      <div class="pz-meta">
        <h1>{$title} Report</h1>
        <p>Generated: {$now}</p>
      </div>
      {$printBtn}
    </div>
  </div>
  <div class="tbl-wrap">
    <table>
      <thead><tr>{$th}</tr></thead>
      <tbody>{$tbody}</tbody>
    </table>
  </div>
</div>
<div class="pz-footer">
  <span>Prima-Solution Development and Construction Corp. &mdash; Confidential</span>
  <span>Total records: <strong>{$count}</strong> &nbsp;&bull;&nbsp; {$now}</span>
</div>
</body>
</html>
HTML;
    }

    private function statusBadge(string $val): string
    {
        $map = [
            'on-going'      => 'ongoing',
            'on going'      => 'ongoing',
            'ongoing'       => 'ongoing',
            'planned'       => 'planned',
            'completed'     => 'completed',
            'past deadline' => 'halted',
            'archived'      => 'halted',
            'available'     => 'available',
            'in use'        => 'inuse',
            'in-use'        => 'inuse',
            'under maintenance' => 'maintenance',
            'unavailable'   => 'unavailable',
            'deployed'      => 'deployed',
            'on leave'      => 'onleave',
            'on-leave'      => 'onleave',
            'in-storage'    => 'instorage',
            'in storage'    => 'instorage',
            'transfer pending' => 'pending',
        ];
        $cls = $map[strtolower(trim($val))] ?? 'default';
        return '<span class="bdg bdg-' . $cls . '">' . esc($val) . '</span>';
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function resolveUserName(mixed $userId): string
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
        } catch (\Throwable) {}
        return $id;
    }

    private function normDate(mixed $val): string
    {
        if (empty($val)) return '';
        if (is_object($val) && method_exists($val, 'get')) {
            try { return $val->get()->format('Y-m-d'); } catch (\Throwable) {}
        }
        if (is_numeric($val)) return date('Y-m-d', (int) $val);
        if (is_string($val)) return substr($val, 0, 10);
        return '';
    }

    private function formatDateMDY(mixed $val): string
    {
        if (empty($val)) return '';
        // Firestore Timestamp object
        if (is_object($val) && method_exists($val, 'get')) {
            try { return $val->get()->format('m/d/Y'); } catch (\Throwable) {}
        }
        // Unix timestamp
        if (is_numeric($val)) return date('m/d/Y', (int) $val);
        // String: YYYY-MM-DD or MM/DD/YYYY
        if (is_string($val)) {
            $val = trim($val);
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $val)) {
                $ts = strtotime(substr($val, 0, 10));
                return $ts !== false ? date('m/d/Y', $ts) : $val;
            }
            return substr($val, 0, 10);
        }
        return '';
    }

    private function resolveRegion(string $address): string
    {
        $addr = strtolower($address);

        $luzon = [
            'metro manila','ncr','manila','quezon city','makati','pasig','taguig',
            'marikina','caloocan','malabon','navotas','valenzuela','paranaque',
            'las pinas','muntinlupa','san juan','mandaluyong','pateros',
            'cavite','laguna','batangas','rizal','bulacan','pampanga','tarlac',
            'zambales','bataan','nueva ecija','aurora','pangasinan','la union',
            'ilocos','benguet','baguio','nueva vizcaya','quirino','cagayan',
            'isabela','abra','kalinga','apayao','ifugao','mountain province',
            'quezon province','camarines','albay','sorsogon','masbate',
            'occidental mindoro','oriental mindoro','palawan','romblon',
        ];
        $visayas = [
            'cebu','iloilo','bacolod','negros','leyte','samar','bohol',
            'aklan','antique','capiz','guimaras','biliran','eastern samar',
            'northern samar','western samar','southern leyte','siquijor',
        ];
        $mindanao = [
            'davao','cagayan de oro','zamboanga','general santos','cotabato',
            'bukidnon','misamis','lanao','maguindanao','sultan kudarat',
            'sarangani','south cotabato','north cotabato','agusan','surigao',
            'camiguin','dinagat','basilan','sulu','tawi-tawi',
        ];

        foreach ($luzon as $k)    { if (str_contains($addr, $k)) return 'Luzon'; }
        foreach ($visayas as $k)  { if (str_contains($addr, $k)) return 'Visayas'; }
        foreach ($mindanao as $k) { if (str_contains($addr, $k)) return 'Mindanao'; }
        return '';
    }

    private function now(): string
    {
        return date('F j, Y g:i A');
    }
}
