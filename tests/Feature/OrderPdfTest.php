<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class OrderPdfTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $this->tenant = Tenant::factory()->create(['owner_id' => $user->id]);
        $this->tenant->users()->attach($user->id, ['role' => 'owner']);
    }

    public function test_signed_url_returns_pdf(): void
    {
        $order = Order::factory()->create(['tenant_id' => $this->tenant->id]);

        $url = URL::temporarySignedRoute('orders.pdf', now()->addHour(), ['order' => $order->id]);

        $response = $this->get($url);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_unsigned_url_returns_403(): void
    {
        $order = Order::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->get(route('orders.pdf', ['order' => $order->id]));

        $response->assertForbidden();
    }

    public function test_cancelled_order_returns_404(): void
    {
        $order = Order::factory()->cancelled()->create(['tenant_id' => $this->tenant->id]);

        $url = URL::temporarySignedRoute('orders.pdf', now()->addHour(), ['order' => $order->id]);

        $response = $this->get($url);

        $response->assertNotFound();
    }

    public function test_nonexistent_order_returns_404(): void
    {
        $url = URL::temporarySignedRoute('orders.pdf', now()->addHour(), ['order' => 99999]);

        $response = $this->get($url);

        $response->assertNotFound();
    }
}
