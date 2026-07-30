<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_balance_rejects', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('uploaded_file_id')->nullable();
            $table->date('balance_date')->nullable();

            $table->string('reason', 120)->nullable();
            $table->text('raw')->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index('uploaded_file_id');
            $table->index('balance_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_balance_rejects');
    }
};
