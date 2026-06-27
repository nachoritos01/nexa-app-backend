<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTenant;
use Tests\TestCase;

class ModuleFlagsTest extends TestCase
{
    use RefreshDatabase;
    use WithTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenant();
    }

    public function test_all_modules_enabled_by_default(): void
    {
        $this->assertTrue(hasModule('payments'));
        $this->assertTrue(hasModule('customer_portal'));
        $this->assertTrue(hasModule('locations'));
        $this->assertTrue(hasModule('api'));
        $this->assertTrue(hasModule('exports'));
    }

    public function test_unknown_module_returns_false(): void
    {
        $this->assertFalse(hasModule('nonexistent'));
        $this->assertFalse(hasModule(''));
    }

    public function test_disabled_payments_returns_404_on_billing(): void
    {
        config(['modules.payments' => false]);

        $response = $this->actingAs($this->tenantUser)
            ->get('/billing/checkout?plan=growth&period=monthly');

        $response->assertNotFound();
    }

    public function test_disabled_customer_portal_returns_404(): void
    {
        config(['modules.customer_portal' => false]);

        $response = $this->get('/my-account/login');

        $response->assertNotFound();
    }

    public function test_disabled_exports_returns_404(): void
    {
        config(['modules.exports' => false]);

        $response = $this->actingAs($this->tenantUser)
            ->get('/admin/exports/orders');

        $response->assertNotFound();
    }

    public function test_disabled_module_reflects_in_helper(): void
    {
        config(['modules.api' => false]);
        $this->assertFalse(hasModule('api'));

        config(['modules.payments' => false]);
        $this->assertFalse(hasModule('payments'));

        config(['modules.locations' => false]);
        $this->assertFalse(hasModule('locations'));
    }
}
