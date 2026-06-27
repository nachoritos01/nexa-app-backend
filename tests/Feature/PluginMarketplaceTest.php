<?php

namespace Tests\Feature;

use App\Filament\Pages\Marketplace;
use App\Filament\Resources\LocationResource;
use App\Models\Plugin;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Concerns\WithTenant;
use Tests\TestCase;

class PluginMarketplaceTest extends TestCase
{
    use RefreshDatabase;
    use WithTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenant();
    }

    public function test_has_module_backward_compatible_without_plugin_record(): void
    {
        // No plugin record exists for 'payments' — should return true (backward compat)
        config(['modules.payments' => true]);
        hasModule(null); // Clear cache

        $this->assertTrue(hasModule('payments'));
    }

    public function test_has_module_returns_true_without_tenant_context(): void
    {
        // Remove tenant context
        app()->forgetInstance('currentTenant');

        config(['modules.payments' => true]);
        hasModule(null); // Clear cache

        $this->assertTrue(hasModule('payments'));
    }

    public function test_has_module_false_when_global_disabled(): void
    {
        config(['modules.payments' => false]);
        hasModule(null); // Clear cache

        // Even with an active plugin, global kill switch wins
        $plugin = Plugin::factory()->create(['slug' => 'payments']);
        $this->tenant->activatePlugin($plugin);

        $this->assertFalse(hasModule('payments'));
    }

    public function test_has_module_false_when_plugin_not_active_for_tenant(): void
    {
        config(['modules.payments' => true]);
        hasModule(null); // Clear cache

        $plugin = Plugin::factory()->create(['slug' => 'payments']);
        // Plugin exists but tenant has NOT activated it

        $this->assertFalse(hasModule('payments'));
    }

    public function test_has_module_true_when_global_and_plugin_active(): void
    {
        config(['modules.payments' => true]);
        hasModule(null); // Clear cache

        $plugin = Plugin::factory()->create(['slug' => 'payments']);
        $this->tenant->activatePlugin($plugin);

        $this->assertTrue(hasModule('payments'));
    }

    public function test_has_module_memoizes_plugin_query(): void
    {
        config(['modules.payments' => true]);
        hasModule(null); // Clear cache

        $plugin = Plugin::factory()->create(['slug' => 'payments']);
        $this->tenant->activatePlugin($plugin);

        // First call — should query DB
        $result1 = hasModule('payments');
        $this->assertTrue($result1);

        // Count queries on second call — should be zero (memoized)
        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        $result2 = hasModule('payments');
        $this->assertTrue($result2);
        $this->assertEquals(0, $queryCount, 'hasModule() should not query DB on second call');
    }

    public function test_sidebar_hidden_when_plugin_disabled(): void
    {
        config(['modules.locations' => true]);
        hasModule(null); // Clear cache

        $plugin = Plugin::factory()->create([
            'slug' => 'locations',
            'required_modules' => ['locations'],
        ]);

        // Plugin exists but NOT activated for tenant
        $this->assertFalse(LocationResource::canAccess());

        // Activate it
        $this->tenant->activatePlugin($plugin);
        hasModule(null); // Clear cache

        $this->assertTrue(LocationResource::canAccess());

        // Deactivate it
        $this->tenant->deactivatePlugin($plugin);
        hasModule(null); // Clear cache

        $this->assertFalse(LocationResource::canAccess());
    }

    public function test_marketplace_toggle_via_livewire(): void
    {
        $plugin = Plugin::factory()->create([
            'slug' => 'test_livewire_toggle',
            'required_modules' => [],
        ]);

        Livewire::actingAs($this->tenantUser)
            ->test(Marketplace::class)
            ->call('togglePlugin', $plugin->id)
            ->assertHasNoErrors();

        $this->assertTrue($this->tenant->fresh()->hasPlugin('test_livewire_toggle'));

        // Toggle off
        Livewire::actingAs($this->tenantUser)
            ->test(Marketplace::class)
            ->call('togglePlugin', $plugin->id)
            ->assertHasNoErrors();

        $this->assertFalse($this->tenant->fresh()->hasPlugin('test_livewire_toggle'));
    }

    public function test_tenant_isolation_on_plugins(): void
    {
        $tenantB = Tenant::factory()->create();
        app()->instance('currentTenant', $this->tenant);

        $plugin = Plugin::factory()->create(['slug' => 'isolation_test']);

        // Activate for tenant A
        $this->tenant->activatePlugin($plugin);
        $this->assertTrue($this->tenant->hasPlugin('isolation_test'));
        $this->assertFalse($tenantB->hasPlugin('isolation_test'));

        // Activate for tenant B
        $tenantB->activatePlugin($plugin);
        $this->assertTrue($tenantB->hasPlugin('isolation_test'));

        // Deactivate for tenant A should NOT affect tenant B
        $this->tenant->deactivatePlugin($plugin);
        $this->assertFalse($this->tenant->hasPlugin('isolation_test'));
        $this->assertTrue($tenantB->hasPlugin('isolation_test'));
    }

    public function test_tenant_can_activate_plan_included_plugin(): void
    {
        $plugin = Plugin::factory()->create([
            'slug' => 'test_plugin',
            'included_in_plans' => ['starter', 'growth', 'pro'],
        ]);

        $this->tenant->activatePlugin($plugin);

        $this->assertTrue($this->tenant->hasPlugin('test_plugin'));
        $this->assertDatabaseHas('tenant_plugins', [
            'tenant_id' => $this->tenant->id,
            'plugin_id' => $plugin->id,
            'is_active' => true,
            'billing_type' => 'included',
        ]);
    }

    public function test_tenant_can_deactivate_plugin(): void
    {
        $plugin = Plugin::factory()->create(['slug' => 'test_deactivate']);
        $this->tenant->activatePlugin($plugin);

        $this->assertTrue($this->tenant->hasPlugin('test_deactivate'));

        $this->tenant->deactivatePlugin($plugin);

        $this->assertFalse($this->tenant->hasPlugin('test_deactivate'));
        $this->assertDatabaseHas('tenant_plugins', [
            'tenant_id' => $this->tenant->id,
            'plugin_id' => $plugin->id,
            'is_active' => false,
        ]);
    }

    public function test_marketplace_accessible_with_permission(): void
    {
        $response = $this->actingAs($this->tenantUser)
            ->get('/admin/marketplace');

        $response->assertOk();
    }

    public function test_marketplace_denied_without_permission(): void
    {
        $user = $this->createUserWithRole('produccion');

        $response = $this->actingAs($user)
            ->get('/admin/marketplace');

        $response->assertForbidden();
    }

    public function test_plugin_seeder_idempotent(): void
    {
        $this->seed(\Database\Seeders\PluginSeeder::class);
        $countAfterFirst = Plugin::count();

        $this->seed(\Database\Seeders\PluginSeeder::class);
        $countAfterSecond = Plugin::count();

        $this->assertEquals($countAfterFirst, $countAfterSecond);
        $this->assertEquals(8, $countAfterFirst);
    }

    public function test_existing_module_flags_test_still_passes(): void
    {
        hasModule(null); // Clear cache

        // Regression: hasModule still works for all config-based checks
        $this->assertTrue(hasModule('payments'));
        $this->assertTrue(hasModule('customer_portal'));
        $this->assertTrue(hasModule('locations'));
        $this->assertTrue(hasModule('api'));
        $this->assertTrue(hasModule('exports'));

        // Unknown module still returns false
        $this->assertFalse(hasModule('nonexistent'));

        // Disabling via config still works
        config(['modules.api' => false]);
        hasModule(null); // Clear cache
        $this->assertFalse(hasModule('api'));
    }

    public function test_plugin_is_included_in_plan(): void
    {
        $plugin = Plugin::factory()->create([
            'slug' => 'test_plan',
            'included_in_plans' => ['growth', 'pro'],
        ]);

        $this->assertTrue($plugin->isIncludedInPlan('growth'));
        $this->assertTrue($plugin->isIncludedInPlan('pro'));
        $this->assertFalse($plugin->isIncludedInPlan('starter'));
    }

    public function test_plugin_is_available_checks_global_modules(): void
    {
        $plugin = Plugin::factory()->create([
            'slug' => 'test_available',
            'required_modules' => ['payments'],
        ]);

        config(['modules.payments' => true]);
        $this->assertTrue($plugin->isAvailable());

        config(['modules.payments' => false]);
        $this->assertFalse($plugin->isAvailable());
    }
}
