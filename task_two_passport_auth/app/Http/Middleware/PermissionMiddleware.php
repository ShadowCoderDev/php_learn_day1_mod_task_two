<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permission): Response
    {
        if (!auth()->check()) {
            abort(403, 'شما دسترسی ندارید.');
        }

        $permissions = is_array($permission) ? $permission : explode('|', $permission);

        if (!auth()->user()->hasPermissions($permissions)) {
            abort(403, 'شما دسترسی ندارید.');
        }

        return $next($request);
    }
}
