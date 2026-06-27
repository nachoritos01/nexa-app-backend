<?php

namespace Tests\Feature;

use App\Models\FeatureUsage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_track_creates_record_with_tenant(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['owner_id' => $user->id]);

        app()->instance('currentTenant', $tenant);
        $this->actingAs($user);

        FeatureUsage::track('order_created');

        $this->assertDatabaseHas('feature_usages', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'feature' => 'order_created',
        ]);
    }

    public function test_track_noop_without_tenant(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        FeatureUsage::track('order_created');

        $this->assertDatabaseCount('feature_usages', 0);
    }

    public function test_login_event_updates_last_login_at(): void
    {
        $user = User::factory()->create(['last_login_at' => null]);

        event(new Login('web', $user, false));

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
    }
}
