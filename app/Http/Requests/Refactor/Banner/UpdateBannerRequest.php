<?php

namespace App\Http\Requests\Refactor\Banner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateBannerRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'reference_id' => 'nullable|integer',
            'type' => 'nullable|in:package,service',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'duration' => 'nullable|integer|min:1',
            'status' => 'nullable|in:active,inactive',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        // Collect validation errors
        $errors = collect($validator->errors()->messages())->mapWithKeys(function ($messages, $attribute) {
            return [$attribute => $messages[0]]; // Take only the first error message per attribute
        });
        $response = [
            'status' => false,
            'messages' => $errors,
        ];

        // Return custom JSON response
        throw new HttpResponseException(response()->json($response, 422));
    }
}
