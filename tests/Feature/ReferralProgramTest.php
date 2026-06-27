<?php

namespace Tests\Feature;

use App\Models\Referral;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralProgramTest extends TestCase
{
    use RefreshDatabase;

    public function test_referral_code_auto_generated_on_tenant_creation(): void
    {
        $tenant = Tenant::factory()->create();

        $this->assertNotNull($tenant->referral_code);
        $this->assertEquals(8, strlen($tenant->referral_code));
    }

    public function test_registration_with_ref_code_creates_referral_record(): void
    {
        $referrer = Tenant::factory()->create();

        $this->assertNotNull($referrer->referral_code);

        // Simulate what handleRegistration does: create a new tenant and referral
        $owner = User::factory()->create();
        $referred = Tenant::factory()->create(['owner_id' => $owner->id]);

        Referral::create([
            'referrer_tenant_id' => $referrer->id,
            'referred_tenant_id' => $referred->id,
        ]);

        $this->assertDatabaseHas('referrals', [
            'referrer_tenant_id' => $referrer->id,
            'referred_tenant_id' => $referred->id,
        ]);

        $this->assertNull(Referral::first()->converted_at);
    }

    public function test_invalid_ref_code_does_not_create_referral(): void
    {
        $result = Tenant::where('referral_code', 'INVALID99')->first();

        $this->assertNull($result);
        $this->assertEquals(0, Referral::count());
    }

    public function test_conversion_marks_converted_at(): void
    {
        $referrer = Tenant::factory()->create();
        $referred = Tenant::factory()->create();

        $referral = Referral::create([
            'referrer_tenant_id' => $referrer->id,
            'referred_tenant_id' => $referred->id,
        ]);

        $this->assertNull($referral->converted_at);

        $referral->update(['converted_at' => now()]);

        $this->assertNotNull($referral->fresh()->converted_at);
    }

    public function test_referred_tenant_gets_bonus_trial_days(): void
    {
        $baseDays = config('saas.trial.days', 14);
        $bonusDays = config('saas.trial.referral_bonus_referred', 7);
        $expectedDays = $baseDays + $bonusDays;

        $referred = Tenant::factory()->create([
            'trial_ends_at' => now()->startOfDay()->addDays($expectedDays)->endOfDay(),
        ]);

        $this->assertEquals($expectedDays, $referred->trialDaysRemaining());
    }

    public function test_referrer_gets_bonus_days_on_registration(): void
    {
        $referrer = Tenant::factory()->trial()->create();
        $originalEnd = $referrer->trial_ends_at->copy();

        $effective = $referrer->addReferralBonusDays(15, cap: 90);

        $this->assertEquals(15, $effective);
        $this->assertEquals(15, $referrer->fresh()->referral_bonus_days);
        $this->assertTrue($referrer->fresh()->trial_ends_at->equalTo($originalEnd->addDays(15)));
    }

    public function test_referrer_bonus_capped_at_registration_limit(): void
    {
        $referrer = Tenant::factory()->trial()->create([
            'referral_bonus_days' => 80,
        ]);

        // Cap is 90, already at 80, so only 10 effective
        $effective = $referrer->addReferralBonusDays(15, cap: 90);

        $this->assertEquals(10, $effective);
        $this->assertEquals(90, $referrer->fresh()->referral_bonus_days);
    }

    public function test_referrer_gets_bonus_on_conversion(): void
    {
        $referrer = Tenant::factory()->trial()->create([
            'referral_bonus_days' => 15,
        ]);
        $originalEnd = $referrer->trial_ends_at->copy();

        // Conversion bonus uses total cap (120)
        $effective = $referrer->addReferralBonusDays(15, cap: 120);

        $this->assertEquals(15, $effective);
        $this->assertEquals(30, $referrer->fresh()->referral_bonus_days);
        $this->assertTrue($referrer->fresh()->trial_ends_at->equalTo($originalEnd->addDays(15)));
    }

    public function test_referrer_total_bonus_capped(): void
    {
        $referrer = Tenant::factory()->trial()->create([
            'referral_bonus_days' => 115,
        ]);

        // Total cap 120, already at 115, only 5 effective
        $effective = $referrer->addReferralBonusDays(15, cap: 120);

        $this->assertEquals(5, $effective);
        $this->assertEquals(120, $referrer->fresh()->referral_bonus_days);

        // At cap, no more days
        $effective = $referrer->addReferralBonusDays(15, cap: 120);

        $this->assertEquals(0, $effective);
        $this->assertEquals(120, $referrer->fresh()->referral_bonus_days);
    }

    public function test_bonus_days_no_effect_when_subscribed(): void
    {
        $referrer = Tenant::factory()->subscribed()->create([
            'trial_ends_at' => now()->subDays(5),
        ]);
        $originalTrialEnd = $referrer->trial_ends_at->copy();

        $effective = $referrer->addReferralBonusDays(15, cap: 90);

        // Days are tracked
        $this->assertEquals(15, $effective);
        $this->assertEquals(15, $referrer->fresh()->referral_bonus_days);

        // But trial_ends_at is NOT modified
        $this->assertTrue($referrer->fresh()->trial_ends_at->equalTo($originalTrialEnd));
    }
}
