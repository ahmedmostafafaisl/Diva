<?php

namespace App\Http\Requests\Refactor\User;

use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreServiceRequest extends FormRequest
{

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
            'service_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:8048',
            'featured' => 'required|boolean',
            'status' => 'required|in:active,inactive',
            'priority' => 'required|integer|min:0',
            'tabby' => 'required|boolean',
            'tamara' => 'required|boolean',
            'online_payment' => 'required|boolean',
            'apple_pay' => 'required|boolean',
            'cover_image'  => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            // 'dy_item_number' => [
            //     'required',
            //     'string',
            //     function ($attribute, $value, $fail) {
            //         $existsInPackages = DB::table('packages')->where('dy_item_number', $value)->exists();
            //         $existsInServices = DB::table('services')->where('dy_item_number', $value)->exists();

            //         if ($existsInPackages || $existsInServices) {
            //             $fail('The dy_item_number must be unique across packages and services.');
            //         }
            //     },
            // ],
            'dy_product_name' => 'required|string|max:255',
            'skills' => 'required|array',
            'skills.*' => 'exists:skills,id',
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
