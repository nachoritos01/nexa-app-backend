<?php

namespace App\Observers;

use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Jobs\SendPushNotification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    public function created(Order $order): void
    {
        OrderCreated::dispatch($order);
    }

    public function updating(Order $order): void
    {
        if (! $order->isDirty('status')) {
            return;
        }

        $oldStatus = $order->getOriginal('status');
        $newStatus = $order->status;

        $statusValue = $newStatus instanceof \App\Enums\OrderStatus
            ? $newStatus->value
            : (string) $newStatus;

        OrderStatusChanged::dispatch(
            $order,
            $oldStatus instanceof \App\Enums\OrderStatus ? $oldStatus->value : (string) $oldStatus,
            $statusValue,
        );

        $this->sendPushNotifications($order, $statusValue);
    }

    private function sendPushNotifications(Order $order, string $statusValue): void
    {
        /** @var int|null $tenantId */
        $tenantId = $order->getAttribute('tenant_id');

        if (! $tenantId) {
            return;
        }

        $tokens = User::whereHas('tenants', fn ($q) => $q->where('tenants.id', $tenantId))
            ->whereNotNull('push_token')
            ->pluck('push_token')
            ->all();

        if (empty($tokens)) {
            return;
        }

        $statusLabel = $order->status instanceof \App\Enums\OrderStatus
            ? $order->status->label()
            : $statusValue;

        SendPushNotification::dispatch(
            $tokens,
            "Order #{$order->id}",
            "Status updated: {$statusLabel}",
            [
                'type' => 'order_status_changed',
                'order_id' => $order->id,
                'status' => $statusValue,
            ],
        );

        Log::info('OrderObserver: Push notification dispatched', [
            'order_id' => $order->id,
            'status' => $statusValue,
            'tokens_count' => count($tokens),
        ]);
    }
}
