<?php

namespace App\Http\Controllers\StoreBackend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use App\Services\CashierPosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Mode Kasir (POS & KDS ringkas) di Web per toko (/{store_slug}/backend/cashier).
 * Dapat diakses oleh akun Kasir cabang maupun Admin Toko cabang (via store.cashier).
 */
class StoreBackendCashierPosController extends Controller
{
    public function __construct(protected CashierPosService $pos)
    {
    }

    protected function getStore(Request $request): Store
    {
        return $request->attributes->get('store');
    }

    public function index(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);
        $user = Auth::guard('web')->user();
        $catalog = $this->pos->getActiveCatalog($store);
        $kds = $this->pos->getKdsQueue($store);
        $recent = $this->pos->getRecentCashierOrders($store);

        return view('store_backend.cashier.pos', [
            'store' => $store,
            'user' => $user,
            'catalog' => $catalog,
            'initialKds' => $kds,
            'initialRecent' => $recent,
        ]);
    }

    public function menus(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);
        return response()->json([
            'success' => true,
            'data' => $this->pos->getActiveCatalog($store),
        ]);
    }

    public function createOrder(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);
        $user = Auth::guard('web')->user();

        $validated = $request->validate([
            'payment_mode' => 'required|in:cash,qris',
            'mark_paid' => 'nullable|boolean',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.menu_id' => 'required|integer|exists:menus,id',
            'items.*.quantity' => 'required|integer|min:1|max:999',
            'items.*.selected_options' => 'nullable|array',
            'items.*.special_notes' => 'nullable|string|max:255',
        ]);

        $result = $this->pos->createOrder($store, $user, $validated);

        return response()->json($result, $result['success'] ? 201 : 422);
    }

    public function markPaid(Request $request, string $store_slug, Order $order)
    {
        $store = $this->getStore($request);
        $user = Auth::guard('web')->user();

        $updated = $this->pos->markCashPaid($store, $order, $user);

        return response()->json([
            'success' => true,
            'message' => "Pesanan #{$updated->order_number} berhasil ditandai LUNAS (Cash).",
            'order' => $this->pos->presentOrder($updated),
        ]);
    }

    public function syncMidtrans(Request $request, string $store_slug, Order $order)
    {
        $store = $this->getStore($request);
        $res = $this->pos->syncQrisMidtrans($store, $order);

        return response()->json($res);
    }

    public function recentOrders(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);
        return response()->json([
            'success' => true,
            'data' => $this->pos->getRecentCashierOrders($store),
        ]);
    }

    public function kdsQueue(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);
        return response()->json([
            'success' => true,
            'data' => $this->pos->getKdsQueue($store),
        ]);
    }

    public function updateKdsStatus(Request $request, string $store_slug, Order $order)
    {
        $store = $this->getStore($request);
        $user = Auth::guard('web')->user();

        $v = $request->validate([
            'status' => 'required|in:' . Order::STATUS_PACKAGING . ',' . Order::STATUS_READY,
        ]);

        $updated = $this->pos->updateKdsStatus($store, $order, $v['status'], $user);

        return response()->json([
            'success' => true,
            'message' => $v['status'] === Order::STATUS_READY
                ? "Pesanan #{$updated->order_number} selesai (Done)."
                : "Pesanan #{$updated->order_number} masuk tahap Packaging.",
            'order' => $this->pos->presentOrder($updated),
        ]);
    }
}

