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
            ->where('close_order_at', '>', now())
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
        $campaignTotalOrdered = $campaign->total_ordered;
        $campaignRemainingQuota = $campaign->remaining_quota;
        $campaignIsSoldOut = $campaign->is_sold_out;

        // Early Bird Quota (First 5 orders get per-menu promo directly)
        $totalOrdersCount = $campaign->total_orders_count;
        $earlyBirdQuota = (int) ($campaign->early_bird_quota ?? 5);
        $remainingEarlyBirdSlots = max(0, $earlyBirdQuota - $totalOrdersCount);
        $isEarlyBirdAvailable = $remainingEarlyBirdSlots > 0;

        // Format menus to reflect campaign discounts and stock/quota limits
        $menus = $campaign->menus->map(function ($menu) use ($campaign, $campaignIsSoldOut, $isEarlyBirdAvailable) {
            $pivot = $menu->pivot;
            $basePrice = $pivot->custom_price ?? $menu->price;
            $discountPrice = $pivot->discount_price ?? 0;

            // If min_qty campaign:
            // 5 early bird buyers get direct per-menu discount price.
            // After 5 buyers, promo expires and displays normal price until order reaches min_order_qty.
            if ($campaign->discount_type === 'min_qty') {
                $finalPrice = $isEarlyBirdAvailable 
                    ? max(0, (float) $basePrice - (float) $discountPrice)
                    : (float) $basePrice;
            } else {
                $finalPrice = max(0, (float) $basePrice - (float) $discountPrice);
            }

            $menuMaxQuota = $pivot->max_quota;
            $orderedQty = $campaign->getMenuOrderedQuantity($menu->id);
            $remainingQuota = $campaign->getMenuRemainingQuota($menu->id, $menuMaxQuota);
            $isMenuSoldOut = ($remainingQuota !== null && $remainingQuota <= 0) || $campaignIsSoldOut;

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
                'current_stock' => $remainingQuota !== null ? $remainingQuota : $menu->current_stock,
                'image' => $menu->image,
                'max_quota' => $menuMaxQuota,
                'ordered_quantity' => $orderedQty,
                'remaining_quota' => $remainingQuota,
                'is_sold_out' => $isMenuSoldOut,
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
            'is_sold_out' => $campaignIsSoldOut,
            'message' => $isExpired 
                ? 'Periode Pre-Order untuk campaign ini telah ditutup / expired.' 
                : ($campaignIsSoldOut ? 'Maaf, seluruh kuota untuk batch Pre-Order ini telah habis (Sold Out).' : 'Campaign aktif.'),
            'data' => [
                'campaign' => [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'slug' => $campaign->slug,
                    'banner_image' => $campaign->banner_image,
                    'description' => $campaign->description,
                    'discount_type' => $campaign->discount_type,
                    'discount_value' => (float) $campaign->discount_value,
                    'min_order_qty' => (int) ($campaign->min_order_qty ?? 0),
                    'early_bird_quota' => $earlyBirdQuota,
                    'total_orders_count' => $totalOrdersCount,
                    'remaining_early_bird_slots' => $remainingEarlyBirdSlots,
                    'is_early_bird_available' => $isEarlyBirdAvailable,
                    'min_purchase_amount' => (float) $campaign->min_purchase_amount,
                    'close_order_at' => $campaign->close_order_at->toIso8601String(),
                    'ready_at' => $campaign->ready_at->toIso8601String(),
                    'max_quota' => $campaign->max_quota,
                    'total_ordered' => $campaignTotalOrdered,
                    'remaining_quota' => $campaignRemainingQuota,
                    'is_sold_out' => $campaignIsSoldOut,
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
                    'payment_type' => $campaign->store->payment_type ?? 'qris_static',
                    'has_mayar' => $campaign->store->hasMayarConfigured(),
                ],
                'menus' => $menus
            ]
        ]);
    }
}
