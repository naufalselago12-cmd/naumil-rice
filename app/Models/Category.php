<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'description',
        'display_order',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function menus()
    {
        return $this->hasMany(Menu::class);
    }
}
