<?php

namespace App\Services;

use App\Models\Store;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MidtransPaymentService
{
    public const SNAP_SANDBOX_URL = 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    public const SNAP_PRODUCTION_URL = 'https://app.midtrans.com/snap/v1/transactions';

    public const API_SANDBOX_URL = 'https://api.sandbox.midtrans.com/v2';
    public const API_PRODUCTION_URL = 'https://api.midtrans.com/v2';

    public const SNAP_JS_SANDBOX = 'https://app.sandbox.midtrans.com/snap/snap.js';
    public const SNAP_JS_PRODUCTION = 'https://app.midtrans.com/snap/snap.js';

    public const SIMULATOR_URL = 'https://simulator.sandbox.midtrans.com/';

    /**
     * Get Snap API URL based on environment
     */
    public static function getSnapUrl(string $environment = 'sandbox'): string
    {
        return strtolower($environment) === 'production'
            ? self::SNAP_PRODUCTION_URL
            : self::SNAP_SANDBOX_URL;
    }

    /**
     * Get Core API base URL based on environment
     */
    public static function getApiUrl(string $environment = 'sandbox'): string
    {
        return strtolower($environment) === 'production'
            ? self::API_PRODUCTION_URL
            : self::API_SANDBOX_URL;
    }

    /**
     * Get Snap.js library URL based on environment
     */
    public static function getSnapJsUrl(string $environment = 'sandbox'): string
    {
        return strtolower($environment) === 'production'
            ? self::SNAP_JS_PRODUCTION
            : self::SNAP_JS_SANDBOX;
    }

    /**
     * Create Midtrans Snap Transaction for an Order
     */
    public function createSnapTransaction(Store $store, Order $order): array
    {
        $environment = $store->active_midtrans_environment;
        $serverKey = trim($store->active_midtrans_server_key ?? '');
        $clientKey = trim($store->active_midtrans_client_key ?? '');

        if (empty($serverKey)) {
            return [
                'success' => false,
                'message' => 'Server Key Midtrans (' . strtoupper($environment) . ') belum dikonfigurasi pada cabang ini.',
            ];
        }

        $grossAmount = (int) round($order->total_amount);
        if ($grossAmount <= 0) {
            return [
                'success' => false,
                'message' => 'Total pembayaran tidak valid (Rp 0).',
            ];
        }

        $client = $order->client;
        $fullName = trim($client->name ?? 'Pelanggan Naumil Store');
        $nameParts = explode(' ', $fullName, 2);
        $firstName = mb_substr($nameParts[0], 0, 45);
        $lastName = isset($nameParts[1]) ? mb_substr($nameParts[1], 0, 45) : '';

        $email = !empty($client->email) ? $client->email : ($client->whatsapp . '@naumilrice.com');
        $phone = preg_replace('/[^0-9]/', '', $client->whatsapp ?? '');
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        // Build item details
        $itemDetails = [];
        if ($order->relationLoaded('items') && $order->items->count() > 0) {
            foreach ($order->items as $item) {
                $menuName = $item->menu->name ?? ('Item #' . $item->menu_id);
                $unitPrice = (int) round($item->price);
                $qty = (int) max(1, $item->quantity);

                $itemDetails[] = [
                    'id' => (string) mb_substr('MENU-' . $item->menu_id, 0, 45),
                    'price' => $unitPrice,
                    'quantity' => $qty,
                    'name' => (string) mb_substr($menuName, 0, 50),
                ];
            }
        }

        // Fallback item if no items loaded
        if (empty($itemDetails)) {
            $itemDetails[] = [
                'id' => (string) mb_substr('ORDER-' . $order->order_number, 0, 45),
                'price' => $grossAmount,
                'quantity' => 1,
                'name' => (string) mb_substr("Pesanan #{$order->order_number}", 0, 50),
            ];
        }

        // Midtrans Requirement: sum(price * quantity) MUST strictly equal gross_amount!
        $calculatedSum = 0;
        foreach ($itemDetails as $it) {
            $calculatedSum += ((int) $it['price'] * (int) $it['quantity']);
        }

        $diff = $grossAmount - $calculatedSum;
        if ($diff !== 0) {
            if ($diff < 0) {
                // Discount / Promo deduction
                $itemDetails[] = [
                    'id' => 'PROMO-DISC',
                    'price' => $diff,
                    'quantity' => 1,
                    'name' => 'Diskon / Potongan Promo',
                ];
            } else {
                // Adjustment / Delivery fee / Additional charge
                $itemDetails[] = [
                    'id' => 'ADJUSTMENT',
                    'price' => $diff,
                    'quantity' => 1,
                    'name' => 'Penyesuaian Biaya / Diskon',
                ];
            }
        }

        $returnUrl = url('/payment/midtrans/return?order=' . urlencode($order->order_number));

        $payload = [
            'transaction_details' => [
                'order_id' => $order->order_number,
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => [
                'first_name' => $firstName ?: 'Pelanggan',
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone ?: '628120000000',
                'billing_address' => [
                    'first_name' => $firstName ?: 'Pelanggan',
                    'last_name' => $lastName,
                    'address' => mb_substr($client->address ?? $store->address ?? 'Indonesia', 0, 100),
                ],
            ],
            'item_details' => $itemDetails,
            'callbacks' => [
                'finish' => $returnUrl,
            ],
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'duration' => 24,
                'unit' => 'hours',
            ],
        ];

        $snapUrl = self::getSnapUrl($environment);

        try {
            $response = Http::timeout(15)
                ->withBasicAuth($serverKey, '')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($snapUrl, $payload);

            if ($response->successful()) {
                $json = $response->json();
                $snapToken = $json['token'] ?? null;
                $redirectUrl = $json['redirect_url'] ?? null;

                if ($snapToken) {
                    $order->update([
                        'snap_token' => $snapToken,
                        'payment_url' => $redirectUrl,
                        'payment_id' => $order->order_number,
                        'payment_gateway_provider' => Store::GATEWAY_PROVIDER_MIDTRANS,
                        'payment_payload' => array_merge($json, [
                            'snap_token' => $snapToken,
                            'redirect_url' => $redirectUrl,
                            'environment' => $environment,
                            'created_at' => now()->toIso8601String(),
                        ]),
                    ]);

                    return [
                        'success' => true,
                        'snap_token' => $snapToken,
                        'payment_url' => $redirectUrl,
                        'client_key' => $clientKey,
                        'environment' => $environment,
                        'data' => $json,
                    ];
                }
            }

            $rawMsg = $response->json('error_messages') ?? $response->json('message') ?? $response->body();
            if (is_array($rawMsg)) {
                $rawMsg = implode(', ', $rawMsg);
            }

            Log::warning('Midtrans Snap Transaction Creation Failed', [
                'status' => $response->status(),
                'response' => $response->body(),
                'order' => $order->order_number,
                'environment' => $environment,
            ]);

            return [
                'success' => false,
                'message' => 'Gagal membuat transaksi Midtrans: ' . $rawMsg,
                'status_code' => $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('Midtrans Snap Exception: ' . $e->getMessage(), [
                'order' => $order->order_number,
                'environment' => $environment,
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kendala koneksi ke server Midtrans: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check transaction status with Midtrans Core API
     */
    public function checkPaymentStatus(Store $store, Order $order): array
    {
        $environment = $store->active_midtrans_environment;
        $serverKey = trim($store->active_midtrans_server_key ?? '');

        if (empty($serverKey)) {
            return [
                'success' => false,
                'is_paid' => false,
                'message' => 'Server Key Midtrans belum dikonfigurasi.',
            ];
        }

        $orderId = $order->order_number;
        $apiUrl = self::getApiUrl($environment);
        $endpoint = "{$apiUrl}/{$orderId}/status";

        try {
            $response = Http::timeout(10)
                ->withBasicAuth($serverKey, '')
                ->withHeaders(['Accept' => 'application/json'])
                ->get($endpoint);

            if ($response->successful()) {
                $json = $response->json();
                $transactionStatus = strtolower($json['transaction_status'] ?? '');
                $fraudStatus = strtolower($json['fraud_status'] ?? '');
                $paymentType = $json['payment_type'] ?? 'midtrans';

                $isPaid = false;
                if ($transactionStatus === 'settlement') {
                    $isPaid = true;
                } elseif ($transactionStatus === 'capture') {
                    $isPaid = ($fraudStatus === 'accept');
                }

                return [
                    'success' => true,
                    'is_paid' => $isPaid,
                    'transaction_status' => $transactionStatus,
                    'fraud_status' => $fraudStatus,
                    'payment_type' => $paymentType,
                    'data' => $json,
                    'message' => $isPaid ? 'Pembayaran terkonfirmasi lunas.' : "Status transaksi: {$transactionStatus}",
                ];
            }

            return [
                'success' => false,
                'is_paid' => false,
                'status_code' => $response->status(),
                'message' => 'Transaksi belum ditemukan di server Midtrans (' . $response->status() . ').',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'is_paid' => false,
                'message' => 'Gagal menghubungi server Midtrans: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Test Server Key and Client Key connection with Midtrans API
     */
    public function testConnection(string $serverKey, string $clientKey, string $environment = 'sandbox'): array
    {
        $serverKey = trim($serverKey);
        $clientKey = trim($clientKey);
        $environment = strtolower($environment);

        if (empty($serverKey)) {
            return [
                'success' => false,
                'message' => 'Server Key wajib diisi untuk melakukan pengujian koneksi.',
            ];
        }

        // Format check: Warn if keys don't match the environment
        if ($environment === 'sandbox') {
            if (!str_starts_with($serverKey, 'SB-Mid-server-') && !str_starts_with($serverKey, 'Mid-server-')) {
                return [
                    'success' => false,
                    'message' => 'Format Server Key Sandbox tidak sesuai. Server Key Sandbox Midtrans umumnya diawali dengan "SB-Mid-server-" atau "Mid-server-".',
                ];
            }
        } else {
            if (str_starts_with($serverKey, 'SB-')) {
                return [
                    'success' => false,
                    'message' => 'Kunci tertukar: Server Key diawali "SB-" yang merupakan kunci Sandbox, sedangkan mode yang dipilih adalah Production.',
                ];
            }
            if (!str_starts_with($serverKey, 'Mid-server-')) {
                return [
                    'success' => false,
                    'message' => 'Format Server Key Production tidak sesuai. Server Key Production Midtrans diawali dengan "Mid-server-".',
                ];
            }
        }

        // Test with Midtrans API ping
        $apiUrl = self::getApiUrl($environment);
        $endpoint = "{$apiUrl}/ping_test_connection_check/status";

        try {
            $response = Http::timeout(10)
                ->withBasicAuth($serverKey, '')
                ->withHeaders(['Accept' => 'application/json'])
                ->get($endpoint);

            $status = $response->status();

            // HTTP 401 means invalid Server Key
            if ($status === 401) {
                return [
                    'success' => false,
                    'message' => 'Autentikasi Ditolak (401 Unauthorized): Server Key Midtrans (' . strtoupper($environment) . ') tidak valid. Mohon periksa kembali di dashboard Midtrans (Settings -> Access Keys).',
                ];
            }

            // HTTP 404 or 200 means Basic Auth succeeded! (404 is because dummy order doesn't exist)
            if ($status === 404 || $status === 200) {
                return [
                    'success' => true,
                    'message' => 'Koneksi Berhasil! Server Key Midtrans (' . strtoupper($environment) . ') valid dan siap digunakan.',
                    'environment' => $environment,
                ];
            }

            return [
                'success' => true,
                'message' => 'Koneksi terhubung ke Midtrans (' . strtoupper($environment) . '), status respons: ' . $status,
                'environment' => $environment,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Gagal menghubungi server Midtrans: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Create a simulated test Snap transaction for testing & demonstration
     */
    public function createTestTransaction(Store $store, int $amount = 10000, string $environment = 'sandbox'): array
    {
        $serverKey = trim(($environment === 'production' ? $store->midtrans_server_key_production : $store->midtrans_server_key) ?? '');
        $clientKey = trim(($environment === 'production' ? $store->midtrans_client_key_production : $store->midtrans_client_key) ?? '');

        if (empty($serverKey) || empty($clientKey)) {
            return [
                'success' => false,
                'message' => 'Server Key dan Client Key ' . strtoupper($environment) . ' harus diisi sebelum memulai uji coba.',
            ];
        }

        $testOrderId = 'TEST-' . ($store->code ?: 'PO') . '-' . strtoupper(Str::random(6));
        $snapUrl = self::getSnapUrl($environment);

        $payload = [
            'transaction_details' => [
                'order_id' => $testOrderId,
                'gross_amount' => $amount,
            ],
            'customer_details' => [
                'first_name' => 'Uji Coba Admin',
                'last_name' => 'Naumil',
                'email' => 'test-admin@naumilrice.com',
                'phone' => '6281234567890',
            ],
            'item_details' => [
                [
                    'id' => 'TEST-001',
                    'price' => $amount,
                    'quantity' => 1,
                    'name' => 'Uji Coba Pembayaran Midtrans',
                ]
            ],
            'callbacks' => [
                'finish' => url('/payment/midtrans/return?order=' . $testOrderId),
            ],
        ];

        try {
            $response = Http::timeout(15)
                ->withBasicAuth($serverKey, '')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($snapUrl, $payload);

            if ($response->successful()) {
                $json = $response->json();
                return [
                    'success' => true,
                    'order_id' => $testOrderId,
                    'amount' => $amount,
                    'snap_token' => $json['token'] ?? null,
                    'payment_url' => $json['redirect_url'] ?? null,
                    'client_key' => $clientKey,
                    'environment' => $environment,
                    'simulator_url' => self::SIMULATOR_URL,
                    'message' => 'Transaksi uji coba berhasil dibuat!',
                ];
            }

            return [
                'success' => false,
                'message' => 'Gagal membuat transaksi uji coba: ' . ($response->json('message') ?? $response->body()),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Kendala koneksi: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check status of a test transaction
     */
    public function checkTestTransactionStatus(Store $store, string $orderId, string $environment = 'sandbox'): array
    {
        $serverKey = trim(($environment === 'production' ? $store->midtrans_server_key_production : $store->midtrans_server_key) ?? '');
        $apiUrl = self::getApiUrl($environment);
        $endpoint = "{$apiUrl}/{$orderId}/status";

        try {
            $response = Http::timeout(10)
                ->withBasicAuth($serverKey, '')
                ->withHeaders(['Accept' => 'application/json'])
                ->get($endpoint);

            if ($response->successful()) {
                $json = $response->json();
                $status = strtolower($json['transaction_status'] ?? 'unknown');
                $isPaid = in_array($status, ['settlement', 'capture']);

                return [
                    'success' => true,
                    'is_paid' => $isPaid,
                    'transaction_status' => $status,
                    'payment_type' => $json['payment_type'] ?? '-',
                    'fraud_status' => $json['fraud_status'] ?? '-',
                    'gross_amount' => $json['gross_amount'] ?? '0',
                    'message' => $isPaid ? 'Pembayaran uji coba terkonfirmasi LUNAS (Settlement)!' : "Status: {$status}",
                    'data' => $json,
                ];
            }

            return [
                'success' => false,
                'message' => 'Transaksi belum ditemukan di server Midtrans (' . $response->status() . ').',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Gagal menghubungi server Midtrans: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify SHA512 Signature Key from Midtrans Webhook
     */
    public function verifySignature(array $payload, string $serverKey): bool
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $incomingSignature = (string) ($payload['signature_key'] ?? '');

        if (empty($incomingSignature) || empty($orderId) || empty($statusCode) || empty($grossAmount) || empty($serverKey)) {
            return false;
        }

        $calculated = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        return hash_equals($calculated, $incomingSignature);
    }
}

