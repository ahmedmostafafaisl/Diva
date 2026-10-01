<?php

namespace App\Http\Resources\Refactor\Cart;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class CartProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = Auth::user();

        $wash = [];

        if ($user && $user->wishlist) {
            foreach ($user->wishlist->products as $value) {
                $wash[] = $value->product_id;
            }
        }

        if (! $this->product || $this->product->stock_status !== 'instock') {
            return [];
        }

        return [
            // cart row
            'cart_product_id' => $this->id,

            // product
            'id' => $this->product->id ?? null,
            'variation_id' => $this->variation_id,
            'variation_type' => $this->variation->type ?? null,

            'name' => $this->product->name ?? null,
            'description' => $this->product->desc ?? null,

            // pricing snapshot from cart row
            'price' => $this->product->price ?? null,
            'unit_price' => $this->unit_price,
            'line_total' => $this->line_total,

            'wishlist' => in_array($this->product->id, $wash),

            // normal products
            'quantity' => $this->quantity !== null ? (int) $this->quantity : null,
            'standard' => $this->standard,

            // lens selections
            'right_standard' => $this->right_standard,
            'right_quantity' => $this->right_quantity !== null ? (int) $this->right_quantity : null,
            'right_price' => $this->right_price,

            'left_standard' => $this->left_standard,
            'left_quantity' => $this->left_quantity !== null ? (int) $this->left_quantity : null,
            'left_price' => $this->left_price,

            'images' => $this->product->images->map(function ($image) {
                return $image->image;
            })->values(),
        ];
    }
}
