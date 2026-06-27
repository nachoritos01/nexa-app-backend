<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // 1. Admin user (requires ADMIN_EMAIL and ADMIN_PASSWORD in .env)
            AdminUserSeeder::class,

            // 2. Default tenant — binds currentTenant for subsequent seeders
            DefaultTenantSeeder::class,

            // 3. Roles and permissions (Spatie Permission)
            RolesAndPermissionsSeeder::class,

            // 4. Plugin marketplace (requires tenant + permissions)
            PluginSeeder::class,

            // 5. Billing history (backfill existing tenants)
            BillingEventSeeder::class,

            // 6. Loyalty rewards (requires tenant)
            LoyaltyRewardSeeder::class,
        ]);
    }
}
