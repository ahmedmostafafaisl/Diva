<?php

namespace App\Http\Requests\Refactor\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RespondToFollowRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'status' => 'required|in:accepted,rejected',
        ];
    }
}
