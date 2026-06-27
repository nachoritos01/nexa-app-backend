<?php

namespace Tests\Feature;

use App\Enums\OrderPriority;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPriorityTest extends TestCase
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

        return $user;
    }

    public function test_priority_default_is_normal(): void
    {
        $user = $this->createOwnerWithPlan('growth');
        $tenant = $user->tenants()->first();

        $order = Order::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertEquals(OrderPriority::Normal, $order->fresh()->priority);
    }

    public function test_priority_can_be_set_to_urgent(): void
    {
        $user = $this->createOwnerWithPlan('growth');
        $tenant = $user->tenants()->first();

        $order = Order::factory()->confirmed()->create([
            'tenant_id' => $tenant->id,
            'priority' => OrderPriority::Urgent->value,
        ]);

        $this->assertEquals(OrderPriority::Urgent, $order->fresh()->priority);
    }

    public function test_priority_enum_has_correct_values(): void
    {
        $this->assertEquals('urgent', OrderPriority::Urgent->value);
        $this->assertEquals('high', OrderPriority::High->value);
        $this->assertEquals('normal', OrderPriority::Normal->value);
        $this->assertEquals('low', OrderPriority::Low->value);

        $this->assertEquals('Urgent', OrderPriority::Urgent->label());
        $this->assertEquals('danger', OrderPriority::Urgent->color());
    }
}
