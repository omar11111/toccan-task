<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // التحقق من الهوية تم عبر middleware auth:api، مفيش قيد إضافي هنا
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'An order must contain at least one item.',
            'items.min' => 'An order must contain at least one item.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'items.*.price.min' => 'Price must be greater than zero.',
        ];
    }
}
