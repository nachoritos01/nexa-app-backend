<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTenant;
use Tests\TestCase;

class CustomerAuthControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenant();
    }

    public function test_login_page_loads(): void
    {
        $response = $this->get(route('customer.login'));

        $response->assertOk();
        $response->assertViewIs('customer.login');
    }

    public function test_login_with_valid_credentials(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'phone' => '5559999999',
            'password' => 'secret123',
        ]);

        $response = $this->post(route('customer.login.submit'), [
            'phone' => '5559999999',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('customer.orders'));
        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_login_with_invalid_credentials(): void
    {
        Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'phone' => '5559999999',
            'password' => 'secret123',
        ]);

        $response = $this->post(route('customer.login.submit'), [
            'phone' => '5559999999',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('phone');
        $this->assertGuest('customer');
    }

    public function test_login_with_nonexistent_phone(): void
    {
        $response = $this->post(route('customer.login.submit'), [
            'phone' => '0000000000',
            'password' => 'anything',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('phone');
        $this->assertGuest('customer');
    }

    public function test_login_requires_phone_and_password(): void
    {
        $response = $this->post(route('customer.login.submit'), []);

        $response->assertSessionHasErrors(['phone', 'password']);
    }

    public function test_logout(): void
    {
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($customer, 'customer')
            ->post(route('customer.logout'));

        $response->assertRedirect(route('home'));
        $this->assertGuest('customer');
    }
}
