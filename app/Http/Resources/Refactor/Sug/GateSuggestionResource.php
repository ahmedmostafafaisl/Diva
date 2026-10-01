<?php

namespace App\Http\Resources\Refactor\Sug;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Refactor\Gate\GateResource;
use App\Http\Resources\Refactor\Product\ProductResource;

class GateSuggestionResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gate_id' => $this->gate_id,
            'product_id' => $this->product_id,
            'gate' => new GateResource($this->whenLoaded('gate')),
            'product' => new ProductResource($this->whenLoaded('product')),
            'created_at' => $this->created_at,
        ];
    }
}
