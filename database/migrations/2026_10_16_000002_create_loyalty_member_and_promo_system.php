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
        // 1. Add loyalty columns to clients table
        Schema::table('clients', function (Blueprint $table) {
            $table->string('password')->nullable()->after('email');
            $table->enum('gender', ['Laki-laki', 'Perempuan'])->nullable()->after('password');
            $table->string('occupation')->nullable()->after('gender');
            $table->boolean('is_loyalty_member')->default(false)->after('occupation');
            $table->integer('loyalty_points')->default(0)->after('is_loyalty_member');
            $table->dateTime('email_verified_at')->nullable()->after('activated_at');
        });

        // 2. Pivot table: client_stores (Pelanggan Setia Toko Afiliasi)
        Schema::create('client_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->integer('points')->default(0);
            $table->timestamps();

            $table->unique(['client_id', 'store_id']);
        });

        // 3. Add reward_points to menus table
        Schema::table('menus', function (Blueprint $table) {
            $table->integer('reward_points')->default(0)->after('current_stock');
        });

        // 4. Create loyalty_promos table
        Schema::create('loyalty_promos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('promo_type', ['all_menu_percent', 'per_menu', 'min_purchase_discount'])->default('all_menu_percent');
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->decimal('discount_amount', 12, 2)->nullable();
            $table->decimal('min_purchase_amount', 12, 2)->default(0);
            $table->boolean('allow_benefit_choice')->default(true); // Pelanggan bisa pilih: Potongan atau Poin Lebih Banyak
            $table->decimal('extra_points_multiplier', 4, 2)->default(2.00); // Misal 2x poin
            $table->integer('extra_points_bonus')->default(0); // Misal +10 poin flat
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 5. Create loyalty_promo_menus (for per_menu promo)
        Schema::create('loyalty_promo_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_promo_id')->constrained('loyalty_promos')->onDelete('cascade');
            $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->decimal('discount_amount', 12, 2)->nullable();
            $table->timestamps();
        });

        // 6. Create client_point_transactions table (Riwayat Poin Pelanggan Setia)
        Schema::create('client_point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->enum('type', ['earn', 'redeem', 'bonus', 'adjustment'])->default('earn');
            $table->integer('points'); // bisa positif atau negatif
            $table->string('description');
            $table->timestamp('created_at')->useCurrent();
        });

        // 7. Add loyalty columns to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_loyalty_order')->default(false)->after('discount_amount');
            $table->foreignId('loyalty_promo_id')->nullable()->constrained('loyalty_promos')->nullOnDelete()->after('is_loyalty_order');
            $table->enum('loyalty_benefit_choice', ['discount', 'points'])->nullable()->after('loyalty_promo_id');
            $table->integer('points_earned')->default(0)->after('loyalty_benefit_choice');
            $table->integer('points_used')->default(0)->after('points_earned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['loyalty_promo_id']);
            $table->dropColumn([
                'is_loyalty_order',
                'loyalty_promo_id',
                'loyalty_benefit_choice',
                'points_earned',
                'points_used',
            ]);
        });

        Schema::dropIfExists('client_point_transactions');
        Schema::dropIfExists('loyalty_promo_menus');
        Schema::dropIfExists('loyalty_promos');

        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('reward_points');
        });

        Schema::dropIfExists('client_stores');

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'password',
                'gender',
                'occupation',
                'is_loyalty_member',
                'loyalty_points',
                'email_verified_at',
            ]);
        });
    }
};
