<?php

namespace App\Http\Requests\Refactor\SubCategory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubCategoryRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'gate_id' => 'nullable|exists:gates,id',
            'name' => 'sometimes|string|max:255',
            'desc' => 'nullable|string',
            'parent' => 'nullable|boolean',
            'count' => 'nullable|integer',
            'status' => 'sometimes|in:active,inactive',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:8048',
        ];
    }
}
