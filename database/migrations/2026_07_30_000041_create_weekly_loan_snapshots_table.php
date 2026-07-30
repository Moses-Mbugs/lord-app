<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('weekly_loan_snapshots', function (Blueprint $table) {
            $table->id();

            $table->date('report_date')->index();
            $table->date('week_start');
            $table->date('mtd_start');

            $table->string('segment_code', 40)->index();
            $table->string('sub_segment_name', 255)->default('');

            $table->decimal('weekly_mv', 24, 2)->default(0);
            $table->decimal('mtd_mv', 24, 2)->default(0);
            $table->decimal('total_loans', 24, 2)->default(0); // KES equivalent, LCY+FCY combined

            $table->timestamps();

            $table->unique(['report_date', 'segment_code', 'sub_segment_name'], 'wls_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_loan_snapshots');
    }
};
