<?php

namespace App\Http\Resources\Refactor\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Refactor\Product\ProductResource;

class UserProductListResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'subcategory_id' => $this->subcategory_id,
            'subcategory_name' => $this->subcategory->name ?? '',
            'products' => ProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
