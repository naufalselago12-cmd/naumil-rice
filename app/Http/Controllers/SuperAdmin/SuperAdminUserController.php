<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SuperAdminUserController extends Controller
{
    public function index(Request $request)
    {
        $storeFilter = $request->query('store_id');
        $roleFilter = $request->query('role');

        $stores = Store::orderBy('name')->get();

        $users = User::with('store')
            ->when($storeFilter, fn($q) => $q->where('store_id', $storeFilter))
            ->when($roleFilter, fn($q) => $q->where('role', $roleFilter))
            ->orderBy('id', 'desc')
            ->get();

        return view('superadmin.users.index', compact('users', 'stores', 'storeFilter', 'roleFilter'));
    }

    public function create()
    {
        $stores = Store::where('status', 'active')->orderBy('name')->get();
        return view('superadmin.users.create', compact('stores'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:100|alpha_dash|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => ['required', Rule::in([User::ROLE_SUPERADMIN, User::ROLE_STORE_ADMIN])],
            'store_id' => [
                'nullable',
                Rule::requiredIf(fn() => $request->role === User::ROLE_STORE_ADMIN),
                'exists:stores,id',
            ],
        ], [
            'store_id.required' => 'Pilih cabang / toko yang akan dikelola oleh admin toko ini.',
        ]);

        // If role is superadmin, clear store_id
        if ($validated['role'] === User::ROLE_SUPERADMIN) {
            $validated['store_id'] = null;
        }

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        $storeInfo = $user->store ? " ditugaskan ke cabang {$user->store->name}" : "";
        return redirect()->route('superadmin.users.index')
            ->with('success', "User '{$user->name}' (@{$user->username}) berhasil dibuat{$storeInfo}.");
    }

    public function edit(User $user)
    {
        $stores = Store::where('status', 'active')->orderBy('name')->get();
        return view('superadmin.users.edit', compact('user', 'stores'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:100|alpha_dash|unique:users,username,' . $user->id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role' => ['required', Rule::in([User::ROLE_SUPERADMIN, User::ROLE_STORE_ADMIN])],
            'store_id' => [
                'nullable',
                Rule::requiredIf(fn() => $request->role === User::ROLE_STORE_ADMIN),
                'exists:stores,id',
            ],
        ], [
            'store_id.required' => 'Pilih cabang / toko yang akan dikelola oleh admin toko ini.',
        ]);

        if ($validated['role'] === User::ROLE_SUPERADMIN) {
            $validated['store_id'] = null;
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('superadmin.users.index')
            ->with('success', "Data user '{$user->name}' berhasil diperbarui.");
    }

    public function destroy(User $user)
    {
        // Protect primary super admin account
        if ($user->username === 'naufal_su') {
            return back()->with('error', 'Akun Super Admin Utama (naufal_su) tidak dapat dihapus.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('superadmin.users.index')
            ->with('success', "User '{$name}' berhasil dihapus.");
    }
}
