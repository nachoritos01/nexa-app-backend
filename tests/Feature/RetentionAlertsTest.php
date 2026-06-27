<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\InactivityReminderNotification;
use App\Notifications\LowHealthScoreAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RetentionAlertsTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactivity_reminder_sent_after_14_days_without_orders(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $tenant = Tenant::factory()->subscribed()->create(['owner_id' => $owner->id]);
        $tenant->users()->attach($owner->id, ['role' => 'owner']);

        // Old order (20 days ago)
        Order::factory()->create([
            'tenant_id' => $tenant->id,
            'created_at' => now()->subDays(20),
        ]);

        $this->artisan('saas:retention-alerts')->assertSuccessful();

        Notification::assertSentTo($owner, InactivityReminderNotification::class);
    }

    public function test_inactivity_reminder_not_sent_with_recent_orders(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $tenant = Tenant::factory()->subscribed()->create(['owner_id' => $owner->id]);
        $tenant->users()->attach($owner->id, ['role' => 'owner']);

        // Recent order (3 days ago)
        Order::factory()->create([
            'tenant_id' => $tenant->id,
            'created_at' => now()->subDays(3),
        ]);

        $this->artisan('saas:retention-alerts')->assertSuccessful();

        Notification::assertNotSentTo($owner, InactivityReminderNotification::class);
    }

    public function test_inactivity_reminder_not_sent_twice_in_14_days(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $tenant = Tenant::factory()->subscribed()->create([
            'owner_id' => $owner->id,
            'settings' => ['inactivity_reminder_sent_at' => now()->subDays(5)->toISOString()],
        ]);
        $tenant->users()->attach($owner->id, ['role' => 'owner']);

        $this->artisan('saas:retention-alerts')->assertSuccessful();

        Notification::assertNotSentTo($owner, InactivityReminderNotification::class);
    }

    public function test_low_health_alert_sent_to_super_admins(): void
    {
        Notification::fake();

        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        Tenant::factory()->create([
            'is_active' => true,
            'health_score' => 30,
        ]);

        $this->artisan('saas:retention-alerts')->assertSuccessful();

        Notification::assertSentTo($superAdmin, LowHealthScoreAlert::class);
    }

    public function test_low_health_alert_not_sent_when_all_healthy(): void
    {
        Notification::fake();

        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        Tenant::factory()->create([
            'is_active' => true,
            'health_score' => 80,
        ]);

        $this->artisan('saas:retention-alerts')->assertSuccessful();

        Notification::assertNotSentTo($superAdmin, LowHealthScoreAlert::class);
    }
}
