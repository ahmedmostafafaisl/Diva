<?php

namespace App\Transformers;

class WpProductToEditedTransformer
{
    private static function money($v): ?string
    {
        if ($v === '' || $v === null) {
            return null;
        }

        return number_format((float) $v, 2, '.', '');
    }

    /**
     * Transform one product from the new diva-commerce endpoint
     * (https://diva.sa/wp-json/diva-commerce/v1/products) into the app schema.
     */
    public static function transform(array $p, array $context = []): array
    {
        $wishlistIds = $context['wishlist_ids'] ?? [];
        $cartIds = $context['cart_ids'] ?? [];

        $productId = isset($p['id']) ? (int) $p['id'] : null;

        // ========== attributes ==========
        // New API has "attributes" (product attributes) and "all_attributes"
        // (attributes + categories). We keep "attributes" only.
        $attributes = collect($p['attributes'] ?? [])
            ->map(fn ($a) => [
                'id' => $a['id'] ?? null,
                'name' => $a['name'] ?? null,
                'value' => $a['value'] ?? null,
                'options' => $a['options'] ?? null,
            ])->values()->all();

        // ========== subCategories ==========
        $subCategories = collect($p['categories'] ?? [])
            ->map(fn ($c) => [
                'id' => $c['id'] ?? null,
                'name' => $c['name'] ?? null,
            ])->values()->all();

        // ========== tags ==========
        $tags = collect($p['tags'] ?? [])
            ->map(fn ($t) => [
                'id' => $t['id'] ?? null,
                'name' => $t['name'] ?? null,
                'slug' => isset($t['slug']) ? urldecode((string) $t['slug']) : null,
            ])->values()->all();

        // ========== addons ==========
        $addons = collect($p['add_ons'] ?? [])
            ->map(fn ($a) => [
                'title' => $a['title'] ?? null,
                'type' => $a['type'] ?? null,
                'options' => $a['options'] ?? null,
            ])->values()->all();

        // ========== variations ==========
        $rawVariations = collect($p['variations'] ?? [])->filter(fn ($v) => is_array($v))->values();

        $variations = $rawVariations
            ->map(function ($v) {
                // power slug is url-encoded, e.g. "%d8%b2%d9%8a%d9%86%d8%a9" => "زينة", "0-25", "1-00-2"
                $powerSlug = $v['attributes']['attribute_pa_power'] ?? ($v['attribute_pa_power'] ?? null);

                return [
                    'id' => $v['variation_id'] ?? null,
                    'parent_id' => $v['parent_id'] ?? null,
                    'sku' => $v['sku'] ?? null,
                    'price' => self::money($v['price'] ?? null),
                    'stock' => $v['stock_status'] ?? null,
                    'stock_quantity' => isset($v['stock_quantity']) ? (int) $v['stock_quantity'] : null,
                    'regular_price' => self::money($v['regular_price'] ?? null),
                    'sale_price' => self::money($v['sale_price'] ?? null),
                    'image' => $v['image'] ?? null,
                    'power' => $powerSlug !== null ? urldecode((string) $powerSlug) : null,
                ];
            })->values()->all();

        // ========== prices ==========
        // "display_price" is the lowest current price (e.g. "64.00").
        // Regular / sale come from the cheapest variation; fallback to display_price + discount %.
        $price = self::money($p['display_price'] ?? ($p['price'] ?? null));

        $cheapest = $rawVariations
            ->filter(fn ($v) => ($v['price'] ?? '') !== '')
            ->sortBy(fn ($v) => (float) $v['price'])
            ->first();

        $regular = $cheapest ? self::money($cheapest['regular_price'] ?? null) : null;
        $sale = $cheapest ? self::money($cheapest['sale_price'] ?? null) : null;

        $discountPercent = (float) ($p['discount'] ?? 0);

        if ($regular === null && $price !== null) {
            $regular = $discountPercent > 0 && $discountPercent < 100
                ? self::money((float) $price / (1 - $discountPercent / 100))
                : $price;
        }

        if ($sale === null && $discountPercent > 0) {
            $sale = $price;
        }

        // ========== discount ==========
        $discount = null;
        $discount_percentage = null;
        if ($regular !== null && (float) $regular > 0 && $sale !== null && (float) $sale < (float) $regular) {
            $discount = number_format(((float) $regular - (float) $sale), 2, '.', '');
            $discount_percentage = number_format((((float) $regular - (float) $sale) / (float) $regular) * 100, 2, '.', '');
        }

        return [
            'id' => $productId,
            'name' => $p['name'] ?? null,
            'slug' => $p['slug'] ?? null,
            'sku' => $p['sku'] ?? null,
            'stock_status' => $p['stock_status'] ?? null,

            'description' => $p['description'] ?? null,
            'short_description' => $p['short_description'] ?? null,

            'type_of_product' => $p['type_of_product'] ?? null,
            'classification' => ($p['Classification'] ?? '') !== '' ? $p['Classification'] : null,
            'brand' => $p['brand_name'] ?? null,
            'brand_image' => $p['brand_image_url'] ?? null,
            'plus_cat' => null,

            'display_price' => self::money($p['display_price'] ?? null),
            'price' => $price,
            'sale_price' => $sale,
            'regular_price' => $regular,

            'discount' => $discount,
            'discount_percentage' => $discount_percentage,
            'variation_type' => null,

            'likes_count' => $productId ? (int) (($context['likes'] ?? [])[$productId] ?? 0) : 0,
            'wishlist' => $productId ? in_array($productId, $wishlistIds) : false,
            'cart' => $productId ? in_array($productId, $cartIds) : false,

            'type' => 'original',

            'standard' => null,
            'all_price' => null,
            'count' => null,

            'images' => array_values(array_filter($p['images'] ?? [])),

            'subCategories' => $subCategories,
            'tags' => $tags,
            'addons' => $addons,
            'attributes' => $attributes,
            'variations' => $variations,

            'created_at' => null,
            'related_products' => [],
        ];
    }
}
