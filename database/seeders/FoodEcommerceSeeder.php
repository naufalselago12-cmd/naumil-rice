<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Store;
use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuOption;
use App\Models\Campaign;
use App\Models\CampaignMenu;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use Illuminate\Support\Str;
use Carbon\Carbon;

class FoodEcommerceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Stores
        $storePusat = Store::create([
            'name' => 'Naumil Store - Flagship Sudirman',
            'code' => 'NR-SDR',
            'address' => 'Jl. Jenderal Sudirman No. 45, Jakarta Selatan',
            'phone' => '081298765432',
            'logo' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=80',
            'qris_image' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=00020101021226600014ID.LINKAJA.WWW011893600918000000000102150812987654325204581253033605802ID5911NAUMIL_RICE6007JAKARTA62070703A016304',
            'qris_data' => '00020101021226600014ID.LINKAJA.WWW011893600918000000000102150812987654325204581253033605802ID5911NAUMIL_RICE6007JAKARTA62070703A016304',
            'status' => 'active',
        ]);

        $storeTebet = Store::create([
            'name' => 'Naumil Store - Branch Tebet Kitchen',
            'code' => 'NR-TBT',
            'address' => 'Jl. Tebet Raya No. 88, Jakarta Selatan',
            'phone' => '081387654321',
            'logo' => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=400&q=80',
            'qris_image' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=00020101021226600014ID.LINKAJA.WWW011893600918000000000202150813876543215204581253033605802ID5917NAUMIL_RICE_TEBET6007JAKARTA62070703A016304',
            'qris_data' => '00020101021226600014ID.LINKAJA.WWW011893600918000000000202150813876543215204581253033605802ID5917NAUMIL_RICE_TEBET6007JAKARTA62070703A016304',
            'status' => 'active',
        ]);

        // 2. Categories for Sudirman
        $catRiceBowl = Category::create([
            'store_id' => $storePusat->id,
            'name' => 'Signature Rice Bowls',
            'description' => 'Nasi aromatik khas Naumil dengan aneka lauk istimewa',
            'display_order' => 1,
        ]);

        $catNasiGoreng = Category::create([
            'store_id' => $storePusat->id,
            'name' => 'Wok Special Fried Rice',
            'description' => 'Nasi goreng racikan chef dengan aroma wok hei otentik',
            'display_order' => 2,
        ]);

        $catSideDish = Category::create([
            'store_id' => $storePusat->id,
            'name' => 'Side Dishes & Drinks',
            'description' => 'Camilan pendamping dan minuman segar',
            'display_order' => 3,
        ]);

        // 3. Menus for Sudirman
        $menu1 = Menu::create([
            'store_id' => $storePusat->id,
            'category_id' => $catRiceBowl->id,
            'name' => 'Rice Bowl Cumi Hitam Madura',
            'description' => 'Nasi daun jeruk pulen disajikan dengan cumi saus tinta hitam bumbu pekat gurih, serundeng, dan sambal mangga segar.',
            'price' => 45000,
            'initial_stock' => 50,
            'current_stock' => 42,
            'image' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80',
            'is_active' => true,
        ]);

        // Options for Menu 1
        MenuOption::create(['menu_id' => $menu1->id, 'type' => 'topping', 'name' => 'Telur Mata Sapi Setengah Matang', 'price' => 6000]);
        MenuOption::create(['menu_id' => $menu1->id, 'type' => 'topping', 'name' => 'Extra Cumi Hitam (50g)', 'price' => 15000]);
        MenuOption::create(['menu_id' => $menu1->id, 'type' => 'topping', 'name' => 'Kulit Crispy Gurih', 'price' => 8000]);
        MenuOption::create(['menu_id' => $menu1->id, 'type' => 'upsize', 'name' => 'Regular Size (Porsi Pas)', 'price' => 0]);
        MenuOption::create(['menu_id' => $menu1->id, 'type' => 'upsize', 'name' => 'Jumbo Size (Nasi + 30% Lauk)', 'price' => 12000]);
        MenuOption::create(['menu_id' => $menu1->id, 'type' => 'condition', 'name' => 'Level Pedas: Sedang (Standard)', 'price' => 0]);
        MenuOption::create(['menu_id' => $menu1->id, 'type' => 'condition', 'name' => 'Level Pedas: Extra Pedas Nampol', 'price' => 3000]);

        $menu2 = Menu::create([
            'store_id' => $storePusat->id,
            'category_id' => $catRiceBowl->id,
            'name' => 'Rice Bowl Beef Sei Sapi Sambal Luat',
            'description' => 'Daging sapi asap harum rempah khas NTT dengan sambal luat asam pedas segar dan kuah kaldu gurih.',
            'price' => 52000,
            'initial_stock' => 60,
            'current_stock' => 55,
            'image' => 'https://images.unsplash.com/photo-1544025162-d76694265947?w=600&q=80',
            'is_active' => true,
        ]);

        MenuOption::create(['menu_id' => $menu2->id, 'type' => 'topping', 'name' => 'Telur Onsen Lembut', 'price' => 7000]);
        MenuOption::create(['menu_id' => $menu2->id, 'type' => 'topping', 'name' => 'Keju Mozzarella Torch', 'price' => 9000]);
        MenuOption::create(['menu_id' => $menu2->id, 'type' => 'upsize', 'name' => 'Regular Bowl', 'price' => 0]);
        MenuOption::create(['menu_id' => $menu2->id, 'type' => 'upsize', 'name' => 'Monster Meat Bowl (Double Daging)', 'price' => 20000]);

        $menu3 = Menu::create([
            'store_id' => $storePusat->id,
            'category_id' => $catRiceBowl->id,
            'name' => 'Rice Bowl Ayam Suwir Kecombrang',
            'description' => 'Dada ayam segar disuwir bumbu rempah kecombrang harum wangi, disajikan dengan tempe orek krispi dan lalapan.',
            'price' => 38000,
            'initial_stock' => 80,
            'current_stock' => 70,
            'image' => 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?w=600&q=80',
            'is_active' => true,
        ]);
        MenuOption::create(['menu_id' => $menu3->id, 'type' => 'topping', 'name' => 'Telur Dadar Crispy', 'price' => 5000]);
        MenuOption::create(['menu_id' => $menu3->id, 'type' => 'upsize', 'name' => 'Porsi Normal', 'price' => 0]);
        MenuOption::create(['menu_id' => $menu3->id, 'type' => 'upsize', 'name' => 'Porsi Jumbo', 'price' => 10000]);

        $menu4 = Menu::create([
            'store_id' => $storePusat->id,
            'category_id' => $catNasiGoreng->id,
            'name' => 'Nasi Goreng Wagyu Saus Mentega',
            'description' => 'Nasi goreng wok harum mentega gurih dengan potongan daging meltique wagyu juicy dan acar timun wortel.',
            'price' => 58000,
            'initial_stock' => 40,
            'current_stock' => 35,
            'image' => 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=600&q=80',
            'is_active' => true,
        ]);
        MenuOption::create(['menu_id' => $menu4->id, 'type' => 'topping', 'name' => 'Sunny Side Up Egg', 'price' => 6000]);
        MenuOption::create(['menu_id' => $menu4->id, 'type' => 'upsize', 'name' => 'Regular', 'price' => 0]);
        MenuOption::create(['menu_id' => $menu4->id, 'type' => 'upsize', 'name' => 'Porsi Sultan (+50% Wagyu)', 'price' => 25000]);

        $menu5 = Menu::create([
            'store_id' => $storePusat->id,
            'category_id' => $catSideDish->id,
            'name' => 'Es Cincau Gula Aren Naumil',
            'description' => 'Minuman cincau hijau alami dengan santan kelapa murni dan gula aren organik dingin menyegarkan.',
            'price' => 18000,
            'initial_stock' => 100,
            'current_stock' => 90,
            'image' => 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?w=600&q=80',
            'is_active' => true,
        ]);

        // 4. Open PO Campaign for Sudirman
        $qrTokenSudirman = 'PO-NR-SDR-' . Str::random(10);
        $campaignSudirman = Campaign::create([
            'store_id' => $storePusat->id,
            'title' => 'Open Pre-Order Batch #15: Naumil Store Nusantara Feasts',
            'slug' => 'open-po-batch-15-naumil-sudirman',
            'banner_image' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1200&q=80',
            'description' => 'Pre-order batch spesial akhir pekan! Nikmati menu signature rice bowl dan nasi goreng wagyu dibuat fresh dengan diskon potongan Rp 5.000 per bowl dan promo minimum belanja Rp 100.000.',
            'discount_type' => 'fixed',
            'discount_value' => 5000,
            'min_purchase_amount' => 100000,
            'close_order_at' => Carbon::now()->addDays(3)->setTime(21, 0, 0),
            'ready_at' => Carbon::now()->addDays(4)->setTime(11, 30, 0),
            'qr_token' => $qrTokenSudirman,
            'is_active' => true,
        ]);

        // Attach menus to campaign with specific discounts
        CampaignMenu::create([
            'campaign_id' => $campaignSudirman->id,
            'menu_id' => $menu1->id,
            'custom_price' => 45000,
            'discount_price' => 5000, // becomes 40.000
            'max_quota' => 50,
        ]);

        CampaignMenu::create([
            'campaign_id' => $campaignSudirman->id,
            'menu_id' => $menu2->id,
            'custom_price' => 52000,
            'discount_price' => 5000, // becomes 47.000
            'max_quota' => 40,
        ]);

        CampaignMenu::create([
            'campaign_id' => $campaignSudirman->id,
            'menu_id' => $menu3->id,
            'custom_price' => 38000,
            'discount_price' => 3000, // becomes 35.000
            'max_quota' => 50,
        ]);

        CampaignMenu::create([
            'campaign_id' => $campaignSudirman->id,
            'menu_id' => $menu4->id,
            'custom_price' => 58000,
            'discount_price' => 8000, // becomes 50.000
            'max_quota' => 30,
        ]);

        CampaignMenu::create([
            'campaign_id' => $campaignSudirman->id,
            'menu_id' => $menu5->id,
            'custom_price' => 18000,
            'discount_price' => 2000, // becomes 16.000
            'max_quota' => 60,
        ]);

        // 5. Sample Clients
        $client1 = Client::create([
            'name' => 'Dimas Wicaksono',
            'whatsapp' => '081234567890',
            'email' => 'dimas.wicak@gmail.com',
            'address' => 'Gedung Menara Sudirman Lt. 14, SCBD Jakarta',
            'last_order_at' => Carbon::now()->subHours(2),
        ]);

        $client2 = Client::create([
            'name' => 'Siti Aisyah Rahmawati',
            'whatsapp' => '085678901234',
            'email' => 'siti.aisyah@corporate.id',
            'address' => 'Apartemen Senopati Suites Tower 2 No. 8B',
            'last_order_at' => Carbon::now()->subMinutes(30),
        ]);

        $client3 = Client::create([
            'name' => 'Rian Pratama',
            'whatsapp' => '081987654321',
            'email' => 'rian.pratama@gmail.com',
            'address' => 'Jl. Senopati Dalam No. 12, Kebayoran Baru',
            'last_order_at' => Carbon::now()->subMinutes(10),
        ]);

        // 6. Sample Orders in Various Stages for KDS testing
        // Order 1: On Proses
        $order1 = Order::create([
            'order_number' => 'NR-PO-261012-001',
            'store_id' => $storePusat->id,
            'campaign_id' => $campaignSudirman->id,
            'client_id' => $client1->id,
            'status' => 'Order On Proses',
            'payment_method' => 'QRIS Dinamis',
            'payment_status' => 'paid',
            'subtotal' => 97000,
            'discount_amount' => 10000,
            'total_amount' => 87000,
            'notes' => 'Tolong pisahkan sambal mangga di cup terpisah ya kak.',
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'menu_id' => $menu1->id,
            'menu_name' => $menu1->name,
            'price' => 40000,
            'quantity' => 2,
            'subtotal' => 80000,
            'selected_options' => [
                ['name' => 'Telur Mata Sapi Setengah Matang', 'price' => 6000],
                ['name' => 'Regular Size (Porsi Pas)', 'price' => 0]
            ],
            'special_notes' => '1 setengah matang, 1 matang biasa',
        ]);

        OrderStatusLog::create(['order_id' => $order1->id, 'status' => 'Order Konfirmed', 'note' => 'Pesanan diterima sistem']);
        OrderStatusLog::create(['order_id' => $order1->id, 'status' => 'Order On Proses', 'note' => 'Dapur sedang memasak pesanan']);

        // Order 2: Packaging
        $order2 = Order::create([
            'order_number' => 'NR-PO-261012-002',
            'store_id' => $storePusat->id,
            'campaign_id' => $campaignSudirman->id,
            'client_id' => $client2->id,
            'status' => 'Order On Packaging',
            'payment_method' => 'Bayar Terima Makanan',
            'payment_status' => 'unpaid',
            'subtotal' => 50000,
            'discount_amount' => 8000,
            'total_amount' => 50000,
            'notes' => 'Sediakan sendok dan garpu kayu.',
        ]);

        OrderItem::create([
            'order_id' => $order2->id,
            'menu_id' => $menu4->id,
            'menu_name' => $menu4->name,
            'price' => 50000,
            'quantity' => 1,
            'subtotal' => 50000,
            'selected_options' => [
                ['name' => 'Sunny Side Up Egg', 'price' => 6000]
            ],
            'special_notes' => 'Acar agak banyak ya',
        ]);

        OrderStatusLog::create(['order_id' => $order2->id, 'status' => 'Order Konfirmed', 'note' => 'Pesanan dikonfirmasi']);
        OrderStatusLog::create(['order_id' => $order2->id, 'status' => 'Order On Proses', 'note' => 'Sedang di wok']);
        OrderStatusLog::create(['order_id' => $order2->id, 'status' => 'Order On Packaging', 'note' => 'Sedang dikemas di box insulated']);

        // Order 3: Order Konfirmed
        $order3 = Order::create([
            'order_number' => 'NR-PO-261012-003',
            'store_id' => $storePusat->id,
            'campaign_id' => $campaignSudirman->id,
            'client_id' => $client3->id,
            'status' => 'Order Konfirmed',
            'payment_method' => 'QRIS Dinamis',
            'payment_status' => 'paid',
            'subtotal' => 47000,
            'discount_amount' => 5000,
            'total_amount' => 47000,
            'notes' => 'Pesanan baru masuk via Pre-Order Link QR.',
        ]);

        OrderItem::create([
            'order_id' => $order3->id,
            'menu_id' => $menu2->id,
            'menu_name' => $menu2->name,
            'price' => 47000,
            'quantity' => 1,
            'subtotal' => 47000,
            'selected_options' => [
                ['name' => 'Telur Onsen Lembut', 'price' => 7000]
            ],
            'special_notes' => 'Daging jangan terlalu pedas',
        ]);

        OrderStatusLog::create(['order_id' => $order3->id, 'status' => 'Order Konfirmed', 'note' => 'Menunggu antrean dapur']);
    }
}
