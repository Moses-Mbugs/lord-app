<?php

declare(strict_types=1);

namespace App\Exports\Finance;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BranchMoversWorkbookExport implements WithMultipleSheets
{
    public function __construct(
        private readonly string $startDate,
        private readonly string $endDate,
        private readonly int $limit = 10
    ) {
    }

    public function sheets(): array
    {
        $limit = max(1, (int) $this->limit);

        return [
            // Sheet 1: Branch Summary (from group_movers)
            new BranchSummarySheet($this->startDate, $this->endDate),

            // Sheet 2: Branch Movement (Top 10 gainers then Top 10 losers) (from group_movers)
            new BranchMovementSheet($this->startDate, $this->endDate, $limit),

            // Sheet 3: CIF Movers by Branch (Top 10 CIF gainers/losers per branch) (from customer_balances)
            new CifMoversByBranchSheet($this->startDate, $this->endDate, $limit),

            // Sheet 4: Loan Account Movers by Branch — commented out pending loan fixes
            // new LoanAccountMoversByBranchSheet($this->startDate, $this->endDate, $limit),
        ];
    }
}

/**
 * SHEET 1: Branch Summary (group_movers: group_type=BRANCH, scope=SUMMARY)
 */
class BranchSummarySheet implements FromArray, WithTitle, WithHeadings, ShouldAutoSize, WithStyles, WithColumnFormatting, WithEvents
{
    public function __construct(
        private readonly string $startDate,
        private readonly string $endDate
    ) {
    }

    public function title(): string
    {
        return 'Branch Summary';
    }

    public function headings(): array
    {
        return ['Branch Code', 'Branch Name', 'Start Balance', 'End Balance', 'Dep Movement', 'Loan Opening', 'Loan Closing', 'Loan Movement'];
    }

    public function array(): array
    {
        $rows = DB::table('group_movers')
            ->where('group_type', 'BRANCH')
            ->where('scope', 'SUMMARY')
            ->whereDate('start_date', $this->startDate)
            ->whereDate('end_date', $this->endDate)
            ->orderByRaw("CASE WHEN group_key = 'ALL' THEN 1 ELSE 0 END") // TOTAL last
            ->orderBy('group_key')
            ->get([
                'group_key',
                'group_name',
                'start_balance',
                'end_balance',
                'movement',
            ]);

        if ($rows->isEmpty()) {
            return [['No data', 'No qualifying movements for this period.', '', '', '', '', '', '']];
        }

        $loanByBranch = $this->fetchLoanData();

        return $rows->map(function ($r) use ($loanByBranch) {
            $key   = strtoupper(trim((string) ($r->group_key ?? '')));
            $loan  = $loanByBranch[$key] ?? ['open' => 0.0, 'close' => 0.0];
            $lOpen  = (float) $loan['open'];
            $lClose = (float) $loan['close'];

            return [
                (string) ($r->group_key ?? ''),
                (string) ($r->group_name ?? ''),
                (float) ($r->start_balance ?? 0),
                (float) ($r->end_balance ?? 0),
                (float) ($r->movement ?? 0),
                $lOpen,
                $lClose,
                round($lClose - $lOpen, 2),
            ];
        })->toArray();
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet   = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                // Green header for loan columns
                $sheet->getStyle('F1:H1')->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCFCE7']],
                    'font' => ['bold' => true, 'color' => ['rgb' => '166534']],
                ]);

                // Left border to separate deposit vs loan section
                $sheet->getStyle("F1:F{$lastRow}")->applyFromArray([
                    'borders' => ['left' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'BBF7D0']]],
                ]);

                // Light green fill on loan data cells (rows 2+)
                if ($lastRow > 1) {
                    $sheet->getStyle("F2:H{$lastRow}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']],
                        'font' => ['color' => ['rgb' => '166534']],
                    ]);
                }
            },
        ];
    }

    private function fetchLoanData(): array
    {
        $loanStartDate = DB::table('loan_listings')
            ->whereNotNull('as_at_date')
            ->whereDate('as_at_date', '<=', $this->startDate)
            ->max('as_at_date');

        $loanEndDate = DB::table('loan_listings')
            ->whereNotNull('as_at_date')
            ->whereDate('as_at_date', '<=', $this->endDate)
            ->max('as_at_date');

        if (!$loanStartDate && !$loanEndDate) return [];

        $dates = array_values(array_unique(array_filter([$loanStartDate, $loanEndDate])));

        $rows = DB::table('loan_listings as ll')
            ->joinSub(
                DB::table('loan_listings')
                    ->whereIn(DB::raw('DATE(as_at_date)'), $dates)
                    ->whereRaw("UPPER(TRIM(COALESCE(business_segment,''))) != 'CORPORATE'")
                    ->whereRaw("(TRIM(COALESCE(loan_status, '')) = '' OR loan_status IN ('NORM', 'Normal', 'OAEM', 'SUBS', 'Watch'))")
                    ->select(DB::raw('DATE(as_at_date) AS snap_date'), 'related_account', DB::raw('MAX(id) AS max_id'))
                    ->groupBy(DB::raw('DATE(as_at_date)'), 'related_account'),
                'dedup', 'll.id', '=', 'dedup.max_id'
            )
            ->selectRaw(
                "UPPER(TRIM(COALESCE(NULLIF(TRIM(ll.branch),''), LEFT(ll.related_account, 3)))) AS branch_code,
                 SUM(CASE WHEN DATE(ll.as_at_date) = ? THEN ll.loan_book_outstanding ELSE 0 END) AS loan_open,
                 SUM(CASE WHEN DATE(ll.as_at_date) = ? THEN ll.loan_book_outstanding ELSE 0 END) AS loan_close",
                [$loanStartDate ?? $loanEndDate, $loanEndDate ?? $loanStartDate]
            )
            ->groupByRaw("UPPER(TRIM(COALESCE(NULLIF(TRIM(ll.branch),''), LEFT(ll.related_account, 3))))")
            ->get();

        $result = [];
        $allOpen = 0.0;
        $allClose = 0.0;

        foreach ($rows as $r) {
            $code = strtoupper(trim((string) $r->branch_code));
            if ($code === '') continue;
            $result[$code] = ['open' => (float) $r->loan_open, 'close' => (float) $r->loan_close];
            $allOpen  += (float) $r->loan_open;
            $allClose += (float) $r->loan_close;
        }

        $result['ALL'] = ['open' => $allOpen, 'close' => $allClose];

        return $result;
    }
}

/**
 * SHEET 2: Branch Movement (Top 10 gainers then below Top 10 losers)
 * group_movers: group_type=BRANCH, scope=TOP
 */
class BranchMovementSheet implements FromArray, WithTitle, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    private array $mergeRows = [];
    private array $boldRows  = [];

    public function __construct(
        private readonly string $startDate,
        private readonly string $endDate,
        private readonly int $limit = 10
    ) {
    }

    public function title(): string
    {
        return 'Branch Movement';
    }

    public function array(): array
    {
        $limit = max(1, (int) $this->limit);

        $gainers = DB::table('group_movers')
            ->where('group_type', 'BRANCH')
            ->where('scope', 'TOP')
            ->where('direction', 'GAIN')
            ->whereDate('start_date', $this->startDate)
            ->whereDate('end_date', $this->endDate)
            ->orderBy('rank')
            ->limit($limit)
            ->get(['rank', 'group_key', 'group_name', 'start_balance', 'end_balance', 'movement']);

        $losers = DB::table('group_movers')
            ->where('group_type', 'BRANCH')
            ->where('scope', 'TOP')
            ->where('direction', 'LOSS')
            ->whereDate('start_date', $this->startDate)
            ->whereDate('end_date', $this->endDate)
            ->orderBy('rank')
            ->limit($limit)
            ->get(['rank', 'group_key', 'group_name', 'start_balance', 'end_balance', 'movement']);

        $rows = [];

        // Title
        $rows[] = ['Branch Movement (Top Gainers & Losers)'];
        $this->mergeRows[] = 1;
        $this->boldRows[]  = 1;

        // Period
        $rows[] = ["Period: {$this->startDate} → {$this->endDate}"];
        $this->mergeRows[] = 2;

        // Spacer
        $rows[] = [''];
        $this->mergeRows[] = 3;

        // Gainers section
        $gHeaderRow = count($rows) + 1;
        $rows[] = ['Top 10 Gainers'];
        $this->mergeRows[] = $gHeaderRow;
        $this->boldRows[]  = $gHeaderRow;

        $gTableHeaderRow = count($rows) + 1;
        $rows[] = ['Rank', 'Branch Code', 'Branch Name', 'Start Balance', 'End Balance', 'Movement'];
        $this->boldRows[] = $gTableHeaderRow;

        if ($gainers->isEmpty()) {
            $rows[] = ['', '(no gainers)', '', '', '', ''];
        } else {
            foreach ($gainers as $r) {
                $rows[] = [
                    (int) ($r->rank ?? 0),
                    (string) ($r->group_key ?? ''),
                    (string) ($r->group_name ?? ''),
                    (float) ($r->start_balance ?? 0),
                    (float) ($r->end_balance ?? 0),
                    (float) ($r->movement ?? 0),
                ];
            }
        }

        // Spacer
        $rows[] = [''];
        $this->mergeRows[] = count($rows);

        // Losers section
        $lHeaderRow = count($rows) + 1;
        $rows[] = ['Top 10 Losers'];
        $this->mergeRows[] = $lHeaderRow;
        $this->boldRows[]  = $lHeaderRow;

        $lTableHeaderRow = count($rows) + 1;
        $rows[] = ['Rank', 'Branch Code', 'Branch Name', 'Start Balance', 'End Balance', 'Movement'];
        $this->boldRows[] = $lTableHeaderRow;

        if ($losers->isEmpty()) {
            $rows[] = ['', '(no losers)', '', '', '', ''];
        } else {
            foreach ($losers as $r) {
                $rows[] = [
                    (int) ($r->rank ?? 0),
                    (string) ($r->group_key ?? ''),
                    (string) ($r->group_name ?? ''),
                    (float) ($r->start_balance ?? 0),
                    (float) ($r->end_balance ?? 0),
                    (float) ($r->movement ?? 0),
                ];
            }
        }

        // Pad rows to 6 columns
        return array_map(fn ($r) => array_pad(is_array($r) ? $r : [$r], 6, ''), $rows);
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach ($this->mergeRows as $r) {
                    $sheet->mergeCells("A{$r}:F{$r}");
                }

                foreach ($this->boldRows as $r) {
                    $sheet->getStyle("A{$r}:F{$r}")->getFont()->setBold(true);
                }

                $sheet->freezePane('A4');
            },
        ];
    }
}

/**
 * SHEET 3: CIF Movers by Branch (Top N CIF gainers/losers per branch, side-by-side, all in one sheet)
 * source: customer_balances (LCY equivalent preferred)
 */
class CifMoversByBranchSheet implements FromArray, WithTitle, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    private array $mergeRows = [];
    private array $boldRows  = [];

    public function __construct(
        private readonly string $startDate,
        private readonly string $endDate,
        private readonly int $limit = 10
    ) {
    }

    public function title(): string
    {
        return 'CIF Movers by Branch';
    }

    public function array(): array
    {
        $limit = max(1, (int) $this->limit);

        // LCY equivalent preferred
        $balPlain = Schema::hasColumn('customer_balances', 'lcy_balance') ? 'lcy_balance' : 'balance';

        $startExpr = "SUM(CASE WHEN cb.balance_date = ? THEN cb.{$balPlain} ELSE 0 END)";
        $endExpr   = "SUM(CASE WHEN cb.balance_date = ? THEN cb.{$balPlain} ELSE 0 END)";
        $moveExpr  = "({$endExpr} - {$startExpr})";

        // Pull ALL branch+cif movements in one query
        $raw = DB::table('customer_balances as cb')
            ->selectRaw("UPPER(TRIM(cb.branch_code)) as branch_code")
            ->selectRaw("cb.cif as cif")
            ->selectRaw("MAX(cb.customer_name) as customer_name")
            ->selectRaw("{$startExpr} as start_balance", [$this->startDate])
            ->selectRaw("{$endExpr} as end_balance", [$this->endDate])
            ->selectRaw("{$moveExpr} as movement", [$this->endDate, $this->startDate])
            ->whereIn('cb.balance_date', [$this->startDate, $this->endDate])
            ->whereNotNull('cb.branch_code')
            ->whereNotNull('cb.cif')
            ->whereRaw("UPPER(TRIM(cb.branch_code)) <> 'P50'")
            ->groupByRaw("UPPER(TRIM(cb.branch_code))")
            ->groupBy('cb.cif')
            ->havingRaw("{$moveExpr} <> 0", [$this->endDate, $this->startDate])
            ->get();

        $byBranch = collect($raw)
            ->map(function ($r) {
                $r->branch_code = $this->normalizeBranchCode((string) ($r->branch_code ?? ''));
                return $r;
            })
            ->filter(fn ($r) => is_string($r->branch_code) && trim($r->branch_code) !== '')
            ->groupBy(fn ($r) => (string) $r->branch_code);

        // Preferred order first; then include any other branches found in data
        $preferred = [
            'P01','P02','P03','P04','P06','P07','P08','P09','P11','P12','P13','P15','P17',
            'P22','P23','P24','P25','P30',
        ];

$found = $byBranch->keys()->map(fn ($k) => (string) $k)->all();
        $ordered = [];
        foreach ($preferred as $b) {
            if (in_array($b, $found, true)) $ordered[] = $b;
        }
        $others = array_values(array_diff($found, $preferred));
        sort($others);
        $branchOrder = array_merge($ordered, $others);

        $rows = [];

        // Title
        $rows[] = ['Top CIF Movers by Branch (Gainers vs Losers)'];
        $this->mergeRows[] = 1;
        $this->boldRows[]  = 1;

        // Period
        $rows[] = ["Period: {$this->startDate} → {$this->endDate}"];
        $this->mergeRows[] = 2;

        // Spacer
        $rows[] = [''];
        $this->mergeRows[] = 3;

        foreach ($branchOrder as $branchCode) {
            $branchCode = (string) $branchCode;
    $branchName = $this->branchDisplayName($branchCode);
            $branchTitle = "Branch {$branchCode} - {$branchName}";

            // Branch header
            $branchHeaderRow = count($rows) + 1;
            $rows[] = [$branchTitle];
            $this->mergeRows[] = $branchHeaderRow;
            $this->boldRows[]  = $branchHeaderRow;

            // Table headers (side-by-side)
            $headerRow = count($rows) + 1;
            $rows[] = [
                'Rank', 'CIF', 'Customer Name', 'Movement', '',
                'Rank', 'CIF', 'Customer Name', 'Movement',
            ];
            $this->boldRows[] = $headerRow;

            /** @var Collection $items */
            $items = $byBranch->get($branchCode, collect());

            $gainers = $items
                ->filter(fn ($r) => (float) $r->movement > 0 && (float) $r->end_balance >= 0) // overdraft filter on gains
                ->sortByDesc(fn ($r) => (float) $r->movement)
                ->take($limit)
                ->values();

            $losers = $items
                ->filter(fn ($r) => (float) $r->movement < 0)
                ->sortBy(fn ($r) => (float) $r->movement) // most negative first
                ->take($limit)
                ->values();

            $maxRows = max($gainers->count(), $losers->count(), 1);

            for ($i = 0; $i < $maxRows; $i++) {
                $g = $gainers->get($i);
                $l = $losers->get($i);

                $rows[] = [
                    $g ? ($i + 1) : '',
                    $g ? (string) $g->cif : ($items->isEmpty() ? '(no movers)' : ''),
                    $g ? (string) ($g->customer_name ?? '') : '',
                    $g ? (float) ($g->movement ?? 0) : '',
                    '',
                    $l ? ($i + 1) : '',
                    $l ? (string) $l->cif : ($items->isEmpty() ? '(no movers)' : ''),
                    $l ? (string) ($l->customer_name ?? '') : '',
                    $l ? (float) ($l->movement ?? 0) : '',
                ];
            }

            // Spacer between branches
            $rows[] = [''];
            $this->mergeRows[] = count($rows);
        }

        // Pad to 9 columns
        return array_map(fn ($r) => array_pad(is_array($r) ? $r : [$r], 9, ''), $rows);
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach ($this->mergeRows as $r) {
                    $sheet->mergeCells("A{$r}:I{$r}");
                }

                foreach ($this->boldRows as $r) {
                    $sheet->getStyle("A{$r}:I{$r}")->getFont()->setBold(true);
                }

                $sheet->freezePane('A4');
            },
        ];
    }

    private function normalizeBranchCode(string $branchCode): string
    {
        $b = strtoupper(trim($branchCode));
        if (preg_match('/^P(\d{1,2})$/', $b, $m)) {
            return 'P' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
        }
        return $b;
    }

    private function branchDisplayName(string $branchCode): string
    {
        $b = $this->normalizeBranchCode($branchCode);

        return match ($b) {
            'P01' => 'TOWERS',
            'P02' => 'MOMBASA MOI AVENUE',
            'P03' => 'PLAZA',
            'P04' => 'WESTMINSTER',
            'P06' => 'THIKA',
            'P07' => 'ELDORET',
            'P08' => 'KISUMU',
            'P09' => 'KISII',
            'P11' => 'INDUSTRIAL AREA',
            'P12' => 'KARATINA',
            'P13' => 'WESTLANDS',
            'P15' => 'NAKURU',
            'P17' => 'NYERI',
            'P22' => 'UPPER HILL',
            'P23' => 'VALLEY ARCADE',
            'P24' => 'KAREN',
            'P25' => 'NYALI',
            'P30' => 'FORTIS OFFICE PARK',
            'P50' => 'HEAD OFFICE',
            default => $b,
        };
    }
}

/**
 * SHEET 4: Loan Account Movers by Branch
 * Top N loan account gainers/losers per branch, side-by-side.
 * Source: loan_listings (deduped per related_account per date, Corporate excluded).
 */
class LoanAccountMoversByBranchSheet implements FromArray, WithTitle, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    private array $mergeRows       = [];
    private array $boldRows        = [];
    private array $branchHdrRows   = [];
    private array $tableHdrRows    = [];

    public function __construct(
        private readonly string $startDate,
        private readonly string $endDate,
        private readonly int $limit = 10
    ) {}

    public function title(): string { return 'Loan Acct Movers'; }

    public function array(): array
    {
        $limit = max(1, (int) $this->limit);

        $loanStartDate = DB::table('loan_listings')
            ->whereNotNull('as_at_date')->whereDate('as_at_date', '<=', $this->startDate)->max('as_at_date');
        $loanEndDate = DB::table('loan_listings')
            ->whereNotNull('as_at_date')->whereDate('as_at_date', '<=', $this->endDate)->max('as_at_date');

        if (!$loanStartDate || !$loanEndDate || $loanStartDate === $loanEndDate) {
            return array_map(fn ($r) => array_pad($r, 13, ''), [
                ['No loan movement data — only one snapshot available for the selected period.'],
            ]);
        }

        $raw = DB::table('loan_listings as ll')
            ->joinSub(
                DB::table('loan_listings')
                    ->whereIn(DB::raw('DATE(as_at_date)'), [$loanStartDate, $loanEndDate])
                    ->whereRaw("UPPER(TRIM(COALESCE(business_segment,''))) != 'CORPORATE'")
                    ->whereRaw("(TRIM(COALESCE(loan_status, '')) = '' OR loan_status IN ('NORM', 'Normal', 'OAEM', 'SUBS', 'Watch'))")
                    ->select(DB::raw('DATE(as_at_date) AS snap_date'), 'related_account', DB::raw('MAX(id) AS max_id'))
                    ->groupBy(DB::raw('DATE(as_at_date)'), 'related_account'),
                'dedup', 'll.id', '=', 'dedup.max_id'
            )
            ->selectRaw(
                "UPPER(TRIM(COALESCE(NULLIF(TRIM(ll.branch),''), LEFT(ll.related_account, 3)))) AS branch_code,
                 MAX(COALESCE(NULLIF(TRIM(ll.branch_name),''), NULLIF(TRIM(ll.branch),''), LEFT(ll.related_account, 3))) AS branch_name,
                 ll.related_account,
                 MAX(ll.name) AS account_name,
                 SUM(CASE WHEN DATE(ll.as_at_date) = ? THEN ll.loan_book_outstanding ELSE 0 END) AS loan_open,
                 SUM(CASE WHEN DATE(ll.as_at_date) = ? THEN ll.loan_book_outstanding ELSE 0 END) AS loan_close",
                [$loanStartDate, $loanEndDate]
            )
            ->groupByRaw("UPPER(TRIM(COALESCE(NULLIF(TRIM(ll.branch),''), LEFT(ll.related_account, 3)))), ll.related_account")
            ->get()
            ->map(function ($r) {
                $r->loan_movement = round((float) $r->loan_close - (float) $r->loan_open, 2);
                return $r;
            })
            ->filter(fn ($r) => $r->loan_movement != 0)
            ->groupBy(fn ($r) => strtoupper(trim((string) $r->branch_code)));

        $rows   = [];
        $rowNum = 0;

        // Title
        $rows[] = ['Loan Account Movers by Branch (Top Gainers vs Losers)', '', '', '', '', '', '', '', '', '', '', '', ''];
        $this->mergeRows[] = ++$rowNum;
        $this->boldRows[]  = $rowNum;

        // Period / snapshot info
        $rows[] = ["Period: {$this->startDate} → {$this->endDate}  |  Loan snapshots: {$loanStartDate} → {$loanEndDate}  |  Corporate excluded", '', '', '', '', '', '', '', '', '', '', '', ''];
        $this->mergeRows[] = ++$rowNum;

        // Spacer
        $rows[] = array_fill(0, 13, '');
        $this->mergeRows[] = ++$rowNum;

        foreach ($raw as $branchCode => $branchRows) {
            $branchName  = (string) ($branchRows->first()->branch_name ?? $branchCode);
            $displayName = trim($branchName) !== '' && strtoupper($branchName) !== strtoupper($branchCode)
                ? "{$branchCode} — {$branchName}"
                : $branchCode;

            // Branch header
            $rows[] = [$displayName, '', '', '', '', '', '', '', '', '', '', '', ''];
            $this->branchHdrRows[] = ++$rowNum;
            $this->mergeRows[]     = $rowNum;
            $this->boldRows[]      = $rowNum;

            // Column header (gainers left | spacer | losers right)
            $rows[] = [
                'Rank', 'Account No.', 'Name', 'Opening', 'Closing', 'Movement',
                '',
                'Rank', 'Account No.', 'Name', 'Opening', 'Closing', 'Movement',
            ];
            $this->tableHdrRows[] = ++$rowNum;
            $this->boldRows[]     = $rowNum;

            $gainers = $branchRows
                ->filter(fn ($r) => $r->loan_movement > 0)
                ->sortByDesc(fn ($r) => $r->loan_movement)
                ->take($limit)->values();

            $losers = $branchRows
                ->filter(fn ($r) => $r->loan_movement < 0)
                ->sortBy(fn ($r) => $r->loan_movement)
                ->take($limit)->values();

            $maxRows = max($gainers->count(), $losers->count(), 1);

            for ($i = 0; $i < $maxRows; $i++) {
                $g = $gainers->get($i);
                $l = $losers->get($i);
                $rows[] = [
                    $g ? $i + 1 : '',
                    $g ? (string) $g->related_account : ($gainers->isEmpty() ? '(no gainers)' : ''),
                    $g ? (string) ($g->account_name ?? '') : '',
                    $g ? (float) $g->loan_open : '',
                    $g ? (float) $g->loan_close : '',
                    $g ? (float) $g->loan_movement : '',
                    '',
                    $l ? $i + 1 : '',
                    $l ? (string) $l->related_account : ($losers->isEmpty() ? '(no losers)' : ''),
                    $l ? (string) ($l->account_name ?? '') : '',
                    $l ? (float) $l->loan_open : '',
                    $l ? (float) $l->loan_close : '',
                    $l ? (float) $l->loan_movement : '',
                ];
                ++$rowNum;
            }

            // Spacer between branches
            $rows[] = array_fill(0, 13, '');
            $this->mergeRows[] = ++$rowNum;
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'M' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                // Merge + bold tracked rows
                foreach ($this->mergeRows as $r) {
                    $sheet->mergeCells("A{$r}:M{$r}");
                }
                foreach ($this->boldRows as $r) {
                    $sheet->getStyle("A{$r}:M{$r}")->getFont()->setBold(true);
                }

                // Title row — dark green banner
                $sheet->getStyle('A1:M1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '166534']],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(26);

                // Branch header rows — mid green
                foreach ($this->branchHdrRows as $r) {
                    $sheet->getStyle("A{$r}:M{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F6B3F']],
                    ]);
                }

                // Table header rows — gainers side green, losers side red
                foreach ($this->tableHdrRows as $r) {
                    $sheet->getStyle("A{$r}:F{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '166534']],
                    ]);
                    $sheet->getStyle("H{$r}:M{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '991B1B']],
                    ]);
                }

                $sheet->freezePane('A4');
            },
        ];
    }
}