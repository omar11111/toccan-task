<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * @param array{
     *     customer_name: string,
     *     customer_email: string,
     *     items: array<int, array{product_name: string, quantity: int, price: float}>
     * } $data
     */
    public function create(string $userId, array $data): Order
    {
        return DB::transaction(function () use ($userId, $data) {
            $order = Order::create([
                'user_id' => $userId,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'total' => 0, // هتُحسب بعد إضافة الـ items
            ]);

            foreach ($data['items'] as $item) {
                $order->items()->create([
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            $order->recalculateTotal();

            return $order->fresh('items');
        });
    }

    /**
     * @param array{
     *     customer_name?: string,
     *     customer_email?: string,
     *     items?: array<int, array{product_name: string, quantity: int, price: float}>
     * } $data
     */
    public function update(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $order->update(array_filter([
                'customer_name' => $data['customer_name'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
            ], fn ($value) => $value !== null));

            if (isset($data['items'])) {
                // استبدال كامل للـ items - أبسط وأوضح من محاولة diff/merge جزئي
                $order->items()->delete();

                foreach ($data['items'] as $item) {
                    $order->items()->create([
                        'product_name' => $item['product_name'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                    ]);
                }

                $order->recalculateTotal();
            }

            return $order->fresh('items');
        });
    }

    public function delete(Order $order): void
    {
        $order->assertCanBeDeleted();

        $order->delete();
    }

    public function confirm(Order $order): Order
    {
        $order->confirm();

        return $order->fresh();
    }

    public function cancel(Order $order): Order
    {
        $order->cancel();

        return $order->fresh();
    }
}
