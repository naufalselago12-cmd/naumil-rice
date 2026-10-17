<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Events\OrderStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentReturnController extends Controller
{
    /**
     * Handle return redirect from Mayar payment gateway
     */
    public function handle(Request $request)
    {
        Log::info('=== [MAYAR RETURN REDIRECT RECEIVED] ===', [
            'url'   => $request->fullUrl(),
            'query' => $request->query(),
        ]);

        $orderNumber = $request->query('order') 
            ?? $request->query('order_number') 
            ?? $request->query('orderId');

        $order = null;
        if ($orderNumber) {
            $order = Order::with(['store', 'client'])->where('order_number', $orderNumber)->first();
        }

        // If status/payment indicates success from query parameters
        $statusParam = strtoupper($request->query('status') ?? $request->query('payment_status') ?? '');
        $isSuccess = in_array($statusParam, ['PAID', 'SUCCESS', 'SETTLED', 'COMPLETED', 'LUNAS'])
            || $request->query('statusCode') == '200'
            || $request->query('success') === 'true';

        // If not already success from query params, actively verify with Mayar API
        if ($order && !$isSuccess && $order->payment_status !== 'paid' && $order->store) {
            try {
                $mayarService = new \App\Services\MayarPaymentService();
                $check = $mayarService->checkPaymentStatus($order->store, $order);
                if ($check['success'] && $check['is_paid']) {
                    $isSuccess = true;
                    Log::info("Order #{$order->order_number} verified as PAID via Mayar API on return redirect.");
                }
            } catch (\Throwable $e) {
                Log::warning("Mayar API verification failed on return redirect: " . $e->getMessage());
            }
        }

        if ($order && $isSuccess) {
            if ($order->payment_status !== 'paid') {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => in_array($order->status, [Order::STATUS_CONFIRMED, 'pending']) ? Order::STATUS_PROCESSING : $order->status,
                ]);

                OrderStatusLog::create([
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'note' => 'Pembayaran lunas via Mayar.id (redirect return).',
                ]);

                try {
                    broadcast(new OrderStatusUpdated($order))->toOthers();
                } catch (\Throwable $e) {}

                Log::info("Order #{$order->order_number} marked as PAID via Mayar redirect return.");
            }
        }

        return view('payment.return', [
            'order' => $order,
            'orderNumber' => $orderNumber ?? ($order->order_number ?? ''),
        ]);
    }
}
