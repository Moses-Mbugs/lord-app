<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * movement_type and rank are legacy columns from an older top_movers schema.
 * The current insert path (app/Services/Reports/TopMoversService.php) never
 * populates them — it writes scope/direction instead — so they must be
 * nullable or every insert fails with "doesn't have a default value".
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('top_movers', function (Blueprint $table) {
            $table->string('movement_type')->nullable()->change();
            $table->unsignedInteger('rank')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('top_movers', function (Blueprint $table) {
            $table->string('movement_type')->nullable(false)->change();
            $table->unsignedInteger('rank')->nullable(false)->change();
        });
    }
};
