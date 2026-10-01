<?php

namespace App\Transformers;

use Illuminate\Support\Arr;

class WpProductToEditedTransformer
{
    private static function money($v): ?string
    {
        if ($v === '' || $v === null) return null;
        return number_format((float)$v, 2, '.', '');
    }

    public static function transform(array $p, array $context = []): array
    {
        // context: wishlistIds[], cartIds[] ممكن تبعته من controller
        $wishlistIds = $context['wishlist_ids'] ?? [];
        $cartIds     = $context['cart_ids'] ?? [];

        $productId = $p['id'] ?? null;

        // ========== attributes ==========
        $attributes = collect($p['attributes'] ?? [])
            ->map(fn($a) => [
                'id' => $a['id'] ?? null,                 // original غالبًا مفيهوش id
                'name' => $a['name'] ?? null,
                'value' => $a['value'] ?? null,
                'options' => $a['options'] ?? null,
            ])->values()->all();

        // ========== subCategories ==========
        $subCategories = collect($p['categories'] ?? [])
            ->map(fn($c) => [
                'id' => $c['id'] ?? null,
                'name' => $c['name'] ?? null,
            ])->values()->all();

        // ========== tags ==========
        $tags = collect($p['tags'] ?? [])
            ->map(fn($t) => [
                'id' => $t['id'] ?? null,
                'name' => $t['name'] ?? null,
                'slug' => $t['slug'] ?? null,
            ])->values()->all();

        // ========== addons (من add_ons في original) ==========
        // NewProductResource عندك متوقع: title/type/options
        // original عنده id/options فقط → نخلي title/type null
        $addons = collect($p['add_ons'] ?? [])
            ->map(fn($a) => [
                'title' => null,
                'type' => null,
                'options' => $a['options'] ?? null,
            ])->values()->all();

        // ========== variations ==========
        $variations = collect($p['variations'] ?? [])
            ->map(fn($v) => [
                'id' => $v['variation_id'] ?? null,
                'sku' => $v['sku'] ?? null,
                'price' => self::money($v['price'] ?? null),
                'stock' => $v['stock_status'] ?? null,
                'regular_price' => self::money($v['regular_price'] ?? null),
                'sale_price' => self::money($v['sale_price'] ?? null),
            ])->values()->all();

        // ========== prices ==========
        $price = self::money($p['price'] ?? null);

        // regular_price في original عندك كان "" بس فيه regular_price_1 / regular_price_2
        // لو regular_price فاضي، ناخد regular_price_1 كـ fallback
        $regular = self::money($p['regular_price'] ?? null)
            ?? self::money($p['regular_price_1'] ?? null);

        $sale = self::money($p['sale_price'] ?? null); // غالبًا هتبقى null

        // ========== discount ==========
        $discount = null;
        $discount_percentage = null;
        if ($regular !== null && (float)$regular > 0 && $sale !== null && (float)$sale < (float)$regular) {
            $discount = number_format(((float)$regular - (float)$sale), 2, '.', '');
            $discount_percentage = number_format((((float)$regular - (float)$sale) / (float)$regular) * 100, 2, '.', '');
        }

        return [
            'id' => $productId,
            'name' => $p['name'] ?? null,
            'slug' => $p['slug'] ?? null, // original غالبًا مش موجود → null
            'stock_status' => $p['stock_status'] ?? null,

            // عندك في edited بتسميها description لكن بتحط desc
            'description' => $p['description'] ?? null,
            'short_description' => $p['short_description'] ?? null,

            'type_of_product' => $p['type_of_product'] ?? null,
            'brand' => $p['brand_name'] ?? null,
            'plus_cat' => null,

            'price' => $price,
            'sale_price' => $sale,
            'regular_price' => $regular,

            'discount' => $discount,
            'discount_percentage' => $discount_percentage,
            'variation_type' => null,

            // ✅ booleans true/false فقط
            'wishlist' => $productId ? in_array($productId, $wishlistIds) : false,
            'cart' => $productId ? in_array($productId, $cartIds) : false,

            'type' => 'original',

            // fields غير موجودة في original → null
            'standard' => null,
            'all_price' => null,
            'count' => null,

            'images' => $p['images'] ?? [],

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
