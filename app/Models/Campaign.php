<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'title',
        'slug',
        'banner_image',
        'description',
        'discount_type',
        'discount_value',
        'min_purchase_amount',
        'close_order_at',
        'ready_at',
        'qr_token',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'float',
        'min_purchase_amount' => 'float',
        'close_order_at' => 'datetime',
        'ready_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $appends = ['is_expired', 'is_open_for_order'];

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->is_active) {
            return true;
        }
        return Carbon::now()->greaterThanOrEqualTo($this->close_order_at);
    }

    public function getIsOpenForOrderAttribute(): bool
    {
        return $this->is_active && !$this->getIsExpiredAttribute();
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'campaign_menus')
                    ->withPivot('custom_price', 'discount_price', 'max_quota')
                    ->withTimestamps();
    }

    public function campaignMenus()
    {
        return $this->hasMany(CampaignMenu::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
