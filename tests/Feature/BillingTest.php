<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTenant;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;
    use WithTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenant();
    }

    public function test_owner_can_access_billing_page(): void
    {
        $response = $this->actingAs($this->tenantUser)
            ->get(route('filament.admin.pages.billing'));

        $response->assertOk();
    }

    public function test_admin_cannot_access_billing_page(): void
    {
        $admin = $this->createUserWithRole('admin');

        $response = $this->actingAs($admin)
            ->get(route('filament.admin.pages.billing'));

        $response->assertForbidden();
    }

    public function test_ventas_cannot_access_billing_page(): void
    {
        $ventas = $this->createUserWithRole('ventas');

        $response = $this->actingAs($ventas)
            ->get(route('filament.admin.pages.billing'));

        $response->assertForbidden();
    }

    public function test_billing_page_shows_current_plan(): void
    {
        $response = $this->actingAs($this->tenantUser)
            ->get(route('filament.admin.pages.billing'));

        $response->assertOk();
        $response->assertSee($this->tenant->planLabel() . ' Plan');
    }

    public function test_billing_page_shows_plan_comparison(): void
    {
        $response = $this->actingAs($this->tenantUser)
            ->get(route('filament.admin.pages.billing'));

        $response->assertOk();
        $response->assertSee('Starter');
        $response->assertSee('Growth');
        $response->assertSee('Pro');
    }
}
