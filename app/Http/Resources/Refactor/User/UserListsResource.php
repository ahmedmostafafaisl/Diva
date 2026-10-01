<?php

namespace App\Http\Resources\Refactor\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Refactor\Product\ProductResource;

class UserListsResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subcategory_id' => $this->subcategory_id,
            'name' => $this->subcategory->name ?? 'غير معروف',

        ];
    }
}
