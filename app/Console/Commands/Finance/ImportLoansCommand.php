<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Services\Reports\LoanImportService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\File\File;

class ImportLoansCommand extends Command
{
    protected $signature = 'loans:import
                            {path : Path to the Loan Book Excel file (.xlsx)}
                            {--date= : Override the as-at date (YYYY-MM-DD)}';

    protected $description = 'Import a Loan Book Excel file into the loan_listings table';

    public function handle(LoanImportService $importer): int
    {
        $path = $this->argument('path');
        $date = $this->option('date') ?: null;

        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $this->info("Importing: {$path}");

        // Wrap as UploadedFile so the service can use the same code path
        $file = new UploadedFile(
            $path,
            basename($path),
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true // test mode — skip is_uploaded_file check
        );

        try {
            $result = $importer->importFromUpload($file, $date);

            $this->info("Done.");
            $this->table(
                ['Date', 'Inserted', 'Skipped', 'File'],
                [[$result['as_at_date'], $result['inserted'], $result['skipped'], $result['filename']]]
            );

            $this->info('Rebuilding RM workload summary...');
            Artisan::call('finance:build-rm-workload', [], $this->output);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
