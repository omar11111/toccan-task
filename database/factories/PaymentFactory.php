<?php
// database/factories/PaymentFactory.php
namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_method' => 'credit_card',
            'status' => PaymentStatus::Pending,
            'amount' => $this->faker->randomFloat(2, 100, 5000),
            'idempotency_key' => \Illuminate\Support\Str::uuid7()->toString(),
        ];
    }

    public function successful(): static
    {
        return $this->state(['status' => PaymentStatus::Successful]);
    }
}
