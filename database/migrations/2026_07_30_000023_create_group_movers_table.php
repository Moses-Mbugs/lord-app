<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('group_movers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();

            $table->string('group_type', 30); // e.g. BRANCH, SEGMENT
            $table->string('group_key', 50); // e.g. branch_code, segment_code, etc.
            $table->string('group_name', 255)->nullable();

            $table->date('start_date')->index();
            $table->date('end_date')->index();

            $table->decimal('start_balance', 20, 2)->default(0);
            $table->decimal('end_balance', 20, 2)->default(0);
            $table->decimal('movement', 20, 2)->default(0);

            $table->string('scope', 20)->default('SUMMARY'); // SUMMARY | TOP
            $table->string('direction', 10)->nullable(); // GAIN | LOSS, only for TOP
            $table->unsignedInteger('rank')->nullable(); // 1..N, only for TOP

            $table->index(['group_type', 'scope']);
            $table->index(['group_type', 'start_date', 'end_date']);
            $table->index(['group_type', 'scope', 'direction', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_movers');
    }
};
