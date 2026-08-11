<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('top_movers', function (Blueprint $table) {
            $table->string('sub_segment', 50)->nullable()->after('branch_code');
            $table->index('sub_segment');
        });
    }

    public function down(): void
    {
        Schema::table('top_movers', function (Blueprint $table) {
            $table->dropIndex(['sub_segment']);
            $table->dropColumn('sub_segment');
        });
    }
};
