<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Events\OrderStatusUpdated;
use App\Services\MayarPaymentService;
use App\Services\MidtransPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentReturnController extends Controller
{
    /**
     * Handle return redirect from payment gateway (Midtrans or Mayar)
     */
    public function handle(Request $request)
    {
        Log::info('=== [PAYMENT RETURN REDIRECT RECEIVED] ===', [
            'url'   => $request->fullUrl(),
            'query' => $request->query(),
        ]);

        $orderNumber = $request->query('order') 
            ?? $request->query('order_number') 
            ?? $request->query('order_id')
            ?? $request->query('orderId');

        $order = null;
        if ($orderNumber) {
            $order = Order::with(['store', 'client'])->where('order_number', $orderNumber)->first();
        }

        // Check if query parameters indicate success
        $statusParam = strtoupper((string) ($request->query('transaction_status') ?? $request->query('status') ?? $request->query('payment_status') ?? ''));
        $statusCode = (string) $request->query('status_code', $request->query('statusCode', ''));

        $isSuccess = in_array($statusParam, ['PAID', 'SUCCESS', 'SETTLED', 'SETTLEMENT', 'COMPLETED', 'LUNAS', 'CAPTURE'])
            || $statusCode === '200'
            || $request->query('success') === 'true';

        // If not already determined as success, check active payment gateway status API
        if ($order && !$isSuccess && $order->payment_status !== 'paid' && $order->store) {
            $provider = $order->payment_gateway_provider ?? $order->store->getActiveGatewayProvider();

            if ($provider === 'midtrans' || $order->store->isMidtrans() || !empty($order->snap_token)) {
                try {
                    $midtransService = new MidtransPaymentService();
                    $check = $midtransService->checkPaymentStatus($order->store, $order);
                    if (!empty($check['success']) && !empty($check['is_paid'])) {
                        $isSuccess = true;
                        Log::info("Order #{$order->order_number} verified as PAID via Midtrans API on return redirect.");
                    }
                } catch (\Throwable $e) {
                    Log::warning("Midtrans API verification failed on return redirect: " . $e->getMessage());
                }
            } else {
                try {
                    $mayarService = new MayarPaymentService();
                    $check = $mayarService->checkPaymentStatus($order->store, $order);
                    if (!empty($check['success']) && !empty($check['is_paid'])) {
                        $isSuccess = true;
                        Log::info("Order #{$order->order_number} verified as PAID via Mayar API on return redirect.");
                    }
                } catch (\Throwable $e) {
                    Log::warning("Mayar API verification failed on return redirect: " . $e->getMessage());
                }
            }
        }

        if ($order && $isSuccess) {
            if ($order->payment_status !== 'paid') {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => in_array($order->status, [Order::STATUS_CONFIRMED, 'pending', 'Order Konfirmed']) ? Order::STATUS_PROCESSING : $order->status,
                ]);

                $providerName = ($order->payment_gateway_provider === 'midtrans' || $order->store->isMidtrans()) ? 'Midtrans' : 'Mayar.id';

                OrderStatusLog::create([
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'note' => "Pembayaran lunas via {$providerName} (redirect return).",
                ]);

                try {
                    broadcast(new OrderStatusUpdated($order))->toOthers();
                } catch (\Throwable $e) {}

                Log::info("Order #{$order->order_number} marked as PAID via {$providerName} redirect return.");
            }
        }

        return view('payment.return', [
            'order' => $order,
            'orderNumber' => $orderNumber ?? ($order->order_number ?? ''),
        ]);
    }
}
