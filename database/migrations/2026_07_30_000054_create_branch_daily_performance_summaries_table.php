<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branch_daily_performance_summaries', function (Blueprint $table) {
            $table->id();

            $table->date('balance_date');
            $table->date('loan_as_of_date');
            $table->string('branch_code', 10);
            $table->string('branch_name', 100);

            $table->decimal('target_deposits', 20, 2)->default(0);
            $table->unsignedInteger('target_accounts')->default(0);
            $table->decimal('target_lending', 20, 2)->default(0);

            $table->decimal('actual_deposits', 20, 2)->default(0);
            $table->unsignedInteger('actual_accounts')->default(0);
            $table->decimal('actual_lending', 20, 2)->default(0);

            $table->decimal('deposit_pct', 8, 2)->default(0);
            $table->decimal('account_pct', 8, 2)->default(0);
            $table->decimal('lending_pct', 8, 2)->default(0);
            $table->decimal('ldr_pct', 8, 2)->default(0);

            $table->decimal('lcy_amount', 20, 2)->default(0);
            $table->decimal('fcy_amount', 20, 2)->default(0);
            $table->decimal('lcy_pct', 8, 2)->default(0);
            $table->decimal('fcy_pct', 8, 2)->default(0);

            $table->decimal('current_amount', 20, 2)->default(0);
            $table->decimal('savings_amount', 20, 2)->default(0);
            $table->decimal('term_amount', 20, 2)->default(0);
            $table->decimal('current_pct', 8, 2)->default(0);
            $table->decimal('savings_pct', 8, 2)->default(0);
            $table->decimal('term_pct', 8, 2)->default(0);

            $table->decimal('casa_amount', 20, 2)->default(0);
            $table->decimal('casa_pct', 8, 2)->default(0);

            $table->decimal('mtd_movement', 20, 2)->nullable();
            $table->decimal('ytd_movement', 20, 2)->nullable();
            $table->date('mtd_reference_date')->nullable();
            $table->date('ytd_reference_date')->nullable();

            $table->unsignedInteger('total_cifs')->default(0)->after('ytd_reference_date');
            $table->unsignedInteger('total_accounts')->default(0)->after('total_cifs');
            $table->unsignedInteger('dormant_accounts')->default(0)->after('total_accounts');
            $table->decimal('dormancy_rate', 8, 2)->default(0)->after('dormant_accounts');

            $table->decimal('mtd_loan_movement', 20, 2)->nullable()->after('dormancy_rate');
            $table->decimal('ytd_loan_movement', 20, 2)->nullable()->after('mtd_loan_movement');
            $table->date('mtd_loan_reference_date')->nullable()->after('ytd_loan_movement');
            $table->date('ytd_loan_reference_date')->nullable()->after('mtd_loan_reference_date');

            $table->timestamp('last_built_at')->nullable();

            $table->timestamps();

            $table->unique(['balance_date', 'loan_as_of_date', 'branch_code'], 'branch_daily_perf_summaries_date_loan_branch_unique');
            $table->index(['branch_code', 'balance_date'], 'branch_daily_perf_summaries_branch_date_index');
            $table->index('last_built_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_daily_performance_summaries');
    }
};
