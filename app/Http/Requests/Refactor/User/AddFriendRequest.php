<?php

namespace App\Http\Requests\Refactor\User;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class AddFriendRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'friend_id' => [
                'required',
                'integer',
                'different:auth_user',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('type', 'customer');
                })
            ]
        ];
    }
}
