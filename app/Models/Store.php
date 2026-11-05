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
}
