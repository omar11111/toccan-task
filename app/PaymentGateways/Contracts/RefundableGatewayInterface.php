<?php

namespace App\PaymentGateways\Contracts;

use App\Models\Payment;

interface RefundableGatewayInterface
{
    /**
     * @param  float|null  $amount  لو null، يتم استرداد المبلغ كامل
     */
    public function refund(Payment $payment, ?float $amount = null): array;
}
