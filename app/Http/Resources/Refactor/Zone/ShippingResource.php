<?php

namespace App\Http\Resources\Refactor\Zone;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // 'zone' => [
            //     'id' => $this->zone?->id,
            //     'name' => $this->zone?->name,
            // ],
            'title' => $this->title,
            'method_title' => $this->method_title,
            'method_description' => $this->method_description,
            'instance_id' => $this->instance_id,
            'order' => $this->order,
            'enabled' => $this->enabled,
            'cost' => $this->cost,
            'tax' => $this->tax,
            'created_at' => $this->created_at,
        ];
    }
}
