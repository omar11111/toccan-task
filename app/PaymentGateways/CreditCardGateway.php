<?php

namespace App\PaymentGateways;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\PaymentGateways\Contracts\PaymentGatewayInterface;
use App\PaymentGateways\Contracts\RefundableGatewayInterface;
use Illuminate\Support\Str;

class CreditCardGateway implements PaymentGatewayInterface, RefundableGatewayInterface
{
    public function __construct(
        private readonly string $apiKey,
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
        // Simulation: في نظام حقيقي هنا هيكون HTTP call فعلي لمزود الدفع (مثلاً Stripe API)
        // بما إن التاسك طلب "simulate"، بنحاكي نجاح/فشل بشكل قابل للتحكم في الاختبارات

        $cardNumber = $data['card_number'] ?? null;

        // محاكاة فشل: لو رقم الكارت بينتهي بـ 0000، نفترض رفض من البنك
        if ($cardNumber && str_ends_with($cardNumber, '0000')) {
            return [
                'status' => PaymentStatus::Failed->value,
                'gateway_reference' => null,
                'raw_response' => ['card_last_four' => substr($cardNumber, -4)],
                'failure_reason' => 'Card declined by issuer (simulated).',
            ];
        }

        return [
            'status' => PaymentStatus::Successful->value,
            'gateway_reference' => 'cc_'.Str::random(20),
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
            'gateway_reference' => 'rf_'.Str::random(20),
            'raw_response' => ['refunded_amount' => $refundAmount],
            'failure_reason' => null,
        ];
    }
}
