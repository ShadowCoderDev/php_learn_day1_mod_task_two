<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // بررسی وجود هدر Authorization
            if (!$request->bearerToken()) {
                return response()->json([
                    'status' => false,
                    'message' => 'توکن ارائه نشده است'
                ], 401);
            }
            
            // بررسی احراز هویت کاربر
            if (!auth()->check()) {
                return response()->json([
                    'status' => false,
                    'message' => 'توکن نامعتبر یا منقضی شده است'
                ], 401);
            }
            
            // ادامه درخواست
            return $next($request);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'خطا در احراز هویت: ' . $e->getMessage()
            ], 401);
        }
    }
}