<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Item;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WebhookLog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Tenant $tenantB;

    private User $userA;

    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->userA = User::factory()->create();
        $this->tenantA = Tenant::factory()->pro()->create(['owner_id' => $this->userA->id, 'name' => 'Tenant A']);
        $this->tenantA->users()->attach($this->userA->id, ['role' => 'owner']);
        $this->userA->syncRoles(['owner']);

        $this->userB = User::factory()->create();
        $this->tenantB = Tenant::factory()->pro()->create(['owner_id' => $this->userB->id, 'name' => 'Tenant B']);
        $this->tenantB->users()->attach($this->userB->id, ['role' => 'owner']);
        $this->userB->syncRoles(['owner']);
    }

    public function test_tenant_a_cannot_see_tenant_b_orders(): void
    {
        app()->instance('currentTenant', $this->tenantA);
        Order::factory()->count(2)->create();

        app()->instance('currentTenant', $this->tenantB);
        Order::factory()->count(3)->create();

        app()->instance('currentTenant', $this->tenantA);
        $this->assertCount(2, Order::all());

        app()->instance('currentTenant', $this->tenantB);
        $this->assertCount(3, Order::all());
    }

    public function test_tenant_a_cannot_see_tenant_b_customers(): void
    {
        app()->instance('currentTenant', $this->tenantA);
        Customer::create(['name' => 'Customer A', 'phone' => '1111111111']);

        app()->instance('currentTenant', $this->tenantB);
        Customer::create(['name' => 'Customer B', 'phone' => '2222222222']);

        app()->instance('currentTenant', $this->tenantA);
        $customers = Customer::all();
        $this->assertCount(1, $customers);
        $this->assertEquals('Customer A', $customers->first()->name);
    }

    public function test_tenant_a_cannot_see_tenant_b_items(): void
    {
        app()->instance('currentTenant', $this->tenantA);
        Item::factory()->count(3)->create();

        app()->instance('currentTenant', $this->tenantB);
        Item::factory()->count(1)->create();

        app()->instance('currentTenant', $this->tenantA);
        $this->assertCount(3, Item::all());

        app()->instance('currentTenant', $this->tenantB);
        $this->assertCount(1, Item::all());
    }

    public function test_creating_model_auto_assigns_current_tenant(): void
    {
        app()->instance('currentTenant', $this->tenantA);

        $order = Order::factory()->create();

        $this->assertEquals($this->tenantA->id, $order->tenant_id);
    }

    public function test_without_global_scopes_returns_all_records(): void
    {
        app()->instance('currentTenant', $this->tenantA);
        Order::factory()->count(2)->create();

        app()->instance('currentTenant', $this->tenantB);
        Order::factory()->count(3)->create();

        $all = Order::withoutGlobalScopes()->get();
        $this->assertCount(5, $all);
    }

    public function test_api_v1_with_tenant_a_token_does_not_return_tenant_b_data(): void
    {
        app()->instance('currentTenant', $this->tenantA);
        Order::factory()->count(2)->create(['tenant_id' => $this->tenantA->id]);

        app()->instance('currentTenant', $this->tenantB);
        Order::factory()->count(3)->create(['tenant_id' => $this->tenantB->id]);

        $tokenA = $this->userA->createToken('Test A');
        $tokenA->accessToken->update(['tenant_id' => $this->tenantA->id]);

        $response = $this->withToken($tokenA->plainTextToken)
            ->getJson('/api/v1/orders');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_webhook_logs_filtered_by_tenant(): void
    {
        WebhookLog::create([
            'tenant_id' => $this->tenantA->id,
            'event' => 'order.created',
            'url' => 'https://a.example.com/webhook',
            'payload' => ['test' => true],
            'status_code' => 200,
            'response' => 'ok',
            'attempt' => 1,
        ]);

        WebhookLog::create([
            'tenant_id' => $this->tenantB->id,
            'event' => 'order.created',
            'url' => 'https://b.example.com/webhook',
            'payload' => ['test' => true],
            'status_code' => 200,
            'response' => 'ok',
            'attempt' => 1,
        ]);

        app()->instance('currentTenant', $this->tenantA);
        $logsA = WebhookLog::all();
        $this->assertCount(1, $logsA);
        $this->assertEquals('https://a.example.com/webhook', $logsA->first()->url);

        app()->instance('currentTenant', $this->tenantB);
        $logsB = WebhookLog::all();
        $this->assertCount(1, $logsB);
        $this->assertEquals('https://b.example.com/webhook', $logsB->first()->url);
    }

    public function test_direct_db_query_without_scope_returns_all(): void
    {
        app()->instance('currentTenant', $this->tenantA);
        Order::factory()->count(2)->create();

        app()->instance('currentTenant', $this->tenantB);
        Order::factory()->count(3)->create();

        // Direct query bypassing Eloquent scopes
        $count = \Illuminate\Support\Facades\DB::table('orders')->count();
        $this->assertEquals(5, $count);
    }
}
