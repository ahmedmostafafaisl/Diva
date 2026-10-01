<?php

namespace App\Http\Requests\Refactor\Address;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            // 'user_id' => 'required|exists:users,id',
            'user_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if (!$value && !auth()->check()) {
                        $fail('The User ID is required if no authenticated user is available.');
                    }
                },
            ],
            'type' => 'required|in:home,work,other',
            'name' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'default' => 'boolean',
            'status' => 'nullable|in:active,inactive',
            'lat' => 'nullable|numeric',
            'long' => 'nullable|numeric',
            'location_note' => 'nullable|string',
            'street'   => 'nullable|string',
            'state'   => 'nullable|string',

        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'user_id' => $this->input('user_id', auth()->id()),
        ]);
    }
}
