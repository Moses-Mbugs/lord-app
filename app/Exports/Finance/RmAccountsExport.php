<?php

declare(strict_types=1);

namespace App\Exports\Finance;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RmAccountsExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithTitle,
    ShouldAutoSize,
    WithStyles
{
    public function __construct(
        protected Collection $rows,
        protected string $rmCode,
    ) {
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Account Number',
            'CIF',
            'Customer Name',
            'Branch Code',
            'Status',
        ];
    }

    public function map($row): array
    {
        $dormant = strtoupper(trim((string) ($row->dormant_flag ?? '')));

        return [
            $row->account_number,
            $row->cif,
            $row->customer_name,
            $row->branch_code,
            $dormant === 'Y' ? 'Dormant' : 'Active',
        ];
    }

    public function title(): string
    {
        return substr($this->rmCode, 0, 31) ?: 'RM Accounts';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('1')->getFont()->setBold(true);

        return [];
    }
}
