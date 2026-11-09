<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CancellationReason extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'reason',
        'applies_to',
        'requires_remark',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'requires_remark' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order', 'asc');
    }

    public function scopeForCustomer($query)
    {
        return $query->whereIn('applies_to', ['customer', 'all']);
    }

    public function scopeForAdmin($query)
    {
        return $query->whereIn('applies_to', ['admin', 'all']);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'cancellation_reason_id');
    }
}

