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

    protected static function booted()
    {
        static::updated(function (Order $order) {
            if ($order->isDirty('status') && $order->status === self::STATUS_READY) {
                app(\App\Services\CustomerNotificationService::class)->sendOrderReadyNotification($order);
            }
        });
    }

    protected $fillable = [
        'order_number',
        'store_id',
        'campaign_id',
        'client_id',
        'status',
        'payment_method',
        'payment_status',
        'payment_url',
        'payment_id',
        'payment_payload',
        'payment_proof',
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
        'payment_payload' => 'array',
    ];

    protected $appends = ['payment_proof_url'];

    public function getPaymentProofUrlAttribute(): ?string
    {
        if (!$this->payment_proof) return null;
        if (str_starts_with($this->payment_proof, 'http://') || str_starts_with($this->payment_proof, 'https://')) {
            return $this->payment_proof;
        }
        return asset('storage/' . $this->payment_proof);
    }

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
