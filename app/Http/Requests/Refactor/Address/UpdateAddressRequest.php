<?php

namespace App\Http\Requests\Refactor\Address;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{


    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'type' => 'sometimes|in:home,work,other',
            'name' => 'sometimes|string|max:255',
            'city' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'default' => 'boolean',
            'status' => 'sometimes|in:active,inactive',
            'lat' => 'sometimes|numeric',
            'long' => 'sometimes|numeric',
            'location_note' => 'nullable|string',
            'street'   => 'nullable|string',
            'state'   => 'nullable|string',
        ];
    }
}
