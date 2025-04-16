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

// مسیرهای عمومی - نیازی به احراز هویت ندارند
Route::post("register", [ApiController::class, "register"]);
Route::post("login", [ApiController::class, "login"]);

// مسیرهای محافظت شده - نیاز به احراز هویت دارند
Route::middleware(['auth:api', 'api.token'])->group(function () {
    Route::post("refresh-token", [ApiController::class, "refreshToken"]);
    Route::get("profile", [ApiController::class, "profile"]);
    Route::post("logout", [ApiController::class, "logout"]);
    
    // مسیرهای مخصوص مدیر - نیاز به نقش مدیر دارند
    Route::middleware('admin')->group(function () {
        // افزودن مسیرهای مخصوص مدیر در اینجا
        Route::get("admin-test", function() {
            return response()->json([
                'status' => true,
                'message' => 'شما دسترسی مدیر دارید'
            ]);
        });
    });
});

// مسیر پیش‌فرض - تمام مسیرهای ناموجود را می‌گیرد
Route::fallback(function(){
    return response()->json([
        'status' => false,
        'message' => 'مسیر مورد نظر یافت نشد'
    ], 404);
});