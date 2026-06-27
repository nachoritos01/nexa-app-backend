<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTenant;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use Laravel\Cashier\SubscriptionBuilder;
use Mockery;
use Tests\Concerns\WithTenant;
use Tests\TestCase;

class BillingSwapTest extends TestCase
{
    use RefreshDatabase;
    use WithTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenant();
        $this->withoutMiddleware(EnsureTenant::class);

        config([
            'saas.plans.starter.stripe_monthly' => 'price_starter_monthly',
            'saas.plans.starter.stripe_yearly' => 'price_starter_yearly',
            'saas.plans.growth.stripe_monthly' => 'price_growth_monthly',
            'saas.plans.growth.stripe_yearly' => 'price_growth_yearly',
            'saas.plans.pro.stripe_monthly' => 'price_pro_monthly',
            'saas.plans.pro.stripe_yearly' => 'price_pro_yearly',
        ]);
    }

    public function test_first_subscription_redirects_to_stripe_checkout(): void
    {
        $this->tenant->update(['plan' => 'starter', 'subscribed_at' => null]);

        $checkoutBuilder = Mockery::mock(SubscriptionBuilder::class);
        $checkoutBuilder->shouldReceive('allowPromotionCodes')->andReturnSelf();
        $checkoutBuilder->shouldReceive('checkout')->andReturn(
            redirect('https://checkout.stripe.com/fake-session')
        );

        $tenantMock = Mockery::mock($this->tenant)->makePartial();
        $tenantMock->shouldReceive('subscribed')->with('default')->andReturn(false);
        $tenantMock->shouldReceive('isOnTrial')->andReturn(false);
        $tenantMock->shouldReceive('newSubscription')
            ->with('default', 'price_growth_monthly')
            ->andReturn($checkoutBuilder);

        app()->instance('currentTenant', $tenantMock);

        $response = $this->actingAs($this->tenantUser)
            ->get(route('billing.checkout', ['plan' => 'growth', 'period' => 'monthly']));

        $response->assertRedirect('https://checkout.stripe.com/fake-session');
    }

    public function test_swap_plan_when_already_subscribed(): void
    {
        $this->tenant->update(['plan' => 'starter', 'subscribed_at' => now()]);

        $subscriptionMock = Mockery::mock(Subscription::class)->makePartial();
        $subscriptionMock->shouldReceive('swap')
            ->with('price_growth_monthly')
            ->once()
            ->andReturnSelf();

        $tenantMock = Mockery::mock($this->tenant)->makePartial();
        $tenantMock->shouldReceive('subscribed')->with('default')->andReturn(true);
        $tenantMock->shouldReceive('subscription')->with('default')->andReturn($subscriptionMock);

        app()->instance('currentTenant', $tenantMock);

        $response = $this->actingAs($this->tenantUser)
            ->get(route('billing.checkout', ['plan' => 'growth', 'period' => 'monthly']));

        $response->assertRedirect(route('filament.admin.pages.billing'));
        $response->assertSessionHas('success', 'Plan updated successfully.');
    }

    public function test_downgrade_blocked_when_usage_exceeds_limits(): void
    {
        $this->tenant->update(['plan' => 'growth', 'subscribed_at' => now()]);

        // Create usage exceeding starter limits (starter: max_users=2, max_locations=1)
        $extraUsers = User::factory()->count(3)->create();
        foreach ($extraUsers as $user) {
            $this->tenant->users()->attach($user->id, ['role' => 'ventas']);
        }
        Location::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Location 1',
            'address' => 'Calle 1',
            'city' => 'CDMX',
        ]);
        Location::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Location 2',
            'address' => 'Calle 2',
            'city' => 'CDMX',
        ]);

        $this->tenant->clearUsageCache();
        $realUsage = $this->tenant->usageCounts();

        $subscriptionMock = Mockery::mock(Subscription::class)->makePartial();
        $subscriptionMock->shouldNotReceive('swap');

        $tenantMock = Mockery::mock($this->tenant)->makePartial();
        $tenantMock->shouldReceive('subscribed')->with('default')->andReturn(true);
        $tenantMock->shouldReceive('subscription')->with('default')->andReturn($subscriptionMock);
        $tenantMock->shouldReceive('usageCounts')->andReturn($realUsage);

        app()->instance('currentTenant', $tenantMock);

        $response = $this->actingAs($this->tenantUser)
            ->get(route('billing.checkout', ['plan' => 'starter', 'period' => 'monthly']));

        $response->assertRedirect(route('filament.admin.pages.billing'));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Cannot downgrade to this plan', session('error'));
    }

    public function test_checkout_redirects_with_error_when_no_price_id(): void
    {
        config(['saas.plans.growth.stripe_monthly' => null]);

        $response = $this->actingAs($this->tenantUser)
            ->get(route('billing.checkout', ['plan' => 'growth', 'period' => 'monthly']));

        $response->assertRedirect(route('filament.admin.pages.billing'));
        $response->assertSessionHas('error', 'Plan not available. Please configure Stripe price IDs.');
    }

    public function test_checkout_requires_authentication(): void
    {
        $response = $this->get(route('billing.checkout', ['plan' => 'growth', 'period' => 'monthly']));

        $response->assertRedirect();
        $this->assertStringContainsString('login', $response->headers->get('Location') ?? '');
    }
}
