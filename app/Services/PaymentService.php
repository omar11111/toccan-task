<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\OrderNotConfirmedException;
use App\Exceptions\PaymentAlreadyProcessedException;
use App\Models\Order;
use App\Models\Payment;
use App\PaymentGateways\PaymentGatewayResolver;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayResolver $resolver,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function process(Order $order, string $paymentMethod, string $idempotencyKey, array $data): Payment
    {
        // 1. Idempotency check - قبل أي transaction
        $existing = Payment::where('idempotency_key', $idempotencyKey)->first();

        if ($existing) {
            return $existing;
        }

        // 2. Resolve الـ Gateway قبل الـ transaction - ده مش محتاج lock أصلًا
        $gateway = $this->resolver->resolve($paymentMethod);

        return DB::transaction(function () use ($order, $paymentMethod, $idempotencyKey, $data, $gateway) {
            // 3. Lock على الـ Order
            $lockedOrder = Order::lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->status === OrderStatus::Paid) {
                throw new PaymentAlreadyProcessedException;
            }

            if ($lockedOrder->status !== OrderStatus::Confirmed) {
                throw new OrderNotConfirmedException($lockedOrder);
            }

            // 4. Payment record بحالة pending - أثر دائم لمحاولة الدفع
            $payment = Payment::create([
                'order_id' => $lockedOrder->id,
                'payment_method' => $paymentMethod,
                'status' => PaymentStatus::Pending,
                'amount' => $lockedOrder->total,
                'idempotency_key' => $idempotencyKey,
            ]);

            // 5. استدعاء الـ Gateway
            $result = $gateway->charge($lockedOrder, $data);

            // 6. تحديث النتيجة - مقارنة عبر Enum مش magic string
            if (PaymentStatus::from($result['status']) === PaymentStatus::Successful) {
                $payment->markAsSuccessful(
                    gatewayReference: $result['gateway_reference'],
                    gatewayResponse: $result['raw_response'],
                );

                $lockedOrder->markAsPaid();
            } else {
                $payment->markAsFailed(
                    gatewayResponse: $result['raw_response'],
                );
            }

            return $payment->fresh();
        });
    }
}
