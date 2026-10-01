<?php

namespace App\Http\Resources\Refactor\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => (int)$this->user_id ?? null,
            'city_id' => $this->city_id ?? null,
            'district_id' => $this->district_id ?? null,
            'status' => $this->status,
            'type' => (string) $this->type,
            'name' => $this->name,
            'lat' => $this->lat,
            'long' => $this->long,
            'location_note' => $this->location_note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'city' => (string)$this->city,
            'district' => $this->district,
        ];
    }
}
