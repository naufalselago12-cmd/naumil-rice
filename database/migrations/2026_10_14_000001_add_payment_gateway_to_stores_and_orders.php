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
            $table->string('payment_type')->default('qris_static')->after('qris_data'); // 'qris_static' or 'payment_gateway'
            $table->text('mayar_api_key')->nullable()->after('payment_type');
            $table->string('mayar_webhook_token')->nullable()->after('mayar_api_key');
            $table->string('mayar_environment')->default('sandbox')->after('mayar_webhook_token'); // 'sandbox' or 'production'
        });

        Schema::table('orders', function (Blueprint $table) {
            // Change payment_method to string so it can accommodate payment gateway values
            $table->string('payment_method')->change();
            $table->text('payment_url')->nullable()->after('payment_status');
            $table->string('payment_id')->nullable()->after('payment_url');
            $table->json('payment_payload')->nullable()->after('payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn([
                'payment_type',
                'mayar_api_key',
                'mayar_webhook_token',
                'mayar_environment',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_url',
                'payment_id',
                'payment_payload',
            ]);
        });
    }
};
