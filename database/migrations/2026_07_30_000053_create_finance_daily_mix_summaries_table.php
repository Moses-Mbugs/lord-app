<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('finance_daily_mix_summaries', function (Blueprint $table) {
            $table->id();

            $table->date('balance_date');
            $table->string('summary_scope', 20)->default('OVERALL')->after('balance_date');
            $table->string('segment_code', 10)->default('ALL')->after('summary_scope');
            $table->string('segment_name', 100)->default('Overall')->after('segment_code');

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

            $table->decimal('total_positive_lcy_balance', 20, 2)->default(0);
            $table->unsignedBigInteger('source_row_count')->default(0);

            $table->json('currency_mix_json')->nullable();
            $table->json('deposit_mix_json')->nullable();
            $table->timestamp('last_built_at')->nullable();

            $table->timestamps();

            $table->unique(['balance_date', 'summary_scope', 'segment_code'], 'finance_daily_mix_summaries_balance_scope_segment_unique');
            $table->index(['summary_scope', 'segment_code', 'balance_date'], 'finance_daily_mix_summaries_scope_segment_date_index');
            $table->index('last_built_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_daily_mix_summaries');
    }
};
