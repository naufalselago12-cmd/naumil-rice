<?php

/*
|--------------------------------------------------------------------------
| KiriminAja Mitra API (Kirim Langsung ke Rumah)
|--------------------------------------------------------------------------
| Dokumentasi : https://developer.kiriminaja.com/docs
| Sandbox     : https://tdev.kiriminaja.com
| Production  : https://client.kiriminaja.com
|
| Isi di .env :
|   KIRIMINAJA_ENV=sandbox            (sandbox | production)
|   KIRIMINAJA_API_KEY=xxxxxxxx       (Dashboard KiriminAja > Integrasi)
*/

return [

    // Saklar global modul. Per toko tetap ada saklar sendiri (stores.delivery_enabled).
    'enabled' => env('KIRIMINAJA_ENABLED', true),

    'environment' => env('KIRIMINAJA_ENV', 'sandbox'),

    'api_key' => env('KIRIMINAJA_API_KEY'),

    'base_urls' => [
        'sandbox' => 'https://tdev.kiriminaja.com',
        'production' => 'https://client.kiriminaja.com',
    ],

    'timeout' => (int) env('KIRIMINAJA_TIMEOUT', 25),

    // Data wilayah jarang berubah & sandbox dibatasi 10 request/menit -> wajib di-cache.
    'cache' => [
        'coverage_ttl' => (int) env('KIRIMINAJA_CACHE_COVERAGE', 86400),   // detik (1 hari)
        'couriers_ttl' => (int) env('KIRIMINAJA_CACHE_COURIERS', 21600),   // 6 jam
        'pricing_ttl' => (int) env('KIRIMINAJA_CACHE_PRICING', 300),       // 5 menit
    ],

    // Kurir yang boleh dipakai bila toko belum memilih sendiri.
    'default_express_couriers' => ['jne', 'jnt', 'sicepat', 'anteraja', 'ninja', 'sap', 'idx', 'tiki'],
    'default_instant_couriers' => ['gosend', 'grab_express', 'borzo'],

    // Parameter instant delivery.
    'instant' => [
        'vehicle' => env('KIRIMINAJA_INSTANT_VEHICLE', 'motor'),   // motor | mobil
        'timezone' => env('KIRIMINAJA_TIMEZONE', 'WIB'),            // WIB | WIT | WITA
        'max_weight_grams' => 40000,
        'max_distance_km' => 40,
    ],

    // Default paket (bisa diubah per toko).
    'package' => [
        'weight_per_item' => 500,   // gram / porsi
        'length' => 20,             // cm
        'width' => 20,
        'height' => 10,
        'type_id' => (int) env('KIRIMINAJA_PACKAGE_TYPE_ID', 7),
    ],

    'platform_name' => env('KIRIMINAJA_PLATFORM_NAME', 'naumil'),

    // Metode bayar ke KiriminAja saat membuat pickup (cash = QRIS/bayar via dashboard, credit = KA Credit).
    'payment_method' => env('KIRIMINAJA_PAYMENT_METHOD', 'credit'),
    'pin' => env('KIRIMINAJA_PIN'),

    // Geocoder untuk geotag peta (OpenStreetMap Nominatim, BUKAN Google Maps).
    'geocoder' => [
        'base_url' => env('GEOCODER_BASE_URL', 'https://nominatim.openstreetmap.org'),
        // Kebijakan Nominatim mewajibkan User-Agent yang mengidentifikasi aplikasi.
        'user_agent' => env('GEOCODER_USER_AGENT', 'NaumilStore/1.0 (delivery geotag)'),
        'email' => env('GEOCODER_EMAIL'),
        'country_codes' => 'id',
        'cache_ttl' => 86400,
    ],
];

