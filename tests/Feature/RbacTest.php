<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTenant;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;
    use WithTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenant();
    }

    // --- Owner ---

    public function test_owner_has_all_permissions(): void
    {
        $permissions = [
            'orders.view', 'orders.create', 'orders.edit', 'orders.delete',
            'production.view', 'production.mark',
            'products.manage', 'customers.manage',
            'payments.view', 'payments.create',
            'pricing.manage', 'reports.export',
            'settings.manage', 'users.manage', 'billing.manage',
        ];

        foreach ($permissions as $permission) {
            $this->assertTrue(
                $this->tenantUser->can($permission),
                "Owner should have '{$permission}'"
            );
        }
    }

    public function test_owner_can_access_panel(): void
    {
        $this->assertTrue($this->tenantUser->canAccessPanel(\Filament\Facades\Filament::getDefaultPanel()));
    }

    // --- Admin ---

    public function test_admin_has_all_permissions_except_billing(): void
    {
        $admin = $this->createUserWithRole('admin');

        $this->assertTrue($admin->can('orders.view'));
        $this->assertTrue($admin->can('orders.create'));
        $this->assertTrue($admin->can('orders.edit'));
        $this->assertTrue($admin->can('orders.delete'));
        $this->assertTrue($admin->can('products.manage'));
        $this->assertTrue($admin->can('settings.manage'));
        $this->assertTrue($admin->can('users.manage'));
        $this->assertFalse($admin->can('billing.manage'));
    }

    // --- Ventas ---

    public function test_ventas_can_manage_orders_and_payments(): void
    {
        $ventas = $this->createUserWithRole('ventas');

        $this->assertTrue($ventas->can('orders.view'));
        $this->assertTrue($ventas->can('orders.create'));
        $this->assertTrue($ventas->can('orders.edit'));
        $this->assertTrue($ventas->can('customers.manage'));
        $this->assertTrue($ventas->can('payments.view'));
        $this->assertTrue($ventas->can('payments.create'));
    }

    public function test_ventas_cannot_delete_orders_or_manage_products(): void
    {
        $ventas = $this->createUserWithRole('ventas');

        $this->assertFalse($ventas->can('orders.delete'));
        $this->assertFalse($ventas->can('products.manage'));
        $this->assertFalse($ventas->can('settings.manage'));
        $this->assertFalse($ventas->can('production.view'));
    }

    // --- Produccion ---

    public function test_produccion_can_view_orders_and_production(): void
    {
        $produccion = $this->createUserWithRole('produccion');

        $this->assertTrue($produccion->can('orders.view'));
        $this->assertTrue($produccion->can('production.view'));
        $this->assertTrue($produccion->can('production.mark'));
    }

    public function test_produccion_cannot_create_or_edit_orders(): void
    {
        $produccion = $this->createUserWithRole('produccion');

        $this->assertFalse($produccion->can('orders.create'));
        $this->assertFalse($produccion->can('orders.edit'));
        $this->assertFalse($produccion->can('orders.delete'));
        $this->assertFalse($produccion->can('products.manage'));
        $this->assertFalse($produccion->can('payments.view'));
    }

    // --- Contabilidad ---

    public function test_contabilidad_can_view_orders_and_payments(): void
    {
        $contabilidad = $this->createUserWithRole('contabilidad');

        $this->assertTrue($contabilidad->can('orders.view'));
        $this->assertTrue($contabilidad->can('payments.view'));
        $this->assertTrue($contabilidad->can('reports.export'));
    }

    public function test_contabilidad_cannot_create_or_edit(): void
    {
        $contabilidad = $this->createUserWithRole('contabilidad');

        $this->assertFalse($contabilidad->can('orders.create'));
        $this->assertFalse($contabilidad->can('orders.edit'));
        $this->assertFalse($contabilidad->can('payments.create'));
        $this->assertFalse($contabilidad->can('products.manage'));
        $this->assertFalse($contabilidad->can('settings.manage'));
    }

    // --- No tenant ---

    public function test_user_without_tenant_cannot_access_panel(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->canAccessPanel(\Filament\Facades\Filament::getDefaultPanel()));
    }
}
