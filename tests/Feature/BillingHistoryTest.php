<?php

namespace Tests\Feature;

use App\Events\PlanChanged;
use App\Models\BillingEvent;
use App\Models\Plugin;
use App\Models\Tenant;
use App\Services\BillingHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTenant;
use Tests\TestCase;

class BillingHistoryTest extends TestCase
{
    use RefreshDatabase;
    use WithTenant;

    private BillingHistoryService $historyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenant();
        $this->historyService = app(BillingHistoryService::class);
    }

    public function test_billing_event_recorded_on_subscription(): void
    {
        $this->historyService->recordSubscriptionStarted($this->tenant, 'growth');

        $this->assertDatabaseHas('billing_events', [
            'tenant_id' => $this->tenant->id,
            'type' => 'subscription_started',
            'description' => 'Subscribed to Growth plan',
            'amount' => 699,
        ]);
    }

    public function test_billing_event_recorded_on_plan_change(): void
    {
        PlanChanged::dispatch($this->tenant, 'starter', 'growth');

        $this->assertDatabaseHas('billing_events', [
            'tenant_id' => $this->tenant->id,
            'type' => 'plan_changed',
            'description' => 'Changed plan from Starter to Growth',
            'amount' => 699,
        ]);
    }

    public function test_billing_event_recorded_on_plugin_activation(): void
    {
        $plugin = Plugin::factory()->create([
            'slug' => 'history_test_plugin',
            'name' => 'History Test Plugin',
            'is_free' => false,
            'price_monthly' => 499,
            'stripe_price_id' => null,
            'included_in_plans' => [],
        ]);

        $this->historyService->recordPluginActivated($this->tenant, $plugin);

        $this->assertDatabaseHas('billing_events', [
            'tenant_id' => $this->tenant->id,
            'type' => 'plugin_activated',
            'description' => 'Activated History Test Plugin',
            'amount' => 499,
        ]);
    }

    public function test_billing_event_recorded_on_plugin_deactivation(): void
    {
        $plugin = Plugin::factory()->create([
            'slug' => 'deactivate_history',
            'name' => 'Deactivate History Plugin',
        ]);

        $this->historyService->recordPluginDeactivated($this->tenant, $plugin);

        $this->assertDatabaseHas('billing_events', [
            'tenant_id' => $this->tenant->id,
            'type' => 'plugin_deactivated',
            'description' => 'Deactivated Deactivate History Plugin',
            'amount' => null,
        ]);
    }

    public function test_billing_event_recorded_on_subscription_cancellation(): void
    {
        $this->historyService->recordSubscriptionCancelled($this->tenant);

        $this->assertDatabaseHas('billing_events', [
            'tenant_id' => $this->tenant->id,
            'type' => 'subscription_cancelled',
        ]);
    }

    public function test_billing_history_visible_on_billing_page(): void
    {
        $this->historyService->recordSubscriptionStarted($this->tenant, 'growth');

        $response = $this->actingAs($this->tenantUser)
            ->get(route('filament.admin.pages.billing'));

        $response->assertOk();
        $response->assertSee('Billing History');
        $response->assertSee('Subscribed to Growth plan');
    }

    public function test_billing_history_tenant_isolation(): void
    {
        // Create event for current tenant
        $this->historyService->recordSubscriptionStarted($this->tenant, 'growth');

        // Create event for another tenant (use DB to bypass BelongsToTenant boot)
        $otherTenant = Tenant::factory()->create();
        \Illuminate\Support\Facades\DB::table('billing_events')->insert([
            'tenant_id' => $otherTenant->id,
            'type' => 'subscription_started',
            'description' => 'Subscribed to Pro plan',
            'amount' => 1299,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Current tenant should only see their own events
        $events = $this->tenant->billingEvents()->get();
        $this->assertCount(1, $events);
        $this->assertEquals('Subscribed to Growth plan', $events->first()->description);
    }

    public function test_billing_history_empty_when_no_events(): void
    {
        $response = $this->actingAs($this->tenantUser)
            ->get(route('filament.admin.pages.billing'));

        $response->assertOk();
        $response->assertDontSee('Billing History');
    }

    public function test_formatted_amount_returns_correct_format(): void
    {
        $this->historyService->recordSubscriptionStarted($this->tenant, 'growth');

        $event = BillingEvent::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)
            ->first();

        $this->assertEquals('$699/mo', $event->formattedAmount());
    }

    public function test_formatted_amount_returns_null_when_no_amount(): void
    {
        $this->historyService->recordSubscriptionCancelled($this->tenant);

        $event = BillingEvent::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)
            ->first();

        $this->assertNull($event->formattedAmount());
    }
}
