<?php

namespace App\Repositories\Product;

use App\Helper\ApiResponseHelper;
use App\Http\Resources\Refactor\Product\NewProductResource;
use App\Http\Resources\Refactor\Product\SingleProductResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SubCategory;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Services\New\WpProductsClient;
use App\Services\Sync\WooProductSyncService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductRepository implements ProductRepositoryInterface
{
    use ApiResponseHelper;

    public function getAll()
    {
        return Product::with('subCategories', 'images')->get();
    }

    public function findById(int $id): ?Product
    {
        return Product::with('subCategories', 'images', 'variations')->find($id);
    }

    public function store(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create($data);
            if (isset($data['sub_categories'])) {
                $product->subCategories()->sync($data['sub_categories']);
            }
            if (! empty($data['images'])) {
                foreach ($data['images'] as $image) {
                    $fileName = uniqid().'.'.$image->getClientOriginalExtension();
                    $rut = 'Product/images';
                    $imagePath = $image->storeAs($rut, $fileName, 'public');
                    ProductImage::create(['product_id' => $product->id, 'image' => $imagePath]);
                }
            }

            return $product->load('images');
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product->update($data);

            if (! empty($data['images'])) {
                // Delete old images from storage
                foreach ($product->images as $oldImage) {
                    Storage::disk('public')->delete($oldImage->image);
                }

                // Remove old images from DB
                $product->images()->delete();

                // Add new images
                foreach ($data['images'] as $image) {
                    $fileName = uniqid().'.'.$image->getClientOriginalExtension();
                    $rut = 'Product/images';
                    $imagePath = $image->storeAs($rut, $fileName, 'public');
                    ProductImage::create(['product_id' => $product->id, 'image' => $imagePath]);
                }
            }

            if (isset($data['sub_categories'])) {
                $product->subCategories()->sync($data['sub_categories']);
            }

            return $product->load('images');
        });
    }

    public function delete(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            // Delete images from storage
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->image);
            }

            // Delete related images from database
            $product->images()->delete();

            return $product->delete();
        });
    }

    public function searchProducts($searchTerm, $subCategoryId = null, $sort = null, $minPrice = null, $maxPrice = null, $brand = null, $typeOfProduct = null, $plus = null, $attributeValues = [])
    {
        // dd($searchTerm, $subCategoryId, $sort, $minPrice, $maxPrice, $brand, $typeOfProduct);
        $query = Product::with(['subCategories', 'attributes']);

        // Search by name or description
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('desc', 'like', "%{$searchTerm}%");
            });
        }
        // 🔍 Search by multiple attribute values (array)
        if (! empty($attributeValues) && is_array($attributeValues)) {
            $query->whereHas('attributes', function ($q) use ($attributeValues) {
                $q->where(function ($subQuery) use ($attributeValues) {
                    foreach ($attributeValues as $val) {
                        $subQuery->orWhere('value', 'like', "%{$val}%");
                    }
                });
            });
        }

        // Filter by subcategory
        if ($subCategoryId) {
            $query->whereHas('subCategories', fn ($q) => $q->where('sub_categories.id', $subCategoryId));
        }

        // **Fix price filtering (cast to decimal)**
        if (! is_null($minPrice) && ! is_null($maxPrice)) {
            $query->whereRaw('CAST(price AS DECIMAL(10,2)) BETWEEN ? AND ?', [$minPrice, $maxPrice]);
        } elseif (! is_null($minPrice)) {
            $query->whereRaw('CAST(price AS DECIMAL(10,2)) >= ?', [$minPrice]);
        } elseif (! is_null($maxPrice)) {
            $query->whereRaw('CAST(price AS DECIMAL(10,2)) <= ?', [$maxPrice]);
        }

        // **Fix brand filtering**
        if ($brand) {
            $query->where('brand', 'like', "%{$brand}%"); // Ensure brand filtering works
        }

        // **Fix type_of_product filtering**
        if ($typeOfProduct) {
            $query->where('type_of_product', 'like', "%{$typeOfProduct}%");
        }

        if ($plus) {
            $query->where('plus_cat', 'like', "%{$plus}%");
        }

        // **Sorting Logic**
        if ($sort === 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('price', 'desc');
        } elseif ($sort === 'created_at_asc') {
            $query->orderBy('created_at', 'asc');
        } elseif ($sort === 'created_at_desc') {
            $query->orderBy('created_at', 'desc'); // Default sorting
        }
        // $query->where('stock_status', 'instock');

        $products = $query->get();

        return $this->setCode(200)
            ->setData(SingleProductResource::collection($products))
            ->setMessage('Successfully retrieved products')
            ->send();
    }

    public function searchProducts2(
        $searchTerm,
        $subCategoryId = null,
        $sort = null,
        $minPrice = null,
        $maxPrice = null,
        $brand = null,
        $typeOfProduct = null,
        $plus = null,
        $attributeValues = [],
        ?WpProductsClient $wp = null,
        ?WooProductSyncService $sync = null,
        $perPage = 20
    ) {
        $query = Product::with(['subCategories', 'attributes', 'images', 'variations']);

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('desc', 'like', "%{$searchTerm}%");
            });
        }

        if (! empty($attributeValues) && is_array($attributeValues)) {
            $query->whereHas('attributes', function ($q) use ($attributeValues) {
                $q->where(function ($subQuery) use ($attributeValues) {
                    foreach ($attributeValues as $val) {
                        $subQuery->orWhere('value', 'like', "%{$val}%");
                    }
                });
            });
        }
        $subCategoryId = $subCategoryId ?? 211;

        if ($subCategoryId) {
            $query->whereHas('subCategories', fn ($q) => $q->where('sub_categories.id', $subCategoryId));
        }

        if (! is_null($minPrice) && ! is_null($maxPrice)) {
            $query->whereRaw('CAST(price AS DECIMAL(10,2)) BETWEEN ? AND ?', [$minPrice, $maxPrice]);
        } elseif (! is_null($minPrice)) {
            $query->whereRaw('CAST(price AS DECIMAL(10,2)) >= ?', [$minPrice]);
        } elseif (! is_null($maxPrice)) {
            $query->whereRaw('CAST(price AS DECIMAL(10,2)) <= ?', [$maxPrice]);
        }

        if ($brand) {
            $query->where('brand', 'like', "%{$brand}%");
        }
        if ($typeOfProduct) {
            $query->where('type_of_product', 'like', "%{$typeOfProduct}%");
        }
        if ($plus) {
            $query->where('plus_cat', 'like', "%{$plus}%");
        }

        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'created_at_asc' => $query->orderBy('created_at', 'asc'),
            'created_at_desc' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('id', 'desc'),
        };

        // ✅ paginate
        $paginator = $query->paginate($perPage);

        // ✅ live sync for current page only (TTL 1 hour)
        foreach ($paginator->items() as $p) {
            $needsSync = ! $p->woo_synced_at || Carbon::parse($p->woo_synced_at)->lt(now()->subHour());

            if ($needsSync) {
                $woo = $wp->singleProductV2((int) $p->id);
                if (! empty($woo)) {
                    $sync->syncSingleProductV2((int) $p->id, $woo);
                }
            }
        }

        // ✅ reload updated items from DB (preserve same order)
        $ids = collect($paginator->items())->pluck('id')->all();

        $fresh = Product::with(['subCategories', 'attributes', 'images', 'variations'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $updatedItems = collect($paginator->items())
            ->map(fn ($p) => $fresh[$p->id] ?? $p)
            ->values();

        return $this->setCode(200)->setData([
            'data' => SingleProductResource::collection($updatedItems),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'total_pages' => $paginator->lastPage(),
        ])->setMessage('Successfully retrieved products')->send();
    }

    public function getSubCategoryProducts($perPage, $page, $subCategory_id)
    {
        $subCategory = SubCategory::find($subCategory_id);

        if (! $subCategory) {
            return $this->setCode(404)->setData([])->setMessage('SubCategory not found')->send();
        }
        $products = $subCategory->products()->paginate($perPage, ['*'], 'page', $page);

        return $this->setCode(code: 200)->setData([
            'products' => NewProductResource::collection($products->items()),
            'Page_Number' => $products->currentPage(),
            'Number_Of_Pages' => $products->lastPage(),
        ])->setMessage('Product Created successfully')->send();
    }

    // brands
    public function getAllUniqueBrands()
    {
        return Product::whereNotNull('brand')
            ->groupBy('brand')
            ->select('brand', \DB::raw('MIN(brand_image) as brand_image'))
            ->get();
    }

    public function getProductsByBrand(string $brand)
    {
        $products = Product::where('brand', $brand)->with('images')->get();

        // Get the brand image (first or any with that brand)
        $brandImage = Product::where('brand', $brand)->value('brand_image');

        return response()->json([
            'brand' => $brand,
            'brand_image' => $brandImage,
            'products' => $products,
        ]);
    }
}
