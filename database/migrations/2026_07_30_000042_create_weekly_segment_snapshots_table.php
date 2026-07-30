<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('weekly_segment_snapshots', function (Blueprint $table) {
            $table->id();

            $table->date('report_date')->index(); // weekEnd, the Monday being reported on
            $table->date('week_start'); // latest balance_date before report_date
            $table->date('mtd_start'); // first balance_date of the month
            $table->date('ytd_start'); // last balance_date of prior year

            $table->string('segment_code', 10)->index(); // CB | CM | CS | OT | ALL
            $table->string('sub_segment_name', 255)->default('');

            $table->decimal('bank_weekly_mv', 24, 2)->default(0)->after('sub_segment_name');
            $table->decimal('bank_mtd_mv', 24, 2)->default(0)->after('bank_weekly_mv');
            $table->decimal('bank_ytd_mv', 24, 2)->default(0)->after('bank_mtd_mv');
            $table->decimal('bank_total_deposits', 24, 2)->default(0)->after('bank_ytd_mv');

            $table->decimal('lcy_weekly_mv', 24, 2)->default(0);
            $table->decimal('lcy_mtd_mv', 24, 2)->default(0);
            $table->decimal('lcy_ytd_mv', 24, 2)->default(0);
            $table->decimal('lcy_total_deposits', 24, 2)->default(0); // closing end balance

            $table->decimal('fcy_weekly_mv', 24, 2)->default(0);
            $table->decimal('fcy_mtd_mv', 24, 2)->default(0);
            $table->decimal('fcy_ytd_mv', 24, 2)->default(0);
            $table->decimal('fcy_total_deposits', 24, 2)->default(0);

            $table->timestamps();

            $table->unique(['report_date', 'segment_code', 'sub_segment_name'], 'wss_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_segment_snapshots');
    }
};
