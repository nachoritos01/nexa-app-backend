<?php

namespace Tests\Feature\Api\V1;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerApiV1Test extends TestCase
{
    use RefreshDatabase;

    private string $token;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $this->tenant = Tenant::factory()->pro()->create(['owner_id' => $user->id]);
        $this->tenant->users()->attach($user->id, ['role' => 'owner']);
        $user->syncRoles(['owner']);

        app()->instance('currentTenant', $this->tenant);

        $sanctumToken = $user->createToken('Test');
        $sanctumToken->accessToken->update(['tenant_id' => $this->tenant->id]);
        $this->token = $sanctumToken->plainTextToken;
    }

    public function test_list_customers_returns_paginated_results(): void
    {
        Customer::create(['tenant_id' => $this->tenant->id, 'name' => 'Test Customer', 'phone' => '5551111111']);
        Customer::create(['tenant_id' => $this->tenant->id, 'name' => 'Another Customer', 'phone' => '5552222222']);

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/customers');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_search_customers_by_name(): void
    {
        Customer::create(['tenant_id' => $this->tenant->id, 'name' => 'Juan Perez', 'phone' => '5551111111']);
        Customer::create(['tenant_id' => $this->tenant->id, 'name' => 'Maria Lopez', 'phone' => '5552222222']);

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/customers?search=juan');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_show_customer_returns_orders_count(): void
    {
        $customer = Customer::create(['tenant_id' => $this->tenant->id, 'name' => 'Test', 'phone' => '5551111111']);

        $response = $this->withToken($this->token)
            ->getJson("/api/v1/customers/{$customer->id}");

        $response->assertOk()
            ->assertJsonStructure(['data' => ['id', 'name', 'phone', 'orders_count']]);
    }

    // Store tests

    public function test_store_returns_201(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/customers', [
                'name' => 'New Customer',
                'phone' => '5553333333',
                'email' => 'new@example.com',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Customer')
            ->assertJsonPath('data.phone', '5553333333');
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/customers', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'phone']);
    }

    public function test_store_auto_assigns_tenant_id(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/customers', [
                'name' => 'Tenant Customer',
                'phone' => '5554444444',
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('customers', [
            'name' => 'Tenant Customer',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    // Update tests

    public function test_update_changes_fields(): void
    {
        $customer = Customer::create(['tenant_id' => $this->tenant->id, 'name' => 'Old Name', 'phone' => '5551111111']);

        $response = $this->withToken($this->token)
            ->putJson("/api/v1/customers/{$customer->id}", [
                'name' => 'Updated Name',
                'phone' => '5559999999',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.phone', '5559999999');
    }

    public function test_partial_update_works(): void
    {
        $customer = Customer::create(['tenant_id' => $this->tenant->id, 'name' => 'Original', 'phone' => '5551111111']);

        $response = $this->withToken($this->token)
            ->putJson("/api/v1/customers/{$customer->id}", [
                'name' => 'Changed Only Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Changed Only Name')
            ->assertJsonPath('data.phone', '5551111111');
    }

    // Customer orders tests

    public function test_customer_orders_returns_paginated_results(): void
    {
        $customer = Customer::create(['tenant_id' => $this->tenant->id, 'name' => 'Test', 'phone' => '5551111111']);

        Order::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->withToken($this->token)
            ->getJson("/api/v1/customers/{$customer->id}/orders");

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['total', 'page', 'per_page', 'last_page'],
            ])
            ->assertJsonPath('meta.total', 2);
    }

    // Tenant isolation test

    public function test_cannot_access_customer_from_other_tenant(): void
    {
        $otherUser = User::factory()->create();
        $otherTenant = Tenant::factory()->pro()->create(['owner_id' => $otherUser->id]);

        // Temporarily switch tenant context to create customer in other tenant
        app()->instance('currentTenant', $otherTenant);
        $otherCustomer = Customer::create(['tenant_id' => $otherTenant->id, 'name' => 'Other', 'phone' => '5550000000']);
        app()->instance('currentTenant', $this->tenant);

        $response = $this->withToken($this->token)
            ->getJson("/api/v1/customers/{$otherCustomer->id}");

        $response->assertNotFound();
    }
}
