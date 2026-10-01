<?php

namespace App\Http\Controllers;

use App\Http\Resources\BannerResource;
use App\Models\Banner;
use App\Models\Package;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class BannerController extends Controller
{
    public function getAllBanners()
    {
        $banners = Banner::all();
        return response(['banners' => BannerResource::collection($banners)], 200);
    }



    public function getAllActiveBanners()
    {
        $banners = Banner::where('to', '>=', Carbon::now())->get();
        foreach ($banners as $banner) {
            if ($banner->type == 'package') {
                $package = Package::where('id', '=', $banner->reference_id)->with('items')->first();
                $banner['package'] = $package;
                $banner['service'] = null;
            } elseif ($banner->type == 'service') {
                $service = Service::where('id', '=', $banner->reference_id)->first();
                $banner['service'] = $service;
                $banner['package'] = null;
            }
        }
        return response(['banners' => $banners], 200);
    }

    public function getSingleBanner($id)
    {
        $banner = Banner::find($id);
        return response(['banner' => new BannerResource($banner)], 200);
    }


    public function storeBanner(Request $request)
    {
        $fields = $request->validate([
            'banner_image' => 'required|mimes:jpg,png,jpeg,gif',
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'type' => 'required|string:in:package,service',
            'reference_id' => 'required|integer',
            'duration' => 'required|integer',
            'status' => 'required|string',
        ]);

        if ($request->hasFile('banner_image')) {
            $fields['banner_image'] = $request->file('banner_image');
            $fileName = uniqid() . '.' . $fields['banner_image']->getClientOriginalExtension();
            $rut = 'banners';
            $image_path = $fields['banner_image']->storeAs($rut, $fileName, 'public');
            $fields['banner_image'] = $image_path;
        }

        $banner = Banner::create($fields);

        return response()->json(['message' => "Banner Posted Successfully", 'banner' => new BannerResource($banner)], 200);
    }


    public function updateBanner(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);
        $fields = $request->validate([
            'banner_image' => 'mimes:jpg,png,jpeg,gif',
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'type' => 'required|string:in:package,service',
            'reference_id' => 'required|integer',
            'duration' => 'required|integer',
            'status' => 'required|string',
        ]);

        if ($request->hasFile('banner_image')) {

            if ($banner->banner_image) {
                Storage::disk('public')->delete($banner->banner_image);
            }

            $fields['banner_image'] = $request->file('banner_image');
            $fileName = uniqid() . '.' . $fields['banner_image']->getClientOriginalExtension();
            $rut = 'banners';
            $image_path = $fields['banner_image']->storeAs($rut, $fileName, 'public');
            $fields['banner_image'] = $image_path;
        }
        $banner->update($fields);
        return response()->json(['message' => "Banner Posted Successfully", 'banner' => new BannerResource($banner)], 200);
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
}
