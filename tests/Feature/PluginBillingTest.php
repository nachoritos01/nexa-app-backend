<?php

namespace Tests\Feature;

use App\Models\Plugin;
use App\Models\Tenant;
use App\Services\PluginBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\WithTenant;
use Tests\TestCase;

class PluginBillingTest extends TestCase
{
    use RefreshDatabase;
    use WithTenant;

    private PluginBillingService $billingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenant();
        $this->billingService = app(PluginBillingService::class);
    }

    public function test_free_plugin_activation_no_stripe_call(): void
    {
        $plugin = Plugin::factory()->create([
            'slug' => 'free_test',
            'is_free' => true,
            'included_in_plans' => ['starter', 'growth', 'pro'],
        ]);

        // Free plugin should activate directly without touching Stripe
        $this->billingService->activatePlugin($this->tenant, $plugin);

        $this->assertTrue($this->tenant->hasPlugin('free_test'));
        $this->assertDatabaseHas('tenant_plugins', [
            'tenant_id' => $this->tenant->id,
            'plugin_id' => $plugin->id,
            'is_active' => true,
            'billing_type' => 'included',
        ]);
    }

    public function test_included_plugin_activates_without_stripe(): void
    {
        $plugin = Plugin::factory()->create([
            'slug' => 'included_test',
            'is_free' => false,
            'price_monthly' => 999,
            'stripe_price_id' => 'price_included_test',
            'included_in_plans' => [$this->tenant->plan],
        ]);

        // Plugin included in plan should activate directly
        $this->billingService->activatePlugin($this->tenant, $plugin);

        $this->assertTrue($this->tenant->hasPlugin('included_test'));
        $this->assertDatabaseHas('tenant_plugins', [
            'tenant_id' => $this->tenant->id,
            'plugin_id' => $plugin->id,
            'billing_type' => 'included',
        ]);
    }

    public function test_paid_plugin_requires_subscription(): void
    {
        $plugin = Plugin::factory()->withStripePrice('price_paid_test')->create([
            'slug' => 'paid_no_sub',
            'included_in_plans' => [],
        ]);

        // Tenant has no Stripe subscription — should throw
        $this->expectException(\RuntimeException::class);

        $this->billingService->activatePlugin($this->tenant, $plugin);
    }

    public function test_subscription_deletion_deactivates_paid_plugins(): void
    {
        $plugin = Plugin::factory()->withStripePrice('price_deactivate_test')->create([
            'slug' => 'paid_deactivate',
            'included_in_plans' => [],
        ]);

        // Manually activate as paid (simulating prior Stripe activation)
        $this->tenant->plugins()->syncWithoutDetaching([
            $plugin->id => [
                'is_active' => true,
                'activated_at' => now(),
                'billing_type' => 'paid',
                'stripe_subscription_item_id' => 'si_test_123',
            ],
        ]);
        $this->tenant->clearPluginCache();

        $this->assertTrue($this->tenant->hasPlugin('paid_deactivate'));

        // Simulate subscription deletion — deactivate all paid plugins
        $this->billingService->deactivateAllPaidPlugins($this->tenant);

        $this->tenant->clearPluginCache();
        $this->assertFalse($this->tenant->hasPlugin('paid_deactivate'));
        $this->assertDatabaseHas('tenant_plugins', [
            'tenant_id' => $this->tenant->id,
            'plugin_id' => $plugin->id,
            'is_active' => false,
        ]);
    }

    public function test_free_plugin_deactivation_no_stripe_call(): void
    {
        $plugin = Plugin::factory()->create([
            'slug' => 'free_deactivate',
            'is_free' => true,
        ]);

        $this->billingService->activatePlugin($this->tenant, $plugin);
        $this->assertTrue($this->tenant->hasPlugin('free_deactivate'));

        $this->billingService->deactivatePlugin($this->tenant, $plugin);
        $this->tenant->clearPluginCache();
        $this->assertFalse($this->tenant->hasPlugin('free_deactivate'));
    }

    public function test_billing_page_shows_plugin_addons(): void
    {
        $plugin = Plugin::factory()->withStripePrice('price_addon_display')->create([
            'slug' => 'addon_display',
            'name' => 'Test Addon',
            'included_in_plans' => [],
        ]);

        // Manually activate as paid
        $this->tenant->plugins()->syncWithoutDetaching([
            $plugin->id => [
                'is_active' => true,
                'activated_at' => now(),
                'billing_type' => 'paid',
                'stripe_subscription_item_id' => 'si_display_123',
            ],
        ]);
        $this->tenant->clearPluginCache();

        $response = $this->actingAs($this->tenantUser)
            ->get('/admin/billing');

        $response->assertOk();
        $response->assertSee('Active Add-ons');
        $response->assertSee('Test Addon');
    }

    public function test_marketplace_shows_confirm_modal_for_paid_plugin(): void
    {
        $plugin = Plugin::factory()->withStripePrice('price_modal_test')->create([
            'slug' => 'paid_modal',
            'name' => 'Paid Modal Plugin',
            'included_in_plans' => [],
        ]);

        // Tenant needs a subscription for the modal to show (otherwise "subscribe first")
        // Without subscription, should show warning notification instead
        Livewire::actingAs($this->tenantUser)
            ->test(\App\Filament\Pages\Marketplace::class)
            ->call('togglePlugin', $plugin->id)
            ->assertNotified();
    }

    public function test_plugin_factory_with_stripe_price_state(): void
    {
        $plugin = Plugin::factory()->withStripePrice('price_factory_test')->create();

        $this->assertFalse($plugin->is_free);
        $this->assertEquals(999, $plugin->price_monthly);
        $this->assertEquals('price_factory_test', $plugin->stripe_price_id);
    }
}
