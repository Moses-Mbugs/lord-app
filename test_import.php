<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\UploadedFile;
use App\Services\Loans\LoanUtilizationImportService;
use App\Models\Loans\LoanUtilizationSnapshot;
use App\Models\Loans\LoanUtilizationEntry;
use App\Services\Loans\LoanUtilizationDashboardService;

$path = __DIR__ . '/storage/app/test-loans-portfolio.xls';
$file = new UploadedFile($path, 'LOANS PORTFOLIO NEW_v1_18.08.2026.xls', null, null, true);

$service = $app->make(LoanUtilizationImportService::class);

try {
    $snapshot = $service->import($file, null);
    echo "Snapshot created: id={$snapshot->id} status={$snapshot->status} rows={$snapshot->total_rows} exposure={$snapshot->total_exposure_lcy} as_of={$snapshot->as_of_date}\n";
} catch (\Throwable $e) {
    echo "IMPORT FAILED: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "Entries count in DB: " . LoanUtilizationEntry::where('snapshot_id', $snapshot->id)->count() . "\n";

$dashboardService = $app->make(LoanUtilizationDashboardService::class);
$dashboard = $dashboardService->build($snapshot);

echo "\n--- Dashboard ---\n";
foreach ($dashboard['products'] as $p) {
    printf(
        "%-58s vol=%-5d perf=%-14s nonPerf=%-14s total=%-14s npl=%s%%\n",
        $p['product_name'],
        $p['volume'],
        number_format($p['performing']),
        number_format($p['non_performing']),
        number_format($p['total']),
        number_format($p['npl_ratio'] * 100, 1)
    );
}
$t = $dashboard['totals'];
echo "\nGRAND TOTAL vol={$t['volume']} perf=" . number_format($t['performing']) . " nonPerf=" . number_format($t['non_performing']) . " total=" . number_format($t['total']) . " npl=" . number_format($t['npl_ratio']*100,2) . "%\n";
