<?php

namespace App\Http\Resources\Refactor\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDataResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'user_id'    => $this->user_id,
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'phone'      => $this->phone,
            'email'      => $this->email,
            'city'       => $this->city,
            'country'    => $this->country,
            'state'      => $this->state,
            'address_1'  => $this->address_1,
            'address_2'  => $this->address_2,
            'postcode'   => $this->postcode,
            'street'   => $this->street,
            'type'   => $this->type,
        ];
    }
}
