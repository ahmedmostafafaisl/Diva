<?php

namespace App\Http\Requests\Refactor\Wishlist;

use Illuminate\Foundation\Http\FormRequest;

class WishlistRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
        ];
    }
}
