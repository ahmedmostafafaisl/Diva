<?php

namespace App\Http\Requests\Refactor\User;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserDataRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {

        $userDataId = $this->route('id');
        return [

            'first_name' => 'nullable|string',
            'last_name'  => 'nullable|string',
            // 'phone'      => 'nullable|unique:user_data,phone,' . $this->user()->id,
            // 'phone' => [
            //     'nullable',
            //     Rule::unique('user_data')
            //         ->where('user_id', $this->user_id)
            //         ->ignore($userDataId),
            // ],
            // 'email'      => 'nullable',
            // 'email' => 'required|email|unique:users,email,' . $this->user()->id,
            'email' => [
                'required',
                'email',
                Rule::unique('users')
                    ->where('id', $this->user_id)
                    ->ignore($userDataId),
            ],
            'city'       => 'nullable|string',
            'state'      => 'nullable|string',
            'street'   => 'nullable|string',
            'type' => 'required|in:home,work,other',
            'birth_date'  => 'nullable|string',
            'gender'  => 'nullable|string',
            'location_note'   => 'nullable|string',

        ];
    }
}
