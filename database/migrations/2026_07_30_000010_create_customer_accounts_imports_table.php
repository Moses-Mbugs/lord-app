<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_accounts_imports', function (Blueprint $table) {
            $table->id();

            $table->string('introducer')->nullable();
            $table->string('cust_category')->nullable();
            $table->string('eti_cif_class_category')->nullable();
            $table->string('etibiseg2')->nullable();
            $table->string('acc_ofcr')->nullable();

            $table->string('f12_cif')->nullable();
            $table->string('f12_ac_no')->nullable();
            $table->string('branch_code')->nullable();

            $table->decimal('acy_withdrawable_bal', 20, 2)->nullable();
            $table->string('cust_ac_no')->nullable();

            $table->string('record_stat')->nullable();
            $table->string('account_class')->nullable();
            $table->string('ac_desc')->nullable();

            $table->date('ac_open_date')->nullable();
            $table->date('dormancy_date')->nullable();

            $table->string('ac_stat_dormant')->nullable();
            $table->string('address_line1')->nullable();

            $table->decimal('lcy_curr_balance', 20, 2)->nullable();

            $table->string('cheque_book_facility')->nullable();
            $table->string('atm_facility')->nullable();

            $table->string('telephone')->nullable();
            $table->string('e_mail')->nullable();

            $table->index(['f12_cif']);
            $table->index(['f12_ac_no']);
            $table->index(['cust_ac_no']);
            $table->index(['branch_code']);
            $table->index('acc_ofcr', 'idx_cai_acc_ofcr');

            $table->timestamps();
        });

        DB::statement('ALTER TABLE customer_accounts_imports ADD INDEX idx_cai_acc_ofcr_upper ((UPPER(TRIM(acc_ofcr))))');
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_accounts_imports');
    }
};
