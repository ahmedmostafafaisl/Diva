<?php

namespace App\Http\Resources\Refactor\User\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientWithAddressResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'type' => $this->type,
            'phone' => $this->phone,
            'second_phone' => $this->second_phone,
            'email' => $this->email,
        ];
    }
}
