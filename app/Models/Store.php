<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'code',
        'address',
        'phone',
        'logo',
        'qris_image',
        'qris_data',
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

    public function getFrontendUrlAttribute(): string
    {
        return url('/' . ($this->slug ?: Str::slug($this->name)));
    }

    public function getBackendUrlAttribute(): string
    {
        return url('/' . ($this->slug ?: Str::slug($this->name)) . '/backend');
    }
}
