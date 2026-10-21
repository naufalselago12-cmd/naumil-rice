<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\OrderItem;

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
        'min_order_qty',
        'min_purchase_amount',
        'close_order_at',
        'ready_at',
        'max_quota',
        'qr_token',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'float',
        'min_order_qty' => 'integer',
        'min_purchase_amount' => 'float',
        'close_order_at' => 'datetime',
        'ready_at' => 'datetime',
        'max_quota' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'is_expired',
        'is_open_for_order',
        'total_ordered',
        'remaining_quota',
        'is_sold_out',
    ];

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->is_active) {
            return true;
        }
        return Carbon::now()->greaterThanOrEqualTo($this->close_order_at);
    }

    public function getTotalOrderedAttribute(): int
    {
        return (int) OrderItem::whereHas('order', function ($q) {
            $q->where('campaign_id', $this->id)
              ->where('status', '!=', 'Order Reject');
        })->sum('quantity');
    }

    public function getRemainingQuotaAttribute(): ?int
    {
        if ($this->max_quota === null) {
            return null;
        }
        return max(0, $this->max_quota - $this->getTotalOrderedAttribute());
    }

    public function getIsSoldOutAttribute(): bool
    {
        if ($this->max_quota === null) {
            return false;
        }
        return $this->getRemainingQuotaAttribute() <= 0;
    }

    public function getIsOpenForOrderAttribute(): bool
    {
        return $this->is_active && !$this->getIsExpiredAttribute() && !$this->getIsSoldOutAttribute();
    }

    public function getMenuOrderedQuantity(int $menuId): int
    {
        return (int) OrderItem::whereHas('order', function ($q) {
            $q->where('campaign_id', $this->id)
              ->where('status', '!=', 'Order Reject');
        })->where('menu_id', $menuId)->sum('quantity');
    }

    public function getMenuRemainingQuota(int $menuId, ?int $menuMaxQuota): ?int
    {
        if ($menuMaxQuota === null) {
            return $this->remaining_quota;
        }
        $ordered = $this->getMenuOrderedQuantity($menuId);
        $menuRemaining = max(0, $menuMaxQuota - $ordered);
        if ($this->max_quota !== null) {
            $campaignRemaining = $this->remaining_quota ?? $menuRemaining;
            return min($menuRemaining, $campaignRemaining);
        }
        return $menuRemaining;
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
