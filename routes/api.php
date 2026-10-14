<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StoreApiController;
use App\Http\Controllers\Api\CampaignApiController;
use App\Http\Controllers\Api\ClientApiController;
use App\Http\Controllers\Api\OrderApiController;

Route::prefix('v1')->group(function () {
    // 1. Stores
    Route::get('/stores', [StoreApiController::class, 'index']);
    Route::get('/stores/{id}', [StoreApiController::class, 'show']);

    // 2. Campaigns (Open Pre-Order)
    Route::get('/campaigns', [CampaignApiController::class, 'index']);
    Route::get('/campaigns/{token}', [CampaignApiController::class, 'showByToken']);

    // 3. Clients (Suggestion & Registration)
    Route::get('/clients', [ClientApiController::class, 'index']);
    Route::post('/clients/check-or-register', [ClientApiController::class, 'checkOrRegister']);

    // 4. Orders
    Route::post('/orders', [OrderApiController::class, 'store']);
    Route::get('/orders/trace/{orderNumber}', [OrderApiController::class, 'trace']);
    Route::get('/orders/kds', [OrderApiController::class, 'kdsQueue']);
    Route::patch('/orders/{id}/status', [OrderApiController::class, 'updateStatus']);

    // 5. Payment Gateway Webhook (Mayar.id)
    Route::post('/payment/mayar/webhook/{store_slug?}', [\App\Http\Controllers\Api\MayarWebhookController::class, 'handle']);
});
