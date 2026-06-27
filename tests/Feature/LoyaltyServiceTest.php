<?php

namespace Tests\Feature;

use App\Enums\LoyaltyTier;
use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\LoyaltyCoupon;
use App\Models\LoyaltyReward;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Services\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTenant;
use Tests\TestCase;

class LoyaltyServiceTest extends TestCase
{
    use RefreshDatabase;
    use WithTenant;

    private LoyaltyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenant();
        $this->service = app(LoyaltyService::class);
    }

    // --- calculatePoints ---

    public function test_calculate_points_bronze_tier(): void
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        // Default: 10 points_per_amount, Bronze multiplier = 1.0
        // 1000 / 10 = 100 base * 1.0 = 100
        $points = $this->service->calculatePoints(1000, $customer);

        $this->assertEquals(100, $points);
    }

    public function test_calculate_points_silver_tier(): void
    {
        $customer = Customer::factory()->silverTier()->create(['tenant_id' => $this->tenant->id]);

        // 1000 / 10 = 100 base * 1.5 = 150
        $points = $this->service->calculatePoints(1000, $customer);

        $this->assertEquals(150, $points);
    }

    public function test_calculate_points_gold_tier(): void
    {
        $customer = Customer::factory()->goldTier()->create(['tenant_id' => $this->tenant->id]);

        // 1000 / 10 = 100 base * 2.0 = 200
        $points = $this->service->calculatePoints(1000, $customer);

        $this->assertEquals(200, $points);
    }

    public function test_calculate_points_vip_tier(): void
    {
        $customer = Customer::factory()->vipTier()->create(['tenant_id' => $this->tenant->id]);

        // 1000 / 10 = 100 base * 3.0 = 300
        $points = $this->service->calculatePoints(1000, $customer);

        $this->assertEquals(300, $points);
    }

    public function test_calculate_points_zero_amount(): void
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        $points = $this->service->calculatePoints(0, $customer);

        $this->assertEquals(0, $points);
    }

    public function test_calculate_points_small_amount(): void
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        // 5 / 10 = 0 (integer division)
        $points = $this->service->calculatePoints(5, $customer);

        $this->assertEquals(0, $points);
    }

    // --- creditTransactionPoints ---

    public function test_credit_transaction_points_creates_transaction(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'first_purchase_bonus' => true, // Already claimed to isolate credit test
        ]);
        $order = Order::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'total' => 1000,
        ]);

        $this->service->creditTransactionPoints($order);

        $this->assertDatabaseHas('loyalty_transactions', [
            'customer_id' => $customer->id,
            'type' => 'credit',
            'points' => 100,
        ]);

        $customer->refresh();
        $this->assertEquals(100, $customer->loyalty_points);
    }

    public function test_credit_transaction_points_with_first_purchase_bonus(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'first_purchase_bonus' => false,
        ]);
        $order = Order::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'total' => 1000,
        ]);

        $this->service->creditTransactionPoints($order);

        $customer->refresh();
        // 100 points + 50 first purchase bonus = 150
        $this->assertEquals(150, $customer->loyalty_points);
        $this->assertTrue($customer->first_purchase_bonus);

        $this->assertDatabaseHas('loyalty_transactions', [
            'customer_id' => $customer->id,
            'type' => 'bonus',
            'description' => 'First purchase bonus',
        ]);
    }

    public function test_credit_transaction_points_no_double_first_purchase_bonus(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'first_purchase_bonus' => true,
        ]);
        $order = Order::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'total' => 1000,
        ]);

        $this->service->creditTransactionPoints($order);

        $customer->refresh();
        $this->assertEquals(100, $customer->loyalty_points);
        $this->assertEquals(1, LoyaltyTransaction::where('customer_id', $customer->id)->count());
    }

    public function test_credit_transaction_points_skips_order_without_customer(): void
    {
        $order = Order::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => null,
            'total' => 1000,
        ]);

        $this->service->creditTransactionPoints($order);

        $this->assertEquals(0, LoyaltyTransaction::count());
    }

    // --- reverseTransactionPoints ---

    public function test_reverse_transaction_points(): void
    {
        $customer = Customer::factory()->withPoints(100)->create(['tenant_id' => $this->tenant->id]);
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'total' => 1000,
        ]);

        // Create the original credit transaction
        LoyaltyTransaction::create([
            'customer_id' => $customer->id,
            'tenant_id' => $this->tenant->id,
            'type' => 'credit',
            'points' => 100,
            'balance_after' => 100,
            'description' => 'Order #' . $order->id . ' completed',
            'reference_type' => Order::class,
            'reference_id' => $order->id,
        ]);

        $this->service->reverseTransactionPoints($order);

        $customer->refresh();
        $this->assertEquals(0, $customer->loyalty_points);

        $this->assertDatabaseHas('loyalty_transactions', [
            'customer_id' => $customer->id,
            'type' => 'reversal',
            'points' => -100,
        ]);
    }

    public function test_reverse_transaction_points_no_original_transaction(): void
    {
        $customer = Customer::factory()->withPoints(100)->create(['tenant_id' => $this->tenant->id]);
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
        ]);

        $this->service->reverseTransactionPoints($order);

        // No reversal transaction should be created
        $this->assertEquals(0, LoyaltyTransaction::where('type', 'reversal')->count());
        $customer->refresh();
        $this->assertEquals(100, $customer->loyalty_points);
    }

    // --- redeemReward ---

    public function test_redeem_reward_creates_coupon(): void
    {
        $customer = Customer::factory()->withPoints(500)->create(['tenant_id' => $this->tenant->id]);
        $reward = LoyaltyReward::factory()->costing(200)->create(['tenant_id' => $this->tenant->id]);

        $coupon = $this->service->redeemReward($customer, $reward);

        $this->assertInstanceOf(LoyaltyCoupon::class, $coupon);
        $this->assertNotEmpty($coupon->code);
        $this->assertEquals($reward->type, $coupon->type);
        $this->assertEquals($reward->value, $coupon->value);
        $this->assertTrue($coupon->expires_at->isFuture());

        $customer->refresh();
        $this->assertEquals(300, $customer->loyalty_points);

        $this->assertDatabaseHas('loyalty_transactions', [
            'customer_id' => $customer->id,
            'type' => 'debit',
            'points' => -200,
        ]);
    }

    public function test_redeem_reward_deducts_exact_points(): void
    {
        $customer = Customer::factory()->withPoints(100)->create(['tenant_id' => $this->tenant->id]);
        $reward = LoyaltyReward::factory()->costing(100)->create(['tenant_id' => $this->tenant->id]);

        $coupon = $this->service->redeemReward($customer, $reward);

        $customer->refresh();
        $this->assertEquals(0, $customer->loyalty_points);
        $this->assertNotNull($coupon->code);
    }

    // --- Tier integration ---

    public function test_tier_multiplier_applied_correctly(): void
    {
        // Bronze (0 lifetime) => multiplier 1.0
        $this->assertEquals(1.0, LoyaltyTier::Bronze->multiplier());

        // Silver (500+ lifetime) => multiplier 1.5
        $this->assertEquals(1.5, LoyaltyTier::Silver->multiplier());

        // Gold (1500+ lifetime) => multiplier 2.0
        $this->assertEquals(2.0, LoyaltyTier::Gold->multiplier());

        // VIP (3000+ lifetime) => multiplier 3.0
        $this->assertEquals(3.0, LoyaltyTier::VIP->multiplier());
    }

    public function test_tier_from_points(): void
    {
        $this->assertEquals(LoyaltyTier::Bronze, LoyaltyTier::fromPoints(0));
        $this->assertEquals(LoyaltyTier::Bronze, LoyaltyTier::fromPoints(499));
        $this->assertEquals(LoyaltyTier::Silver, LoyaltyTier::fromPoints(500));
        $this->assertEquals(LoyaltyTier::Gold, LoyaltyTier::fromPoints(1500));
        $this->assertEquals(LoyaltyTier::VIP, LoyaltyTier::fromPoints(3000));
    }

    // --- Referral bonus ---

    public function test_credit_referral_bonus(): void
    {
        $referrer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
        $referred = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->service->creditReferralBonus($referrer, $referred);

        $referrer->refresh();
        // Default referral_bonus = 100
        $this->assertEquals(100, $referrer->loyalty_points);

        $this->assertDatabaseHas('loyalty_transactions', [
            'customer_id' => $referrer->id,
            'type' => 'bonus',
            'points' => 100,
        ]);
    }

    public function test_first_purchase_credits_referrer(): void
    {
        $referrer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
        $referred = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'referred_by' => $referrer->id,
            'first_purchase_bonus' => false,
        ]);

        $order = Order::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $referred->id,
            'total' => 1000,
        ]);

        $this->service->creditTransactionPoints($order);

        $referrer->refresh();
        // Referrer gets 100 bonus points
        $this->assertEquals(100, $referrer->loyalty_points);
    }

    public function test_referral_bonus_not_given_without_referrer(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'referred_by' => null,
            'first_purchase_bonus' => false,
        ]);

        $order = Order::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'total' => 1000,
        ]);

        $this->service->creditTransactionPoints($order);

        // Only 2 transactions: credit + first purchase bonus (no referral)
        $this->assertEquals(2, LoyaltyTransaction::where('customer_id', $customer->id)->count());
    }
}
