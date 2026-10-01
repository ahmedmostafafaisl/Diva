<?php

namespace App\Services\Sync;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Variation;
use App\Models\VariationAddon;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WooProductSyncService
{
    // ✅ مرة كل ساعة
    private int $ttlSeconds = 3600;

    public function syncSingleProductV2(int $productId, array $woo): ?Product
    {
        // ✅ endpoint may return [ { ... } ]
        if (isset($woo[0]) && is_array($woo[0])) {
            $woo = $woo[0];
        }

        $wooId = (int) ($woo['id'] ?? ($woo['Id'] ?? 0));
        if (empty($woo) || $wooId !== $productId) {
            return null;
        }

        return DB::transaction(function () use ($productId, $woo) {

            // ✅ upsert product first to avoid "name has no default value"
            $product = Product::updateOrCreate(
                ['id' => $productId],
                [
                    'sku' => (string) $productId,
                    'name' => $woo['name'] ?? ($woo['Name'] ?? ('Product '.$productId)),
                    'desc' => $woo['description'] ?? ($woo['desc'] ?? null),
                    'short_description' => $woo['short_description'] ?? null,
                    'stock_status' => $woo['stock_status'] ?? 'instock',
                    'type_of_product' => $woo['type_of_product'] ?? null,
                    'brand' => $woo['brand_name'] ?? ($woo['brand'] ?? null),
                    'tax' => array_key_exists('tax', $woo) ? (bool) $woo['tax'] : null,
                ]
            );

            // ✅ TTL: لو اتعمل sync خلال ساعة → رجّع من DB بدون أي تحديثات ثقيلة
            if (
                $product->woo_synced_at &&
                Carbon::parse($product->woo_synced_at)->diffInSeconds(now()) < $this->ttlSeconds
            ) {
                return Product::with(['subCategories', 'images', 'variations.addons'])->find($product->id);
            }

            // ✅ images key now "images" (fallback to old "Images")
            $wooImages = collect($woo['images'] ?? ($woo['Images'] ?? []))->filter()->values()->all();

            // Images diff (بدون delete-all)
            if (! empty($wooImages)) {
                $existing = ProductImage::where('product_id', $product->id)->pluck('image')->all();

                $toDelete = array_diff($existing, $wooImages);
                $toInsert = array_diff($wooImages, $existing);

                if (! empty($toDelete)) {
                    ProductImage::where('product_id', $product->id)->whereIn('image', $toDelete)->delete();
                }

                foreach ($toInsert as $url) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image' => $url,
                    ]);
                }
            }

            // Variations upsert (مرة واحدة)
            // 3) Variations sync (delete removed + upsert new/updated)
            $wooVars = collect($woo['variations'] ?? [])->filter()->values();

            if ($wooVars->isNotEmpty()) {

                // ✅ skus from Woo (variation_id)
                $wooSkus = $wooVars
                    ->map(fn ($v) => (string) ($v['variation_id'] ?? ''))
                    ->filter(fn ($sku) => $sku !== '')
                    ->unique()
                    ->values()
                    ->all();

                // ✅ 1) delete old variations that no longer exist in Woo
                if (! empty($wooSkus)) {
                    Variation::where('product_id', $product->id)
                        ->whereNotIn('sku', $wooSkus)
                        ->delete();
                } else {
                    // لو Woo رجّع variations بس بدون variation_id (نادر) -> امسح الكل
                    Variation::where('product_id', $product->id)->delete();
                }

                // ✅ get existing types after deletion (to keep local type if you want)
                $existingTypesBySku = Variation::where('product_id', $product->id)
                    ->pluck('type', 'sku')
                    ->toArray();

                // ✅ 2) upsert current Woo variations
                $rows = $wooVars->map(function ($v) use ($product, $existingTypesBySku) {
                    $sku = (string) ($v['variation_id'] ?? '');
                    if ($sku === '') {
                        return null;
                    }

                    $regular = $this->toMoney($v['regular_price'] ?? null);
                    $sale = $this->toMoney($v['sale_price'] ?? null);
                    $price = $sale ?? $regular;

                    // type may exist in attributes pa_lense-type OR in "type"
                    $attrType = $v['attributes']['pa_lense-type'] ?? null;
                    $wooType = $v['type'] ?? null;

                    return [
                        'product_id' => $product->id,
                        'sku' => $sku,
                        'price' => $price,
                        'regular_price' => $regular,
                        'sale_price' => $sale,
                        'stock_status' => $v['stock_status'] ?? 'instock',
                        'image_url' => $v['image'] ?? null,

                        // ✅ choose type:
                        // option A) always take woo type/attr type:
                        // 'type' => ($wooType ?: $attrType),

                        // option B) keep existing local type if exists, else take woo:
                        'type' => $existingTypesBySku[$sku] ?? ($wooType ?: $attrType),

                        'updated_at' => now(),
                        'created_at' => now(),
                    ];
                })->filter()->values()->all();

                if (! empty($rows)) {
                    Variation::upsert(
                        $rows,
                        ['product_id', 'sku'],
                        ['price', 'regular_price', 'sale_price', 'stock_status', 'image_url', 'type', 'updated_at']
                    );

                    // هات variations بعد الـ upsert
                    $dbVariations = Variation::where('product_id', $product->id)
                        ->get()
                        ->keyBy('sku');

                    foreach ($wooVars as $v) {
                        $sku = (string) ($v['variation_id'] ?? '');
                        if ($sku === '') {
                            continue;
                        }

                        /** @var Variation|null $variation */
                        $variation = $dbVariations->get($sku);

                        if (! $variation) {
                            continue;
                        }

                        $variationType = (string) ($v['type'] ?? '');

                        // نحفظ addons فقط ل variation نوعه "نظر"
                        if ($variationType === 'نظر') {
                            $this->syncVariationAddons($variation, $v['addons'] ?? []);
                        } else {
                            // لو variation مش "نظر" نمسح أي addons قديمة له
                            VariationAddon::where('variation_id', $variation->id)->delete();
                        }
                    }
                }

                // ✅ 3) update product price fields based on variations after sync
                $vars = Variation::where('product_id', $product->id)->get();

                $minPrice = $vars->whereNotNull('price')->min('price');
                $maxRegular = $vars->whereNotNull('regular_price')->max('regular_price');
                $maxPrice = $vars->whereNotNull('price')->max('price');

                if ($minPrice !== null) {
                    $product->price = $minPrice;
                }
                if ($maxRegular !== null) {
                    $product->regular_price = $maxRegular;
                } elseif ($maxPrice !== null) {
                    $product->regular_price = $maxPrice;
                }

                $product->sale_price = null;
            } else {
                // ✅ لو Woo مفيهوش variations خالص => امسح variations المحلية (لو ده منطقي عندك)
                Variation::where('product_id', $product->id)->delete();
            }

            // all_price: حدّثه فقط لو فاضي + تجاهل ",,,,,,"
            if (empty($product->all_price) && ! empty($woo['price'])) {
                $normalized = $this->normalizeAllPriceCsv((string) $woo['price']);
                if ($normalized !== '') {
                    $product->all_price = $normalized;
                }
            }

            // ختم sync time
            $product->woo_synced_at = now();
            $product->save();

            return Product::with(['subCategories', 'images', 'variations.addons'])->find($product->id);
        });
    }

    private function toMoney($v): ?float
    {
        if ($v === '' || $v === null) {
            return null;
        }

        return (float) $v;
    }

    private function normalizeAllPriceCsv(string $csv): string
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', $csv))));
        $parts = array_values(array_filter($parts, fn ($x) => (float) $x > 0));

        return implode(',', $parts);
    }

    // new helper methods
    private function normalizeAddonType(string $title): ?string
    {
        $title = trim($title);

        if (str_contains($title, 'قياس النظر')) {
            return 'vision_power';
        }

        if (str_contains($title, 'الكمية')) {
            return 'quantity_price';
        }

        return null;
    }

    private function normalizeAddonSide(string $title): ?string
    {
        $title = trim($title);

        if (str_contains($title, 'يمين')) {
            return 'right';
        }

        if (str_contains($title, 'يسار')) {
            return 'left';
        }

        return null;
    }

    private function syncVariationAddons(Variation $variation, array $addons): void
    {
        $rows = [];

        foreach ($addons as $addon) {
            $title = (string) ($addon['title'] ?? '');
            $type = $this->normalizeAddonType($title);
            $side = $this->normalizeAddonSide($title);

            if (! $type || ! $side) {
                continue;
            }

            foreach (($addon['options'] ?? []) as $option) {
                $label = trim((string) ($option['label'] ?? ''));
                if ($label === '') {
                    continue;
                }

                $price = $this->toMoney($option['price'] ?? null);

                $rows[] = [
                    'variation_id' => $variation->id,
                    'side' => $side,
                    'addon_type' => $type,
                    'label' => $label,
                    'price' => $price,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // امسح القديم وأدخل الجديد لنفس الـ variation
        VariationAddon::where('variation_id', $variation->id)->delete();

        if (! empty($rows)) {
            VariationAddon::insert($rows);
        }
    }
}
