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
            'payment_type' => 'required|in:qris_static,payment_gateway',
            'qris_image' => 'nullable|string',
            'qris_file' => 'nullable|image|max:3072',
            'qris_data' => 'nullable|string',
            'mayar_environment' => 'nullable|in:sandbox,production',
            'mayar_api_key' => 'nullable|string',
            'mayar_webhook_token' => 'nullable|string',
            'mayar_api_key_production' => 'nullable|string',
            'mayar_webhook_token_production' => 'nullable|string',
        ], [
            'payment_type.required' => 'Wajib memilih salah satu metode pembayaran: QRIS Statik atau Payment Gateway.',
        ]);

        // Validation rule: If QRIS Static is chosen, ensure QRIS file or data exists
        if ($validated['payment_type'] === 'qris_static') {
            $hasExistingQris = !empty($store->qris_image) || !empty($store->qris_data);
            $hasNewQris = $request->hasFile('qris_file') || !empty($validated['qris_data']) || !empty($validated['qris_image']);
            
            if (!$hasExistingQris && !$hasNewQris) {
                return back()->withErrors([
                    'qris_file' => 'Metode QRIS Statik dipilih: Wajib upload file gambar QRIS atau masukkan string payload merchant.',
                ])->withInput();
            }
        }

        // Validation rule: If Payment Gateway is chosen, ensure the active environment API Key is present
        if ($validated['payment_type'] === 'payment_gateway') {
            $env = $validated['mayar_environment'] ?? 'sandbox';
            if ($env === 'production') {
                $hasKey = !empty($validated['mayar_api_key_production']) || !empty($store->mayar_api_key_production);
                if (!$hasKey) {
                    return back()->withErrors([
                        'mayar_api_key_production' => 'Mode Production dipilih: Wajib mengisi API Key Mayar.id Production untuk memproses transaksi live.',
                    ])->withInput();
                }
            } else {
                $hasKey = !empty($validated['mayar_api_key']) || !empty($store->mayar_api_key);
                if (!$hasKey) {
                    return back()->withErrors([
                        'mayar_api_key' => 'Mode Sandbox dipilih: Wajib mengisi API Key Mayar.id Sandbox untuk pengujian.',
                    ])->withInput();
                }
            }
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

        $validated['mayar_environment'] = $validated['mayar_environment'] ?? 'sandbox';

        $store->update($validated);

        $envLabel = ($validated['mayar_environment'] === 'production') ? 'Production (Live)' : 'Sandbox (Uji Coba)';
        $paymentLabel = ($validated['payment_type'] === 'payment_gateway') ? "Payment Gateway Mayar.id [{$envLabel}]" : 'QRIS Statik';

        return redirect()->route('store.backend.settings', ['store_slug' => $store->slug])
            ->with('success', "Profil dan metode pembayaran cabang ({$paymentLabel}) berhasil disimpan.");
    }

    public function testMayar(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);
        $environment = $request->input('environment', 'sandbox');
        $apiKey = $request->input('api_key');

        if (empty($apiKey)) {
            $apiKey = ($environment === 'production') 
                ? ($store->mayar_api_key_production ?: $store->mayar_api_key) 
                : $store->mayar_api_key;
        }

        $service = new \App\Services\MayarPaymentService();
        $result = $service->testConnection($apiKey ?? '', $environment);

        return response()->json($result);
    }
}
