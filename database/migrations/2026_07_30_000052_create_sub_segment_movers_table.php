<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sub_segment_movers', function (Blueprint $table) {
            $table->id();

            $table->date('start_date');
            $table->date('end_date');

            $table->string('business')->nullable();
            $table->string('business_segment_name')->nullable();
            $table->string('business_seg_short')->nullable();
            $table->string('mis_code', 50);
            $table->string('code_desc')->nullable();

            $table->decimal('start_balance', 24, 2)->default(0);
            $table->decimal('end_balance', 24, 2)->default(0);
            $table->decimal('movement', 24, 2)->default(0);
            $table->unsignedBigInteger('cif_count')->default(0);

            $table->timestamps();

            $table->unique(['start_date', 'end_date', 'mis_code'], 'sub_segment_movers_unique_dates_code');
            $table->index(['start_date', 'end_date'], 'sub_segment_movers_dates_idx');
            $table->index('business_segment_name');
            $table->index('mis_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_segment_movers');
    }
};
