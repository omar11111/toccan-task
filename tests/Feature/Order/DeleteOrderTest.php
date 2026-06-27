<?php

namespace Tests\Feature\Order;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_delete_order_without_payments(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();

        $response = $this->actingAs($user, 'api')->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_user_cannot_delete_order_with_payments(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->confirmed()->create();
        Payment::factory()->for($order)->successful()->create();

        $response = $this->actingAs($user, 'api')->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(409);
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_user_cannot_delete_another_users_order(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = Order::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser, 'api')->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(403);
    }
}
