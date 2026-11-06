<?php

namespace App\Services;

use App\Models\Order;
use App\Models\StoreNotification;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Membuat riwayat notifikasi pesanan untuk admin backend toko
 * dan mengirimnya sebagai Web Push lewat OneSignal.
 *
 * - COD (Bayar Terima Makanan)      -> notifikasi saat pesanan masuk  (suara: notif-pembelian.wav)
 * - Bayar langsung (QRIS / Gateway) -> notifikasi saat pembayaran masuk (suara: notif-pembayaran.wav)
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

        return $paymentMethod ?: 'Pembayaran Langsung';
    }

    /**
     * Pesanan baru masuk. Hanya COD yang dinotifikasi di sini;
     * metode bayar langsung baru dinotifikasi ketika pembayarannya masuk.
     */
    public function notifyOrderPlaced(Order $order): ?StoreNotification
    {
        if (!self::isCod($order->payment_method)) {
            return null;
        }

        return $this->record($order, StoreNotification::TYPE_COD, 'order_placed');
    }

    /**
     * Pembayaran langsung masuk (lunas / bukti bayar diunggah).
     */
    public function notifyDirectPayment(Order $order): ?StoreNotification
    {
        if (self::isCod($order->payment_method)) {
            return null;
        }

        $event = $order->payment_status === 'paid' ? 'payment_received' : 'payment_review';

        return $this->record($order, StoreNotification::TYPE_DIRECT, $event);
    }

    protected function record(Order $order, string $type, string $event): ?StoreNotification
    {
        try {
            if (StoreNotification::where('order_id', $order->id)->where('type', $type)->exists()) {
                return null;
            }

            $order->loadMissing(['client', 'items']);
            $data = $this->snapshot($order);

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

        return [
            'order_number' => $order->order_number,
            'customer_name' => $order->client?->name ?? 'Pelanggan',
            'customer_whatsapp' => $order->client?->whatsapp,
            'items' => $items,
            'subtotal' => (float) $order->subtotal,
            'discount' => (float) $order->discount_amount,
            'total' => (float) $order->total_amount,
            'payment_method' => $order->payment_method,
            'payment_label' => self::paymentLabel($order->payment_method),
            'payment_status' => $order->payment_status,
            'order_status' => $order->status,
            'notes' => $order->notes,
        ];
    }

    protected function compose(string $type, string $event, array $data): array
    {
        $summary = collect($data['items'])
            ->map(fn ($i) => $i['quantity'] . 'x ' . $i['name'])
            ->implode(', ');
        $total = 'Rp ' . number_format($data['total'], 0, ',', '.');

        if ($type === StoreNotification::TYPE_COD) {
            $title = 'Pesanan Baru (COD) - ' . $data['customer_name'];
            $body = "{$summary}\nTotal {$total} - Bayar setelah makanan jadi";
        } elseif ($event === 'payment_review') {
            $title = 'Bukti Bayar Masuk - ' . $data['customer_name'];
            $body = "{$summary}\nTotal {$total} - {$data['payment_method']} (menunggu verifikasi)";
        } else {
            $title = 'Pembayaran Diterima - ' . $data['customer_name'];
            $body = "{$summary}\nTotal {$total} - {$data['payment_method']}";
        }

        return [$title, $body];
    }

    /**
     * Kirim Web Push ke seluruh admin toko (yang sedang login di OneSignal)
     * lewat external_id.
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
        $externalIds = User::where(function ($q) {
                $q->where('role', User::ROLE_STORE_ADMIN)
                  ->orWhere('role', 'store_admin');
            })
            ->where('store_id', $notification->store_id)
            ->pluck('id')
            ->map(fn ($id) => $prefix . $id)
            ->all();

        if (empty($externalIds)) {
            $notification->update(['push_status' => 'skipped', 'push_response' => 'Tidak ada admin toko.']);
            return;
        }

        $slug = $notification->store?->slug;
        $url = route('store.backend.notification', ['store_slug' => $slug]);

        try {
            $response = Http::withHeaders(['Authorization' => 'Key ' . $apiKey])
                ->acceptJson()
                ->asJson()
                ->timeout(8)
                ->post(config('onesignal.api_url'), [
                    'app_id' => $appId,
                    'target_channel' => 'push',
                    'include_aliases' => ['external_id' => $externalIds],
                    'headings' => ['en' => $notification->title, 'id' => $notification->title],
                    'contents' => ['en' => $notification->body, 'id' => $notification->body],
                    // web_url: hanya untuk Web Push. Di aplikasi mobile, klik notifikasi ditangani
                    // oleh aplikasi (membuka halaman Notifikasi) lewat additionalData, bukan membuka browser.
                    'web_url' => $url,
                    'web_push_topic' => 'order-' . $notification->order_id,
                    'chrome_web_icon' => asset('favicon-192x192.png'),
                    // Android: channel native dibuat oleh aplikasi (naumil_rice_apps) dengan suara kustom
                    // sehingga suara tetap berbunyi walau aplikasi tertutup. COD -> notif-pembelian, langsung -> notif-pembayaran
                    'existing_android_channel_id' => $notification->type === StoreNotification::TYPE_COD
                        ? config('onesignal.android_channel_cod')
                        : config('onesignal.android_channel_direct'),
                    'priority' => 10,
                    'data' => [
                        'notification_id' => $notification->id,
                        'type' => $notification->type,
                        'order_id' => $notification->order_id,
                    ],
                ]);

            $resJson = $response->json();
            $hasErrors = !empty($resJson['errors']);
            $isSent = $response->successful() && !$hasErrors;

            $notification->update([
                'push_status' => $isSent ? 'sent' : 'failed',
                'push_response' => mb_substr($response->body(), 0, 2000),
            ]);
        } catch (\Throwable $e) {
            Log::warning('OneSignal push gagal: ' . $e->getMessage());
            $notification->update(['push_status' => 'failed', 'push_response' => mb_substr($e->getMessage(), 0, 2000)]);
        }
    }
}

