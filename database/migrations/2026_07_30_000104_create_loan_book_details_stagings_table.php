<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loan_book_details_stagings', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('loan_book_run_id');
            $table->unsignedInteger('processed_by')->nullable();

            $table->unsignedInteger('row_number')->nullable();

            $table->string('related_account')->nullable();
            $table->string('related_customer_id')->nullable();
            $table->string('name')->nullable();

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

            $table->decimal('limit_amount', 22, 2)->nullable();
            $table->decimal('limit_lcy', 22, 2)->nullable();

            $table->string('group_code')->nullable();
            $table->string('sub_sic_code')->nullable();
            $table->string('business_segment')->nullable();
            $table->string('product_code')->nullable();

            $table->date('latest_status_change')->nullable();

            $table->string('rm_officer')->nullable();
            $table->string('collateral_code')->nullable();

            $table->longText('raw_payload')->nullable();

            $table->timestamps();

            $table->foreign('loan_book_run_id')
                ->references('id')
                ->on('loan_book_runs')
                ->onDelete('cascade');

            $table->index('loan_book_run_id');
            $table->index('processed_by');
            $table->index('related_account');
            $table->index('related_customer_id');
            $table->index('currency');
            $table->index('branch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_book_details_stagings');
    }
};
