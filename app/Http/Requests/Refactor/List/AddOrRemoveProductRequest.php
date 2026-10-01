<?php

namespace App\Http\Requests\Refactor\List;

use Illuminate\Foundation\Http\FormRequest;

class AddOrRemoveProductRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'list_id' => 'required|exists:my_lists,id',
            'product_id' => 'required|exists:products,id',
        ];
    }
}
