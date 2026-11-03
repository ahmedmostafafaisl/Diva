<?php

namespace App\Services\New;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WpProductsClient
{
    /**
     * Max items the new endpoint returns per page.
     * We loop over pages until has_more = false.
     */
    private const COMMERCE_PER_PAGE = 100;

    /** Safety cap so a bad has_more flag can never loop forever. */
    private const COMMERCE_MAX_PAGES = 50;

    /**
     * Get ALL products of a category from the new endpoint:
     * https://diva.sa/wp-json/diva-commerce/v1/products
     *
     * Response shape:
     * { items: [...], page, per_page, total, total_pages, has_more, includes: {...} }
     */
    public function productsByCategory(int $categoryId): array
    {
        $ttl = (int) config('services.wp.commerce_cache_ttl', 300);

        return Cache::remember(
            "wp:commerce:products:cat:{$categoryId}",
            $ttl,
            fn () => $this->fetchAllPages($categoryId)
        );
    }

    private function fetchAllPages(int $categoryId): array
    {
        $base = rtrim((string) config('services.wp.commerce_base'), '/');
        $categoryParam = (string) config('services.wp.commerce_category_param', 'category');

        $items = [];
        $page = 1;

        do {
            $res = Http::timeout(20)->acceptJson()->get("$base/products", [
                $categoryParam => $categoryId,
                'page' => $page,
                'per_page' => self::COMMERCE_PER_PAGE,
            ]);

            if (! $res->successful()) {
                Log::warning('WP commerce products failed', [
                    'category' => $categoryId,
                    'page' => $page,
                    'status' => $res->status(),
                    'body' => mb_substr($res->body(), 0, 1000),
                ]);
                break;
            }

            $json = $res->json();

            if (! is_array($json) || ! isset($json['items']) || ! is_array($json['items'])) {
                Log::warning('WP commerce products unexpected payload', [
                    'category' => $categoryId,
                    'page' => $page,
                    'body' => mb_substr($res->body(), 0, 1000),
                ]);
                break;
            }

            foreach ($json['items'] as $p) {
                if (is_array($p) && isset($p['id'])) {
                    $items[] = $p;
                }
            }

            $hasMore = (bool) ($json['has_more'] ?? false);
            $totalPages = (int) ($json['total_pages'] ?? $page);
            $page++;
        } while (($hasMore || $page <= $totalPages) && $page <= self::COMMERCE_MAX_PAGES);

        // De-duplicate by id (just in case pages overlap)
        return array_values(array_column($items, null, 'id'));
    }

    public function singleProductV2(int $productId): array
    {
        $base = rtrim((string) config('services.wp.base'), '/');

        $res = Http::timeout(15)->get("$base/single-product-v2/", [
            'product_id' => $productId,
        ]);

        if (! $res->successful()) {
            Log::warning('WP singleProductV2 failed', [
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
            Log::warning('WP singleProductV2 unexpected payload', [
                'product_id' => $productId,
                'body' => $res->json(),
            ]);

            return [];
        }

        return $json;
    }
}
