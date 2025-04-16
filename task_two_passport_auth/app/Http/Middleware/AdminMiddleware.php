<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // اگر کاربر احراز هویت نشده است یا نقش آن مدیر نیست
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'دسترسی محدود. تنها مدیران مجاز به دسترسی هستند.',
                'type' => auth()->check() ? 'type2' : null  // نوع کاربر را برمی‌گرداند (اگر احراز هویت شده باشد)
            ], 403);
        }
        
        // نوع کاربر را به درخواست اضافه می‌کنیم
        $request->attributes->add(['user_type' => 'type1']);
        
        return $next($request);
    }
}