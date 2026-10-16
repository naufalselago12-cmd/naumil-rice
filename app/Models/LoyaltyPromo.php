<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyPromo extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'title',
        'description',
        'promo_type',
        'discount_percent',
        'discount_amount',
        'min_purchase_amount',
        'allow_benefit_choice',
        'extra_points_multiplier',
        'extra_points_bonus',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'discount_percent' => 'float',
        'discount_amount' => 'float',
        'min_purchase_amount' => 'float',
        'allow_benefit_choice' => 'boolean',
        'extra_points_multiplier' => 'float',
        'extra_points_bonus' => 'integer',
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'loyalty_promo_menus')
            ->withPivot('discount_percent', 'discount_amount')
            ->withTimestamps();
    }

    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        return true;
    }
}
