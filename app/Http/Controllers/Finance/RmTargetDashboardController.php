<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\RelationshipManager;
use App\Models\Finance\RmTarget;
use App\Services\Finance\RmTargetActualsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RmTargetDashboardController extends Controller
{
    public function __construct(private readonly RmTargetActualsService $actualsService)
    {
    }

    /**
     * Same 1-5 rating scale used on the branch dashboard (BranchDailyPerformanceSummaryService
     * views / bdGrade()): 5 = Far Exceeds (>=120%), 4 = Exceeds (101-119%), 3 = Meets (96-100%),
     * 2 = Partially Meets (50-95%), 1 = Doesn't Meet (<50%).
     */
    private function gradeFor(?float $pct): ?int
    {
        if ($pct === null) {
            return null;
        }

        return match (true) {
            $pct >= 120 => 5,
            $pct >= 101 => 4,
            $pct >= 96  => 3,
            $pct >= 50  => 2,
            default     => 1,
        };
    }

    public function index(Request $request): View
    {
        $year = (int) $request->input('year', now()->year);

        $targetsByRm = RmTarget::query()
            ->where('period_year', $year)
            ->get()
            ->keyBy('rm_code');

        $actuals = $this->actualsService->forYear($year);

        $rms = RelationshipManager::query()->orderBy('rm_code')->get();

        $rows = $rms->map(function (RelationshipManager $rm) use ($targetsByRm, $actuals) {
            $code   = strtoupper(trim($rm->rm_code));
            $target = $targetsByRm->get($code);
            $actual = $actuals[$code] ?? ['actual_deposits' => 0.0, 'actual_loans' => 0.0, 'actual_ntb' => 0];

            $hasTarget = $target !== null;

            $depositPct = $hasTarget && $target->deposit_target > 0
                ? round(((float) $actual['actual_deposits'] / (float) $target->deposit_target) * 100, 1)
                : null;
            $loanPct = $hasTarget && $target->loan_target > 0
                ? round(((float) $actual['actual_loans'] / (float) $target->loan_target) * 100, 1)
                : null;
            $ntbPct = $hasTarget && $target->ntb_target > 0
                ? round(((int) $actual['actual_ntb'] / (int) $target->ntb_target) * 100, 1)
                : null;

            return [
                'rm_code'          => $rm->rm_code,
                'name'             => $rm->name,
                'segment'          => $rm->segment,
                'subsegment'       => $rm->subsegment,
                'has_target'       => $hasTarget,
                'deposit_target'   => $hasTarget ? (float) $target->deposit_target : null,
                'loan_target'      => $hasTarget ? (float) $target->loan_target : null,
                'ntb_target'       => $hasTarget ? (int) $target->ntb_target : null,
                'actual_deposits'  => (float) $actual['actual_deposits'],
                'actual_loans'     => (float) $actual['actual_loans'],
                'actual_ntb'       => (int) $actual['actual_ntb'],
                'deposit_pct'      => $depositPct,
                'loan_pct'         => $loanPct,
                'ntb_pct'          => $ntbPct,
                'deposit_grade'    => $this->gradeFor($depositPct),
                'loan_grade'       => $this->gradeFor($loanPct),
                'ntb_grade'        => $this->gradeFor($ntbPct),
            ];
        })->sortByDesc('actual_deposits')->values();

        $targeted = $rows->where('has_target', true);

        $targetTotals = [
            'deposits' => (float) $targeted->sum('deposit_target'),
            'loans'    => (float) $targeted->sum('loan_target'),
            'ntb'      => (int) $targeted->sum('ntb_target'),
        ];

        $actualTotals = [
            'deposits' => (float) $targeted->sum('actual_deposits'),
            'loans'    => (float) $targeted->sum('actual_loans'),
            'ntb'      => (int) $targeted->sum('actual_ntb'),
        ];

        return view('finance.rm-targets.dashboard', [
            'rows'            => $rows,
            'year'            => $year,
            'targetTotals'    => $targetTotals,
            'actualTotals'    => $actualTotals,
            'targetedCount'   => $targeted->count(),
            'untargetedCount' => $rows->count() - $targeted->count(),
        ]);
    }
}
