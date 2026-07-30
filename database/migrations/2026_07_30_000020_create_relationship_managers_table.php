<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('relationship_managers', function (Blueprint $table) {
            $table->id();

            $table->string('staff_number', 20)->unique();
            $table->string('rm_code', 10)->unique();
            $table->string('name');
            $table->string('segment', 50)->nullable()->after('name')->index();
            $table->string('subsegment', 50)->nullable()->after('segment')->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relationship_managers');
    }
};
