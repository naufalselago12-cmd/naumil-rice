<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_id',
        'type',
        'name',
        'price',
        'is_required',
    ];

    protected $casts = [
        'price' => 'float',
        'is_required' => 'boolean',
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}
