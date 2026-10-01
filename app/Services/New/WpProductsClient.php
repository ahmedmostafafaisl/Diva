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
            // رجع array فاضي بدل ما تكسر الـ API بتاعك
            return [];
        }

        $json = $res->json();

        // الـ endpoint بتاعك بيرجع Array مباشرة حسب اللي انت باعته قبل كده
        return is_array($json) ? $json : [];
    }

    public function singleProductV2(int $productId): array
    {
        $base = rtrim(config('services.wp.base'), '/');

        $res = Http::timeout(15)->get("$base/single-product-v2/", [
            'product_id' => $productId,
        ]);

        if (! $res->successful()) {
            return [];
        }

        $json = $res->json();

        // ✅ new response: [ { ... } ]
        if (is_array($json) && isset($json[0]) && is_array($json[0])) {
            return $json[0];
        }

        return is_array($json) ? $json : [];
    }
}
