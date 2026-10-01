<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'currency' => 'nullable|string',
            'billing_email' => 'nullable|email',
            'shipment_note' => 'nullable|string',
            'payment_method' => 'required|in:apple_pay,moyasar,tabby,tamara',
        ];
    }
}
