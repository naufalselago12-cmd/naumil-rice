<?php

namespace App\Http\Controllers\StoreBackend;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreBackendSettingsController extends Controller
{
    protected function getStore(Request $request): Store
    {
        return $request->attributes->get('store');
    }

    public function index(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);
        return view('store_backend.settings.index', compact('store'));
    }

    public function update(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);

        $validated = $request->validate([
            'address' => 'required|string',
            'phone' => 'nullable|string|max:50',
            'logo' => 'nullable|string',
            'logo_file' => 'nullable|image|max:3072',
            'qris_image' => 'nullable|string',
            'qris_file' => 'nullable|image|max:3072',
            'qris_data' => 'nullable|string',
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

        return redirect()->route('store.backend.settings', ['store_slug' => $store->slug])
            ->with('success', 'Profil dan QRIS cabang berhasil diperbarui.');
    }
}
