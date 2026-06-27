<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions (idempotent)
        $permissions = [
            'orders.view',
            'orders.create',
            'orders.edit',
            'orders.delete',
            'production.view',
            'production.mark',
            'products.manage',
            'customers.manage',
            'payments.view',
            'payments.create',
            'pricing.manage',
            'reports.export',
            'settings.manage',
            'users.manage',
            'billing.manage',
            'plugins.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles with permissions (idempotent)
        $roles = [
            'owner' => $permissions, // All permissions
            'admin' => array_filter($permissions, fn ($p) => $p !== 'billing.manage'),
            'ventas' => [
                'orders.view', 'orders.create', 'orders.edit',
                'customers.manage',
                'payments.view', 'payments.create',
            ],
            'produccion' => [
                'orders.view',
                'production.view', 'production.mark',
            ],
            'contabilidad' => [
                'orders.view',
                'payments.view',
                'reports.export',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }

        $this->command->info('Roles and permissions seeded successfully.');
    }
}
