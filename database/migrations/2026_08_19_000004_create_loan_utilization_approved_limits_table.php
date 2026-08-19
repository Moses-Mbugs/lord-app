<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loan_utilization_approved_limits', function (Blueprint $table) {
            $table->increments('id');

            $table->string('product_name')->unique();
            $table->decimal('approved_limit', 22, 2)->default(0);

            $table->unsignedInteger('updated_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_utilization_approved_limits');
    }
};
