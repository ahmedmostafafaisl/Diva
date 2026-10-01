<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class NotificationStoreRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'title'   => 'nullable|string|max:255',
            'body'    => 'nullable|string',
            'read'    => 'boolean',
            'data'    => 'nullable|array',
        ];
    }
}
