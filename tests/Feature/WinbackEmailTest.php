<?php

namespace Tests\Feature;

use App\Models\CancellationSurvey;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\WinbackNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WinbackEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_winback_email_sent_7d_after_cancellation(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create([
            'owner_id' => $owner->id,
            'subscribed_at' => null,
        ]);

        $survey = CancellationSurvey::create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'reason' => 'price',
        ]);
        $survey->created_at = now()->subDays(7);
        $survey->saveQuietly();

        $this->artisan('saas:winback-emails')->assertSuccessful();

        Notification::assertSentTo($owner, WinbackNotification::class);
    }

    public function test_winback_email_not_sent_before_7d(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create([
            'owner_id' => $owner->id,
            'subscribed_at' => null,
        ]);

        $survey = CancellationSurvey::create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'reason' => 'price',
        ]);
        $survey->created_at = now()->subDays(3);
        $survey->saveQuietly();

        $this->artisan('saas:winback-emails')->assertSuccessful();

        Notification::assertNotSentTo($owner, WinbackNotification::class);
    }

    public function test_winback_email_not_sent_if_resubscribed(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $tenant = Tenant::factory()->subscribed()->create([
            'owner_id' => $owner->id,
        ]);

        $survey = CancellationSurvey::create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'reason' => 'price',
        ]);
        $survey->created_at = now()->subDays(7);
        $survey->saveQuietly();

        $this->artisan('saas:winback-emails')->assertSuccessful();

        Notification::assertNotSentTo($owner, WinbackNotification::class);
    }

    public function test_winback_email_not_sent_twice(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create([
            'owner_id' => $owner->id,
            'subscribed_at' => null,
            'settings' => ['winback_email_sent_at' => now()->subDays(1)->toISOString()],
        ]);

        $survey = CancellationSurvey::create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'reason' => 'price',
        ]);
        $survey->created_at = now()->subDays(7);
        $survey->saveQuietly();

        $this->artisan('saas:winback-emails')->assertSuccessful();

        Notification::assertNotSentTo($owner, WinbackNotification::class);
    }
}
