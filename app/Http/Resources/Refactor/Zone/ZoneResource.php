<?php

namespace App\Http\Resources\Refactor\Zone;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZoneResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'order'      => $this->order,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
