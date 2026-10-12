<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'description',
        'price',
        'initial_stock',
        'current_stock',
        'image',
        'is_active',
    ];

    protected $casts = [
        'price' => 'float',
        'initial_stock' => 'integer',
        'current_stock' => 'integer',
        'is_active' => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function options()
    {
        return $this->hasMany(MenuOption::class);
    }

    public function toppings()
    {
        return $this->hasMany(MenuOption::class)->where('type', 'topping');
    }

    public function upsizes()
    {
        return $this->hasMany(MenuOption::class)->where('type', 'upsize');
    }

    public function conditions()
    {
        return $this->hasMany(MenuOption::class)->where('type', 'condition');
    }

    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_menus')
                    ->withPivot('custom_price', 'discount_price', 'max_quota')
                    ->withTimestamps();
    }
}
