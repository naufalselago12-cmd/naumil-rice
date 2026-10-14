<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use App\Models\OrderStatusLog;
use App\Events\OrderStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MayarWebhookController extends Controller
{
    /**
     * Handle incoming webhook notification from Mayar.id
     */
    public function handle(Request $request, ?string $store_slug = null)
    {
        $payload = $request->all();
        Log::info('Mayar Webhook Received', [
            'store_slug' => $store_slug,
            'payload' => $payload,
        ]);

        $store = null;
        if ($store_slug) {
            $store = Store::where('slug', $store_slug)->orWhere('code', $store_slug)->first();
        }

        // Verify webhook token if store has configured token
        if ($store && !empty($store->mayar_webhook_token)) {
            $incomingToken = $request->header('x-mayar-token') 
                ?? $request->header('x-callback-token') 
                ?? $request->query('token') 
                ?? $request->input('token');

            if ($incomingToken !== $store->mayar_webhook_token) {
                Log::warning('Mayar Webhook Invalid Token', [
                    'expected' => $store->mayar_webhook_token,
                    'received' => $incomingToken,
                ]);
                return response()->json(['success' => false, 'message' => 'Unauthorized token'], 401);
            }
        }

        // Extract payment data
        $event = $payload['event'] ?? $payload['type'] ?? 'payment.received';
        $data = $payload['data'] ?? $payload;

        $paymentId = $data['id'] ?? $data['paymentId'] ?? $data['transactionId'] ?? null;
        $status = strtoupper($data['status'] ?? $data['paymentStatus'] ?? '');

        // Find corresponding Order:
        $order = null;

        // 1. By orderId / orderNumber / order_id directly
        $orderNumber = $data['orderId'] ?? $data['order_id'] ?? $data['orderNumber'] ?? $data['order_number'] ?? null;
        if ($orderNumber) {
            $order = Order::where('order_number', $orderNumber)->first();
        }

        // 2. By payment_id
        if (!$order && $paymentId) {
            $order = Order::where('payment_id', $paymentId)->first();
        }

        // 3. By order_number regex in description or remark
        if (!$order) {
            $textToSearch = ($data['description'] ?? '') . ' ' . ($data['remark'] ?? '') . ' ' . ($data['extraData'] ?? '');
            if (preg_match('/NR-PO-[A-Z0-9\-]+/', $textToSearch, $matches)) {
                $order = Order::where('order_number', $matches[0])->first();
            }
        }

        if (!$order) {
            Log::warning('Mayar Webhook Order Not Found', ['payment_id' => $paymentId]);
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan untuk ID pembayaran ini.'
            ], 404);
        }

        // Check if event/status indicates successful payment
        $isSuccess = in_array($status, ['SUCCESS', 'PAID', 'SETTLED', 'COMPLETED'])
            || str_contains(strtolower($event), 'success')
            || str_contains(strtolower($event), 'paid')
            || str_contains(strtolower($event), 'received');

        if ($isSuccess) {
            $order->update([
                'payment_status' => 'paid',
                'status' => $order->status === Order::STATUS_CONFIRMED ? Order::STATUS_PROCESSING : $order->status,
                'payment_payload' => array_merge($order->payment_payload ?? [], ['webhook' => $payload]),
            ]);

            OrderStatusLog::create([
                'order_id' => $order->id,
                'status' => $order->status,
                'note' => 'Pembayaran Rp ' . number_format($order->total_amount, 0, ',', '.') . ' terverifikasi otomatis via Payment Gateway Mayar.id.',
            ]);

            try {
                broadcast(new OrderStatusUpdated($order))->toOthers();
            } catch (\Exception $e) {
                // Ignore if broadcasting daemon not active
            }

            Log::info("Order #{$order->order_number} marked as PAID via Mayar.id Webhook.");

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil diverifikasi.',
                'order_number' => $order->order_number,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Webhook diterima tapi status belum selesai.',
            'status' => $status,
        ]);
    }
}
