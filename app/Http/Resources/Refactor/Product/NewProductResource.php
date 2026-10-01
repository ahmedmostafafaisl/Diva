<?php

namespace App\Http\Resources\Refactor\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class NewProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = Auth::user(); // Get authenticated user
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
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'stock_status' => $this->stock_status,
            'description' => $this->desc,
            'short_description' => $this->short_description,
            'type_of_product' => $this->type_of_product,
            'brand'       => $this->brand,
            'plus_cat' => $this->plus_cat,
            'price'       => $this->price,
            'sale_price'  => $sale_price,
            'regular_price' => $regular_price,
            'discount'       => $discount,
            'discount_percentage' => $discount_percentage,
            'variation_type' => $variation_type,
            'wishlist'    => $user && $user->wishlist ? $user->wishlist->products->pluck('product_id')->contains($this->id) : false,
            'cart'        => $user && $user->cart ? $user->cart->products->pluck('product_id')->contains($this->id) : false,
            'type'        => 'original',
            'standard'    => $this->standard ?? null,
            'all_price'   => $this->all_price ?? null,
            'count'       => $this->count ?? null,
            'images'      => array_map(fn($image) => str_replace('3.73.173.183', 'centerialmall.com', $image), $this->images->pluck('image')->toArray()),

            // Mapping categories
            'subCategories' => $this->subCategories->map(fn($category) => [
                'id'   => $category->id,
                'name' => $category->name,
            ]),

            // Mapping tags
            'tags' => $this->tags->map(fn($tag) => [
                'id'   => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ]),

            // Mapping add-ons
            'addons' => $this->addOns->map(fn($addon) => [
                'title'  => $addon->title,
                'type'   => $addon->type,
                'options' => $addon->options,
            ]),
            'attributes' => $this->attributes->map(fn($attribute) => [
                'id'   => $attribute->id,
                'name' => $attribute->name,
                'value' => $attribute->value,
                'options' => $attribute->options,
            ]),
            // Mapping variations
            'variations' => $variations->map(fn($variation) => [
                'id'            => $variation->id,
                'sku'           => $variation->sku,
                'price'         => $variation->price,
                'stock'         => $variation->stock_status,
                'regular_price' => $variation->regular_price,
                'sale_price'    => $variation->sale_price,
            ]),

                     'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'related_products' => $this->subcategory
                ? NewProductResource::collection($this->subCategories->products->where('id', '!=', $this->id)->values())
                : [],
        ];
    }
}
