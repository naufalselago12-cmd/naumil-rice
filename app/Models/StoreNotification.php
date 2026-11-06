<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreNotification extends Model
{
    public const TYPE_COD = 'cod';       // Bayar setelah makanan jadi
    public const TYPE_DIRECT = 'direct'; // Bayar langsung (QRIS / Payment Gateway)

    protected $fillable = [
        'store_id',
        'order_id',
        'type',
        'event',
        'title',
        'body',
        'data',
        'push_status',
        'push_response',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

