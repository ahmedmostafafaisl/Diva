<?php

namespace App\Http\Resources\Refactor\Product;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class SingleProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = Auth::user();

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


        // Check if the product belongs to subcategory 211
        $isInSubcategory211 = $this->subCategories->contains('id', 211);

        // Get the variations
        $variations = $this->variations;

        // Check if the product has more than one variation
        $hasMultipleVariations = $variations->count() > 1;

        // Determine regular_price, sale_price, and variation_type
        $regular_price = $this->regular_price;
        $sale_price = $this->sale_price;
        $variation_type = $this->sale_type;

        if ($isInSubcategory211 && $hasMultipleVariations) {
            $firstVariation = $variations->first();
            if ($firstVariation) {
                $regular_price = $firstVariation->regular_price;
                $sale_price = $firstVariation->sale_price;
                $variation_type = $firstVariation->type;
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
    $regular_price > 0 &&
    $sale_price !== null &&
    (
        $regular_price > $sale_price ||
        $regular_price > $this->price
    )
) {
    $discount = number_format($regular_price - $sale_price, 2, '.', '');
    $discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2, '.', '');
}


        return [
            'id' => $this->id,
            'name' => $this->name,
            'brand' => $this->brand,
            'plus' => $this->plus_cat,
            'price'       => $this->price,
            'sale_price'  => $sale_price,
            'regular_price' => $regular_price,
            'discount'       => $discount,
            'discount_percentage' => $discount_percentage,
            'variation_type' => $variation_type,
            'description' => $this->desc,
            'short_description' => $this->short_description,
            'type_of_product' => $this->type_of_product,
                'stock_status' => $this->stock_status,
            'type' => 'original',
            'attributes' => $this->attributes,
            'wishlist' => in_array($this->id, $wash),
            'cart' => in_array($this->id, $cart),
            'images' => $this->images->map(function ($image) {
                return str_replace('3.73.173.183', 'centerialmall.com', $image->image);
            }),

        ];
    }
}
