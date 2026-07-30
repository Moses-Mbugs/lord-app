<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Minimal stand-in: top_movers.business_unit_id has a real FK to this table
 * in the source monolith, but nothing in the Finance module's application
 * code reads/writes business_units — no code elsewhere in this project
 * references it. Kept minimal so the top_movers FK constraint stays valid.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('business_units', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_units');
    }
};
