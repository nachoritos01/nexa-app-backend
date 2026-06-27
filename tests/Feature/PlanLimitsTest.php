<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTenant;
use Tests\TestCase;

class PlanLimitsTest extends TestCase
{
    use RefreshDatabase;
    use WithTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenant();
    }

    // --- isAtLimit ---

    public function test_starter_is_at_limit_when_orders_reach_max(): void
    {
        $this->tenant->update(['plan' => 'starter']);
        $maxOrders = config('saas.plans.starter.max_orders');

        Order::factory()->count($maxOrders)->create(['tenant_id' => $this->tenant->id]);

        $this->assertTrue($this->tenant->isAtLimit('orders'));
    }

    public function test_starter_is_not_at_limit_below_max(): void
    {
        $this->tenant->update(['plan' => 'starter']);

        Order::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);

        $this->assertFalse($this->tenant->isAtLimit('orders'));
    }

    public function test_pro_never_at_limit(): void
    {
        $this->tenant->update(['plan' => 'pro']);

        Order::factory()->count(100)->create(['tenant_id' => $this->tenant->id]);

        $this->assertFalse($this->tenant->isAtLimit('orders'));
        $this->assertFalse($this->tenant->isAtLimit('items'));
        $this->assertFalse($this->tenant->isAtLimit('locations'));
        $this->assertFalse($this->tenant->isAtLimit('customers'));
    }

    // --- isNearLimit ---

    public function test_near_limit_at_80_percent(): void
    {
        $this->tenant->update(['plan' => 'starter']);
        $maxOrders = config('saas.plans.starter.max_orders');
        $nearCount = (int) ceil($maxOrders * 0.8);

        Order::factory()->count($nearCount)->create(['tenant_id' => $this->tenant->id]);

        $this->assertTrue($this->tenant->isNearLimit('orders'));
    }

    public function test_not_near_limit_below_80_percent(): void
    {
        $this->tenant->update(['plan' => 'starter']);
        $maxOrders = config('saas.plans.starter.max_orders');
        $safeCount = (int) floor($maxOrders * 0.5);

        Order::factory()->count($safeCount)->create(['tenant_id' => $this->tenant->id]);

        $this->assertFalse($this->tenant->isNearLimit('orders'));
    }

    public function test_pro_never_near_limit(): void
    {
        $this->tenant->update(['plan' => 'pro']);

        Order::factory()->count(100)->create(['tenant_id' => $this->tenant->id]);

        $this->assertFalse($this->tenant->isNearLimit('orders'));
    }

    // --- usagePercentage ---

    public function test_usage_percentage_returns_correct_value(): void
    {
        $this->tenant->update(['plan' => 'starter']);
        $maxItems = config('saas.plans.starter.max_items');

        Item::factory()->count(10)->create(['tenant_id' => $this->tenant->id]);

        $percentage = $this->tenant->usagePercentage('items');
        $expected = (int) round(10 / $maxItems * 100);

        $this->assertEquals($expected, $percentage);
    }

    public function test_usage_percentage_null_for_unlimited(): void
    {
        $this->tenant->update(['plan' => 'pro']);

        $this->assertNull($this->tenant->usagePercentage('orders'));
    }

    // --- usageCounts ---

    public function test_usage_counts_orders_only_current_month(): void
    {
        $this->tenant->update(['plan' => 'starter']);

        // Orders from current month
        Order::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        // Orders from last month
        Order::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now()->subMonth(),
        ]);

        $usage = $this->tenant->usageCounts();

        $this->assertEquals(3, $usage['orders']);
    }

    // --- Policy enforcement ---

    public function test_policy_blocks_order_creation_at_limit(): void
    {
        $this->tenant->update(['plan' => 'starter']);
        $maxOrders = config('saas.plans.starter.max_orders');

        Order::factory()->count($maxOrders)->create(['tenant_id' => $this->tenant->id]);

        $this->assertFalse($this->tenantUser->can('create', Order::class));
    }

    public function test_policy_allows_order_creation_below_limit(): void
    {
        $this->tenant->update(['plan' => 'starter']);

        Order::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);

        $this->assertTrue($this->tenantUser->can('create', Order::class));
    }

    public function test_policy_allows_order_creation_on_pro(): void
    {
        $this->tenant->update(['plan' => 'pro']);

        Order::factory()->count(100)->create(['tenant_id' => $this->tenant->id]);

        $this->assertTrue($this->tenantUser->can('create', Order::class));
    }

    // --- Subscription helpers ---

    public function test_is_subscribed_with_subscribed_at(): void
    {
        $tenant = Tenant::factory()->create([
            'subscribed_at' => now(),
        ]);

        $this->assertTrue($tenant->isSubscribed());
    }

    public function test_is_not_subscribed_without_subscribed_at(): void
    {
        $tenant = Tenant::factory()->create([
            'subscribed_at' => null,
        ]);

        $this->assertFalse($tenant->isSubscribed());
    }

    // --- planLabel ---

    public function test_plan_label_returns_config_label(): void
    {
        $this->tenant->update(['plan' => 'growth']);
        $this->assertEquals('Growth', $this->tenant->planLabel());

        $this->tenant->update(['plan' => 'pro']);
        $this->assertEquals('Pro', $this->tenant->planLabel());

        $this->tenant->update(['plan' => 'starter']);
        $this->assertEquals('Starter', $this->tenant->planLabel());
    }

    // --- Factory state ---

    public function test_subscribed_factory_state(): void
    {
        $tenant = Tenant::factory()->subscribed('pro')->create();

        $this->assertEquals('pro', $tenant->plan);
        $this->assertNotNull($tenant->subscribed_at);
        $this->assertTrue($tenant->isSubscribed());
    }
}
