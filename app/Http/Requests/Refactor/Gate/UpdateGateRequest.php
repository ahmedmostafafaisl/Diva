<?php

namespace App\Http\Requests\Refactor\Gate;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGateRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'desc' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
            'status' => 'sometimes|in:active,inactive',
            'popular' => 'boolean',
            'suggestion' => 'boolean',
            'banners.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg'
        ];
    }
}
