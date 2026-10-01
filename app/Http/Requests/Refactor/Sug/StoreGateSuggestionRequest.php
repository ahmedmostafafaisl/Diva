<?php

namespace App\Http\Requests\Refactor\Sug;

use Illuminate\Foundation\Http\FormRequest;

class StoreGateSuggestionRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'gate_id' => 'required|exists:gates,id',
            'product_id' => 'required|exists:products,id',
        ];
    }
}
