<?php

namespace App\PaymentGateways;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\PaymentGateways\Contracts\PaymentGatewayInterface;
use App\PaymentGateways\Contracts\RefundableGatewayInterface;
use Illuminate\Support\Str;

class PaypalGateway implements PaymentGatewayInterface, RefundableGatewayInterface
{
    public function __construct(
        private readonly string $clientId,
        private readonly string $secret,
    ) {}

    /**
     * @return array{
     *     status: string,
     *     gateway_reference: string|null,
     *     raw_response: array|null,
     *     failure_reason: string|null
     * }
     */
    public function charge(Order $order, array $data): array
    {
        // Simulation: مفيش شرط فشل هنا حاليًا، نجاح دايمًا للتبسيط
        // (ممكن نضيف نفس منطق المحاكاة لو احتجنا نتست فشل Paypal بالتحديد)

        return [
            'status' => PaymentStatus::Successful->value,
            'gateway_reference' => 'pp_'.Str::random(20),
            'raw_response' => ['amount' => (float) $order->total, 'currency' => 'USD'],
            'failure_reason' => null,
        ];
    }

    /**
     * @return array{
     *     successful: bool,
     *     gateway_reference: string|null,
     *     raw_response: array|null,
     *     failure_reason: string|null
     * }
     */
    public function refund(Payment $payment, ?float $amount = null): array
    {
        $refundAmount = $amount ?? (float) $payment->amount;

        return [
            'successful' => true,
            'gateway_reference' => 'pprf_'.Str::random(20),
            'raw_response' => ['refunded_amount' => $refundAmount],
            'failure_reason' => null,
        ];
    }
}
