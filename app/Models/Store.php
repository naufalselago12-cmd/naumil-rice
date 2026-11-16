<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Store extends Model
{
    use HasFactory;

    public const PAYMENT_TYPE_QRIS_STATIC = 'qris_static';
    public const PAYMENT_TYPE_GATEWAY = 'payment_gateway';

    public const GATEWAY_PROVIDER_MIDTRANS = 'midtrans';
    public const GATEWAY_PROVIDER_MAYAR = 'mayar';

    protected $fillable = [
        'name',
        'slug',
        'code',
        'address',
        'phone',
        'logo',
        'qris_image',
        'qris_data',
        'payment_type',
        'payment_gateway_provider',
        'mayar_api_key',
        'mayar_webhook_token',
        'mayar_environment',
        'mayar_api_key_production',
        'mayar_webhook_token_production',
        'midtrans_environment',
        'midtrans_server_key',
        'midtrans_client_key',
        'midtrans_merchant_id',
        'midtrans_server_key_production',
        'midtrans_client_key_production',
        'midtrans_merchant_id_production',
        'status',
        // Modul Kirim Langsung ke Rumah (KiriminAja)
        'delivery_enabled',
        'ka_province_id',
        'ka_province_name',
        'ka_city_id',
        'ka_city_name',
        'ka_district_id',
        'ka_district_name',
        'ka_subdistrict_id',
        'ka_subdistrict_name',
        'ka_postal_code',
        'ka_address',
        'ka_address_note',
        'ka_latitude',
        'ka_longitude',
        'ka_geo_address',
        'delivery_couriers',
        'delivery_weight_per_item',
        'delivery_package_length',
        'delivery_package_width',
        'delivery_package_height',
    ];

    protected $casts = [
        'delivery_enabled' => 'boolean',
        'delivery_couriers' => 'array',
        'ka_latitude' => 'float',
        'ka_longitude' => 'float',
        'ka_province_id' => 'integer',
        'ka_city_id' => 'integer',
        'ka_district_id' => 'integer',
        'ka_subdistrict_id' => 'integer',
        'delivery_weight_per_item' => 'integer',
        'delivery_package_length' => 'integer',
        'delivery_package_width' => 'integer',
        'delivery_package_height' => 'integer',
    ];

    protected static function booted()
    {
        static::saving(function ($store) {
            if (empty($store->slug) && !empty($store->name)) {
                $baseSlug = Str::slug($store->name);
                $store->slug = $baseSlug;
            }
        });
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function menus()
    {
        return $this->hasMany(Menu::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function loyaltyCustomers()
    {
        return $this->belongsToMany(Client::class, 'client_stores')
                    ->withPivot('points')
                    ->withTimestamps();
    }

    public function loyaltyPromos()
    {
        return $this->hasMany(LoyaltyPromo::class);
    }

    public function getFrontendUrlAttribute(): string
    {
        return url('/' . ($this->slug ?: Str::slug($this->name)));
    }

    public function getBackendUrlAttribute(): string
    {
        return url('/' . ($this->slug ?: Str::slug($this->name)) . '/backend');
    }

    public function isQrisStatic(): bool
    {
        return $this->payment_type !== self::PAYMENT_TYPE_GATEWAY;
    }

    public function isPaymentGateway(): bool
    {
        return $this->payment_type === self::PAYMENT_TYPE_GATEWAY;
    }

    public function isMidtrans(): bool
    {
        return $this->isPaymentGateway() && ($this->payment_gateway_provider ?? self::GATEWAY_PROVIDER_MIDTRANS) === self::GATEWAY_PROVIDER_MIDTRANS;
    }

    public function isMayar(): bool
    {
        return $this->isPaymentGateway() && ($this->payment_gateway_provider ?? '') === self::GATEWAY_PROVIDER_MAYAR;
    }

    public function getActiveGatewayProvider(): string
    {
        return $this->payment_gateway_provider ?? self::GATEWAY_PROVIDER_MIDTRANS;
    }

    public function getActiveMidtransEnvironmentAttribute(): string
    {
        return $this->midtrans_environment ?? 'sandbox';
    }

    public function getActiveMidtransServerKeyAttribute(): ?string
    {
        if ($this->active_midtrans_environment === 'production') {
            return !empty($this->midtrans_server_key_production) ? $this->midtrans_server_key_production : $this->midtrans_server_key;
        }
        return $this->midtrans_server_key;
    }

    public function getActiveMidtransClientKeyAttribute(): ?string
    {
        if ($this->active_midtrans_environment === 'production') {
            return !empty($this->midtrans_client_key_production) ? $this->midtrans_client_key_production : $this->midtrans_client_key;
        }
        return $this->midtrans_client_key;
    }

    public function getActiveMidtransMerchantIdAttribute(): ?string
    {
        if ($this->active_midtrans_environment === 'production') {
            return !empty($this->midtrans_merchant_id_production) ? $this->midtrans_merchant_id_production : $this->midtrans_merchant_id;
        }
        return $this->midtrans_merchant_id;
    }

    public function hasMidtransConfigured(): bool
    {
        return !empty($this->active_midtrans_server_key) && !empty($this->active_midtrans_client_key);
    }

    public function hasMidtransSandboxConfigured(): bool
    {
        return !empty($this->midtrans_server_key) && !empty($this->midtrans_client_key);
    }

    public function hasMidtransProductionConfigured(): bool
    {
        return !empty($this->midtrans_server_key_production) && !empty($this->midtrans_client_key_production);
    }

    public function getActiveMayarApiKeyAttribute(): ?string
    {
        if (($this->mayar_environment ?? 'sandbox') === 'production') {
            return !empty($this->mayar_api_key_production) ? $this->mayar_api_key_production : $this->mayar_api_key;
        }
        return $this->mayar_api_key;
    }

    public function getActiveMayarWebhookTokenAttribute(): ?string
    {
        if (($this->mayar_environment ?? 'sandbox') === 'production') {
            return !empty($this->mayar_webhook_token_production) ? $this->mayar_webhook_token_production : $this->mayar_webhook_token;
        }
        return $this->mayar_webhook_token;
    }

    public function hasMayarConfigured(): bool
    {
        return !empty($this->active_mayar_api_key);
    }

    public function hasSandboxConfigured(): bool
    {
        return !empty($this->mayar_api_key);
    }

    public function hasProductionConfigured(): bool
    {
        return !empty($this->mayar_api_key_production);
    }

    /*
    |--------------------------------------------------------------------------
    | Kirim Langsung ke Rumah (KiriminAja)
    |--------------------------------------------------------------------------
    */

    /** Alamat & geotag toko sudah lengkap sebagai titik asal pengiriman? */
    public function hasDeliveryOriginConfigured(): bool
    {
        return !empty($this->ka_province_id)
            && !empty($this->ka_city_id)
            && !empty($this->ka_district_id)
            && !empty($this->ka_subdistrict_id)
            && !empty($this->ka_address)
            && !empty($this->ka_postal_code)
            && $this->ka_latitude !== null
            && $this->ka_longitude !== null;
    }

    /** Opsi "Kirim Langsung ke Rumah" tersedia untuk pembeli? */
    public function isDeliveryAvailable(): bool
    {
        return (bool) config('kiriminaja.enabled')
            && (bool) $this->delivery_enabled
            && $this->hasDeliveryOriginConfigured();
    }

    /** Alamat lengkap asal pengiriman (dikirim sebagai alamat pickup ke KiriminAja). */
    public function getDeliveryOriginFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->ka_address,
            $this->ka_subdistrict_name,
            $this->ka_district_name,
            $this->ka_city_name,
            $this->ka_province_name,
            $this->ka_postal_code,
        ], fn ($v) => $v !== null && trim((string) $v) !== '');

        return implode(', ', $parts);
    }
}
