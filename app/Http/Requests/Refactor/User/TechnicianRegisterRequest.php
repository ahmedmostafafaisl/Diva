<?php

namespace App\Http\Requests\Refactor\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class TechnicianRegisterRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'username' => 'required|string',
            'city_id' => 'required|integer|exists:cities,id',
            'district_id' => 'required|array',
            'district_id.*' => 'integer|exists:districts,id',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|confirmed',
            'following_id'  => 'required|integer|unique:users,following_id',
            'role' => 'required|string',
            'skills' => 'required|array',
            'skills.*' => 'integer|exists:skills,id',
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
