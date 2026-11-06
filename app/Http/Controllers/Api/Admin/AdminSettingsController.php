<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Store;
use App\Services\MayarPaymentService;
use App\Services\MidtransPaymentService;
use Illuminate\Http\Request;

class AdminSettingsController extends BaseAdminController
{
    /** Kredensial rahasia tidak pernah dikirim balik ke aplikasi; hanya status "sudah diisi". */
    private const SECRETS = [
        'midtrans_server_key', 'midtrans_client_key', 'midtrans_merchant_id',
        'midtrans_server_key_production', 'midtrans_client_key_production', 'midtrans_merchant_id_production',
        'mayar_api_key', 'mayar_webhook_token', 'mayar_api_key_production', 'mayar_webhook_token_production',
    ];

    public function show(Request $request)
    {
        return $this->ok(['data' => $this->present($this->currentStore($request))]);
    }

    /** POST agar upload logo / QRIS multipart bekerja. */
    public function update(Request $request)
    {
        $store = $this->currentStore($request);

        $v = $request->validate([
            'address' => 'required|string',
            'phone' => 'nullable|string|max:50',
            'logo' => 'nullable|string',
            'logo_file' => 'nullable|image|max:3072',
            'payment_type' => 'required|in:qris_static,payment_gateway',
            'payment_gateway_provider' => 'nullable|in:midtrans,mayar',
            'qris_image' => 'nullable|string',
            'qris_file' => 'nullable|image|max:3072',
            'qris_data' => 'nullable|string',
            'midtrans_environment' => 'nullable|in:sandbox,production',
            'mayar_environment' => 'nullable|in:sandbox,production',
            ...array_fill_keys(self::SECRETS, 'nullable|string'),
        ]);

        // Kredensial kosong = pertahankan nilai lama
        foreach (self::SECRETS as $key) {
            if (empty($v[$key])) {
                unset($v[$key]);
            }
        }

        $v['payment_gateway_provider'] = $v['payment_gateway_provider'] ?? Store::GATEWAY_PROVIDER_MIDTRANS;
        $v['midtrans_environment'] = $v['midtrans_environment'] ?? 'sandbox';
        $v['mayar_environment'] = $v['mayar_environment'] ?? 'sandbox';

        $has = fn (string $key) => !empty($v[$key]) || !empty($store->{$key});

        if ($v['payment_type'] === 'qris_static') {
            if (empty($store->qris_image) && !$request->hasFile('qris_file') && empty($v['qris_image'])) {
                return $this->fail('Metode QRIS Statik dipilih: wajib mengunggah gambar QRIS toko Anda.');
            }
        } elseif ($v['payment_gateway_provider'] === Store::GATEWAY_PROVIDER_MIDTRANS) {
            $suffix = $v['midtrans_environment'] === 'production' ? '_production' : '';
            if (!$has("midtrans_server_key{$suffix}") || !$has("midtrans_client_key{$suffix}")) {
                return $this->fail('Server Key & Client Key Midtrans ' . ($suffix ? 'Production' : 'Sandbox') . ' wajib diisi.');
            }
        } else {
            $suffix = $v['mayar_environment'] === 'production' ? '_production' : '';
            if (!$has("mayar_api_key{$suffix}")) {
                return $this->fail('API Key Mayar.id ' . ($suffix ? 'Production' : 'Sandbox') . ' wajib diisi.');
            }
        }

        if ($request->hasFile('logo_file')) {
            $v['logo'] = asset('storage/' . $request->file('logo_file')->store('stores/logos', 'public'));
        }
        if ($request->hasFile('qris_file')) {
            $v['qris_image'] = asset('storage/' . $request->file('qris_file')->store('stores/qris', 'public'));
        }
        unset($v['logo_file'], $v['qris_file']);

        $store->update($v);

        return $this->ok(['data' => $this->present($store->fresh())], 'Profil & metode pembayaran cabang berhasil disimpan.');
    }

    public function testMidtrans(Request $request)
    {
        $store = $this->currentStore($request);
        $env = $request->input('environment', 'sandbox');
        $production = $env === 'production';

        $server = $request->input('server_key')
            ?: ($production ? ($store->midtrans_server_key_production ?: $store->midtrans_server_key) : $store->midtrans_server_key);
        $client = $request->input('client_key')
            ?: ($production ? ($store->midtrans_client_key_production ?: $store->midtrans_client_key) : $store->midtrans_client_key);

        return response()->json((new MidtransPaymentService())->testConnection($server ?? '', $client ?? '', $env));
    }

    public function testMayar(Request $request)
    {
        $store = $this->currentStore($request);
        $env = $request->input('environment', 'sandbox');

        $key = $request->input('api_key')
            ?: ($env === 'production' ? ($store->mayar_api_key_production ?: $store->mayar_api_key) : $store->mayar_api_key);

        return response()->json((new MayarPaymentService())->testConnection($key ?? '', $env));
    }

    protected function fail(string $message)
    {
        return response()->json(['success' => false, 'message' => $message, 'errors' => ['payment_type' => [$message]]], 422);
    }

    protected function present(Store $store): array
    {
        $data = $store->only([
            'id', 'name', 'slug', 'code', 'address', 'phone', 'logo', 'qris_image', 'qris_data',
            'payment_type', 'payment_gateway_provider', 'midtrans_environment', 'mayar_environment',
        ]);
        $data['frontend_url'] = $store->frontend_url;
        $data['configured'] = collect(self::SECRETS)->mapWithKeys(fn ($k) => [$k => !empty($store->{$k})])->all();

        return $data;
    }
}
