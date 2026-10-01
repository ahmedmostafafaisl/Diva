<?php

namespace App\Http\Resources\Refactor\SubCategory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Refactor\Product\NewProductResource;



class SingleSubCategoryResource extends JsonResource
{
    protected $page;
    protected $perPage;

    protected $category_id;

    public function __construct($resource, $page = 1, $perPage = 10, $category_id = null)
    {
        parent::__construct($resource);
        $this->page = $page;
        $this->perPage = $perPage;
        $this->category_id = $category_id;
    }

    public function toArray(Request $request): array
    {
        // Get products for current subcategory
        $subCategoryProducts = $this->products ?? collect();

        // Filter products based on parent subcategory if category_id is provided
        if ($this->category_id) {
            $parentSubCategory = \App\Models\SubCategory::find($this->category_id);
            $parentProducts = $parentSubCategory?->products ?? collect();
            $subCategoryProducts = $subCategoryProducts->intersect($parentProducts);
        }

        // Filter instock products
  $allProducts = $subCategoryProducts
            ->filter(fn($product) => $product->type_of_product === 'Lenses')
            ->map(fn($product) => new NewProductResource($product));

        // Paginate products manually
        $totalProducts = $allProducts->count();
        $totalPages = ceil($totalProducts / $this->perPage);
        $paginatedProducts = $allProducts->forPage($this->page, $this->perPage);

        // Brands & plus categories from filtered products
        $brands = $subCategoryProducts->pluck('brand')->reject(fn($name) => empty($name))->unique()->values()->toArray();
        $new_brands = $subCategoryProducts
            ->filter(fn($product) => !empty($product->brand)) // Skip empty brands
            ->unique('brand') // Avoid brand duplicates
            ->map(function ($product) {
                return [
                    'name' => $product->brand,
                    'image' => $product->brand_image, // adjust this if needed
                ];
            })
            ->values()
            ->toArray();
        $plus = $subCategoryProducts->pluck('plus_cat')->reject(fn($name) => empty($name))->unique()->values()->toArray();
        $plus = array_unique(array_merge(['الترتيب', 'العلامات التجاريه'], $plus));

        return [
            'id' => $this->id,
            'gate_id' =>(int) $this->gate_id,
            'name' => (string)$this->name,
            'desc' =>(string) $this->desc,
            'images' => $this->images->map(fn($image) => asset('storage/' . $image->image)),
            'brands' => $brands,
            'new_brands' => $new_brands,
            'plus' => $plus,
            'products' => [
                'data' => $paginatedProducts->values(),
                'current_page' => (int) $this->page,
                'total_pages' => (int) $totalPages,
            ],
        ];
    }
}
