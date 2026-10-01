<?php

namespace App\Http\Resources\Refactor\Wishlist;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Refactor\Product\ProductResource;

class WishlistProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->product->id ?? null,
            'name' =>(string) $this->product->name ?? null,
            'description' =>(string) $this->product->desc ?? null,
            'price' =>(string) $this->product->price ?? null,
            'images' => $this->product->images->map(fn($image) => $image->image),
        ];
    }
}
