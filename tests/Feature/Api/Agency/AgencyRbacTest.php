<?php

namespace Tests\Feature\Api\Agency;

use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Verifies the `agency.access` gate: a tenant member whose role lacks the
 * `agency.*` permissions is forbidden, while the owner (all permissions) is not.
 */
class AgencyRbacTest extends AgencyTestCase
{
    /** @return array{0: User, 1: string} a user + token whose role has no agency permission. */
    private function makeRestrictedUser(): array
    {
        // 'ventas' is seeded without agency.view/agency.manage.
        Role::findOrCreate('ventas', 'web');

        $user = User::factory()->create();
        $tenant = Tenant::factory()->pro()->create(['owner_id' => $user->id]);
        $tenant->users()->attach($user->id, ['role' => 'ventas']);
        $user->syncRoles(['ventas']);

        $token = $user->createToken('Test');
        $token->accessToken->update(['tenant_id' => $tenant->id]);

        return [$user, $token->plainTextToken];
    }

    public function test_role_without_agency_permission_is_forbidden_on_read(): void
    {
        [, $token] = $this->makeRestrictedUser();

        $this->withToken($token)
            ->getJson('/api/agency/clients')
            ->assertForbidden()
            ->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_role_without_agency_permission_is_forbidden_on_write(): void
    {
        [, $token] = $this->makeRestrictedUser();

        $this->withToken($token)
            ->postJson('/api/agency/clients', ['name' => 'ACME'])
            ->assertForbidden();
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
