<?php

namespace App\Http\Requests\Refactor\User\Client;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFcmTokenRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'fcm_token' => 'required|string',
        ];
    }
}
