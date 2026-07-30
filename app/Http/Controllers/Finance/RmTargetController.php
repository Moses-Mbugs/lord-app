<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\RelationshipManager;
use App\Models\Finance\RmTarget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RmTargetController extends Controller
{
    public function index(): View
    {
        return view('finance.rm-targets.manage');
    }

    public function data(Request $request): JsonResponse
    {
        $year   = (int) $request->input('year', now()->year);
        $search = trim((string) $request->input('search', ''));

        $query = RmTarget::query()
            ->where('period_year', $year)
            ->orderBy('rm_code');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('rm_code', 'like', "%{$search}%");
            });
        }

        $targets = $query->get();

        $rmsByCode = RelationshipManager::query()
            ->whereIn('rm_code', $targets->pluck('rm_code'))
            ->get()
            ->keyBy('rm_code');

        $rows = $targets->map(function (RmTarget $t) use ($rmsByCode) {
            $rm = $rmsByCode->get(strtoupper(trim($t->rm_code)));

            return [
                'id'             => $t->id,
                'rm_code'        => $t->rm_code,
                'name'           => $rm?->name,
                'segment'        => $rm?->segment,
                'period_year'    => $t->period_year,
                'deposit_target' => (float) $t->deposit_target,
                'loan_target'    => (float) $t->loan_target,
                'ntb_target'     => (int) $t->ntb_target,
                'created_by'     => $t->created_by,
                'updated_by'     => $t->updated_by,
                'updated_at'     => $t->updated_at,
            ];
        });

        $years = RmTarget::query()
            ->select('period_year')
            ->distinct()
            ->orderByDesc('period_year')
            ->pluck('period_year');

        if (! $years->contains($year)) {
            $years = $years->push($year)->sortByDesc(fn ($y) => $y)->values();
        }

        return response()->json([
            'success' => true,
            'rows'    => $rows->values(),
            'total'   => $rows->count(),
            'year'    => $year,
            'years'   => $years,
        ]);
    }

    public function rmList(): JsonResponse
    {
        $rms = RelationshipManager::query()
            ->orderBy('rm_code')
            ->get(['rm_code', 'name', 'segment']);

        return response()->json($rms);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $rmCode = strtoupper(trim($data['rm_code']));

        $exists = RmTarget::query()
            ->where('rm_code', $rmCode)
            ->where('period_year', $data['period_year'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => "A target for {$rmCode} in {$data['period_year']} already exists. Edit it instead.",
            ], 422);
        }

        if (! RelationshipManager::query()->where('rm_code', $rmCode)->exists()) {
            return response()->json([
                'success' => false,
                'message' => "RM code {$rmCode} does not exist. Add them under RM Management first.",
            ], 422);
        }

        $target = RmTarget::create([
            'rm_code'        => $rmCode,
            'period_year'    => $data['period_year'],
            'deposit_target' => $data['deposit_target'],
            'loan_target'    => $data['loan_target'],
            'ntb_target'     => $data['ntb_target'],
            'created_by'     => Auth::user()?->name,
            'updated_by'     => Auth::user()?->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Target for {$rmCode} ({$data['period_year']}) added.",
            'target'  => $target,
        ]);
    }

    public function update(Request $request, RmTarget $rmTarget): JsonResponse
    {
        $data   = $this->validated($request);
        $rmCode = strtoupper(trim($data['rm_code']));

        $target = RmTarget::query()
            ->where('rm_code', $rmCode)
            ->where('period_year', $data['period_year'])
            ->where('id', '<>', $rmTarget->id)
            ->exists();

        if ($target) {
            return response()->json([
                'success' => false,
                'message' => "A target for {$rmCode} in {$data['period_year']} already exists.",
            ], 422);
        }

        $rmTarget->update([
            'rm_code'        => $rmCode,
            'period_year'    => $data['period_year'],
            'deposit_target' => $data['deposit_target'],
            'loan_target'    => $data['loan_target'],
            'ntb_target'     => $data['ntb_target'],
            'updated_by'     => Auth::user()?->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Target for {$rmCode} updated.",
            'target'  => $rmTarget->fresh(),
        ]);
    }

    public function destroy(RmTarget $rmTarget): JsonResponse
    {
        $code = $rmTarget->rm_code;
        $year = $rmTarget->period_year;
        $rmTarget->delete();

        return response()->json([
            'success' => true,
            'message' => "Target for {$code} ({$year}) removed.",
        ]);
    }

    /**
     * @return array{rm_code: string, period_year: int, deposit_target: float, loan_target: float, ntb_target: int}
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'rm_code'        => ['required', 'string', 'max:10'],
            'period_year'    => ['required', 'integer', 'min:2000', 'max:2100'],
            'deposit_target' => ['required', 'numeric', 'min:0'],
            'loan_target'    => ['required', 'numeric', 'min:0'],
            'ntb_target'     => ['required', 'integer', 'min:0'],
        ]);
    }
}
