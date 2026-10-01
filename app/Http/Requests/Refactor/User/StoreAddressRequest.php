<?php

namespace App\Http\Requests\Refactor\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAddressRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'city_id' => 'required|exists:cities,id',
            'district_id' => 'required|exists:districts,id',
            'status' => 'required|in:active,inactive',
            'type' => 'required|in:home,work,other',
            'name' => 'nullable|string|max:255',
            'lat' => 'nullable|string',
            'long' => 'nullable|string',
            'location_note' => 'nullable|string',
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
