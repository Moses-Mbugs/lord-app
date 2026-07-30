<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rm_targets', function (Blueprint $table) {
            $table->id();
            $table->string('rm_code', 10);
            $table->unsignedSmallInteger('period_year');
            $table->decimal('deposit_target', 18, 2)->default(0);
            $table->decimal('loan_target', 18, 2)->default(0);
            $table->unsignedInteger('ntb_target')->default(0);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['rm_code', 'period_year']);
            $table->index('rm_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rm_targets');
    }
};
