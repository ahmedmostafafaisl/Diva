<?php

namespace App\Http\Resources\Refactor\Review;

use App\Http\Resources\Refactor\User\Client\ClientResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => new ClientResource($this->user),
            'rate' => $this->rate,
            'notes' => $this->notes,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
