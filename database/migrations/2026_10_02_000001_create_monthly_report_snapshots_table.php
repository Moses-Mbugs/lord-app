<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monthly_report_snapshots', function (Blueprint $table) {
            $table->id();

            // 'loans_deposits' (reports:email-monthly-performance) or 'branches' (reports:email-monthly-branches)
            $table->string('report_type', 40);
            $table->char('month', 7); // YYYY-MM

            // The data dates the snapshot was built from — used to detect a stale snapshot
            // when newer balances / loan files land for the same month.
            $table->date('deposits_as_at');
            $table->date('loans_start')->nullable();
            $table->date('loans_as_at')->nullable();

            // Full MonthlyPerformanceReportService::build() / buildBranches() result
            $table->json('payload');

            $table->timestamps();

            $table->unique(['report_type', 'month'], 'mrs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_report_snapshots');
    }
};
