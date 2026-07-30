<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('uploaded_files', function (Blueprint $table) {
            $table->id();

            $table->string('file_type'); // balances | deposits
            $table->string('original_name');
            $table->date('file_date')->nullable();
            $table->string('checksum', 64)->nullable(); // sha256
            $table->string('stored_path')->nullable();

            $table->string('status')->default('imported'); // imported|failed
            $table->text('error')->nullable();

            $table->json('meta')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->index(['file_type', 'file_date']);
            $table->unique(['file_type', 'original_name', 'file_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uploaded_files');
    }
};
