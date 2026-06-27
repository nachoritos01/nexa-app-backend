<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Item;
use App\Models\Location;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PersonalAccessToken;
use App\Observers\CacheInvalidationObserver;
use App\Observers\OrderObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \Filament\Http\Responses\Auth\Contracts\RegistrationResponse::class,
            \App\Http\Responses\RegistrationResponse::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Cashier::useCustomerModel(\App\Models\Tenant::class);
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        Order::observe(OrderObserver::class);

        $cacheModels = [Order::class, Customer::class, Item::class, Location::class, Payment::class];
        foreach ($cacheModels as $model) {
            $model::observe(CacheInvalidationObserver::class);
        }

        Event::subscribe(\App\Listeners\WebhookEventSubscriber::class);

        $this->configureRateLimiting();

        Event::listen(Login::class, function (Login $event): void {
            /** @var \App\Models\User $user */
            $user = $event->user;
            $user->updateQuietly(['last_login_at' => now()]);
        });

        Event::listen(\App\Events\PlanChanged::class, function (\App\Events\PlanChanged $event): void {
            app(\App\Services\BillingHistoryService::class)->recordPlanChanged(
                $event->tenant,
                $event->previousPlan,
                $event->newPlan
            );
        });
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api-tenant', function (Request $request) {
            $user = $request->user();

            if (! $user) {
                return Limit::perMinute(config('saas.api.rate_limits.unauthenticated', 10))
                    ->by($request->ip());
            }

            $token = $user->currentAccessToken();
            $tenant = null;

            if ($token instanceof PersonalAccessToken && $token->tenant_id) {
                $tenant = \App\Models\Tenant::find($token->tenant_id);
            }

            $plan = $tenant?->plan ?? 'starter';
            $limit = config("saas.api.rate_limits.{$plan}", 60);

            return Limit::perMinute($limit)->by($token?->getKey() ?? $request->ip());
        });
    }
}
