<?php

namespace App\Http\Requests\Refactor\Gate;

use Illuminate\Foundation\Http\FormRequest;

class StoreGateRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'desc' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:8048',
            'status' => 'nullable|in:active,inactive',
            'popular' => 'boolean',
            'suggestion' => 'boolean',
            'banners.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:8048'
        ];
    }
}
