<?php

namespace App\Http\Controllers\StoreBackend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\Store;
use App\Events\OrderStatusUpdated;
use Illuminate\Http\Request;

class StoreBackendOrderController extends Controller
{
    protected function getStore(Request $request): Store
    {
        return $request->attributes->get('store');
    }

    public function index(Request $request, string $store_slug)
    {
        $store = $this->getStore($request);
        $status = $request->query('status');
        $search = $request->query('search');

        $query = Order::with(['client', 'items', 'campaign'])
            ->where('store_id', $store->id)
            ->orderBy('id', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('whatsapp', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('store_backend.orders.index', compact('store', 'orders', 'status', 'search'));
    }

    public function show(Request $request, string $store_slug, Order $order)
    {
        $store = $this->getStore($request);

        if ($order->store_id !== $store->id) {
            abort(403, 'Pesanan ini bukan milik cabang ini.');
        }

        $order->load(['client', 'items.menu', 'statusLogs', 'campaign']);
        return view('store_backend.orders.show', compact('store', 'order'));
    }

    public function updateStatus(Request $request, string $store_slug, Order $order)
    {
        $store = $this->getStore($request);

        if ($order->store_id !== $store->id) {
            abort(403, 'Pesanan ini bukan milik cabang ini.');
        }

        $validated = $request->validate([
            'status' => 'required|in:Order Konfirmed,Order On Proses,Order On Packaging,Order On Delivery,Order Ready,Order Reject',
            'note' => 'nullable|string',
            'rejection_reason' => 'nullable|string',
        ]);

        $order->status = $validated['status'];
        if ($validated['status'] === Order::STATUS_REJECT) {
            $order->rejection_reason = $validated['rejection_reason'] ?? 'Pesanan dibatalkan oleh dapur cabang.';
        }
        $order->save();

        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => $validated['status'],
            'note' => $validated['note'] ?? 'Status diubah via Dashboard Dapur Cabang ' . $store->name,
        ]);

        if (class_exists(OrderStatusUpdated::class)) {
            try {
                broadcast(new OrderStatusUpdated($order));
            } catch (\Exception $e) {
                // broadcast silent fail if pusher not running
            }
        }

        return back()->with('success', "Status pesanan {$order->order_number} berhasil diubah menjadi {$validated['status']}.");
    }

    public function syncMayarPayment(Request $request, string $store_slug, Order $order)
    {
        $store = $this->getStore($request);

        if ($order->store_id !== $store->id) {
            abort(403, 'Pesanan ini bukan milik cabang ini.');
        }

        try {
            $mayarService = new \App\Services\MayarPaymentService();
            $check = $mayarService->checkPaymentStatus($store, $order);

            if (!empty($check['success']) && !empty($check['is_paid'])) {
                if ($order->payment_status !== 'paid') {
                    $order->update([
                        'payment_status' => 'paid',
                        'status' => in_array($order->status, [Order::STATUS_CONFIRMED, 'pending', 'Order Konfirmed']) ? Order::STATUS_PROCESSING : $order->status,
                    ]);

                    OrderStatusLog::create([
                        'order_id' => $order->id,
                        'status' => $order->status,
                        'note' => 'Pembayaran lunas terverifikasi via Sinkronisasi Mayar.id oleh admin cabang.',
                    ]);

                    if (class_exists(OrderStatusUpdated::class)) {
                        try {
                            broadcast(new OrderStatusUpdated($order));
                        } catch (\Exception $e) {}
                    }
                }

                return back()->with('success', "Status pembayaran pesanan {$order->order_number} berhasil diverifikasi: LUNAS (PAID) dari Mayar!");
            }

            $info = $check['status'] ?? ($check['message'] ?? 'Belum Lunas');
            return back()->with('warning', "Respon dari server Mayar: {$info}. Pembayaran belum terkonfirmasi lunas.");
        } catch (\Throwable $e) {
            return back()->with('error', "Gagal menghubungi server Mayar: " . $e->getMessage());
        }
    }
}

