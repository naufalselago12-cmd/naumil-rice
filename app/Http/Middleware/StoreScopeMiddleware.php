<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Models\Store;
use Symfony\Component\HttpFoundation\Response;

class StoreScopeMiddleware
{
    /**
     * Handle an incoming request.
     */
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
            abort(404, "Cabang / Toko '{$storeSlug}' tidak ditemukan dalam sistem Naumil Store.");
        }

        // Share store with all views and current request
        $request->attributes->set('store', $store);
        View::share('currentStore', $store);

        // Check authentication: if not logged into web guard, check if superadmin is logged in
        if (!Auth::guard('web')->check()) {
            if (Auth::guard('superadmin')->check() && Auth::guard('superadmin')->user()->isSuperAdmin()) {
                Auth::guard('web')->login(Auth::guard('superadmin')->user());
            } else {
                return redirect()->route('store.backend.login', ['store_slug' => $store->slug ?: $storeSlug])
                    ->with('warning', "Silakan login untuk mengakses backend {$store->name}.");
            }
        }

        $user = Auth::guard('web')->user();

        // Check permission: superadmin or assigned store_admin
        if (!$user->canManageStore($store->id)) {
            $assignedStoreName = $user->store ? $user->store->name : 'Toko Lain';
            return redirect()->route('store.backend.dashboard', ['store_slug' => $user->store?->slug ?? ''])
                ->with('error', "Akses Ditolak: Anda tidak memiliki wewenang untuk mengelola cabang '{$store->name}'. Anda ditugaskan di '{$assignedStoreName}'.");
        }

        return $next($request);
    }
}
