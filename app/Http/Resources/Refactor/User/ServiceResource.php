<?php

namespace App\Http\Resources\Refactor\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar ?? null,
            'name_en' => $this->name_en ?? null,
            'price' => $this->price ?? null,
            'duration' => $this->duration ?? null,
            'description_ar' => $this->description_ar ?? null,
            'description_en' => $this->description_en ?? null,
            'service_image' => $this->getImageUrl($this->service_image),
            'featured' => $this->featured ?? null,
            'status' => $this->status ?? null,
            'priority' => $this->priority ?? null,
            'tabby' => $this->tabby ?? null,
            'tamara' => $this->tamara ?? null,
            'online_payment' => $this->online_payment ?? null,
            'apple_pay' => $this->apple_pay ?? null,
            'cover_image' => $this->getImageUrl($this->cover_image),
            'dy_item_number' => $this->dy_item_number ?? null,
            'dy_product_name' => $this->dy_product_name ?? null,
            'skills' => SkillResource::collection($this->whenLoaded('skills')),
            'has_skills' => $this->skills()->exists(), // Check if service has skills
            'created_at' => $this->created_at ? $this->created_at->format('d-m-Y H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('d-m-Y H:i:s') : null,
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
