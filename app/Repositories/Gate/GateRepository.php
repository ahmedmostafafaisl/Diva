<?php

namespace App\Repositories\Gate;

use App\Models\Gate;
use App\Models\GateBanner;
use App\Repositories\Interfaces\GateRepositoryInterface;
use Illuminate\Support\Facades\Storage;

class GateRepository implements GateRepositoryInterface
{
    public function all()
    {
        return Gate::findOrFail(1)->load('banners', 'SubCategories.images', 'SubCategories.children.images');
    }

    public function find(int $id)
    {
        return Gate::with([
            'banners',

            'SubCategories.images',
            'SubCategories.children.images',
        ])->findOrFail($id);
    }

    public function create(array $data)
    {
        if (isset($data['image']) && $data['image']->isValid()) {
            $image = $data['image'];
            $fileName = uniqid().'.'.$image->getClientOriginalExtension();
            $rut = 'Gate/images';
            $image_path = $image->storeAs($rut, $fileName, 'public');
            $data['image'] = $image_path;
        }
        $gate = Gate::create($data);

        if (isset($data['banners']) && is_array($data['banners'])) {
            foreach ($data['banners'] as $bannerImage) {
                if ($bannerImage instanceof \Illuminate\Http\UploadedFile) {
                    $imagePath = $this->storeImage($bannerImage, 'Gate/Banners');
                    GateBanner::create([
                        'gate_id' => $gate->id,
                        'image' => $imagePath,
                    ]);
                }
            }
        }

        return $gate;
    }

    public function update(Gate $gate, array $data)
    {
        if (isset($data['image']) && $data['image']->isValid()) {
            $image = $data['image'];
            $fileName = uniqid().'.'.$image->getClientOriginalExtension();
            $rut = 'Gate/images';
            $image_path = $image->storeAs($rut, $fileName, 'public');
            $data['image'] = $image_path;
        }
        $gate->update($data);

        if (isset($data['banners']) && is_array($data['banners'])) {
            $oldBanners = $gate->banners;
            foreach ($oldBanners as $banner) {
                Storage::disk('public')->delete($banner->image);
                $banner->delete();
            }
            foreach ($data['banners'] as $bannerImage) {
                if ($bannerImage instanceof \Illuminate\Http\UploadedFile) {
                    $imagePath = $this->storeImage($bannerImage, 'Gate/Banners');
                    GateBanner::create([
                        'gate_id' => $gate->id,
                        'image' => $imagePath,
                    ]);
                }
            }
        }

        return $gate->load('banners');
    }

    public function delete(int $id): bool
    {
        $gate = Gate::find($id);

        return $gate ? $gate->delete() : false;
    }

    private function storeImage($image, $path)
    {
        $fileName = uniqid().'.'.$image->getClientOriginalExtension();

        return $image->storeAs($path, $fileName, 'public');
    }
}
