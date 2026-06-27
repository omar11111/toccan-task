<?php

namespace Tests\Feature\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_only_sees_their_own_orders(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Order::factory()->for($userA)->count(2)->create();
        Order::factory()->for($userB)->count(3)->create();

        $response = $this->actingAs($userA, 'api')->getJson('/api/orders');

        $response->assertStatus(200)->assertJsonCount(2, 'data');
    }

    public function test_can_filter_orders_by_status(): void
    {
        $user = User::factory()->create();

        Order::factory()->for($user)->create(['status' => OrderStatus::Pending]);
        Order::factory()->for($user)->create(['status' => OrderStatus::Confirmed]);
        Order::factory()->for($user)->create(['status' => OrderStatus::Confirmed]);

        $response = $this->actingAs($user, 'api')->getJson('/api/orders?status=confirmed');

        $response->assertStatus(200)->assertJsonCount(2, 'data');
    }

    public function test_orders_list_is_paginated(): void
    {
        $user = User::factory()->create();
        Order::factory()->for($user)->count(20)->create();

        $response = $this->actingAs($user, 'api')->getJson('/api/orders?per_page=5');

        $response->assertStatus(200)->assertJsonCount(5, 'data');
    }
}
