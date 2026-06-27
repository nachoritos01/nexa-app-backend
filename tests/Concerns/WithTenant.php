<?php

namespace Tests\Concerns;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

trait WithTenant
{
    protected Tenant $tenant;

    protected User $tenantUser;

    protected function setUpTenant(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->tenantUser = User::factory()->create();
        $this->tenant = Tenant::factory()->create(['owner_id' => $this->tenantUser->id]);
        $this->tenant->users()->attach($this->tenantUser->id, ['role' => 'owner']);

        $this->tenantUser->syncRoles(['owner']);

        app()->instance('currentTenant', $this->tenant);
    }

    protected function createUserWithRole(string $role): User
    {
        $user = User::factory()->create();
        $this->tenant->users()->attach($user->id, ['role' => $role]);
        $user->syncRoles([$role]);

        return $user;
    }
}
