<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignMenu;
use App\Models\Store;
use App\Models\Menu;
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
            'discount_type' => 'required|in:none,percentage,fixed,min_purchase',
            'discount_value' => 'nullable|numeric|min:0',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'close_order_at' => 'required|date',
            'ready_at' => 'required|date|after_or_equal:close_order_at',
            'selected_menus' => 'required|array|min:1',
            'selected_menus.*' => 'exists:menus,id',
            'menu_discounts' => 'nullable|array',
            'menu_custom_prices' => 'nullable|array',
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
                'min_purchase_amount' => $validated['min_purchase_amount'] ?? 0,
                'close_order_at' => $validated['close_order_at'],
                'ready_at' => $validated['ready_at'],
                'qr_token' => $qrToken,
                'is_active' => true,
            ]);

            foreach ($validated['selected_menus'] as $menuId) {
                $discountPrice = $request->input("menu_discounts.{$menuId}", 0);
                $customPrice = $request->input("menu_custom_prices.{$menuId}", null);

                $campaign->menus()->attach($menuId, [
                    'custom_price' => $customPrice ?: null,
                    'discount_price' => $discountPrice ?: 0,
                    'max_quota' => 100,
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
            'discount_type' => 'required|in:none,percentage,fixed,min_purchase',
            'discount_value' => 'nullable|numeric|min:0',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'close_order_at' => 'required|date',
            'ready_at' => 'required|date|after_or_equal:close_order_at',
            'is_active' => 'boolean',
            'selected_menus' => 'required|array|min:1',
            'selected_menus.*' => 'exists:menus,id',
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
                'min_purchase_amount' => $validated['min_purchase_amount'] ?? 0,
                'close_order_at' => $validated['close_order_at'],
                'ready_at' => $validated['ready_at'],
                'is_active' => $request->has('is_active'),
            ]);

            // Sync campaign menus
            $syncData = [];
            foreach ($validated['selected_menus'] as $menuId) {
                $discountPrice = $request->input("menu_discounts.{$menuId}", 0);
                $customPrice = $request->input("menu_custom_prices.{$menuId}", null);
                $syncData[$menuId] = [
                    'custom_price' => $customPrice ?: null,
                    'discount_price' => $discountPrice ?: 0,
                    'max_quota' => 100,
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
