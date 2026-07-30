<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('top_movers', function (Blueprint $table) {
            $table->id();

            $table->date('start_date');
            $table->date('end_date');
            $table->string('currency_type')->default('LCY');
            $table->string('scope', 20)->default('cif_currency')->after('currency_type');

            $table->foreignId('business_unit_id')->nullable()
                ->constrained('business_units')->nullOnDelete();

            $table->string('movement_type'); // Gainer|Loser
            $table->unsignedInteger('rank');

            // varchar(30) indexed — the later, more specific of two conflicting
            // definitions in the source monolith's migration history.
            $table->string('cif', 30)->nullable();
            $table->string('customer_name', 255)->nullable()->after('cif');
            $table->string('name')->nullable();
            $table->string('business')->nullable();

            $table->decimal('balance_start', 18, 2)->default(0);
            $table->decimal('balance_end', 18, 2)->default(0);

            // decimal(20,2) indexed — the later, more specific of two
            // conflicting definitions in the source monolith's history.
            $table->decimal('movement', 20, 2)->default(0);

            $table->timestamps();

            $table->string('currency', 10)->nullable();
            $table->string('branch_code', 20)->nullable();
            $table->string('cust_ac_no', 50)->nullable();
            $table->decimal('start_balance', 20, 2)->default(0);
            $table->decimal('end_balance', 20, 2)->default(0);
            $table->enum('direction', ['GAIN', 'LOSS'])->nullable();

            $table->unique(['end_date', 'currency_type', 'business_unit_id', 'movement_type', 'rank'], 'tm_unique');
            $table->index(['end_date', 'currency_type', 'business_unit_id']);
            $table->index(['start_date', 'end_date', 'scope'], 'top_movers_period_scope_idx');
            $table->index('cif');
            $table->index('movement');
            $table->index('currency');
            $table->index('branch_code');
            $table->index('cust_ac_no');
            $table->index('direction');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('top_movers');
    }
};
