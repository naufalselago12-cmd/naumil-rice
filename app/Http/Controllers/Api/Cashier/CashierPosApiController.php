<?php

namespace App\Http\Controllers\Api\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use App\Services\CashierPosService;
use Illuminate\Http\Request;

/**
 * API Mode Kasir (POS & KDS ringkas) untuk Aplikasi Mobile naumil_rice_apps.
 * Dapat diakses oleh akun Kasir maupun Admin Toko cabang yang aktif (via store.api.member).
 */
class CashierPosApiController extends Controller
{
    public function __construct(protected CashierPosService $pos)
    {
    }

    protected function store(Request $request): Store
    {
        return $request->attributes->get('store');
    }

    public function menus(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->pos->getActiveCatalog($this->store($request)),
        ]);
    }

    public function createOrder(Request $request)
    {
        $v = $request->validate([
            'payment_mode' => 'required|in:cash,qris',
            'mark_paid' => 'nullable|boolean',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.menu_id' => 'required|integer|exists:menus,id',
            'items.*.quantity' => 'required|integer|min:1|max:999',
            'items.*.selected_options' => 'nullable|array',
            'items.*.special_notes' => 'nullable|string|max:255',
        ]);

        $res = $this->pos->createOrder($this->store($request), $request->user(), $v);

        return response()->json($res, $res['success'] ? 201 : 422);
    }

    public function markPaid(Request $request, Order $order)
    {
        $updated = $this->pos->markCashPaid($this->store($request), $order, $request->user());

        return response()->json([
            'success' => true,
            'message' => "Pesanan #{$updated->order_number} berhasil ditandai LUNAS (Cash).",
            'order' => $this->pos->presentOrder($updated),
        ]);
    }

    public function syncMidtrans(Request $request, Order $order)
    {
        return response()->json(
            $this->pos->syncQrisMidtrans($this->store($request), $order)
        );
    }

    public function recentOrders(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->pos->getRecentCashierOrders($this->store($request)),
        ]);
    }

    public function kdsQueue(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->pos->getKdsQueue($this->store($request)),
        ]);
    }

    public function updateKdsStatus(Request $request, Order $order)
    {
        $v = $request->validate([
            'status' => 'required|in:' . Order::STATUS_PACKAGING . ',' . Order::STATUS_READY,
        ]);

        $updated = $this->pos->updateKdsStatus($this->store($request), $order, $v['status'], $request->user());

        return response()->json([
            'success' => true,
            'message' => $v['status'] === Order::STATUS_READY
                ? "Pesanan #{$updated->order_number} selesai (Done)."
                : "Pesanan #{$updated->order_number} masuk tahap Packaging.",
            'order' => $this->pos->presentOrder($updated),
        ]);
    }
}

