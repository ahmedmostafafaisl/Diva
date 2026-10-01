<?php

namespace App\Http\Resources\Refactor\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Refactor\User\SingleEmployeeResource;

class UserWithAttachResource extends JsonResource
{

    public function toArray(Request $request): array
    {


        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'status' => $this->status,
            "following_id" => $this->following_id,
            'role' => $this->role,
            'created_at' => $this->created_at,
        ];
    }
}
