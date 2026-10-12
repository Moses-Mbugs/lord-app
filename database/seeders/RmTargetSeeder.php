<?php

namespace Database\Seeders;

use App\Models\Finance\RmTarget;
use Illuminate\Database\Seeder;

class RmTargetSeeder extends Seeder
{
    /**
     * FY2026 deposit/NTB targets for the current 17-RM portfolio, sourced from
     * Frontline Budgets.xlsx (RMS-protected; last re-exported to CSV and
     * imported 2026-10). loan_target is left at 0 — the source workbook's
     * loan target column is blank for every RM. Kinanga (KE1222) and Nyawira
     * (KE1296), who left the bank, are intentionally excluded. Joan Sang
     * (KE1343) is a new RM not yet in the budget workbook, so her target is
     * 0 until finance sets one via /rm-targets/manage.
     *
     * Re-running this seeder is safe: it upserts on (rm_code, period_year),
     * so it won't clobber any value an admin has since edited through the UI
     * unless the file is re-run with changed figures here.
     */
    private const TARGETS = [
        'KE0343' => ['deposit' => 58366824.86,   'ntb' => 300],
        'KE0445' => ['deposit' => 68287727.90,   'ntb' => 300],
        'KE0539' => ['deposit' => 2088174097.06, 'ntb' => 180],
        'KE0827' => ['deposit' => 1076026349.80, 'ntb' => 180],
        'KE0887' => ['deposit' => 450124631.52,  'ntb' => 300],
        'KE0949' => ['deposit' => 243170259.98,  'ntb' => 300],
        'KE1187' => ['deposit' => 222404066.38,  'ntb' => 300],
        'KE1189' => ['deposit' => 2077748688.46, 'ntb' => 180],
        'KE1228' => ['deposit' => 2939025543.26, 'ntb' => 180],
        'KE1229' => ['deposit' => 221127456.64,  'ntb' => 300],
        'KE1262' => ['deposit' => 139527323.64,  'ntb' => 300],
        'KE1285' => ['deposit' => 693585916.88,  'ntb' => 240],
        'KE1286' => ['deposit' => 209119261.82,  'ntb' => 300],
        'KE1301' => ['deposit' => 617604210.64,  'ntb' => 180],
        'KE1318' => ['deposit' => 238363678.08,  'ntb' => 300],
        'KE1330' => ['deposit' => 1456355266.88, 'ntb' => 35],
        'KE1343' => ['deposit' => 0,             'ntb' => 0],
    ];

    public function run(): void
    {
        $year = 2026;

        foreach (self::TARGETS as $rmCode => $target) {
            RmTarget::updateOrCreate(
                ['rm_code' => $rmCode, 'period_year' => $year],
                [
                    'deposit_target' => $target['deposit'],
                    'loan_target'    => 0,
                    'ntb_target'     => $target['ntb'],
                    'updated_by'     => 'database:seed --class=RmTargetSeeder',
                ]
            );
        }

        $this->command?->info(count(self::TARGETS) . " RM targets seeded for FY{$year}.");
    }
}
