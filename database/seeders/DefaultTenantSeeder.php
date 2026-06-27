<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class DefaultTenantSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = config('app.admin_email');
        $admin = $adminEmail ? User::where('email', $adminEmail)->first() : null;

        // Fallback: use first user if ADMIN_EMAIL not set
        if (! $admin) {
            $admin = User::first();
        }

        if (! $admin) {
            $this->command->warn('No users found. Skipping DefaultTenantSeeder.');

            return;
        }

        // Create default tenant (idempotent)
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'default'],
            [
                'name' => config('business.name', 'Default Business'),
                'plan' => 'pro',
                'owner_id' => $admin->id,
                'is_active' => true,
                'subscribed_at' => now(),
                'onboarding_completed_at' => now(),
            ]
        );

        // Ensure onboarding is marked complete for existing tenant
        if (! $tenant->onboarding_completed_at) {
            $tenant->update(['onboarding_completed_at' => now()]);
        }

        // Ensure tenant is marked as subscribed (Pro without Stripe)
        if (! $tenant->subscribed_at) {
            $tenant->update(['subscribed_at' => now()]);
        }

        // Ensure admin is member
        if (! $tenant->users()->where('user_id', $admin->id)->exists()) {
            $tenant->users()->attach($admin->id, ['role' => 'owner']);
        }

        // Backfill tenant_id on business tables
        $businessTables = ['orders', 'customers', 'items', 'quotes', 'locations'];
        foreach ($businessTables as $table) {
            if (! \Schema::hasTable($table)) {
                continue;
            }
            $updated = DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => $tenant->id]);
            if ($updated > 0) {
                $this->command->info("  {$table}: backfilled {$updated} rows");
            }
        }

        // Assign Spatie 'owner' role if it exists
        if (Role::where('name', 'owner')->exists()) {
            $admin->syncRoles(['owner']);
        }

        // Bind to container so subsequent seeders auto-assign tenant_id
        app()->instance('currentTenant', $tenant);

        $this->command->info("Default tenant '{$tenant->name}' ready (ID: {$tenant->id})");
    }
}
