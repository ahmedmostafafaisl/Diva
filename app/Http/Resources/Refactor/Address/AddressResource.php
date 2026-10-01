<?php

namespace App\Http\Resources\Refactor\Address;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' =>(int) $this->user_id,
            'city' =>(string) $this->city,
            'type' =>(string) $this->type,
            'name' => (string)$this->name,
            'description' => (string)$this->description,
            'default' => (int)$this->default,
            'street' =>(string) $this->street,
            'state' =>(string) $this->state,
            // 'long' => $this->long,
            // 'location_note' => $this->location_note,
        ];
    }
}
