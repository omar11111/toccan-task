<?php

namespace Tests\Feature\Order;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_customer_details_without_touching_items(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['customer_name' => 'Old Name']);
        OrderItem::factory()->for($order)->create(['quantity' => 1, 'price' => 100]);

        $response = $this->actingAs($user, 'api')->putJson("/api/orders/{$order->id}", [
            'customer_name' => 'New Name',
        ]);

        $response->assertStatus(200)->assertJsonPath('data.customer_name', 'New Name');
        $this->assertEquals(1, $order->items()->count());
    }

    public function test_updating_items_replaces_old_items_completely(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();
        $oldItem = OrderItem::factory()->for($order)->create(['product_name' => 'Old Product']);

        $response = $this->actingAs($user, 'api')->putJson("/api/orders/{$order->id}", [
            'items' => [
                ['product_name' => 'New Product', 'quantity' => 2, 'price' => 50],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.total', 100);

        $this->assertDatabaseMissing('order_items', ['id' => $oldItem->id]);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'product_name' => 'New Product']);
    }

    public function test_total_is_recalculated_after_items_update(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['total' => 999]);

        $response = $this->actingAs($user, 'api')->putJson("/api/orders/{$order->id}", [
            'items' => [
                ['product_name' => 'Item A', 'quantity' => 3, 'price' => 20],
                ['product_name' => 'Item B', 'quantity' => 1, 'price' => 40],
            ],
        ]);

        $response->assertStatus(200)->assertJsonPath('data.total', 100); // (3*20)+(1*40)
    }

    public function test_user_cannot_update_another_users_order(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = Order::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser, 'api')->putJson("/api/orders/{$order->id}", [
            'customer_name' => 'Hacked Name',
        ]);

        $response->assertStatus(403);
    }
}
