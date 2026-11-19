<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware API Sanctum untuk anggota toko yang aktif:
 * mengizinkan akun Admin Toko (store_admin) maupun akun Kasir (cashier)
 * yang terhubung ke cabang.
 */
class ApiStoreMemberMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !($user->isStoreAdmin() || $user->isCashier()) || !$user->isActiveAccount() || !$user->store) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Akun harus aktif sebagai Admin Toko atau Kasir cabang.',
            ], 403);
        }

        $request->attributes->set('store', $user->store);

        return $next($request);
    }
}

