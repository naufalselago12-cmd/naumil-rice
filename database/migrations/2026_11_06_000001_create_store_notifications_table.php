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
        Schema::create('store_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);   // cod | direct
            $table->string('event', 30);  // order_placed | payment_received | payment_review
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable(); // snapshot: pelanggan, rincian, total, metode bayar
            $table->string('push_status', 30)->default('pending'); // pending | sent | failed | skipped
            $table->text('push_response')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Satu notifikasi per pesanan (mencegah duplikat dari webhook / sinkronisasi berulang)
            $table->unique(['order_id', 'type']);
            $table->index(['store_id', 'id']);
            $table->index(['store_id', 'read_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_notifications');
    }
};

