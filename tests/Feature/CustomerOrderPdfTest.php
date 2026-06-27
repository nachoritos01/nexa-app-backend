<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderPdfTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $this->tenant = Tenant::factory()->create(['owner_id' => $user->id]);
        $this->tenant->users()->attach($user->id, ['role' => 'owner']);

        app()->instance('currentTenant', $this->tenant);

        $this->customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Customer',
            'phone' => '5551234567',
            'email' => 'customer@test.com',
            'password' => 'password',
        ]);
    }

    public function test_authenticated_customer_can_download_order_pdf(): void
    {
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.order.pdf', $order));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_customer_cannot_download_other_customers_order(): void
    {
        $otherCustomer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Other Customer',
            'phone' => '5559999999',
        ]);

        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $otherCustomer->id,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.order.pdf', $order));

        $response->assertForbidden();
    }

    public function test_cancelled_order_returns_404(): void
    {
        $order = Order::factory()->cancelled()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.order.pdf', $order));

        $response->assertNotFound();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->get(route('customer.order.pdf', $order));

        $response->assertRedirect(route('customer.login'));
    }
}
