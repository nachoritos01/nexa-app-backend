<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createUserWithPlan(string $plan): User
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create([
            'owner_id' => $user->id,
            'plan' => $plan,
            'is_active' => true,
        ]);
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $user->assignRole('owner');

        // Bind tenant context
        app()->instance('currentTenant', $tenant);
        session(['tenant_id' => $tenant->id]);

        return $user;
    }

    public function test_growth_plan_can_export_orders(): void
    {
        $user = $this->createUserWithPlan('growth');

        $this->actingAs($user)
            ->withSession(['tenant_id' => $user->tenants()->first()->id])
            ->get('/admin/exports/orders')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload('orders-' . now()->format('Y-m-d') . '.csv');
    }

    public function test_starter_plan_cannot_export(): void
    {
        $user = $this->createUserWithPlan('starter');

        $this->actingAs($user)
            ->withSession(['tenant_id' => $user->tenants()->first()->id])
            ->get('/admin/exports/orders')
            ->assertForbidden();
    }

    public function test_pro_plan_can_export_customers(): void
    {
        $user = $this->createUserWithPlan('pro');

        $this->actingAs($user)
            ->withSession(['tenant_id' => $user->tenants()->first()->id])
            ->get('/admin/exports/customers')
            ->assertOk()
            ->assertDownload('customers-' . now()->format('Y-m-d') . '.csv');
    }

    public function test_user_without_permission_cannot_export(): void
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

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get('/admin/exports/orders')
            ->assertForbidden();
    }

    public function test_unknown_export_type_returns_404(): void
    {
        $user = $this->createUserWithPlan('growth');

        $this->actingAs($user)
            ->withSession(['tenant_id' => $user->tenants()->first()->id])
            ->get('/admin/exports/unknown')
            ->assertNotFound();
    }
}
