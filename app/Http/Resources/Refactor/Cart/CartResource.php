<?php

namespace App\Http\Resources\Refactor\Cart;

use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' =>(int) $this->user_id,
            'products' => CartProductResource::collection($this->products),
        ];
    }
}
