<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:100',
        ]);

        $field = filter_var($data['username'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::where($field, $data['username'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['username' => 'Username atau password yang dimasukkan salah.']);
        }

        if (!$user->isStoreAdmin() || !$user->store) {
            throw ValidationException::withMessages([
                'username' => 'Aplikasi ini hanya untuk akun admin toko yang terhubung ke sebuah cabang.',
            ]);
        }

        $token = $user->createToken($data['device_name'] ?? 'naumil-rice-apps')->plainTextToken;

        return response()->json(['success' => true, 'token' => $token] + $this->profile($user));
    }

    public function me(Request $request)
    {
        return response()->json(['success' => true] + $this->profile($request->user()));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['success' => true, 'message' => 'Berhasil logout.']);
    }

    protected function profile(User $user): array
    {
        /** @var Store $store */
        $store = $user->store;

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
            ],
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'code' => $store->code,
                'logo' => $store->logo,
                'address' => $store->address,
            ],
            // external_id OneSignal untuk perangkat ini (dipakai OneSignal.login pada aplikasi)
            'onesignal_external_id' => config('onesignal.external_id_prefix') . $user->id,
        ];
    }
}

