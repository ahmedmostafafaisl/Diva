<?php

namespace App\Repositories\Banner;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Interfaces\BannerRepositoryInterface;

class BannerRepository implements BannerRepositoryInterface
{
    public function getAllBanners()
    {
        return Banner::all();
    }

    public function getBannerById($id)
    {
        return Banner::findOrFail($id);
    }

    public function createBanner($data)
    {

        if (isset($data['banner_image'])) {
            $image = $data['banner_image'];
            $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
            $rut = 'Banners/' . $data['type'];
            $image_path = $image->storeAs($rut, $fileName, 's3');

            $data['banner_image'] = $image_path;
        }
        return Banner::create($data);
    }

    public function updateBanner($id,  $data)
    {
        $banner = Banner::findOrFail($id);
        if (isset($data['banner_image'])) {
            // if ($banner->banner_image) {
            //     Storage::disk('s3')->delete($banner->banner_image);
            // }
            $image = $data['banner_image'];
            $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
            $rut = 'Banners/' . $data['type'];
            $image_path = $image->storeAs($rut, $fileName, 's3');

            $data['banner_image'] = $image_path;
        }
        $banner->update($data);
        return $banner;
    }

    public function deleteBanner($id)
    {
        try {
            $totalBanners = Banner::count();
            //Log::info("Total banners before deletion: $totalBanners");

            if ($totalBanners <= 1) {
                $lastBanner = Banner::first();
                if ($lastBanner->status != 'active') {
                    $lastBanner->status = 'active';
                    $lastBanner->save();
                    // Log::info("Last banner status updated to active");
                }

                return response()->json([
                    'message' => 'The last banner cannot be deleted to avoid affecting the mobile app interface.',
                    'status' => 400
                ], 400);
            }

            $banner = Banner::findOrFail($id);

            if ($banner->banner_image) {
                Storage::disk('public')->delete($banner->banner_image);
            }

            $banner->delete();

            $remainingBanners = Banner::count();
            //Log::info("Total banners after deletion: $remainingBanners");

            if ($remainingBanners == 1) {
                $lastBanner = Banner::first();
                //Log::info("Last banner status before update: " . $lastBanner->status);
                if ($lastBanner->status != 'active') {
                    $lastBanner->status = 'active';
                    $lastBanner->save();
                    //Log::info("Last banner status updated to active");
                }
            }

            return response()->json(['message' => 'Banner Deleted Successfully', 'banner' => $banner], 200);
        } catch (\Exception $e) {
            //Log::error("Error deleting banner: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete banner.',
                'errors' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    public function getAllActiveBanners()
    {
        return Banner::where('status', 'active')->where('to', '>=', Carbon::now())->with('service', 'package')->get();
    }
}
