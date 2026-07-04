<?php

namespace Tests\Feature\Api\Agency;

use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Verifies the `agency.access` gate:
 * - a role with no `agency.*` permission is fully forbidden,
 * - a view-only role (`agency.view`, e.g. ventas) can read but not write,
 * - the owner (all permissions) has full access.
 */
class AgencyRbacTest extends AgencyTestCase
{
    /**
     * A user + tenant-scoped token whose role has the given permissions.
     *
     * @param  list<string>  $permissions
     * @return array{0: User, 1: string}
     */
    private function makeUserWithRole(string $roleName, array $permissions): array
    {
        $role = Role::findOrCreate($roleName, 'web');
        $role->syncPermissions($permissions);

        $user = User::factory()->create();
        $tenant = Tenant::factory()->pro()->create(['owner_id' => $user->id]);
        $tenant->users()->attach($user->id, ['role' => $roleName]);
        $user->syncRoles([$roleName]);

        $token = $user->createToken('Test');
        $token->accessToken->update(['tenant_id' => $tenant->id]);

        return [$user, $token->plainTextToken];
    }

    public function test_role_without_any_agency_permission_is_forbidden_on_read(): void
    {
        [, $token] = $this->makeUserWithRole('no_agency', ['orders.view']);

        $this->withToken($token)
            ->getJson('/api/agency/clients')
            ->assertForbidden()
            ->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_view_only_role_can_read_but_not_write(): void
    {
        // 'ventas' is seeded with agency.view but not agency.manage.
        [, $token] = $this->makeUserWithRole('ventas', ['agency.view']);

        $this->withToken($token)->getJson('/api/agency/clients')->assertOk();

        $this->withToken($token)
            ->postJson('/api/agency/clients', ['name' => 'ACME'])
            ->assertForbidden()
            ->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_owner_keeps_full_agency_access(): void
    {
        // $this->token (from AgencyTestCase) is an owner → has agency.* after seeding.
        $this->withToken($this->token)->getJson('/api/agency/clients')->assertOk();

        $this->withToken($this->token)
            ->postJson('/api/agency/clients', ['name' => 'ACME'])
            ->assertCreated();
    }
}
