<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Events\PaymentReceived;
use App\Models\Tenant;
use App\Services\WebhookService;
use Illuminate\Events\Dispatcher;

class WebhookEventSubscriber
{
    public function __construct(
        private WebhookService $webhookService,
    ) {
    }

    public function handleOrderCreated(OrderCreated $event): void
    {
        $tenant = $this->resolveTenant($event->order->tenant_id);

        if (! $tenant) {
            return;
        }

        $this->webhookService->dispatch($tenant, 'order.created', [
            'order_id' => $event->order->id,
            'customer_name' => $event->order->customer_name,
            'status' => $event->order->status instanceof \App\Enums\OrderStatus
                ? $event->order->status->value
                : (string) $event->order->status,
            'total' => (string) $event->order->total,
        ]);
    }

    public function handleOrderStatusChanged(OrderStatusChanged $event): void
    {
        $tenant = $this->resolveTenant($event->order->tenant_id);

        if (! $tenant) {
            return;
        }

        $this->webhookService->dispatch($tenant, 'order.status_changed', [
            'order_id' => $event->order->id,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'customer_name' => $event->order->customer_name,
        ]);
    }

    public function handlePaymentReceived(PaymentReceived $event): void
    {
        $order = $event->payment->order;

        if (! $order) {
            return;
        }

        $tenant = $this->resolveTenant($order->tenant_id);

        if (! $tenant) {
            return;
        }

        $this->webhookService->dispatch($tenant, 'payment.received', [
            'payment_id' => $event->payment->id,
            'order_id' => $order->id,
            'amount' => (string) $event->payment->amount,
            'method' => $event->payment->method instanceof \App\Enums\PaymentMethod
                ? $event->payment->method->value
                : (string) $event->payment->method,
        ]);
    }

    /** @return array<string, string> */
    public function subscribe(Dispatcher $events): array
    {
        return [
            OrderCreated::class => 'handleOrderCreated',
            OrderStatusChanged::class => 'handleOrderStatusChanged',
            PaymentReceived::class => 'handlePaymentReceived',
        ];
    }

    private function resolveTenant(?int $tenantId): ?Tenant
    {
        if (! $tenantId) {
            return null;
        }

        return Tenant::find($tenantId);
    }
}
