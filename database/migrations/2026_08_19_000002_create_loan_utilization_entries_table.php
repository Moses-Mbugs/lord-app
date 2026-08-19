<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loan_utilization_entries', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('snapshot_id');

            $table->string('account_reference')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('product_name')->nullable();
            $table->string('credit_line_code')->nullable();

            $table->decimal('gross_outstanding_lcy', 22, 2)->default(0);
            $table->unsignedInteger('dpd')->default(0);

            $table->string('classification_code')->nullable();
            $table->string('risk_rating')->nullable();
            $table->string('classification_label')->nullable();
            $table->string('ifrs9_stage')->nullable();
            $table->string('performance_status')->nullable();

            $table->date('value_date')->nullable();
            $table->string('business')->nullable();

            $table->timestamps();

            $table->foreign('snapshot_id')
                ->references('id')
                ->on('loan_utilization_snapshots')
                ->onDelete('cascade');

            $table->index('snapshot_id');
            $table->index(['snapshot_id', 'product_name']);
            $table->index(['snapshot_id', 'performance_status']);
            $table->index('credit_line_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_utilization_entries');
    }
};
