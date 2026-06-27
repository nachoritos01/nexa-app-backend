<?php

namespace Tests\Feature;

use App\Filament\Widgets\CurrentPlanWidget;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTenant;
use Tests\TestCase;

class ConversionUiTest extends TestCase
{
    use RefreshDatabase;
    use WithTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenant();
    }

    // --- /suspended page ---

    public function test_suspended_page_accessible_by_authenticated_user(): void
    {
        $response = $this->actingAs($this->tenantUser)
            ->get(route('suspended'));

        $response->assertOk();
    }

    public function test_suspended_page_shows_plans(): void
    {
        $response = $this->actingAs($this->tenantUser)
            ->get(route('suspended'));

        $response->assertOk();
        $response->assertSee('Starter');
        $response->assertSee('Growth');
        $response->assertSee('Pro');
    }

    public function test_suspended_page_requires_authentication(): void
    {
        $response = $this->get(route('suspended'));

        $response->assertRedirect();
    }

    // --- Middleware suspension redirect ---

    public function test_middleware_redirects_suspended_tenant_to_suspended_page(): void
    {
        $graceDays = config('saas.trial.grace_days', 3);

        $this->tenant->update([
            'trial_ends_at' => now()->subDays($graceDays + 1),
            'subscribed_at' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->tenantUser)
            ->get('/admin');

        $response->assertRedirect(route('suspended'));
    }

    public function test_middleware_allows_billing_when_suspended(): void
    {
        $graceDays = config('saas.trial.grace_days', 3);

        $this->tenant->update([
            'trial_ends_at' => now()->subDays($graceDays + 1),
            'subscribed_at' => null,
            'is_active' => true,
        ]);

        // Billing checkout should not redirect to /suspended
        $response = $this->actingAs($this->tenantUser)
            ->get(route('billing.checkout', ['plan' => 'starter', 'period' => 'monthly']));

        // Should not be a redirect to /suspended (may redirect to Stripe or error about price ID)
        $this->assertNotEquals(route('suspended'), $response->headers->get('Location'));
    }

    public function test_middleware_allows_suspended_page_when_suspended(): void
    {
        $graceDays = config('saas.trial.grace_days', 3);

        $this->tenant->update([
            'trial_ends_at' => now()->subDays($graceDays + 1),
            'subscribed_at' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->tenantUser)
            ->get(route('suspended'));

        $response->assertOk();
    }

    public function test_middleware_does_not_redirect_active_tenant(): void
    {
        $this->tenant->update([
            'trial_ends_at' => now()->addDays(10),
            'subscribed_at' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->tenantUser)
            ->get('/admin');

        $response->assertOk();
    }

    public function test_middleware_does_not_redirect_subscribed_tenant(): void
    {
        $this->tenant->update([
            'subscribed_at' => now(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->tenantUser)
            ->get('/admin');

        $response->assertOk();
    }

    // --- Middleware: user with only suspended tenants ---

    public function test_middleware_redirects_user_with_only_suspended_tenants(): void
    {
        $graceDays = config('saas.trial.grace_days', 3);

        // Make the tenant inactive (suspended) so it won't be found in active query
        $this->tenant->update([
            'trial_ends_at' => now()->subDays($graceDays + 1),
            'subscribed_at' => null,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->tenantUser)
            ->get('/admin');

        $response->assertRedirect(route('suspended'));
    }

    // --- CurrentPlanWidget ---

    public function test_current_plan_widget_visible_for_trial_tenant(): void
    {
        $this->tenant->update([
            'trial_ends_at' => now()->addDays(10),
            'subscribed_at' => null,
        ]);

        $this->assertTrue(CurrentPlanWidget::canView());
    }

    public function test_current_plan_widget_visible_for_subscribed_tenant(): void
    {
        $this->tenant->update([
            'subscribed_at' => now(),
        ]);

        $this->assertTrue(CurrentPlanWidget::canView());
    }

    public function test_current_plan_widget_shows_upgrade_for_starter(): void
    {
        $this->tenant->update([
            'plan' => 'starter',
            'subscribed_at' => now(),
        ]);

        $widget = new CurrentPlanWidget();
        $data = $widget->getPlanData();

        $this->assertTrue($data['show_upgrade']);
    }

    public function test_current_plan_widget_shows_upgrade_for_growth(): void
    {
        $this->tenant->update([
            'plan' => 'growth',
            'subscribed_at' => now(),
        ]);

        $widget = new CurrentPlanWidget();
        $data = $widget->getPlanData();

        $this->assertTrue($data['show_upgrade']);
    }

    public function test_current_plan_widget_hides_upgrade_for_pro(): void
    {
        $this->tenant->update([
            'plan' => 'pro',
            'subscribed_at' => now(),
        ]);

        $widget = new CurrentPlanWidget();
        $data = $widget->getPlanData();

        $this->assertFalse($data['show_upgrade']);
    }
}
