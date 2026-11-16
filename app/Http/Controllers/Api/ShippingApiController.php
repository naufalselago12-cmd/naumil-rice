<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\GeoService;
use App\Services\KiriminAja\KiriminAjaClient;
use App\Services\KiriminAja\KiriminAjaException;
use App\Services\KiriminAja\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShippingApiController extends Controller
{
    public function provinces(KiriminAjaClient $ka): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $ka->provinces(),
            ]);
        } catch (KiriminAjaException $e) {
            return $this->errorResponse($e);
        }
    }

    public function cities(Request $request, KiriminAjaClient $ka): JsonResponse
    {
        $validated = $request->validate([
            'province_id' => 'required|integer|min:1',
        ]);

        try {
            return response()->json([
                'success' => true,
                'data' => $ka->cities((int) $validated['province_id']),
            ]);
        } catch (KiriminAjaException $e) {
            return $this->errorResponse($e);
        }
    }

    public function districts(Request $request, KiriminAjaClient $ka): JsonResponse
    {
        $validated = $request->validate([
            'city_id' => 'required|integer|min:1',
        ]);

        try {
            return response()->json([
                'success' => true,
                'data' => $ka->districts((int) $validated['city_id']),
            ]);
        } catch (KiriminAjaException $e) {
            return $this->errorResponse($e);
        }
    }

    public function subdistricts(Request $request, KiriminAjaClient $ka): JsonResponse
    {
        $validated = $request->validate([
            'district_id' => 'required|integer|min:1',
        ]);

        try {
            return response()->json([
                'success' => true,
                'data' => $ka->subdistricts((int) $validated['district_id']),
            ]);
        } catch (KiriminAjaException $e) {
            return $this->errorResponse($e);
        }
    }

    public function searchAddresses(Request $request, KiriminAjaClient $ka): JsonResponse
    {
        $validated = $request->validate([
            'keyword' => 'required|string|min:2|max:120',
        ]);

        try {
            return response()->json([
                'success' => true,
                'data' => $ka->searchAddresses($validated['keyword']),
            ]);
        } catch (KiriminAjaException $e) {
            return $this->errorResponse($e);
        }
    }

    public function geoReverse(Request $request, GeoService $geo, KiriminAjaClient $ka): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'match_region' => 'nullable|boolean',
        ]);

        $info = $geo->reverse((float) $validated['latitude'], (float) $validated['longitude']);
        if (!$info) {
            return response()->json([
                'success' => false,
                'message' => 'Alamat tidak dapat dideteksi dari koordinat tersebut.',
            ], 404);
        }

        if (!empty($validated['match_region']) && $ka->isConfigured()) {
            try {
                $info['matched_region'] = $geo->matchRegion($ka, $info['region_hints'] ?? []);
            } catch (\Throwable $e) {
                $info['matched_region'] = null;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $info,
        ]);
    }

    public function geoSearch(Request $request, GeoService $geo): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:150',
        ]);

        return response()->json([
            'success' => true,
            'data' => $geo->search($validated['q']),
        ]);
    }

    public function quote(Request $request, ShippingService $shipping): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'district_id' => 'required|integer|min:1',
            'subdistrict_id' => 'nullable|integer|min:1',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'address' => 'nullable|string|max:500',
            'total_qty' => 'nullable|integer|min:1',
            'item_value' => 'nullable|numeric|min:0',
        ]);

        $store = Store::findOrFail($validated['store_id']);
        if (!$store->isDeliveryAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'Pengiriman KiriminAja belum diaktifkan atau alamat asal toko belum lengkap.',
            ], 422);
        }

        try {
            $result = $shipping->quote(
                $store,
                [
                    'district_id' => (int) $validated['district_id'],
                    'subdistrict_id' => isset($validated['subdistrict_id']) ? (int) $validated['subdistrict_id'] : null,
                    'latitude' => isset($validated['latitude']) ? (float) $validated['latitude'] : null,
                    'longitude' => isset($validated['longitude']) ? (float) $validated['longitude'] : null,
                    'address' => $validated['address'] ?? null,
                ],
                (int) ($validated['total_qty'] ?? 1),
                (int) round((float) ($validated['item_value'] ?? 10000))
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (KiriminAjaException $e) {
            return $this->errorResponse($e);
        }
    }

    public function webhook(Request $request, ShippingService $shipping): JsonResponse
    {
        $configuredKey = (string) config('kiriminaja.api_key');
        if ($configuredKey !== '') {
            $authHeader = trim((string) $request->header('Authorization', ''));
            if (!hash_equals('Bearer ' . $configuredKey, $authHeader)) {
                Log::warning('[KiriminAja webhook] Unauthorized attempt');
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }
        }

        $payload = $request->all();
        $updated = $shipping->handleWebhook($payload);

        return response()->json([
            'status' => true,
            'updated' => $updated,
        ]);
    }

    protected function errorResponse(KiriminAjaException $e): JsonResponse
    {
        $status = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 502;
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], $status);
    }
}

