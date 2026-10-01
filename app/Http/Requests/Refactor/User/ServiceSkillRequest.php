<?php

namespace App\Http\Requests\Refactor\User;

use Illuminate\Foundation\Http\FormRequest;

class ServiceSkillRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'skills' => 'required|array',
            'skills.*' => 'exists:skills,id', // Validate that skill IDs exist in the skills table
        ];
    }
}
