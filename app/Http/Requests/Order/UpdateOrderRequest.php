<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ملكية الـ order نفسها بتتفحص في الـ Controller عبر authorizeOrderOwnership
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['sometimes', 'string', 'max:255'],
            'customer_email' => ['sometimes', 'email', 'max:255'],

            // لو items اتبعتت، دي القائمة الكاملة الجديدة (PUT semantics - استبدال كامل)
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_name' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.price' => ['required_with:items', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.min' => 'If items are provided, the order must contain at least one item.',
        ];
    }
}
