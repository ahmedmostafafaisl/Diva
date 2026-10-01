<?php

namespace App\Http\Resources\Refactor\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ProductResource extends JsonResource
{

    public function toArray(Request $request): array
    {

        $user = Auth::user(); // Get authenticated user

        $wash = [];

        if ($user != null) {
            if ($user->wishlist != null) {
                foreach ($user->wishlist->products as $key => $value) {
                    array_push($wash, $value->product_id);
                }
            }
        }

        $cart = [];

        if ($user != null) {
            if ($user->cart != null) {
                foreach ($user->cart->products as $key => $value) {
                    array_push($cart, $value->product_id);
                }
            }
        }

        // Prepare sale/regular price (if nullable)
        $sale_price = $this->sale_price ?? null;
        $regular_price = $this->regular_price ?? null;
        $variation_type = $this->variation_type ?? null;

        // Calculate discount if applicable
        $discount = null;
        $discount_percentage = null;

        if (
            $regular_price !== null &&
            $sale_price !== null &&
            (
                $regular_price > $sale_price ||
                $regular_price > $this->price
            )
        ) {
            $discount = $regular_price - $sale_price;
            $discount_percentage = round((($regular_price - $sale_price) / $regular_price) * 100, 2); // % off
            $discount = number_format($regular_price - $sale_price, 2, '.', '');
            $discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2, '.', '');
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'brand' => $this->brand,
            'price' => $this->price,
            'sale_price' => $this->sale_price,
            'regular_price' => $this->regular_price,
            'discount'       => $discount,
            'discount_percentage' => $discount_percentage,
            'description' => $this->desc,
            'short_description' => $this->short_description,
            'type_of_product' => $this->type_of_product,
                'stock_status' => $this->stock_status,
            'type' => 'original',
            'wishlist' => in_array($this->id, $wash),
            'cart' => in_array($this->id, $cart),
            'images' => $this->images->map(function ($image) {
                return str_replace('3.73.173.183', 'centerialmall.com', $image->image);
            }),

        ];
    }
}
