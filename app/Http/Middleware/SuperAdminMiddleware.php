<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // First check superadmin guard
        if (!Auth::guard('superadmin')->check()) {
            // Check if web guard is logged in as superadmin
            if (Auth::guard('web')->check() && Auth::guard('web')->user()->isSuperAdmin()) {
                Auth::guard('superadmin')->login(Auth::guard('web')->user());
            } else {
                return redirect()->route('superadmin.login')->with('warning', 'Silakan login sebagai Super Admin.');
            }
        }

        $user = Auth::guard('superadmin')->user();

        if (!$user->isSuperAdmin()) {
            Auth::guard('superadmin')->logout();
            return redirect()->route('superadmin.login')
                ->with('error', 'Akses ditolak: Portal Super Admin hanya untuk Super Administrator.');
        }

        auth()->shouldUse('superadmin');

        return $next($request);
    }
}
