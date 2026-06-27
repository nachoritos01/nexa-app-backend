<?php

namespace Tests\Feature\Api\V1;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_pro_plan_has_300_limit(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->pro()->create(['owner_id' => $user->id]);
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $user->syncRoles(['owner']);

        app()->instance('currentTenant', $tenant);

        $sanctumToken = $user->createToken('Test');
        $sanctumToken->accessToken->update(['tenant_id' => $tenant->id]);

        $response = $this->withToken($sanctumToken->plainTextToken)
            ->getJson('/api/v1/orders');

        $response->assertOk()
            ->assertHeader('X-RateLimit-Limit', 300);
    }

    public function test_rate_limit_header_present(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->pro()->create(['owner_id' => $user->id]);
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $user->syncRoles(['owner']);

        app()->instance('currentTenant', $tenant);

        $sanctumToken = $user->createToken('Test');
        $sanctumToken->accessToken->update(['tenant_id' => $tenant->id]);

        $response = $this->withToken($sanctumToken->plainTextToken)
            ->getJson('/api/v1/orders');

        $response->assertOk()
            ->assertHeader('X-RateLimit-Limit')
            ->assertHeader('X-RateLimit-Remaining');
    }

    public function test_unauthenticated_returns_401(): void
    {
        $response = $this->getJson('/api/v1/orders');

        $response->assertUnauthorized();
    }

    public function test_exceeding_limit_returns_429(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->pro()->create(['owner_id' => $user->id]);
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $user->syncRoles(['owner']);

        app()->instance('currentTenant', $tenant);

        // Temporarily set very low limit for testing
        config(['saas.api.rate_limits.pro' => 2]);

        $sanctumToken = $user->createToken('Test');
        $sanctumToken->accessToken->update(['tenant_id' => $tenant->id]);
        $token = $sanctumToken->plainTextToken;

        // Make requests up to the limit
        $this->withToken($token)->getJson('/api/v1/orders')->assertOk();
        $this->withToken($token)->getJson('/api/v1/orders')->assertOk();

        // Third request should be rate limited
        $response = $this->withToken($token)->getJson('/api/v1/orders');
        $response->assertStatus(429);
    }
}
