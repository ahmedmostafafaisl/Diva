<?php
use App\Http\Controllers\BannerController ;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
|  Banners Routes
|--------------------------------------------------------------------------
*/


Route::prefix('banners')->group(function () {
    // Get all banners
    Route::get('/all', [BannerController::class,'getAllbanners']);


    //Get All Active Banners

    Route::get('/all-active', [BannerController::class,'getAllActiveBanners']);

    //Get Single

    Route::get('/{id}',[BannerController::class,'getSingleBanner'])->middleware('auth:sanctum');

    // Add a new banner
    Route::post('/add', [BannerController::class,'storeBanner'])->middleware('auth:sanctum');

    // Update a banner
    Route::post('/{id}/update', [BannerController::class, 'updateBanner'])->middleware('auth:sanctum');

    // Delete a banner
    Route::delete('/{id}/delete', [BannerController::class, 'deleteBanner'])->middleware('auth:sanctum');
});
