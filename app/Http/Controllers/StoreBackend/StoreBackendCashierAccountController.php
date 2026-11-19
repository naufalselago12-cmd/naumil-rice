<?php

namespace App\Http\Controllers\StoreBackend;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Manajemen Akun Kasir (per toko) di Store Backend Web.
 * Hanya dapat diakses oleh Admin Toko cabang tersebut / Super Admin (via store.scope).
 */
class StoreBackendCashierAccountController extends Controller
{
    protected function getStore(Request $request): Store
    {
        return $request->attributes->get('store');
    }

    protected function ownCashier(Store $store, User $cashier): void
    {
        if ((int) $cashier->store_id !== (int) $store->id || !$cashier->isCashier()) {
            abort(403, 'Akun kasir ini bukan milik cabang Anda.');
        }
    }

    public function index(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);

        $cashiers = User::where('store_id', $store->id)
            ->where('role', User::ROLE_CASHIER)
            ->orderByDesc('id')
            ->get();

        return view('store_backend.cashiers.index', compact('store', 'cashiers'));
    }

    public function store(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'username' => 'required|string|max:80|alpha_dash|unique:users,username',
            'email' => 'nullable|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|max:100',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'Nama kasir wajib diisi.',
            'username.required' => 'Username login kasir wajib diisi.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, tanda hubung (-), atau garis bawah (_).',
            'username.unique' => 'Username sudah digunakan akun lain.',
            'email.unique' => 'Email sudah digunakan akun lain.',
            'password.min' => 'Password minimal 6 karakter.',
        ]);

        $email = !empty($validated['email'])
            ? strtolower(trim($validated['email']))
            : strtolower($validated['username']) . '.kasir' . $store->id . '.' . strtolower(Str::random(3)) . '@naumilrice.local';

        $attrs = [
            'name' => trim($validated['name']),
            'username' => trim($validated['username']),
            'email' => $email,
            'password' => Hash::make($validated['password']),
            'role' => User::ROLE_CASHIER,
            'store_id' => $store->id,
        ];

        if (Schema::hasColumn('users', 'is_active')) {
            $attrs['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        }

        $cashier = User::create($attrs);

        return redirect()
            ->route('store.backend.cashiers.index', ['store_slug' => $store->slug])
            ->with('success', "Akun kasir '{$cashier->name}' (@{$cashier->username}) berhasil ditambahkan.");
    }

    public function update(Request $request, string $store_slug, User $cashier)
    {
        $store = $this->getStore($request);
        $this->ownCashier($store, $cashier);

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'username' => 'required|string|max:80|alpha_dash|unique:users,username,' . $cashier->id,
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $cashier->id,
            'password' => 'nullable|string|min:6|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $attrs = [
            'name' => trim($validated['name']),
            'username' => trim($validated['username']),
        ];

        if (!empty($validated['email'])) {
            $attrs['email'] = strtolower(trim($validated['email']));
        }
        if (!empty($validated['password'])) {
            $attrs['password'] = Hash::make($validated['password']);
        }
        if (Schema::hasColumn('users', 'is_active') && $request->has('is_active')) {
            $attrs['is_active'] = $request->boolean('is_active');
        }

        $cashier->update($attrs);

        // Jika dinonaktifkan, cabut token mobile agar langsung logout
        if (isset($attrs['is_active']) && !$attrs['is_active']) {
            $cashier->tokens()->delete();
        }

        return redirect()
            ->route('store.backend.cashiers.index', ['store_slug' => $store->slug])
            ->with('success', "Akun kasir '{$cashier->name}' berhasil diperbarui.");
    }

    public function toggle(Request $request, string $store_slug, User $cashier)
    {
        $store = $this->getStore($request);
        $this->ownCashier($store, $cashier);

        if (!Schema::hasColumn('users', 'is_active')) {
            return back()->with('error', 'Jalankan migrasi database (php artisan migrate) untuk fitur aktif/nonaktif akun kasir.');
        }

        $newState = !$cashier->isActiveAccount();
        $cashier->update(['is_active' => $newState]);

        if (!$newState) {
            $cashier->tokens()->delete();
        }

        $label = $newState ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()
            ->route('store.backend.cashiers.index', ['store_slug' => $store->slug])
            ->with('success', "Akun kasir '{$cashier->name}' berhasil {$label}.");
    }

    public function destroy(Request $request, string $store_slug, User $cashier)
    {
        $store = $this->getStore($request);
        $this->ownCashier($store, $cashier);

        $name = $cashier->name;
        $cashier->tokens()->delete();
        $cashier->delete();

        return redirect()
            ->route('store.backend.cashiers.index', ['store_slug' => $store->slug])
            ->with('success', "Akun kasir '{$name}' berhasil dihapus.");
    }
}

