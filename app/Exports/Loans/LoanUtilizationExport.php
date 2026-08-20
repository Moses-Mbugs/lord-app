<?php

namespace App\Exports\Loans;

use App\Models\Loans\LoanUtilizationProductOverride;
use App\Models\Loans\LoanUtilizationSnapshot;
use App\Services\Loans\LoanUtilizationDashboardService;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LoanUtilizationExport
{
    public function __construct(protected LoanUtilizationDashboardService $dashboardService)
    {
    }

    public function generate(LoanUtilizationSnapshot $snapshot, string $fileName): string
    {
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }

        $spreadsheet = new Spreadsheet();

        $acctSheet = $spreadsheet->getActiveSheet();
        $acctSheet->setTitle('ACCT_Portfolio');
        $this->writeEntries($acctSheet, $snapshot);

        $dashboardSheet = new Worksheet($spreadsheet, 'Dashboard');
        $spreadsheet->addSheet($dashboardSheet);
        $this->writeDashboard($dashboardSheet, $snapshot);

        $mappingSheet = new Worksheet($spreadsheet, 'Product_Mapping');
        $spreadsheet->addSheet($mappingSheet);
        $this->writeProductMapping($mappingSheet, $snapshot);

        $notesSheet = new Worksheet($spreadsheet, 'Notes');
        $spreadsheet->addSheet($notesSheet);
        $this->writeNotes($notesSheet);

        // ACCT_Portfolio can run to several thousand rows — PhpSpreadsheet's autosize
        // measures every cell's text width at save time, which is fine for the small
        // summary sheets but far too slow there, so it gets fixed widths instead.
        foreach (['A' => 20, 'B' => 32, 'C' => 44, 'D' => 22, 'E' => 12, 'F' => 14, 'G' => 16, 'H' => 26, 'I' => 10, 'J' => 16, 'K' => 14, 'L' => 14] as $col => $width) {
            $acctSheet->getColumnDimension($col)->setWidth($width);
        }

        foreach ([$dashboardSheet, $mappingSheet] as $sheet) {
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

            for ($columnIndex = 1; $columnIndex <= $highestColumnIndex; $columnIndex++) {
                $columnLetter = Coordinate::stringFromColumnIndex($columnIndex);
                $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
            }
        }

        $directory = storage_path('app/loan-utilization-exports');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filePath = $directory . '/' . $fileName;

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $filePath;
    }

    protected function writeEntries(Worksheet $sheet, LoanUtilizationSnapshot $snapshot): void
    {
        $headers = [
            'Account Reference', 'Customer Name', 'Product Name',
            'GROSS Loans Outstanding LCY', 'Days Past Due (DPD)',
            'Classification Code', 'Risk Rating (Frr/Orr)', 'Classification',
            'IFRS 9 Stage', 'Performance Status', 'Value Date', 'Business',
        ];

        $sheet->fromArray([$headers], null, 'A1');
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);
        $sheet->freezePane('A2');

        $rows = [];

        $snapshot->entries()->orderBy('id')->chunk(1000, function ($entries) use (&$rows) {
            foreach ($entries as $e) {
                $rows[] = [
                    $e->account_reference,
                    $e->customer_name,
                    $e->product_name,
                    (float) $e->gross_outstanding_lcy,
                    $e->dpd,
                    $e->classification_code,
                    $e->risk_rating,
                    $e->classification_label,
                    $e->ifrs9_stage,
                    $e->performance_status,
                    optional($e->value_date)->format('Y-m-d'),
                    $e->business,
                ];
            }
        });

        if (!empty($rows)) {
            $sheet->fromArray($rows, null, 'A2');
        }
    }

    protected function writeDashboard(Worksheet $sheet, LoanUtilizationSnapshot $snapshot): void
    {
        $data = $this->dashboardService->build($snapshot);

        $sheet->fromArray([['LOAN PRODUCT UTILISATION EXECUTIVE DASHBOARD']], null, 'A1');
        $sheet->fromArray([["As-Of Date: {$data['as_of_date']}  |  All figures LCY"]], null, 'A2');

        $headers = [
            'Product Name', 'Approved Limit', 'Performing Exposure', 'Non-Performing O/S',
            'Total O/S', '% Utilised', 'YTD', 'MTD', 'WTD', 'Last Day', 'NPL Ratio', '# Volume',
        ];
        $sheet->fromArray([$headers], null, 'A4');
        $sheet->getStyle('A4:L4')->getFont()->setBold(true);

        $row = 5;
        foreach ($data['products'] as $p) {
            $sheet->fromArray([[
                $p['product_name'], $p['approved_limit'], $p['performing'], $p['non_performing'],
                $p['total'], $p['utilisation'], $p['ytd'], $p['mtd'], $p['wtd'], $p['last_day'],
                $p['npl_ratio'], $p['volume'],
            ]], null, 'A' . $row);
            $row++;
        }

        $t = $data['totals'];
        $sheet->fromArray([[
            'PORTFOLIO TOTAL', $t['approved_limit'], $t['performing'], $t['non_performing'],
            $t['total'], $t['utilisation'], $t['ytd'], $t['mtd'], $t['wtd'], $t['last_day'],
            $t['npl_ratio'], $t['volume'],
        ]], null, 'A' . $row);
        $sheet->getStyle('A' . $row . ':L' . $row)->getFont()->setBold(true);
    }

    protected function writeProductMapping(Worksheet $sheet, LoanUtilizationSnapshot $snapshot): void
    {
        $sheet->fromArray([['Credit Line Code', 'Assigned Product Name', '# Loans', 'Total Exposure (LCY)', 'Source']], null, 'A1');
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        $overrideCodes = LoanUtilizationProductOverride::query()->pluck('credit_line_code')->flip();

        $stats = $snapshot->entries()
            ->selectRaw('credit_line_code, product_name, COUNT(*) as cnt, SUM(gross_outstanding_lcy) as total_exposure')
            ->groupBy('credit_line_code', 'product_name')
            ->orderByDesc('total_exposure')
            ->get();

        $row = 2;
        foreach ($stats as $s) {
            $code = $s->credit_line_code ?? '(blank)';
            $sheet->fromArray([[
                $code,
                $s->product_name,
                $s->cnt,
                (float) $s->total_exposure,
                isset($overrideCodes[$code]) ? 'Manual override' : 'Auto-mapped',
            ]], null, 'A' . $row);
            $row++;
        }
    }

    protected function writeNotes(Worksheet $sheet): void
    {
        $notes = [
            ['Conversion Notes & Assumptions'],
            [],
            ['This workbook was generated from the Loan Utilization module in the lord-app system, sourced from a LOANS PORTFOLIO NEW core-banking extract.'],
            [],
            ['1. Product Name'],
            ['Derived from the Credit Line code prefix (see Product_Mapping sheet), with sector overrides for Agriculture/School Finance based on Industry Segment. Manual corrections are stored as overrides and take precedence over the automatic rules on every future import.'],
            [],
            ['2. IFRS 9 Stage'],
            ['Extracted from the "Gl Name" text (e.g. "TERM LOAN - STAGE 2" -> Stage 2). Write-off status defaults to Stage 3 if no stage number is present.'],
            [],
            ['3. Performance Status'],
            ['Stage 1 = Performing; Stage 2 and Stage 3 = Non-Performing.'],
            [],
            ['4. Classification label'],
            ['Mapped from "User Defined Status" per standard CBK convention. DPD bucket boundaries are a best-effort default - confirm against actual policy.'],
            [],
            ['5. Approved Limit'],
            ['A manual input maintained in the Loan Utilization dashboard (board-approved product ceilings), not derived from the source extract.'],
        ];

        $sheet->fromArray($notes, null, 'A1');
        $sheet->getColumnDimension('A')->setWidth(110);
    }
}
