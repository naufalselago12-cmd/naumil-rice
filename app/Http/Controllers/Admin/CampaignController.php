<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignMenu;
use App\Models\Store;
use App\Models\Menu;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $storeId = $request->query('store_id');
        $stores = Store::where('status', 'active')->get();
        $selectedStoreId = $storeId ?: ($stores->first()?->id ?? null);

        $campaigns = Campaign::with(['store', 'menus'])
            ->when($selectedStoreId, fn($q) => $q->where('store_id', $selectedStoreId))
            ->withCount('orders')
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.campaigns.index', compact('campaigns', 'stores', 'selectedStoreId'));
    }

    public function create(Request $request)
    {
        $storeId = $request->query('store_id');
        $stores = Store::where('status', 'active')->get();
        $selectedStoreId = $storeId ?: ($stores->first()?->id ?? null);

        $menus = Menu::where('store_id', $selectedStoreId)->where('is_active', true)->get();

        return view('admin.campaigns.create', compact('stores', 'menus', 'selectedStoreId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'banner_url' => 'nullable|string',
            'banner_file' => 'nullable|image|max:4096',
            'discount_type' => 'required|in:none,percentage,fixed,min_qty',
            'discount_value' => 'nullable|numeric|min:0',
            'min_order_qty' => 'nullable|integer|min:0',
            'early_bird_quota' => 'nullable|integer|min:0',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'close_order_at' => 'required|date',
            'ready_at' => 'required|date|after_or_equal:close_order_at',
            'max_quota' => 'nullable|integer|min:1',
            'selected_menus' => 'required|array|min:1',
            'selected_menus.*' => 'exists:menus,id',
            'menu_discounts' => 'nullable|array',
            'menu_custom_prices' => 'nullable|array',
            'menu_max_quotas' => 'nullable|array',
        ]);

        $bannerPath = $validated['banner_url'] ?? 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1200&q=80';
        if ($request->hasFile('banner_file')) {
            $path = $request->file('banner_file')->store('campaigns', 'public');
            $bannerPath = asset('storage/' . $path);
        }

        // Generate clean unique slug and QR token
        $slug = Str::slug($validated['title']) . '-' . Str::random(5);
        $qrToken = 'PO-' . strtoupper(Str::random(10));

        DB::transaction(function () use ($validated, $bannerPath, $slug, $qrToken, $request) {
            $campaign = Campaign::create([
                'store_id' => $validated['store_id'],
                'title' => $validated['title'],
                'slug' => $slug,
                'banner_image' => $bannerPath,
                'description' => $validated['description'] ?? null,
                'discount_type' => $validated['discount_type'],
                'discount_value' => $validated['discount_value'] ?? 0,
                'min_order_qty' => !empty($validated['min_order_qty']) ? (int)$validated['min_order_qty'] : 0,
                'early_bird_quota' => isset($validated['early_bird_quota']) && $validated['early_bird_quota'] !== '' ? (int)$validated['early_bird_quota'] : 5,
                'min_purchase_amount' => 0,
                'close_order_at' => $validated['close_order_at'],
                'ready_at' => $validated['ready_at'],
                'max_quota' => !empty($validated['max_quota']) ? (int)$validated['max_quota'] : null,
                'qr_token' => $qrToken,
                'is_active' => true,
            ]);

            foreach ($validated['selected_menus'] as $menuId) {
                $discountPrice = $request->input("menu_discounts.{$menuId}", 0);
                $customPrice = $request->input("menu_custom_prices.{$menuId}", null);
                $menuQuota = $request->input("menu_max_quotas.{$menuId}", null);

                $campaign->menus()->attach($menuId, [
                    'custom_price' => $customPrice ?: null,
                    'discount_price' => $discountPrice ?: 0,
                    'max_quota' => !empty($menuQuota) ? (int)$menuQuota : null,
                ]);
            }
        });

        return redirect()->route('admin.campaigns.index', ['store_id' => $validated['store_id']])
            ->with('success', 'Campaign Open Pre-Order dan QR Code berhasil di-generate.');
    }

    public function show(Campaign $campaign)
    {
        $campaign->load(['store', 'menus.options', 'orders.client']);
        
        // Frontend URL for QR Code (Single Domain Inertia)
        $frontendCampaignUrl = url("/campaign/" . $campaign->qr_token);
        $qrCodeApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=" . urlencode($frontendCampaignUrl);

        return view('admin.campaigns.show', compact('campaign', 'frontendCampaignUrl', 'qrCodeApiUrl'));
    }

    public function printBill(Request $request, Campaign $campaign)
    {
        $campaign->load(['store', 'menus']);
        $store = $campaign->store;
        $mode = $request->query('mode', 'rekap'); // 'rekap' or 'all_orders'

        $orders = $campaign->orders()
            ->with(['client', 'items.menu', 'store', 'campaign'])
            ->where('status', '!=', Order::STATUS_REJECT)
            ->orderBy('id', 'asc')
            ->get();

        $campaignMenus = $campaign->menus->keyBy('id');
        $earlyBirdQuota = (int) ($campaign->early_bird_quota ?? 5);

        // Production / menu summary calculation
        $menuSummary = [];
        $totalEarlyBirdDiscounts = 0;
        $totalOtherDiscounts = 0;

        foreach ($orders as $orderIndex => $order) {
            $isOrderEarlyBird = ($orderIndex < $earlyBirdQuota);
            $orderDiscountRemaining = (float) $order->discount_amount;

            $order->is_early_bird = $isOrderEarlyBird;
            $order->early_bird_rank = $isOrderEarlyBird ? ($orderIndex + 1) : null;

            // Per-item discount calculation for this order
            $itemDiscounts = [];
            $totalItemDiscountsInOrder = 0;

            foreach ($order->items as $item) {
                $cMenu = $campaignMenus->get($item->menu_id);
                $dPrice = ($cMenu && $cMenu->pivot) ? (float) ($cMenu->pivot->discount_price ?? 0) : 0;

                $itemDisc = 0;
                if ($isOrderEarlyBird && $dPrice > 0) {
                    $itemDisc = $dPrice * $item->quantity;
                }
                $itemDiscounts[$item->id] = $itemDisc;
                $totalItemDiscountsInOrder += $itemDisc;
            }

            // Fallback if discount came from percentage or general rule
            if ($totalItemDiscountsInOrder == 0 && $orderDiscountRemaining > 0 && $order->subtotal > 0) {
                foreach ($order->items as $item) {
                    $itemDiscounts[$item->id] = ($item->subtotal / $order->subtotal) * $orderDiscountRemaining;
                }
            }

            foreach ($order->items as $item) {
                $key = $item->menu_name;
                if (!isset($menuSummary[$key])) {
                    $menuSummary[$key] = [
                        'name' => $item->menu_name,
                        'quantity' => 0,
                        'subtotal' => 0, // gross
                        'discount' => 0, // promo / early bird
                        'net_subtotal' => 0, // net after promo
                        'options' => [],
                    ];
                }

                $itemDisc = $itemDiscounts[$item->id] ?? 0;
                $menuSummary[$key]['quantity'] += $item->quantity;
                $menuSummary[$key]['subtotal'] += $item->subtotal;
                $menuSummary[$key]['discount'] += $itemDisc;
                $menuSummary[$key]['net_subtotal'] += ($item->subtotal - $itemDisc);

                if (!empty($item->selected_options) && is_array($item->selected_options)) {
                    foreach ($item->selected_options as $opt) {
                        $optName = $opt['name'] ?? null;
                        if ($optName) {
                            if (!isset($menuSummary[$key]['options'][$optName])) {
                                $menuSummary[$key]['options'][$optName] = [
                                    'name' => $optName,
                                    'quantity' => 0,
                                    'price' => !empty($opt['price']) ? (float)$opt['price'] : 0,
                                ];
                            }
                            $menuSummary[$key]['options'][$optName]['quantity'] += $item->quantity;
                        }
                    }
                }
            }

            if ($isOrderEarlyBird) {
                $totalEarlyBirdDiscounts += (float) $order->discount_amount;
            } else {
                $totalOtherDiscounts += (float) $order->discount_amount;
            }
        }

        $totalOrdersCount = $orders->count();
        $earlyBirdOrdersCount = min($totalOrdersCount, $earlyBirdQuota);
        $regularOrdersCount = max(0, $totalOrdersCount - $earlyBirdOrdersCount);
        $totalPorsi = $orders->flatMap->items->sum('quantity');
        $grossTotal = $orders->sum('subtotal');
        $totalPointsDiscounts = ($orders->sum('points_used') ?? 0) * 1000;
        $totalDiscounts = $orders->sum('discount_amount');
        $netTotal = $orders->sum('total_amount');

        $paidOrders = $orders->where('payment_status', 'paid');
        $paidOrdersCount = $paidOrders->count();
        $paidTotal = $paidOrders->sum('total_amount');

        $unpaidOrders = $orders->where('payment_status', '!=', 'paid');
        $unpaidOrdersCount = $unpaidOrders->count();
        $unpaidTotal = $unpaidOrders->sum('total_amount');

        return view('print.campaign_bill', compact(
            'store',
            'campaign',
            'orders',
            'menuSummary',
            'totalOrdersCount',
            'earlyBirdOrdersCount',
            'regularOrdersCount',
            'earlyBirdQuota',
            'totalPorsi',
            'grossTotal',
            'totalEarlyBirdDiscounts',
            'totalOtherDiscounts',
            'totalPointsDiscounts',
            'totalDiscounts',
            'netTotal',
            'paidOrdersCount',
            'paidTotal',
            'unpaidOrdersCount',
            'unpaidTotal',
            'mode'
        ));
    }

    public function edit(Campaign $campaign)
    {
        $stores = Store::where('status', 'active')->get();
        $menus = Menu::where('store_id', $campaign->store_id)->where('is_active', true)->get();
        $campaign->load('menus');

        return view('admin.campaigns.edit', compact('campaign', 'stores', 'menus'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'banner_url' => 'nullable|string',
            'banner_file' => 'nullable|image|max:4096',
            'discount_type' => 'required|in:none,percentage,fixed,min_qty',
            'discount_value' => 'nullable|numeric|min:0',
            'min_order_qty' => 'nullable|integer|min:0',
            'early_bird_quota' => 'nullable|integer|min:0',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'close_order_at' => 'required|date',
            'ready_at' => 'required|date|after_or_equal:close_order_at',
            'max_quota' => 'nullable|integer|min:1',
            'is_active' => 'nullable',
            'selected_menus' => 'required|array|min:1',
            'selected_menus.*' => 'exists:menus,id',
            'menu_discounts' => 'nullable|array',
            'menu_custom_prices' => 'nullable|array',
            'menu_max_quotas' => 'nullable|array',
        ]);

        $bannerPath = $campaign->banner_image;
        if ($request->filled('banner_url')) {
            $bannerPath = $validated['banner_url'];
        }
        if ($request->hasFile('banner_file')) {
            $path = $request->file('banner_file')->store('campaigns', 'public');
            $bannerPath = asset('storage/' . $path);
        }

        DB::transaction(function () use ($campaign, $validated, $bannerPath, $request) {
            $campaign->update([
                'title' => $validated['title'],
                'banner_image' => $bannerPath,
                'description' => $validated['description'] ?? null,
                'discount_type' => $validated['discount_type'],
                'discount_value' => $validated['discount_value'] ?? 0,
                'min_order_qty' => !empty($validated['min_order_qty']) ? (int)$validated['min_order_qty'] : 0,
                'early_bird_quota' => isset($validated['early_bird_quota']) && $validated['early_bird_quota'] !== '' ? (int)$validated['early_bird_quota'] : 5,
                'min_purchase_amount' => 0,
                'close_order_at' => $validated['close_order_at'],
                'ready_at' => $validated['ready_at'],
                'max_quota' => !empty($validated['max_quota']) ? (int)$validated['max_quota'] : null,
                'is_active' => $request->boolean('is_active'),
            ]);

            // Sync campaign menus
            $syncData = [];
            foreach ($validated['selected_menus'] as $menuId) {
                $discountPrice = $request->input("menu_discounts.{$menuId}", 0);
                $customPrice = $request->input("menu_custom_prices.{$menuId}", null);
                $menuQuota = $request->input("menu_max_quotas.{$menuId}", null);

                $syncData[$menuId] = [
                    'custom_price' => $customPrice ?: null,
                    'discount_price' => $discountPrice ?: 0,
                    'max_quota' => !empty($menuQuota) ? (int)$menuQuota : null,
                ];
            }
            $campaign->menus()->sync($syncData);
        });

        return redirect()->route('admin.campaigns.show', $campaign->id)
            ->with('success', 'Campaign Open PO berhasil diperbarui.');
    }

    public function destroy(Campaign $campaign)
    {
        $storeId = $campaign->store_id;
        $campaign->delete();

        return redirect()->route('admin.campaigns.index', ['store_id' => $storeId])
            ->with('success', 'Campaign berhasil dihapus.');
    }
}
