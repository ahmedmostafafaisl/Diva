<?php

namespace App\Http\Requests\Refactor\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'sku' => 'nullable|string|unique:products,sku',
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'price' => 'nullable|numeric',
            'sale_price' => 'nullable|numeric',
            'regular_price' => 'nullable|numeric',
            'desc' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'vendor_id' => 'nullable|integer',
            'store_name' => 'nullable|string|max:255',
            'store_url' => 'nullable|string|max:255',
            'sub_categories' => 'array',
            'sub_categories.*' => 'exists:sub_categories,id',
            'images' => 'array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }
}
