<?php

namespace App\Repositories\SubCategory;


use App\Models\SubCategory;
use App\Models\SubCategoryImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Interfaces\SubCategoryRepositoryInterface;

class SubCategoryRepository implements SubCategoryRepositoryInterface
{
    public function getAll()
    {
        return SubCategory::with('images')->orderBy('priority', 'asc')->get();
    }

    public function findById($id)
    {
        return SubCategory::with('images')->findOrFail($id);
    }

    public function create(array $data)
    {
        DB::beginTransaction();

        try {


            $subCategory = SubCategory::create($data);
            if (isset($data['images'])) {
                foreach ($data['images'] as $image) {
                    $path = $image->store('subcategory_images', 'public');
                    SubCategoryImage::create(['sub_category_id' => $subCategory->id, 'image' => $path]);
                }
            }
            DB::commit();
            return $subCategory;
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception("Error creating sub-category: " . $e->getMessage());
        }
    }

    public function update($id, array $data)
    {
        DB::beginTransaction();

        try {
            $subCategory = SubCategory::findOrFail($id);
            $subCategory->update($data);
            if (isset($data['images'])) {
                $subCategory->images()->delete();
                foreach ($data['images'] as $image) {
                    $path = $image->store('subcategory_images', 'public');
                    SubCategoryImage::create(['sub_category_id' => $subCategory->id, 'image' => $path]);
                }
            }
            DB::commit();
            return $subCategory;
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception("Error updating sub-category: " . $e->getMessage());
        }
    }

    public function delete($id)
    {
        $subCategory = SubCategory::findOrFail($id);

        if ($subCategory->images()->count() > 0) {
            foreach ($subCategory->images as $image) {
                Storage::disk('public')->delete($image->image_path);
            }
        }

        return $subCategory->delete();
    }

    private function storeImage($image)
    {
        $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
        return $image->storeAs('SubCategory/images', $fileName, 'public');
    }
}
