<?php

namespace App\Http\Requests\Refactor\Suggestion;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubCategorySuggestionRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'subCategory_id' => 'required|exists:sub_categories,id',
            'product_id' => 'required|exists:products,id',
        ];
    }
}
