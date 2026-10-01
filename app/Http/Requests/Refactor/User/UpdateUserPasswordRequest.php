<?php

namespace App\Http\Requests\Refactor\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUserPasswordRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'password' => 'required|string|min:8|confirmed',
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
