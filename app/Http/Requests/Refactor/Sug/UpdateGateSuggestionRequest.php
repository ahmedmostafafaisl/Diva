<?php

namespace App\Http\Requests\Refactor\Sug;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGateSuggestionRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'gate_id' => 'sometimes|exists:gates,id',
            'product_id' => 'sometimes|exists:products,id',
        ];
    }
}
