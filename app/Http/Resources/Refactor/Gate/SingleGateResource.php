<?php

namespace App\Http\Resources\Refactor\Gate;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Refactor\Product\GatProductResource;
use App\Http\Resources\Refactor\Product\NewProductResource;

class SingleGateResource extends JsonResource
{
    protected $page;
    protected $perPage;

    public function __construct($resource, $page = 1, $perPage = 10)
    {
        parent::__construct($resource);
        $this->page = $page;
        $this->perPage = $perPage;
    }

    public function toArray(Request $request): array
    {

        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'desc' => $this->desc,
            'parent' => (int)$this->parent,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'banners' => $this->banners->map(fn($banner) => asset('storage/' . $banner->image)),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];

        if ($this->parent == 0) {
            // Get all products (first 10 instock)


            $data['category_id'] = optional($this->SubCategories->first())->id;

            $data['SubCategories'] = $this->SubCategories
                ?  $this->SubCategories->whereIn('id', [211, 137, 322])
                ->flatMap(function ($subCategory) {
                    return $subCategory->children
                         ->where('id', 211)
                         ->sortBy('priority')->map(function ($child) use ($subCategory) {
                        return [
                            'id' => $child->id,
                            'name' => $child->name,
                            'desc' => $child->desc,
                            'images' => $child->images->map(fn($image) => asset('storage/' . $image->image)),
                            'suggestions' => $child->products
                                ->intersect($subCategory->products)
                                ->filter(fn($product) => $product->stock_status === 'instock')
                                ->take(5)
                                ->map(fn($product) => new GatProductResource($product))
                                ->values(),
                            'brands' => $child->products
                                ->intersect($subCategory->products)
                                ->map(fn($product) => [
                                    'brand' => $product->brand,
                                    'brand_image' => $product->brand_image,
                                ]),
                        ];
                    });
                })
                ->unique('id')
                ->values()
                : [];
        } else {


            $data['SubCategories'] = $this->SubCategories
                ? $this->SubCategories
                 ->where('id', 211)->sortBy('priority')->map(function ($SubCategory) {
                    $uniqueProducts = $SubCategory->products->unique('id');
                    return [
                        'id' => $SubCategory->id,
                        'name' => $SubCategory->name,
                        'desc' => $SubCategory->desc,
                        'images' => $SubCategory->images->map(fn($image) => asset('storage/' . $image->image)),
                        'suggestions' => $SubCategory->products
                            ->filter(fn($product) => $product->stock_status === 'instock')
                            ->take(5)
                            ->map(fn($product) => new GatProductResource($product))
                            ->values(),

                        'brands' => $SubCategory->products
                            ->unique('brand')
                            ->filter(fn($product) => !empty($product->brand))
                            ->map(function ($product) {
                                return [
                                    'name' => $product->brand,
                                    'brand_image' => $product->brand_image,
                                ];
                            })->unique()->values(),
                    ];
                })
                : null;
        }


        return $data;
    }
}
