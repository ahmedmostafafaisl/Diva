<?php

namespace App\Http\Requests\Refactor\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|string',
            'description_ar' => 'required|string',
            'description_en' => 'required|string',
            'service_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'featured' => 'required|boolean',
            'status' => 'nullable|in:active,inactive',
            'priority' => 'required|integer|min:0',
            'tabby' => 'nullable|boolean',
            'tamara' => 'nullable|boolean',
            'online_payment' => 'nullable|boolean',
            'apple_pay' => 'nullable|boolean',
            'cover_image'  => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'dy_item_number' => 'nullable|string|max:255',
            'dy_product_name' => 'nullable|string|max:255',
            'skills' => 'nullable|array',
            'skills.*' => 'exists:skills,id', // Ensure each skill ID exists
            'payment_methods' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (!request('tabby') && !request('tamara') && !request('online_payment') && !request('apple_pay')) {
                        $fail('At least one payment method must be selected.');
                    }
                },
            ],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        // Collect validation errors
        $errors = collect($validator->errors()->messages())->mapWithKeys(function ($messages, $attribute) {
            return [$attribute => $messages[0]]; // Take only the first error message per attribute
        });
        $response = [
            'status' => false,
            'messages' => $errors,
        ];

        // Return custom JSON response
        throw new HttpResponseException(response()->json($response, 422));
    }
}
