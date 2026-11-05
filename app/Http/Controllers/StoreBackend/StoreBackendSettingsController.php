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
            'payment_gateway_provider' => 'nullable|in:midtrans,mayar',
            'qris_image' => 'nullable|string',
            'qris_file' => 'nullable|image|max:3072',
            'qris_data' => 'nullable|string',
            // Midtrans Credentials
            'midtrans_environment' => 'nullable|in:sandbox,production',
            'midtrans_server_key' => 'nullable|string',
            'midtrans_client_key' => 'nullable|string',
            'midtrans_merchant_id' => 'nullable|string',
            'midtrans_server_key_production' => 'nullable|string',
            'midtrans_client_key_production' => 'nullable|string',
            'midtrans_merchant_id_production' => 'nullable|string',
            // Mayar Credentials
            'mayar_environment' => 'nullable|in:sandbox,production',
            'mayar_api_key' => 'nullable|string',
            'mayar_webhook_token' => 'nullable|string',
            'mayar_api_key_production' => 'nullable|string',
            'mayar_webhook_token_production' => 'nullable|string',
        ], [
            'payment_type.required' => 'Wajib memilih salah satu metode pembayaran: QRIS Statik atau Payment Gateway.',
        ]);

        $validated['payment_gateway_provider'] = $validated['payment_gateway_provider'] ?? Store::GATEWAY_PROVIDER_MIDTRANS;

        // Validation rule: If QRIS Static is chosen, ensure QRIS file exists or is already stored
        if ($validated['payment_type'] === 'qris_static') {
            $hasExistingQris = !empty($store->qris_image);
            $hasNewQris = $request->hasFile('qris_file') || !empty($validated['qris_image']);
            
            if (!$hasExistingQris && !$hasNewQris) {
                return back()->withErrors([
                    'qris_file' => 'Metode QRIS Statik dipilih: Wajib upload file gambar QRIS resmi toko Anda.',
                ])->withInput();
            }
        }

        // Validation rule: If Payment Gateway is chosen, ensure credentials are provided for selected provider
        if ($validated['payment_type'] === 'payment_gateway') {
            $provider = $validated['payment_gateway_provider'] ?? Store::GATEWAY_PROVIDER_MIDTRANS;

            if ($provider === Store::GATEWAY_PROVIDER_MIDTRANS) {
                $env = $validated['midtrans_environment'] ?? 'sandbox';
                if ($env === 'production') {
                    $hasKey = !empty($validated['midtrans_server_key_production']) || !empty($store->midtrans_server_key_production);
                    $hasClient = !empty($validated['midtrans_client_key_production']) || !empty($store->midtrans_client_key_production);
                    if (!$hasKey || !$hasClient) {
                        return back()->withErrors([
                            'midtrans_server_key_production' => 'Mode Production dipilih: Wajib mengisi Server Key & Client Key Midtrans Production.',
                        ])->withInput();
                    }
                } else {
                    $hasKey = !empty($validated['midtrans_server_key']) || !empty($store->midtrans_server_key);
                    $hasClient = !empty($validated['midtrans_client_key']) || !empty($store->midtrans_client_key);
                    if (!$hasKey || !$hasClient) {
                        return back()->withErrors([
                            'midtrans_server_key' => 'Mode Sandbox dipilih: Wajib mengisi Server Key & Client Key Midtrans Sandbox untuk pengujian.',
                        ])->withInput();
                    }
                }
            } else {
                // Mayar validation
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
        }

        if ($request->hasFile('logo_file')) {
            $path = $request->file('logo_file')->store('stores/logos', 'public');
            $validated['logo'] = asset('storage/' . $path);
        }

        if ($request->hasFile('qris_file')) {
            $path = $request->file('qris_file')->store('stores/qris', 'public');
            $validated['qris_image'] = asset('storage/' . $path);
        }

        $validated['midtrans_environment'] = $validated['midtrans_environment'] ?? 'sandbox';
        $validated['mayar_environment'] = $validated['mayar_environment'] ?? 'sandbox';

        $store->update($validated);

        if ($validated['payment_type'] === 'payment_gateway') {
            if ($validated['payment_gateway_provider'] === Store::GATEWAY_PROVIDER_MIDTRANS) {
                $envLabel = ($validated['midtrans_environment'] === 'production') ? 'Production (Live)' : 'Sandbox (Uji Coba)';
                $paymentLabel = "Payment Gateway Midtrans [{$envLabel}]";
            } else {
                $envLabel = ($validated['mayar_environment'] === 'production') ? 'Production (Live)' : 'Sandbox (Uji Coba)';
                $paymentLabel = "Payment Gateway Mayar.id [{$envLabel}]";
            }
        } else {
            $paymentLabel = 'QRIS Statik';
        }

        return redirect()->route('store.backend.settings', ['store_slug' => $store->slug])
            ->with('success', "Profil dan metode pembayaran cabang ({$paymentLabel}) berhasil disimpan.");
    }

    public function testMidtrans(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);
        $environment = $request->input('environment', 'sandbox');
        $serverKey = $request->input('server_key');
        $clientKey = $request->input('client_key');

        if (empty($serverKey)) {
            $serverKey = ($environment === 'production')
                ? ($store->midtrans_server_key_production ?: $store->midtrans_server_key)
                : $store->midtrans_server_key;
        }

        if (empty($clientKey)) {
            $clientKey = ($environment === 'production')
                ? ($store->midtrans_client_key_production ?: $store->midtrans_client_key)
                : $store->midtrans_client_key;
        }

        $service = new \App\Services\MidtransPaymentService();
        $result = $service->testConnection($serverKey ?? '', $clientKey ?? '', $environment);

        return response()->json($result);
    }

    public function testMidtransTransaction(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);
        $environment = $request->input('environment', $store->active_midtrans_environment);
        $amount = (int) $request->input('amount', 10000);

        if ($amount < 1000) {
            $amount = 10000;
        }

        $service = new \App\Services\MidtransPaymentService();
        $result = $service->createTestTransaction($store, $amount, $environment);

        return response()->json($result);
    }

    public function testMidtransStatus(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);
        $orderId = (string) $request->input('order_id');
        $environment = $request->input('environment', $store->active_midtrans_environment);

        if (empty($orderId)) {
            return response()->json(['success' => false, 'message' => 'Order ID uji coba tidak ditemukan.'], 400);
        }

        $service = new \App\Services\MidtransPaymentService();
        $result = $service->checkTestTransactionStatus($store, $orderId, $environment);

        return response()->json($result);
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
