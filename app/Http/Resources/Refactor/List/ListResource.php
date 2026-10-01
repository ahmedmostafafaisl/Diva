<?php

namespace App\Http\Resources\Refactor\List;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Refactor\Product\ProductResource;

class ListResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'products' => ProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
