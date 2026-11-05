<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Store;
use App\Models\Order;
use App\Models\Client;
use App\Models\Menu;
use App\Models\Category;
use App\Services\MidtransPaymentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MidtransPaymentGatewayTest extends TestCase
{
    protected Store $store;
    protected User $storeAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::firstOrCreate(
            ['slug' => 'test-midtrans-store'],
            [
                'name' => 'Test Midtrans Store',
                'code' => 'TMS-01',
                'address' => 'Jl. Uji Coba No. 123',
                'phone' => '081234567890',
                'payment_type' => 'payment_gateway',
                'payment_gateway_provider' => 'midtrans',
                'midtrans_environment' => 'sandbox',
                'midtrans_server_key' => 'SB-Mid-server-TESTKEY123456',
                'midtrans_client_key' => 'SB-Mid-client-TESTKEY123456',
                'midtrans_merchant_id' => 'G123456789',
                'midtrans_server_key_production' => 'Mid-server-PRODKEY123456',
                'midtrans_client_key_production' => 'Mid-client-PRODKEY123456',
                'status' => 'active',
            ]
        );

        $this->storeAdmin = User::firstOrCreate(
            ['username' => 'tms_admin'],
            [
                'name' => 'TMS Admin',
                'email' => 'tms_admin@test.com',
                'password' => bcrypt('password123'),
                'role' => User::ROLE_STORE_ADMIN,
                'store_id' => $this->store->id,
            ]
        );
    }

    public function test_midtrans_service_url_and_signature_calculation()
    {
        $service = new MidtransPaymentService();

        // Check URLs
        $this->assertEquals(MidtransPaymentService::SNAP_SANDBOX_URL, MidtransPaymentService::getSnapUrl('sandbox'));
        $this->assertEquals(MidtransPaymentService::SNAP_PRODUCTION_URL, MidtransPaymentService::getSnapUrl('production'));
        $this->assertEquals(MidtransPaymentService::API_SANDBOX_URL, MidtransPaymentService::getApiUrl('sandbox'));
        $this->assertEquals(MidtransPaymentService::API_PRODUCTION_URL, MidtransPaymentService::getApiUrl('production'));

        // Check signature calculation
        $orderId = 'ORDER-TEST-001';
        $statusCode = '200';
        $grossAmount = '50000.00';
        $serverKey = 'SB-Mid-server-TESTKEY123456';

        $expectedSig = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $expectedSig,
        ];

        $this->assertTrue($service->verifySignature($payload, $serverKey));
        $this->assertFalse($service->verifySignature($payload, 'wrong-key'));
    }

    public function test_midtrans_connection_key_format_validation()
    {
        $service = new MidtransPaymentService();

        // Empty server key
        $resEmpty = $service->testConnection('', 'client-key', 'sandbox');
        $this->assertFalse($resEmpty['success']);

        // Sandbox mode with invalid prefix
        $resWrongPrefix = $service->testConnection('INVALID-PREFIX-123', 'client-key', 'sandbox');
        $this->assertFalse($resWrongPrefix['success']);
        $this->assertStringContainsString('SB-Mid-server-', $resWrongPrefix['message']);

        // Production mode with Sandbox key (starts with SB-)
        $resMismatched = $service->testConnection('SB-Mid-server-ABC123456', 'client-key', 'production');
        $this->assertFalse($resMismatched['success']);
        $this->assertStringContainsString('Kunci tertukar', $resMismatched['message']);
    }

    public function test_midtrans_snap_transaction_creation()
    {
        $client = Client::firstOrCreate(
            ['whatsapp' => '081299887766'],
            ['name' => 'Budi Testing', 'email' => 'budi@test.com', 'address' => 'Jakarta']
        );

        $category = Category::firstOrCreate(
            ['store_id' => $this->store->id, 'name' => 'Makanan Utama'],
            ['slug' => 'makanan-utama-' . $this->store->id]
        );

        $menu = Menu::firstOrCreate(
            ['store_id' => $this->store->id, 'name' => 'Nasi Bakar Ayam'],
            ['category_id' => $category->id, 'price' => 25000, 'stock' => 100, 'status' => 'available']
        );

        $order = Order::create([
            'order_number' => 'NR-TEST-' . strtoupper(Str::random(6)),
            'store_id' => $this->store->id,
            'client_id' => $client->id,
            'status' => Order::STATUS_CONFIRMED,
            'payment_method' => 'Payment Gateway (Midtrans)',
            'payment_status' => 'unpaid',
            'subtotal' => 50000,
            'discount_amount' => 0,
            'total_amount' => 50000,
        ]);

        $order->items()->create([
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            'quantity' => 2,
            'price' => 25000,
            'subtotal' => 50000,
        ]);

        // Mock Midtrans Snap API
        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'dummy-snap-token-12345',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/dummy-snap-token-12345',
            ], 201),
        ]);

        $service = new MidtransPaymentService();
        $result = $service->createSnapTransaction($this->store, $order);

        $this->assertTrue($result['success']);
        $this->assertEquals('dummy-snap-token-12345', $result['snap_token']);
        $this->assertEquals('https://app.sandbox.midtrans.com/snap/v2/vtweb/dummy-snap-token-12345', $result['payment_url']);

        $order->refresh();
        $this->assertEquals('dummy-snap-token-12345', $order->snap_token);
        $this->assertEquals('midtrans', $order->payment_gateway_provider);
    }

    public function test_midtrans_webhook_marks_order_as_paid()
    {
        $client = Client::firstOrCreate(
            ['whatsapp' => '081299887755'],
            ['name' => 'Siti Testing', 'email' => 'siti@test.com', 'address' => 'Jakarta']
        );

        $order = Order::create([
            'order_number' => 'NR-HOOK-' . strtoupper(Str::random(6)),
            'store_id' => $this->store->id,
            'client_id' => $client->id,
            'status' => Order::STATUS_CONFIRMED,
            'payment_method' => 'Payment Gateway (Midtrans)',
            'payment_status' => 'unpaid',
            'subtotal' => 35000,
            'discount_amount' => 0,
            'total_amount' => 35000,
            'payment_gateway_provider' => 'midtrans',
        ]);

        $grossAmount = '35000.00';
        $serverKey = $this->store->midtrans_server_key;
        $signature = hash('sha512', $order->order_number . '200' . $grossAmount . $serverKey);

        $webhookPayload = [
            'order_id' => $order->order_number,
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'signature_key' => $signature,
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
            'fraud_status' => 'accept',
        ];

        $response = $this->postJson("/api/v1/payment/midtrans/notification/{$this->store->slug}", $webhookPayload);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'payment_status' => 'paid',
        ]);

        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals(Order::STATUS_PROCESSING, $order->status);

        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'status' => Order::STATUS_PROCESSING,
        ]);
    }

    public function test_midtrans_webhook_rejects_invalid_signature()
    {
        $client = Client::firstOrCreate(
            ['whatsapp' => '081299887744'],
            ['name' => 'Fake Customer', 'email' => 'fake@test.com', 'address' => 'Jakarta']
        );

        $order = Order::create([
            'order_number' => 'NR-FAKE-' . strtoupper(Str::random(6)),
            'store_id' => $this->store->id,
            'client_id' => $client->id,
            'status' => Order::STATUS_CONFIRMED,
            'payment_method' => 'Payment Gateway (Midtrans)',
            'payment_status' => 'unpaid',
            'subtotal' => 20000,
            'total_amount' => 20000,
            'payment_gateway_provider' => 'midtrans',
        ]);

        $webhookPayload = [
            'order_id' => $order->order_number,
            'status_code' => '200',
            'gross_amount' => '20000.00',
            'signature_key' => 'INVALID_SIGNATURE_HASH',
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
        ];

        $response = $this->postJson("/api/v1/payment/midtrans/notification/{$this->store->slug}", $webhookPayload);

        $response->assertStatus(401);
        $response->assertJson(['status' => 'error', 'message' => 'Invalid signature key']);

        $order->refresh();
        $this->assertEquals('unpaid', $order->payment_status);
    }

    public function test_store_backend_settings_can_update_midtrans()
    {
        $response = $this->actingAs($this->storeAdmin)->put(
            "/{$this->store->slug}/backend/settings",
            [
                'address' => 'Alamat Baru Cabang',
                'phone' => '081299998888',
                'payment_type' => 'payment_gateway',
                'payment_gateway_provider' => 'midtrans',
                'midtrans_environment' => 'sandbox',
                'midtrans_server_key' => 'SB-Mid-server-NEWTEST123456',
                'midtrans_client_key' => 'SB-Mid-client-NEWTEST123456',
                'midtrans_merchant_id' => 'G999888777',
            ]
        );

        $response->assertSessionHas('success');

        $this->store->refresh();
        $this->assertEquals('payment_gateway', $this->store->payment_type);
        $this->assertEquals('midtrans', $this->store->payment_gateway_provider);
        $this->assertEquals('sandbox', $this->store->midtrans_environment);
        $this->assertEquals('SB-Mid-server-NEWTEST123456', $this->store->midtrans_server_key);
        $this->assertEquals('SB-Mid-client-NEWTEST123456', $this->store->midtrans_client_key);
    }

    public function test_midtrans_simulation_endpoints()
    {
        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'sim-snap-token-999',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/sim-snap-token-999',
            ], 201),
            'https://api.sandbox.midtrans.com/v2/TEST-SIM-001/status' => Http::response([
                'transaction_status' => 'settlement',
                'payment_type' => 'bank_transfer',
                'gross_amount' => '10000.00',
            ], 200),
        ]);

        // 1. Create simulated test transaction
        $resCreate = $this->actingAs($this->storeAdmin)->postJson(
            "/{$this->store->slug}/backend/settings/test-midtrans-transaction",
            ['amount' => 10000]
        );

        $resCreate->assertStatus(200);
        $resCreate->assertJson([
            'success' => true,
            'amount' => 10000,
            'snap_token' => 'sim-snap-token-999',
            'simulator_url' => MidtransPaymentService::SIMULATOR_URL,
        ]);

        // 2. Check simulation status
        $resStatus = $this->actingAs($this->storeAdmin)->postJson(
            "/{$this->store->slug}/backend/settings/test-midtrans-status",
            ['order_id' => 'TEST-SIM-001']
        );

        $resStatus->assertStatus(200);
        $resStatus->assertJson([
            'success' => true,
            'is_paid' => true,
            'transaction_status' => 'settlement',
        ]);
    }

    public function test_order_checkout_api_returns_midtrans_snap_token()
    {
        $category = Category::firstOrCreate(
            ['store_id' => $this->store->id, 'name' => 'Paket Ricebox'],
            ['slug' => 'paket-ricebox-' . $this->store->id]
        );

        $menu = Menu::firstOrCreate(
            ['store_id' => $this->store->id, 'name' => 'Nasi Daun Jeruk Ayam Suwir'],
            ['category_id' => $category->id, 'price' => 30000, 'stock' => 50, 'status' => 'available']
        );

        Http::fake([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'api-snap-token-777',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/api-snap-token-777',
            ], 201),
        ]);

        $orderPayload = [
            'store_id' => $this->store->id,
            'order_type' => 'guest',
            'customer_name' => 'Rina Guest',
            'customer_whatsapp' => '081233445566',
            'customer_email' => 'rina@example.com',
            'customer_address' => 'Jl. Kebon Jeruk No. 5',
            'payment_method' => 'Payment Gateway (Midtrans)',
            'items' => [
                [
                    'menu_id' => $menu->id,
                    'quantity' => 1,
                ]
            ],
        ];

        $response = $this->postJson('/api/v1/orders', $orderPayload);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'data' => [
                'payment_method' => 'Payment Gateway (Midtrans)',
                'snap_token' => 'api-snap-token-777',
                'payment_gateway_provider' => 'midtrans',
                'midtrans_client_key' => $this->store->midtrans_client_key,
                'midtrans_environment' => 'sandbox',
            ]
        ]);
    }

    public function test_payment_return_redirect_handles_midtrans()
    {
        $client = Client::firstOrCreate(
            ['whatsapp' => '081255667788'],
            ['name' => 'Return Customer', 'email' => 'ret@test.com', 'address' => 'Jakarta']
        );

        $order = Order::create([
            'order_number' => 'NR-RET-' . strtoupper(Str::random(6)),
            'store_id' => $this->store->id,
            'client_id' => $client->id,
            'status' => Order::STATUS_CONFIRMED,
            'payment_method' => 'Payment Gateway (Midtrans)',
            'payment_status' => 'unpaid',
            'subtotal' => 40000,
            'total_amount' => 40000,
            'snap_token' => 'snap-ret-123',
            'payment_gateway_provider' => 'midtrans',
        ]);

        $response = $this->get("/payment/midtrans/return?order={$order->order_number}&status_code=200&transaction_status=settlement");

        $response->assertStatus(200);
        $response->assertSee('Pembayaran Berhasil!');

        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals(Order::STATUS_PROCESSING, $order->status);
    }
}

