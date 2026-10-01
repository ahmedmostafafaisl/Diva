<?php

namespace App\Http\Resources\Refactor\Sug;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Refactor\Product\ProductResource;
use App\Http\Resources\Refactor\SubCategory\SubCategoryResource;

class SubCategorySuggestionResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subCategory' => new SubCategoryResource($this->subCategory),
            'product' => new ProductResource($this->product),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
