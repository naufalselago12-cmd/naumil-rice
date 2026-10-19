<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Store;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Campaign;
use App\Models\CampaignMenu;
use App\Models\Client;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

class CampaignQuotaAndVisibilityTest extends TestCase
{
    protected Store $store;
    protected Menu $campaignMenu;
    protected Menu $regularMenu;
    protected Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create a store
        $this->store = Store::create([
            'name' => 'Cabang Test Quota',
            'code' => 'TEST-Q-' . uniqid(),
            'slug' => 'cabang-test-quota-' . uniqid(),
            'address' => 'Jl. Test No 123',
            'phone' => '081234567890',
            'status' => 'active',
        ]);

        $category = Category::create([
            'store_id' => $this->store->id,
            'name' => 'Signature',
            'display_order' => 1,
        ]);

        // 2. Create 2 menus for this store
        $this->campaignMenu = Menu::create([
            'store_id' => $this->store->id,
            'category_id' => $category->id,
            'name' => 'Nasi Ayam Pre-Order',
            'price' => 35000,
            'initial_stock' => 100,
            'current_stock' => 100,
            'is_active' => true,
        ]);

        $this->regularMenu = Menu::create([
            'store_id' => $this->store->id,
            'category_id' => $category->id,
            'name' => 'Es Teh Manis Reguler',
            'price' => 5000,
            'initial_stock' => 100,
            'current_stock' => 100,
            'is_active' => true,
        ]);
    }

    public function test_when_active_campaign_exists_only_campaign_menus_appear_on_storefront()
    {
        // Create an active campaign for the store including only $this->campaignMenu
        $campaign = Campaign::create([
            'store_id' => $this->store->id,
            'title' => 'Batch Open PO Weekend',
            'slug' => 'batch-open-po-weekend-' . uniqid(),
            'close_order_at' => Carbon::now()->addDays(2),
            'ready_at' => Carbon::now()->addDays(3),
            'max_quota' => 20,
            'qr_token' => 'PO-' . strtoupper(uniqid()),
            'is_active' => true,
        ]);

        CampaignMenu::create([
            'campaign_id' => $campaign->id,
            'menu_id' => $this->campaignMenu->id,
            'custom_price' => 30000,
            'discount_price' => 5000,
            'max_quota' => 10,
        ]);

        $response = $this->get('/' . $this->store->slug);
        $response->assertStatus(200);

        // Verify Inertia response contains only campaign menu and NOT regular menu
        $response->assertInertia(fn (Assert $page) => $page
            ->component('StoreFront')
            ->has('campaigns', 1)
            ->has('menus', 1)
            ->where('menus.0.id', $this->campaignMenu->id)
            ->where('menus.0.name', 'Nasi Ayam Pre-Order')
            ->where('menus.0.max_quota', 10)
        );
    }

    public function test_when_no_active_campaign_all_regular_menus_appear_on_storefront()
    {
        // No campaigns created for store
        $response = $this->get('/' . $this->store->slug);
        $response->assertStatus(200);

        // All active menus should appear
        $response->assertInertia(fn (Assert $page) => $page
            ->component('StoreFront')
            ->has('campaigns', 0)
            ->has('menus', 2)
        );
    }

    public function test_order_fails_when_exceeding_menu_max_quota()
    {
        $campaign = Campaign::create([
            'store_id' => $this->store->id,
            'title' => 'Batch PO Limited Menu Quota',
            'slug' => 'batch-po-limited-menu-' . uniqid(),
            'close_order_at' => Carbon::now()->addDays(2),
            'ready_at' => Carbon::now()->addDays(3),
            'max_quota' => 50,
            'qr_token' => 'PO-' . strtoupper(uniqid()),
            'is_active' => true,
        ]);

        CampaignMenu::create([
            'campaign_id' => $campaign->id,
            'menu_id' => $this->campaignMenu->id,
            'custom_price' => 35000,
            'discount_price' => 0,
            'max_quota' => 5, // Menu quota is only 5
        ]);

        // Attempt to order 6 portions
        $payload = [
            'store_id' => $this->store->id,
            'campaign_id' => $campaign->id,
            'order_type' => 'guest',
            'customer_name' => 'Budi Tester',
            'customer_whatsapp' => '081299998888',
            'customer_email' => 'budi@test.com',
            'customer_address' => 'Jl. Kebon Jeruk No 1',
            'payment_method' => 'Bayar Terima Makanan',
            'items' => [
                [
                    'menu_id' => $this->campaignMenu->id,
                    'quantity' => 6,
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/orders', $payload);
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('kuota', strtolower($response->json('message')));
    }

    public function test_order_fails_when_exceeding_campaign_max_quota()
    {
        $campaign = Campaign::create([
            'store_id' => $this->store->id,
            'title' => 'Batch PO Limited Batch Quota',
            'slug' => 'batch-po-limited-batch-' . uniqid(),
            'close_order_at' => Carbon::now()->addDays(2),
            'ready_at' => Carbon::now()->addDays(3),
            'max_quota' => 4, // Entire batch quota is 4
            'qr_token' => 'PO-' . strtoupper(uniqid()),
            'is_active' => true,
        ]);

        CampaignMenu::create([
            'campaign_id' => $campaign->id,
            'menu_id' => $this->campaignMenu->id,
            'custom_price' => 35000,
            'discount_price' => 0,
            'max_quota' => 10,
        ]);

        // Attempt to order 5 portions (exceeds campaign max_quota of 4)
        $payload = [
            'store_id' => $this->store->id,
            'campaign_id' => $campaign->id,
            'order_type' => 'guest',
            'customer_name' => 'Siti Tester',
            'customer_whatsapp' => '081277776666',
            'customer_email' => 'siti@test.com',
            'customer_address' => 'Jl. Mawar No 10',
            'payment_method' => 'Bayar Terima Makanan',
            'items' => [
                [
                    'menu_id' => $this->campaignMenu->id,
                    'quantity' => 5,
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/orders', $payload);
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('kuota', strtolower($response->json('message')));
    }

    public function test_order_succeeds_when_within_quotas()
    {
        $campaign = Campaign::create([
            'store_id' => $this->store->id,
            'title' => 'Batch PO Valid Quota',
            'slug' => 'batch-po-valid-quota-' . uniqid(),
            'close_order_at' => Carbon::now()->addDays(2),
            'ready_at' => Carbon::now()->addDays(3),
            'max_quota' => 20,
            'qr_token' => 'PO-' . strtoupper(uniqid()),
            'is_active' => true,
        ]);

        CampaignMenu::create([
            'campaign_id' => $campaign->id,
            'menu_id' => $this->campaignMenu->id,
            'custom_price' => 35000,
            'discount_price' => 0,
            'max_quota' => 10,
        ]);

        // Order 3 portions (within both 10 and 20)
        $payload = [
            'store_id' => $this->store->id,
            'campaign_id' => $campaign->id,
            'order_type' => 'guest',
            'customer_name' => 'Agus Tester',
            'customer_whatsapp' => '081255554444',
            'customer_email' => 'agus@test.com',
            'customer_address' => 'Jl. Melati No 5',
            'payment_method' => 'Bayar Terima Makanan',
            'items' => [
                [
                    'menu_id' => $this->campaignMenu->id,
                    'quantity' => 3,
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/orders', $payload);
        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertEquals(3, $campaign->total_ordered);
        $this->assertEquals(17, $campaign->remaining_quota);
    }
}
