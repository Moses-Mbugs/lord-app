<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branch_cif_movers', function (Blueprint $table) {
            $table->id();

            $table->string('branch_code', 20)->index();
            $table->string('branch_name', 100)->nullable();
            $table->string('cif', 30)->index();
            $table->string('customer_name', 255)->nullable();

            $table->date('start_date')->index();
            $table->date('end_date')->index();

            $table->decimal('start_balance', 20, 4)->default(0);
            $table->decimal('end_balance', 20, 4)->default(0);
            $table->decimal('movement', 20, 4)->default(0);

            $table->enum('direction', ['GAIN', 'LOSS'])->index();
            $table->unsignedSmallInteger('rank');

            $table->timestamps();

            $table->unique(['branch_code', 'cif', 'start_date', 'end_date', 'direction'], 'branch_cif_movers_unique');
            $table->index(['branch_code', 'start_date', 'end_date', 'direction'], 'bcm_branch_date_dir_idx');
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_cif_movers');
    }
};
