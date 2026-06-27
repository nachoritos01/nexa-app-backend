<?php

namespace Tests\Feature\Api\V1;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiV1Test extends TestCase
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

    public function test_list_orders_returns_paginated_results(): void
    {
        Order::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/orders');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['total', 'page', 'per_page', 'last_page'],
            ])
            ->assertJsonPath('meta.total', 3);
    }

    public function test_show_order_returns_order_with_relations(): void
    {
        $order = Order::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withToken($this->token)
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertOk()
            ->assertJsonStructure(['data' => ['id', 'customer_name', 'status']]);
    }

    public function test_create_order_returns_201(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/orders', [
                'customer_name' => 'API Customer',
                'customer_phone' => '5551234567',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.customer_name', 'API Customer');

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'API Customer',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_without_token_returns_401(): void
    {
        $response = $this->getJson('/api/v1/orders');

        $response->assertUnauthorized();
    }

    public function test_token_from_other_tenant_does_not_see_data(): void
    {
        // Create order for current tenant
        Order::factory()->create(['tenant_id' => $this->tenant->id]);

        // Create another tenant with its own token
        $otherUser = User::factory()->create();
        $otherTenant = Tenant::factory()->pro()->create(['owner_id' => $otherUser->id]);
        $otherTenant->users()->attach($otherUser->id, ['role' => 'owner']);

        $otherSanctumToken = $otherUser->createToken('Other');
        $otherSanctumToken->accessToken->update(['tenant_id' => $otherTenant->id]);

        $response = $this->withToken($otherSanctumToken->plainTextToken)
            ->getJson('/api/v1/orders');

        $response->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_filter_by_status_works(): void
    {
        Order::factory()->confirmed()->create(['tenant_id' => $this->tenant->id]);
        Order::factory()->create(['tenant_id' => $this->tenant->id, 'status' => OrderStatus::Pending]);

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/orders?status=confirmed');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_update_status_transitions_order(): void
    {
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::Pending,
        ]);

        $response = $this->withToken($this->token)
            ->patchJson("/api/v1/orders/{$order->id}/status", [
                'status' => 'confirmed',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', OrderStatus::Confirmed->value);

        $this->assertNotNull($order->fresh()->confirmed_at);
    }

    public function test_update_status_requires_auth(): void
    {
        $order = Order::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->patchJson("/api/v1/orders/{$order->id}/status", [
            'status' => 'confirmed',
        ]);

        $response->assertUnauthorized();
    }
}
