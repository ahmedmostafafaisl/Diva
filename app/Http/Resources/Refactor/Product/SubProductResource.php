<?php

namespace App\Http\Resources\Refactor\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class SubProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = Auth::user();

        $wash = [];

        if ($user != null && $user->wishlist != null) {
            foreach ($user->wishlist->products as $value) {
                $wash[] = $value->product_id;
            }
        }

        $cart = [];

        if ($user != null && $user->cart != null) {
            foreach ($user->cart->products as $value) {
                $cart[] = $value->product_id;
            }
        }

        $isInSubcategory211 = $this->subCategories->contains('id', 211);
        $variations = $this->variations;
        $hasMultipleVariations = $variations->count() > 1;

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

        $discount = null;
        $discount_percentage = null;

        if (
            $regular_price !== null &&
            $sale_price !== null &&
            $regular_price > $sale_price
        ) {
            $discount = number_format($regular_price - $sale_price, 2, '.', '');
            $discount_percentage = number_format((($regular_price - $sale_price) / $regular_price) * 100, 2, '.', '');
        }

        return [
            'id' => $this->id,
            'sub_category_id' => $this->subCategories
                ->pluck('id')
                ->intersect([169, 189, 210, 211, 267])
                ->first(),
            'sku' => $this->sku,
            'name' => $this->name,
            'brand' => $this->brand,
            'type' => 'original',
            'price' => $this->price,
            'sale_price' => $sale_price,
            'regular_price' => $regular_price,
            'discount' => $discount,
            'discount_percentage' => $discount_percentage,
            'variation_type' => $variation_type,
            'description' => $this->desc,
            'short_description' => $this->short_description,
            'type_of_product' => $this->type_of_product,
            'stock_status' => $this->stock_status,
            'tax' => (bool) ($this->tax ?? false),
            'wishlist' => in_array($this->id, $wash),
            'cart' => in_array($this->id, $cart),
            'standard' => $this->standard ? explode(',', $this->standard) : [],
            'all_price' => $this->all_price ?? null,

            'images' => $this->images->map(function ($image) {
                return str_replace('3.73.173.183', 'centerialmall.com', $image->image);
            })->values(),

            'variations' => $this->variations->map(function ($variation) {
                $addons = $variation->addons ?? collect();

                $visionRight = $addons
                    ->where('addon_type', 'vision_power')
                    ->where('side', 'right')
                    ->pluck('label')
                    ->values();

                $visionLeft = $addons
                    ->where('addon_type', 'vision_power')
                    ->where('side', 'left')
                    ->pluck('label')
                    ->values();

                $quantityRight = $addons
                    ->where('addon_type', 'quantity_price')
                    ->where('side', 'right')
                    ->map(function ($item) {
                        return [
                            'label' => $item->label,
                            'price' => $item->price,
                        ];
                    })
                    ->values();

                $quantityLeft = $addons
                    ->where('addon_type', 'quantity_price')
                    ->where('side', 'left')
                    ->map(function ($item) {
                        return [
                            'label' => $item->label,
                            'price' => $item->price,
                        ];
                    })
                    ->values();

                return [
                    'id' => $variation->id,
                    'sku' => $variation->sku,
                    'price' => $variation->price,
                    'stock' => $variation->stock_status,
                    'regular_price' => $variation->regular_price,
                    'sale_price' => $variation->sale_price,
                    'type' => $variation->type,
                    'vision_right' => $visionRight,
                    'vision_left' => $visionLeft,
                    'quantity_right' => $quantityRight,
                    'quantity_left' => $quantityLeft,
                ];
            })->values(),

            'related_products' => ProductResource::collection($this->relatedProducts()),
        ];
    }
}
