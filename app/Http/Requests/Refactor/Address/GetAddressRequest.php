<?php

namespace App\Http\Requests\Refactor\Address;

use Illuminate\Foundation\Http\FormRequest;

class GetAddressRequest extends FormRequest
{


    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if (!$value && !auth()->check()) {
                        $fail('The User ID is required if no authenticated user is available.');
                    }
                },
            ],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'user_id' => $this->input('user_id', auth()->id()),
        ]);
    }
}
