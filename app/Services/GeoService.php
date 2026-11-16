<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Geocoding berbasis OpenStreetMap Nominatim (bukan Google Maps).
 *  - reverse : titik peta (lat,long) -> alamat
 *  - search  : teks -> daftar titik
 *
 * Dipanggil lewat server (bukan langsung dari browser) agar:
 *  - memenuhi kebijakan Nominatim (User-Agent yang jelas, cache, rate-limit)
 *  - hasil di-cache sehingga drag marker berulang tidak membebani layanan publik.
 */
class GeoService
{
    protected function http()
    {
        return Http::baseUrl(rtrim(config('kiriminaja.geocoder.base_url'), '/'))
            ->withHeaders([
                'User-Agent' => config('kiriminaja.geocoder.user_agent'),
                'Accept-Language' => 'id,en;q=0.5',
            ])
            ->acceptJson()
            ->timeout(12);
    }

    protected function baseParams(): array
    {
        $p = ['format' => 'jsonv2', 'addressdetails' => 1, 'accept-language' => 'id'];
        if ($email = config('kiriminaja.geocoder.email')) {
            $p['email'] = $email;
        }
        return $p;
    }

    /**
     * @return array{latitude:float,longitude:float,display_name:string,short_address:string,road:?string,village:?string,district:?string,city:?string,state:?string,postcode:?string,postal_code:?string,region_hints:array}|null
     */
    public function reverse(float $lat, float $lon): ?array
    {
        $lat = round($lat, 6);
        $lon = round($lon, 6);
        $key = 'geo:rev:' . round($lat, 5) . ':' . round($lon, 5);

        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached ?: null;
        }

        try {
            $res = $this->http()->get('/reverse', $this->baseParams() + ['lat' => $lat, 'lon' => $lon, 'zoom' => 18]);
        } catch (\Throwable $e) {
            Log::warning('[Geo] reverse gagal', ['error' => $e->getMessage()]);
            return null;
        }

        if ($res->failed() || !is_array($res->json()) || empty($res->json('display_name'))) {
            return null;
        }

        $data = $this->normalize($res->json(), $lat, $lon);
        Cache::put($key, $data, config('kiriminaja.geocoder.cache_ttl'));
        return $data;
    }

    /** @return array<int,array> */
    public function search(string $query): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 3) {
            return [];
        }

        $key = 'geo:search:' . md5(mb_strtolower($query));
        if ($cached = Cache::get($key)) {
            return $cached;
        }

        try {
            $res = $this->http()->get('/search', $this->baseParams() + [
                'q' => $query,
                'limit' => 6,
                'countrycodes' => config('kiriminaja.geocoder.country_codes', 'id'),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[Geo] search gagal', ['error' => $e->getMessage()]);
            return [];
        }

        if ($res->failed() || !is_array($res->json())) {
            return [];
        }

        $rows = collect($res->json())
            ->map(fn ($r) => $this->normalize($r, (float) $r['lat'], (float) $r['lon']))
            ->values()->all();

        Cache::put($key, $rows, config('kiriminaja.geocoder.cache_ttl'));
        return $rows;
    }

    protected function normalize(array $r, float $lat, float $lon): array
    {
        $a = $r['address'] ?? [];
        $displayName = (string) ($r['display_name'] ?? '');
        $road = $a['road'] ?? $a['pedestrian'] ?? $a['residential'] ?? null;
        // Level kelurahan/desa pada OSM Indonesia
        $village = $a['village'] ?? $a['suburb'] ?? $a['neighbourhood'] ?? $a['quarter'] ?? null;
        // Level kecamatan
        $district = $a['city_district'] ?? $a['municipality'] ?? $a['suburb'] ?? $a['county'] ?? null;
        $city = $a['city'] ?? $a['regency'] ?? $a['county'] ?? $a['town'] ?? null;
        $state = $a['state'] ?? null;
        $postcode = isset($a['postcode']) ? trim((string) $a['postcode']) : null;
        if ($postcode === '') {
            $postcode = null;
        }

        $shortParts = array_values(array_unique(array_filter([
            $road ? trim($road . (!empty($a['house_number']) ? ' No. ' . $a['house_number'] : '')) : null,
            $village,
            $district,
        ], fn ($v) => $v !== null && trim((string) $v) !== '')));
        $shortAddress = !empty($shortParts) ? implode(', ', $shortParts) : $displayName;

        return [
            'latitude' => $lat,
            'longitude' => $lon,
            'display_name' => $displayName,
            'short_address' => $shortAddress,
            'road' => $road,
            'village' => $village,
            'district' => $district,
            'city' => $city,
            'state' => $state,
            'postcode' => $postcode,
            'postal_code' => $postcode,
            'region_hints' => [
                'village' => $village,
                'district' => $district,
                'city' => $city,
                'state' => $state,
                'postcode' => $postcode,
            ],
        ];
    }

    /**
     * Tebak ID wilayah KiriminAja dari hasil reverse geocoding.
     * Mengembalikan kandidat terbaik atau null bila tidak cukup yakin.
     *
     * @param array{village?:?string,district?:?string,city?:?string,state?:?string,postcode?:?string} $hints
     */
    public function matchRegion(\App\Services\KiriminAja\KiriminAjaClient $ka, array $hints): ?array
    {
        $queries = array_values(array_unique(array_filter([
            trim(($hints['village'] ?? '') . ' ' . ($hints['district'] ?? '')),
            $hints['village'] ?? null,
            $hints['district'] ?? null,
        ], fn ($q) => $q && mb_strlen(trim($q)) >= 3)));

        $tokens = collect([$hints['village'] ?? '', $hints['district'] ?? '', $hints['city'] ?? ''])
            ->map(fn ($v) => $this->norm($v))->filter()->values();

        $best = null;
        $bestScore = 0;

        foreach (array_slice($queries, 0, 2) as $q) {
            foreach ($ka->searchAddresses($q) as $cand) {
                $hay = $this->norm($cand['full_address']);
                $score = 0;
                foreach ($tokens as $i => $t) {
                    if ($t !== '' && str_contains($hay, $t)) {
                        $score += [3, 2, 2][$i] ?? 1;
                    }
                }
                if (!empty($hints['postcode']) && str_contains($hay, (string) $hints['postcode'])) {
                    $score += 2;
                }
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = $cand;
                }
            }
            if ($bestScore >= 5) {
                break;
            }
        }

        if ($bestScore < 3 || !$best) {
            return null;
        }

        // Pecah full_address ("Kelurahan, Kecamatan, Kota, Provinsi[, KodePos]") agar dropdown bisa ikut terisi labelnya
        $parts = array_values(array_filter(array_map('trim', explode(',', (string) ($best['full_address'] ?? '')))));
        if (!empty($parts) && preg_match('/^\d{5}$/', (string) end($parts))) {
            array_pop($parts);
        }

        return array_merge($best, [
            'subdistrict_name' => $best['subdistrict_name'] ?? ($parts[0] ?? $hints['village'] ?? null),
            'district_name' => $best['district_name'] ?? ($parts[1] ?? $hints['district'] ?? null),
            'city_name' => $best['city_name'] ?? ($parts[2] ?? $hints['city'] ?? null),
            'province_name' => $best['province_name'] ?? ($parts[3] ?? $hints['state'] ?? null),
        ]);
    }

    protected function norm(?string $s): string
    {
        $s = mb_strtolower(trim((string) $s));
        $s = preg_replace('/\b(kecamatan|kec\.?|kelurahan|kel\.?|desa|kabupaten|kab\.?|kota)\b/u', '', $s);
        return trim(preg_replace('/\s+/', ' ', $s));
    }
}

