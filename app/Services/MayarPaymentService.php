<?php

namespace App\Services;

use App\Models\Store;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MayarPaymentService
{
    public const SANDBOX_URL = 'https://api.mayar.io/hl/v1';
    public const PRODUCTION_URL = 'https://api.mayar.id/hl/v1';

    /**
     * Get Base API URL based on store environment
     */
    public static function getBaseUrl(string $environment = 'sandbox'): string
    {
        return strtolower($environment) === 'production' 
            ? self::PRODUCTION_URL 
            : self::SANDBOX_URL;
    }

    /**
     * Create Mayar Payment Link / Invoice for an Order
     */
    public function createPaymentLink(Store $store, Order $order): array
    {
        $apiKey = trim($store->mayar_api_key ?? '');

        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'API Key Mayar.id belum dikonfigurasi pada cabang ini.',
            ];
        }

        $baseUrl = self::getBaseUrl($store->mayar_environment ?? 'sandbox');
        $endpoint = "{$baseUrl}/payment/create";

        $client = $order->client;
        $customerName = $client->name ?? 'Pelanggan Naumil Rice';
        $customerEmail = !empty($client->email) ? $client->email : ($client->whatsapp . '@naumilrice.com');
        $customerMobile = preg_replace('/[^0-9]/', '', $client->whatsapp ?? '');
        if (str_starts_with($customerMobile, '0')) {
            $customerMobile = '62' . substr($customerMobile, 1);
        }

        $payload = [
            'name' => $customerName,
            'email' => $customerEmail,
            'mobile' => $customerMobile ?: '628120000000',
            'amount' => (int) round($order->total_amount),
            'description' => "Pesanan #{$order->order_number} ({$store->name})",
            'redirectUrl' => url('/payment/mayar/return?order=' . $order->order_number),
            'expiredAt' => now()->addHours(24)->toIso8601String(),
        ];

        try {
            $response = Http::timeout(15)
                ->withToken($apiKey)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, $payload);

            if ($response->successful()) {
                $json = $response->json();
                $data = $json['data'] ?? [];
                
                // Mayar link is usually in data.link or data.paymentUrl or data.url
                $paymentUrl = $data['link'] ?? $data['paymentUrl'] ?? $data['url'] ?? null;
                $paymentId = $data['id'] ?? $data['transactionId'] ?? null;

                if ($paymentUrl) {
                    $order->update([
                        'payment_url' => $paymentUrl,
                        'payment_id' => $paymentId,
                        'payment_payload' => $json,
                    ]);

                    return [
                        'success' => true,
                        'payment_url' => $paymentUrl,
                        'payment_id' => $paymentId,
                        'data' => $json,
                    ];
                }
            }

            $rawMsg = $response->json('messages') ?? $response->json('message') ?? $response->body();
            if (str_contains(strtolower($rawMsg), 'scope not allowed to write')) {
                $friendlyMsg = 'API Key Mayar tidak memiliki izin WRITE. Harap buat API Key baru di dashboard Mayar.id dan centang izin WRITE / Full Access.';
            } else {
                $friendlyMsg = 'Gagal membuat link pembayaran Mayar: ' . $rawMsg;
            }

            Log::warning('Mayar Payment Link Failed', [
                'status' => $response->status(),
                'response' => $response->body(),
                'order' => $order->order_number,
            ]);

            return [
                'success' => false,
                'message' => $friendlyMsg,
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Mayar API Exception: ' . $e->getMessage(), [
                'order' => $order->order_number,
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kendala koneksi ke server Mayar: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Test API connection with given credentials
     */
    public function testConnection(string $apiKey, string $environment = 'sandbox'): array
    {
        $apiKey = trim($apiKey);
        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'API Key tidak boleh kosong.',
            ];
        }

        // Check JWT payload scope if token is JWT
        $parts = explode('.', $apiKey);
        if (count($parts) === 3) {
            $jwtPayload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            if (is_array($jwtPayload) && isset($jwtPayload['scope'])) {
                $scope = (array) $jwtPayload['scope'];
                $hasWrite = (!empty($scope['write']) && $scope['write'] !== false)
                    || (!empty($scope['full']) && $scope['full'] !== false)
                    || in_array('write', array_keys($scope), true)
                    || in_array('write', $scope, true);

                if (!$hasWrite) {
                    return [
                        'success' => false,
                        'message' => 'API Key terhubung, tetapi HANYA memiliki izin READ. Harap buat API Key baru di dashboard Mayar.id dan pastikan mencentang izin WRITE (Create Payment Link).',
                        'scope' => $scope,
                    ];
                }
            }
        }

        $baseUrl = self::getBaseUrl($environment);

        try {
            // Test ping or test dummy minimal request to check token validity
            $response = Http::timeout(10)
                ->withToken($apiKey)
                ->get("{$baseUrl}/balance");

            // Even if balance is 404, check if 401 Unauthorized
            if ($response->status() === 401 || $response->status() === 403) {
                return [
                    'success' => false,
                    'message' => 'Koneksi Ditolak (401/403): API Key Mayar tidak valid atau belum diaktifkan.',
                ];
            }

            return [
                'success' => true,
                'message' => 'API Key Mayar terhubung dengan baik (' . strtoupper($environment) . '). Izin READ/WRITE siap.',
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Gagal menghubungi server Mayar: ' . $e->getMessage(),
            ];
        }
    }
}
