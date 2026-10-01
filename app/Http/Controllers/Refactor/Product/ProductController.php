<?php

namespace App\Http\Controllers\Refactor\Product;

use App\Helper\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Refactor\Product\ProductStoreRequest;
use App\Http\Requests\Refactor\Product\ProductUpdateRequest;
use App\Http\Resources\Refactor\Product\NewProductResource;
use App\Http\Resources\Refactor\Product\SingleProductResource;
use App\Http\Resources\Refactor\Product\SubProductResource;
use App\Models\Product;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Services\New\WpProductsClient;
use App\Services\Sync\WooProductSyncService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponseHelper;

    protected ProductRepositoryInterface $repository;

    public function __construct(ProductRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index()
    {
        $products = $this->repository->getAll();

        return $this->setCode(code: 200)->setData(NewProductResource::collection($products))->setMessage('success')->send();
    }

    public function store(ProductStoreRequest $request)
    {
        $product = $this->repository->store($request->validated());

        return $this->setCode(code: 200)->setData(new NewProductResource($product))->setMessage('Product Created successfully')->send();
    }

    public function show(int $id)
    {
        $product = $this->repository->findById($id);

        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return $this->setCode(code: 200)->setData(new SubProductResource($product))->setMessage('success')->send();
    }

    public function update(ProductUpdateRequest $request, Product $product)
    {
        $updatedProduct = $this->repository->update($product, $request->validated());

        return $this->setCode(code: 200)->setData(new SingleProductResource($updatedProduct))->setMessage('Product Updated successfully')->send();
    }

    public function destroy(Product $product)
    {
        $this->repository->delete($product);

        return $this->setCode(code: 200)->setData([])->setMessage('Product Deleted successfully')->send();
    }

    public function searchProducts(Request $request)
    {
        $data = $request->validate([
            'search' => 'nullable|string|min:2',
            'subCategory_id' => 'nullable|exists:sub_categories,id',
            'sort_by' => 'nullable|string|in:price_asc,price_desc,created_at_asc,created_at_desc',
            'minPrice' => 'nullable',
            'maxPrice' => 'nullable',
            'brand' => 'nullable|string|min:2',
            'type' => 'nullable|string|min:2',
            'plus' => 'nullable|string|min:2',
            'attributeValues' => 'nullable|array',
            'attributeValues.*' => 'nullable|string|min:2',

        ]);

        return $products = $this->repository->searchProducts(
            $request->search,
            $request->subCategory_id,
            $request->sort_by,
            $request->minPrice,
            $request->maxPrice,
            $request->brand,
            $request->type,
            $request->plus,
            $request->attributeValues
        );
    }

    public function searchProducts2(Request $request, WpProductsClient $wp, WooProductSyncService $sync)
    {
        $data = $request->validate([
            'search' => 'nullable|string|min:2',
            'subCategory_id' => 'nullable|exists:sub_categories,id',
            'sort_by' => 'nullable|string|in:price_asc,price_desc,created_at_asc,created_at_desc',
            'minPrice' => 'nullable',
            'maxPrice' => 'nullable',
            'brand' => 'nullable|string|min:2',
            'type' => 'nullable|string|min:2',
            'plus' => 'nullable|string|min:2',
            'attributeValues' => 'nullable|array',
            'attributeValues.*' => 'nullable|string|min:2',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        return $this->repository->searchProducts2(
            $request->search,
            $request->subCategory_id,
            $request->sort_by,
            $request->minPrice,
            $request->maxPrice,
            $request->brand,
            $request->type,
            $request->plus,
            $request->attributeValues ?? [],
            $wp,
            $sync,
            (int) ($request->per_page ?? 20)
        );
    }

    public function getSubCategoryProducts(Request $request, $subCategory_id)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('current_page', 1);

        return $products = $this->repository->getSubCategoryProducts($perPage, $page, $subCategory_id);
    }

    // brands
    public function getUniqueBrands()
    {

        $brands = $this->repository->getAllUniqueBrands();

        return response()->json([
            'brands' => $brands,
        ]);
    }

    public function getProductsByBrand(Request $request)
    {
        $request->validate([
            'brand' => 'required|string',
        ]);

        return $products = $this->repository->getProductsByBrand($request->brand);

    }

    public function showWithWooSync(int $id, WpProductsClient $wp, WooProductSyncService $sync)
    {
        // ✅ fetch from DB first
        $product = $this->repository->findById($id);

        if ($product && $product->woo_synced_at && \Carbon\Carbon::parse($product->woo_synced_at)->gt(now()->subHour())) {
            return $this->setCode(200)
                ->setData(new SubProductResource($product))
                ->setMessage('success')
                ->send();
        }

        // call Woo only if needed
        $woo = $wp->singleProductV2($id);
        $product = $sync->syncSingleProductV2($id, $woo) ?: $product;

        if (! $product) {
            return $this->setCode(404)->setData([])->setMessage('Product not found')->send();
        }

        return $this->setCode(200)
            ->setData(new SubProductResource($product))
            ->setMessage('success')
            ->send();
    }
}
