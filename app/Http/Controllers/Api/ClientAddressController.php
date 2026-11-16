<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientAddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $client = $this->resolveCustomer($request);
        if (!$client) {
            return $this->unauthorized();
        }

        return response()->json([
            'success' => true,
            'data' => $client->addresses()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $client = $this->resolveCustomer($request);
        if (!$client) {
            return $this->unauthorized();
        }

        $validated = $this->validatePayload($request);

        $address = DB::transaction(function () use ($client, $validated) {
            $hasAny = $client->addresses()->exists();
            $makeDefault = !$hasAny || !empty($validated['is_default']);

            if ($makeDefault) {
                $client->addresses()->update(['is_default' => false]);
            }

            $created = $client->addresses()->create([
                'label' => $validated['label'],
                'recipient_name' => $validated['recipient_name'] ?? $client->name,
                'phone' => $validated['phone'] ?? $client->whatsapp,
                'province_id' => (int) $validated['province_id'],
                'province_name' => $validated['province_name'],
                'city_id' => (int) $validated['city_id'],
                'city_name' => $validated['city_name'],
                'district_id' => (int) $validated['district_id'],
                'district_name' => $validated['district_name'],
                'subdistrict_id' => (int) $validated['subdistrict_id'],
                'subdistrict_name' => $validated['subdistrict_name'],
                'postal_code' => $validated['postal_code'],
                'address' => $validated['address'],
                'address_note' => $validated['address_note'] ?? null,
                'latitude' => (float) $validated['latitude'],
                'longitude' => (float) $validated['longitude'],
                'geo_address' => $validated['geo_address'] ?? null,
                'is_default' => $makeDefault,
            ]);

            if ($makeDefault) {
                $client->update(['address' => $created->full_address]);
            }

            return $created;
        });

        return response()->json([
            'success' => true,
            'message' => 'Alamat berhasil disimpan ke buku alamat.',
            'data' => $address->fresh(),
            'addresses' => $client->addresses()->get(),
        ], 201);
    }

    public function update(Request $request, ClientAddress $address): JsonResponse
    {
        $client = $this->resolveCustomer($request);
        if (!$client) {
            return $this->unauthorized();
        }
        if ($address->client_id !== $client->id) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $validated = $this->validatePayload($request);

        DB::transaction(function () use ($client, $address, $validated) {
            $makeDefault = $address->is_default || !empty($validated['is_default']);
            if (!empty($validated['is_default'])) {
                $client->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            }

            $address->update([
                'label' => $validated['label'],
                'recipient_name' => $validated['recipient_name'] ?? $address->recipient_name ?? $client->name,
                'phone' => $validated['phone'] ?? $address->phone ?? $client->whatsapp,
                'province_id' => (int) $validated['province_id'],
                'province_name' => $validated['province_name'],
                'city_id' => (int) $validated['city_id'],
                'city_name' => $validated['city_name'],
                'district_id' => (int) $validated['district_id'],
                'district_name' => $validated['district_name'],
                'subdistrict_id' => (int) $validated['subdistrict_id'],
                'subdistrict_name' => $validated['subdistrict_name'],
                'postal_code' => $validated['postal_code'],
                'address' => $validated['address'],
                'address_note' => $validated['address_note'] ?? null,
                'latitude' => (float) $validated['latitude'],
                'longitude' => (float) $validated['longitude'],
                'geo_address' => $validated['geo_address'] ?? null,
                'is_default' => $makeDefault,
            ]);

            if ($makeDefault) {
                $client->update(['address' => $address->fresh()->full_address]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Alamat berhasil diperbarui.',
            'data' => $address->fresh(),
            'addresses' => $client->addresses()->get(),
        ]);
    }

    public function setDefault(Request $request, ClientAddress $address): JsonResponse
    {
        $client = $this->resolveCustomer($request);
        if (!$client) {
            return $this->unauthorized();
        }
        if ($address->client_id !== $client->id) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        DB::transaction(function () use ($client, $address) {
            $client->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);
            $client->update(['address' => $address->fresh()->full_address]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Alamat utama berhasil diubah.',
            'addresses' => $client->addresses()->get(),
        ]);
    }

    public function destroy(Request $request, ClientAddress $address): JsonResponse
    {
        $client = $this->resolveCustomer($request);
        if (!$client) {
            return $this->unauthorized();
        }
        if ($address->client_id !== $client->id) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        DB::transaction(function () use ($client, $address) {
            $wasDefault = $address->is_default;
            $address->delete();

            if ($wasDefault) {
                $next = $client->addresses()->first();
                if ($next) {
                    $next->update(['is_default' => true]);
                    $client->update(['address' => $next->full_address]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Alamat berhasil dihapus.',
            'addresses' => $client->addresses()->get(),
        ]);
    }

    protected function resolveCustomer(Request $request): ?Client
    {
        $sessionId = session('customer_client_id');
        if ($sessionId) {
            $client = Client::find($sessionId);
            if ($client && $client->is_loyalty_member && $client->is_active) {
                return $client;
            }
        }

        $clientId = $request->input('client_id') ?? $request->header('X-Customer-Id');
        $email = strtolower(trim((string) ($request->input('email') ?? $request->input('customer_email') ?? $request->header('X-Customer-Email') ?? '')));

        if ($clientId && $email !== '') {
            return Client::where('id', $clientId)
                ->where('email', $email)
                ->where('is_loyalty_member', true)
                ->where('is_active', true)
                ->first();
        }

        return null;
    }

    protected function validatePayload(Request $request): array
    {
        return $request->validate([
            'label' => 'required|string|max:50',
            'recipient_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'province_id' => 'required|integer|min:1',
            'province_name' => 'required|string|max:120',
            'city_id' => 'required|integer|min:1',
            'city_name' => 'required|string|max:120',
            'district_id' => 'required|integer|min:1',
            'district_name' => 'required|string|max:120',
            'subdistrict_id' => 'required|integer|min:1',
            'subdistrict_name' => 'required|string|max:120',
            'postal_code' => 'required|digits:5',
            'address' => 'required|string|min:10|max:500',
            'address_note' => 'nullable|string|max:150',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'geo_address' => 'nullable|string|max:500',
            'is_default' => 'nullable|boolean',
        ]);
    }

    protected function unauthorized(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Sesi Pelanggan Setia tidak valid atau belum login.',
        ], 401);
    }
}

