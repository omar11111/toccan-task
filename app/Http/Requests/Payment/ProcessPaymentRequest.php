<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ملكية الـ order بتتفحص في الـ Controller، وجود الـ Idempotency-Key بيتفحص في الـ Middleware
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', Rule::in(array_keys(config('payments.gateways', [])))],

            // بيانات اختيارية خاصة بالـ gateway (مثلاً card_number للـ simulation)
            'gateway_data' => ['sometimes', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.in' => 'The selected payment method is not supported.',
        ];
    }
}
