<?php

namespace App\Http\Requests\Refactor\Zone;

use Illuminate\Foundation\Http\FormRequest;

class ShippingRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'zone_id' => 'required|exists:zones,id',
            'title' => 'required|string',
            'method_title' => 'nullable|string',
            'method_description' => 'nullable|string',
            'instance_id' => 'nullable|integer',
            'order' => 'nullable|integer',
            'enabled' => 'nullable|boolean',
            'cost' => 'nullable|string',
            'tax' => 'nullable|string',
        ];
    }
}
