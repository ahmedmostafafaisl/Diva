<?php

namespace App\Http\Resources\Refactor\Sug;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Refactor\Gate\GateResource;
use App\Http\Resources\Refactor\Product\ProductResource;

class SingleGateSuggestionResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gate_id' => $this->gate_id,
            'product' => $this->product,
            'gate' => new GateResource($this->whenLoaded('gate')),
            'created_at' => $this->created_at,
        ];
    }
}
