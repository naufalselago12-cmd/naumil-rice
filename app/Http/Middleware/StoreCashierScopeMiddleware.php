<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Scope middleware untuk halaman Mode Kasir (POS & KDS Kasir) per toko.
 * Mengizinkan akun Kasir aktif cabang tersebut, Admin Toko cabang tersebut, atau Super Admin.
 */
class StoreCashierScopeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $storeSlug = $request->route('store_slug');

        if (!$storeSlug) {
            abort(404, 'Parameter toko tidak ditemukan.');
        }

        $store = Store::where('slug', $storeSlug)
            ->orWhere('code', $storeSlug)
            ->first();

        if (!$store) {
            abort(404, "Cabang / Toko '{$storeSlug}' tidak ditemukan.");
        }

        $request->attributes->set('store', $store);
        View::share('currentStore', $store);

        if (!Auth::guard('web')->check()) {
            if (Auth::guard('superadmin')->check() && Auth::guard('superadmin')->user()->isSuperAdmin()) {
                Auth::guard('web')->login(Auth::guard('superadmin')->user());
            } else {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Sesi berakhir. Silakan login kembali.'], 401);
                }
                return redirect()->route('store.backend.login', ['store_slug' => $store->slug ?: $storeSlug])
                    ->with('warning', "Silakan login untuk mengakses Kasir {$store->name}.");
            }
        }

        $user = Auth::guard('web')->user();

        if (!$user->isActiveAccount()) {
            Auth::guard('web')->logout();
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Akun Anda sedang dinonaktifkan.'], 403);
            }
            return redirect()->route('store.backend.login', ['store_slug' => $store->slug ?: $storeSlug])
                ->with('error', 'Akun Anda sedang dinonaktifkan oleh admin toko.');
        }

        if (!$user->canAccessStoreCashier($store->id)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak untuk cabang ini.'], 403);
            }
            if ($user->store?->slug) {
                $targetRoute = $user->isCashier() ? 'store.backend.cashier.pos' : 'store.backend.dashboard';
                return redirect()->route($targetRoute, ['store_slug' => $user->store->slug])
                    ->with('error', "Akses Ditolak: Anda terdaftar di cabang '{$user->store->name}'.");
            }
            Auth::guard('web')->logout();
            return redirect()->route('store.backend.login', ['store_slug' => $store->slug ?: $storeSlug])
                ->with('error', 'Anda tidak memiliki wewenang untuk cabang ini.');
        }

        return $next($request);
    }
}

