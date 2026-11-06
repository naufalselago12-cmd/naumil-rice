<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OneSignal Web Push
    |--------------------------------------------------------------------------
    | App ID & REST API Key diambil dari OneSignal Dashboard
    | (Settings > Keys & IDs). REST API Key HANYA dipakai di server.
    */

    'app_id' => env('ONESIGNAL_APP_ID', '28d706da-25b6-4615-8728-1af01f3f871b'),

    'rest_api_key' => env('ONESIGNAL_REST_API_KEY'),

    // Opsional: khusus Safari (macOS) bila memakai Safari Web ID
    'safari_web_id' => env('ONESIGNAL_SAFARI_WEB_ID'),

    'api_url' => env('ONESIGNAL_API_URL', 'https://api.onesignal.com/notifications'),

    // Prefix external_id OneSignal untuk admin toko: "{prefix}{user_id}"
    'external_id_prefix' => env('ONESIGNAL_EXTERNAL_ID_PREFIX', 'naumil-store-admin-'),

    // ID channel notifikasi Android yang dibuat di aplikasi mobile (harus sama dengan MainActivity.kt)
    'android_channel_cod' => env('ONESIGNAL_ANDROID_CHANNEL_COD', 'naumil_cod_v1'),
    'android_channel_direct' => env('ONESIGNAL_ANDROID_CHANNEL_DIRECT', 'naumil_direct_v1'),

];

