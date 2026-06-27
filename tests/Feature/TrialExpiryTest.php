<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantSuspendedNotification;
use App\Notifications\TrialExpiredNotification;
use App\Notifications\TrialExpiringNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\WithTenant;
use Tests\TestCase;

class TrialExpiryTest extends TestCase
{
    use RefreshDatabase;
    use WithTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenant();
    }

    // --- isSuspended() ---

    public function test_is_suspended_true_when_trial_expired_past_grace(): void
    {
        $graceDays = config('saas.trial.grace_days', 3);

        $tenant = Tenant::factory()->create([
            'trial_ends_at' => now()->subDays($graceDays + 1),
            'subscribed_at' => null,
            'is_active' => true,
        ]);

        $this->assertTrue($tenant->isSuspended());
    }

    public function test_is_suspended_false_during_grace_period(): void
    {
        $tenant = Tenant::factory()->create([
            'trial_ends_at' => now()->subDay(),
            'subscribed_at' => null,
            'is_active' => true,
        ]);

        $this->assertFalse($tenant->isSuspended());
    }

    public function test_is_suspended_false_when_subscribed(): void
    {
        $graceDays = config('saas.trial.grace_days', 3);

        $tenant = Tenant::factory()->create([
            'trial_ends_at' => now()->subDays($graceDays + 1),
            'subscribed_at' => now(),
            'is_active' => true,
        ]);

        $this->assertFalse($tenant->isSuspended());
    }

    public function test_is_suspended_false_when_on_trial(): void
    {
        $tenant = Tenant::factory()->trial()->create();

        $this->assertFalse($tenant->isSuspended());
    }

    // --- suspend() ---

    public function test_suspend_sets_is_active_false(): void
    {
        $tenant = Tenant::factory()->create(['is_active' => true]);

        $tenant->suspend();

        $this->assertFalse($tenant->fresh()->is_active);
    }

    // --- Command: expiring notifications ---

    public function test_command_sends_notification_3_days_before_expiry(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        Tenant::factory()->create([
            'owner_id' => $owner->id,
            'trial_ends_at' => now()->addDays(3),
            'subscribed_at' => null,
            'is_active' => true,
        ]);

        $this->artisan('saas:check-trial-expiry')->assertSuccessful();

        Notification::assertSentTo($owner, TrialExpiringNotification::class, function ($notification) {
            return $notification->daysRemaining === 3;
        });
    }

    public function test_command_sends_notification_1_day_before_expiry(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        Tenant::factory()->create([
            'owner_id' => $owner->id,
            'trial_ends_at' => now()->addDay(),
            'subscribed_at' => null,
            'is_active' => true,
        ]);

        $this->artisan('saas:check-trial-expiry')->assertSuccessful();

        Notification::assertSentTo($owner, TrialExpiringNotification::class, function ($notification) {
            return $notification->daysRemaining === 1;
        });
    }

    public function test_command_does_not_send_at_2_days(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        Tenant::factory()->create([
            'owner_id' => $owner->id,
            'trial_ends_at' => now()->addDays(2),
            'subscribed_at' => null,
            'is_active' => true,
        ]);

        $this->artisan('saas:check-trial-expiry')->assertSuccessful();

        Notification::assertNotSentTo($owner, TrialExpiringNotification::class);
    }

    // --- Command: expired notification ---

    public function test_command_sends_expired_notification_on_expiry_day(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        Tenant::factory()->create([
            'owner_id' => $owner->id,
            'trial_ends_at' => today(),
            'subscribed_at' => null,
            'is_active' => true,
        ]);

        $this->artisan('saas:check-trial-expiry')->assertSuccessful();

        Notification::assertSentTo($owner, TrialExpiredNotification::class);
    }

    // --- Command: suspension ---

    public function test_command_suspends_tenant_after_grace_period(): void
    {
        Notification::fake();

        $graceDays = config('saas.trial.grace_days', 3);
        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create([
            'owner_id' => $owner->id,
            'trial_ends_at' => now()->subDays($graceDays + 1),
            'subscribed_at' => null,
            'is_active' => true,
        ]);

        $this->artisan('saas:check-trial-expiry')->assertSuccessful();

        $this->assertFalse($tenant->fresh()->is_active);
        Notification::assertSentTo($owner, TenantSuspendedNotification::class);
    }

    public function test_command_does_not_affect_subscribed_tenants(): void
    {
        Notification::fake();

        $graceDays = config('saas.trial.grace_days', 3);
        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create([
            'owner_id' => $owner->id,
            'trial_ends_at' => now()->subDays($graceDays + 1),
            'subscribed_at' => now(),
            'is_active' => true,
        ]);

        $this->artisan('saas:check-trial-expiry')->assertSuccessful();

        $this->assertTrue($tenant->fresh()->is_active);
        Notification::assertNotSentTo($owner, TenantSuspendedNotification::class);
    }

    public function test_command_does_not_affect_already_suspended_tenants(): void
    {
        Notification::fake();

        $graceDays = config('saas.trial.grace_days', 3);
        $owner = User::factory()->create();
        Tenant::factory()->create([
            'owner_id' => $owner->id,
            'trial_ends_at' => now()->subDays($graceDays + 1),
            'subscribed_at' => null,
            'is_active' => false,
        ]);

        $this->artisan('saas:check-trial-expiry')->assertSuccessful();

        Notification::assertNotSentTo($owner, TenantSuspendedNotification::class);
    }
}
