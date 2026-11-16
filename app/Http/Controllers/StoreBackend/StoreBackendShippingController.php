<?php

namespace App\Http\Controllers\StoreBackend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use App\Services\KiriminAja\KiriminAjaClient;
use App\Services\KiriminAja\KiriminAjaException;
use App\Services\KiriminAja\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreBackendShippingController extends Controller
{
    protected function getStore(Request $request): Store
    {
        return $request->attributes->get('store');
    }

    /**
     * Simulasi biaya pengiriman dari alamat asal toko ke tujuan pilihan admin.
     */
    public function simulate(Request $request, string $store_slug, ShippingService $shipping): JsonResponse
    {
        $store = $this->getStore($request);

        $validated = $request->validate([
            'destination_district_id' => 'required|integer|min:1',
            'destination_subdistrict_id' => 'nullable|integer|min:1',
            'destination_latitude' => 'nullable|numeric|between:-90,90',
            'destination_longitude' => 'nullable|numeric|between:-180,180',
            'destination_address' => 'nullable|string|max:500',
            'weight_grams' => 'required|integer|min:1|max:50000',
            'item_value' => 'required|numeric|min:0',
            // Opsional override alamat asal dari form sebelum admin menekan Simpan
            'origin_district_id' => 'nullable|integer|min:1',
            'origin_subdistrict_id' => 'nullable|integer|min:1',
            'origin_postal_code' => 'nullable|digits:5',
            'origin_address' => 'nullable|string|max:500',
            'origin_province_name' => 'nullable|string|max:120',
            'origin_city_name' => 'nullable|string|max:120',
            'origin_district_name' => 'nullable|string|max:120',
            'origin_subdistrict_name' => 'nullable|string|max:120',
            'origin_latitude' => 'nullable|numeric|between:-90,90',
            'origin_longitude' => 'nullable|numeric|between:-180,180',
            'delivery_couriers' => 'nullable|array',
            'delivery_couriers.*' => 'string|max:40',
        ]);

        $simStore = $store->replicate();
        $simStore->delivery_weight_per_item = (int) $validated['weight_grams'];

        if (!empty($validated['origin_district_id'])) {
            $simStore->ka_district_id = (int) $validated['origin_district_id'];
        }
        if (!empty($validated['origin_subdistrict_id'])) {
            $simStore->ka_subdistrict_id = (int) $validated['origin_subdistrict_id'];
        }
        if (!empty($validated['origin_postal_code'])) {
            $simStore->ka_postal_code = $validated['origin_postal_code'];
        }
        if (!empty($validated['origin_address'])) {
            $simStore->ka_address = $validated['origin_address'];
        }
        if (!empty($validated['origin_province_name'])) {
            $simStore->ka_province_name = $validated['origin_province_name'];
        }
        if (!empty($validated['origin_city_name'])) {
            $simStore->ka_city_name = $validated['origin_city_name'];
        }
        if (!empty($validated['origin_district_name'])) {
            $simStore->ka_district_name = $validated['origin_district_name'];
        }
        if (!empty($validated['origin_subdistrict_name'])) {
            $simStore->ka_subdistrict_name = $validated['origin_subdistrict_name'];
        }
        if (isset($validated['origin_latitude'], $validated['origin_longitude']) && $validated['origin_latitude'] !== null && $validated['origin_longitude'] !== null) {
            $simStore->ka_latitude = (float) $validated['origin_latitude'];
            $simStore->ka_longitude = (float) $validated['origin_longitude'];
        }
        if (array_key_exists('delivery_couriers', $validated)) {
            $simStore->delivery_couriers = array_values(array_filter((array) $validated['delivery_couriers']));
        }

        if (!$simStore->hasDeliveryOriginConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Lengkapi alamat asal pengiriman toko (Provinsi, Kota, Kecamatan, Kelurahan, Kode Pos, Alamat, serta koordinat Peta) terlebih dahulu.',
            ], 422);
        }

        try {
            $quote = $shipping->quote(
                $simStore,
                [
                    'district_id' => (int) $validated['destination_district_id'],
                    'subdistrict_id' => isset($validated['destination_subdistrict_id']) ? (int) $validated['destination_subdistrict_id'] : null,
                    'latitude' => isset($validated['destination_latitude']) ? (float) $validated['destination_latitude'] : null,
                    'longitude' => isset($validated['destination_longitude']) ? (float) $validated['destination_longitude'] : null,
                    'address' => $validated['destination_address'] ?? 'Alamat Tujuan Simulasi',
                ],
                1,
                (int) round((float) $validated['item_value'])
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'origin' => $simStore->delivery_origin_full_address,
                    'weight_grams' => $quote['weight'],
                    'options' => $quote['options'],
                    'errors' => $quote['errors'],
                ],
            ]);
        } catch (KiriminAjaException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 502);
        }
    }

    /**
     * Ambil ringkasan akun KiriminAja (saldo KA Credit, daftar kurir aktif).
     */
    public function accountInfo(Request $request, string $store_slug, KiriminAjaClient $ka): JsonResponse
    {
        if (!$ka->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'KIRIMINAJA_API_KEY belum dikonfigurasi di .env.',
            ], 422);
        }

        try {
            $balance = null;
            try {
                $balance = $ka->creditBalance();
            } catch (KiriminAjaException $e) {
                $balance = ['error' => $e->getMessage()];
            }

            $couriers = $ka->couriers();

            return response()->json([
                'success' => true,
                'data' => [
                    'environment' => $ka->environment(),
                    'balance' => $balance,
                    'couriers' => $couriers,
                ],
            ]);
        } catch (KiriminAjaException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 502);
        }
    }

    /**
     * Buat pickup / request pengiriman ke KiriminAja untuk order delivery yang sudah dibayar.
     */
    public function bookShipment(Request $request, string $store_slug, Order $order, ShippingService $shipping)
    {
        $store = $this->getStore($request);
        if ((int) $order->store_id !== (int) $store->id) {
            abort(403, 'Pesanan bukan milik cabang toko ini.');
        }

        try {
            $result = $shipping->createShipment($order);
            $msg = 'Pickup KiriminAja berhasil dibuat!'
                . (!empty($result['awb']) ? ' No. Resi (AWB): ' . $result['awb'] : ' Order ID: ' . ($result['order_id'] ?? $order->ka_order_id));

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'data' => $result]);
            }
            return back()->with('success', $msg);
        } catch (KiriminAjaException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', 'Gagal membuat pickup KiriminAja: ' . $e->getMessage());
        }
    }

    /**
     * Sinkronkan status tracking terbaru dari KiriminAja.
     */
    public function refreshShipment(Request $request, string $store_slug, Order $order, ShippingService $shipping)
    {
        $store = $this->getStore($request);
        if ((int) $order->store_id !== (int) $store->id) {
            abort(403, 'Pesanan bukan milik cabang toko ini.');
        }

        try {
            $tracking = $shipping->refreshTracking($order);
            $msg = 'Status pengiriman diperbarui: ' . ($tracking['label'] ?? $tracking['status'])
                . (!empty($tracking['awb']) ? ' (AWB: ' . $tracking['awb'] . ')' : '');

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'data' => $tracking]);
            }
            return back()->with('success', $msg);
        } catch (KiriminAjaException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', 'Gagal refresh tracking: ' . $e->getMessage());
        }
    }

    /**
     * Batalkan shipment di KiriminAja.
     */
    public function cancelShipment(Request $request, string $store_slug, Order $order, ShippingService $shipping)
    {
        $store = $this->getStore($request);
        if ((int) $order->store_id !== (int) $store->id) {
            abort(403, 'Pesanan bukan milik cabang toko ini.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:200',
        ]);

        try {
            $res = $shipping->cancelShipment($order, $validated['reason']);
            $msg = 'Pengiriman KiriminAja berhasil dibatalkan.';

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'data' => $res]);
            }
            return back()->with('success', $msg);
        } catch (KiriminAjaException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', 'Gagal membatalkan pengiriman: ' . $e->getMessage());
        }
    }
}

