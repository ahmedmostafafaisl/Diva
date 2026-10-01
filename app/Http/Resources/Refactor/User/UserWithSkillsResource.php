<?php

namespace App\Http\Resources\Refactor\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserWithSkillsResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,

            'skills' => SkillResource::collection($this->skills),
        ];
    }
}
