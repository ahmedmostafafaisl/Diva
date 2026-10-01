<?php

namespace App\Http\Requests\Refactor\User;

use Illuminate\Foundation\Http\FormRequest;

class AddProductToListRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'subcategory_id' => 'required|integer|exists:sub_categories,id',
            'product_id' => 'required|integer|exists:products,id',
        ];
    }
}
