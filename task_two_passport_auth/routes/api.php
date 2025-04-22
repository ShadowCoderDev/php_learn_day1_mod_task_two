<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\ApiController;

///*
//
//|--------------------------------------------------------------------------
//| API Routes
//|--------------------------------------------------------------------------
//|
//| Here is where you can register API routes for your application. These
//| routes are loaded by the RouteServiceProvider and all of them will
//| be assigned to the "api" middleware group. Make something great!
//|
//*/




Route::post('/register', [ApiController::class, 'register']);
Route::post('/login', [ApiController::class, 'login']);


Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [ApiController::class, 'logout']);
    Route::get('/profile', [ApiController::class, 'profile']);
    Route::post('/refresh-token', [ApiController::class, 'refreshToken']);
    Route::get('/permissions', [ApiController::class, 'permissions']);
    Route::get('/roles', [ApiController::class, 'roles']);
    Route::get('/check-permission/{permission}', [ApiController::class, 'checkPermission']);
});


Route::fallback(function(){
    return response()->json([
        'status' => false,
        'message' => 'مسیر مورد نظر یافت نشد'
    ], 404);
 });

 