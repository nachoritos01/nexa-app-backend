<?php

namespace Tests\Unit\Models;

use App\Enums\OrderStatus;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTenant;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;
    use WithTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenant();
    }

    public function test_confirm_sets_status_and_timestamp(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Pending]);

        $order->confirm();

        $this->assertEquals(OrderStatus::Confirmed, $order->status);
        $this->assertNotNull($order->confirmed_at);
    }

    public function test_complete_sets_status_and_timestamp(): void
    {
        $order = Order::factory()->confirmed()->create();

        $order->complete();

        $this->assertEquals(OrderStatus::Completed, $order->status);
        $this->assertNotNull($order->completed_at);
    }

    public function test_cancel_sets_status_and_timestamp(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Pending]);

        $order->cancel();

        $this->assertEquals(OrderStatus::Cancelled, $order->status);
        $this->assertNotNull($order->cancelled_at);
    }

    public function test_scope_active_excludes_completed_and_cancelled(): void
    {
        Order::factory()->create(['status' => OrderStatus::Pending]);
        Order::factory()->confirmed()->create();
        Order::factory()->completed()->create();
        Order::factory()->cancelled()->create();

        $active = Order::active()->get();

        $this->assertCount(2, $active);
        $this->assertFalse($active->contains('status', OrderStatus::Completed));
        $this->assertFalse($active->contains('status', OrderStatus::Cancelled));
    }

    public function test_scope_by_status_filters_correctly(): void
    {
        Order::factory()->create(['status' => OrderStatus::Pending]);
        Order::factory()->confirmed()->create();
        Order::factory()->confirmed()->create();

        $this->assertCount(2, Order::byStatus('confirmed')->get());
        $this->assertCount(1, Order::byStatus('pending')->get());
    }

    public function test_recalculate_totals(): void
    {
        $order = Order::factory()->create(['subtotal' => 0, 'total_paid' => 0, 'total' => 0]);
        $item = Item::factory()->create();

        OrderLine::factory()->create([
            'order_id' => $order->id,
            'item_id' => $item->id,
            'quantity' => 5,
            'unit_price' => 200,
            'subtotal' => 1000,
        ]);
        OrderLine::factory()->create([
            'order_id' => $order->id,
            'item_id' => $item->id,
            'quantity' => 3,
            'unit_price' => 185,
            'subtotal' => 555,
        ]);

        $order->recalculateTotals();

        $this->assertEquals(1555, $order->subtotal);
        $this->assertEquals(1555, $order->total);
    }

    public function test_status_label_accessor(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Confirmed]);

        $this->assertEquals('Confirmed', $order->status_label);
    }
}
