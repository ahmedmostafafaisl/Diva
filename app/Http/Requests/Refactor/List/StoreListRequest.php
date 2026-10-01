<?php

namespace App\Http\Requests\Refactor\List;

use Illuminate\Foundation\Http\FormRequest;

class StoreListRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:general,compare,waiting',
            'description' => 'nullable|string|max:1000',
        ];
    }
}
