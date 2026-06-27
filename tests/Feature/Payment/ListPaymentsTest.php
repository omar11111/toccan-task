<?php

namespace Tests\Feature\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListPaymentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_payments_for_specific_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();
        Payment::factory()->for($order)->count(2)->create();

        $otherOrder = Order::factory()->for($user)->create();
        Payment::factory()->for($otherOrder)->create();

        $response = $this->actingAs($user, 'api')->getJson("/api/orders/{$order->id}/payments");

        $response->assertStatus(200)->assertJsonCount(2, 'data');
    }

    public function test_user_can_list_all_their_payments_across_orders(): void
    {
        $user = User::factory()->create();
        $orderA = Order::factory()->for($user)->create();
        $orderB = Order::factory()->for($user)->create();
        Payment::factory()->for($orderA)->create();
        Payment::factory()->for($orderB)->create();

        $response = $this->actingAs($user, 'api')->getJson('/api/payments');

        $response->assertStatus(200)->assertJsonCount(2, 'data');
    }

    public function test_user_cannot_list_payments_for_another_users_order(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = Order::factory()->for($owner)->create();
        Payment::factory()->for($order)->create();

        $response = $this->actingAs($otherUser, 'api')->getJson("/api/orders/{$order->id}/payments");

        $response->assertStatus(403);
    }

    public function test_users_all_payments_list_does_not_leak_other_users_payments(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        Payment::factory()->for(Order::factory()->for($userA))->create();
        Payment::factory()->for(Order::factory()->for($userB))->count(3)->create();

        $response = $this->actingAs($userA, 'api')->getJson('/api/payments');

        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }
}
