<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Loans\LoanUtilizationSnapshot;
use App\Models\Loans\LoanUtilizationApprovedLimit;
use App\Exports\Loans\LoanUtilizationExport;

$snapshot = LoanUtilizationSnapshot::where('status', 'completed')->latest('id')->first();
echo "Using snapshot id={$snapshot->id}\n";

// Set an approved limit like the UI would
LoanUtilizationApprovedLimit::updateOrCreate(
    ['product_name' => 'Personal Loan/Pension Backed/Insurance Premium Financing'],
    ['approved_limit' => 5000000000, 'updated_by' => null]
);

$start = microtime(true);
$export = $app->make(LoanUtilizationExport::class);
$path = $export->generate($snapshot, 'test_export.xlsx');
echo "Export written to: $path in " . round(microtime(true) - $start, 2) . "s\n";
echo "File size: " . filesize($path) . " bytes\n";

// Re-read it to confirm sheets and dashboard values
$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($path);
echo "Sheets: " . implode(', ', $spreadsheet->getSheetNames()) . "\n";

$dash = $spreadsheet->getSheetByName('Dashboard');
$rows = $dash->toArray(null, true, true, false);
foreach ($rows as $i => $row) {
    if ($i > 15) break;
    echo $i . ': ' . implode(' | ', array_map(fn($v) => is_float($v) ? round($v, 2) : $v, $row)) . "\n";
}

unlink($path);
