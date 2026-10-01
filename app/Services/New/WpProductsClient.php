<?php

namespace App\Services\New;

use Illuminate\Support\Facades\Http;

class WpProductsClient
{
    public function productsByCategory(int $categoryId): array
    {
        $base = rtrim(config('services.wp.base'), '/');

        $res = Http::timeout(15)->get("$base/products-by-category-s", [
            'category_ids' => $categoryId,
        ]);

        if (! $res->successful()) {
            \Log::warning('WP productsByCategory failed', [
                'category' => $categoryId,
                'status' => $res->status(),
                'body' => $res->body(),
            ]);

            return [];
        }

        $json = $res->json();

        if (! is_array($json)) {
            return [];
        }

        // Wrapped response: {"products": [...]} or {"data": [...]}
        if (isset($json['products']) && is_array($json['products'])) {
            $json = $json['products'];
        } elseif (isset($json['data']) && is_array($json['data']) && array_is_list($json['data'])) {
            $json = $json['data'];
        }

        // Single product object instead of a list
        if (! array_is_list($json)) {
            if (isset($json['id'])) {
                $json = [$json];
            } else {
                // Error/message object, e.g. {"code": "...", "message": "..."}
                \Log::warning('WP productsByCategory unexpected payload', [
                    'category' => $categoryId,
                    'body' => $json,
                ]);

                return [];
            }
        }

        // Keep only real product rows
        return array_values(array_filter($json, fn ($p) => is_array($p) && isset($p['id'])));
    }

    public function singleProductV2(int $productId): array
    {
        $base = rtrim(config('services.wp.base'), '/');

        $res = Http::timeout(15)->get("$base/single-product-v2/", [
            'product_id' => $productId,
        ]);

        if (! $res->successful()) {
            \Log::warning('WP singleProductV2 failed', [
                'product_id' => $productId,
                'status' => $res->status(),
                'body' => $res->body(),
            ]);

            return [];
        }

        $json = $res->json();

        if (! is_array($json)) {
            return [];
        }

        // Unwrap common shapes: {"product": {...}}, {"data": {...}} or {"data": [{...}]}
        foreach (['product', 'data'] as $key) {
            if (isset($json[$key]) && is_array($json[$key])) {
                $json = $json[$key];
                break;
            }
        }

        // [ {...} ]
        if (array_is_list($json)) {
            $json = (isset($json[0]) && is_array($json[0])) ? $json[0] : [];
        }

        // Normalize the id key
        $json['id'] = $json['id'] ?? $json['Id'] ?? $json['ID'] ?? $json['product_id'] ?? null;

        if (empty($json['id'])) {
            \Log::warning('WP singleProductV2 unexpected payload', [
                'product_id' => $productId,
                'body' => $res->json(),
            ]);

            return [];
        }

        return $json;
    }
}
