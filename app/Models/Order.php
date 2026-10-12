<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public const STATUS_CONFIRMED = 'Order Konfirmed';
    public const STATUS_PROCESSING = 'Order On Proses';
    public const STATUS_PACKAGING = 'Order On Packaging';
    public const STATUS_DELIVERY = 'Order On Delivery';
    public const STATUS_READY = 'Order Ready';
    public const STATUS_REJECT = 'Order Reject';

    public const ALL_STATUSES = [
        self::STATUS_CONFIRMED,
        self::STATUS_PROCESSING,
        self::STATUS_PACKAGING,
        self::STATUS_DELIVERY,
        self::STATUS_READY,
        self::STATUS_REJECT,
    ];

    protected $fillable = [
        'order_number',
        'store_id',
        'campaign_id',
        'client_id',
        'status',
        'payment_method',
        'payment_status',
        'subtotal',
        'discount_amount',
        'total_amount',
        'notes',
        'rejection_reason',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'discount_amount' => 'float',
        'total_amount' => 'float',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(OrderStatusLog::class)->orderBy('id', 'desc');
    }
}
