<?php

namespace App\Http\Requests\Refactor\User\Client;

use Illuminate\Foundation\Http\FormRequest;

class SearchClientRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'phone' => 'nullable|string',
            'username' => 'nullable|string',
        ];
    }
}
