<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\ApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes 
Route::post("register", [ApiController::class, "register"]);
Route::post("login", [ApiController::class, "login"]);

// Protected routes 
Route::middleware('auth:api')->group(function () {
    Route::post("refresh-token", [ApiController::class, "refreshToken"]);
    Route::get("profile", [ApiController::class, "profile"]);
    Route::post("logout", [ApiController::class, "logout"]);
    
    // Admin only routes 
    Route::middleware('admin')->group(function () {
    });
});

// Fallback route 
Route::fallback(function(){
    return response()->json([
        'status' => false,
        'message' => 'مسیر مورد نظر یافت نشد'
    ], 404);
});