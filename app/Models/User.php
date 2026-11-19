<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_SUPERADMIN = 'superadmin';
    public const ROLE_STORE_ADMIN = 'store_admin';
    public const ROLE_CASHIER = 'cashier';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'store_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function isStoreAdmin(): bool
    {
        return $this->role === self::ROLE_STORE_ADMIN;
    }

    public function isCashier(): bool
    {
        return $this->role === self::ROLE_CASHIER;
    }

    public function isActiveAccount(): bool
    {
        return $this->is_active !== false;
    }

    /**
     * Hak akses penuh pengelolaan backend toko (Super Admin / Admin Toko saja, bukan Kasir).
     */
    public function canManageStore($storeId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!$this->isStoreAdmin() || !$this->isActiveAccount()) {
            return false;
        }

        return $this->store_id && (int) $this->store_id === (int) $storeId;
    }

    /**
     * Hak akses modul Kasir POS & KDS Kasir (Kasir cabang, Admin Toko cabang, atau Super Admin).
     */
    public function canAccessStoreCashier($storeId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!$this->isActiveAccount()) {
            return false;
        }

        if (!$this->isStoreAdmin() && !$this->isCashier()) {
            return false;
        }

        return $this->store_id && (int) $this->store_id === (int) $storeId;
    }
}
