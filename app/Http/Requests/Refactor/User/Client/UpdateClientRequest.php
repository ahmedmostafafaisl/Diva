<?php

namespace App\Http\Requests\Refactor\User\Client;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'username' => 'nullable|string|max:255',
            'phone' => 'nullable|unique:users,phone,' . $this->client . '|string|max:15',
            'second_phone' => 'nullable|unique:users,second_phone,' . $this->client . '|string|max:15',
            'email' => 'nullable|email|unique:users,email,' . $this->client . '|max:255',
        ];
    }
}
