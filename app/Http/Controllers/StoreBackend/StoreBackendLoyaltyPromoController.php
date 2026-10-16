<?php

namespace App\Http\Controllers\StoreBackend;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPromo;
use App\Models\Menu;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreBackendLoyaltyPromoController extends Controller
{
    protected function getStore(Request $request): Store
    {
        return $request->attributes->get('store');
    }

    public function index(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);

        $promos = LoyaltyPromo::where('store_id', $store->id)
            ->with(['menus'])
            ->orderBy('id', 'desc')
            ->get();

        return view('store_backend.loyalty_promos.index', compact('store', 'promos'));
    }

    public function create(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);
        $menus = Menu::where('store_id', $store->id)->where('is_active', true)->orderBy('name')->get();

        return view('store_backend.loyalty_promos.create', compact('store', 'menus'));
    }

    public function store(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'promo_type' => 'required|in:all_menu_percent,per_menu,min_purchase_discount',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'allow_benefit_choice' => 'nullable|boolean',
            'extra_points_multiplier' => 'nullable|numeric|min:1',
            'extra_points_bonus' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'nullable',
            'menus' => 'nullable|array',
            'menus.*.id' => 'exists:menus,id',
            'menus.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'menus.*.discount_amount' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $store, $request) {
            $promo = LoyaltyPromo::create([
                'store_id' => $store->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'promo_type' => $validated['promo_type'],
                'discount_percent' => $validated['discount_percent'] ?? 0,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'min_purchase_amount' => $validated['min_purchase_amount'] ?? 0,
                'allow_benefit_choice' => $request->boolean('allow_benefit_choice', true),
                'extra_points_multiplier' => $validated['extra_points_multiplier'] ?? 2.00,
                'extra_points_bonus' => $validated['extra_points_bonus'] ?? 0,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]);

            if ($validated['promo_type'] === 'per_menu' && !empty($validated['menus'])) {
                $attachData = [];
                foreach ($validated['menus'] as $m) {
                    if (!empty($m['id'])) {
                        $attachData[$m['id']] = [
                            'discount_percent' => $m['discount_percent'] ?? 0,
                            'discount_amount' => $m['discount_amount'] ?? 0,
                        ];
                    }
                }
                $promo->menus()->sync($attachData);
            }
        });

        return redirect()->route('store.backend.loyaltyPromos.index', ['store_slug' => $store->slug])
            ->with('success', 'Promo Pelanggan Setia berhasil dibuat.');
    }

    public function edit(Request $request, string $store_slug, LoyaltyPromo $promo)
    {
        $store = $this->getStore($request);

        if ($promo->store_id !== $store->id) {
            abort(403, 'Promo ini bukan milik cabang ini.');
        }

        $menus = Menu::where('store_id', $store->id)->where('is_active', true)->orderBy('name')->get();
        $promo->load('menus');

        return view('store_backend.loyalty_promos.edit', compact('store', 'promo', 'menus'));
    }

    public function update(Request $request, string $store_slug, LoyaltyPromo $promo)
    {
        $store = $this->getStore($request);

        if ($promo->store_id !== $store->id) {
            abort(403, 'Promo ini bukan milik cabang ini.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'promo_type' => 'required|in:all_menu_percent,per_menu,min_purchase_discount',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'allow_benefit_choice' => 'nullable|boolean',
            'extra_points_multiplier' => 'nullable|numeric|min:1',
            'extra_points_bonus' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'nullable',
            'menus' => 'nullable|array',
            'menus.*.id' => 'exists:menus,id',
            'menus.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'menus.*.discount_amount' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($promo, $validated, $request) {
            $promo->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'promo_type' => $validated['promo_type'],
                'discount_percent' => $validated['discount_percent'] ?? 0,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'min_purchase_amount' => $validated['min_purchase_amount'] ?? 0,
                'allow_benefit_choice' => $request->boolean('allow_benefit_choice', true),
                'extra_points_multiplier' => $validated['extra_points_multiplier'] ?? 2.00,
                'extra_points_bonus' => $validated['extra_points_bonus'] ?? 0,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]);

            if ($validated['promo_type'] === 'per_menu') {
                $attachData = [];
                if (!empty($validated['menus'])) {
                    foreach ($validated['menus'] as $m) {
                        if (!empty($m['id'])) {
                            $attachData[$m['id']] = [
                                'discount_percent' => $m['discount_percent'] ?? 0,
                                'discount_amount' => $m['discount_amount'] ?? 0,
                            ];
                        }
                    }
                }
                $promo->menus()->sync($attachData);
            } else {
                $promo->menus()->detach();
            }
        });

        return redirect()->route('store.backend.loyaltyPromos.index', ['store_slug' => $store->slug])
            ->with('success', 'Promo Pelanggan Setia berhasil diperbarui.');
    }

    public function destroy(Request $request, string $store_slug, LoyaltyPromo $promo)
    {
        $store = $this->getStore($request);

        if ($promo->store_id !== $store->id) {
            abort(403, 'Promo ini bukan milik cabang ini.');
        }

        $promo->delete();

        return redirect()->route('store.backend.loyaltyPromos.index', ['store_slug' => $store->slug])
            ->with('success', 'Promo berhasil dihapus.');
    }
}
