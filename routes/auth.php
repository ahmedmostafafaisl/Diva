<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;




/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/
Route::prefix('users')->group(function () {

    // This is where you can register users
    Route::post("/register", [AuthController::class, "register"])->middleware('auth:sanctum');

    // This is where you can login
    Route::post("/login", [AuthController::class, "login"]);

    // Get user permissions
    Route::get("{id}/permissions", [AuthController::class, "get_user_permissions"]);

    // Login request for customer
    Route::post("/customer/login-request", [AuthController::class, "login_customer"]);

    // Update customer data
    Route::post("/customer/{id}/update", [AuthController::class, "update_customer_data"])->middleware('auth:sanctum');

    // Login verification with OTP
    Route::post("/customer/login-verify-otp", [AuthController::class, "loginVerifyOtp"]);

    // Logout
    Route::get("/logout", [AuthController::class, "logout"])->middleware('auth:sanctum');
    Route::get("/account/deactivate", [AuthController::class, "change_user_status"])->middleware('auth:sanctum');

    // Get current user
    Route::get("/me", [AuthController::class, "me"])->middleware('auth:sanctum');
});
