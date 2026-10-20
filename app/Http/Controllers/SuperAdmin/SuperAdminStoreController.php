<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SuperAdminStoreController extends Controller
{
    public function index()
    {
        $stores = Store::with(['users' => function ($q) {
            $q->where('role', User::ROLE_STORE_ADMIN);
        }])
        ->withCount(['categories', 'menus', 'campaigns', 'orders'])
        ->get();

        return view('superadmin.stores.index', compact('stores'));
    }

    public function create()
    {
        return view('superadmin.stores.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:100|alpha_dash|unique:stores,slug',
            'code' => 'required|string|max:50|unique:stores,code',
            'address' => 'required|string',
            'phone' => 'nullable|string|max:50',
            'logo' => 'nullable|string',
            'logo_file' => 'nullable|image|max:3072',
            'payment_type' => 'nullable|in:qris_static,payment_gateway',
            'qris_image' => 'nullable|string',
            'qris_file' => 'nullable|image|max:3072',
            'qris_data' => 'nullable|string',
            'mayar_api_key' => 'nullable|string',
            'mayar_webhook_token' => 'nullable|string',
            'mayar_api_key_production' => 'nullable|string',
            'mayar_webhook_token_production' => 'nullable|string',
            'mayar_environment' => 'nullable|in:sandbox,production',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['payment_type'] = $validated['payment_type'] ?? 'qris_static';
        $validated['mayar_environment'] = $validated['mayar_environment'] ?? 'sandbox';

        if (empty($validated['slug'])) {
            $baseSlug = Str::slug($validated['name']);
            $slug = $baseSlug;
            $counter = 1;
            while (Store::where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }
            $validated['slug'] = $slug;
        } else {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        if ($request->hasFile('logo_file')) {
            $path = $request->file('logo_file')->store('stores/logos', 'public');
            $validated['logo'] = asset('storage/' . $path);
        }

        if ($request->hasFile('qris_file')) {
            $path = $request->file('qris_file')->store('stores/qris', 'public');
            $validated['qris_image'] = asset('storage/' . $path);
        }

        if (empty($validated['qris_image']) && !empty($validated['qris_data'])) {
            $validated['qris_image'] = 'https://api.qrserver.com/v1/create-qr-code/?size=350x350&data=' . urlencode($validated['qris_data']);
        }

        $store = Store::create($validated);

        return redirect()->route('superadmin.stores.index')
            ->with('success', "Cabang '{$store->name}' berhasil dibuat dengan URL frontend: /{$store->slug} dan backend: /{$store->slug}/backend.");
    }

    public function edit(Store $store)
    {
        $storeAdmins = User::where('store_id', $store->id)->get();
        return view('superadmin.stores.edit', compact('store', 'storeAdmins'));
    }

    public function update(Request $request, Store $store)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|alpha_dash|unique:stores,slug,' . $store->id,
            'code' => 'required|string|max:50|unique:stores,code,' . $store->id,
            'address' => 'required|string',
            'phone' => 'nullable|string|max:50',
            'logo' => 'nullable|string',
            'logo_file' => 'nullable|image|max:3072',
            'payment_type' => 'nullable|in:qris_static,payment_gateway',
            'qris_image' => 'nullable|string',
            'qris_file' => 'nullable|image|max:3072',
            'qris_data' => 'nullable|string',
            'mayar_api_key' => 'nullable|string',
            'mayar_webhook_token' => 'nullable|string',
            'mayar_api_key_production' => 'nullable|string',
            'mayar_webhook_token_production' => 'nullable|string',
            'mayar_environment' => 'nullable|in:sandbox,production',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['slug'] = Str::slug($validated['slug']);
        $validated['payment_type'] = $validated['payment_type'] ?? 'qris_static';
        $validated['mayar_environment'] = $validated['mayar_environment'] ?? 'sandbox';

        if ($request->hasFile('logo_file')) {
            $path = $request->file('logo_file')->store('stores/logos', 'public');
            $validated['logo'] = asset('storage/' . $path);
        }

        if ($request->hasFile('qris_file')) {
            $path = $request->file('qris_file')->store('stores/qris', 'public');
            $validated['qris_image'] = asset('storage/' . $path);
        }

        if (empty($validated['qris_image']) && !empty($validated['qris_data'])) {
            $validated['qris_image'] = 'https://api.qrserver.com/v1/create-qr-code/?size=350x350&data=' . urlencode($validated['qris_data']);
        }

        $store->update($validated);

        return redirect()->route('superadmin.stores.index')
            ->with('success', "Data cabang '{$store->name}' berhasil diperbarui.");
    }

    public function destroy(Store $store)
    {
        $name = $store->name;
        $store->delete();

        return redirect()->route('superadmin.stores.index')
            ->with('success', "Cabang '{$name}' berhasil dihapus.");
    }
}
