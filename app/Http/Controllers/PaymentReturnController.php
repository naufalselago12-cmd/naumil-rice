<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Events\OrderStatusUpdated;
use Illuminate\Http\Request;

class PaymentReturnController extends Controller
{
    /**
     * Handle return redirect from Mayar payment gateway
     */
    public function handle(Request $request)
    {
        $orderNumber = $request->query('order') 
            ?? $request->query('order_number') 
            ?? $request->query('orderId');

        $order = null;
        if ($orderNumber) {
            $order = Order::with(['store', 'client'])->where('order_number', $orderNumber)->first();
        }

        // If status/payment indicates success or paid from query parameters, mark order paid
        $statusParam = strtoupper($request->query('status') ?? $request->query('payment_status') ?? '');
        if ($order && in_array($statusParam, ['PAID', 'SUCCESS', 'SETTLED', 'COMPLETED'])) {
            if ($order->payment_status !== 'paid') {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => $order->status === Order::STATUS_CONFIRMED ? Order::STATUS_PROCESSING : $order->status,
                ]);

                OrderStatusLog::create([
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'note' => 'Pembayaran lunas via Mayar.id (redirect callback).',
                ]);

                try {
                    broadcast(new OrderStatusUpdated($order))->toOthers();
                } catch (\Exception $e) {}
            }
        }

        return view('payment.return', [
            'order' => $order,
            'orderNumber' => $orderNumber ?? ($order->order_number ?? ''),
        ]);
    }
}
