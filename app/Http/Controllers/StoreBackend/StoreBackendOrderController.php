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

        $order->load(['client', 'items.menu', 'statusLogs', 'campaign', 'cancellationReason']);
        $cancellationReasons = \App\Models\CancellationReason::active()->forAdmin()->get();

        return view('store_backend.orders.show', compact('store', 'order', 'cancellationReasons'));
    }

    public function printBill(Request $request, string $store_slug, Order $order)
    {
        $store = $this->getStore($request);

        if ($order->store_id !== $store->id) {
            abort(403, 'Pesanan ini bukan milik cabang ini.');
        }

        $order->load(['client', 'items.menu', 'campaign', 'store']);
        return view('print.order_bill', compact('store', 'order'));
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
            'cancellation_reason_id' => 'nullable|exists:cancellation_reasons,id',
            'cancellation_reason' => 'nullable|string',
            'cancellation_remark' => 'nullable|string',
        ]);

        $order->status = $validated['status'];
        if ($validated['status'] === Order::STATUS_REJECT) {
            $reason = $validated['cancellation_reason'] ?? ($validated['rejection_reason'] ?? 'Pesanan dibatalkan oleh dapur cabang.');
            $remark = trim((string)($validated['cancellation_remark'] ?? ''));
            $fullReason = $reason . ($remark ? ' - ' . $remark : '');

            $order->rejection_reason = $fullReason;
            $order->cancelled_by = 'admin';
            $order->cancellation_reason_id = $validated['cancellation_reason_id'] ?? null;
            $order->cancellation_reason = $reason;
            $order->cancellation_remark = $remark ?: null;
            $order->cancelled_at = now();

            // Restore menu stock
            foreach ($order->items as $item) {
                if ($item->menu) {
                    $item->menu->increment('current_stock', $item->quantity);
                }
            }

            app(\App\Services\StoreNotificationService::class)->notifyOrderCancelled($order, 'admin', $fullReason);
        }
        $order->save();

        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => $validated['status'],
            'note' => $validated['note'] ?? ($validated['status'] === Order::STATUS_REJECT ? ($order->rejection_reason ?? 'Pesanan dibatalkan oleh admin') : 'Status diubah via Dashboard Dapur Cabang ' . $store->name),
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

    public function cancelOrder(Request $request, string $store_slug, Order $order)
    {
        $store = $this->getStore($request);

        if ($order->store_id !== $store->id) {
            abort(403, 'Pesanan ini bukan milik cabang ini.');
        }

        $validated = $request->validate([
            'cancellation_reason_id' => 'nullable|exists:cancellation_reasons,id',
            'reason' => 'required|string|max:255',
            'remark' => 'nullable|string|max:1000',
        ]);

        $reason = $validated['reason'];
        $remark = trim((string)($validated['remark'] ?? ''));

        if (!empty($validated['cancellation_reason_id'])) {
            $reasonModel = \App\Models\CancellationReason::find($validated['cancellation_reason_id']);
            if ($reasonModel && $reasonModel->requires_remark && empty($remark)) {
                return back()->with('error', 'Mohon isi catatan detail jika memilih alasan Lainnya.');
            }
        } elseif (mb_strtolower($reason) === 'lainnya' && empty($remark)) {
            return back()->with('error', 'Mohon isi catatan detail jika memilih alasan Lainnya.');
        }

        $fullReason = $reason . ($remark ? ' - ' . $remark : '');

        \Illuminate\Support\Facades\DB::transaction(function () use ($order, $store, $validated, $reason, $remark, $fullReason) {
            $order->update([
                'status' => Order::STATUS_REJECT,
                'rejection_reason' => $fullReason,
                'cancelled_by' => 'admin',
                'cancellation_reason_id' => $validated['cancellation_reason_id'] ?? null,
                'cancellation_reason' => $reason,
                'cancellation_remark' => $remark ?: null,
                'cancelled_at' => now(),
            ]);

            // Restore menu stock
            foreach ($order->items as $item) {
                if ($item->menu) {
                    $item->menu->increment('current_stock', $item->quantity);
                }
            }

            OrderStatusLog::create([
                'order_id' => $order->id,
                'status' => Order::STATUS_REJECT,
                'note' => 'Pesanan dibatalkan oleh Admin Dapur Cabang ' . $store->name . '. Alasan: ' . $fullReason,
            ]);

            if (class_exists(OrderStatusUpdated::class)) {
                try {
                    broadcast(new OrderStatusUpdated($order));
                } catch (\Exception $e) {}
            }

            app(\App\Services\StoreNotificationService::class)->notifyOrderCancelled($order, 'admin', $fullReason);
        });

        return back()->with('success', "Pesanan {$order->order_number} berhasil dibatalkan oleh Admin.");
    }

    public function syncMidtransPayment(Request $request, string $store_slug, Order $order)
    {
        $store = $this->getStore($request);

        if ($order->store_id !== $store->id) {
            abort(403, 'Pesanan ini bukan milik cabang ini.');
        }

        try {
            $midtransService = new \App\Services\MidtransPaymentService();
            $check = $midtransService->checkPaymentStatus($store, $order);

            if (!empty($check['success']) && !empty($check['is_paid'])) {
                if ($order->payment_status !== 'paid') {
                    $order->update([
                        'payment_status' => 'paid',
                        'status' => in_array($order->status, [Order::STATUS_CONFIRMED, 'pending', 'Order Konfirmed']) ? Order::STATUS_PROCESSING : $order->status,
                    ]);

                    OrderStatusLog::create([
                        'order_id' => $order->id,
                        'status' => $order->status,
                        'note' => 'Pembayaran lunas terverifikasi via Sinkronisasi Midtrans oleh admin cabang.',
                    ]);

                    if (class_exists(OrderStatusUpdated::class)) {
                        try {
                            broadcast(new OrderStatusUpdated($order));
                        } catch (\Exception $e) {}
                    }
                }

                return back()->with('success', "Status pembayaran pesanan {$order->order_number} berhasil diverifikasi: LUNAS (Settlement) dari Midtrans!");
            }

            $info = $check['message'] ?? ($check['transaction_status'] ?? 'Belum Lunas');
            return back()->with('warning', "Respon dari server Midtrans: {$info}. Pembayaran belum terkonfirmasi lunas.");
        } catch (\Throwable $e) {
            return back()->with('error', "Gagal menghubungi server Midtrans: " . $e->getMessage());
        }
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

    public function verifyPayment(Request $request, string $store_slug, Order $order)
    {
        $store = $this->getStore($request);

        if ($order->store_id !== $store->id) {
            abort(403, 'Pesanan ini bukan milik cabang ini.');
        }

        $order->payment_status = 'paid';

        // Advance to processing if currently confirmed
        if (in_array($order->status, [Order::STATUS_CONFIRMED, 'pending', 'Order Konfirmed'])) {
            $order->status = Order::STATUS_PROCESSING;
        }

        $order->save();

        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => $order->status,
            'note' => 'Pembayaran pesanan berhasil diverifikasi oleh Admin Cabang (Status: PAID). Pesanan dilanjutkan ke dapur.',
        ]);

        $order->load(['store', 'client', 'items.menu', 'statusLogs', 'campaign']);

        if (class_exists(OrderStatusUpdated::class)) {
            try {
                broadcast(new OrderStatusUpdated($order));
            } catch (\Exception $e) {}
        }

        return back()->with('success', "Pembayaran pesanan {$order->order_number} berhasil diverifikasi (Status: PAID) dan dilanjutkan ke proses dapur!");
    }
}


