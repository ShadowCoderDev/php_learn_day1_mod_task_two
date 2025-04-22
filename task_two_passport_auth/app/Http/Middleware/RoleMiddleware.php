<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role): Response
    {
        if (!auth()->check()) {
            abort(403, 'شما دسترسی ندارید.');
        }

        $roles = is_array($role) ? $role : explode('|', $role);

        if (!auth()->user()->hasRoles($roles)) {
            abort(403, 'شما دسترسی ندارید.');
        }

        return $next($request);
    }
}
