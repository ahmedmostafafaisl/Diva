<?php

namespace App\Http\Resources\Refactor\Wishlist;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Refactor\Product\ProductResource;

class WishlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' =>(int) $this->user_id,
            'products' => WishlistProductResource::collection($this->products),
            // 'products'  => $this->products ? [$this->products->map(fn($product) => [
            //     'id' => $product->id,
            //     'name' => $product->name,
            //     'brand' => $product->brand,
            //     'description' => $product->desc,
            //     'price' => $product->price,
            //     'images' => $product->images->map(fn($image) => asset('storage/' . $image->image)),
            // ])] : null,
        ];
    }
}
