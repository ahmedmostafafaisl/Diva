<?php

namespace App\Http\Requests\Refactor\SubCategory;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubCategoryRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'gate_id' => 'required|exists:gates,id',
            'name' => 'required|string|max:255',
            'desc' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'parent' => 'nullable|boolean',
            'count' => 'nullable|integer',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:8048',

        ];
    }
}
