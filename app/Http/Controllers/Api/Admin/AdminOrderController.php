<?php

namespace App\Http\Controllers\Api\Admin;

use App\Events\OrderStatusUpdated;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Services\MayarPaymentService;
use App\Services\MidtransPaymentService;
use Illuminate\Http\Request;

class AdminOrderController extends BaseAdminController
{
    private const KDS_STATUSES = [
        Order::STATUS_CONFIRMED,
        Order::STATUS_PROCESSING,
        Order::STATUS_PACKAGING,
        Order::STATUS_DELIVERY,
    ];

    public function index(Request $request)
    {
        $store = $this->currentStore($request);

        $query = Order::with(['client', 'items', 'campaign'])
            ->where('store_id', $store->id)->orderByDesc('id');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($payment = $request->query('payment_status')) {
            $query->where('payment_status', $payment);
        }
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$search}%")
                        ->orWhere('whatsapp', 'like', "%{$search}%"));
            });
        }

        $page = $query->paginate(15);

        return $this->ok([
            'data' => $page->items(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function show(Request $request, Order $order)
    {
        $this->own($request, $order, 'Pesanan');

        return $this->ok(['data' => $order->load(['client', 'items.menu', 'statusLogs', 'campaign'])]);
    }

    /** Antrian dapur (KDS): pesanan yang masih berjalan, terlama lebih dulu. */
    public function kds(Request $request)
    {
        $orders = Order::with(['client', 'items'])
            ->where('store_id', $this->currentStore($request)->id)
            ->whereIn('status', self::KDS_STATUSES)
            ->orderBy('id')->get();

        return $this->ok(['data' => $orders]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $this->own($request, $order, 'Pesanan');
        $store = $this->currentStore($request);

        $v = $request->validate([
            'status' => 'required|in:' . implode(',', Order::ALL_STATUSES),
            'note' => 'nullable|string',
            'rejection_reason' => 'nullable|string',
            'cancellation_reason_id' => 'nullable|exists:cancellation_reasons,id',
            'cancellation_reason' => 'nullable|string',
            'cancellation_remark' => 'nullable|string',
        ]);

        $order->status = $v['status'];
        if ($v['status'] === Order::STATUS_REJECT) {
            $reason = $v['cancellation_reason'] ?? ($v['rejection_reason'] ?? 'Pesanan dibatalkan oleh dapur cabang.');
            $remark = trim((string)($v['cancellation_remark'] ?? ''));
            $fullReason = $reason . ($remark ? ' - ' . $remark : '');

            $order->rejection_reason = $fullReason;
            $order->cancelled_by = 'admin';
            $order->cancellation_reason_id = $v['cancellation_reason_id'] ?? null;
            $order->cancellation_reason = $reason;
            $order->cancellation_remark = $remark ?: null;
            $order->cancelled_at = now();

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
            'status' => $v['status'],
            'note' => $v['note'] ?? ($v['status'] === Order::STATUS_REJECT ? ($order->rejection_reason ?? 'Pesanan dibatalkan oleh admin') : 'Status diubah via Aplikasi Mobile ' . $store->name),
        ]);
        $this->broadcast($order);

        return $this->ok(['data' => $order->load(['client', 'items.menu', 'statusLogs', 'campaign'])],
            "Status pesanan {$order->order_number} menjadi {$v['status']}.");
    }

    public function cancel(Request $request, Order $order)
    {
        $this->own($request, $order, 'Pesanan');
        $store = $this->currentStore($request);

        $v = $request->validate([
            'cancellation_reason_id' => 'nullable|exists:cancellation_reasons,id',
            'reason' => 'required|string|max:255',
            'remark' => 'nullable|string|max:1000',
        ]);

        $reason = $v['reason'];
        $remark = trim((string)($v['remark'] ?? ''));

        if (!empty($v['cancellation_reason_id'])) {
            $reasonModel = \App\Models\CancellationReason::find($v['cancellation_reason_id']);
            if ($reasonModel && $reasonModel->requires_remark && empty($remark)) {
                return $this->fail('Mohon isi catatan detail jika memilih alasan Lainnya.', 422);
            }
        } elseif (mb_strtolower($reason) === 'lainnya' && empty($remark)) {
            return $this->fail('Mohon isi catatan detail jika memilih alasan Lainnya.', 422);
        }

        $fullReason = $reason . ($remark ? ' - ' . $remark : '');

        $order->update([
            'status' => Order::STATUS_REJECT,
            'rejection_reason' => $fullReason,
            'cancelled_by' => 'admin',
            'cancellation_reason_id' => $v['cancellation_reason_id'] ?? null,
            'cancellation_reason' => $reason,
            'cancellation_remark' => $remark ?: null,
            'cancelled_at' => now(),
        ]);

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

        $this->broadcast($order);
        app(\App\Services\StoreNotificationService::class)->notifyOrderCancelled($order, 'admin', $fullReason);

        return $this->ok(['data' => $order->load(['client', 'items.menu', 'statusLogs', 'campaign'])],
            "Pesanan {$order->order_number} berhasil dibatalkan oleh Admin.");
    }

    public function verifyPayment(Request $request, Order $order)
    {
        $this->own($request, $order, 'Pesanan');

        $order->payment_status = 'paid';
        if (in_array($order->status, [Order::STATUS_CONFIRMED, 'pending'])) {
            $order->status = Order::STATUS_PROCESSING;
        }
        $order->save();

        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => $order->status,
            'note' => 'Pembayaran pesanan berhasil diverifikasi oleh Admin Cabang (Status: PAID). Pesanan dilanjutkan ke dapur.',
        ]);
        $this->broadcast($order);

        return $this->ok(['data' => $order->load(['client', 'items.menu', 'statusLogs', 'campaign'])],
            "Pembayaran {$order->order_number} terverifikasi (PAID).");
    }

    public function syncMidtrans(Request $request, Order $order)
    {
        return $this->syncGateway($request, $order, 'Midtrans', fn ($store, $o) => (new MidtransPaymentService())->checkPaymentStatus($store, $o));
    }

    public function syncMayar(Request $request, Order $order)
    {
        return $this->syncGateway($request, $order, 'Mayar.id', fn ($store, $o) => (new MayarPaymentService())->checkPaymentStatus($store, $o));
    }

    protected function syncGateway(Request $request, Order $order, string $label, callable $check)
    {
        $this->own($request, $order, 'Pesanan');

        try {
            $res = $check($this->currentStore($request), $order);

            if (!empty($res['success']) && !empty($res['is_paid'])) {
                if ($order->payment_status !== 'paid') {
                    $order->update([
                        'payment_status' => 'paid',
                        'status' => in_array($order->status, [Order::STATUS_CONFIRMED, 'pending']) ? Order::STATUS_PROCESSING : $order->status,
                    ]);
                    OrderStatusLog::create([
                        'order_id' => $order->id,
                        'status' => $order->status,
                        'note' => "Pembayaran lunas terverifikasi via Sinkronisasi {$label} oleh admin cabang.",
                    ]);
                    $this->broadcast($order);
                }

                return $this->ok(['paid' => true, 'data' => $order->fresh(['client', 'items.menu', 'statusLogs', 'campaign'])],
                    "Pembayaran {$order->order_number} LUNAS dari {$label}.");
            }

            $info = $res['message'] ?? $res['transaction_status'] ?? $res['status'] ?? 'Belum Lunas';

            return $this->ok(['paid' => false, 'data' => $order->fresh(['client', 'items.menu', 'statusLogs', 'campaign'])],
                "Respon {$label}: {$info}. Pembayaran belum terkonfirmasi lunas.");
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => "Gagal menghubungi server {$label}: " . $e->getMessage()], 502);
        }
    }

    protected function broadcast(Order $order): void
    {
        try {
            broadcast(new OrderStatusUpdated($order->load(['store', 'client', 'items.menu', 'statusLogs', 'campaign'])));
        } catch (\Throwable $e) {
            // Reverb tidak berjalan: abaikan
        }
    }
}
