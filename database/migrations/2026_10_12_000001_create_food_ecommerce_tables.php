<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Stores (Cabang / Toko)
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('logo')->nullable();
            $table->string('qris_image')->nullable();
            $table->text('qris_data')->nullable(); // QRIS payload string / merchant string
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // 2. Categories
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });

        // 3. Menus
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->integer('initial_stock')->default(100);
            $table->integer('current_stock')->default(100);
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Menu Options (Condition: Toppings, Upsize/Variant, Notes)
        Schema::create('menu_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');
            $table->enum('type', ['topping', 'upsize', 'condition'])->default('topping');
            $table->string('name');
            $table->decimal('price', 12, 2)->default(0);
            $table->boolean('is_required')->default(false);
            $table->timestamps();
        });

        // 5. Campaigns (Open PO)
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('banner_image')->nullable();
            $table->text('description')->nullable();
            $table->enum('discount_type', ['none', 'percentage', 'fixed', 'min_purchase'])->default('none');
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('min_purchase_amount', 12, 2)->default(0);
            $table->dateTime('close_order_at');
            $table->dateTime('ready_at');
            $table->string('qr_token', 64)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 6. Campaign Menus (Pivot with specific discount / custom price)
        Schema::create('campaign_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');
            $table->decimal('custom_price', 12, 2)->nullable();
            $table->decimal('discount_price', 12, 2)->default(0);
            $table->integer('max_quota')->nullable();
            $table->timestamps();
        });

        // 7. Clients (User customers)
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('whatsapp')->index();
            $table->string('email')->index();
            $table->text('address')->nullable();
            $table->dateTime('last_order_at')->nullable();
            $table->timestamps();
        });

        // 8. Orders
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->enum('status', [
                'Order Konfirmed',
                'Order On Proses',
                'Order On Packaging',
                'Order On Delivery',
                'Order Ready',
                'Order Reject'
            ])->default('Order Konfirmed');
            $table->enum('payment_method', ['QRIS Dinamis', 'Bayar Terima Makanan'])->default('QRIS Dinamis');
            $table->enum('payment_status', ['unpaid', 'paid', 'verified'])->default('unpaid');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        // 9. Order Items
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');
            $table->string('menu_name');
            $table->decimal('price', 12, 2);
            $table->integer('quantity');
            $table->decimal('subtotal', 12, 2);
            $table->json('selected_options')->nullable(); // toppings, upsize details
            $table->text('special_notes')->nullable();
            $table->timestamps();
        });

        // 10. Order Status Logs (for tracing)
        Schema::create('order_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('status');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_logs');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('campaign_menus');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('menu_options');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('stores');
    }
};
