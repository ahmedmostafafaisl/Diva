<?php

namespace App\Http\Requests\Refactor\User\Client;

use Illuminate\Foundation\Http\FormRequest;

class CreateClientRequest extends FormRequest
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
            'username' => 'nullable|string|max:255',
            'phone' => 'required|unique:users,phone|string|max:15',
            'second_phone' => 'nullable|unique:users,second_phone|string|max:15',
            'email' => 'nullable|email|unique:users,email|max:255',
        ];
    }
}
