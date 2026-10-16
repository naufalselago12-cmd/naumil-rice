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
        'is_active',
        'activation_token',
        'activated_at',
        'last_order_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'activated_at' => 'datetime',
        'last_order_at' => 'datetime',
    ];

    protected $appends = ['display_label', 'display_concat'];

    public function getDisplayLabelAttribute(): string
    {
        return "{$this->name} [{$this->whatsapp}, {$this->email}]";
    }

    public function getDisplayConcatAttribute(): string
    {
        return "{$this->name} ({$this->email})";
    }

    public function generateActivationToken(): string
    {
        $this->activation_token = \Illuminate\Support\Str::random(48);
        $this->save();
        return $this->activation_token;
    }

    public function activate(): void
    {
        $this->update([
            'is_active' => true,
            'activated_at' => now(),
            'activation_token' => null,
        ]);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
