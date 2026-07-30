<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loan_book_runs', function (Blueprint $table) {
            $table->increments('id');

            $table->string('batch_reference')->unique();
            $table->date('loan_book_date')->nullable();

            $table->string('pms_original_filename')->nullable();
            $table->string('loan_details_original_filename')->nullable();
            $table->string('portfolio_original_filename')->nullable();
            $table->string('credit_cards_original_filename')->nullable();
            $table->string('lms_original_filename')->nullable();

            $table->unsignedInteger('total_pms_rows')->default(0);
            $table->unsignedInteger('total_loan_details_rows')->default(0);
            $table->integer('total_portfolio_rows_read')->default(0);
            $table->integer('total_portfolio_rows_selected')->default(0);
            $table->integer('total_credit_card_rows_read')->default(0);
            $table->integer('total_credit_card_rows_selected')->default(0);
            $table->unsignedInteger('total_lms_rows_read')->default(0);
            $table->unsignedInteger('total_lms_rows_selected')->default(0);
            $table->unsignedInteger('total_final_loan_book_rows')->default(0);
            $table->unsignedInteger('total_exceptions')->default(0);

            $table->decimal('total_pms_net_outstanding', 22, 2)->default(0);
            $table->decimal('total_pms_negative_outstanding', 22, 2)->default(0);
            $table->decimal('total_loan_book_outstanding', 22, 2)->default(0);
            $table->decimal('control_difference', 22, 2)->default(0);

            $table->string('status')->default('pending');
            $table->unsignedInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->longText('control_summary')->nullable();
            $table->longText('failure_reason')->nullable();

            $table->timestamps();

            $table->index('loan_book_date');
            $table->index('status');
            $table->index('processed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_book_runs');
    }
};
