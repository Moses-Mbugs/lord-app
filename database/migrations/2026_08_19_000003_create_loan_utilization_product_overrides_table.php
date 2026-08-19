<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loan_utilization_product_overrides', function (Blueprint $table) {
            $table->increments('id');

            $table->string('credit_line_code')->unique();
            $table->string('product_name');

            $table->unsignedInteger('updated_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_utilization_product_overrides');
    }
};
