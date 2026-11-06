<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Client;
use App\Models\ClientPointTransaction;
use App\Models\LoyaltyPromo;
use App\Models\Menu;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminLoyaltyController extends BaseAdminController
{
    /* ---------- Pelanggan Setia ---------- */

    public function customers(Request $request)
    {
        $store = $this->currentStore($request);
        $search = $request->query('search');

        $query = Client::where(function ($q) use ($store) {
            $q->whereHas('stores', fn ($s) => $s->where('stores.id', $store->id))
                ->orWhereHas('orders', fn ($o) => $o->where('store_id', $store->id));
        })->where('is_loyalty_member', true);

        if ($search) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('whatsapp', 'like', "%{$search}%")
                ->orWhere('occupation', 'like', "%{$search}%"));
        }

        $page = $query->with(['stores' => fn ($s) => $s->where('stores.id', $store->id)])
            ->withCount(['orders' => fn ($o) => $o->where('store_id', $store->id)])
            ->orderByDesc('id')->paginate(15);

        return $this->ok([
            'data' => $page->items(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function customer(Request $request, Client $customer)
    {
        $store = $this->currentStore($request);
        $this->assertCustomer($store->id, $customer);
        $customer->load(['stores' => fn ($q) => $q->where('stores.id', $store->id)]);

        $orders = Order::where('client_id', $customer->id)->where('store_id', $store->id)
            ->with('items')->orderByDesc('id')->limit(20)->get();

        $transactions = ClientPointTransaction::where('client_id', $customer->id)
            ->where(fn ($q) => $q->where('store_id', $store->id)->orWhereNull('store_id'))
            ->orderByDesc('id')->limit(30)->get();

        $pivot = $customer->stores->first();

        return $this->ok(['data' => [
            'customer' => $customer,
            'store_points' => $pivot ? (int) $pivot->pivot->points : 0,
            'orders' => $orders,
            'transactions' => $transactions,
        ]]);
    }

    public function adjustPoints(Request $request, Client $customer)
    {
        $store = $this->currentStore($request);
        $this->assertCustomer($store->id, $customer);
        $v = $request->validate([
            'points' => 'required|integer|not_in:0',
            'action' => 'required|in:add,deduct',
            'note' => 'required|string|max:255',
        ]);
        $points = abs($v['points']);

        if ($v['action'] === 'add') {
            $customer->addPoints($points, "Penyesuaian Manual Admin ({$store->name}): {$v['note']}", $store->id);
            $msg = "Berhasil menambahkan {$points} poin ke {$customer->name}.";
        } else {
            if ($customer->loyalty_points < $points) {
                return response()->json(['success' => false,
                    'message' => "Saldo poin ({$customer->loyalty_points} Pts) tidak mencukupi untuk dikurangi {$points} Pts."], 422);
            }
            $customer->deductPoints($points, "Pengurangan Manual Admin ({$store->name}): {$v['note']}", $store->id);
            $msg = "Berhasil memotong {$points} poin dari {$customer->name}.";
        }

        return $this->ok(['loyalty_points' => $customer->fresh()->loyalty_points], $msg);
    }

    /** Pelanggan harus terkait dengan cabang ini (afiliasi atau pernah memesan). */
    protected function assertCustomer(int $storeId, Client $customer): void
    {
        $linked = $customer->stores()->where('stores.id', $storeId)->exists()
            || $customer->orders()->where('store_id', $storeId)->exists();
        abort_unless($linked, 403, 'Pelanggan ini tidak terkait dengan cabang Anda.');
    }

    /* ---------- Promo Pelanggan Setia ---------- */

    public function promos(Request $request)
    {
        $items = LoyaltyPromo::where('store_id', $this->currentStore($request)->id)->with('menus')->orderByDesc('id')->get();

        return $this->ok(['data' => $items]);
    }

    public function storePromo(Request $request)
    {
        $store = $this->currentStore($request);
        $v = $this->validatedPromo($request);

        $promo = DB::transaction(function () use ($v, $store, $request) {
            $promo = LoyaltyPromo::create($this->promoPayload($v, $request) + ['store_id' => $store->id]);
            $this->syncPromoMenus($promo, $v, $store->id);

            return $promo;
        });

        return $this->ok(['data' => $promo->load('menus')], 'Promo Pelanggan Setia berhasil dibuat.', 201);
    }

    public function updatePromo(Request $request, LoyaltyPromo $promo)
    {
        $this->own($request, $promo, 'Promo');
        $v = $this->validatedPromo($request);

        DB::transaction(function () use ($promo, $v, $request) {
            $promo->update($this->promoPayload($v, $request));
            $this->syncPromoMenus($promo, $v, $promo->store_id);
        });

        return $this->ok(['data' => $promo->fresh('menus')], 'Promo Pelanggan Setia berhasil diperbarui.');
    }

    public function destroyPromo(Request $request, LoyaltyPromo $promo)
    {
        $this->own($request, $promo, 'Promo');
        $promo->delete();

        return $this->ok([], 'Promo berhasil dihapus.');
    }

    protected function validatedPromo(Request $request): array
    {
        return $request->validate([
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
    }

    protected function promoPayload(array $v, Request $request): array
    {
        return [
            'title' => $v['title'],
            'description' => $v['description'] ?? null,
            'promo_type' => $v['promo_type'],
            'discount_percent' => $v['discount_percent'] ?? 0,
            'discount_amount' => $v['discount_amount'] ?? 0,
            'min_purchase_amount' => $v['min_purchase_amount'] ?? 0,
            'allow_benefit_choice' => $request->boolean('allow_benefit_choice', true),
            'extra_points_multiplier' => $v['extra_points_multiplier'] ?? 2.00,
            'extra_points_bonus' => $v['extra_points_bonus'] ?? 0,
            'start_date' => $v['start_date'] ?? null,
            'end_date' => $v['end_date'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ];
    }

    protected function syncPromoMenus(LoyaltyPromo $promo, array $v, int $storeId): void
    {
        if ($v['promo_type'] !== 'per_menu') {
            $promo->menus()->detach();
            return;
        }

        $allowed = Menu::where('store_id', $storeId)->pluck('id')->all();
        $attach = [];
        foreach ($v['menus'] ?? [] as $m) {
            if (!empty($m['id']) && in_array((int) $m['id'], $allowed, true)) {
                $attach[$m['id']] = [
                    'discount_percent' => $m['discount_percent'] ?? 0,
                    'discount_amount' => $m['discount_amount'] ?? 0,
                ];
            }
        }
        $promo->menus()->sync($attach);
    }
}
