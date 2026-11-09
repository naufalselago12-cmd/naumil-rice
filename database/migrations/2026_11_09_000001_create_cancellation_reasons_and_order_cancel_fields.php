<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create cancellation_reasons table
        if (!Schema::hasTable('cancellation_reasons')) {
            Schema::create('cancellation_reasons', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->nullable();
                $table->string('reason');
                $table->string('applies_to', 20)->default('all'); // 'customer', 'admin', 'all'
                $table->boolean('requires_remark')->default(false);
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Seed master cancellation reasons
            DB::table('cancellation_reasons')->insert([
                // Customer reasons
                [
                    'code' => 'cust_change_menu',
                    'reason' => 'Ingin mengubah menu / pesanan',
                    'applies_to' => 'customer',
                    'requires_remark' => false,
                    'sort_order' => 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'cust_wrong_address',
                    'reason' => 'Salah memasukkan alamat pengiriman',
                    'applies_to' => 'customer',
                    'requires_remark' => false,
                    'sort_order' => 2,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'cust_delivery_time',
                    'reason' => 'Waktu pengantaran terlalu lama',
                    'applies_to' => 'customer',
                    'requires_remark' => false,
                    'sort_order' => 3,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'cust_wrong_payment',
                    'reason' => 'Salah memilih metode pembayaran',
                    'applies_to' => 'customer',
                    'requires_remark' => false,
                    'sort_order' => 4,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'cust_change_mind',
                    'reason' => 'Berubah pikiran / tidak jadi membeli',
                    'applies_to' => 'customer',
                    'requires_remark' => false,
                    'sort_order' => 5,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                // Admin reasons
                [
                    'code' => 'admin_out_of_stock',
                    'reason' => 'Bahan baku habis / menu tidak tersedia',
                    'applies_to' => 'admin',
                    'requires_remark' => false,
                    'sort_order' => 10,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'admin_kitchen_overload',
                    'reason' => 'Dapur sedang overload / antrean penuh',
                    'applies_to' => 'admin',
                    'requires_remark' => false,
                    'sort_order' => 11,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'admin_customer_requested',
                    'reason' => 'Pelanggan meminta pembatalan langsung via chat / telepon',
                    'applies_to' => 'admin',
                    'requires_remark' => false,
                    'sort_order' => 12,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'admin_out_of_reach',
                    'reason' => 'Alamat pengiriman di luar jangkauan kurir',
                    'applies_to' => 'admin',
                    'requires_remark' => false,
                    'sort_order' => 13,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'admin_technical_issue',
                    'reason' => 'Kendala operasional / teknis dapur',
                    'applies_to' => 'admin',
                    'requires_remark' => false,
                    'sort_order' => 14,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                // Shared "Lainnya" reason
                [
                    'code' => 'reason_other',
                    'reason' => 'Lainnya',
                    'applies_to' => 'all',
                    'requires_remark' => true,
                    'sort_order' => 99,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        // 2. Add cancellation & refund fields to orders table
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'cancelled_by')) {
                $table->string('cancelled_by', 30)->nullable()->after('rejection_reason');
            }
            if (!Schema::hasColumn('orders', 'cancellation_reason_id')) {
                $table->foreignId('cancellation_reason_id')->nullable()->after('cancelled_by')
                    ->constrained('cancellation_reasons')->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'cancellation_reason')) {
                $table->string('cancellation_reason', 255)->nullable()->after('cancellation_reason_id');
            }
            if (!Schema::hasColumn('orders', 'cancellation_remark')) {
                $table->text('cancellation_remark')->nullable()->after('cancellation_reason');
            }
            if (!Schema::hasColumn('orders', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancellation_remark');
            }
            if (!Schema::hasColumn('orders', 'refund_email')) {
                $table->string('refund_email', 255)->nullable()->after('cancelled_at');
            }
            if (!Schema::hasColumn('orders', 'refund_bank_name')) {
                $table->string('refund_bank_name', 100)->nullable()->after('refund_email');
            }
            if (!Schema::hasColumn('orders', 'refund_bank_account_number')) {
                $table->string('refund_bank_account_number', 100)->nullable()->after('refund_bank_name');
            }
            if (!Schema::hasColumn('orders', 'refund_bank_account_name')) {
                $table->string('refund_bank_account_name', 100)->nullable()->after('refund_bank_account_number');
            }
            if (!Schema::hasColumn('orders', 'refund_status')) {
                $table->string('refund_status', 50)->nullable()->after('refund_bank_account_name');
            }
            if (!Schema::hasColumn('orders', 'refund_otp')) {
                $table->string('refund_otp', 10)->nullable()->after('refund_status');
            }
            if (!Schema::hasColumn('orders', 'refund_otp_expires_at')) {
                $table->timestamp('refund_otp_expires_at')->nullable()->after('refund_otp');
            }
            if (!Schema::hasColumn('orders', 'refund_otp_verified_at')) {
                $table->timestamp('refund_otp_verified_at')->nullable()->after('refund_otp_expires_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $columns = [
                'cancelled_by',
                'cancellation_reason_id',
                'cancellation_reason',
                'cancellation_remark',
                'cancelled_at',
                'refund_email',
                'refund_bank_name',
                'refund_bank_account_number',
                'refund_bank_account_name',
                'refund_status',
                'refund_otp',
                'refund_otp_expires_at',
                'refund_otp_verified_at',
            ];

            if (Schema::hasColumn('orders', 'cancellation_reason_id')) {
                $table->dropForeign(['cancellation_reason_id']);
            }

            foreach ($columns as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('cancellation_reasons');
    }
};

