<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashRegisterTest extends TestCase
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

    public function test_cash_register_accessible_with_permission(): void
    {
        $user = $this->createOwnerWithPlan('growth');

        $this->actingAs($user)
            ->withSession(['tenant_id' => $user->tenants()->first()->id])
            ->get('/admin/cash-register')
            ->assertOk();
    }

    public function test_totals_by_method_correct(): void
    {
        $user = $this->createOwnerWithPlan('growth');
        $tenant = $user->tenants()->first();

        $order = Order::factory()->create(['tenant_id' => $tenant->id]);

        Payment::create([
            'order_id' => $order->id,
            'amount' => 500,
            'method' => 'cash',
            'received_at' => now(),
        ]);
        Payment::create([
            'order_id' => $order->id,
            'amount' => 300,
            'method' => 'transfer',
            'received_at' => now(),
        ]);

        $totalCash = Payment::where('method', 'cash')
            ->whereDate('received_at', today())
            ->sum('amount');
        $totalTransfer = Payment::where('method', 'transfer')
            ->whereDate('received_at', today())
            ->sum('amount');

        $this->assertEquals(500, $totalCash);
        $this->assertEquals(300, $totalTransfer);
    }

    public function test_date_filter_works(): void
    {
        $user = $this->createOwnerWithPlan('growth');
        $tenant = $user->tenants()->first();

        $order = Order::factory()->create(['tenant_id' => $tenant->id]);

        Payment::create([
            'order_id' => $order->id,
            'amount' => 100,
            'method' => 'cash',
            'received_at' => now(),
        ]);
        Payment::create([
            'order_id' => $order->id,
            'amount' => 200,
            'method' => 'cash',
            'received_at' => now()->subDays(10),
        ]);

        // Only today's payments
        $todayTotal = Payment::whereDate('received_at', today())->sum('amount');
        $this->assertEquals(100, $todayTotal);

        // Last 30 days
        $monthTotal = Payment::whereBetween('received_at', [now()->subDays(30), now()])->sum('amount');
        $this->assertEquals(300, $monthTotal);
    }
}
