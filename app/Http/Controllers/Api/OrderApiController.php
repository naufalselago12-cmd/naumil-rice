<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use App\Models\Client;
use App\Models\Menu;
use App\Models\Campaign;
use App\Models\LoyaltyPromo;
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
            'order_type' => 'nullable|in:guest,loyalty',
            'loyalty_promo_id' => 'nullable|exists:loyalty_promos,id',
            'loyalty_benefit_choice' => 'nullable|in:discount,points',
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
            $orderType = $validated['order_type'] ?? 'guest';
            $isLoyalty = false;
            $client = null;

            // 1. Check if customer is ordering as a Pelanggan Setia
            if ($orderType === 'loyalty') {
                $sessionClientId = session('customer_client_id');
                if ($sessionClientId) {
                    $client = Client::find($sessionClientId);
                }
                if (!$client) {
                    $client = Client::where('email', strtolower(trim($validated['customer_email'])))->first();
                }

                if ($client && $client->is_loyalty_member && $client->is_active) {
                    $isLoyalty = true;
                    $client->update([
                        'name' => $validated['customer_name'],
                        'whatsapp' => $validated['customer_whatsapp'],
                        'address' => $validated['customer_address'],
                        'last_order_at' => now(),
                    ]);
                    // Auto affiliate to this store
                    $client->stores()->syncWithoutDetaching([$validated['store_id']]);
                }
            }

            // 2. If guest order (Beli makanan saja)
            if (!$isLoyalty) {
                // Find or create guest client without requiring email verification
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
                        'is_loyalty_member' => false,
                        'last_order_at' => now(),
                    ]);
                }
                // NOTE: Guest orders DO NOT require email verification, so NO activation email is sent!
            }

            // 3. Check campaign if provided
            $campaign = null;
            if (!empty($validated['campaign_id'])) {
                $campaign = Campaign::with('menus')->find($validated['campaign_id']);
                if ($campaign && $campaign->is_expired) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Maaf, periode Open Pre-Order untuk campaign ini sudah ditutup.'
                    ], 422);
                }

                if ($campaign && $campaign->max_quota !== null) {
                    $incomingTotalQty = collect($validated['items'])->sum('quantity');
                    $alreadyOrdered = $campaign->total_ordered;
                    if ($alreadyOrdered + $incomingTotalQty > $campaign->max_quota) {
                        $remaining = max(0, $campaign->max_quota - $alreadyOrdered);
                        return response()->json([
                            'success' => false,
                            'message' => $remaining > 0
                                ? "Maaf, kuota pesanan untuk batch Open PO ini tersisa {$remaining} porsi. Jumlah pesanan Anda ({$incomingTotalQty} porsi) melebihi kuota yang tersedia."
                                : "Maaf, seluruh kuota untuk batch Open PO ini telah habis terjual (Sold Out)."
                        ], 422);
                    }
                }
            }

            // 4. Calculate items, totals, and loyalty reward points
            $subtotal = 0;
            $totalBasePoints = 0;
            $itemsData = [];
            $incomingTotalQty = collect($validated['items'])->sum('quantity');

            // Check if campaign is active and uses min_qty discount
            $isMinQtyCampaign = $campaign && ($campaign->discount_type === 'min_qty');

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

                        // Check menu-specific quota inside campaign if set
                        $menuMaxQuota = $campaignMenu->pivot->max_quota;
                        if ($menuMaxQuota !== null) {
                            $alreadyMenuOrdered = $campaign->getMenuOrderedQuantity($menu->id);
                            $requestedQty = (int) $itemInput['quantity'];
                            if ($alreadyMenuOrdered + $requestedQty > $menuMaxQuota) {
                                $remainingMenuQuota = max(0, $menuMaxQuota - $alreadyMenuOrdered);
                                return response()->json([
                                    'success' => false,
                                    'message' => $remainingMenuQuota > 0
                                        ? "Maaf, kuota untuk menu '{$menu->name}' pada batch Open PO ini tersisa {$remainingMenuQuota} porsi. Anda memesan {$requestedQty} porsi."
                                        : "Maaf, kuota pemesanan untuk menu '{$menu->name}' pada batch Open PO ini sudah habis terjual."
                                ], 422);
                            }
                        }
                    }
                }

                // For min_qty campaign, per-menu discount is evaluated at campaign level when min_order_qty condition is met
                $itemEffectivePrice = $isMinQtyCampaign ? $basePrice : max(0, $basePrice - $discountPrice);

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

                // Points earned per menu (only for loyalty members)
                if ($isLoyalty && ($menu->reward_points ?? 0) > 0) {
                    $totalBasePoints += ((int)$menu->reward_points * $itemInput['quantity']);
                }

                $itemsData[] = [
                    'menu_id' => $menu->id,
                    'menu_name' => $menu->name,
                    'price' => $linePrice,
                    'quantity' => $itemInput['quantity'],
                    'subtotal' => $lineSubtotal,
                    'base_price' => $basePrice,
                    'discount_price' => $discountPrice,
                    'selected_options' => $optionsList,
                    'special_notes' => $itemInput['special_notes'] ?? null,
                ];

                // Decrement stock if trackable
                if ($menu->current_stock >= $itemInput['quantity']) {
                    $menu->decrement('current_stock', $itemInput['quantity']);
                }
            }

            // 5. Calculate discounts and loyalty point benefits
            $discountAmount = 0;
            $isEarlyBird = false;

            if ($campaign) {
                // Check early bird qualification (First X orders in this campaign)
                $existingOrdersCount = Order::where('campaign_id', $campaign->id)
                    ->where('status', '!=', 'Order Reject')
                    ->count();
                $earlyBirdQuota = (int) ($campaign->early_bird_quota ?? 5);
                $isEarlyBird = $existingOrdersCount < $earlyBirdQuota;

                if ($campaign->discount_type === 'min_qty') {
                    $minQty = (int) ($campaign->min_order_qty ?? 15);
                    $meetsMinQty = ($minQty > 0 && $incomingTotalQty >= $minQty);

                    // Rules:
                    // 1. 5 order pertama ($isEarlyBird) TETAP mendapatkan promo per menu yang sudah disetting.
                    //    Jika order early bird ini juga mencapai min_order_qty, menu yang tidak punya diskon per menu juga dapat diskon persentase promo.
                    // 2. Jika sudah melewati 5 orang (bukan early bird):
                    //    - Jika total pesanan customer >= min_order_qty (misal >= 15):
                    //      Semua pembeli ini DAPAT PROMO sesuai rules:
                    //      Menu yang ada diskon per menu dapat diskon per menu.
                    //      Menu tanpa diskon per menu dapat diskon persentase promo.
                    //    - Jika total pesanan customer < min_order_qty (< 15):
                    //      Promo hangus, harga normal (diskon = 0).
                    if ($isEarlyBird || $meetsMinQty) {
                        foreach ($itemsData as $itemLine) {
                            $qty = (int) $itemLine['quantity'];
                            $bPrice = (float) $itemLine['base_price'];
                            $dPrice = (float) $itemLine['discount_price'];

                            if ($dPrice > 0) {
                                $discountAmount += ($dPrice * $qty);
                            } elseif ($meetsMinQty) {
                                $pct = (float) ($campaign->discount_value ?? 0);
                                $discountAmount += (($bPrice * $pct) / 100) * $qty;
                            }
                        }
                    }
                } elseif ($campaign->discount_type === 'percentage') {
                    $discountAmount += ($subtotal * $campaign->discount_value) / 100;
                } elseif ($campaign->discount_type === 'fixed') {
                    $discountAmount += min($subtotal, $campaign->discount_value);
                }
            }

            $pointsEarned = 0;
            $appliedPromoId = null;
            $benefitChoice = null;

            if ($isLoyalty) {
                $pointsEarned = $totalBasePoints;

                if (!empty($validated['loyalty_promo_id'])) {
                    $loyaltyPromo = LoyaltyPromo::with('menus')
                        ->where('id', $validated['loyalty_promo_id'])
                        ->where('store_id', $validated['store_id'])
                        ->first();

                    if ($loyaltyPromo && $loyaltyPromo->isCurrentlyActive()) {
                        $appliedPromoId = $loyaltyPromo->id;
                        $benefitChoice = $validated['loyalty_benefit_choice'] ?? 'discount';

                        // If promo allows choice and customer picked 'points' (Poin Lebih Banyak)
                        if ($loyaltyPromo->allow_benefit_choice && $benefitChoice === 'points') {
                            $multiplier = max(1.0, (float)($loyaltyPromo->extra_points_multiplier ?: 2.0));
                            $bonus = (int)($loyaltyPromo->extra_points_bonus ?: 0);
                            $pointsEarned = (int)round(($totalBasePoints * $multiplier) + $bonus);
                            // No additional monetary discount applied when extra points is chosen
                        } else {
                            // Customer chose discount
                            $loyaltyDiscount = 0;
                            if ($loyaltyPromo->promo_type === 'all_menu_percent') {
                                $pct = (float)$loyaltyPromo->discount_percent;
                                $loyaltyDiscount = ($subtotal * $pct) / 100;
                            } elseif ($loyaltyPromo->promo_type === 'per_menu') {
                                foreach ($itemsData as $itemLine) {
                                    $pMenu = $loyaltyPromo->menus->where('id', $itemLine['menu_id'])->first();
                                    if ($pMenu && $pMenu->pivot) {
                                        if ($pMenu->pivot->discount_percent > 0) {
                                            $loyaltyDiscount += ($itemLine['subtotal'] * $pMenu->pivot->discount_percent) / 100;
                                        } elseif ($pMenu->pivot->discount_amount > 0) {
                                            $loyaltyDiscount += ($pMenu->pivot->discount_amount * $itemLine['quantity']);
                                        }
                                    }
                                }
                            } elseif ($loyaltyPromo->promo_type === 'min_purchase_discount') {
                                if ($subtotal >= $loyaltyPromo->min_purchase_amount) {
                                    $loyaltyDiscount = (float)$loyaltyPromo->discount_amount;
                                }
                            }

                            $discountAmount += $loyaltyDiscount;
                            $benefitChoice = 'discount';
                        }
                    }
                }
            }

            $discountAmount = min($subtotal, $discountAmount);
            $totalAmount = max(0, $subtotal - $discountAmount);

            // 6. Generate unique order number
            $datePrefix = Carbon::now()->format('ymd');
            $randomSuffix = strtoupper(Str::random(4));
            $orderNumber = "NR-PO-{$datePrefix}-{$randomSuffix}";

            // 7. Create Order
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
                'is_loyalty_order' => $isLoyalty,
                'loyalty_promo_id' => $appliedPromoId,
                'loyalty_benefit_choice' => $benefitChoice,
                'points_earned' => $pointsEarned,
                'points_used' => 0,
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
            ]);

            // 8. Create Order Items
            foreach ($itemsData as $itemData) {
                $order->items()->create([
                    'menu_id' => $itemData['menu_id'],
                    'menu_name' => $itemData['menu_name'],
                    'price' => $itemData['price'],
                    'quantity' => $itemData['quantity'],
                    'subtotal' => $itemData['subtotal'],
                    'selected_options' => $itemData['selected_options'],
                    'special_notes' => $itemData['special_notes'],
                ]);
            }

            // 9. Credit loyalty points to customer if earned
            if ($isLoyalty && $pointsEarned > 0) {
                $client->addPoints($pointsEarned, "Reward poin dari pesanan #{$orderNumber}", $validated['store_id'], $order->id);
            }

            // 10. Create Status Log
            $earlyBirdTag = $isEarlyBird ? ' [Early Bird - Promo 5 Pembeli Pertama]' : '';
            OrderStatusLog::create([
                'order_id' => $order->id,
                'status' => Order::STATUS_CONFIRMED,
                'note' => 'Pre-Order berhasil disubmit oleh ' . $client->name . $earlyBirdTag . ($isLoyalty ? ' (Pelanggan Setia - Reward: +' . $pointsEarned . ' Pts)' : ' (Tamu)'),
            ]);

            // Load full relationships
            $order->load(['store', 'client', 'items.menu', 'statusLogs', 'campaign', 'loyaltyPromo']);

            // 11. If Payment Gateway (Mayar.id), create payment invoice link
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

            // 12. Broadcast WebSocket event
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

        // Auto-sync payment status with Mayar if order is still unpaid
        if ($order->payment_status === 'unpaid' 
            && in_array($order->payment_method, ['Payment Gateway', 'Payment Gateway (Mayar.id)'])
            && $order->store
            && $order->store->hasMayarConfigured()) {
            try {
                $mayarService = new \App\Services\MayarPaymentService();
                $check = $mayarService->checkPaymentStatus($order->store, $order);
                if ($check['success'] && $check['is_paid']) {
                    $order->update([
                        'payment_status' => 'paid',
                        'status' => in_array($order->status, [Order::STATUS_CONFIRMED, 'pending']) ? Order::STATUS_PROCESSING : $order->status,
                    ]);
                    \App\Models\OrderStatusLog::create([
                        'order_id' => $order->id,
                        'status' => $order->status,
                        'note' => 'Pembayaran terkonfirmasi lunas via Mayar.id (sinkronisasi live trace).',
                    ]);
                    try {
                        broadcast(new \App\Events\OrderStatusUpdated($order))->toOthers();
                    } catch (\Throwable $e) {}
                    $order->refresh();
                }
            } catch (\Throwable $e) {}
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

        // Check if order is a Pre-Order (linked to a Campaign with ready_at)
        $isPreorder = !empty($order->campaign_id) && !empty($order->campaign);
        $campaignReadyAt = $isPreorder && !empty($order->campaign->ready_at) ? $order->campaign->ready_at : null;

        if ($isPreorder && $campaignReadyAt) {
            Carbon::setLocale('id');
            $readyDateFormatted = $campaignReadyAt->locale('id')->translatedFormat('l, d F Y');
            $readyTimeFormatted = $campaignReadyAt->format('H:i') . ' WIB';
            $readyFullFormatted = $campaignReadyAt->locale('id')->translatedFormat('l, d F Y • H:i') . ' WIB';

            $isReady = $order->status === Order::STATUS_READY;
            $isDelivery = $order->status === Order::STATUS_DELIVERY;
            $isReject = $order->status === Order::STATUS_REJECT;

            $queueBadge = "Batch PO: " . $order->campaign->title;
            if ($isReady) {
                $queueMessage = 'Pesanan Pre-Order Anda telah selesai dimasak oleh dapur dan siap diambil / diantar!';
            } elseif ($isDelivery) {
                $queueMessage = 'Pesanan Pre-Order Anda sedang dalam pengantaran oleh kurir menuju alamat Anda!';
            } elseif ($isReject) {
                $queueMessage = 'Pesanan Pre-Order ini dibatalkan oleh dapur.';
            } else {
                $queueMessage = "Pesanan terdaftar dalam batch Pre-Order ({$order->campaign->title}). Menu PO dijadwalkan siap (ready) pada {$readyFullFormatted}.";
            }

            $queueInfo = [
                'is_in_queue' => false,
                'is_preorder' => true,
                'campaign_id' => $order->campaign->id,
                'campaign_title' => $order->campaign->title,
                'ready_at' => $campaignReadyAt->toISOString(),
                'ready_date_formatted' => $readyDateFormatted,
                'ready_time_formatted' => $readyTimeFormatted,
                'ready_full_formatted' => $readyFullFormatted,
                'queue_number' => null,
                'orders_ahead' => 0,
                'total_active_queue' => count($storeActiveOrders),
                'estimated_minutes' => null,
                'queue_badge' => $queueBadge,
                'queue_message' => $queueMessage,
            ];
        } else {
            $queueInfo = [
                'is_in_queue' => false,
                'is_preorder' => false,
                'campaign_id' => null,
                'campaign_title' => null,
                'ready_at' => null,
                'ready_date_formatted' => null,
                'ready_time_formatted' => null,
                'ready_full_formatted' => null,
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

                $queueInfo['is_in_queue'] = true;
                $queueInfo['queue_number'] = $queueNumber;
                $queueInfo['orders_ahead'] = $ordersAhead;
                $queueInfo['estimated_minutes'] = $estimatedMinutes;
                $queueInfo['queue_badge'] = "Antrean #{$queueNumber}";
                $queueInfo['queue_message'] = $ordersAhead === 0
                    ? 'Pesanan Anda berada di urutan pertama (sedang dimasak/dipersiapkan oleh dapur)!'
                    : "Anda berada di nomor antrean #{$queueNumber}. Ada {$ordersAhead} pesanan lain sedang disiapkan di depan Anda.";
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

        $query = Order::with(['client', 'store', 'items.menu', 'statusLogs', 'campaign'])
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
        $order->payment_status = 'payment_on_review';

        $order->save();

        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => $order->status,
            'note' => 'Bukti pembayaran QRIS berhasil diunggah oleh pelanggan. Menunggu verifikasi admin (Status: Payment on Review).',
        ]);

        $order->load(['store', 'client', 'items.menu', 'statusLogs', 'campaign']);

        // Broadcast to WebSocket channel
        try {
            broadcast(new OrderStatusUpdated($order));
        } catch (\Exception $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Bukti pembayaran QRIS berhasil diunggah! Pembayaran Anda sedang ditinjau oleh kasir/admin cabang (Payment on Review).',
            'data' => [
                'order' => $order,
                'payment_status' => $order->payment_status,
                'payment_proof_url' => $order->payment_proof_url,
            ]
        ]);
    }
}
