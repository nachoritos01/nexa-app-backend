<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createOwnerWithPlan(string $plan): User
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create([
            'owner_id' => $user->id,
            'plan' => $plan,
            'is_active' => true,
        ]);
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $user->assignRole('owner');

        app()->instance('currentTenant', $tenant);
        session(['tenant_id' => $tenant->id]);

        return $user;
    }

    public function test_product_profitability_returns_correct_data(): void
    {
        $user = $this->createOwnerWithPlan('growth');
        $tenant = $user->tenants()->first();

        $item = Item::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Test Item']);
        $order = Order::factory()->create(['tenant_id' => $tenant->id]);

        OrderLine::factory()->create([
            'order_id' => $order->id,
            'item_id' => $item->id,
            'quantity' => 10,
            'unit_price' => 200,
            'subtotal' => 2000,
        ]);

        $lines = OrderLine::query()
            ->join('orders', 'order_lines.order_id', '=', 'orders.id')
            ->join('items', 'order_lines.item_id', '=', 'items.id')
            ->whereBetween('orders.created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->whereNull('orders.deleted_at')
            ->selectRaw('items.name, SUM(order_lines.quantity) as total_qty, SUM(order_lines.subtotal) as total_revenue')
            ->groupBy('items.id', 'items.name')
            ->get();

        $this->assertCount(1, $lines);
        $this->assertEquals('Test Item', $lines->first()->name);
        $this->assertEquals(10, $lines->first()->total_qty);
        $this->assertEquals(2000, $lines->first()->total_revenue);
    }

    public function test_monthly_comparison_returns_two_datasets(): void
    {
        $user = $this->createOwnerWithPlan('growth');
        $tenant = $user->tenants()->first();

        // Create orders this month and last month
        Order::factory()->create(['tenant_id' => $tenant->id, 'created_at' => now()]);
        Order::factory()->create(['tenant_id' => $tenant->id, 'created_at' => now()->subMonth()]);

        $currentData = Order::query()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $previousData = Order::query()
            ->whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->count();

        $this->assertGreaterThanOrEqual(1, $currentData);
        $this->assertGreaterThanOrEqual(1, $previousData);
    }

    public function test_export_profitability_csv(): void
    {
        $user = $this->createOwnerWithPlan('growth');

        $this->actingAs($user)
            ->withSession(['tenant_id' => $user->tenants()->first()->id])
            ->get('/admin/exports/profitability')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_reports_only_visible_with_permission(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create([
            'owner_id' => $user->id,
            'plan' => 'growth',
            'is_active' => true,
        ]);
        $tenant->users()->attach($user->id, ['role' => 'ventas']);
        $user->assignRole('ventas');

        app()->instance('currentTenant', $tenant);

        // ventas role doesn't have reports.export permission
        $this->assertFalse($user->can('reports.export'));
    }
}
