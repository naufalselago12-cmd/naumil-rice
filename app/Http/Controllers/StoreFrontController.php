<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Campaign;
use Inertia\Inertia;
use Illuminate\Http\Request;

class StoreFrontController extends Controller
{
    /**
     * Root URL (/) - Directory of all stores (Looping per toko)
     */
    public function directory()
    {
        $isSuperAdmin = \Illuminate\Support\Facades\Auth::guard('superadmin')->check()
            || (\Illuminate\Support\Facades\Auth::guard('web')->check() && \Illuminate\Support\Facades\Auth::guard('web')->user()->isSuperAdmin());
        $webUser = \Illuminate\Support\Facades\Auth::guard('web')->user();

        $stores = Store::where('status', 'active')
            ->withCount([
                'menus' => fn($q) => $q->where('is_active', true),
                'campaigns' => fn($q) => $q->where('is_active', true)->where('close_order_at', '>', now()),
            ])
            ->get()
            ->map(function ($store) use ($isSuperAdmin, $webUser) {
                return [
                    'id' => $store->id,
                    'name' => $store->name,
                    'code' => $store->code,
                    'slug' => $store->slug ?: \Illuminate\Support\Str::slug($store->name),
                    'address' => $store->address,
                    'phone' => $store->phone,
                    'logo' => $store->logo,
                    'qris_image' => $store->qris_image,
                    'status' => $store->status,
                    'menus_count' => $store->menus_count,
                    'campaigns_count' => $store->campaigns_count,
                    'frontend_url' => url('/' . ($store->slug ?: $store->code)),
                    'backend_url' => url('/' . ($store->slug ?: $store->code) . '/backend'),
                    'can_manage_backend' => $isSuperAdmin || ($webUser && $webUser->canManageStore($store->id)),
                ];
            });

        return Inertia::render('Home', [
            'stores' => $stores,
        ]);
    }

    /**
     * Store Front (/{nama_toko}) - All data bound to the selected store
     */
    public function show(string $store_slug)
    {
        $store = Store::where('slug', $store_slug)
            ->orWhere('code', $store_slug)
            ->first();

        if (!$store) {
            return redirect()->route('home')->with('error', "Cabang '{$store_slug}' tidak ditemukan.");
        }

        // Check if viewer has authority to access store backend (Super Admin or Store Admin for this store)
        $canAccessBackend = false;
        if (\Illuminate\Support\Facades\Auth::guard('superadmin')->check()) {
            $canAccessBackend = true;
        } elseif (\Illuminate\Support\Facades\Auth::guard('web')->check()) {
            $user = \Illuminate\Support\Facades\Auth::guard('web')->user();
            if ($user->canManageStore($store->id)) {
                $canAccessBackend = true;
            }
        }

        // 1. Active Pre-Order campaigns for this store (Open PO)
        $campaigns = Campaign::where('store_id', $store->id)
            ->where('is_active', true)
            ->where('close_order_at', '>', now())
            ->with(['menus' => function ($q) {
                $q->where('is_active', true)->with('options');
            }])
            ->get()
            ->map(function ($camp) {
                $totalOrdered = $camp->total_ordered;
                $remQuota = $camp->remaining_quota;
                $isSoldOut = $camp->is_sold_out;

                $campMenus = $camp->menus->map(function ($m) use ($camp) {
                    $pivot = $m->pivot;
                    $menuMax = $pivot->max_quota;
                    $menuOrdered = $camp->getMenuOrderedQuantity($m->id);
                    $menuRemaining = $camp->getMenuRemainingQuota($m->id, $menuMax);
                    $effectivePrice = ($camp->discount_type === 'min_qty')
                        ? (float) ($pivot->custom_price ?? $m->price)
                        : max(0, ($pivot->custom_price ?? $m->price) - ($pivot->discount_price ?? 0));

                    return [
                        'id' => $m->id,
                        'name' => $m->name,
                        'description' => $m->description,
                        'image' => $m->image,
                        'price' => (float) $m->price,
                        'custom_price' => $pivot->custom_price ? (float) $pivot->custom_price : null,
                        'discount_price' => (float) ($pivot->discount_price ?? 0),
                        'effective_price' => (float) $effectivePrice,
                        'max_quota' => $menuMax,
                        'ordered_quantity' => $menuOrdered,
                        'remaining_quota' => $menuRemaining,
                        'is_sold_out' => ($menuRemaining !== null && $menuRemaining <= 0),
                        'options' => $m->options,
                    ];
                });

                return [
                    'id' => $camp->id,
                    'title' => $camp->title,
                    'slug' => $camp->slug,
                    'banner_image' => $camp->banner_image,
                    'description' => $camp->description,
                    'discount_type' => $camp->discount_type,
                    'discount_value' => (float) $camp->discount_value,
                    'min_order_qty' => (int) ($camp->min_order_qty ?? 0),
                    'min_purchase_amount' => (float) $camp->min_purchase_amount,
                    'close_order_at' => $camp->close_order_at->toIso8601String(),
                    'ready_at' => $camp->ready_at->toIso8601String(),
                    'max_quota' => $camp->max_quota,
                    'total_ordered' => $totalOrdered,
                    'remaining_quota' => $remQuota,
                    'is_sold_out' => $isSoldOut,
                    'qr_token' => $camp->qr_token,
                    'is_active' => $camp->is_active,
                    'menus' => $campMenus,
                ];
            });

        // 2. Filter menus based on active campaign status
        // Jika ada campaign aktif, menu lain TIDAK AKAN MUNCUL; hanya menu campaign aktif yang tampil
        $hasActiveCampaign = $campaigns->isNotEmpty();

        if ($hasActiveCampaign) {
            $poMenuIds = $campaigns->flatMap(fn($camp) => collect($camp['menus'])->pluck('id'))->unique()->values()->toArray();

            // Kategori yang hanya memiliki menu dalam campaign aktif
            $categories = Category::where('store_id', $store->id)
                ->whereHas('menus', function ($q) use ($poMenuIds) {
                    $q->where('is_active', true)->whereIn('id', $poMenuIds);
                })
                ->with(['menus' => function ($q) use ($poMenuIds) {
                    $q->where('is_active', true)->whereIn('id', $poMenuIds)->with('options');
                }])
                ->orderBy('display_order', 'asc')
                ->get();

            // Menu yang tampil HANYA menu yang ada di dalam campaign aktif
            $menus = Menu::where('store_id', $store->id)
                ->where('is_active', true)
                ->whereIn('id', $poMenuIds)
                ->with(['category', 'options'])
                ->get()
                ->map(function ($menu) use ($campaigns) {
                    foreach ($campaigns as $camp) {
                        $found = collect($camp['menus'])->firstWhere('id', $menu->id);
                        if ($found) {
                            $menu->effective_price = $found['effective_price'];
                            $menu->original_price = $found['price'];
                            $menu->discount_price = $found['discount_price'];
                            $menu->price = $found['effective_price'];
                            $menu->max_quota = $found['max_quota'];
                            $menu->remaining_quota = $found['remaining_quota'];
                            $menu->is_sold_out = $found['is_sold_out'];
                            if ($found['remaining_quota'] !== null) {
                                $menu->current_stock = $found['remaining_quota'];
                            }
                            break;
                        }
                    }
                    return $menu;
                });
        } else {
            // Jika TIDAK ADA campaign aktif, tampilkan menu reguler standar toko
            $categories = Category::where('store_id', $store->id)
                ->with(['menus' => function ($q) {
                    $q->where('is_active', true)->with('options');
                }])
                ->orderBy('display_order', 'asc')
                ->get();

            $menus = Menu::where('store_id', $store->id)
                ->where('is_active', true)
                ->with(['category', 'options'])
                ->get();
        }

        // 5. Other stores for quick switching
        $otherStores = Store::where('status', 'active')
            ->where('id', '!=', $store->id)
            ->select('id', 'name', 'code', 'slug', 'address')
            ->get();

        // 6. All active stores for loyalty customer multi-selection
        $allStores = Store::where('status', 'active')
            ->select('id', 'name', 'code', 'slug', 'address')
            ->get();

        // 7. Active loyalty promos for this store
        $loyaltyPromos = \App\Models\LoyaltyPromo::where('store_id', $store->id)
            ->where('is_active', true)
            ->with('menus')
            ->get()
            ->filter(fn($p) => $p->isCurrentlyActive())
            ->values();

        return Inertia::render('StoreFront', [
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'code' => $store->code,
                'slug' => $store->slug ?: \Illuminate\Support\Str::slug($store->name),
                'address' => $store->address,
                'phone' => $store->phone,
                'logo' => $store->logo,
                'qris_image' => $store->qris_image,
                'qris_data' => $store->qris_data,
                'status' => $store->status,
                'payment_type' => $store->payment_type ?? 'qris_static',
                'has_mayar' => $store->hasMayarConfigured(),
                'backend_url' => url('/' . ($store->slug ?: $store->code) . '/backend'),
                'can_manage_backend' => $canAccessBackend,
            ],
            'categories' => $categories,
            'menus' => $menus,
            'campaigns' => $campaigns,
            'otherStores' => $otherStores,
            'allStores' => $allStores,
            'loyaltyPromos' => $loyaltyPromos,
        ]);
    }
}
