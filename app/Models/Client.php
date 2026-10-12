<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'whatsapp',
        'email',
        'address',
        'last_order_at',
    ];

    protected $casts = [
        'last_order_at' => 'datetime',
    ];

    protected $appends = ['display_label'];

    public function getDisplayLabelAttribute(): string
    {
        return "{$this->name} [{$this->whatsapp}, {$this->email}]";
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
