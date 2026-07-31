<?php

namespace App\Exports\Loans;

use App\Models\Loans\LoanBookRun;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LoanBookExport
{
    public function generate(LoanBookRun $run, string $fileName): string
    {
        // maatwebsite/excel reconfigures PhpSpreadsheet's cell cache backend
        // globally on boot, which affects this raw Spreadsheet usage too even
        // though it never calls the Maatwebsite facade directly. Scoped bump,
        // not a server-wide config change.
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }

        $spreadsheet = new Spreadsheet();

        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Summary');
        $this->writeSummarySheet($summarySheet, $run);

        $entriesSheet = new Worksheet($spreadsheet, 'Loan Book');
        $spreadsheet->addSheet($entriesSheet);
        $this->writeLoanBookRows($entriesSheet, $run->entries);

        $exceptionsSheet = new Worksheet($spreadsheet, 'Exceptions');
        $spreadsheet->addSheet($exceptionsSheet);
        $this->writeExceptionRows($exceptionsSheet, $run->exceptions);

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

            for ($columnIndex = 1; $columnIndex <= $highestColumnIndex; $columnIndex++) {
                $columnLetter = Coordinate::stringFromColumnIndex($columnIndex);
                $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
            }

            $sheet->freezePane('A2');
        }

        $directory = storage_path('app/loan-book-exports');

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

    protected function writeSummarySheet(Worksheet $sheet, LoanBookRun $run): void
    {
        $rows = [
            ['Batch Reference', $run->batch_reference],
            ['Loan Book Date', optional($run->loan_book_date)->format('Y-m-d')],
            ['Status', $run->status],

            ['PMS File', $run->pms_original_filename],
            ['PMS Rows', $run->total_pms_rows],

            ['Loan Details File', $run->loan_details_original_filename],
            ['Loan Details Rows', $run->total_loan_details_rows],

            ['Portfolio File', $run->portfolio_original_filename],
            ['Portfolio Rows Read', $run->total_portfolio_rows_read],
            ['Portfolio Rows Selected', $run->total_portfolio_rows_selected],

            ['Credit Cards File', $run->credit_cards_original_filename],
            ['Credit Card Rows Read', $run->total_credit_card_rows_read],
            ['Credit Card Rows Selected', $run->total_credit_card_rows_selected],

            ['Final Loan Book Rows', $run->total_final_loan_book_rows],
            ['Exceptions', $run->total_exceptions],
            ['Total PMS Net Outstanding', $run->total_pms_net_outstanding],
            ['Total PMS Negative Outstanding', $run->total_pms_negative_outstanding],
            ['Total Loan Book Outstanding', $run->total_loan_book_outstanding],
            ['Control Difference', $run->control_difference],
            ['Processed At', optional($run->processed_at)->format('Y-m-d H:i:s')],
        ];

        $sheet->fromArray([['Metric', 'Value']], null, 'A1');
        $sheet->fromArray($rows, null, 'A2');

        $sheet->getStyle('A1:B1')->getFont()->setBold(true);
    }

    protected function writeLoanBookRows(Worksheet $sheet, $records): void
    {
        $headers = [
            'source_report',
            'source_type',
            'source_row_number',

            'related_account',
            'related_customer_id',
            'name',

            'branch',
            'branch_name',
            'currency',
            'contract_currency',

            'product_type',
            'gl_name',
            'frr',
            'orr',
            'account_status',
            'status',

            'value_dt',
            'maturity_date',
            'status_since',

            'pdo_days',
            'days_in_arrears',
            'amount_arrears',

            'interest_rate',
            'exch_rate',
            'tenor',

            'limit',
            'limit_lcy',

            'card_account',
            'lcy_curr_balance',

            'net_outstanding_amount',
            'loan_book_outstanding',
            'outstanding_amount_lcy',

            'pms_gl_codes',
            'linecode',
            'industrycode',
            'group_code',
            'sub_sic_code',
            'business_segment',
            'product_code',
            'latest_status_change',
            'rm_officer',
            'collateral_code',
            'description',
        ];

        $sheet->fromArray([$headers], null, 'A1');
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);

        if ($records->isEmpty()) {
            return;
        }

        $rowNumber = 2;

        foreach ($records as $record) {
            $row = [];

            foreach ($headers as $header) {
                $row[] = $this->cellValue($record->{$header});
            }

            $sheet->fromArray([$row], null, 'A' . $rowNumber);
            $rowNumber++;
        }
    }

    protected function writeExceptionRows(Worksheet $sheet, $records): void
    {
        $headers = [
            'exception_type',
            'source',
            'related_account',
            'related_customer_id',
            'name',
            'amount',
            'remarks',
            'payload',
        ];

        $sheet->fromArray([$headers], null, 'A1');
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);

        if ($records->isEmpty()) {
            return;
        }

        $rowNumber = 2;

        foreach ($records as $record) {
            $row = [];

            foreach ($headers as $header) {
                $row[] = $this->cellValue($record->{$header});
            }

            $sheet->fromArray([$row], null, 'A' . $rowNumber);
            $rowNumber++;
        }
    }

    protected function cellValue($value): mixed
    {
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
