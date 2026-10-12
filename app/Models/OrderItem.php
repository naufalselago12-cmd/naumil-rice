<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'menu_id',
        'menu_name',
        'price',
        'quantity',
        'subtotal',
        'selected_options',
        'special_notes',
    ];

    protected $casts = [
        'price' => 'float',
        'quantity' => 'integer',
        'subtotal' => 'float',
        'selected_options' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}
