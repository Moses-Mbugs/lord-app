<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Models\Finance\RmTarget;
use App\Services\Reports\RmPortfolioService;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Imports FY deposit/NTB targets per RM into rm_targets, so
 * reports:email-weekly-rm-movers can show Budget vs Actual.
 *
 * Accepts two input formats:
 * - .csv: a plain, normalized file with a header row containing at least
 *   rm_code, deposit_target, ntb_target (any extra columns are ignored).
 * - .xlsx: read via PhpSpreadsheet, using the same fixed column layout as the
 *   "Frontline Budgets.xlsx" workbook shared Oct 2026 ("Targets 2026" sheet:
 *   header row 2, data from row 3, Staff No. in column C, FY Deposit Portfolio
 *   in column M, NTB Clients target in column Z).
 *
 * CSV is the recommended path: workbooks protected by this organization's
 * information-protection policy (an RMS/MIP-style OLE encryption wrapper) are
 * opaque to PhpSpreadsheet — only a licensed, authenticated Excel can open
 * them — so a protected .xlsx must first be exported to CSV from Excel
 * (or have its relevant columns copy-pasted into one) before this command can
 * read it. An unprotected .xlsx can be passed directly.
 *
 * loan_target is never set here — there is no reliable FY loan budget figure
 * in the source workbook (its "FY Asset Portfolio" column is blank) — it is
 * left at the model's default (0) until a real loan budget exists.
 *
 * Only imports RMs already present in reports.balances.rm_portfolio — any
 * other row (e.g. RMs who have since left the bank) is skipped.
 */
class ImportRmTargetsCommand extends Command
{
    private const STAFF_NO_COL       = 3;  // C: Staff no. (rm_code)
    private const DEPOSIT_TARGET_COL = 13; // M: FY Deposit Portfolio
    private const NTB_TARGET_COL     = 26; // Z: NTB Clients
    private const FIRST_DATA_ROW     = 3;

    protected $signature = 'reports:import-rm-targets
        {file : Path to the budget file (.csv recommended, or an unprotected .xlsx)}
        {--sheet= : .xlsx only — sheet name to read; defaults to the first sheet whose name contains "Targets"}
        {--year= : Target period_year; defaults to the current year}
    ';

    protected $description = 'Import FY deposit/NTB targets per RM into rm_targets, from a normalized CSV (recommended) or an unprotected .xlsx workbook.';

    public function handle(): int
    {
        $path = (string) $this->argument('file');

        if (!is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $year = (int) ($this->option('year') ?: now()->year);
        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $rows = match ($ext) {
            'csv'   => $this->readCsv($path),
            default => $this->readXlsx($path),
        };

        if ($rows === null) {
            return self::FAILURE;
        }

        $this->line("Parsed " . count($rows) . " candidate row(s) for period_year {$year}...");

        $knownCodes = RmPortfolioService::codes();
        $imported   = [];
        $skipped    = [];

        foreach ($rows as $row) {
            $rmCode = strtoupper(trim((string) $row['rm_code']));

            if ($rmCode === '' || !preg_match('/^KE\d{3,5}$/', $rmCode)) {
                continue;
            }

            if (!in_array($rmCode, $knownCodes, true)) {
                $skipped[] = $rmCode;
                continue;
            }

            RmTarget::updateOrCreate(
                ['rm_code' => $rmCode, 'period_year' => $year],
                [
                    'deposit_target' => (float) $row['deposit_target'],
                    'ntb_target'     => (int) $row['ntb_target'],
                    'updated_by'     => 'reports:import-rm-targets',
                ]
            );

            $imported[] = $rmCode;
        }

        $this->info('RM targets imported: ' . count($imported));
        $this->line(implode(', ', $imported));

        $missing = array_values(array_diff($knownCodes, $imported));
        if (!empty($missing)) {
            $this->warn('In our portfolio but not found/matched in the file: ' . implode(', ', $missing));
        }

        if (!empty($skipped)) {
            $this->line('Skipped (not in reports.balances.rm_portfolio): ' . implode(', ', array_unique($skipped)));
        }

        $this->warn('loan_target was NOT imported — no reliable FY loan budget figure exists yet. Set it manually via /rm-targets/manage if a real loan budget becomes available.');

        return self::SUCCESS;
    }

    /** @return array<int, array{rm_code: string, deposit_target: float|string, ntb_target: int|string}>|null */
    private function readCsv(string $path): ?array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->error('Could not open CSV file.');
            return null;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            $this->error('CSV file is empty.');
            fclose($handle);
            return null;
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h, " \t\n\r\0\x0B\xEF\xBB\xBF")), $header);
        $required = ['rm_code', 'deposit_target', 'ntb_target'];
        $missingCols = array_diff($required, $header);

        if (!empty($missingCols)) {
            $this->error('CSV is missing required column(s): ' . implode(', ', $missingCols));
            fclose($handle);
            return null;
        }

        $rows = [];
        while (($line = fgetcsv($handle)) !== false) {
            $assoc = array_combine($header, array_pad($line, count($header), ''));
            $rows[] = [
                'rm_code'        => $assoc['rm_code'] ?? '',
                'deposit_target' => $assoc['deposit_target'] ?? 0,
                'ntb_target'     => $assoc['ntb_target'] ?? 0,
            ];
        }

        fclose($handle);

        return $rows;
    }

    /** @return array<int, array{rm_code: string, deposit_target: float|string, ntb_target: int|string}>|null */
    private function readXlsx(string $path): ?array
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
        } catch (\Throwable $e) {
            $this->error("Could not open workbook: " . $e->getMessage());
            $this->warn('If this workbook is protected by an organization information-protection policy, PhpSpreadsheet cannot open it. Export it to CSV from Excel instead (rm_code, deposit_target, ntb_target columns) and pass that file.');
            return null;
        }

        $sheet = $this->resolveSheet($spreadsheet);
        if (!$sheet) {
            $this->error('No matching sheet found. Pass --sheet= to name it explicitly.');
            return null;
        }

        $this->line("Reading sheet \"{$sheet->getTitle()}\"...");

        $rows = [];
        $highestRow = $sheet->getHighestRow();

        for ($row = self::FIRST_DATA_ROW; $row <= $highestRow; $row++) {
            $rows[] = [
                'rm_code'        => (string) $this->cell($sheet, self::STAFF_NO_COL, $row),
                'deposit_target' => (float) ($this->cell($sheet, self::DEPOSIT_TARGET_COL, $row) ?: 0),
                'ntb_target'     => (int) ($this->cell($sheet, self::NTB_TARGET_COL, $row) ?: 0),
            ];
        }

        return $rows;
    }

    private function cell(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $colIndex, int $row): mixed
    {
        $coordinate = Coordinate::stringFromColumnIndex($colIndex) . $row;
        return $sheet->getCell($coordinate)->getCalculatedValue();
    }

    private function resolveSheet(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet): ?\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        $sheetOpt = trim((string) ($this->option('sheet') ?? ''));
        if ($sheetOpt !== '') {
            return $spreadsheet->getSheetByName($sheetOpt);
        }

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            if (stripos($sheet->getTitle(), 'Targets') !== false) {
                return $sheet;
            }
        }

        return $spreadsheet->getSheet(0);
    }
}
