<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_profitability_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('upload_batch_id')->constrained('upload_batches')->onDelete('cascade');
            $table->string('record_type', 10); // 'ytd' | 'monthly'

            $table->string('cif')->nullable();
            $table->string('name')->nullable();
            $table->string('segment', 10)->nullable();
            $table->string('rm')->nullable();
            $table->string('month', 10)->nullable(); // null for ytd rows

            $table->decimal('interest_from_loans', 20, 6)->default(0);
            $table->decimal('interest_from_ods', 20, 6)->default(0);
            $table->decimal('interest_from_trade', 20, 6)->default(0);
            $table->decimal('total_interest_income', 20, 6)->default(0);
            $table->decimal('interest_paid', 20, 6)->default(0);
            $table->decimal('net_ftp_interest', 20, 6)->default(0);
            $table->decimal('net_interest_income', 20, 6)->default(0);

            $table->decimal('payments', 20, 6)->default(0);
            $table->decimal('receivables', 20, 6)->default(0);
            $table->decimal('liquidity', 20, 6)->default(0);
            $table->decimal('cash_management', 20, 6)->default(0);

            $table->decimal('fees_and_commissions', 20, 6)->default(0);
            $table->decimal('trade_fees', 20, 6)->default(0);
            $table->decimal('acquiring_expense', 20, 6)->default(0);
            $table->decimal('total_fees', 20, 6)->default(0);

            $table->decimal('fx_income', 20, 6)->default(0);
            $table->decimal('other_income', 20, 6)->default(0);
            $table->decimal('total_revenue', 20, 6)->default(0);

            // Monthly-only fields; null on ytd rows
            $table->decimal('ftp_income', 20, 6)->nullable();
            $table->decimal('ftp_expense', 20, 6)->nullable();
            $table->decimal('casa_lcy', 20, 6)->nullable();
            $table->decimal('casa_fcy', 20, 6)->nullable();
            $table->decimal('term_deposits_lcy', 20, 6)->nullable();
            $table->decimal('term_deposits_fcy', 20, 6)->nullable();
            $table->decimal('total_deposits', 20, 6)->nullable();
            $table->decimal('loans_lcy', 20, 6)->nullable();
            $table->decimal('loans_fcy', 20, 6)->nullable();
            $table->decimal('od_lcy', 20, 6)->nullable();
            $table->decimal('od_fcy', 20, 6)->nullable();
            $table->decimal('gross_loans', 20, 6)->nullable();

            $table->timestamps();

            $table->index(['upload_batch_id', 'record_type'], 'cpr_batch_type_idx');
            $table->index(['upload_batch_id', 'record_type', 'segment'], 'cpr_batch_type_seg_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_profitability_records');
    }
};
