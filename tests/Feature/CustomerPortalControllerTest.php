<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\LoyaltyReward;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTenant;
use Tests\TestCase;

class CustomerPortalControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithTenant;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenant();
        $this->customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    // --- Authentication ---

    public function test_orders_requires_authentication(): void
    {
        $response = $this->get(route('customer.orders'));

        $response->assertRedirect();
    }

    public function test_loyalty_requires_authentication(): void
    {
        $response = $this->get(route('customer.loyalty'));

        $response->assertRedirect();
    }

    // --- Orders ---

    public function test_orders_page_loads(): void
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.orders'));

        $response->assertOk();
        $response->assertViewIs('customer.portal');
        $response->assertViewHas('tab', 'orders');
    }

    public function test_orders_page_shows_customer_orders(): void
    {
        Order::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.orders'));

        $response->assertOk();
        $response->assertViewHas('orders');
    }

    // --- Order Detail ---

    public function test_order_detail_shows_own_order(): void
    {
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.order.detail', $order));

        $response->assertOk();
        $response->assertViewHas('order');
    }

    public function test_order_detail_blocks_other_customers_order(): void
    {
        $otherCustomer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $otherCustomer->id,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.order.detail', $order));

        $response->assertForbidden();
    }

    // --- Profile ---

    public function test_profile_page_loads(): void
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.profile'));

        $response->assertOk();
        $response->assertViewHas('tab', 'profile');
    }

    public function test_update_profile(): void
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->put(route('customer.profile.update'), [
                'name' => 'New Name',
                'email' => 'new@example.com',
            ]);

        $response->assertRedirect();
        $this->customer->refresh();
        $this->assertEquals('New Name', $this->customer->name);
        $this->assertEquals('new@example.com', $this->customer->email);
    }

    // --- Payments ---

    public function test_payments_page_loads(): void
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.payments'));

        $response->assertOk();
        $response->assertViewHas('tab', 'payments');
        $response->assertViewHas('totalPaid');
    }

    // --- Loyalty ---

    public function test_loyalty_page_loads(): void
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.loyalty'));

        $response->assertOk();
        $response->assertViewHas('tab', 'loyalty');
        $response->assertViewHas('transactions');
        $response->assertViewHas('availableRewards');
    }

    public function test_redeem_reward_success(): void
    {
        $customer = Customer::factory()->withPoints(500)->create(['tenant_id' => $this->tenant->id]);
        $reward = LoyaltyReward::factory()->costing(200)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($customer, 'customer')
            ->post(route('customer.loyalty.redeem', $reward));

        $response->assertRedirect(route('customer.loyalty'));
        $response->assertSessionHas('success');

        $customer->refresh();
        $this->assertEquals(300, $customer->loyalty_points);

        $this->assertDatabaseHas('loyalty_coupons', [
            'customer_id' => $customer->id,
            'loyalty_reward_id' => $reward->id,
        ]);
    }

    public function test_redeem_reward_insufficient_points(): void
    {
        $customer = Customer::factory()->withPoints(50)->create(['tenant_id' => $this->tenant->id]);
        $reward = LoyaltyReward::factory()->costing(200)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($customer, 'customer')
            ->post(route('customer.loyalty.redeem', $reward));

        $response->assertRedirect();
        $response->assertSessionHasErrors('reward');

        $customer->refresh();
        $this->assertEquals(50, $customer->loyalty_points);
    }

    public function test_redeem_reward_tier_too_low(): void
    {
        $customer = Customer::factory()->withPoints(500)->create(['tenant_id' => $this->tenant->id]);
        $reward = LoyaltyReward::factory()->costing(100)->forTier(2)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($customer, 'customer')
            ->post(route('customer.loyalty.redeem', $reward));

        $response->assertRedirect();
        $response->assertSessionHasErrors('reward');
    }

    // --- Settings / Delete Account ---

    public function test_settings_page_loads(): void
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.settings'));

        $response->assertOk();
        $response->assertViewHas('tab', 'settings');
    }

    public function test_delete_account_anonymizes_customer(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'password' => 'password',
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->delete(route('customer.account.delete'), [
                'password' => 'password',
            ]);

        $response->assertRedirect(route('home'));

        $customer->refresh();
        $this->assertEquals('Cliente eliminado', $customer->name);
        $this->assertStringStartsWith('deleted-', $customer->phone);
        $this->assertNull($customer->email);
        $this->assertNull($customer->password);
    }

    public function test_delete_account_wrong_password(): void
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->delete(route('customer.account.delete'), [
                'password' => 'wrong-password',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('password');

        $this->customer->refresh();
        $this->assertNotEquals('Cliente eliminado', $this->customer->name);
    }
}
