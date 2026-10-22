<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\Store;
use App\Events\OrderStatusUpdated;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $storeId = $request->query('store_id');
        $status = $request->query('status');
        $search = $request->query('search');

        $stores = Store::where('status', 'active')->get();
        $selectedStoreId = $storeId ?: null;

        $query = Order::with(['store', 'client', 'items', 'campaign'])
            ->orderBy('id', 'desc');

        if ($selectedStoreId) {
            $query->where('store_id', $selectedStoreId);
        }

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

        return view('admin.orders.index', compact('orders', 'stores', 'selectedStoreId', 'status', 'search'));
    }

    public function show(Order $order)
    {
        $order->load(['store', 'client', 'items.menu', 'statusLogs', 'campaign']);
        return view('admin.orders.show', compact('order'));
    }

    public function printBill(Order $order)
    {
        $order->load(['store', 'client', 'items.menu', 'campaign']);
        $store = $order->store;
        return view('print.order_bill', compact('order', 'store'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:Order Konfirmed,Order On Proses,Order On Packaging,Order On Delivery,Order Ready,Order Reject',
            'note' => 'nullable|string',
            'rejection_reason' => 'nullable|string',
        ]);

        $order->status = $validated['status'];
        if ($validated['status'] === Order::STATUS_REJECT) {
            $order->rejection_reason = $validated['rejection_reason'] ?? 'Pesanan dibatalkan/ditolak oleh admin';
        }
        $order->save();

        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => $validated['status'],
            'note' => $validated['note'] ?? 'Status diubah oleh Admin ke ' . $validated['status'],
        ]);

        $order->load(['store', 'client', 'items.menu', 'statusLogs']);

        try {
            broadcast(new OrderStatusUpdated($order))->toOthers();
        } catch (\Exception $e) {
            // Ignore if broadcast worker offline
        }

        return redirect()->back()->with('success', 'Status pesanan berhasil diubah menjadi: ' . $order->status);
    }
}
