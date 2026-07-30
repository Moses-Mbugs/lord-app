<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sub_segment_mappings', function (Blueprint $table) {
            $table->id();

            $table->string('mis_class', 100)->nullable();
            $table->string('business_segment_name')->nullable();
            $table->string('mis_code', 50)->unique();
            $table->string('code_desc')->nullable();
            $table->string('business_seg_short')->nullable();
            $table->string('business')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('business_segment_name');
            $table->index('business_seg_short');
            $table->index('business');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_segment_mappings');
    }
};
