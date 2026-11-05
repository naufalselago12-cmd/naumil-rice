<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use App\Models\OrderStatusLog;
use App\Events\OrderStatusUpdated;
use App\Services\MidtransPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    /**
     * Handle incoming notification from Midtrans
     */
    public function handle(Request $request, ?string $store_slug = null)
    {
        $rawContent = (string) $request->getContent();
        $payload = $request->all();

        if (empty($payload) && !empty($rawContent)) {
            $decoded = json_decode($rawContent, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        // 1. Full Logging
        $logData = [
            'timestamp'  => now()->toDateTimeString(),
            'method'     => $request->method(),
            'full_url'   => $request->fullUrl(),
            'store_slug' => $store_slug,
            'client_ip'  => $request->ip(),
            'raw_body'   => $rawContent,
            'payload'    => $payload,
        ];

        Log::info("=== [MIDTRANS NOTIFICATION RECEIVED] ===", $logData);

        try {
            $logLine = "[" . now()->toIso8601String() . "] " . json_encode($logData) . PHP_EOL;
            @file_put_contents(storage_path('logs/midtrans_notification.log'), $logLine, FILE_APPEND);
        } catch (\Throwable $e) {}

        // 2. Handle GET probes (health checks)
        if ($request->isMethod('GET')) {
            return response()->json([
                'status' => 'success',
                'message' => 'Midtrans notification webhook endpoint is active and listening.',
                'store' => $store_slug ?? 'all',
                'server_time' => now()->toIso8601String(),
            ], 200);
        }

        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $incomingSignature = (string) ($payload['signature_key'] ?? '');
        $transactionStatus = strtolower((string) ($payload['transaction_status'] ?? ''));
        $fraudStatus = strtolower((string) ($payload['fraud_status'] ?? ''));
        $paymentType = (string) ($payload['payment_type'] ?? 'midtrans');

        if (empty($orderId)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No order_id in payload',
            ], 400);
        }

        // If this is a test simulation transaction from admin
        if (str_starts_with($orderId, 'TEST-')) {
            Log::info("[MIDTRANS NOTIFICATION] Test simulation order processed: {$orderId}, status: {$transactionStatus}");
            return response()->json([
                'status' => 'success',
                'message' => 'Test transaction notification acknowledged',
                'order_id' => $orderId,
                'transaction_status' => $transactionStatus,
            ], 200);
        }

        // 3. Resolve Order
        $order = Order::with(['store', 'client'])->where('order_number', $orderId)->first();
        if (!$order) {
            // Also attempt strip prefixes or find by payment_id
            $order = Order::with(['store', 'client'])->where('payment_id', $orderId)->first();
        }

        if (!$order) {
            Log::warning("[MIDTRANS NOTIFICATION] Order not found for order_id: {$orderId}");
            return response()->json([
                'status' => 'error',
                'message' => "Order {$orderId} not found",
            ], 404);
        }

        $store = $order->store;
        if (!$store && $store_slug) {
            $store = Store::where('slug', $store_slug)->orWhere('code', $store_slug)->first();
        }

        // 4. Verify Signature Key
        $service = new MidtransPaymentService();
        $isSignatureValid = false;

        if ($store) {
            // Candidate keys: active, sandbox, and production keys
            $serverKeys = array_values(array_filter([
                $store->active_midtrans_server_key,
                $store->midtrans_server_key,
                $store->midtrans_server_key_production,
            ]));

            foreach ($serverKeys as $key) {
                if ($service->verifySignature($payload, $key)) {
                    $isSignatureValid = true;
                    break;
                }
            }
        }

        if (!$isSignatureValid && !empty($incomingSignature)) {
            Log::warning("[MIDTRANS NOTIFICATION] Signature key mismatch for order {$orderId}!", [
                'received_signature' => $incomingSignature,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid signature key',
            ], 401);
        }

        // 5. Update Order Payment Status based on Midtrans Transaction Status
        $isSettled = ($transactionStatus === 'settlement');
        if ($transactionStatus === 'capture') {
            $isSettled = ($fraudStatus === 'accept');
        }

        if ($isSettled) {
            if ($order->payment_status !== 'paid') {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => in_array($order->status, [Order::STATUS_CONFIRMED, 'pending', 'Order Konfirmed']) ? Order::STATUS_PROCESSING : $order->status,
                    'payment_payload' => array_merge((array) $order->payment_payload, [
                        'last_notification' => $payload,
                        'settled_at' => now()->toIso8601String(),
                    ]),
                ]);

                OrderStatusLog::create([
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'note' => "Pembayaran terkonfirmasi lunas via Midtrans ({$paymentType} - {$transactionStatus}).",
                ]);

                try {
                    broadcast(new OrderStatusUpdated($order))->toOthers();
                } catch (\Throwable $e) {}

                Log::info("[MIDTRANS NOTIFICATION] Order #{$order->order_number} marked as PAID.");
            }
        } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'])) {
            if ($order->payment_status !== 'paid') {
                $order->update([
                    'payment_status' => ($transactionStatus === 'expire') ? 'expired' : 'failed',
                    'payment_payload' => array_merge((array) $order->payment_payload, [
                        'last_notification' => $payload,
                        'failed_at' => now()->toIso8601String(),
                    ]),
                ]);

                OrderStatusLog::create([
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'note' => "Pembayaran Midtrans tidak berhasil ({$transactionStatus}).",
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notification processed successfully',
            'order_number' => $order->order_number,
            'payment_status' => $order->payment_status,
        ], 200);
    }
}

