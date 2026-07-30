<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rm_workload_summary', function (Blueprint $table) {
            $table->id();

            $table->string('rm_code', 20)->unique();
            $table->string('officer_name')->nullable();
            $table->string('segment', 50)->nullable();
            $table->string('subsegment', 50)->nullable();

            $table->unsignedInteger('cif_count')->default(0);
            $table->unsignedInteger('account_count')->default(0);
            $table->unsignedInteger('dormant_count')->default(0);
            $table->unsignedInteger('active_count')->default(0);
            $table->decimal('dormancy_rate', 5, 2)->default(0);

            $table->decimal('total_deposits', 20, 2)->default(0);
            $table->decimal('total_loans', 20, 2)->default(0);

            $table->date('balance_date')->nullable();
            $table->date('loan_date')->nullable();

            $table->timestamps();

            $table->index('segment');
            $table->index('subsegment');
            $table->index('total_deposits');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rm_workload_summary');
    }
};
