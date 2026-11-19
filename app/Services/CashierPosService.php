<?php

namespace App\Services;

use App\Events\NewOrderCreated;
use App\Events\OrderStatusUpdated;
use App\Models\Category;
use App\Models\Client;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CashierPosService
{
    private static ?bool $hasOrderSourceColumn = null;
    private static ?bool $hasCashierUserIdColumn = null;
    private static ?bool $hasFulfillmentTypeColumn = null;

    protected function hasOrderSourceColumn(): bool
    {
        return self::$hasOrderSourceColumn ??= Schema::hasColumn('orders', 'order_source');
    }

    protected function hasCashierUserIdColumn(): bool
    {
        return self::$hasCashierUserIdColumn ??= Schema::hasColumn('orders', 'cashier_user_id');
    }

    protected function hasFulfillmentTypeColumn(): bool
    {
        return self::$hasFulfillmentTypeColumn ??= Schema::hasColumn('orders', 'fulfillment_type');
    }

    /**
     * Katalog POS Kasir: hanya menu aktif milik cabang tersebut + kategori & info Midtrans.
     */
    public function getActiveCatalog(Store $store): array
    {
        $categories = Category::where('store_id', $store->id)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $menus = Menu::with(['category:id,name', 'options'])
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->orderBy('category_id')
            ->orderBy('name')
            ->get()
            ->map(function (Menu $m) {
                $img = $m->image;
                $imgUrl = null;
                if ($img) {
                    if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
                        $imgUrl = $img;
                    } elseif (str_starts_with($img, '/storage/') || str_starts_with($img, 'storage/')) {
                        $imgUrl = asset(ltrim($img, '/'));
                    } elseif (str_starts_with($img, '/')) {
                        $imgUrl = url($img);
                    } else {
                        $imgUrl = asset('storage/' . ltrim($img, '/'));
                    }
                }

                return [
                    'id' => $m->id,
                    'category_id' => $m->category_id,
                    'category_name' => $m->category?->name ?? 'Menu',
                    'name' => $m->name,
                    'description' => $m->description,
                    'price' => (float) $m->price,
                    'current_stock' => (int) ($m->current_stock ?? 0),
                    'initial_stock' => (int) ($m->initial_stock ?? 0),
                    'image' => $m->image,
                    'image_url' => $imgUrl,
                    'is_active' => (bool) $m->is_active,
                    'options' => $m->options->map(fn ($o) => [
                        'id' => $o->id,
                        'type' => $o->type,
                        'name' => $o->name,
                        'price' => (float) $o->price,
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();

        $activeCatIds = collect($menus)->pluck('category_id')->filter()->unique()->all();
        $filteredCategories = $categories
            ->filter(fn ($c) => empty($activeCatIds) || in_array($c->id, $activeCatIds, true))
            ->values()
            ->all();

        return [
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'code' => $store->code,
                'has_midtrans' => $store->hasMidtransConfigured(),
                'midtrans_environment' => $store->active_midtrans_environment,
                'midtrans_client_key' => $store->active_midtrans_client_key,
                'midtrans_snap_js_url' => MidtransPaymentService::getSnapJsUrl($store->active_midtrans_environment),
            ],
            'categories' => $filteredCategories,
            'menus' => $menus,
        ];
    }

    /**
     * Buat transaksi pesanan dari Kasir (POS) tanpa input data pelanggan.
     * - payment_mode = 'cash': status bayar awalnya 'unpaid' agar kasir klik tombol "Lunas" setelah menerima uang.
     * - payment_mode = 'qris': langsung membuat transaksi QRIS Midtrans dan mengembalikan QR / Snap URL.
     */
    public function createOrder(Store $store, User $actor, array $payload): array
    {
        $paymentMode = strtolower((string) ($payload['payment_mode'] ?? 'cash'));
        if (!in_array($paymentMode, ['cash', 'qris'], true)) {
            throw ValidationException::withMessages([
                'payment_mode' => 'Metode pembayaran kasir hanya mendukung Cash atau QRIS Midtrans.',
            ]);
        }

        if ($paymentMode === 'qris' && !$store->hasMidtransConfigured()) {
            throw ValidationException::withMessages([
                'payment_mode' => 'QRIS Midtrans belum dikonfigurasi pada cabang ini. Atur Server Key & Client Key Midtrans di Pengaturan Toko terlebih dahulu.',
            ]);
        }

        $itemsInput = $payload['items'] ?? [];
        if (empty($itemsInput) || !is_array($itemsInput)) {
            throw ValidationException::withMessages([
                'items' => 'Pilih minimal 1 menu sebelum checkout.',
            ]);
        }

        $order = DB::transaction(function () use ($store, $actor, $paymentMode, $itemsInput, $payload) {
            $walkInClient = $this->resolveWalkInClient($store);

            $subtotal = 0.0;
            $preparedItems = [];

            foreach ($itemsInput as $row) {
                $menuId = (int) ($row['menu_id'] ?? 0);
                $qty = max(1, (int) ($row['quantity'] ?? 1));

                /** @var Menu|null $menu */
                $menu = Menu::with('options')
                    ->where('store_id', $store->id)
                    ->where('is_active', true)
                    ->find($menuId);

                if (!$menu) {
                    throw ValidationException::withMessages([
                        'items' => 'Salah satu menu sudah tidak aktif atau bukan milik cabang ini.',
                    ]);
                }

                $optionsPrice = 0.0;
                $optionsList = [];
                $dbOptionsById = $menu->options->keyBy('id');

                foreach (($row['selected_options'] ?? []) as $opt) {
                    if (!is_array($opt)) {
                        continue;
                    }
                    $optId = isset($opt['id']) ? (int) $opt['id'] : null;
                    if ($optId && $dbOptionsById->has($optId)) {
                        $dbOpt = $dbOptionsById->get($optId);
                        $optPrice = (float) $dbOpt->price;
                        $optionsList[] = [
                            'id' => $dbOpt->id,
                            'name' => $dbOpt->name,
                            'price' => $optPrice,
                            'type' => $dbOpt->type,
                        ];
                        $optionsPrice += $optPrice;
                    } elseif (!empty($opt['name'])) {
                        $optPrice = max(0.0, (float) ($opt['price'] ?? 0));
                        $optionsList[] = [
                            'name' => (string) $opt['name'],
                            'price' => $optPrice,
                            'type' => (string) ($opt['type'] ?? 'addon'),
                        ];
                        $optionsPrice += $optPrice;
                    }
                }

                $unitPrice = (float) $menu->price + $optionsPrice;
                $lineSubtotal = $unitPrice * $qty;
                $subtotal += $lineSubtotal;

                $preparedItems[] = [
                    'menu' => $menu,
                    'menu_id' => $menu->id,
                    'menu_name' => $menu->name,
                    'price' => $unitPrice,
                    'quantity' => $qty,
                    'subtotal' => $lineSubtotal,
                    'selected_options' => $optionsList,
                    'special_notes' => isset($row['special_notes']) && trim((string) $row['special_notes']) !== ''
                        ? trim((string) $row['special_notes'])
                        : null,
                ];
            }

            $storeCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($store->code ?: 'NM')) ?: 'NM');
            $orderNumber = 'POS-' . substr($storeCode, 0, 6) . '-' . now()->format('ymdHis') . '-' . strtoupper(Str::random(3));

            $orderAttrs = [
                'order_number' => $orderNumber,
                'store_id' => $store->id,
                'campaign_id' => null,
                'client_id' => $walkInClient->id,
                // KDS Kasir langsung masuk tahap Packaging agar step singkat (Packaging -> Done)
                'status' => Order::STATUS_PACKAGING,
                'payment_method' => $paymentMode === 'cash' ? Order::PAYMENT_CASHIER_CASH : Order::PAYMENT_CASHIER_QRIS,
                'payment_status' => 'unpaid',
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'total_amount' => $subtotal,
                'notes' => isset($payload['notes']) && trim((string) $payload['notes']) !== ''
                    ? trim((string) $payload['notes'])
                    : null,
            ];

            if ($this->hasFulfillmentTypeColumn()) {
                $orderAttrs['fulfillment_type'] = Order::FULFILLMENT_PICKUP;
                $orderAttrs['shipping_cost'] = 0;
            }
            if ($this->hasOrderSourceColumn()) {
                $orderAttrs['order_source'] = Order::SOURCE_CASHIER;
            }
            if ($this->hasCashierUserIdColumn()) {
                $orderAttrs['cashier_user_id'] = $actor->id;
            }

            $order = Order::create($orderAttrs);

            foreach ($preparedItems as $itemData) {
                /** @var Menu $menu */
                $menu = $itemData['menu'];
                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_id' => $itemData['menu_id'],
                    'menu_name' => $itemData['menu_name'],
                    'price' => $itemData['price'],
                    'quantity' => $itemData['quantity'],
                    'subtotal' => $itemData['subtotal'],
                    'selected_options' => $itemData['selected_options'],
                    'special_notes' => $itemData['special_notes'],
                ]);

                if ($menu->current_stock >= $itemData['quantity']) {
                    $menu->decrement('current_stock', $itemData['quantity']);
                }
            }

            OrderStatusLog::create([
                'order_id' => $order->id,
                'status' => Order::STATUS_PACKAGING,
                'note' => 'Pesanan dibuat dari Kasir POS oleh ' . $actor->name
                    . ' (' . ($paymentMode === 'cash' ? 'Cash - Menunggu Klik Lunas' : 'QRIS Midtrans') . ').',
            ]);

            return $order->load(['client', 'items.menu', 'cashier']);
        });

        $qrisData = null;
        if ($paymentMode === 'qris') {
            $midtrans = new MidtransPaymentService();
            $qrisRes = $midtrans->createQrisTransaction($store, $order);

            if (!$qrisRes['success']) {
                // Kembalikan stok & hapus order agar tidak ada order QRIS gagal yang menggantung
                DB::transaction(function () use ($order) {
                    foreach ($order->items as $item) {
                        if ($item->menu) {
                            $item->menu->increment('current_stock', $item->quantity);
                        }
                    }
                    $order->statusLogs()->delete();
                    $order->items()->delete();
                    $order->delete();
                });

                return [
                    'success' => false,
                    'message' => $qrisRes['message'] ?? 'Gagal membuat QRIS Midtrans.',
                ];
            }

            $order->refresh()->load(['client', 'items.menu', 'cashier']);
            $qrisData = $qrisRes;
        } elseif (!empty($payload['mark_paid'])) {
            $order = $this->markCashPaid($store, $order, $actor);
        }

        try {
            broadcast(new NewOrderCreated($order))->toOthers();
        } catch (\Throwable $e) {
        }

        return [
            'success' => true,
            'message' => $paymentMode === 'qris'
                ? 'QRIS Midtrans siap dipindai oleh pelanggan.'
                : ($order->payment_status === 'paid'
                    ? 'Transaksi Cash berhasil & sudah ditandai Lunas.'
                    : 'Transaksi Cash dibuat. Klik Lunas setelah menerima pembayaran tunai.'),
            'order' => $this->presentOrder($order),
            'qris' => $qrisData,
        ];
    }

    /**
     * Kasir menandai pesanan Cash sebagai LUNAS.
     * Otomatis mengirim push notifikasi notif_pembelian.wav ke Admin Toko saja (selain kasir).
     */
    public function markCashPaid(Store $store, Order $order, User $actor): Order
    {
        if ((int) $order->store_id !== (int) $store->id) {
            abort(403, 'Pesanan bukan milik cabang Anda.');
        }

        if (!$order->isCashierCash()) {
            throw ValidationException::withMessages([
                'order' => 'Hanya transaksi Kasir Cash yang dapat ditandai lunas secara manual oleh kasir.',
            ]);
        }

        if ($order->payment_status !== 'paid') {
            $order->payment_status = 'paid';
            $order->save(); // Order::booted() akan memicu notifyCashierPayment($order)

            OrderStatusLog::create([
                'order_id' => $order->id,
                'status' => $order->status,
                'note' => 'Pembayaran Tunai (Cash) dikonfirmasi LUNAS oleh kasir ' . $actor->name . '.',
            ]);

            try {
                broadcast(new OrderStatusUpdated($order))->toOthers();
            } catch (\Throwable $e) {
            }
        }

        return $order->fresh(['client', 'items.menu', 'cashier']);
    }

    /**
     * Cek & sinkronisasi status pembayaran QRIS Midtrans untuk pesanan Kasir.
     */
    public function syncQrisMidtrans(Store $store, Order $order): array
    {
        if ((int) $order->store_id !== (int) $store->id) {
            abort(403, 'Pesanan bukan milik cabang Anda.');
        }

        if ($order->payment_status === 'paid') {
            return [
                'success' => true,
                'is_paid' => true,
                'transaction_status' => 'settlement',
                'message' => 'Pembayaran QRIS sudah lunas.',
                'order' => $this->presentOrder($order->loadMissing(['client', 'items.menu', 'cashier'])),
            ];
        }

        $midtrans = new MidtransPaymentService();
        $res = $midtrans->checkPaymentStatus($store, $order);

        if (!empty($res['is_paid'])) {
            $order->payment_status = 'paid';
            $order->payment_payload = array_merge((array) $order->payment_payload, [
                'last_sync' => $res['data'] ?? [],
                'settled_at' => now()->toIso8601String(),
            ]);
            $order->save(); // Order::booted() -> notifyCashierPayment($order) (notif_pembayaran.wav ke Admin)

            OrderStatusLog::create([
                'order_id' => $order->id,
                'status' => $order->status,
                'note' => 'Pembayaran QRIS Midtrans terkonfirmasi LUNAS (' . ($res['transaction_status'] ?? 'settlement') . ').',
            ]);

            try {
                broadcast(new OrderStatusUpdated($order))->toOthers();
            } catch (\Throwable $e) {
            }
        }

        return [
            'success' => (bool) ($res['success'] ?? false),
            'is_paid' => $order->payment_status === 'paid',
            'transaction_status' => $res['transaction_status'] ?? ($order->payment_status === 'paid' ? 'settlement' : 'pending'),
            'message' => $res['message'] ?? 'Menunggu pembayaran pelanggan...',
            'order' => $this->presentOrder($order->fresh(['client', 'items.menu', 'cashier'])),
        ];
    }

    /**
     * Antrian KDS khusus Kasir:
     * Menampilkan pesanan aktif cabang (Confirmed / Proses / Packaging) dengan alur ringkas Packaging -> Done.
     */
    public function getKdsQueue(Store $store): array
    {
        $orders = Order::with(['client', 'items', 'cashier'])
            ->where('store_id', $store->id)
            ->whereIn('status', [
                Order::STATUS_CONFIRMED,
                Order::STATUS_PROCESSING,
                Order::STATUS_PACKAGING,
            ])
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn (Order $o) => $this->presentOrder($o))
            ->values()
            ->all();

        return $orders;
    }

    /**
     * Update status KDS dari Kasir: hanya langsung ke Packaging atau Done (Order Ready).
     */
    public function updateKdsStatus(Store $store, Order $order, string $status, User $actor): Order
    {
        if ((int) $order->store_id !== (int) $store->id) {
            abort(403, 'Pesanan bukan milik cabang Anda.');
        }

        if (!in_array($status, [Order::STATUS_PACKAGING, Order::STATUS_READY], true)) {
            throw ValidationException::withMessages([
                'status' => 'KDS Kasir hanya mendukung tahap Packaging atau Done (Order Ready).',
            ]);
        }

        $order->status = $status;
        $order->save();

        $label = $status === Order::STATUS_READY ? 'Done (Sudah Diterima)' : 'Packaging';
        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => $status,
            'note' => "Status KDS Kasir diubah ke {$label} oleh {$actor->name}.",
        ]);

        try {
            broadcast(new OrderStatusUpdated($order))->toOthers();
        } catch (\Throwable $e) {
        }

        return $order->fresh(['client', 'items', 'cashier']);
    }

    /**
     * Daftar transaksi Kasir terbaru hari ini (maks 40) agar kasir mudah cek / klik Lunas / buka QR lagi.
     */
    public function getRecentCashierOrders(Store $store): array
    {
        $query = Order::with(['client', 'items', 'cashier'])
            ->where('store_id', $store->id)
            ->where(function ($q) {
                if ($this->hasOrderSourceColumn()) {
                    $q->where('order_source', Order::SOURCE_CASHIER)
                      ->orWhereIn('payment_method', [Order::PAYMENT_CASHIER_CASH, Order::PAYMENT_CASHIER_QRIS]);
                } else {
                    $q->whereIn('payment_method', [Order::PAYMENT_CASHIER_CASH, Order::PAYMENT_CASHIER_QRIS]);
                }
            })
            ->whereDate('created_at', '>=', now()->subDay()->toDateString())
            ->orderByDesc('id')
            ->limit(40)
            ->get();

        return $query->map(fn (Order $o) => $this->presentOrder($o))->values()->all();
    }

    protected function resolveWalkInClient(Store $store): Client
    {
        return Client::firstOrCreate(
            ['email' => "kasir-store-{$store->id}@naumilrice.local"],
            [
                'name' => 'Pelanggan Kasir',
                'whatsapp' => '0000000000',
                'address' => $store->address ?: 'Transaksi Kasir Outlet',
                'is_active' => false,
                'is_loyalty_member' => false,
                'last_order_at' => now(),
            ]
        );
    }

    public function presentOrder(Order $order): array
    {
        $payload = is_array($order->payment_payload) ? $order->payment_payload : [];

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'store_id' => $order->store_id,
            'order_source' => $order->isCashierOrder() ? Order::SOURCE_CASHIER : ($order->order_source ?? Order::SOURCE_ONLINE),
            'is_cashier_order' => $order->isCashierOrder(),
            'is_cashier_cash' => $order->isCashierCash(),
            'is_cashier_qris' => $order->isCashierQris(),
            'cashier_user_id' => $order->cashier_user_id,
            'cashier_name' => $order->cashier?->name,
            'customer_name' => $order->isCashierOrder()
                ? ('Walk-in Kasir' . ($order->cashier?->name ? ' (' . $order->cashier->name . ')' : ''))
                : ($order->client?->name ?? 'Pelanggan'),
            'status' => $order->status,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'payment_url' => $order->payment_url,
            'snap_token' => $order->snap_token,
            'qris_url' => $payload['qris_url'] ?? (
                ($payload['mode'] ?? '') === 'core_qris' ? $order->payment_url : null
            ),
            'qr_string' => $payload['qr_string'] ?? null,
            'midtrans_mode' => $payload['mode'] ?? (!empty($order->snap_token) ? 'snap_qris' : null),
            'expiry_time' => $payload['expiry_time'] ?? null,
            'subtotal' => (float) $order->subtotal,
            'discount_amount' => (float) $order->discount_amount,
            'total_amount' => (float) $order->total_amount,
            'notes' => $order->notes,
            'created_at' => $order->created_at?->toIso8601String(),
            'created_human' => $order->created_at?->locale('id')->isoFormat('HH:mm, D MMM'),
            'items' => $order->items->map(fn ($i) => [
                'id' => $i->id,
                'menu_id' => $i->menu_id,
                'menu_name' => $i->menu_name,
                'quantity' => (int) $i->quantity,
                'price' => (float) $i->price,
                'subtotal' => (float) $i->subtotal,
                'selected_options' => $i->selected_options ?? [],
                'special_notes' => $i->special_notes,
            ])->values()->all(),
        ];
    }
}

