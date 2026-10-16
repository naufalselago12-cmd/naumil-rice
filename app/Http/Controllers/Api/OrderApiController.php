<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use App\Models\Client;
use App\Models\Menu;
use App\Models\Campaign;
use App\Events\NewOrderCreated;
use App\Events\OrderStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OrderApiController extends Controller
{
    /**
     * Submit pre-order from frontend
     */
    public function store(Request $request)
    {
        if ($request->has('client') && !$request->has('customer_name')) {
            $whatsapp = $request->input('client.whatsapp', '');
            $email = $request->input('client.email') ?: ($whatsapp ? $whatsapp . '@naumilrice.com' : 'pelanggan@naumilrice.com');
            $request->merge([
                'customer_name' => $request->input('client.name'),
                'customer_whatsapp' => $whatsapp,
                'customer_email' => $email,
                'customer_address' => $request->input('client.address'),
            ]);
        }

        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'campaign_id' => 'nullable|exists:campaigns,id',
            'customer_name' => 'required|string|max:255',
            'customer_whatsapp' => 'required|string|max:50',
            'customer_email' => 'required|email|max:255',
            'customer_address' => 'required|string|max:1000',
            'payment_method' => 'required|in:QRIS Statik,QRIS Dinamis,Payment Gateway,Payment Gateway (Mayar.id),Bayar Terima Makanan',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.menu_id' => 'required|exists:menus,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.selected_options' => 'nullable|array',
            'items.*.special_notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            // 1. Check or register Client (Nama Lengkap, No HP, Email, Alamat Lengkap)
            $client = Client::where('whatsapp', $validated['customer_whatsapp'])
                ->orWhere('email', $validated['customer_email'])
                ->first();

            if ($client) {
                $client->update([
                    'name' => $validated['customer_name'],
                    'whatsapp' => $validated['customer_whatsapp'],
                    'email' => $validated['customer_email'],
                    'address' => $validated['customer_address'],
                    'last_order_at' => now(),
                ]);
            } else {
                $client = Client::create([
                    'name' => $validated['customer_name'],
                    'whatsapp' => $validated['customer_whatsapp'],
                    'email' => $validated['customer_email'],
                    'address' => $validated['customer_address'],
                    'is_active' => false,
                    'last_order_at' => now(),
                ]);
            }

            // Send activation link email if customer account is not active yet
            if (!$client->is_active) {
                app(\App\Services\CustomerNotificationService::class)->sendActivationEmail($client);
            }

            // Automatically log in customer into session
            session([
                'customer_client_id' => $client->id,
                'customer_email' => $client->email,
                'customer_name' => $client->name,
            ]);

            // 2. Check campaign if provided
            $campaign = null;
            if (!empty($validated['campaign_id'])) {
                $campaign = Campaign::with('menus')->find($validated['campaign_id']);
                if ($campaign && $campaign->is_expired) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Maaf, periode Open Pre-Order untuk campaign ini sudah ditutup.'
                    ], 422);
                }
            }

            // 3. Calculate items and totals
            $subtotal = 0;
            $itemsData = [];

            foreach ($validated['items'] as $itemInput) {
                $menu = Menu::with('options')->findOrFail($itemInput['menu_id']);
                
                // Determine item base price (check campaign pivot or standard price)
                $basePrice = $menu->price;
                $discountPrice = 0;

                if ($campaign) {
                    $campaignMenu = $campaign->menus->where('id', $menu->id)->first();
                    if ($campaignMenu && $campaignMenu->pivot) {
                        $basePrice = $campaignMenu->pivot->custom_price ?? $menu->price;
                        $discountPrice = $campaignMenu->pivot->discount_price ?? 0;
                    }
                }

                $itemEffectivePrice = max(0, $basePrice - $discountPrice);

                // Add options price (toppings, upsize)
                $optionsPrice = 0;
                $optionsList = [];
                if (!empty($itemInput['selected_options'])) {
                    foreach ($itemInput['selected_options'] as $opt) {
                        $optPrice = (float) ($opt['price'] ?? 0);
                        $optionsPrice += $optPrice;
                        $optionsList[] = [
                            'name' => $opt['name'] ?? '',
                            'price' => $optPrice,
                            'type' => $opt['type'] ?? 'addon'
                        ];
                    }
                }

                $linePrice = ($itemEffectivePrice + $optionsPrice);
                $lineSubtotal = $linePrice * $itemInput['quantity'];
                $subtotal += $lineSubtotal;

                $itemsData[] = [
                    'menu_id' => $menu->id,
                    'menu_name' => $menu->name,
                    'price' => $linePrice,
                    'quantity' => $itemInput['quantity'],
                    'subtotal' => $lineSubtotal,
                    'selected_options' => $optionsList,
                    'special_notes' => $itemInput['special_notes'] ?? null,
                ];

                // Decrement stock if trackable
                if ($menu->current_stock >= $itemInput['quantity']) {
                    $menu->decrement('current_stock', $itemInput['quantity']);
                }
            }

            // 4. Calculate campaign level discount
            $discountAmount = 0;
            if ($campaign) {
                if ($campaign->discount_type === 'min_purchase' && $subtotal >= $campaign->min_purchase_amount) {
                    $discountAmount = min($subtotal, $campaign->discount_value);
                } elseif ($campaign->discount_type === 'percentage') {
                    $discountAmount = ($subtotal * $campaign->discount_value) / 100;
                }
            }

            $totalAmount = max(0, $subtotal - $discountAmount);

            // 5. Generate unique order number
            $datePrefix = Carbon::now()->format('ymd');
            $randomSuffix = strtoupper(Str::random(4));
            $orderNumber = "NR-PO-{$datePrefix}-{$randomSuffix}";

            // 6. Create Order
            $order = Order::create([
                'order_number' => $orderNumber,
                'store_id' => $validated['store_id'],
                'campaign_id' => $validated['campaign_id'] ?? null,
                'client_id' => $client->id,
                'status' => Order::STATUS_CONFIRMED,
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'unpaid',
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
            ]);

            // 7. Create Order Items
            foreach ($itemsData as $itemData) {
                $order->items()->create($itemData);
            }

            // 8. Create Status Log
            OrderStatusLog::create([
                'order_id' => $order->id,
                'status' => Order::STATUS_CONFIRMED,
                'note' => 'Pre-Order berhasil disubmit oleh ' . $client->name,
            ]);

            // Load full relationships
            $order->load(['store', 'client', 'items.menu', 'statusLogs', 'campaign']);

            // 9. If Payment Gateway (Mayar.id), create payment invoice link
            $paymentUrl = null;
            $paymentMessage = null;
            if (in_array($validated['payment_method'], ['Payment Gateway', 'Payment Gateway (Mayar.id)'])) {
                $mayarService = new \App\Services\MayarPaymentService();
                $mayarRes = $mayarService->createPaymentLink($order->store, $order);
                if ($mayarRes['success'] && !empty($mayarRes['payment_url'])) {
                    $paymentUrl = $mayarRes['payment_url'];
                    $order->refresh();
                } else {
                    $paymentMessage = $mayarRes['message'] ?? 'Gagal membuat link pembayaran Mayar.';
                }
            }

            // 10. Broadcast WebSocket event
            try {
                broadcast(new NewOrderCreated($order));
            } catch (\Exception $e) {
                // If reverb daemon not running, gracefully ignore without interrupting response
            }

            return response()->json([
                'success' => true,
                'message' => 'Pre-Order berhasil dibuat!',
                'data' => [
                    'order_number' => $order->order_number,
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'total_amount' => $order->total_amount,
                    'payment_method' => $order->payment_method,
                    'payment_url' => $order->payment_url ?? $paymentUrl,
                    'payment_message' => $paymentMessage,
                    'payment_type' => $order->store->payment_type,
                    'store_qris_image' => $order->store->qris_image,
                    'store_qris_data' => $order->store->qris_data,
                    'client' => $client,
                    'order' => $order,
                ]
            ], 201);
        });
    }

    /**
     * Real-time trace order by order number
     */
    public function trace(string $orderNumber)
    {
        $order = Order::with(['store', 'client', 'items.menu', 'statusLogs', 'campaign'])
            ->where('order_number', $orderNumber)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan dengan nomor: ' . $orderNumber
            ], 404);
        }

        $stages = [
            Order::STATUS_CONFIRMED,
            Order::STATUS_PROCESSING,
            Order::STATUS_PACKAGING,
            Order::STATUS_DELIVERY,
            Order::STATUS_READY,
        ];

        $currentStageIndex = array_search($order->status, $stages);
        if ($currentStageIndex === false) {
            $currentStageIndex = $order->status === Order::STATUS_REJECT ? -1 : 0;
        }

        // Calculate store active preparation queue
        $activeStatuses = [
            Order::STATUS_CONFIRMED,
            Order::STATUS_PROCESSING,
            Order::STATUS_PACKAGING,
        ];

        $storeActiveOrders = Order::where('store_id', $order->store_id)
            ->whereIn('status', $activeStatuses)
            ->orderBy('id', 'asc')
            ->pluck('id')
            ->toArray();

        $queueInfo = [
            'is_in_queue' => false,
            'queue_number' => null,
            'orders_ahead' => 0,
            'total_active_queue' => count($storeActiveOrders),
            'estimated_minutes' => 0,
            'queue_badge' => '',
            'queue_message' => '',
        ];

        $pos = array_search($order->id, $storeActiveOrders);
        if ($pos !== false) {
            $queueNumber = $pos + 1;
            $ordersAhead = $pos;
            $estimatedMinutes = ($ordersAhead * 10) + 15;

            $queueInfo = [
                'is_in_queue' => true,
                'queue_number' => $queueNumber,
                'orders_ahead' => $ordersAhead,
                'total_active_queue' => count($storeActiveOrders),
                'estimated_minutes' => $estimatedMinutes,
                'queue_badge' => "Antrean #{$queueNumber}",
                'queue_message' => $ordersAhead === 0
                    ? 'Pesanan Anda berada di urutan pertama (sedang dimasak/dipersiapkan oleh dapur)!'
                    : "Anda berada di nomor antrean #{$queueNumber}. Ada {$ordersAhead} pesanan lain sedang disiapkan di depan Anda.",
            ];
        } else {
            if ($order->status === Order::STATUS_READY) {
                $queueInfo['queue_badge'] = 'Pesanan Siap (Ready)';
                $queueInfo['queue_message'] = 'Pesanan Anda telah selesai dimasak dan siap dinikmati!';
            } elseif ($order->status === Order::STATUS_DELIVERY) {
                $queueInfo['queue_badge'] = 'Dalam Pengantaran';
                $queueInfo['queue_message'] = 'Pesanan Anda sedang dibawa kurir menuju lokasi!';
            } elseif ($order->status === Order::STATUS_REJECT) {
                $queueInfo['queue_badge'] = 'Dibatalkan / Tolak';
                $queueInfo['queue_message'] = 'Pesanan ini tidak dapat dilanjutkan oleh dapur.';
            } else {
                $queueInfo['queue_badge'] = $order->status;
                $queueInfo['queue_message'] = 'Pesanan tercatat di antrean sistem.';
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $order,
                'stages' => $stages,
                'current_stage_index' => $currentStageIndex,
                'is_rejected' => $order->status === Order::STATUS_REJECT,
                'rejection_reason' => $order->rejection_reason,
                'queue_info' => $queueInfo,
            ]
        ]);
    }

    /**
     * Kitchen Display System (KDS) Active Queue
     */
    public function kdsQueue(Request $request)
    {
        $storeId = $request->query('store_id');

        $query = Order::with(['client', 'store', 'items.menu', 'statusLogs'])
            ->orderBy('id', 'asc');

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        // Show active queue: Confirmed, On Proses, On Packaging, On Delivery, and recent Ready
        $orders = $query->where(function ($q) {
            $q->whereIn('status', [
                Order::STATUS_CONFIRMED,
                Order::STATUS_PROCESSING,
                Order::STATUS_PACKAGING,
                Order::STATUS_DELIVERY,
            ])->orWhere(function ($q2) {
                $q2->where('status', Order::STATUS_READY)
                   ->where('updated_at', '>=', now()->subHours(12));
            });
        })->get();

        $activeQueueIndex = 1;
        $orders = $orders->map(function ($ord) use (&$activeQueueIndex) {
            $isInActive = in_array($ord->status, [
                Order::STATUS_CONFIRMED,
                Order::STATUS_PROCESSING,
                Order::STATUS_PACKAGING,
            ]);

            $ord->queue_number = $isInActive ? $activeQueueIndex++ : null;
            $ord->waiting_minutes = abs((int) round($ord->created_at ? $ord->created_at->diffInMinutes(now()) : 0));
            $ord->total_portions = $ord->items->sum('quantity');
            return $ord;
        });

        return response()->json([
            'success' => true,
            'data' => $orders,
            'total_queue' => $orders->count(),
            'active_cooking_queue' => $activeQueueIndex - 1,
        ]);
    }

    /**
     * Update order status (used by KDS / Admin)
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:Order Konfirmed,Order On Proses,Order On Packaging,Order On Delivery,Order Ready,Order Reject',
            'note' => 'nullable|string',
            'rejection_reason' => 'nullable|string',
        ]);

        $order = Order::findOrFail($id);

        $order->status = $validated['status'];
        if ($validated['status'] === Order::STATUS_REJECT) {
            $order->rejection_reason = $validated['rejection_reason'] ?? 'Pesanan dibatalkan atau antrean penuh';
        }
        $order->save();

        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => $validated['status'],
            'note' => $validated['note'] ?? ($validated['status'] === Order::STATUS_REJECT ? $order->rejection_reason : 'Status diperbarui menjadi ' . $validated['status']),
        ]);

        $order->load(['store', 'client', 'items.menu', 'statusLogs', 'campaign']);

        // Broadcast to WebSocket channel
        try {
            broadcast(new OrderStatusUpdated($order));
        } catch (\Exception $e) {
            // Gracefully ignore if broadcast queue is offline
        }

        return response()->json([
            'success' => true,
            'message' => 'Status pesanan berhasil diperbarui menjadi: ' . $order->status,
            'data' => $order
        ]);
    }

    /**
     * Upload QRIS payment proof by customer
     * Sets payment_status to 'paid'
     */
    public function uploadPaymentProof(Request $request, string $orderNumber)
    {
        $validated = $request->validate([
            'proof_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $order = Order::with(['store', 'client'])->where('order_number', $orderNumber)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan dengan nomor: ' . $orderNumber
            ], 404);
        }

        $file = $request->file('proof_image');
        $fileName = 'proof_' . $order->order_number . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('payment_proofs', $fileName, 'public');

        $order->payment_proof = $path;
        $order->payment_status = 'paid';

        // Advance to processing if currently confirmed
        if ($order->status === Order::STATUS_CONFIRMED) {
            $order->status = Order::STATUS_PROCESSING;
        }

        $order->save();

        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => $order->status,
            'note' => 'Bukti pembayaran QRIS berhasil diunggah oleh pelanggan. Status Pembayaran: Paid.',
        ]);

        $order->load(['store', 'client', 'items.menu', 'statusLogs', 'campaign']);

        // Broadcast to WebSocket channel
        try {
            broadcast(new OrderStatusUpdated($order));
        } catch (\Exception $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Bukti pembayaran QRIS berhasil diunggah! Status pembayaran kini Paid.',
            'data' => [
                'order' => $order,
                'payment_status' => $order->payment_status,
                'payment_proof_url' => $order->payment_proof_url,
            ]
        ]);
    }
}
