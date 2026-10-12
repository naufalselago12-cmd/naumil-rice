<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'logo',
        'qris_image',
        'qris_data',
        'status',
    ];

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
}
