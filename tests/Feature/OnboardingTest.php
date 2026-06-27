<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create();
        $this->tenant = Tenant::factory()->trial()->create([
            'owner_id' => $this->user->id,
        ]);
        $this->tenant->users()->attach($this->user->id, ['role' => 'owner']);
        $this->user->syncRoles(['owner']);

        app()->instance('currentTenant', $this->tenant);
        session(['tenant_id' => $this->tenant->id]);

        // Seed data for onboarding (no-op in template, override TenantSeedService for custom data)
        app(\App\Services\TenantSeedService::class)->seedForTenant($this->tenant);
    }

    public function test_onboarding_page_accessible_when_not_completed(): void
    {
        $this->actingAs($this->user)
            ->get('/admin/onboarding')
            ->assertOk();
    }

    public function test_onboarding_redirects_to_dashboard_when_completed(): void
    {
        $this->tenant->update(['onboarding_completed_at' => now()]);

        $this->actingAs($this->user)
            ->get('/admin/onboarding')
            ->assertRedirect('/admin');
    }

    public function test_mark_onboarding_step_tracks_progress(): void
    {
        $this->tenant->markOnboardingStep('first_branch');
        $this->tenant->refresh();

        $this->assertTrue($this->tenant->hasCompletedOnboardingStep('first_branch'));
        $this->assertFalse($this->tenant->hasCompletedOnboardingStep('first_order'));
        $this->assertEquals(50, $this->tenant->onboardingProgress());
    }

    public function test_completing_all_steps_marks_onboarding_done(): void
    {
        $steps = config('saas.onboarding.steps');
        foreach ($steps as $step) {
            $this->tenant->markOnboardingStep($step);
        }
        $this->tenant->update(['onboarding_completed_at' => now()]);
        $this->tenant->refresh();

        $this->assertTrue($this->tenant->isOnboardingComplete());
        $this->assertEquals(100, $this->tenant->onboardingProgress());
    }

    public function test_trial_helpers_work_correctly(): void
    {
        $this->assertTrue($this->tenant->isOnTrial());
        $this->assertFalse($this->tenant->isTrialExpired());
        $this->assertGreaterThan(0, $this->tenant->trialDaysRemaining());
        $this->assertEquals('success', $this->tenant->trialColorStatus());
    }

    public function test_trial_expired_status(): void
    {
        $this->tenant->update(['trial_ends_at' => now()->subDay()]);
        $this->tenant->refresh();

        $this->assertFalse($this->tenant->isOnTrial());
        $this->assertTrue($this->tenant->isTrialExpired());
        $this->assertEquals(0, $this->tenant->trialDaysRemaining());
        $this->assertEquals('danger', $this->tenant->trialColorStatus());
    }

    public function test_trial_grace_period(): void
    {
        $this->tenant->update(['trial_ends_at' => now()->subDay()]);
        $this->tenant->refresh();

        $this->assertTrue($this->tenant->isInGracePeriod());
    }

    public function test_trial_grace_period_expired(): void
    {
        $graceDays = config('saas.trial.grace_days', 3);
        $this->tenant->update(['trial_ends_at' => now()->subDays($graceDays + 2)]);
        $this->tenant->refresh();

        $this->assertFalse($this->tenant->isInGracePeriod());
    }

    public function test_subscribed_tenant_is_not_on_trial(): void
    {
        $this->tenant->update(['subscribed_at' => now()]);
        $this->tenant->refresh();

        $this->assertFalse($this->tenant->isOnTrial());
        $this->assertFalse($this->tenant->isTrialExpired());
    }

    public function test_generate_slug_creates_unique_slugs(): void
    {
        $slug1 = Tenant::generateSlug('Mi Imprenta');
        Tenant::factory()->create(['slug' => $slug1]);

        $slug2 = Tenant::generateSlug('Mi Imprenta');
        $this->assertNotEquals($slug1, $slug2);
        $this->assertEquals('mi-imprenta-1', $slug2);
    }
}
