<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Client;
use App\Models\ClientAddress;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Services\KiriminAja\ShippingService;
use App\Services\MidtransPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KiriminAjaDeliveryModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config([
            'kiriminaja.enabled' => true,
            'kiriminaja.environment' => 'sandbox',
            'kiriminaja.api_key' => 'test-ka-key',
            'kiriminaja.pin' => null,
            'kiriminaja.payment_method' => 'credit',
            'kiriminaja.default_express_couriers' => ['jne', 'jnt'],
            'kiriminaja.default_instant_couriers' => ['gosend'],
        ]);
    }

    protected function makeStore(array $overrides = []): Store
    {
        return Store::create(array_merge([
            'name' => 'Naumil Kitchen Tebet',
            'code' => 'NML-TBT',
            'slug' => 'naumil-tebet',
            'address' => 'Jl. Tebet Barat Raya No. 10, Jakarta Selatan',
            'phone' => '081234567890',
            'status' => 'active',
            'payment_type' => 'qris_static',
            'qris_image' => 'https://example.test/qris.png',
            'delivery_enabled' => true,
            'ka_province_id' => 6,
            'ka_province_name' => 'DKI Jakarta',
            'ka_city_id' => 153,
            'ka_city_name' => 'Kota Jakarta Selatan',
            'ka_district_id' => 2101,
            'ka_district_name' => 'Tebet',
            'ka_subdistrict_id' => 31001,
            'ka_subdistrict_name' => 'Tebet Barat',
            'ka_postal_code' => '12810',
            'ka_address' => 'Jl. Tebet Barat Raya No. 10, Ruko Kuning',
            'ka_address_note' => 'Masuk gang melati 10m sebelah apotek',
            'ka_latitude' => -6.234567,
            'ka_longitude' => 106.845678,
            'ka_geo_address' => 'Jl. Tebet Barat Raya No. 10, Tebet, Jakarta Selatan',
            'delivery_couriers' => ['jne', 'jnt', 'gosend'],
            'delivery_weight_per_item' => 500,
        ], $overrides));
    }

    protected function makeMenu(Store $store, float $price = 25000): Menu
    {
        $cat = Category::create([
            'store_id' => $store->id,
            'name' => 'Nasi & Lauk',
            'slug' => 'nasi-lauk-' . uniqid(),
            'is_active' => true,
        ]);

        return Menu::create([
            'store_id' => $store->id,
            'category_id' => $cat->id,
            'name' => 'Nasi Ayam Bakar Madu',
            'slug' => 'nasi-ayam-bakar-' . uniqid(),
            'price' => $price,
            'is_available' => true,
            'reward_points' => 5,
        ]);
    }

    protected function makeStoreAdmin(Store $store): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'store_id' => $store->id,
            'email' => 'admin-' . uniqid() . '@naumil.test',
        ]);
    }

    protected function fakeStandardKiriminAjaAndGeo(array $overrides = []): void
    {
        Http::fake(array_merge([
            'https://tdev.kiriminaja.com/api/mitra/province' => Http::response([
                'status' => true,
                'datas' => [
                    ['id' => 6, 'provinsi_name' => 'DKI Jakarta'],
                    ['id' => 9, 'provinsi_name' => 'Jawa Barat'],
                ],
            ], 200),
            'https://tdev.kiriminaja.com/api/mitra/city' => Http::response([
                'status' => true,
                'datas' => [
                    ['id' => 153, 'kabupaten_name' => 'Jakarta Selatan', 'type' => 'Kota', 'postal_code' => '12810'],
                ],
            ], 200),
            'https://tdev.kiriminaja.com/api/mitra/kecamatan' => Http::response([
                'status' => true,
                'datas' => [
                    ['id' => 2101, 'kecamatan_name' => 'Tebet'],
                    ['id' => 2102, 'kecamatan_name' => 'Pancoran'],
                ],
            ], 200),
            'https://tdev.kiriminaja.com/api/mitra/kelurahan' => Http::response([
                'status' => true,
                'results' => [
                    ['id' => 31001, 'kelurahan_name' => 'Tebet Barat'],
                    ['id' => 31002, 'kelurahan_name' => 'Kalibata'],
                ],
            ], 200),
            'https://tdev.kiriminaja.com/api/mitra/v6.1/addresses*' => Http::response([
                'status' => true,
                'data' => [
                    [
                        'province_id' => 6,
                        'city_id' => 153,
                        'district_id' => 2102,
                        'subdistrict_id' => 31002,
                        'full_address' => 'Kalibata, Pancoran, Kota Jakarta Selatan, DKI Jakarta, 12740',
                    ],
                ],
            ], 200),
            'https://tdev.kiriminaja.com/api/mitra/couriers' => Http::response([
                'status' => true,
                'datas' => [
                    ['code' => 'jne', 'name' => 'JNE', 'type' => 'express'],
                    ['code' => 'jnt', 'name' => 'J&T Express', 'type' => 'express'],
                    ['code' => 'gosend', 'name' => 'GoSend', 'type' => 'instant'],
                ],
            ], 200),
            'https://tdev.kiriminaja.com/api/mitra/v2/schedules' => Http::response([
                'status' => true,
                'schedules' => [
                    ['clock' => date('Y-m-d H:i:s', time() + 3600), 'libur' => false],
                ],
            ], 200),
            'https://tdev.kiriminaja.com/api/mitra/v6.1/shipping_price' => Http::response([
                'status' => true,
                'results' => [
                    [
                        'service' => 'jne',
                        'service_type' => 'REG',
                        'service_name' => 'JNE Reguler',
                        'cost' => 12000,
                        'etd' => '1-2',
                        'group' => 'regular',
                    ],
                    [
                        'service' => 'jnt',
                        'service_type' => 'EZ',
                        'service_name' => 'J&T EZ',
                        'cost' => 10000,
                        'etd' => '1-2',
                        'group' => 'regular',
                    ],
                ],
            ], 200),
            'https://tdev.kiriminaja.com/api/mitra/v4/instant/pricing' => Http::response([
                'status' => true,
                'result' => [
                    [
                        'name' => 'gosend',
                        'costs' => [
                            [
                                'service_type' => 'instant',
                                'estimation' => '1-2 jam',
                                'price' => ['shipping_costs' => 18000],
                            ],
                        ],
                    ],
                ],
            ], 200),
            'https://tdev.kiriminaja.com/api/mitra/v6.2/request_pickup' => function ($req) {
                $body = $req->data();
                $orderId = $body['packages'][0]['order_id'] ?? ($body['order_id'] ?? 'ORD-TEST');
                return Http::response([
                    'status' => true,
                    'text' => 'Success',
                    'pickup_number' => 'PK-9901',
                    'results' => [
                        ['order_id' => $orderId, 'awb' => 'JNT123456789'],
                    ],
                ], 200);
            },
            'https://tdev.kiriminaja.com/api/mitra/tracking' => Http::response([
                'status' => true,
                'status_code' => 106,
                'text' => 'On Delivery',
                'details' => [
                    'awb' => 'JNT123456789',
                    'shipped_at' => '2026-11-15 10:00:00',
                    'delivered' => false,
                ],
                'histories' => [
                    ['created_at' => time(), 'status' => 'Kurir menuju lokasi tujuan'],
                ],
            ], 200),
            'https://tdev.kiriminaja.com/api/mitra/v3/cancel_shipment' => Http::response([
                'status' => true,
                'text' => 'Canceled',
            ], 200),
            'https://nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'Jl. Kalibata Raya No. 8, Pancoran, Jakarta Selatan, DKI Jakarta, 12740, Indonesia',
                'address' => [
                    'road' => 'Jl. Kalibata Raya No. 8',
                    'suburb' => 'Kalibata',
                    'city_district' => 'Pancoran',
                    'city' => 'Kota Jakarta Selatan',
                    'state' => 'DKI Jakarta',
                    'postcode' => '12740',
                ],
            ], 200),
        ], $overrides));
    }

    public function test_coverage_and_osm_reverse_geotag_endpoints_work(): void
    {
        $this->fakeStandardKiriminAjaAndGeo();

        $this->getJson('/api/v1/shipping/provinces')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/v1/shipping/cities?province_id=6')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Kota Jakarta Selatan');

        $this->getJson('/api/v1/shipping/districts?city_id=153')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/v1/shipping/subdistricts?district_id=2101')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Kalibata')
            ->assertJsonPath('data.1.name', 'Tebet Barat');

        $this->getJson('/api/v1/geo/reverse?latitude=-6.256&longitude=106.851&match_region=1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.postal_code', '12740')
            ->assertJsonPath('data.matched_region.province_id', 6)
            ->assertJsonPath('data.matched_region.district_id', 2102)
            ->assertJsonPath('data.matched_region.subdistrict_id', 31002);
    }

    public function test_admin_settings_validates_delivery_origin_and_simulator_computes_rates(): void
    {
        $this->fakeStandardKiriminAjaAndGeo();
        $store = $this->makeStore([
            'delivery_enabled' => false,
            'ka_province_id' => null,
            'ka_city_id' => null,
            'ka_district_id' => null,
            'ka_subdistrict_id' => null,
            'ka_postal_code' => null,
            'ka_address' => null,
            'ka_latitude' => null,
            'ka_longitude' => null,
        ]);
        $admin = $this->makeStoreAdmin($store);

        // Mengaktifkan KiriminAja tanpa melengkapi alamat asal + geotag harus ditolak validasi
        $failRes = $this->actingAs($admin)->put("/{$store->slug}/backend/settings", [
            'address' => $store->address,
            'payment_type' => 'qris_static',
            'delivery_enabled' => 1,
        ]);
        $failRes->assertSessionHasErrors([
            'ka_province_id',
            'ka_city_id',
            'ka_district_id',
            'ka_subdistrict_id',
            'ka_postal_code',
            'ka_address',
            'ka_latitude',
            'ka_longitude',
        ]);

        // Menyimpan pengaturan lengkap berhasil
        $okRes = $this->actingAs($admin)->put("/{$store->slug}/backend/settings", [
            'address' => $store->address,
            'phone' => '081234567890',
            'payment_type' => 'qris_static',
            'delivery_enabled' => 1,
            'ka_province_id' => 6,
            'ka_province_name' => 'DKI Jakarta',
            'ka_city_id' => 153,
            'ka_city_name' => 'Kota Jakarta Selatan',
            'ka_district_id' => 2101,
            'ka_district_name' => 'Tebet',
            'ka_subdistrict_id' => 31001,
            'ka_subdistrict_name' => 'Tebet Barat',
            'ka_postal_code' => '12810',
            'ka_address' => 'Jl. Tebet Barat Raya No. 10, Jakarta Selatan',
            'ka_address_note' => 'Gang Mawar 15m',
            'ka_latitude' => -6.234567,
            'ka_longitude' => 106.845678,
            'ka_geo_address' => 'Jl. Tebet Barat Raya No. 10',
            'delivery_couriers_submitted' => 1,
            'delivery_couriers' => ['jnt', 'jne', 'gosend'],
            'delivery_weight_per_item' => 500,
        ]);
        $okRes->assertRedirect()
            ->assertSessionHasNoErrors();
        $store->refresh();
        $this->assertTrue($store->delivery_enabled);
        $this->assertSame('Gang Mawar 15m', $store->ka_address_note);

        // Simulasi biaya pengiriman dari sisi admin
        $simRes = $this->actingAs($admin)->postJson("/{$store->slug}/backend/settings/simulate-shipping", [
            'destination_district_id' => 2102,
            'destination_subdistrict_id' => 31002,
            'destination_latitude' => -6.256,
            'destination_longitude' => 106.851,
            'destination_address' => 'Jl. Kalibata Raya No. 8',
            'weight_grams' => 1000,
            'item_value' => 50000,
        ]);

        $simRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.options.0.courier', 'jnt')
            ->assertJsonPath('data.options.0.cost', 10000)
            ->assertJsonPath('data.options.1.courier', 'jne')
            ->assertJsonPath('data.options.2.courier', 'gosend')
            ->assertJsonPath('data.options.2.type', 'instant');
    }

    public function test_guest_checkout_supports_pickup_cod_and_validates_delivery_cost_server_side(): void
    {
        $this->fakeStandardKiriminAjaAndGeo();
        $store = $this->makeStore();
        $menu = $this->makeMenu($store, 30000);

        // 1. Guest pilih COD (Ambil Sendiri di Outlet) tanpa mengisi alamat panjang
        $pickupRes = $this->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'order_type' => 'guest',
            'customer_name' => 'Budi Santoso',
            'customer_whatsapp' => '081211122233',
            'customer_email' => 'budi@example.com',
            'payment_method' => 'Bayar Terima Makanan',
            'fulfillment_type' => 'pickup',
            'items' => [
                ['menu_id' => $menu->id, 'quantity' => 2],
            ],
        ]);

        $pickupRes->assertStatus(201)
            ->assertJsonPath('data.fulfillment_type', 'pickup')
            ->assertJsonPath('data.shipping_cost', 0)
            ->assertJsonPath('data.total_amount', 60000);

        // 2. Guest pilih Kirim Langsung ke Rumah namun memakai metode COD -> wajib ditolak 422
        $codDeliveryRes = $this->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'order_type' => 'guest',
            'customer_name' => 'Siti Aminah',
            'customer_whatsapp' => '081299988877',
            'customer_email' => 'siti@example.com',
            'payment_method' => 'Bayar Terima Makanan',
            'fulfillment_type' => 'delivery',
            'shipping' => [
                'type' => 'express',
                'courier' => 'jnt',
                'service_type' => 'EZ',
                'province_id' => 6,
                'province_name' => 'DKI Jakarta',
                'city_id' => 153,
                'city_name' => 'Kota Jakarta Selatan',
                'district_id' => 2102,
                'district_name' => 'Pancoran',
                'subdistrict_id' => 31002,
                'subdistrict_name' => 'Kalibata',
                'postal_code' => '12740',
                'address' => 'Jl. Kalibata Raya No. 8 Jakarta Selatan',
                'latitude' => -6.256,
                'longitude' => 106.851,
            ],
            'items' => [
                ['menu_id' => $menu->id, 'quantity' => 1],
            ],
        ]);
        $codDeliveryRes->assertStatus(422);

        // 3. Guest pilih Kirim Langsung ke Rumah + QRIS Statik: harga ongkir tetap diambil dari quote server KiriminAja
        $deliveryRes = $this->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'order_type' => 'guest',
            'customer_name' => 'Siti Aminah',
            'customer_whatsapp' => '081299988877',
            'customer_email' => 'siti@example.com',
            'payment_method' => 'QRIS Statik',
            'fulfillment_type' => 'delivery',
            'shipping' => [
                'type' => 'express',
                'courier' => 'jnt',
                'service_type' => 'EZ',
                'cost' => 1, // dimanipulasi di client -> harus diabaikan
                'province_id' => 6,
                'province_name' => 'DKI Jakarta',
                'city_id' => 153,
                'city_name' => 'Kota Jakarta Selatan',
                'district_id' => 2102,
                'district_name' => 'Pancoran',
                'subdistrict_id' => 31002,
                'subdistrict_name' => 'Kalibata',
                'postal_code' => '12740',
                'address' => 'Jl. Kalibata Raya No. 8 Jakarta Selatan',
                'address_note' => 'Gang Hijau depan mushola',
                'latitude' => -6.256,
                'longitude' => 106.851,
                'geo_address' => 'Jl. Kalibata Raya No. 8, Pancoran',
            ],
            'items' => [
                ['menu_id' => $menu->id, 'quantity' => 2],
            ],
        ]);

        $deliveryRes->assertStatus(201)
            ->assertJsonPath('data.fulfillment_type', 'delivery')
            ->assertJsonPath('data.shipping_courier', 'jnt')
            ->assertJsonPath('data.shipping_cost', 10000)
            ->assertJsonPath('data.total_amount', 70000)
            ->assertJsonPath('data.shipping_address.address_note', 'Gang Hijau depan mushola');
    }

    public function test_loyalty_customer_multi_address_book_and_reuse_on_checkout(): void
    {
        $this->fakeStandardKiriminAjaAndGeo();
        $store = $this->makeStore();
        $menu = $this->makeMenu($store, 20000);

        $client = Client::create([
            'name' => 'Dewi Lestari',
            'email' => 'dewi@example.com',
            'whatsapp' => '081344455566',
            'address' => '-',
            'is_loyalty_member' => true,
            'is_active' => true,
        ]);

        // Simpan alamat pertama -> otomatis jadi default
        $createRes = $this->postJson('/api/v1/customer/addresses', [
            'client_id' => $client->id,
            'email' => $client->email,
            'label' => 'Rumah Utama',
            'province_id' => 6,
            'province_name' => 'DKI Jakarta',
            'city_id' => 153,
            'city_name' => 'Kota Jakarta Selatan',
            'district_id' => 2102,
            'district_name' => 'Pancoran',
            'subdistrict_id' => 31002,
            'subdistrict_name' => 'Kalibata',
            'postal_code' => '12740',
            'address' => 'Jl. Kalibata Timur No. 15, Pancoran',
            'address_note' => 'Pager coklat masuk gang buntu',
            'latitude' => -6.256,
            'longitude' => 106.851,
        ]);
        $createRes->assertStatus(201)->assertJsonPath('data.is_default', true);
        $firstAddrId = $createRes->json('data.id');

        // Tambah alamat kedua untuk kirim ke kantor
        $createSecond = $this->postJson('/api/v1/customer/addresses', [
            'client_id' => $client->id,
            'email' => $client->email,
            'label' => 'Kantor Kuningan',
            'province_id' => 6,
            'province_name' => 'DKI Jakarta',
            'city_id' => 153,
            'city_name' => 'Kota Jakarta Selatan',
            'district_id' => 2101,
            'district_name' => 'Tebet',
            'subdistrict_id' => 31001,
            'subdistrict_name' => 'Tebet Barat',
            'postal_code' => '12810',
            'address' => 'Menara Kuningan Lantai 5 Jl. Rasuna Said',
            'address_note' => 'Titip lobi resepsionis',
            'latitude' => -6.221,
            'longitude' => 106.832,
        ]);
        $createSecond->assertStatus(201);
        $this->assertCount(2, $client->addresses()->get());

        // Checkout memakai client_address_id tersimpan tanpa input ulang alamat
        $orderRes = $this->postJson('/api/v1/orders', [
            'store_id' => $store->id,
            'order_type' => 'loyalty',
            'customer_name' => $client->name,
            'customer_whatsapp' => $client->whatsapp,
            'customer_email' => $client->email,
            'payment_method' => 'QRIS Statik',
            'fulfillment_type' => 'delivery',
            'client_address_id' => $firstAddrId,
            'shipping' => [
                'type' => 'instant',
                'courier' => 'gosend',
                'service_type' => 'instant',
            ],
            'items' => [
                ['menu_id' => $menu->id, 'quantity' => 1],
            ],
        ]);

        $orderRes->assertStatus(201)
            ->assertJsonPath('data.shipping_courier', 'gosend')
            ->assertJsonPath('data.shipping_cost', 18000)
            ->assertJsonPath('data.total_amount', 38000)
            ->assertJsonPath('data.shipping_address.address_note', 'Pager coklat masuk gang buntu');
    }

    public function test_admin_booking_tracking_cancel_and_webhook_and_midtrans_shipping_line(): void
    {
        $this->fakeStandardKiriminAjaAndGeo([
            'https://app.sandbox.midtrans.com/snap/v1/transactions' => function ($req) {
                $body = $req->data();
                $shippingItem = collect($body['item_details'])->firstWhere('id', 'SHIPPING');
                $this->assertNotNull($shippingItem);
                $this->assertSame(10000, $shippingItem['price']);
                $this->assertSame(60000, $body['transaction_details']['gross_amount']);
                return Http::response([
                    'token' => 'snap-token-123',
                    'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token-123',
                ], 201);
            },
        ]);
        $store = $this->makeStore([
            'payment_type' => 'payment_gateway',
            'payment_gateway_provider' => 'midtrans',
            'midtrans_environment' => 'sandbox',
            'midtrans_server_key' => 'SB-Mid-server-TESTKEY1234',
            'midtrans_client_key' => 'SB-Mid-client-TESTKEY1234',
        ]);
        $admin = $this->makeStoreAdmin($store);
        $client = Client::create([
            'name' => 'Rina Kartika',
            'email' => 'rina@example.com',
            'whatsapp' => '081233344455',
            'address' => 'Jl. Kalibata Raya No. 8',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-KA-TEST1',
            'store_id' => $store->id,
            'client_id' => $client->id,
            'status' => Order::STATUS_CONFIRMED,
            'payment_method' => 'Payment Gateway (Midtrans)',
            'payment_status' => 'paid',
            'subtotal' => 50000,
            'discount_amount' => 0,
            'shipping_cost' => 10000,
            'total_amount' => 60000,
            'fulfillment_type' => Order::FULFILLMENT_DELIVERY,
            'shipping_type' => 'express',
            'shipping_courier' => 'jnt',
            'shipping_service_name' => 'J&T EZ',
            'shipping_service_type' => 'EZ',
            'shipping_status' => ShippingService::STATUS_WAITING,
            'shipping_address' => [
                'recipient_name' => 'Rina Kartika',
                'phone' => '081233344455',
                'province_id' => 6,
                'province_name' => 'DKI Jakarta',
                'city_id' => 153,
                'city_name' => 'Kota Jakarta Selatan',
                'district_id' => 2102,
                'district_name' => 'Pancoran',
                'subdistrict_id' => 31002,
                'subdistrict_name' => 'Kalibata',
                'postal_code' => '12740',
                'address' => 'Jl. Kalibata Raya No. 8',
                'address_note' => 'Samping pos ronda',
                'latitude' => -6.256,
                'longitude' => 106.851,
            ],
        ]);
        $menu = $this->makeMenu($store, 25000);
        $order->items()->create([
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            'price' => 25000,
            'quantity' => 2,
            'subtotal' => 50000,
        ]);

        // Midtrans + KiriminAja sudah di-stub sekaligus di awal test via fakeStandardKiriminAjaAndGeo()

        $snapRes = (new MidtransPaymentService())->createSnapTransaction($store, $order->fresh(['items', 'client']));
        $this->assertTrue($snapRes['success']);

        // Book Pickup
        $this->actingAs($admin)
            ->post("/{$store->slug}/backend/orders/{$order->id}/book-shipping")
            ->assertSessionHas('success');
        $order->refresh();
        $this->assertSame('ORD-KA-TEST1', $order->ka_order_id);
        $this->assertSame('JNT123456789', $order->ka_awb);
        $this->assertSame(ShippingService::STATUS_BOOKED, $order->shipping_status);

        // Refresh Tracking
        $this->actingAs($admin)
            ->post("/{$store->slug}/backend/orders/{$order->id}/refresh-shipping")
            ->assertSessionHas('success');
        $order->refresh();
        $this->assertSame(ShippingService::STATUS_SHIPPED, $order->shipping_status);
        $this->assertSame(Order::STATUS_DELIVERY, $order->status);

        // Webhook tanpa Authorization ditolak (401) jika API key terpasang
        $this->postJson('/api/v1/kiriminaja/webhook', [
            'method' => 'finished_packages',
            'data' => [['order_id' => 'ORD-KA-TEST1', 'awb' => 'JNT123456789']],
        ])->assertStatus(401);

        // Webhook sah -> finish order -> Order Ready + delivered
        $this->withHeaders(['Authorization' => 'Bearer test-ka-key'])
            ->postJson('/api/v1/kiriminaja/webhook', [
                'method' => 'finished_packages',
                'data' => [['order_id' => 'ORD-KA-TEST1', 'awb' => 'JNT123456789']],
            ])->assertOk()->assertJsonPath('updated', 1);

        $order->refresh();
        $this->assertSame(ShippingService::STATUS_DELIVERED, $order->shipping_status);
        $this->assertSame(Order::STATUS_READY, $order->status);
    }
}
