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
        'mayar_api_key',
        'mayar_webhook_token',
        'mayar_environment',
        'mayar_api_key_production',
        'mayar_webhook_token_production',
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
