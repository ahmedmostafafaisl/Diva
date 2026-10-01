<?php

namespace App\Http\Resources\Refactor\Banner;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Refactor\User\ServiceResource;
use App\Http\Resources\Refactor\Package\PackageResource;

class BannerResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'banner_image' => $this->getImageUrl($this->banner_image),
            'reference_id' => $this->reference_id,
            'type' => $this->type,
            'from' => $this->from,
            'to' => $this->to,
            'duration' => $this->duration,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'Package' => $this->type === 'package' ? new PackageResource($this->package) : null,
            'Service' => $this->type === 'service' ? new ServiceResource($this->service) : null,
        ];
    }

    private function getImageUrl($path)
    {
        if ($path && Storage::disk('s3')->exists($path)) {
            return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(100));
        }
        return null;
    }
}
