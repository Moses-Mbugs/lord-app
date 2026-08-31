<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rm_performance_monthly', function (Blueprint $table) {
            $table->id();

            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');

            $table->string('rm_code', 20);

            // Deposits — mobilization proxy (month-end closing balance + movement vs previous populated month)
            $table->decimal('deposit_closing_balance', 20, 2)->default(0);
            $table->decimal('deposit_movement', 20, 2)->nullable();
            $table->date('balance_snapshot_date')->nullable();

            // Loans — disbursement proxy (value_dt bucketed, loan_book_outstanding summed)
            $table->decimal('loan_disbursed_proxy', 20, 2)->default(0);
            $table->unsignedInteger('loan_disbursed_count')->default(0);
            $table->date('loan_snapshot_date')->nullable();

            // NTB — accounts opened
            $table->unsignedInteger('ntb_count')->default(0);

            $table->timestamps();

            $table->unique(['period_year', 'period_month', 'rm_code'], 'rm_perf_month_period_rm_unique');
            $table->index('rm_code');
            $table->index(['period_year', 'rm_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rm_performance_monthly');
    }
};
