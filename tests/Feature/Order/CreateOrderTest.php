<?php

namespace Tests\Feature\Order;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_order_with_items(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/orders', [
            'customer_name' => 'Omar Test',
            'customer_email' => 'omar@test.com',
            'items' => [
                ['product_name' => 'Laptop', 'quantity' => 1, 'price' => 15000],
                ['product_name' => 'Mouse', 'quantity' => 2, 'price' => 250],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total', 15500)
            ->assertJsonCount(2, 'data.items');

        $this->assertDatabaseHas('orders', ['customer_email' => 'omar@test.com', 'total' => 15500]);
    }

    public function test_guest_cannot_create_order(): void
    {
        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Omar Test',
            'customer_email' => 'omar@test.com',
            'items' => [['product_name' => 'Laptop', 'quantity' => 1, 'price' => 15000]],
        ]);

        $response->assertStatus(401);
    }

    public function test_order_creation_fails_without_items(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/orders', [
            'customer_name' => 'Omar Test',
            'customer_email' => 'omar@test.com',
            'items' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_order_creation_fails_with_invalid_item_quantity(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/orders', [
            'customer_name' => 'Omar Test',
            'customer_email' => 'omar@test.com',
            'items' => [['product_name' => 'Laptop', 'quantity' => 0, 'price' => 15000]],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity']);
    }
}
