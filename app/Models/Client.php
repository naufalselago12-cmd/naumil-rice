<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'whatsapp',
        'email',
        'address',
        'password',
        'gender',
        'occupation',
        'is_loyalty_member',
        'loyalty_points',
        'is_active',
        'activation_token',
        'activated_at',
        'email_verified_at',
        'last_order_at',
    ];

    protected $hidden = [
        'password',
        'activation_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_loyalty_member' => 'boolean',
        'loyalty_points' => 'integer',
        'activated_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'last_order_at' => 'datetime',
    ];

    protected $appends = ['display_label', 'display_concat'];

    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = Hash::needsRehash($value) ? Hash::make($value) : $value;
        }
    }

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
            'email_verified_at' => now(),
            'activation_token' => null,
        ]);
    }

    public function isVerified(): bool
    {
        return (bool) ($this->is_active && $this->email_verified_at);
    }

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'client_stores')
            ->withPivot('points')
            ->withTimestamps();
    }

    public function pointTransactions()
    {
        return $this->hasMany(ClientPointTransaction::class)->orderBy('id', 'desc');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Add loyalty points to client and record transaction
     */
    public function addPoints(int $points, string $description, ?int $storeId = null, ?int $orderId = null): void
    {
        if ($points <= 0) return;

        DB::transaction(function () use ($points, $description, $storeId, $orderId) {
            $this->increment('loyalty_points', $points);

            if ($storeId) {
                $pivot = DB::table('client_stores')
                    ->where('client_id', $this->id)
                    ->where('store_id', $storeId)
                    ->first();
                if ($pivot) {
                    DB::table('client_stores')
                        ->where('client_id', $this->id)
                        ->where('store_id', $storeId)
                        ->increment('points', $points);
                } else {
                    $this->stores()->attach($storeId, ['points' => $points]);
                }
            }

            ClientPointTransaction::create([
                'client_id' => $this->id,
                'store_id' => $storeId,
                'order_id' => $orderId,
                'type' => 'earn',
                'points' => $points,
                'description' => $description,
            ]);
        });
    }

    /**
     * Deduct loyalty points from client and record transaction
     */
    public function deductPoints(int $points, string $description, ?int $storeId = null, ?int $orderId = null): bool
    {
        if ($points <= 0) return false;
        if ($this->loyalty_points < $points) return false;

        DB::transaction(function () use ($points, $description, $storeId, $orderId) {
            $this->decrement('loyalty_points', $points);

            if ($storeId) {
                $pivot = DB::table('client_stores')
                    ->where('client_id', $this->id)
                    ->where('store_id', $storeId)
                    ->first();
                if ($pivot && $pivot->points >= $points) {
                    DB::table('client_stores')
                        ->where('client_id', $this->id)
                        ->where('store_id', $storeId)
                        ->decrement('points', $points);
                }
            }

            ClientPointTransaction::create([
                'client_id' => $this->id,
                'store_id' => $storeId,
                'order_id' => $orderId,
                'type' => 'redeem',
                'points' => -$points,
                'description' => $description,
            ]);
        });

        return true;
    }
}
