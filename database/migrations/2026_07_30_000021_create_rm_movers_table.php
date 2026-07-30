<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rm_movers', function (Blueprint $table) {
            $table->id();

            $table->date('start_date');
            $table->date('end_date');
            $table->string('rm_code');

            $table->decimal('start_balance', 24, 2)->default(0);
            $table->decimal('end_balance', 24, 2)->default(0);
            $table->decimal('movement', 24, 2)->default(0);
            $table->unsignedInteger('cif_count')->default(0);

            $table->timestamps();

            $table->unique(['start_date', 'end_date', 'rm_code']);
            $table->index(['start_date', 'end_date']);
            $table->index('rm_code');
            $table->index('movement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rm_movers');
    }
};
