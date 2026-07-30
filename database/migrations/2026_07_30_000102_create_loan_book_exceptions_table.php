<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loan_book_exceptions', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('loan_book_run_id');

            $table->string('exception_type');
            $table->string('source')->nullable();

            $table->string('related_account')->nullable();
            $table->string('related_customer_id')->nullable();
            $table->string('name')->nullable();

            $table->decimal('amount', 22, 2)->nullable();

            $table->text('remarks')->nullable();
            $table->longText('payload')->nullable();

            $table->timestamps();

            $table->foreign('loan_book_run_id')
                ->references('id')
                ->on('loan_book_runs')
                ->onDelete('cascade');

            $table->index('loan_book_run_id');
            $table->index('exception_type');
            $table->index('source');
            $table->index('related_account');
            $table->index('related_customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_book_exceptions');
    }
};
