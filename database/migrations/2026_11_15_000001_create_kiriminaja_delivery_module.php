<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul "Kirim Langsung ke Rumah" (KiriminAja).
 *  - stores         : saklar aktif/nonaktif + alamat & geotag toko (origin pengiriman)
 *  - orders         : jenis fulfillment (ambil di outlet / kirim) + biaya & data shipment
 *  - client_addresses: buku alamat pelanggan setia (simpan sekali, bisa tambah alamat lain)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (!Schema::hasColumn('stores', 'delivery_enabled')) {
                $table->boolean('delivery_enabled')->default(false);
                $table->unsignedInteger('ka_province_id')->nullable();
                $table->string('ka_province_name')->nullable();
                $table->unsignedInteger('ka_city_id')->nullable();
                $table->string('ka_city_name')->nullable();
                $table->unsignedInteger('ka_district_id')->nullable();
                $table->string('ka_district_name')->nullable();
                $table->unsignedInteger('ka_subdistrict_id')->nullable();
                $table->string('ka_subdistrict_name')->nullable();
                $table->string('ka_postal_code', 10)->nullable();
                $table->text('ka_address')->nullable();
                $table->string('ka_address_note', 150)->nullable();   // catatan gang / patokan
                $table->decimal('ka_latitude', 10, 7)->nullable();
                $table->decimal('ka_longitude', 10, 7)->nullable();
                $table->text('ka_geo_address')->nullable();            // alamat hasil deteksi peta
                $table->json('delivery_couriers')->nullable();         // allowlist kurir (null = default)
                $table->unsignedInteger('delivery_weight_per_item')->default(500);
                $table->unsignedSmallInteger('delivery_package_length')->default(20);
                $table->unsignedSmallInteger('delivery_package_width')->default(20);
                $table->unsignedSmallInteger('delivery_package_height')->default(10);
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'fulfillment_type')) {
                $table->string('fulfillment_type', 20)->default('pickup');   // pickup | delivery
                $table->decimal('shipping_cost', 12, 2)->default(0);
                $table->string('shipping_type', 20)->nullable();             // express | instant
                $table->string('shipping_courier', 50)->nullable();
                $table->string('shipping_service_name')->nullable();
                $table->string('shipping_service_type', 60)->nullable();
                $table->string('shipping_etd', 60)->nullable();
                $table->json('shipping_address')->nullable();                // snapshot alamat tujuan
                $table->string('shipping_status', 30)->nullable();
                $table->string('ka_order_id', 60)->nullable()->index();
                $table->string('ka_awb', 80)->nullable()->index();
                $table->string('ka_pickup_number', 80)->nullable();
                $table->json('ka_payload')->nullable();
                $table->dateTime('shipping_booked_at')->nullable();
            }
        });

        if (!Schema::hasTable('client_addresses')) {
            Schema::create('client_addresses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
                $table->string('label', 60)->default('Rumah');
                $table->string('recipient_name');
                $table->string('phone', 30);
                $table->unsignedInteger('province_id')->nullable();
                $table->string('province_name')->nullable();
                $table->unsignedInteger('city_id')->nullable();
                $table->string('city_name')->nullable();
                $table->unsignedInteger('district_id')->nullable();
                $table->string('district_name')->nullable();
                $table->unsignedInteger('subdistrict_id')->nullable();
                $table->string('subdistrict_name')->nullable();
                $table->string('postal_code', 10)->nullable();
                $table->text('address');
                $table->string('address_note', 150)->nullable();   // catatan gang / patokan
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->text('geo_address')->nullable();
                $table->boolean('is_default')->default(false);
                $table->timestamps();

                $table->index(['client_id', 'is_default']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_addresses');

        Schema::table('orders', function (Blueprint $table) {
            $cols = [
                'fulfillment_type', 'shipping_cost', 'shipping_type', 'shipping_courier',
                'shipping_service_name', 'shipping_service_type', 'shipping_etd', 'shipping_address',
                'shipping_status', 'ka_order_id', 'ka_awb', 'ka_pickup_number', 'ka_payload', 'shipping_booked_at',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    if (in_array($col, ['ka_order_id', 'ka_awb'], true)) {
                        $table->dropIndex(['' . $col]);
                    }
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('stores', function (Blueprint $table) {
            $cols = [
                'delivery_enabled', 'ka_province_id', 'ka_province_name', 'ka_city_id', 'ka_city_name',
                'ka_district_id', 'ka_district_name', 'ka_subdistrict_id', 'ka_subdistrict_name',
                'ka_postal_code', 'ka_address', 'ka_address_note', 'ka_latitude', 'ka_longitude',
                'ka_geo_address', 'delivery_couriers', 'delivery_weight_per_item',
                'delivery_package_length', 'delivery_package_width', 'delivery_package_height',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('stores', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

