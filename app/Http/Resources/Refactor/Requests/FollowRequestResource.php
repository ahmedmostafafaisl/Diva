<?php

namespace App\Http\Resources\Refactor\Requests;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowRequestResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'follower_id' => $this->follower_id,
            'followed_id' => $this->followed_id,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
