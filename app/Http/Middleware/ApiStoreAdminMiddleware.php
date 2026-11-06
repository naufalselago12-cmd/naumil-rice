<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Untuk API aplikasi mobile: hanya admin toko (token Sanctum) yang punya cabang.
 * Menyimpan cabang pada request agar controller memakai pola yang sama dengan backend web.
 */
class ApiStoreAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isStoreAdmin() || !$user->store) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Aplikasi ini hanya untuk akun admin toko yang terhubung ke sebuah cabang.',
            ], 403);
        }

        $request->attributes->set('store', $user->store);

        return $next($request);
    }
}

