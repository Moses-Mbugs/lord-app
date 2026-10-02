<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Local Corporates is a Commercial Banking sub-segment, not Corporate Banking. Every report
 * resolves a customer's segment from sub_segment_mappings.business first (CifSegment,
 * WeeklySegmentReportService, loans via LoanMovementService::cifBusinessSubquery()), so
 * re-pointing these rows moves Local Corporates under Commercial everywhere — and out of the
 * branch reports' "exclude Corporate" filter.
 */
return new class extends Migration {
    public function up(): void
    {
        $this->setBusiness('COMMERCIAL BANKING', 'Commercial Banking');
    }

    public function down(): void
    {
        $this->setBusiness('CORPORATE BANKING', 'Corporate Banking');
    }

    /** Re-points the Local Corporates rows, reusing the exact spelling the table already uses for $upper. */
    private function setBusiness(string $upper, string $fallback): void
    {
        $value = DB::table('sub_segment_mappings')
            ->whereRaw('UPPER(TRIM(business)) = ?', [$upper])
            ->value('business') ?? $fallback;

        DB::table('sub_segment_mappings')
            ->where(function ($q) {
                $q->whereRaw("LOWER(TRIM(COALESCE(business_segment_name, ''))) IN ('local corporates', 'local corporate')")
                  ->orWhereRaw("UPPER(TRIM(COALESCE(business_seg_short, ''))) = 'LC'");
            })
            ->update(['business' => $value, 'updated_at' => now()]);
    }
};
