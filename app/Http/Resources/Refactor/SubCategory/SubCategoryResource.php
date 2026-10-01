<?php

namespace App\Http\Resources\Refactor\SubCategory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubCategoryResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gate_id' => $this->gate_id,
            'name' => $this->name,
            'desc' => $this->desc,
            'parent' => $this->parent,
            'count' => $this->count,
            'status' => $this->status,
            'images' => $this->images->map(fn($image) => asset('storage/' . $image->image)),
        ];
    }
}
