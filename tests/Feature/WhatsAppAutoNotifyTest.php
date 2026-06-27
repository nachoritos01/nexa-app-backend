<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WhatsAppAutoNotifyTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_change_dispatches_order_status_changed_event(): void
    {
        Event::fake([OrderStatusChanged::class]);

        $tenant = Tenant::factory()->create();

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_phone' => '3312345678',
            'status' => OrderStatus::Pending,
        ]);

        $order->status = OrderStatus::Confirmed;
        $order->save();

        Event::assertDispatched(OrderStatusChanged::class);
    }

    public function test_status_change_event_not_dispatched_without_status_change(): void
    {
        Event::fake([OrderStatusChanged::class]);

        $tenant = Tenant::factory()->create();

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_phone' => '3312345678',
            'status' => OrderStatus::Pending,
        ]);

        // Update a non-status field
        $order->notes = 'Updated notes';
        $order->save();

        Event::assertNotDispatched(OrderStatusChanged::class);
    }

    public function test_status_change_event_dispatched_regardless_of_phone(): void
    {
        Event::fake([OrderStatusChanged::class]);

        $tenant = Tenant::factory()->create();

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_phone' => '',
            'status' => OrderStatus::Pending,
        ]);

        $order->status = OrderStatus::Confirmed;
        $order->save();

        Event::assertDispatched(OrderStatusChanged::class);
    }

    public function test_multiple_status_transitions_dispatch_multiple_events(): void
    {
        Event::fake([OrderStatusChanged::class]);

        $tenant = Tenant::factory()->create();

        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_phone' => '3312345678',
            'status' => OrderStatus::Pending,
        ]);

        $order->status = OrderStatus::Confirmed;
        $order->save();

        $order->status = OrderStatus::InProgress;
        $order->save();

        Event::assertDispatched(OrderStatusChanged::class, 2);
    }
}
