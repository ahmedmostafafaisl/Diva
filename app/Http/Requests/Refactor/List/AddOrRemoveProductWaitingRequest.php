<?php

namespace App\Http\Requests\Refactor\List;

use Illuminate\Foundation\Http\FormRequest;

class AddOrRemoveProductWaitingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
        ];
    }
}
