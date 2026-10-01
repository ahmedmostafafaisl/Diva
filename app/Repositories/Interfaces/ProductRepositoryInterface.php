<?php

namespace App\Repositories\Interfaces;

use App\Models\Product;
use App\Services\New\WpProductsClient;
use App\Services\Sync\WooProductSyncService;

interface ProductRepositoryInterface
{
    public function getAll();

    public function findById(int $id): ?Product;

    public function store(array $data): Product;

    public function update(Product $product, array $data): Product;

    public function delete(Product $product): bool;

    // public function searchProducts($search, $subCategory_id, $sort);
    public function searchProducts($searchTerm, $subCategoryId = null, $sort = null, $minPrice = null, $maxPrice = null, $brand = null, $type = null, $plus = null, $attributeValues = []);

    public function searchProducts2(
        $searchTerm,
        $subCategoryId = null,
        $sort = null,
        $minPrice = null,
        $maxPrice = null,
        $brand = null,
        $type = null,
        $plus = null,
        $attributeValues = [],
        ?WpProductsClient $wp = null,
        ?WooProductSyncService $sync = null,
        $perPage = 20
    );

    public function getSubCategoryProducts($perPage, $page, $subCategory_id);

    // brands
    public function getAllUniqueBrands();

    public function getProductsByBrand(string $brand);
}
