<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('segment_movers', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->string('segment_code', 10)->index(); // e.g. CS, CB, CM, ALL
            $table->string('segment_name', 50);

            $table->decimal('start_balance', 20, 2)->default(0);
            $table->decimal('end_balance', 20, 2)->default(0);
            $table->decimal('movement', 20, 2)->default(0);
            $table->unsignedInteger('cif_count')->default(0);

            $table->timestamps();

            $table->unique(['start_date', 'end_date', 'segment_code'], 'segment_movers_unique_run');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('segment_movers');
    }
};
