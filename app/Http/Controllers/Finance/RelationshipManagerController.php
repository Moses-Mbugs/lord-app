<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\RelationshipManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class RelationshipManagerController extends Controller
{
    public function index(): View
    {
        return view('finance.relationship-managers.index');
    }

    public function data(Request $request): JsonResponse
    {
        $search  = trim((string) $request->input('search', ''));
        $segment = trim((string) $request->input('segment', ''));

        $query = RelationshipManager::query()->orderBy('rm_code');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('rm_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('staff_number', 'like', "%{$search}%");
            });
        }

        if ($segment !== '') {
            $query->where('segment', $segment);
        }

        $rms = $query->get();

        $segmentCounts = RelationshipManager::query()
            ->select('segment', DB::raw('COUNT(*) as count'))
            ->whereNotNull('segment')
            ->where('segment', '<>', '')
            ->groupBy('segment')
            ->orderBy('segment')
            ->get();

        return response()->json([
            'success'        => true,
            'rows'           => $rms,
            'total'          => RelationshipManager::count(),
            'segment_counts' => $segmentCounts,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'rm_code'      => ['required', 'string', 'max:10', 'unique:relationship_managers,rm_code'],
            'name'         => ['required', 'string', 'max:255'],
            'staff_number' => ['nullable', 'string', 'max:20', 'unique:relationship_managers,staff_number'],
            'segment'      => ['nullable', 'string', 'max:50'],
        ]);

        $data['rm_code'] = strtoupper(trim($data['rm_code']));

        $rm = RelationshipManager::create($data);

        return response()->json([
            'success' => true,
            'message' => "RM {$rm->rm_code} added successfully.",
            'rm'      => $rm,
        ]);
    }

    public function update(Request $request, RelationshipManager $relationshipManager): JsonResponse
    {
        $data = $request->validate([
            'rm_code'      => ['required', 'string', 'max:10', "unique:relationship_managers,rm_code,{$relationshipManager->id}"],
            'name'         => ['required', 'string', 'max:255'],
            'staff_number' => ['nullable', 'string', 'max:20', "unique:relationship_managers,staff_number,{$relationshipManager->id}"],
            'segment'      => ['nullable', 'string', 'max:50'],
        ]);

        $data['rm_code'] = strtoupper(trim($data['rm_code']));

        $relationshipManager->update($data);

        return response()->json([
            'success' => true,
            'message' => "RM {$relationshipManager->rm_code} updated successfully.",
            'rm'      => $relationshipManager->fresh(),
        ]);
    }

    public function destroy(RelationshipManager $relationshipManager): JsonResponse
    {
        $code = $relationshipManager->rm_code;
        $relationshipManager->delete();

        return response()->json([
            'success' => true,
            'message' => "RM {$code} removed.",
        ]);
    }

    public function segments(): JsonResponse
    {
        $segments = [];

        if (Schema::hasColumn('relationship_managers', 'segment')) {
            $segments = RelationshipManager::query()
                ->select('segment')
                ->whereNotNull('segment')
                ->where('segment', '<>', '')
                ->distinct()
                ->orderBy('segment')
                ->pluck('segment')
                ->values()
                ->all();
        }

        return response()->json($segments);
    }
}
