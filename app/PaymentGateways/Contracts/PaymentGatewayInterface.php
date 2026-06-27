<?php

namespace App\PaymentGateways\Contracts;

use App\Models\Order;

interface PaymentGatewayInterface
{
    /**
     * تنفيذ عملية الدفع (simulated) للـ order المحدد.
     *
     * @param  array<string, mixed>  $data  بيانات إضافية خاصة بالـ gateway (مثلاً card token)
     */
    public function charge(Order $order, array $data): array;
}
