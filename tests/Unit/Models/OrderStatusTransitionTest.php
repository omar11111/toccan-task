<?php

namespace Tests\Unit\Models;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderStatusTransitionException;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_can_transition_to_confirmed(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Pending]);

        $order->confirm();

        $this->assertEquals(OrderStatus::Confirmed, $order->status);
    }

    public function test_paid_cannot_transition_to_cancelled(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Paid]);

        $this->expectException(InvalidOrderStatusTransitionException::class);

        $order->cancel();
    }

    public function test_paid_cannot_transition_to_confirmed(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Paid]);

        $this->expectException(InvalidOrderStatusTransitionException::class);

        $order->confirm();
    }

    public function test_cancelled_is_a_final_state(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Cancelled]);

        $this->assertEmpty(OrderStatus::Cancelled->allowedTransitions());
    }

    public function test_confirmed_can_transition_to_paid(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Confirmed]);

        $order->markAsPaid();

        $this->assertEquals(OrderStatus::Paid, $order->status);
    }
}
