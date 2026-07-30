<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loan_listings', function (Blueprint $table) {
            $table->id();

            $table->date('as_at_date');
            $table->string('related_account')->nullable();
            $table->string('cif')->nullable();
            $table->string('name')->nullable();
            $table->string('branch', 100)->nullable();
            $table->string('source_type', 50)->nullable();
            $table->string('branch_name', 150)->nullable();

            $table->string('currency', 10)->default('KES');
            $table->string('currency_type', 3)->default('LCY'); // LCY|FCY

            $table->string('business_segment', 100)->nullable(); // CONSUMER|COMERCIAL|CORPORATE
            $table->string('loan_status', 50)->nullable(); // NORM|OAEM|SUB1|DOUB|LOSS|WOFF
            $table->string('status_bucket', 30)->nullable(); // Performing|Watch|Substandard|Doubtful|Loss

            $table->decimal('loan_book_outstanding', 20, 2)->default(0);
            $table->decimal('outstanding_amount_lcy', 20, 2)->default(0);

            $table->string('product_code', 50)->nullable();
            $table->text('pms_gl_codes')->nullable();
            $table->string('linecode', 100)->nullable();
            $table->json('raw')->nullable();

            $table->timestamps();

            $table->index(['as_at_date', 'currency_type']);
            $table->index(['as_at_date', 'business_segment']);
            $table->index('cif');
            $table->index(['as_at_date', 'status_bucket']);
            $table->index('related_account', 'idx_ll_related_account');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE loan_listings
                ADD COLUMN rm_officer VARCHAR(50)
                    GENERATED ALWAYS AS (UPPER(TRIM(JSON_UNQUOTE(JSON_EXTRACT(raw, '$.rm_officer'))))) VIRTUAL,
                ADD INDEX idx_ll_rm_officer (rm_officer)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_listings');
    }
};
