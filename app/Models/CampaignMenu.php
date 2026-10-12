<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignMenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'menu_id',
        'custom_price',
        'discount_price',
        'max_quota',
    ];

    protected $casts = [
        'custom_price' => 'float',
        'discount_price' => 'float',
        'max_quota' => 'integer',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}
