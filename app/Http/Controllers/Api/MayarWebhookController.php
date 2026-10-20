<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use App\Models\OrderStatusLog;
use App\Events\OrderStatusUpdated;
use App\Services\MayarPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MayarWebhookController extends Controller
{
    /**
     * Handle incoming webhook notification from Mayar.id
     */
    public function handle(Request $request, ?string $store_slug = null)
    {
        $rawContent = (string) $request->getContent();
        $payload = $request->all();

        // Fallback: If $request->all() was not auto-parsed from JSON content
        if (empty($payload) && !empty($rawContent)) {
            $decoded = json_decode($rawContent, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        // 1. FULL LOGGING OF EVERY WEBHOOK CALLBACK
        $logData = [
            'timestamp'   => now()->toDateTimeString(),
            'method'      => $request->method(),
            'full_url'    => $request->fullUrl(),
            'store_slug'  => $store_slug,
            'client_ip'   => $request->ip(),
            'headers'     => $request->headers->all(),
            'query'       => $request->query(),
            'raw_body'    => $rawContent,
            'payload'     => $payload,
        ];

        Log::info("=== [MAYAR WEBHOOK CALLBACK START] ===", $logData);

        // Also write a persistent copy to storage/logs/mayar_webhook.log
        try {
            $logLine = "[" . now()->toIso8601String() . "] " . json_encode($logData) . PHP_EOL;
            @file_put_contents(storage_path('logs/mayar_webhook.log'), $logLine, FILE_APPEND);
        } catch (\Throwable $e) {}

        // 2. Handle GET probes (e.g., browser checks or webhook health monitors)
        if ($request->isMethod('GET')) {
            Log::info("[MAYAR WEBHOOK] GET probe handled successfully.", ['store' => $store_slug]);
            return response()->json([
                'status' => 'success',
                'message' => 'Mayar webhook endpoint is active and listening.',
                'store' => $store_slug ?? 'all',
                'server_time' => now()->toIso8601String(),
            ], 200);
        }

        // 3. Resolve Store if store_slug provided
        $store = null;
        if ($store_slug) {
            $store = Store::where('slug', $store_slug)->orWhere('code', $store_slug)->first();
        }

        // 4. Verify webhook token if store configured one and token was sent in request
        $expectedTokens = $store ? array_values(array_filter([
            $store->mayar_webhook_token,
            $store->mayar_webhook_token_production,
        ])) : [];

        if ($store && !empty($expectedTokens)) {
            $incomingToken = $request->header('x-mayar-token') 
                ?? $request->header('x-callback-token') 
                ?? $request->query('token') 
                ?? $request->input('token');

            if (!empty($incomingToken) && !in_array($incomingToken, $expectedTokens, true)) {
                Log::warning('[MAYAR WEBHOOK] Token mismatch!', [
                    'expected' => $expectedTokens,
                    'received' => $incomingToken,
                ]);
                return response()->json(['success' => false, 'message' => 'Unauthorized token'], 401);
            }
        }

        // 5. Extract payment data & event type
        $event = strtolower((string)($payload['event'] ?? $payload['type'] ?? 'payment.received'));
        $data = $payload['data'] ?? $payload['payload'] ?? $payload;

        // If this is purely an explicit test / ping event with no real payment data
        $isExplicitTest = in_array($event, ['test', 'webhook.test', 'ping']) 
            || (empty($data) && empty($rawContent));
            
        if ($isExplicitTest) {
            Log::info('[MAYAR WEBHOOK] Explicit test / ping event acknowledged.');
            return response()->json([
                'success' => true,
                'message' => 'Mayar webhook test ping received successfully.',
            ], 200);
        }

        // Gather all candidate IDs from payload
        $candidateIds = array_unique(array_filter([
            $data['id'] ?? null,
            $data['transactionId'] ?? null,
            $data['transaction_id'] ?? null,
            $data['paymentId'] ?? null,
            $data['payment_id'] ?? null,
            $data['paymentRequestId'] ?? null,
            $data['payment_request_id'] ?? null,
            $data['paymentLinkId'] ?? null,
            $data['payment_link_id'] ?? null,
            $data['invoiceId'] ?? null,
            $data['invoice_id'] ?? null,
            $payload['id'] ?? null,
            $payload['transactionId'] ?? null,
            $payload['transaction_id'] ?? null,
        ]));

        $status = strtoupper((string)($data['status'] ?? $data['paymentStatus'] ?? $data['transactionStatus'] ?? $payload['status'] ?? ''));

        // 6. MULTI-STRATEGY ORDER RESOLUTION
        $order = null;
        $matchedStrategy = null;

        // Strategy A: Direct order_number from payload keys
        $directOrderNumber = $data['orderId'] ?? $data['order_id'] ?? $data['orderNumber'] ?? $data['order_number'] 
            ?? $payload['orderId'] ?? $payload['order_id'] ?? $payload['orderNumber'] ?? $payload['order_number'] ?? null;
        if (!empty($directOrderNumber)) {
            $order = Order::where('order_number', $directOrderNumber)->first();
            if ($order) $matchedStrategy = "Direct order_number: {$directOrderNumber}";
        }

        // Strategy B: Regex search across the ENTIRE payload JSON string
        // (Will match order numbers in 'description', 'redirectUrl', 'extraData', notes, etc.)
        if (!$order) {
            $searchHaystack = $rawContent . ' ' . json_encode($payload);
            if (preg_match('/NR-PO-[A-Za-z0-9\-_]+/', $searchHaystack, $matches)) {
                $foundNumber = $matches[0];
                $order = Order::where('order_number', $foundNumber)->first();
                if ($order) $matchedStrategy = "Regex from payload haystack: {$foundNumber}";
            }
        }

        // Strategy C: Match by candidate payment IDs in `payment_id` column
        if (!$order && !empty($candidateIds)) {
            $order = Order::whereIn('payment_id', $candidateIds)->first();
            if ($order) $matchedStrategy = "Candidate IDs matching payment_id column: " . json_encode($candidateIds);
        }

        // Strategy D: Search candidate IDs inside `payment_payload` JSON column (using CAST for JSON compatibility)
        if (!$order && !empty($candidateIds)) {
            foreach ($candidateIds as $cid) {
                if (strlen((string) $cid) >= 5) {
                    $order = Order::whereRaw("CAST(payment_payload AS CHAR) LIKE ?", ["%{$cid}%"])
                        ->orWhere('payment_payload', 'like', "%{$cid}%")
                        ->first();
                    if ($order) {
                        $matchedStrategy = "Candidate ID {$cid} found in payment_payload column";
                        break;
                    }
                }
            }
        }

        // Strategy E: Match by payment URL or invoice slug
        if (!$order) {
            $link = $data['link'] ?? $data['paymentUrl'] ?? $data['url'] ?? null;
            if (!empty($link)) {
                $order = Order::where('payment_url', 'like', "%{$link}%")->first();
                if ($order) $matchedStrategy = "Matched by exact payment_url: {$link}";
            }

            // Extract invoice slug (e.g. 'c4xzhpyaza' from '.../invoices/c4xzhpyaza')
            if (!$order) {
                $haystack = $rawContent . ' ' . json_encode($payload);
                if (preg_match('/invoices\/([a-zA-Z0-9_-]+)/', $haystack, $slugMatch)) {
                    $invoiceSlug = $slugMatch[1];
                    $order = Order::where('payment_url', 'like', "%{$invoiceSlug}%")->first();
                    if ($order) $matchedStrategy = "Matched by invoice slug: {$invoiceSlug}";
                }
            }
        }

        // Strategy F: Match by Customer Contact + Amount (Recent unpaid orders within 48 hours)
        $amount = (float)($data['amount'] ?? $data['totalAmount'] ?? $data['paidAmount'] ?? 0);
        $customerEmail = strtolower(trim((string)($data['customerEmail'] ?? $data['customer']['email'] ?? $data['email'] ?? '')));
        $customerMobile = preg_replace('/[^0-9]/', '', (string)($data['customerMobile'] ?? $data['customer']['mobile'] ?? $data['mobile'] ?? ''));

        if (!$order && $amount > 0 && (!empty($customerEmail) || !empty($customerMobile))) {
            $recentOrders = Order::with('client')
                ->where('payment_status', 'unpaid')
                ->where('total_amount', $amount)
                ->where('created_at', '>=', now()->subHours(48))
                ->latest()
                ->get();

            foreach ($recentOrders as $ro) {
                $c = $ro->client;
                if (!$c) continue;

                $clientEmail = strtolower(trim($c->email ?? ''));
                $clientPhone = preg_replace('/[^0-9]/', '', $c->whatsapp ?? '');

                $emailMatch = !empty($customerEmail) && !empty($clientEmail) && ($customerEmail === $clientEmail || str_contains($customerEmail, $clientPhone));
                $phoneMatch = !empty($customerMobile) && !empty($clientPhone) && (
                    str_ends_with($customerMobile, substr($clientPhone, -8)) ||
                    str_ends_with($clientPhone, substr($customerMobile, -8))
                );

                if ($emailMatch || $phoneMatch) {
                    $order = $ro;
                    $matchedStrategy = "Matched by Customer details & amount ({$amount})";
                    break;
                }
            }
        }

        // Strategy G: Fallback by Store + Exact Amount + Recent Unpaid Order (within 24 hours)
        if (!$order && $amount > 0) {
            $recentQuery = Order::where('payment_status', 'unpaid')
                ->where('total_amount', $amount)
                ->where('created_at', '>=', now()->subHours(24));
            if ($store) {
                $recentQuery->where('store_id', $store->id);
            }
            $order = $recentQuery->latest()->first();
            if ($order) $matchedStrategy = "Fallback matched recent unpaid order for amount {$amount}";
        }

        // Strategy H: Active API Verification against Candidate IDs
        if (!$order && !empty($candidateIds)) {
            $storesToTry = $store ? collect([$store]) : Store::whereNotNull('mayar_api_key')->orWhereNotNull('mayar_api_key_production')->get();
            $mayarService = new MayarPaymentService();

            foreach ($storesToTry as $st) {
                $recentUnpaid = Order::where('store_id', $st->id)
                    ->where('payment_status', 'unpaid')
                    ->where('created_at', '>=', now()->subHours(48))
                    ->latest()
                    ->get();

                foreach ($recentUnpaid as $ru) {
                    $statusCheck = $mayarService->checkPaymentStatus($st, $ru);
                    if ($statusCheck['success'] && $statusCheck['is_paid']) {
                        $order = $ru;
                        $matchedStrategy = "Active Mayar API verification confirmed order #{$ru->order_number}";
                        $status = 'PAID';
                        break 2;
                    }
                }
            }
        }

        // 7. If still no order found (e.g. Mayar test simulation from dashboard)
        if (!$order) {
            Log::info("[MAYAR WEBHOOK] No matching order found in database. Acknowledged as test/unmatched callback.", [
                'candidate_ids' => $candidateIds,
                'event'         => $event,
                'status'        => $status,
                'amount'        => $amount,
                'raw_body'      => $rawContent,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Webhook received successfully (test/unmatched ping).',
                'event' => $event,
                'status' => $status,
            ], 200);
        }

        Log::info("[MAYAR WEBHOOK] Order Found via {$matchedStrategy}!", [
            'order_number' => $order->order_number,
            'order_id' => $order->id,
            'current_payment_status' => $order->payment_status,
            'current_status' => $order->status,
            'event' => $event,
            'status' => $status,
        ]);

        // 8. Determine if payment is successful
        $isPaidEvent = in_array($status, ['SUCCESS', 'PAID', 'SETTLED', 'COMPLETED', 'VERIFIED', 'LUNAS', 'BERHASIL', 'DONE'])
            || !empty($data['paidAt'])
            || !empty($data['paid_at'])
            || ($data['isPaid'] ?? false) === true
            || ($data['paid'] ?? false) === true
            || str_contains($event, 'success')
            || str_contains($event, 'paid')
            || str_contains($event, 'received')
            || str_contains($event, 'settled');

        if ($isPaidEvent) {
            // If already paid, acknowledge idempotently
            if ($order->payment_status === 'paid') {
                return response()->json([
                    'success' => true,
                    'message' => 'Pesanan ini sudah berstatus PAID sebelumnya.',
                    'order_number' => $order->order_number,
                    'status' => 'paid',
                ], 200);
            }

            $oldPaymentStatus = $order->payment_status;
            $oldStatus = $order->status;

            // Update order status to paid & processing
            $newStatus = in_array($order->status, [Order::STATUS_CONFIRMED, 'pending']) 
                ? Order::STATUS_PROCESSING 
                : $order->status;

            $order->update([
                'payment_status' => 'paid',
                'status' => $newStatus,
                'payment_payload' => array_merge(
                    $order->payment_payload ?? [], 
                    [
                        'webhook_paid_at' => now()->toIso8601String(),
                        'webhook_payload' => $payload,
                        'matched_strategy' => $matchedStrategy,
                    ]
                ),
            ]);

            OrderStatusLog::create([
                'order_id' => $order->id,
                'status' => $order->status,
                'note' => 'Pembayaran Rp ' . number_format($order->total_amount, 0, ',', '.') . ' terverifikasi otomatis via Payment Gateway Mayar.id (' . ($status ?: $event) . ').',
            ]);

            try {
                broadcast(new OrderStatusUpdated($order))->toOthers();
            } catch (\Throwable $e) {
                // Ignore WebSocket exception if reverb/pusher is not active
            }

            Log::info("=== [MAYAR WEBHOOK PAYMENT CONFIRMED] ===", [
                'order_number' => $order->order_number,
                'old_payment_status' => $oldPaymentStatus,
                'new_payment_status' => 'paid',
                'old_status' => $oldStatus,
                'new_status' => $order->status,
                'amount' => $order->total_amount,
                'event' => $event,
                'status' => $status,
                'strategy' => $matchedStrategy,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil diverifikasi menjadi PAID.',
                'order_number' => $order->order_number,
                'status' => 'paid',
                'strategy' => $matchedStrategy,
            ], 200);
        }

        Log::info("[MAYAR WEBHOOK] Order found, but status/event is not a paid event: {$status} / {$event}");

        return response()->json([
            'success' => true,
            'message' => 'Webhook diterima, status belum berstatus lunas.',
            'order_number' => $order->order_number,
            'status' => $status,
            'event' => $event,
        ], 200);
    }
}
