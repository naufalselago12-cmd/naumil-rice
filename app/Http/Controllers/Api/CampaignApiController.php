<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CampaignApiController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::with(['store'])
            ->where('is_active', true)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $campaigns
        ]);
    }

    public function showByToken(string $token)
    {
        $campaign = Campaign::with([
            'store',
            'menus' => function ($q) {
                $q->with(['category', 'options'])->where('is_active', true);
            }
        ])
        ->where('qr_token', $token)
        ->orWhere('slug', $token)
        ->first();

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign Pre-Order tidak ditemukan atau link QR tidak valid.'
            ], 404);
        }

        $now = Carbon::now();
        $isExpired = !$campaign->is_active || $now->greaterThanOrEqualTo($campaign->close_order_at);

        // Format menus to reflect campaign discounts
        $menus = $campaign->menus->map(function ($menu) use ($campaign) {
            $pivot = $menu->pivot;
            $basePrice = $pivot->custom_price ?? $menu->price;
            $discountPrice = $pivot->discount_price ?? 0;

            // Also check campaign level discount if fixed/percentage
            $finalPrice = max(0, $basePrice - $discountPrice);

            return [
                'id' => $menu->id,
                'category_id' => $menu->category_id,
                'category_name' => $menu->category?->name ?? 'General',
                'name' => $menu->name,
                'description' => $menu->description,
                'original_price' => (float) $menu->price,
                'custom_price' => (float) $basePrice,
                'discount_price' => (float) $discountPrice,
                'final_price' => (float) $finalPrice,
                'initial_stock' => $menu->initial_stock,
                'current_stock' => $menu->current_stock,
                'image' => $menu->image,
                'max_quota' => $pivot->max_quota,
                'options' => [
                    'toppings' => $menu->options->where('type', 'topping')->values(),
                    'upsizes' => $menu->options->where('type', 'upsize')->values(),
                    'conditions' => $menu->options->where('type', 'condition')->values(),
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'is_expired' => $isExpired,
            'message' => $isExpired ? 'Periode Pre-Order untuk campaign ini telah ditutup / expired.' : 'Campaign aktif.',
            'data' => [
                'campaign' => [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'slug' => $campaign->slug,
                    'banner_image' => $campaign->banner_image,
                    'description' => $campaign->description,
                    'discount_type' => $campaign->discount_type,
                    'discount_value' => (float) $campaign->discount_value,
                    'min_purchase_amount' => (float) $campaign->min_purchase_amount,
                    'close_order_at' => $campaign->close_order_at->toIso8601String(),
                    'ready_at' => $campaign->ready_at->toIso8601String(),
                    'qr_token' => $campaign->qr_token,
                    'is_active' => $campaign->is_active,
                    'is_expired' => $isExpired,
                ],
                'store' => [
                    'id' => $campaign->store->id,
                    'name' => $campaign->store->name,
                    'code' => $campaign->store->code,
                    'address' => $campaign->store->address,
                    'phone' => $campaign->store->phone,
                    'logo' => $campaign->store->logo,
                    'qris_image' => $campaign->store->qris_image,
                    'qris_data' => $campaign->store->qris_data,
                ],
                'menus' => $menus
            ]
        ]);
    }
}
