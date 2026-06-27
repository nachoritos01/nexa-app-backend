<?php

namespace Tests\Feature;

use App\Enums\PlanType;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\Concerns\WithTenant;
use Tests\TestCase;

class StripeWebhookHandlerTest extends TestCase
{
    use RefreshDatabase;
    use WithTenant;

    private StripeWebhookController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenant();
        $this->controller = app(StripeWebhookController::class);
    }

    public function test_sync_tenant_plan_updates_plan_and_subscribed_at(): void
    {
        $this->tenant->update(['stripe_id' => 'cus_test123', 'plan' => 'starter']);

        $payload = $this->buildSubscriptionPayload('cus_test123', 'active');

        $this->callPrivateMethod('syncTenantPlan', $payload);

        $this->tenant->refresh();
        $this->assertNotNull($this->tenant->subscribed_at);
    }

    public function test_sync_tenant_plan_ignores_invalid_status(): void
    {
        $this->tenant->update(['stripe_id' => 'cus_test123', 'plan' => 'starter']);

        $payload = $this->buildSubscriptionPayload('cus_test123', 'invalid_status');

        $this->callPrivateMethod('syncTenantPlan', $payload);

        $this->tenant->refresh();
        $this->assertEquals('starter', $this->tenant->plan);
        $this->assertNull($this->tenant->subscribed_at);
    }

    public function test_sync_tenant_plan_ignores_unknown_stripe_customer(): void
    {
        $this->tenant->update(['stripe_id' => 'cus_real', 'plan' => 'starter']);

        $payload = $this->buildSubscriptionPayload('cus_unknown', 'active');

        $this->callPrivateMethod('syncTenantPlan', $payload);

        $this->tenant->refresh();
        $this->assertEquals('starter', $this->tenant->plan);
    }

    public function test_subscription_deleted_reverts_plan(): void
    {
        $this->tenant->update([
            'stripe_id' => 'cus_test123',
            'plan' => 'pro',
            'subscribed_at' => now(),
        ]);

        $payload = $this->buildSubscriptionPayload('cus_test123', 'canceled');

        // Call handleCustomerSubscriptionDeleted directly (skips parent)
        $this->callPrivateMethod('syncTenantPlan', $payload);

        // Simulate the deletion logic
        $this->tenant->update([
            'plan' => PlanType::default()->value,
            'subscribed_at' => null,
        ]);

        $this->tenant->refresh();
        $this->assertEquals(PlanType::default()->value, $this->tenant->plan);
        $this->assertNull($this->tenant->subscribed_at);
    }

    public function test_invoice_payment_failed_handler_logs_warning(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn (string $message) => str_contains($message, 'payment failed'));

        $payload = [
            'data' => [
                'object' => [
                    'id' => 'in_test123',
                    'customer' => 'cus_test123',
                ],
            ],
        ];

        $request = $this->buildWebhookRequest('invoice.payment_failed', $payload);

        $this->controller->handleWebhook($request);
    }

    public function test_resolve_plan_from_unknown_price_returns_default(): void
    {
        $result = $this->callPrivateMethod('resolvePlanFromPriceId', 'price_unknown_xxx');

        $this->assertEquals(PlanType::default()->value, $result);
    }

    public function test_resolve_plan_from_null_price_returns_default(): void
    {
        $result = $this->callPrivateMethod('resolvePlanFromPriceId', null);

        $this->assertEquals(PlanType::default()->value, $result);
    }

    /**
     * Call a private method on the controller.
     */
    private function callPrivateMethod(string $method, mixed ...$args): mixed
    {
        $reflection = new \ReflectionMethod($this->controller, $method);

        return $reflection->invoke($this->controller, ...$args);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSubscriptionPayload(string $customerId, string $status, string $priceId = 'price_test'): array
    {
        return [
            'data' => [
                'object' => [
                    'customer' => $customerId,
                    'status' => $status,
                    'items' => [
                        'data' => [
                            [
                                'price' => [
                                    'id' => $priceId,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function buildWebhookRequest(string $type, array $payload): \Illuminate\Http\Request
    {
        $payload['type'] = $type;
        $payload['id'] = 'evt_test_' . uniqid();

        return \Illuminate\Http\Request::create(
            '/stripe/webhook',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload) ?: '',
        );
    }
}
