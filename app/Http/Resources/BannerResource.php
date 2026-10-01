<?php

namespace App\Http\Resources;

use App\Http\Resources\Refactor\Package\PackageResource;
use App\Http\Resources\Refactor\User\ServiceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'banner_image' => $this->banner_image,
            'reference_id' => $this->reference_id,
            'type' => $this->type,
            'duration' => $this->duration,
            'from' => $this->from,
            'to' => $this->to,
            'status' => $this->status,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),

        ];
    }
}
