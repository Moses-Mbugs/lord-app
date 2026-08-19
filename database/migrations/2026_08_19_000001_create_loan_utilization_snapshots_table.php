<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loan_utilization_snapshots', function (Blueprint $table) {
            $table->increments('id');

            $table->string('batch_reference')->unique();
            $table->string('source_filename')->nullable();
            $table->date('as_of_date')->nullable();

            $table->string('status')->default('pending');
            $table->longText('failure_reason')->nullable();

            $table->unsignedInteger('total_rows')->default(0);
            $table->decimal('total_exposure_lcy', 22, 2)->default(0);

            $table->unsignedInteger('uploaded_by')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->index('as_of_date');
            $table->index('status');
            $table->index('uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_utilization_snapshots');
    }
};
