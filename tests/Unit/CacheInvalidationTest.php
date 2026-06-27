<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->tenant = Tenant::factory()->create(['owner_id' => $user->id]);
        $this->tenant->users()->attach($user->id, ['role' => 'owner']);
        app()->instance('currentTenant', $this->tenant);
    }

    public function test_creating_order_invalidates_usage_cache(): void
    {
        Cache::put("tenant:{$this->tenant->id}:usage_counts", ['orders' => 0], 300);
        Cache::put("tenant:{$this->tenant->id}:dashboard_stats", ['todayOrders' => 0], 60);

        Order::factory()->create();

        $this->assertNull(Cache::get("tenant:{$this->tenant->id}:usage_counts"));
        $this->assertNull(Cache::get("tenant:{$this->tenant->id}:dashboard_stats"));
    }

    public function test_usage_counts_uses_cache_when_available(): void
    {
        $cached = [
            'orders' => 10,
            'users' => 2,
            'locations' => 1,
            'items' => 5,
            'customers' => 20,
        ];

        Cache::put("tenant:{$this->tenant->id}:usage_counts", $cached, 300);

        $result = $this->tenant->usageCounts();

        $this->assertEquals($cached, $result);
    }

    public function test_usage_counts_regenerates_after_cache_miss(): void
    {
        Cache::forget("tenant:{$this->tenant->id}:usage_counts");

        $result = $this->tenant->usageCounts();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('orders', $result);
        $this->assertArrayHasKey('users', $result);
        $this->assertArrayHasKey('locations', $result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('customers', $result);

        // Should now be cached
        $this->assertNotNull(Cache::get("tenant:{$this->tenant->id}:usage_counts"));
    }
}
