<?php

namespace Tests\Feature\Payment;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_replaying_same_idempotency_key_returns_same_payment(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['status' => OrderStatus::Confirmed]);

        $firstResponse = $this->actingAs($user, 'api')
            ->withHeaders(['Idempotency-Key' => 'same-key-123'])
            ->postJson("/api/orders/{$order->id}/payments", ['payment_method' => 'credit_card']);

        $firstPaymentId = $firstResponse->json('data.id');

        // الـ Order بقى paid دلوقتي، لو الـ retry كان هيعمل payment جديد كان هيترفض بـ PaymentAlreadyProcessedException
        // لكن بما إنه نفس الـ key، المفروض يرجع نفس الـ payment القديم بدون ما يدخل الـ business logic تاني
        $secondResponse = $this->actingAs($user, 'api')
            ->withHeaders(['Idempotency-Key' => 'same-key-123'])
            ->postJson("/api/orders/{$order->id}/payments", ['payment_method' => 'credit_card']);

        $secondResponse->assertStatus(201)
            ->assertJsonPath('data.id', $firstPaymentId);

        $this->assertEquals(1, Payment::where('idempotency_key', 'same-key-123')->count());
    }

    public function test_different_idempotency_keys_on_paid_order_are_rejected(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['status' => OrderStatus::Confirmed]);

        $this->actingAs($user, 'api')
            ->withHeaders(['Idempotency-Key' => 'first-key'])
            ->postJson("/api/orders/{$order->id}/payments", ['payment_method' => 'credit_card']);

        // مفتاح مختلف تمامًا، على نفس الـ order اللي بقى paid دلوقتي
        $response = $this->actingAs($user, 'api')
            ->withHeaders(['Idempotency-Key' => 'second-different-key'])
            ->postJson("/api/orders/{$order->id}/payments", ['payment_method' => 'credit_card']);

        $response->assertStatus(409);
        $this->assertEquals(1, Payment::where('order_id', $order->id)->count());
    }
}
