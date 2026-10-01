<?php

namespace App\Http\Requests\Refactor\Zone;

use Illuminate\Foundation\Http\FormRequest;

class ZoneRequest extends FormRequest
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
            'name'  => 'required|string|max:255',
            'order' => 'nullable|integer',
        ];
    }
}
