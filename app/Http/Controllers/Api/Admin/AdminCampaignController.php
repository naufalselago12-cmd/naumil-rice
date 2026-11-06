<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Campaign;
use App\Models\CampaignMenu;
use App\Models\Menu;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminCampaignController extends BaseAdminController
{
    public function index(Request $request)
    {
        $items = Campaign::with('menus')->where('store_id', $this->currentStore($request)->id)
            ->withCount('orders')->orderByDesc('id')->get();

        return $this->ok(['data' => $items]);
    }

    public function show(Request $request, Campaign $campaign)
    {
        $this->own($request, $campaign, 'Campaign');

        return $this->ok(['data' => $campaign->load(['menus', 'orders.client'])]);
    }

    public function store(Request $request)
    {
        $store = $this->currentStore($request);
        $v = $this->validated($request, false);

        $campaign = DB::transaction(function () use ($v, $store) {
            $campaign = Campaign::create($this->payload($v) + [
                'store_id' => $store->id,
                'title' => $v['title'],
                'slug' => Str::slug($v['title']) . '-' . Str::lower(Str::random(6)),
                'banner_image' => $v['banner_url'] ?? 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1200&q=80',
                'qr_token' => 'CAMP-' . strtoupper(Str::random(12)),
                'is_active' => true,
            ]);
            $this->syncMenus($campaign, $v, $store->id);

            return $campaign;
        });

        return $this->ok(['data' => $campaign->load('menus')], 'Batch Pre-Order baru dan kode QR berhasil dibuat.', 201);
    }

    public function update(Request $request, Campaign $campaign)
    {
        $this->own($request, $campaign, 'Campaign');
        $v = $this->validated($request, true);

        DB::transaction(function () use ($campaign, $v, $request) {
            $campaign->update($this->payload($v) + [
                'title' => $v['title'],
                'banner_image' => !empty($v['banner_url']) ? $v['banner_url'] : $campaign->banner_image,
                'is_active' => $request->boolean('is_active'),
            ]);
            $this->syncMenus($campaign, $v, $campaign->store_id);
        });

        return $this->ok(['data' => $campaign->fresh('menus')], 'Campaign Pre-Order berhasil diperbarui.');
    }

    public function destroy(Request $request, Campaign $campaign)
    {
        $this->own($request, $campaign, 'Campaign');
        $campaign->delete();

        return $this->ok([], 'Campaign berhasil dihapus.');
    }

    protected function validated(Request $request, bool $isUpdate): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'banner_url' => 'nullable|string',
            'discount_type' => 'required|in:none,percentage,fixed,min_qty',
            'discount_value' => 'nullable|numeric|min:0',
            'min_order_qty' => 'nullable|integer|min:0',
            'early_bird_quota' => 'nullable|integer|min:0',
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
    }

    protected function payload(array $v): array
    {
        return [
            'description' => $v['description'] ?? null,
            'discount_type' => $v['discount_type'],
            'discount_value' => $v['discount_value'] ?? 0,
            'min_order_qty' => !empty($v['min_order_qty']) ? (int) $v['min_order_qty'] : 0,
            'early_bird_quota' => isset($v['early_bird_quota']) && $v['early_bird_quota'] !== '' ? (int) $v['early_bird_quota'] : 5,
            'min_purchase_amount' => 0,
            'close_order_at' => Carbon::parse($v['close_order_at']),
            'ready_at' => Carbon::parse($v['ready_at']),
            'max_quota' => !empty($v['max_quota']) ? (int) $v['max_quota'] : null,
        ];
    }

    protected function syncMenus(Campaign $campaign, array $v, int $storeId): void
    {
        // Hanya menu milik cabang ini yang boleh dipakai
        $allowed = Menu::where('store_id', $storeId)->whereIn('id', $v['selected_menus'])->pluck('id')->all();

        CampaignMenu::where('campaign_id', $campaign->id)->delete();
        foreach ($allowed as $menuId) {
            CampaignMenu::create([
                'campaign_id' => $campaign->id,
                'menu_id' => $menuId,
                'custom_price' => $v['menu_custom_prices'][$menuId] ?? null,
                'discount_price' => $v['menu_discounts'][$menuId] ?? 0,
                'max_quota' => !empty($v['menu_max_quotas'][$menuId]) ? (int) $v['menu_max_quotas'][$menuId] : null,
            ]);
        }
    }
}
