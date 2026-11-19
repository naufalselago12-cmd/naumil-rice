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
        // 1. Tambah status aktif akun pada tabel users (untuk mengaktifkan/nonaktifkan akun kasir per toko)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('store_id');
            }
        });

        // 2. Tambah sumber pesanan (online / cashier) dan pencatat kasir pada tabel orders
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'order_source')) {
                $table->string('order_source', 30)->default('online')->after('store_id')->index();
            }
            if (!Schema::hasColumn('orders', 'cashier_user_id')) {
                $table->foreignId('cashier_user_id')
                    ->nullable()
                    ->after('order_source')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'cashier_user_id')) {
                $table->dropForeign(['cashier_user_id']);
                $table->dropColumn('cashier_user_id');
            }
            if (Schema::hasColumn('orders', 'order_source')) {
                $table->dropColumn('order_source');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};

