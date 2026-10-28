<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Prepares customer_accounts_imports for the nightly API upsert
 * (finance:sync-customer-accounts):
 *  - officer_name: already used by the app but was added by hand on the server, so only add if missing.
 *  - account_initiator_name: the API's "account initiator -comp name" field.
 *  - unique cust_ac_no: the upsert key ("insert, or update if this account already exists").
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('customer_accounts_imports', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_accounts_imports', 'officer_name')) {
                $table->string('officer_name')->nullable()->after('acc_ofcr');
            }
            if (!Schema::hasColumn('customer_accounts_imports', 'account_initiator_name')) {
                $table->string('account_initiator_name')->nullable()->after('introducer');
            }
        });

        // Refuse to continue rather than silently deleting rows: duplicates must be cleaned by hand.
        $dupes = DB::table('customer_accounts_imports')
            ->select('cust_ac_no')
            ->whereNotNull('cust_ac_no')
            ->groupBy('cust_ac_no')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($dupes > 0) {
            throw new RuntimeException(
                "customer_accounts_imports has {$dupes} cust_ac_no value(s) appearing more than once. "
                . 'Remove the duplicates (keep one row per account), then re-run the migration.'
            );
        }

        Schema::table('customer_accounts_imports', function (Blueprint $table) {
            $table->unique('cust_ac_no', 'uq_cai_cust_ac_no');
        });
    }

    public function down(): void
    {
        Schema::table('customer_accounts_imports', function (Blueprint $table) {
            $table->dropUnique('uq_cai_cust_ac_no');
            $table->dropColumn('account_initiator_name');
        });
    }
};
