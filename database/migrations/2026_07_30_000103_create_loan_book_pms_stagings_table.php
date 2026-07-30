<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loan_book_pms_stagings', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('loan_book_run_id');
            $table->unsignedInteger('processed_by')->nullable();

            $table->unsignedInteger('row_number')->nullable();

            $table->string('gl_code')->nullable();
            $table->string('related_account')->nullable();
            $table->string('related_customer_id')->nullable();
            $table->string('name')->nullable();

            $table->decimal('outstanding_amount', 22, 2)->nullable();

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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_book_pms_stagings');
    }
};
