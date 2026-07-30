<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loan_book_entries', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('loan_book_run_id');

            $table->string('source_report')->nullable();
            $table->string('source_type')->nullable();

            $table->string('related_account')->nullable();
            $table->string('related_customer_id')->nullable();
            $table->string('name')->nullable();

            $table->string('branch_name')->nullable();
            $table->string('gl_name')->nullable();

            $table->string('frr')->nullable();
            $table->string('orr')->nullable();
            $table->string('account_status')->nullable();

            $table->date('value_dt')->nullable();
            $table->date('maturity_date')->nullable();

            $table->string('linecode')->nullable();
            $table->string('branch')->nullable();
            $table->string('product_type')->nullable();
            $table->string('currency')->nullable();
            $table->string('industrycode')->nullable();
            $table->string('status')->nullable();

            $table->decimal('interest_rate', 18, 6)->nullable();
            $table->decimal('exch_rate', 18, 6)->nullable();

            $table->string('tenor')->nullable();

            $table->decimal('limit', 22, 2)->nullable();
            $table->decimal('limit_lcy', 22, 2)->nullable();

            $table->string('group_code')->nullable();
            $table->string('sub_sic_code')->nullable();
            $table->string('business_segment')->nullable();
            $table->string('product_code')->nullable();

            $table->date('latest_status_change')->nullable();

            $table->string('rm_officer')->nullable();
            $table->string('collateral_code')->nullable();

            $table->text('pms_gl_codes')->nullable();

            $table->decimal('lcy_curr_balance', 20, 2)->nullable();
            $table->decimal('amount_arrears', 20, 2)->nullable();
            $table->integer('days_in_arrears')->nullable();
            $table->integer('pdo_days')->nullable();

            $table->date('status_since')->nullable();

            $table->string('card_account')->nullable();
            $table->string('contract_currency')->nullable();

            $table->decimal('outstanding_amount', 22, 2)->nullable();

            $table->string('lms_loan_account_no')->nullable();
            $table->string('application_ref')->nullable();
            $table->decimal('principal_outstanding', 22, 2)->nullable();
            $table->decimal('interest_outstanding', 22, 2)->nullable();
            $table->decimal('penalty_outstanding', 22, 2)->nullable();
            $table->decimal('total_repaid', 22, 2)->nullable();
            $table->decimal('total_fee_revenue', 22, 2)->nullable();
            $table->decimal('dl_fee', 22, 2)->nullable();
            $table->decimal('processing_fee', 22, 2)->nullable();
            $table->decimal('insurance_fee', 22, 2)->nullable();
            $table->decimal('excise_duty', 22, 2)->nullable();

            $table->text('description')->nullable();
            $table->integer('source_row_number')->nullable();

            $table->decimal('net_outstanding_amount', 22, 2)->default(0);
            $table->decimal('loan_book_outstanding', 22, 2)->default(0);
            $table->decimal('outstanding_amount_lcy', 22, 2)->default(0);

            $table->timestamps();

            $table->foreign('loan_book_run_id')
                ->references('id')
                ->on('loan_book_runs')
                ->onDelete('cascade');

            $table->index('loan_book_run_id');
            $table->index('related_account');
            $table->index('related_customer_id');
            $table->index('currency');
            $table->index('branch');
            $table->index('business_segment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_book_entries');
    }
};
