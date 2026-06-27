<?php

namespace Tests\Feature\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_confirm_pending_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['status' => OrderStatus::Pending]);

        $response = $this->actingAs($user, 'api')->patchJson("/api/orders/{$order->id}/confirm");

        $response->assertStatus(200)->assertJsonPath('data.status', 'confirmed');
    }

    public function test_user_can_cancel_pending_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['status' => OrderStatus::Pending]);

        $response = $this->actingAs($user, 'api')->patchJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(200)->assertJsonPath('data.status', 'cancelled');
    }

    public function test_cannot_cancel_a_paid_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['status' => OrderStatus::Paid]);

        $response = $this->actingAs($user, 'api')->patchJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(409)
            ->assertJsonFragment(['message' => "Cannot transition order from [paid] to [cancelled]."]);
    }

    public function test_cannot_confirm_an_already_confirmed_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['status' => OrderStatus::Confirmed]);

        $response = $this->actingAs($user, 'api')->patchJson("/api/orders/{$order->id}/confirm");

        $response->assertStatus(409);
    }
}
