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
        if (!Auth::check()) {
            return redirect()->route('superadmin.login')->with('warning', 'Silakan login sebagai Super Admin.');
        }

        $user = Auth::user();

        if (!$user->isSuperAdmin()) {
            // If they are a store admin, redirect them to their store backend
            if ($user->store_id && $user->store) {
                return redirect()->to(url('/' . $user->store->slug . '/backend'))
                    ->with('error', 'Akses ditolak: Portal Super Admin hanya untuk Super Administrator.');
            }

            abort(403, 'Akses ditolak: Anda tidak memiliki hak akses Super Administrator.');
        }

        return $next($request);
    }
}
