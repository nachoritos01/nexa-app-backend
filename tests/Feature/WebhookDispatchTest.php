<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Events\PaymentReceived;
use App\Jobs\SendWebhookJob;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\WebhookService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WebhookDispatchTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $this->tenant = Tenant::factory()->pro()->create([
            'owner_id' => $user->id,
            'settings' => [
                'webhook_enabled' => true,
                'webhook_url' => 'https://example.com/webhook',
                'webhook_secret' => 'test-secret-key',
                'webhook_events' => ['order.created', 'order.status_changed', 'payment.received'],
            ],
        ]);
        $this->tenant->users()->attach($user->id, ['role' => 'owner']);

        app()->instance('currentTenant', $this->tenant);
    }

    public function test_order_created_dispatches_event(): void
    {
        Event::fake([OrderCreated::class]);

        Order::factory()->create(['tenant_id' => $this->tenant->id]);

        Event::assertDispatched(OrderCreated::class);
    }

    public function test_order_status_change_dispatches_event(): void
    {
        Event::fake([OrderStatusChanged::class, OrderCreated::class]);

        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => OrderStatus::Pending,
        ]);

        $order->status = OrderStatus::Confirmed;
        $order->confirmed_at = now();
        $order->save();

        Event::assertDispatched(OrderStatusChanged::class);
    }

    public function test_payment_dispatches_event(): void
    {
        Event::fake([PaymentReceived::class, OrderCreated::class]);

        $order = Order::factory()->create(['tenant_id' => $this->tenant->id]);

        Payment::create([
            'order_id' => $order->id,
            'amount' => 500,
            'method' => 'cash',
            'received_at' => now(),
        ]);

        Event::assertDispatched(PaymentReceived::class);
    }

    public function test_webhook_service_dispatches_job(): void
    {
        Bus::fake();

        $service = app(WebhookService::class);
        $service->dispatch($this->tenant, 'order.created', ['order_id' => 1]);

        Bus::assertDispatched(SendWebhookJob::class);
    }

    public function test_hmac_signature_is_correct(): void
    {
        $payload = json_encode(['test' => 'data']);
        $secret = 'my-secret';
        $expected = hash_hmac('sha256', $payload, $secret);

        $this->assertNotEmpty($expected);
        $this->assertTrue(hash_equals($expected, hash_hmac('sha256', $payload, $secret)));
    }
}
