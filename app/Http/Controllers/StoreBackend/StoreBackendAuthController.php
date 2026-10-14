<?php

namespace App\Http\Controllers\StoreBackend;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreBackendAuthController extends Controller
{
    protected function getStore(string $storeSlug): Store
    {
        return Store::where('slug', $storeSlug)
            ->orWhere('code', $storeSlug)
            ->firstOrFail();
    }

    public function showLoginForm(string $storeSlug)
    {
        $store = $this->getStore($storeSlug);

        if (Auth::guard('web')->check()) {
            if (Auth::guard('web')->user()->canManageStore($store->id)) {
                return redirect()->route('store.backend.dashboard', ['store_slug' => $store->slug]);
            }
        }

        return view('store_backend.auth.login', compact('store'));
    }

    public function login(Request $request, string $storeSlug)
    {
        $store = $this->getStore($storeSlug);

        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = $credentials['username'];
        $password = $credentials['password'];
        $remember = $request->filled('remember');

        $fieldType = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (Auth::guard('web')->attempt([$fieldType => $loginInput, 'password' => $password], $remember)) {
            $user = Auth::guard('web')->user();

            if (!$user->canManageStore($store->id)) {
                Auth::guard('web')->logout();
                $assigned = $user->store ? $user->store->name : 'cabang lain';
                return back()->withErrors([
                    'username' => "Akses Ditolak: Akun Anda hanya memiliki hak akses untuk {$assigned}.",
                ])->onlyInput('username');
            }

            // If user is superadmin, also sync to superadmin guard
            if ($user->isSuperAdmin()) {
                Auth::guard('superadmin')->login($user, $remember);
            }

            $request->session()->regenerate();
            return redirect()->intended(route('store.backend.dashboard', ['store_slug' => $store->slug]))
                ->with('success', "Selamat datang di Panel Dapur {$store->name}, {$user->name}!");
        }

        return back()->withErrors([
            'username' => 'Username atau password yang dimasukkan salah.',
        ])->onlyInput('username');
    }

    public function logout(Request $request, string $storeSlug)
    {
        $store = $this->getStore($storeSlug);

        // Logout only from store backend (web guard)
        Auth::guard('web')->logout();

        // Invalidate session only if NOT logged in as superadmin
        if (!Auth::guard('superadmin')->check()) {
            $request->session()->invalidate();
        }
        $request->session()->regenerateToken();

        return redirect()->route('store.backend.login', ['store_slug' => $store->slug])
            ->with('success', "Anda telah berhasil logout dari {$store->name}.");
    }
}
