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
        Schema::table('stores', function (Blueprint $table) {
            if (!Schema::hasColumn('stores', 'payment_gateway_provider')) {
                $table->string('payment_gateway_provider')->default('midtrans')->after('payment_type'); // 'midtrans' or 'mayar'
            }
            if (!Schema::hasColumn('stores', 'midtrans_environment')) {
                $table->string('midtrans_environment')->default('sandbox')->after('payment_gateway_provider'); // 'sandbox' or 'production'
            }
            if (!Schema::hasColumn('stores', 'midtrans_server_key')) {
                $table->text('midtrans_server_key')->nullable()->after('midtrans_environment');
            }
            if (!Schema::hasColumn('stores', 'midtrans_client_key')) {
                $table->text('midtrans_client_key')->nullable()->after('midtrans_server_key');
            }
            if (!Schema::hasColumn('stores', 'midtrans_merchant_id')) {
                $table->string('midtrans_merchant_id')->nullable()->after('midtrans_client_key');
            }
            if (!Schema::hasColumn('stores', 'midtrans_server_key_production')) {
                $table->text('midtrans_server_key_production')->nullable()->after('midtrans_merchant_id');
            }
            if (!Schema::hasColumn('stores', 'midtrans_client_key_production')) {
                $table->text('midtrans_client_key_production')->nullable()->after('midtrans_server_key_production');
            }
            if (!Schema::hasColumn('stores', 'midtrans_merchant_id_production')) {
                $table->string('midtrans_merchant_id_production')->nullable()->after('midtrans_client_key_production');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'snap_token')) {
                $table->string('snap_token')->nullable()->after('payment_id');
            }
            if (!Schema::hasColumn('orders', 'payment_gateway_provider')) {
                $table->string('payment_gateway_provider')->nullable()->after('snap_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $columns = [
                'payment_gateway_provider',
                'midtrans_environment',
                'midtrans_server_key',
                'midtrans_client_key',
                'midtrans_merchant_id',
                'midtrans_server_key_production',
                'midtrans_client_key_production',
                'midtrans_merchant_id_production',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('stores', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            $columns = ['snap_token', 'payment_gateway_provider'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

