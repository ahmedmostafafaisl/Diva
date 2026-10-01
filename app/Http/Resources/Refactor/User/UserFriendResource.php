<?php

namespace App\Http\Resources\Refactor\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserFriendResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'friend' => [
                'id' => $this->friend->id,
                'username' => $this->friend->username,
                'email' => $this->friend->email,
                'phone' => $this->friend->phone,
            ],
            'added_at' => $this->created_at,
        ];
    }
}
