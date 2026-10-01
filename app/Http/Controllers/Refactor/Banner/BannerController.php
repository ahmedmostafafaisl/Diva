<?php

namespace App\Http\Controllers\Refactor\Banner;

use App\Models\Banner;
use App\Helper\ApiResponseHelper;
use App\Services\ValidationService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Refactor\Banner\BannerResource;
use App\Http\Requests\Refactor\Banner\StoreBannerRequest;
use App\Http\Requests\Refactor\Banner\UpdateBannerRequest;
use App\Repositories\Interfaces\BannerRepositoryInterface;

class BannerController extends Controller
{

    use ApiResponseHelper;
    protected $bannerRepository;
    private $validationService;
    public function __construct(BannerRepositoryInterface $bannerRepository, ValidationService $validationService)
    {
        $this->bannerRepository = $bannerRepository;
        $this->validationService = $validationService;
    }

    public function index()
    {
        $banners = $this->bannerRepository->getAllBanners();
        return $this->setCode(code: 200)->setData(BannerResource::collection($banners))->setMessage('Success')->send();
    }

    public function show($id)
    {

        $banner = $this->validationService->checkRecordExists(Banner::class, $id);
        if ($banner instanceof \Illuminate\Http\JsonResponse) {
            return $banner;
        }
        $banner = $this->bannerRepository->getBannerById($id);
        return $this->setCode(code: 200)->setData(new BannerResource($banner))->setMessage('Success')->send();
    }

    public function store(StoreBannerRequest $request)
    {

        $fields = $request->validated();


        $banner = $this->bannerRepository->createBanner($fields);
        return $this->setCode(code: 200)->setData(new BannerResource($banner))->setMessage('banner Created successfully')->send();
    }

    public function update(UpdateBannerRequest $request, $id)
    {
        $banner = $this->validationService->checkRecordExists(Banner::class, $id);
        if ($banner instanceof \Illuminate\Http\JsonResponse) {
            return $banner;
        }
        $fields = $request->validated();
        $banner = Banner::findOrFail($id);


        $banner = $this->bannerRepository->updateBanner($id, $fields);
        return $this->setCode(code: 200)->setData(new BannerResource($banner))->setMessage('Banner Updated successfully')->send();
    }

    public function destroy($id)
    {
        $banner = $this->validationService->checkRecordExists(Banner::class, $id);
        if ($banner instanceof \Illuminate\Http\JsonResponse) {
            return $banner;
        }
        return   $this->bannerRepository->deleteBanner($id);
        return $this->setCode(code: 200)->setData($id)->setMessage('Banner deleted successfully')->send();
    }

    public function getAllActiveBanners()
    {
        $banners = $this->bannerRepository->getAllActiveBanners();
        return $this->setCode(code: 200)->setData(BannerResource::collection($banners))->setMessage('success')->send();
    }
}
