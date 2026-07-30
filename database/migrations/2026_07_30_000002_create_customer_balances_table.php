<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_balances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('uploaded_file_id')->nullable()
                ->constrained('uploaded_files')->nullOnDelete();

            $table->string('cust_ac_no', 50)->nullable();
            $table->string('cif', 30)->nullable();

            // NOTE: cif_profiles is owned by a different module in the source
            // monolith and is not part of this project. Kept as a plain,
            // unconstrained column so historical data isn't lost; there is no
            // FK here on purpose.
            $table->unsignedBigInteger('cif_profile_id')->nullable();

            $table->string('customer_name', 255)->nullable();
            $table->string('account_desc', 255)->nullable();

            $table->date('balance_date')->nullable();

            $table->string('currency', 10)->default('KES');
            $table->string('currency_type', 10)->default('LCY'); // LCY|FCY

            $table->decimal('balance', 20, 2)->default(0);
            $table->decimal('acy_balance', 20, 2)->default(0.00);
            $table->decimal('lcy_balance', 20, 2)->default(0.00);

            $table->string('dr_gl', 30)->nullable();
            $table->string('cr_gl', 30)->nullable();

            $table->string('branch_code', 20)->nullable();
            $table->longText('raw')->nullable();
            $table->timestamps();

            $table->index(['balance_date', 'currency_type']);
            $table->index(['cif', 'balance_date']);
            $table->unique(['cust_ac_no', 'balance_date', 'currency_type']);

            $table->index('cif_profile_id');
            $table->index('acy_balance', 'customer_balances_acy_balance_index');
            $table->index('lcy_balance', 'customer_balances_lcy_balance_index');
            $table->index(
                ['balance_date', 'cif', 'branch_code', 'lcy_balance', 'cr_gl'],
                'idx_cb_covering'
            );
        });

        // Functional index on UPPER(TRIM(branch_code)) — requires MySQL 8+
        DB::statement('ALTER TABLE customer_balances ADD INDEX idx_cb_branch_upper ((UPPER(TRIM(branch_code))))');
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_balances');
    }
};
