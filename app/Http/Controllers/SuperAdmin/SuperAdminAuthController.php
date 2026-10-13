<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminAuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            if (Auth::user()->isSuperAdmin()) {
                return redirect()->route('superadmin.dashboard');
            }
            Auth::logout();
        }

        return view('superadmin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = $credentials['username'];
        $password = $credentials['password'];
        $remember = $request->filled('remember');

        $fieldType = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (Auth::attempt([$fieldType => $loginInput, 'password' => $password], $remember)) {
            $user = Auth::user();

            if (!$user->isSuperAdmin()) {
                Auth::logout();
                return back()->withErrors([
                    'username' => 'Akses ditolak: Akun Anda bukan Super Administrator.',
                ])->onlyInput('username');
            }

            $request->session()->regenerate();
            return redirect()->intended(route('superadmin.dashboard'))
                ->with('success', 'Selamat datang di Super Admin Portal, ' . $user->name . '!');
        }

        return back()->withErrors([
            'username' => 'Username atau password Super Admin yang Anda masukkan salah.',
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('superadmin.login')->with('success', 'Anda telah berhasil keluar dari Super Admin.');
    }
}
