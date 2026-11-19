<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Manajemen Akun Kasir per toko untuk Aplikasi Mobile (khusus Admin Toko via store.api).
 */
class AdminCashierAccountController extends BaseAdminController
{
    protected function ownCashier(Request $request, User $cashier): void
    {
        if ((int) $cashier->store_id !== (int) $this->currentStore($request)->id || !$cashier->isCashier()) {
            abort(403, 'Akun kasir ini bukan milik cabang Anda.');
        }
    }

    protected function present(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->isActiveAccount(),
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }

    public function index(Request $request)
    {
        $store = $this->currentStore($request);

        $cashiers = User::where('store_id', $store->id)
            ->where('role', User::ROLE_CASHIER)
            ->orderByDesc('id')
            ->get()
            ->map(fn (User $u) => $this->present($u))
            ->values()
            ->all();

        return $this->ok(['data' => $cashiers]);
    }

    public function store(Request $request)
    {
        $store = $this->currentStore($request);

        $v = $request->validate([
            'name' => 'required|string|max:120',
            'username' => 'required|string|max:80|alpha_dash|unique:users,username',
            'email' => 'nullable|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $email = !empty($v['email'])
            ? strtolower(trim($v['email']))
            : strtolower($v['username']) . '.kasir' . $store->id . '.' . strtolower(Str::random(3)) . '@naumilrice.local';

        $attrs = [
            'name' => trim($v['name']),
            'username' => trim($v['username']),
            'email' => $email,
            'password' => Hash::make($v['password']),
            'role' => User::ROLE_CASHIER,
            'store_id' => $store->id,
        ];

        if (Schema::hasColumn('users', 'is_active')) {
            $attrs['is_active'] = array_key_exists('is_active', $v) ? (bool) $v['is_active'] : true;
        }

        $cashier = User::create($attrs);

        return $this->ok(
            ['data' => $this->present($cashier)],
            "Akun kasir '{$cashier->name}' berhasil ditambahkan.",
            201
        );
    }

    public function update(Request $request, User $cashier)
    {
        $this->ownCashier($request, $cashier);

        $v = $request->validate([
            'name' => 'required|string|max:120',
            'username' => 'required|string|max:80|alpha_dash|unique:users,username,' . $cashier->id,
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $cashier->id,
            'password' => 'nullable|string|min:6|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $attrs = [
            'name' => trim($v['name']),
            'username' => trim($v['username']),
        ];

        if (!empty($v['email'])) {
            $attrs['email'] = strtolower(trim($v['email']));
        }
        if (!empty($v['password'])) {
            $attrs['password'] = Hash::make($v['password']);
        }
        if (Schema::hasColumn('users', 'is_active') && array_key_exists('is_active', $v)) {
            $attrs['is_active'] = (bool) $v['is_active'];
        }

        $cashier->update($attrs);

        if (isset($attrs['is_active']) && !$attrs['is_active']) {
            $cashier->tokens()->delete();
        }

        return $this->ok(
            ['data' => $this->present($cashier->fresh())],
            "Akun kasir '{$cashier->name}' berhasil diperbarui."
        );
    }

    public function toggle(Request $request, User $cashier)
    {
        $this->ownCashier($request, $cashier);

        if (!Schema::hasColumn('users', 'is_active')) {
            return response()->json([
                'success' => false,
                'message' => 'Kolom status aktif belum dimigrasi. Jalankan php artisan migrate.',
            ], 422);
        }

        $newState = !$cashier->isActiveAccount();
        $cashier->update(['is_active' => $newState]);

        if (!$newState) {
            $cashier->tokens()->delete();
        }

        return $this->ok(
            ['data' => $this->present($cashier->fresh())],
            $newState ? 'Akun kasir diaktifkan.' : 'Akun kasir dinonaktifkan.'
        );
    }

    public function destroy(Request $request, User $cashier)
    {
        $this->ownCashier($request, $cashier);

        $name = $cashier->name;
        $cashier->tokens()->delete();
        $cashier->delete();

        return $this->ok([], "Akun kasir '{$name}' berhasil dihapus.");
    }
}

