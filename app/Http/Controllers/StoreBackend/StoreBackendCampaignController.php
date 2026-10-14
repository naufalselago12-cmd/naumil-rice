<?php

namespace App\Http\Controllers\StoreBackend;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignMenu;
use App\Models\Store;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StoreBackendCampaignController extends Controller
{
    protected function getStore(Request $request): Store
    {
        return $request->attributes->get('store');
    }

    public function index(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);

        $campaigns = Campaign::with('menus')
            ->where('store_id', $store->id)
            ->withCount('orders')
            ->orderBy('id', 'desc')
            ->get();

        return view('store_backend.campaigns.index', compact('store', 'campaigns'));
    }

    public function create(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);
        $menus = Menu::where('store_id', $store->id)->where('is_active', true)->get();

        return view('store_backend.campaigns.create', compact('store', 'menus'));
    }

    public function store(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);

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
            'selected_menus' => 'required|array|min:1',
            'selected_menus.*' => 'exists:menus,id',
            'menu_discounts' => 'nullable|array',
            'menu_custom_prices' => 'nullable|array',
        ]);

        $bannerImage = $validated['banner_url'] ?? 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1200&q=80';
        if ($request->hasFile('banner_file')) {
            $path = $request->file('banner_file')->store('campaigns', 'public');
            $bannerImage = asset('storage/' . $path);
        }

        $slug = Str::slug($validated['title']) . '-' . Str::lower(Str::random(6));
        $qrToken = 'CAMP-' . strtoupper(Str::random(12));

        DB::transaction(function () use ($validated, $bannerImage, $slug, $qrToken, $store) {
            $campaign = Campaign::create([
                'store_id' => $store->id,
                'title' => $validated['title'],
                'slug' => $slug,
                'banner_image' => $bannerImage,
                'description' => $validated['description'] ?? null,
                'discount_type' => $validated['discount_type'],
                'discount_value' => $validated['discount_value'] ?? 0,
                'min_purchase_amount' => $validated['min_purchase_amount'] ?? 0,
                'close_order_at' => Carbon::parse($validated['close_order_at']),
                'ready_at' => Carbon::parse($validated['ready_at']),
                'qr_token' => $qrToken,
                'is_active' => true,
            ]);

            foreach ($validated['selected_menus'] as $menuId) {
                CampaignMenu::create([
                    'campaign_id' => $campaign->id,
                    'menu_id' => $menuId,
                    'custom_price' => $validated['menu_custom_prices'][$menuId] ?? null,
                    'discount_price' => $validated['menu_discounts'][$menuId] ?? 0,
                ]);
            }
        });

        return redirect()->route('store.backend.campaigns.index', ['store_slug' => $store->slug])
            ->with('success', 'Batch Pre-Order baru dan kode QR berhasil dibuat.');
    }

    public function show(Request $request, string $store_slug, Campaign $campaign)
    {
        $store = $this->getStore($request);

        if ($campaign->store_id !== $store->id) {
            abort(403, 'Campaign ini bukan milik cabang ini.');
        }

        $campaign->load(['menus', 'orders.client']);
        return view('store_backend.campaigns.show', compact('store', 'campaign'));
    }

    public function edit(Request $request, string $store_slug, Campaign $campaign)
    {
        $store = $this->getStore($request);

        if ($campaign->store_id !== $store->id) {
            abort(403, 'Campaign ini bukan milik cabang ini.');
        }

        $menus = Menu::where('store_id', $store->id)->where('is_active', true)->get();
        $campaign->load('menus');

        return view('store_backend.campaigns.edit', compact('store', 'campaign', 'menus'));
    }

    public function update(Request $request, string $store_slug, Campaign $campaign)
    {
        $store = $this->getStore($request);

        if ($campaign->store_id !== $store->id) {
            abort(403, 'Campaign ini bukan milik cabang ini.');
        }

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
            'is_active' => 'nullable',
            'selected_menus' => 'required|array|min:1',
            'selected_menus.*' => 'exists:menus,id',
            'menu_discounts' => 'nullable|array',
            'menu_custom_prices' => 'nullable|array',
        ]);

        $bannerImage = $campaign->banner_image;
        if (!empty($validated['banner_url'])) {
            $bannerImage = $validated['banner_url'];
        }
        if ($request->hasFile('banner_file')) {
            $path = $request->file('banner_file')->store('campaigns', 'public');
            $bannerImage = asset('storage/' . $path);
        }

        DB::transaction(function () use ($campaign, $validated, $bannerImage, $request) {
            $campaign->update([
                'title' => $validated['title'],
                'banner_image' => $bannerImage,
                'description' => $validated['description'] ?? null,
                'discount_type' => $validated['discount_type'],
                'discount_value' => $validated['discount_value'] ?? 0,
                'min_purchase_amount' => $validated['min_purchase_amount'] ?? 0,
                'close_order_at' => Carbon::parse($validated['close_order_at']),
                'ready_at' => Carbon::parse($validated['ready_at']),
                'is_active' => $request->boolean('is_active'),
            ]);

            CampaignMenu::where('campaign_id', $campaign->id)->delete();
            foreach ($validated['selected_menus'] as $menuId) {
                CampaignMenu::create([
                    'campaign_id' => $campaign->id,
                    'menu_id' => $menuId,
                    'custom_price' => $validated['menu_custom_prices'][$menuId] ?? null,
                    'discount_price' => $validated['menu_discounts'][$menuId] ?? 0,
                ]);
            }
        });

        return redirect()->route('store.backend.campaigns.index', ['store_slug' => $store->slug])
            ->with('success', 'Data Campaign Pre-Order berhasil diperbarui.');
    }

    public function destroy(Request $request, string $store_slug, Campaign $campaign)
    {
        $store = $this->getStore($request);

        if ($campaign->store_id !== $store->id) {
            abort(403, 'Campaign ini bukan milik cabang ini.');
        }

        $campaign->delete();

        return redirect()->route('store.backend.campaigns.index', ['store_slug' => $store->slug])
            ->with('success', 'Campaign berhasil dihapus.');
    }
}
