<?php

namespace App\Http\Requests\Refactor\User;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'city_id' => 'nullable|exists:cities,id',
            'shifts' => 'nullable|array',
            'shifts.*' => 'integer|exists:work_shifts,id',
            'status' => 'nullable|in:active,inactive',
        ];
    }
}
