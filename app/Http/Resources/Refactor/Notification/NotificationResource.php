<?php

namespace App\Http\Resources\Refactor\Notification;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id,
            'user' => $this->user,
            'title'   => $this->title,
            'body'    => $this->body,
            'read'    => $this->read,
            'created_at' => $this->created_at,
        ];
    }
}
