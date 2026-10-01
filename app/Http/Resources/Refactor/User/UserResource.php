<?php

namespace App\Http\Resources\Refactor\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'type' => $this->type,
            'phone' => $this->phone,
            'second_phone' => $this->second_phone,
            'is_verified' => $this->is_verified,
                        'is_online' => $this->is_online,

            'status' => $this->status,
            'email' => $this->email,
            'fcm_token' => $this->fcm_token,
            'following_id' => $this->following_id,
            'role' => $this->role,
            'referral_code' => $this->referral_code,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'districts' => $this->districts  ?? null,
        ];
    }
}
