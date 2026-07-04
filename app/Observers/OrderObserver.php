<?php

namespace App\Observers;

use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Models\Order;

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
    }
}
