<?php

namespace Tests\Feature\Api\V1;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardApiV1Test extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create();
        $this->tenant = Tenant::factory()->pro()->create(['owner_id' => $this->user->id]);
        $this->tenant->users()->attach($this->user->id, ['role' => 'owner']);
        $this->user->syncRoles(['owner']);

        app()->instance('currentTenant', $this->tenant);

        $sanctumToken = $this->user->createToken('Test');
        $sanctumToken->accessToken->update(['tenant_id' => $this->tenant->id]);
        $this->token = $sanctumToken->plainTextToken;
    }

    public function test_returns_expected_json_structure(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/dashboard/stats');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'orders_this_month',
                    'revenue_this_month',
                    'plan',
                    'usage',
                    'limits',
                ],
            ]);
    }

    public function test_counts_this_month_orders_correctly(): void
    {
        Order::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now(),
        ]);

        // Last month's order should not count
        Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now()->subMonth(),
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/dashboard/stats');

        $response->assertOk()
            ->assertJsonPath('data.orders_this_month', 3);
    }

    public function test_calculates_revenue_this_month(): void
    {
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'amount' => 1500,
            'method' => 'cash',
            'received_at' => now(),
        ]);

        // Last month's payment should not count
        $oldPayment = Payment::create([
            'order_id' => $order->id,
            'amount' => 500,
            'method' => 'cash',
            'received_at' => now()->subMonth(),
        ]);
        $oldPayment->forceFill(['created_at' => now()->subMonth()])->saveQuietly();

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/dashboard/stats');

        $response->assertOk();
        $this->assertEquals(1500.0, $response->json('data.revenue_this_month'));
    }

    public function test_includes_plan_and_usage(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/dashboard/stats');

        $response->assertOk();
        $this->assertEquals($this->tenant->plan, $response->json('data.plan'));
        $this->assertIsArray($response->json('data.usage'));
        $this->assertIsArray($response->json('data.limits'));
    }

    public function test_without_token_returns_401(): void
    {
        $response = $this->getJson('/api/v1/dashboard/stats');

        $response->assertUnauthorized();
    }

    public function test_tenant_isolation(): void
    {
        // Create order for current tenant
        Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now(),
        ]);

        // Create another tenant with its own data
        $otherUser = User::factory()->create();
        $otherTenant = Tenant::factory()->pro()->create(['owner_id' => $otherUser->id]);
        $otherTenant->users()->attach($otherUser->id, ['role' => 'owner']);
        $otherUser->syncRoles(['owner']);

        // Switch tenant context to create orders in the other tenant
        app()->instance('currentTenant', $otherTenant);
        Order::factory()->count(5)->create([
            'tenant_id' => $otherTenant->id,
            'created_at' => now(),
        ]);

        $otherSanctumToken = $otherUser->createToken('Other');
        $otherSanctumToken->accessToken->update(['tenant_id' => $otherTenant->id]);

        // Other tenant should only see their own orders
        $response = $this->withToken($otherSanctumToken->plainTextToken)
            ->getJson('/api/v1/dashboard/stats');

        $response->assertOk()
            ->assertJsonPath('data.orders_this_month', 5);
    }
}
