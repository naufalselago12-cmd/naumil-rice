<?php

namespace App\Services\KiriminAja;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pembungkus tipis seluruh endpoint KiriminAja Mitra API (v6.x).
 * Docs: https://developer.kiriminaja.com/docs
 *
 * Coverage Area  : province, city, kecamatan, kelurahan, addresses (unified search)
 * Pricing        : v6.1/shipping_price (express), v4/instant/pricing (instant)
 * Order          : v6.2/request_pickup (express), v6.2/instant/request_pickup (instant)
 * Tracking/Batal : tracking, v4/instant/tracking/{id}, v3/cancel_shipment, v4/instant/pickup/void/{id}
 * Lain-lain      : schedules, get_payment, credit/balance, pin/validate, couriers, courier_services,
 *                  couriers_group, set_whitelist_services, set_callback
 */
class KiriminAjaClient
{
    protected string $apiKey;
    protected string $environment;

    public function __construct(?string $apiKey = null, ?string $environment = null)
    {
        $this->apiKey = trim((string) ($apiKey ?? config('kiriminaja.api_key')));
        $this->environment = $environment ?? (config('kiriminaja.environment') === 'production' ? 'production' : 'sandbox');
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function environment(): string
    {
        return $this->environment;
    }

    public function baseUrl(): string
    {
        return rtrim(config("kiriminaja.base_urls.{$this->environment}"), '/');
    }

    /*
    |--------------------------------------------------------------------------
    | HTTP core
    |--------------------------------------------------------------------------
    */

    protected function http(): PendingRequest
    {
        if (!$this->isConfigured()) {
            throw KiriminAjaException::notConfigured();
        }

        return Http::baseUrl($this->baseUrl())
            ->withToken($this->apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('kiriminaja.timeout', 25));
    }

    /**
     * @return array<string,mixed>
     */
    protected function request(string $method, string $path, array $data = []): array
    {
        try {
            $http = $this->http();
            $response = match (strtoupper($method)) {
                'GET' => $http->get($path, $data),
                'DELETE' => $http->delete($path, $data),
                default => $http->send(strtoupper($method), $path, ['json' => (object) $data]),
            };
        } catch (ConnectionException $e) {
            Log::warning('[KiriminAja] connection error', ['path' => $path, 'error' => $e->getMessage()]);
            throw new KiriminAjaException('Tidak dapat terhubung ke layanan pengiriman. Coba lagi beberapa saat lagi.', 0);
        }

        $body = $response->json();
        $body = is_array($body) ? $body : [];

        if ($response->status() === 429) {
            throw new KiriminAjaException('Layanan pengiriman sedang sibuk (batas permintaan tercapai). Coba lagi dalam 1 menit.', 429, $body);
        }

        if (in_array($response->status(), [401, 403], true)) {
            throw new KiriminAjaException('API Key KiriminAja tidak valid atau tidak memiliki akses.', $response->status(), $body);
        }

        if ($response->failed()) {
            Log::warning('[KiriminAja] HTTP error', ['path' => $path, 'status' => $response->status(), 'body' => $body]);
            throw new KiriminAjaException(
                $this->extractMessage($body) ?: 'Layanan pengiriman mengembalikan error (HTTP ' . $response->status() . ').',
                $response->status(),
                $body
            );
        }

        // KiriminAja menandai kegagalan aplikasi lewat status:false
        if (array_key_exists('status', $body) && $body['status'] === false) {
            throw new KiriminAjaException($this->extractMessage($body) ?: 'Permintaan ditolak oleh KiriminAja.', 422, $body);
        }

        return $body;
    }

    protected function extractMessage(array $body): ?string
    {
        foreach (['text', 'message', 'statusMessage', 'error'] as $key) {
            if (!empty($body[$key]) && is_string($body[$key])) {
                return $body[$key];
            }
        }
        if (!empty($body['errors']) && is_array($body['errors'])) {
            $first = reset($body['errors']);
            return is_array($first) ? (string) reset($first) : (string) $first;
        }
        return null;
    }

    protected function cacheKey(string ...$parts): string
    {
        return 'kiriminaja:' . $this->environment . ':' . implode(':', $parts);
    }

    /*
    |--------------------------------------------------------------------------
    | Coverage Area (hierarki wilayah) - di-cache
    |--------------------------------------------------------------------------
    */

    /** @return array<int,array{id:int,name:string}> */
    public function provinces(): array
    {
        return Cache::remember($this->cacheKey('provinces'), config('kiriminaja.cache.coverage_ttl'), function () {
            $res = $this->request('POST', '/api/mitra/province');
            return collect($res['datas'] ?? [])
                ->map(fn ($r) => ['id' => (int) $r['id'], 'name' => trim($r['provinsi_name'])])
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
        });
    }

    /** @return array<int,array{id:int,name:string,postal_code:?string}> */
    public function cities(int $provinceId): array
    {
        return Cache::remember($this->cacheKey('cities', (string) $provinceId), config('kiriminaja.cache.coverage_ttl'), function () use ($provinceId) {
            $res = $this->request('POST', '/api/mitra/city', ['provinsi_id' => $provinceId]);
            return collect($res['datas'] ?? [])->map(function ($r) {
                $name = trim($r['kabupaten_name']);
                $type = trim((string) ($r['type'] ?? ''));
                if ($type !== '' && stripos($name, $type) !== 0) {
                    $name = $type . ' ' . $name;
                }
                return ['id' => (int) $r['id'], 'name' => $name, 'postal_code' => $r['postal_code'] ?? null];
            })->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
        });
    }

    /** @return array<int,array{id:int,name:string}> */
    public function districts(int $cityId): array
    {
        return Cache::remember($this->cacheKey('districts', (string) $cityId), config('kiriminaja.cache.coverage_ttl'), function () use ($cityId) {
            $res = $this->request('POST', '/api/mitra/kecamatan', ['kabupaten_id' => $cityId]);
            return collect($res['datas'] ?? [])
                ->map(fn ($r) => ['id' => (int) $r['id'], 'name' => trim($r['kecamatan_name'])])
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
        });
    }

    /** @return array<int,array{id:int,name:string}> */
    public function subdistricts(int $districtId): array
    {
        return Cache::remember($this->cacheKey('subdistricts', (string) $districtId), config('kiriminaja.cache.coverage_ttl'), function () use ($districtId) {
            $res = $this->request('POST', '/api/mitra/kelurahan', ['kecamatan_id' => $districtId]);
            // Endpoint ini memakai "results", bukan "datas".
            $rows = $res['results'] ?? $res['datas'] ?? [];
            return collect($rows)
                ->map(fn ($r) => ['id' => (int) $r['id'], 'name' => trim($r['kelurahan_name'])])
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
        });
    }

    /**
     * Pencarian alamat terpadu (provinsi/kota/kecamatan/kelurahan/kode pos). Min 3 karakter.
     *
     * @return array<int,array{province_id:int,city_id:int,district_id:int,subdistrict_id:int,full_address:string}>
     */
    public function searchAddresses(string $keyword): array
    {
        $keyword = trim($keyword);
        if (mb_strlen($keyword) < 3) {
            return [];
        }

        return Cache::remember($this->cacheKey('addr', md5(mb_strtolower($keyword))), config('kiriminaja.cache.coverage_ttl'), function () use ($keyword) {
            $res = $this->request('GET', '/api/mitra/v6.1/addresses', ['search' => $keyword]);
            return collect($res['data'] ?? [])->map(fn ($r) => [
                'province_id' => (int) $r['province_id'],
                'city_id' => (int) $r['city_id'],
                'district_id' => (int) $r['district_id'],
                'subdistrict_id' => (int) $r['subdistrict_id'],
                'full_address' => (string) $r['full_address'],
            ])->values()->all();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Kurir
    |--------------------------------------------------------------------------
    */

    /** @return array<int,array{code:string,name:string,type:string}> */
    public function couriers(): array
    {
        return Cache::remember($this->cacheKey('couriers'), config('kiriminaja.cache.couriers_ttl'), function () {
            $res = $this->request('POST', '/api/mitra/couriers');
            return collect($res['datas'] ?? [])->map(fn ($r) => [
                'code' => (string) $r['code'],
                'name' => (string) ($r['name'] ?? strtoupper($r['code'])),
                'type' => strtolower((string) ($r['type'] ?? 'express')),
            ])->values()->all();
        });
    }

    public function courierGroups(): array
    {
        return Cache::remember($this->cacheKey('courier_groups'), config('kiriminaja.cache.couriers_ttl'), function () {
            return $this->request('POST', '/api/mitra/couriers_group')['datas'] ?? [];
        });
    }

    public function courierServices(string $courierCode): array
    {
        return Cache::remember($this->cacheKey('courier_services', $courierCode), config('kiriminaja.cache.couriers_ttl'), function () use ($courierCode) {
            return $this->request('POST', '/api/mitra/courier_services', ['courier_code' => $courierCode])['datas'] ?? [];
        });
    }

    /** Batasi kurir express yang dikembalikan pricing (hanya express). */
    public function setWhitelistServices(?array $couriers): array
    {
        return $this->request('POST', '/api/mitra/v3/set_whitelist_services', $couriers ? ['services' => array_values($couriers)] : []);
    }

    /** Daftarkan URL webhook akun (hanya 1 URL per akun - menimpa yang lama). */
    public function setCallback(string $url): array
    {
        return $this->request('POST', '/api/mitra/set_callback', ['url' => $url]);
    }

    /*
    |--------------------------------------------------------------------------
    | Pricing
    |--------------------------------------------------------------------------
    */

    /**
     * Ongkir express (berbasis ID kecamatan). Hasil di-cache singkat.
     *
     * @param array{origin:int,destination:int,weight:int,subdistrict_origin?:int,subdistrict_destination?:int,length?:int,width?:int,height?:int,insurance?:string,item_value?:int,courier?:array} $payload
     */
    public function expressPricing(array $payload): array
    {
        $key = $this->cacheKey('price_express', md5(json_encode($payload)));
        if ($cached = Cache::get($key)) {
            return $cached;
        }
        $res = $this->request('POST', '/api/mitra/v6.1/shipping_price', $payload);
        Cache::put($key, $res, config('kiriminaja.cache.pricing_ttl'));
        return $res;
    }

    /**
     * Ongkir instant (GoSend / Grab Express / Borzo) berbasis koordinat.
     *
     * @param array{service?:array,item_price:int,origin:array,destination:array,weight:int,vehicle:string,timezone:string} $payload
     */
    public function instantPricing(array $payload): array
    {
        $key = $this->cacheKey('price_instant', md5(json_encode($payload)));
        if ($cached = Cache::get($key)) {
            return $cached;
        }
        $res = $this->request('POST', '/api/mitra/v4/instant/pricing', $payload);
        Cache::put($key, $res, config('kiriminaja.cache.pricing_ttl'));
        return $res;
    }

    /*
    |--------------------------------------------------------------------------
    | Order / Pickup
    |--------------------------------------------------------------------------
    */

    public function schedules(): array
    {
        return $this->request('POST', '/api/mitra/v2/schedules')['schedules'] ?? [];
    }

    public function createExpressOrder(array $payload): array
    {
        return $this->request('POST', '/api/mitra/v6.2/request_pickup', $payload);
    }

    public function createInstantOrder(array $payload): array
    {
        return $this->request('POST', '/api/mitra/v6.2/instant/request_pickup', $payload);
    }

    /*
    |--------------------------------------------------------------------------
    | Tracking, pembatalan, pembayaran
    |--------------------------------------------------------------------------
    */

    /** $orderIdOrAwb: order id partner atau AWB. */
    public function trackExpress(string $orderIdOrAwb): array
    {
        return $this->request('POST', '/api/mitra/tracking', ['order_id' => $orderIdOrAwb]);
    }

    public function trackInstant(string $orderId): array
    {
        return $this->request('GET', '/api/mitra/v4/instant/tracking/' . rawurlencode($orderId));
    }

    /** Pembatalan express memakai AWB (bukan order id). Alasan 5-200 karakter. */
    public function cancelExpress(string $awb, string $reason): array
    {
        return $this->request('POST', '/api/mitra/v3/cancel_shipment', ['awb' => $awb, 'reason' => mb_substr(str_pad($reason, 5, '.'), 0, 200)]);
    }

    public function cancelInstant(string $orderId): array
    {
        return $this->request('DELETE', '/api/mitra/v4/instant/pickup/void/' . rawurlencode($orderId));
    }

    public function payment(string $paymentIdOrPickupNumber): array
    {
        return $this->request('POST', '/api/mitra/v2/get_payment', ['payment_id' => $paymentIdOrPickupNumber]);
    }

    /** [Beta] Saldo KA Credit. */
    public function creditBalance(): array
    {
        return $this->request('GET', '/api/mitra/v6.2/credit/balance');
    }

    /** [Beta] Validasi PIN 6 digit KA Credit. */
    public function validatePin(string $pin): array
    {
        return $this->request('POST', '/api/mitra/v6.2/pin/validate', ['pin' => $pin]);
    }
}

