<?php

namespace App\Http\Requests\Refactor\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUserRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'username' => 'nullable|string|max:255',
            'type' => 'sometimes|in:employee,customer',
            'phone' => 'nullable|string|unique:users,phone,' . $this->user . '|max:255',
            'email' => 'nullable|string|email|unique:users,email,' . $this->user . '|max:255',
            'status' => 'nullable|in:active,inactive',
            'role' => 'nullable|in:admin,customer_service,technician,team_leader',
            'password' => 'nullable|string|min:8|confirmed',
            'skills' => 'nullable|array',
            'skills.*' => 'exists:skills,id',
            'following_id' => 'nullable|exists:users,id',
            'city_id' => 'nullable|exists:cities,id',
            'district_id' => 'nullable|array',
            'district_id.*' => 'integer|exists:districts,id',
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
