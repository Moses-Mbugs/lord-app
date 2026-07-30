<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    /**
     * Roles used across lord-app's routes.
     *
     * - admin: gates the Users & Roles management page itself.
     * - finance-admin: already referenced by role:finance-admin middleware
     *   throughout routes/web.php (balances, top-movers, relationship
     *   managers, rm-targets, customer-trend, customer-profitability).
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['admin', 'finance-admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }
}
