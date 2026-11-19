<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->isCashier() && $user->store) {
                return redirect()->route('store.backend.cashier.pos', ['store_slug' => $user->store->slug]);
            }
            if ($user->isStoreAdmin() && $user->store) {
                return redirect()->route('store.backend.dashboard', ['store_slug' => $user->store->slug]);
            }
            return redirect()->route('admin.dashboard');
        }
        return view('admin.auth.login');
    }

    /**
     * Handle login submission
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = $credentials['username'];
        $password = $credentials['password'];
        $remember = $request->filled('remember');

        // Check if user input is email or username
        $fieldType = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (Auth::attempt([$fieldType => $loginInput, 'password' => $password], $remember)) {
            $user = Auth::user();

            if (!$user->isActiveAccount()) {
                Auth::logout();
                return back()->withErrors([
                    'username' => 'Akun Anda sedang dinonaktifkan oleh admin toko.',
                ])->onlyInput('username');
            }

            $request->session()->regenerate();

            if ($user->isCashier() && $user->store) {
                return redirect()->route('store.backend.cashier.pos', ['store_slug' => $user->store->slug])
                    ->with('success', "Selamat bertugas di Kasir {$user->store->name}, {$user->name}!");
            }

            if ($user->isStoreAdmin() && $user->store) {
                return redirect()->route('store.backend.dashboard', ['store_slug' => $user->store->slug])
                    ->with('success', 'Selamat datang, ' . $user->name . '!');
            }

            return redirect()->intended(route('admin.dashboard'))->with('success', 'Selamat datang, ' . $user->name . '!');
        }

        return back()->withErrors([
            'username' => 'Username atau password yang Anda masukkan salah.',
        ])->onlyInput('username');
    }

    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'Anda telah berhasil logout.');
    }
}
