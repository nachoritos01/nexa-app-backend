<?php

namespace Tests\Feature;

use App\Models\FeatureUsage;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthScoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_score_calculated_for_active_tenants(): void
    {
        $tenant = Tenant::factory()->create(['is_active' => true]);

        $this->artisan('saas:health-score')->assertSuccessful();

        $tenant->refresh();
        $this->assertNotNull($tenant->health_score);
        $this->assertNotNull($tenant->health_score_calculated_at);
    }

    public function test_health_score_zero_for_inactive_tenant(): void
    {
        $tenant = Tenant::factory()->create([
            'is_active' => true,
        ]);

        $this->artisan('saas:health-score')->assertSuccessful();

        $tenant->refresh();
        $this->assertEquals(0, $tenant->health_score);
    }

    public function test_health_score_high_for_active_tenant(): void
    {
        $owner = User::factory()->create(['last_login_at' => now()]);
        $tenant = Tenant::factory()->create([
            'is_active' => true,
            'owner_id' => $owner->id,
        ]);
        $tenant->users()->attach($owner->id, ['role' => 'owner']);

        // Create recent order
        Order::factory()->create([
            'tenant_id' => $tenant->id,
            'created_at' => now(),
        ]);

        // Track all 6 features
        foreach (config('saas.retention.tracked_features') as $feature) {
            FeatureUsage::create([
                'tenant_id' => $tenant->id,
                'user_id' => $owner->id,
                'feature' => $feature,
                'used_at' => now(),
            ]);
        }

        $this->artisan('saas:health-score')->assertSuccessful();

        $tenant->refresh();
        $this->assertGreaterThanOrEqual(90, $tenant->health_score);
    }

    public function test_suspended_tenants_not_calculated(): void
    {
        $tenant = Tenant::factory()->create(['is_active' => false]);

        $this->artisan('saas:health-score')->assertSuccessful();

        $tenant->refresh();
        $this->assertNull($tenant->health_score);
    }

    public function test_login_component_zero_without_logins(): void
    {
        $owner = User::factory()->create(['last_login_at' => null]);
        $tenant = Tenant::factory()->create([
            'is_active' => true,
            'owner_id' => $owner->id,
        ]);
        $tenant->users()->attach($owner->id, ['role' => 'owner']);

        $this->artisan('saas:health-score')->assertSuccessful();

        $tenant->refresh();
        // With no logins, login component (30%) and user activity (20%) are 0
        $this->assertLessThanOrEqual(30, $tenant->health_score);
    }
}
