<?php

namespace App\Http\Resources\Refactor\Gate;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GateResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'desc' => $this->desc,
            'parent' =>(int) $this->parent,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            // 'status' => $this->status,
            // 'popular' => $this->popular,
            // 'suggestion' => $this->suggestion,
            // 'banners' => $this->banners->map(fn($banner) => asset('storage/' . $banner->image)),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
