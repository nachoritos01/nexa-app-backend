<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create();
        $this->tenant = Tenant::factory()->pro()->create(['owner_id' => $this->user->id]);
        $this->tenant->users()->attach($this->user->id, ['role' => 'owner']);
        $this->user->syncRoles(['owner']);
    }

    public function test_token_is_generated_with_correct_tenant_id(): void
    {
        $token = $this->user->createToken('Test Token');
        $token->accessToken->update(['tenant_id' => $this->tenant->id]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'Test Token',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_revoked_token_does_not_authenticate(): void
    {
        $token = $this->user->createToken('Revokable');
        $token->accessToken->update(['tenant_id' => $this->tenant->id]);

        // Revoke it before any request
        $token->accessToken->delete();

        // Revoked token should not work
        $response = $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/orders');
        $response->assertUnauthorized();
    }

    public function test_non_pro_plan_receives_403(): void
    {
        $starterTenant = Tenant::factory()->create([
            'plan' => 'starter',
            'owner_id' => $this->user->id,
        ]);

        $token = $this->user->createToken('Starter Token');
        $token->accessToken->update(['tenant_id' => $starterTenant->id]);

        $response = $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/orders');

        $response->assertForbidden()
            ->assertJsonPath('code', 'PLAN_UPGRADE_REQUIRED');
    }

    public function test_inactive_tenant_receives_403(): void
    {
        $inactiveTenant = Tenant::factory()->pro()->inactive()->create([
            'owner_id' => $this->user->id,
            'trial_ends_at' => now()->subDays(30),
        ]);

        $token = $this->user->createToken('Inactive Token');
        $token->accessToken->update(['tenant_id' => $inactiveTenant->id]);

        $response = $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/orders');

        $response->assertForbidden()
            ->assertJsonPath('code', 'TENANT_INACTIVE');
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/orders');

        $response->assertUnauthorized();
    }
}
