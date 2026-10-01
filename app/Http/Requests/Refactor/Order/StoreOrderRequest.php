<?php

namespace App\Http\Requests\Refactor\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if (!$value && !auth()->check()) {
                        $fail('The User ID is required if no authenticated user is available.');
                    }
                },
            ],
            'currency' => 'nullable|string',
            'tax' => 'nullable|numeric',
            'total_amount' => 'nullable|numeric',
            'total_price' => 'nullable|numeric',
            'billing_email' => 'nullable|email',
            'payment_method' => 'required|string',
            'payment_id' => 'nullable|string',
            'shipment_note' => 'nullable|string',
            'status' => 'nullable|in:pending,shipped,completed,canceled',
            'payment_status' => 'nullable|in:pending,paid,refunded,unpaid,unpaid_status',
            // 'products' => 'required|array',
            // 'products.*.id' => 'required|exists:products,id',
            // 'products.*.quantity' => 'required|integer|min:1',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'user_id' => $this->input('user_id', auth()->id()),
        ]);
    }
}
