<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    public function index()
    {
        $stores = Store::withCount(['categories', 'menus', 'campaigns', 'orders'])->get();
        return view('admin.stores.index', compact('stores'));
    }

    public function create()
    {
        return view('admin.stores.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:stores,code',
            'address' => 'required|string',
            'phone' => 'nullable|string|max:50',
            'logo' => 'nullable|string',
            'logo_file' => 'nullable|image|max:3072',
            'qris_image' => 'nullable|string',
            'qris_file' => 'nullable|image|max:3072',
            'qris_data' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        if ($request->hasFile('logo_file')) {
            $path = $request->file('logo_file')->store('stores/logos', 'public');
            $validated['logo'] = asset('storage/' . $path);
        }

        if ($request->hasFile('qris_file')) {
            $path = $request->file('qris_file')->store('stores/qris', 'public');
            $validated['qris_image'] = asset('storage/' . $path);
        }

        // If qris_image is not uploaded but qris_data is provided, generate a QR preview URL
        if (empty($validated['qris_image']) && !empty($validated['qris_data'])) {
            $validated['qris_image'] = 'https://api.qrserver.com/v1/create-qr-code/?size=350x350&data=' . urlencode($validated['qris_data']);
        }

        Store::create($validated);

        return redirect()->route('admin.stores.index')->with('success', 'Cabang / Toko baru berhasil ditambahkan.');
    }

    public function edit(Store $store)
    {
        return view('admin.stores.edit', compact('store'));
    }

    public function update(Request $request, Store $store)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:stores,code,' . $store->id,
            'address' => 'required|string',
            'phone' => 'nullable|string|max:50',
            'logo' => 'nullable|string',
            'logo_file' => 'nullable|image|max:3072',
            'qris_image' => 'nullable|string',
            'qris_file' => 'nullable|image|max:3072',
            'qris_data' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

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

        return redirect()->route('admin.stores.index')->with('success', 'Data cabang berhasil diperbarui.');
    }

    public function destroy(Store $store)
    {
        $store->delete();
        return redirect()->route('admin.stores.index')->with('success', 'Cabang berhasil dihapus.');
    }
}
