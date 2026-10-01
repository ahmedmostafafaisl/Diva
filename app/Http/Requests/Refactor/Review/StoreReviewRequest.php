<?php

namespace App\Http\Requests\Refactor\Review;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
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
            'rate' => 'required|integer|min:1|max:5',
            'notes' => 'nullable|string',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'user_id' => $this->input('user_id', auth()->id()),
        ]);
    }
}
