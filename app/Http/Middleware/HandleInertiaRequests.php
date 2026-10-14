<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $superadminUser = \Illuminate\Support\Facades\Auth::guard('superadmin')->user();
        $webUser = \Illuminate\Support\Facades\Auth::guard('web')->user();
        $authUser = $superadminUser ?? $webUser;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $authUser ? [
                    'id' => $authUser->id,
                    'name' => $authUser->name,
                    'username' => $authUser->username,
                    'role' => $authUser->role,
                    'is_super_admin' => $authUser->isSuperAdmin(),
                    'store_id' => $authUser->store_id,
                ] : null,
                'is_super_admin' => \Illuminate\Support\Facades\Auth::guard('superadmin')->check() 
                    || ($webUser && $webUser->isSuperAdmin()),
            ],
        ];
    }
}
