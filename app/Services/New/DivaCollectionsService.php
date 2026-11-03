<?php

namespace App\Services\New;

use App\Models\WishlistProduct;

class DivaCollectionsService
{
    /** name => collection key, e.g. ['WOODY' => 'diva', 'STEEL' => 'chic_diva'] */
    private array $nameToKey = [];

    public function __construct(?array $collections = null)
    {
        $collections ??= config('diva_collections.collections', []);

        foreach ($collections as $key => $collection) {
            foreach ($collection['names'] ?? [] as $name) {
                $this->nameToKey[strtoupper(trim($name))] = $key;
            }
        }
    }

    /**
     * Find the collection of one product from its SKU.
     * Checks the SKU parts from the end, so "DIVA-VAR-SEOUL-STEEL" matches STEEL.
     */
    public function collectionKeyFor(?string $sku): ?string
    {
        if (! $sku) {
            return null;
        }

        $parts = array_reverse(preg_split('/[-_\s]+/', strtoupper(trim($sku))));

        foreach ($parts as $part) {
            if (isset($this->nameToKey[$part])) {
                return $this->nameToKey[$part];
            }
        }

        return null;
    }

    /**
     * The short lens name from the SKU, e.g. "DIVA-VAR-WOODY" => "WOODY".
     */
    public function lensNameFor(?string $sku): ?string
    {
        if (! $sku) {
            return null;
        }

        $parts = preg_split('/[-_\s]+/', strtoupper(trim($sku)));

        foreach (array_reverse($parts) as $part) {
            if (isset($this->nameToKey[$part])) {
                return $part;
            }
        }

        return end($parts) ?: null;
    }

    /**
     * Random products that stay the same for the whole month.
     * Seeded with the year+month, so every user sees the same picks
     * and they change automatically on the 1st of each month.
     */
    public function productsOfTheMonth(array $products, int $limit, ?string $month = null): array
    {
        if ($limit <= 0 || empty($products)) {
            return [];
        }

        $month ??= now()->format('Y-m');

        // Sort by id first so the result doesn't depend on the API order
        usort($products, fn ($a, $b) => ($a['id'] ?? 0) <=> ($b['id'] ?? 0));

        // Seeded shuffle (Fisher–Yates) without touching the global random state
        $seed = crc32('diva-month-'.$month);
        for ($i = count($products) - 1; $i > 0; $i--) {
            $seed = ($seed * 1103515245 + 12345) & 0x7FFFFFFF;
            $j = $seed % ($i + 1);
            [$products[$i], $products[$j]] = [$products[$j], $products[$i]];
        }

        return array_slice($products, 0, $limit);
    }

    /**
     * Wishlist counts for these product ids: [product_id => likes].
     */
    public function likesCount(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        return WishlistProduct::query()
            ->whereIn('product_id', $productIds)
            ->selectRaw('product_id, COUNT(*) as likes')
            ->groupBy('product_id')
            ->pluck('likes', 'product_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /**
     * Most liked first. If fewer products have likes than $limit,
     * the list is filled with the remaining products in their original order.
     */
    public function mostPopular(array $products, array $likes, int $limit): array
    {
        if ($limit <= 0 || empty($products)) {
            return [];
        }

        $indexed = [];
        foreach (array_values($products) as $i => $p) {
            $indexed[] = ['p' => $p, 'i' => $i, 'likes' => $likes[$p['id'] ?? 0] ?? 0];
        }

        usort($indexed, fn ($a, $b) => [$b['likes'], $a['i']] <=> [$a['likes'], $b['i']]);

        return array_map(fn ($row) => $row['p'], array_slice($indexed, 0, $limit));
    }
}
