<?php

namespace App\Services;

use App\Models\Order;
use App\Models\StoreNotification;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Membuat riwayat notifikasi pesanan untuk cabang toko (Admin Toko & Kasir)
 * dan mengirimnya sebagai Web/Mobile Push lewat OneSignal.
 *
 * Aturan Jingle & TTS:
 * - COD (Bayar Terima Makanan) / Cancel          -> notif_pembelian.wav  (channel COD)
 * - Pembayaran Tunai (Cash Kasir) / QRIS / Online -> notif_pembayaran.wav (channel Direct)
 * - Penerima Push: SELURUH akun aktif cabang (Admin Toko & Kasir), baik pesanan online maupun kasir.
 * - TTS Rincian Menu: HANYA untuk akun Kasir; akun Admin hanya menerima TTS nominal tanpa rincian menu.
 */
class StoreNotificationService
{
    public const COD_METHOD = 'Bayar Terima Makanan';

    public static function isCod(?string $paymentMethod): bool
    {
        return $paymentMethod === self::COD_METHOD;
    }

    public static function paymentLabel(?string $paymentMethod): string
    {
        if (self::isCod($paymentMethod)) {
            return 'Bayar Setelah Makanan Jadi (COD)';
        }

        if ($paymentMethod === Order::PAYMENT_CASHIER_CASH) {
            return 'Kasir - Tunai (Cash)';
        }

        if ($paymentMethod === Order::PAYMENT_CASHIER_QRIS) {
            return 'Kasir - QRIS Midtrans';
        }

        return $paymentMethod ?: 'Pembayaran Langsung';
    }

    /**
     * Pesanan baru masuk (Online). Hanya COD yang dinotifikasi di sini;
     * metode bayar langsung baru dinotifikasi ketika pembayarannya masuk.
     */
    public function notifyOrderPlaced(Order $order): ?StoreNotification
    {
        if ($order->isCashierOrder() || !self::isCod($order->payment_method)) {
            return null;
        }

        return $this->record($order, StoreNotification::TYPE_COD, 'order_placed');
    }

    /**
     * Pembayaran langsung masuk (Online: lunas / bukti bayar diunggah).
     */
    public function notifyDirectPayment(Order $order): ?StoreNotification
    {
        if ($order->isCashierOrder() || self::isCod($order->payment_method)) {
            return null;
        }

        $event = $order->payment_status === 'paid' ? 'payment_received' : 'payment_review';

        return $this->record($order, StoreNotification::TYPE_DIRECT, $event);
    }

    /**
     * Pembayaran pesanan dari Kasir (POS) lunas (Cash/Tunai klik Lunas atau QRIS Midtrans settled).
     * Dikirim ke SELURUH akun aktif cabang (Admin Toko & Kasir) agar pembayaran masuk terdengar jelas.
     * - Cash (Tunai) -> notif_pembayaran.wav (channel Direct)
     * - QRIS         -> notif_pembayaran.wav (channel Direct)
     * - TTS rincian menu hanya untuk akun Kasir; akun Admin hanya nominal pembayaran berhasil.
     */
    public function notifyCashierPayment(Order $order, ?string $mode = null): ?StoreNotification
    {
        $isCash = $mode === 'cash' || ($mode === null && $order->isCashierCash());
        $event = $isCash ? 'cashier_cash_paid' : 'cashier_qris_paid';

        return $this->record($order, StoreNotification::TYPE_CASHIER, $event, [
            'cashier_payment_mode' => $isCash ? 'cash' : 'qris',
        ]);
    }

    /**
     * Pesanan dibatalkan (oleh pelanggan atau admin).
     */
    public function notifyOrderCancelled(Order $order, string $cancelledBy = 'customer', ?string $reason = null): ?StoreNotification
    {
        return $this->record($order, StoreNotification::TYPE_CANCEL, 'order_cancelled', [
            'cancelled_by' => $cancelledBy,
            'cancellation_reason' => $reason,
        ]);
    }

    protected function record(Order $order, string $type, string $event, array $extraData = []): ?StoreNotification
    {
        try {
            if (StoreNotification::where('order_id', $order->id)->where('type', $type)->where('event', $event)->exists()) {
                return null;
            }

            $order->loadMissing(['client', 'items', 'cashier']);
            $data = array_merge($this->snapshot($order), $extraData);

            [$title, $body] = $this->compose($type, $event, $data);

            try {
                $notification = StoreNotification::create([
                    'store_id' => $order->store_id,
                    'order_id' => $order->id,
                    'type' => $type,
                    'event' => $event,
                    'title' => $title,
                    'body' => $body,
                    'data' => $data,
                ]);
            } catch (QueryException $e) {
                // Race condition: notifikasi yang sama sudah dibuat proses lain
                return null;
            }

            // Kirim push setelah respons HTTP selesai agar checkout pelanggan tidak melambat
            $id = $notification->id;
            app()->terminating(function () use ($id) {
                $this->sendPush($id);
            });

            return $notification;
        } catch (\Throwable $e) {
            // Notifikasi tidak boleh menggagalkan proses pesanan / pembayaran
            Log::error('StoreNotification gagal dibuat: ' . $e->getMessage(), ['order_id' => $order->id]);
            return null;
        }
    }

    protected function snapshot(Order $order): array
    {
        $items = $order->items->map(function ($item) {
            $options = collect($item->selected_options ?? [])
                ->map(fn ($o) => is_array($o) ? ($o['name'] ?? null) : (string) $o)
                ->filter()
                ->values()
                ->all();

            return [
                'name' => $item->menu_name,
                'quantity' => (int) $item->quantity,
                'price' => (float) $item->price,
                'subtotal' => (float) $item->subtotal,
                'options' => $options,
                'notes' => $item->special_notes,
            ];
        })->values()->all();

        $itemsTtsSummary = collect($items)
            ->map(fn ($i) => ((int) $i['quantity']) . ' ' . trim((string) $i['name']))
            ->filter()
            ->implode(', ');
        if (mb_strlen($itemsTtsSummary) > 220) {
            $itemsTtsSummary = mb_substr($itemsTtsSummary, 0, 217) . '...';
        }

        $isCashier = $order->isCashierOrder();

        return [
            'order_number' => $order->order_number,
            'order_source' => $isCashier ? Order::SOURCE_CASHIER : ($order->order_source ?? Order::SOURCE_ONLINE),
            'is_cashier' => $isCashier,
            'cashier_name' => $order->cashier?->name,
            'customer_name' => $isCashier
                ? ('Kasir' . ($order->cashier?->name ? ' · ' . $order->cashier->name : ''))
                : ($order->client?->name ?? 'Pelanggan'),
            'customer_whatsapp' => $isCashier ? null : $order->client?->whatsapp,
            'items' => $items,
            'items_tts_summary' => $itemsTtsSummary,
            'subtotal' => (float) $order->subtotal,
            'discount' => (float) $order->discount_amount,
            'shipping_cost' => (float) ($order->shipping_cost ?? 0),
            'fulfillment_type' => $order->fulfillment_type ?? 'pickup',
            'shipping_courier' => $order->shipping_courier,
            'shipping_service_name' => $order->shipping_service_name,
            'shipping_address' => $order->shipping_address,
            'ka_awb' => $order->ka_awb,
            'total' => (float) $order->total_amount,
            'payment_method' => $order->payment_method,
            'payment_label' => self::paymentLabel($order->payment_method),
            'payment_status' => $order->payment_status,
            'order_status' => $order->status,
            'notes' => $order->notes,
            'cancelled_by' => $order->cancelled_by,
            'cancellation_reason' => $order->cancellation_reason,
            'cancellation_remark' => $order->cancellation_remark,
            'refund_status' => $order->refund_status,
            'refund_email' => $order->refund_email,
            'refund_bank_name' => $order->refund_bank_name,
            'refund_bank_account_number' => $order->refund_bank_account_number,
            'refund_bank_account_name' => $order->refund_bank_account_name,
        ];
    }

    protected function compose(string $type, string $event, array $data): array
    {
        $summary = collect($data['items'])
            ->map(fn ($i) => $i['quantity'] . 'x ' . $i['name'])
            ->implode(', ');
        $total = 'Rp ' . number_format($data['total'], 0, ',', '.');
        $deliveryTag = ($data['fulfillment_type'] ?? 'pickup') === 'delivery'
            ? ' | Kirim ke Rumah (' . strtoupper((string) ($data['shipping_courier'] ?? 'Kurir')) . ' - Rp ' . number_format((float) ($data['shipping_cost'] ?? 0), 0, ',', '.') . ')'
            : '';

        if ($type === StoreNotification::TYPE_CANCEL || $event === 'order_cancelled') {
            $actor = ($data['cancelled_by'] ?? 'customer') === 'customer' ? 'Pelanggan' : 'Admin';
            $title = "Pesanan Dibatalkan ({$actor}) - " . $data['customer_name'];
            $reasonStr = !empty($data['cancellation_reason']) ? " | Alasan: " . $data['cancellation_reason'] : "";
            $body = "Pesanan #{$data['order_number']} telah dibatalkan ({$summary})\nTotal {$total}{$reasonStr}";
        } elseif ($type === StoreNotification::TYPE_CASHIER) {
            $cashierBy = !empty($data['cashier_name']) ? $data['cashier_name'] : 'Kasir POS';
            if ($event === 'cashier_cash_paid') {
                $title = "Transaksi Kasir (Cash) Lunas - {$cashierBy}";
                $body = "{$summary}\nTotal {$total} - Tunai (Cash) diterima kasir";
            } else {
                $title = "Transaksi Kasir (QRIS) Lunas - {$cashierBy}";
                $body = "{$summary}\nTotal {$total} - QRIS Midtrans Lunas";
            }
        } elseif ($type === StoreNotification::TYPE_COD) {
            $title = 'Pesanan Baru (COD) - ' . $data['customer_name'];
            $body = "{$summary}\nTotal {$total} - Bayar setelah makanan jadi{$deliveryTag}";
        } elseif ($event === 'payment_review') {
            $title = 'Bukti Bayar Masuk - ' . $data['customer_name'];
            $body = "{$summary}\nTotal {$total} - {$data['payment_method']} (menunggu verifikasi){$deliveryTag}";
        } else {
            $title = 'Pembayaran Diterima - ' . $data['customer_name'];
            $body = "{$summary}\nTotal {$total} - {$data['payment_method']}{$deliveryTag}";
        }

        return [$title, $body];
    }

    /**
     * Kirim Web/Mobile Push ke SELURUH akun aktif cabang (Admin Toko & Kasir)
     * lewat external_id OneSignal.
     *
     * Aturan Jingle & TTS:
     * - COD / Cancel                                 -> naumil_cod_v2    (notif_pembelian.wav)
     * - Pembayaran Tunai (Cash) / QRIS / Bayar Online -> naumil_direct_v2 (notif_pembayaran.wav)
     * - Admin Toko (`store_admin`) -> TANPA TTS rincian menu (`include_items_tts = false`)
     * - Kasir (`cashier`)          -> DENGAN TTS rincian menu (`include_items_tts = true`)
     */
    public function sendPush(int $notificationId): void
    {
        $notification = StoreNotification::find($notificationId);
        if (!$notification) {
            return;
        }

        $appId = config('onesignal.app_id');
        $apiKey = config('onesignal.rest_api_key');

        if (!$appId || !$apiKey) {
            $notification->update([
                'push_status' => 'skipped',
                'push_response' => 'ONESIGNAL_APP_ID / ONESIGNAL_REST_API_KEY belum diisi.',
            ]);
            return;
        }

        $prefix = (string) config('onesignal.external_id_prefix');
        // Kirim ke seluruh akun aktif cabang: Admin Toko MAUPUN Kasir
        $storeUsers = User::whereIn('role', [User::ROLE_STORE_ADMIN, User::ROLE_CASHIER])
            ->where('store_id', $notification->store_id)
            ->get()
            ->filter(fn (User $u) => $u->isActiveAccount());

        $adminExternalIds = $storeUsers
            ->filter(fn (User $u) => $u->isStoreAdmin())
            ->pluck('id')
            ->map(fn ($id) => $prefix . $id)
            ->values()
            ->all();

        $cashierExternalIds = $storeUsers
            ->filter(fn (User $u) => $u->isCashier())
            ->pluck('id')
            ->map(fn ($id) => $prefix . $id)
            ->values()
            ->all();

        if (empty($adminExternalIds) && empty($cashierExternalIds)) {
            $notification->update([
                'push_status' => 'skipped',
                'push_response' => 'Tidak ada admin toko atau kasir aktif pada cabang ini.',
            ]);
            return;
        }

        $slug = $notification->store?->slug;
        $url = route('store.backend.notification', ['store_slug' => $slug]);

        $isCashierNotif = $notification->type === StoreNotification::TYPE_CASHIER;
        // HANYA COD & Pembatalan yang memakai notif_pembelian.wav (channel COD).
        // Pembayaran Tunai (Cash Kasir), QRIS Kasir, dan Pembayaran Online semuanya memakai notif_pembayaran.wav (channel Direct).
        $useCodChannel = in_array($notification->type, [StoreNotification::TYPE_COD, StoreNotification::TYPE_CANCEL], true);
        $androidChannelId = $useCodChannel
            ? config('onesignal.android_channel_cod')
            : config('onesignal.android_channel_direct');
        $androidSound = $useCodChannel ? 'notif_pembelian' : 'notif_pembayaran';

        // Rincian menu TTS disiapkan dari snapshot untuk dikirim HANYA ke akun Kasir
        $itemsTtsSummary = (string) ($notification->data['items_tts_summary'] ?? '');
        if ($itemsTtsSummary === '' && !empty($notification->data['items'])) {
            $itemsTtsSummary = collect($notification->data['items'])
                ->map(fn ($i) => ((int) ($i['quantity'] ?? 1)) . ' ' . trim((string) ($i['name'] ?? '')))
                ->filter()
                ->implode(', ');
            if (mb_strlen($itemsTtsSummary) > 220) {
                $itemsTtsSummary = mb_substr($itemsTtsSummary, 0, 217) . '...';
            }
        }

        $baseData = [
            'notification_id' => $notification->id,
            'type' => $notification->type,
            'order_id' => $notification->order_id,
            'event' => $notification->event,
            'order_source' => $isCashierNotif ? Order::SOURCE_CASHIER : ($notification->data['order_source'] ?? Order::SOURCE_ONLINE),
            'is_cashier' => $isCashierNotif,
            'cashier_name' => (string) ($notification->data['cashier_name'] ?? ''),
            'payment_status' => $notification->data['payment_status'] ?? '',
            'total' => (float) ($notification->data['total'] ?? 0),
            'customer_name' => $notification->data['customer_name'] ?? '',
            'order_number' => $notification->data['order_number'] ?? '',
            'cancelled_by' => $notification->data['cancelled_by'] ?? '',
            'cancellation_reason' => $notification->data['cancellation_reason'] ?? '',
        ];

        $batches = [];
        if (!empty($adminExternalIds)) {
            $batches['admin'] = [
                'external_ids' => $adminExternalIds,
                'target_role' => User::ROLE_STORE_ADMIN,
                'include_items_tts' => false,
                'items_tts_summary' => '',
            ];
        }
        if (!empty($cashierExternalIds)) {
            $batches['cashier'] = [
                'external_ids' => $cashierExternalIds,
                'target_role' => User::ROLE_CASHIER,
                'include_items_tts' => true,
                'items_tts_summary' => $itemsTtsSummary,
            ];
        }

        $anySent = false;
        $responses = [];

        foreach ($batches as $groupKey => $batch) {
            try {
                $response = Http::withHeaders(['Authorization' => 'Key ' . $apiKey])
                    ->acceptJson()
                    ->asJson()
                    ->timeout(8)
                    ->post(config('onesignal.api_url'), [
                        'app_id' => $appId,
                        'target_channel' => 'push',
                        'include_aliases' => ['external_id' => $batch['external_ids']],
                        'headings' => ['en' => $notification->title, 'id' => $notification->title],
                        'contents' => ['en' => $notification->body, 'id' => $notification->body],
                        // web_url: hanya untuk Web Push. Di aplikasi mobile, klik notifikasi ditangani
                        // oleh aplikasi (membuka halaman Notifikasi / Pesanan) lewat additionalData.
                        'web_url' => $url,
                        'web_push_topic' => 'order-' . $notification->order_id,
                        'chrome_web_icon' => asset('favicon-192x192.png'),
                        // Android channel native:
                        // - COD / Cancel -> naumil_cod_v2 (notif_pembelian.wav)
                        // - Pembayaran Tunai (Cash) / QRIS / Online Direct -> naumil_direct_v2 (notif_pembayaran.wav)
                        'existing_android_channel_id' => $androidChannelId,
                        'android_sound' => $androidSound,
                        'priority' => 10,
                        'data' => array_merge($baseData, [
                            'target_role' => $batch['target_role'],
                            'include_items_tts' => $batch['include_items_tts'],
                            'items_tts_summary' => $batch['items_tts_summary'],
                        ]),
                    ]);

                $resJson = $response->json();
                $hasErrors = !empty($resJson['errors']);
                $isSent = $response->successful() && !$hasErrors;
                if ($isSent) {
                    $anySent = true;
                }

                $responses[$groupKey] = [
                    'status' => $isSent ? 'sent' : 'failed',
                    'http' => $response->status(),
                    'body' => $resJson ?? mb_substr($response->body(), 0, 500),
                ];
            } catch (\Throwable $e) {
                Log::warning("OneSignal push ({$groupKey}) gagal: " . $e->getMessage());
                $responses[$groupKey] = [
                    'status' => 'failed',
                    'error' => mb_substr($e->getMessage(), 0, 500),
                ];
            }
        }

        $notification->update([
            'push_status' => $anySent ? 'sent' : 'failed',
            'push_response' => mb_substr(json_encode($responses, JSON_UNESCAPED_UNICODE) ?: '', 0, 2000),
        ]);
    }
}

