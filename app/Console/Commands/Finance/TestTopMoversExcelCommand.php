<?php

namespace App\Console\Commands\Finance;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;
use App\Exports\Finance\TopMoversWorkbookExport;

class TestTopMoversExcelCommand extends Command
{
    protected $signature = 'reports:test-topmovers-excel';
    protected $description = 'Quick test to generate a sample Top Movers Excel in storage/app/tmp';

    public function handle(): int
    {
        $grouped = [
            'LCY' => [
                'GAIN' => collect([(object)[
                    'customer_name' => 'TEST',
                    'cif' => '1',
                    'cust_ac_no' => '650',
                    'branch_code' => '834',
                    'currency' => 'KES',
                    'start_balance' => 1,
                    'end_balance' => 2,
                    'movement' => 1,
                ]]),
                'LOSS' => collect(),
            ],
            'FCY' => [
                'GAIN' => collect(),
                'LOSS' => collect(),
            ],
        ];

        $fileName = 'TopMovers_TEST.xlsx';
        $fullPath = storage_path("app/tmp/{$fileName}");

        $binary = Excel::raw(
            new TopMoversWorkbookExport($grouped, '2026-02-12', '2026-02-13'),
            ExcelWriter::XLSX
        );

        if (!$binary || strlen($binary) < 1000) {
            $this->error('Excel binary output is empty/small.');
            return self::FAILURE;
        }

        file_put_contents($fullPath, $binary);

        if (!file_exists($fullPath)) {
            $this->error("File not created: {$fullPath}");
            return self::FAILURE;
        }

        $this->info("✅ Excel created: {$fullPath}");
        $this->line("Size: " . filesize($fullPath) . " bytes");

        return self::SUCCESS;
    }
}
