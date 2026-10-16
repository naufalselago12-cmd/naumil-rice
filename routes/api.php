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
    Route::post('/orders/{orderNumber}/upload-proof', [OrderApiController::class, 'uploadPaymentProof']);
    Route::get('/orders/kds', [OrderApiController::class, 'kdsQueue']);
    Route::patch('/orders/{id}/status', [OrderApiController::class, 'updateStatus']);

    // 5. Payment Gateway Webhook (Mayar.id)
    Route::match(['get', 'post'], '/payment/mayar/webhook/{store_slug?}', [\App\Http\Controllers\Api\MayarWebhookController::class, 'handle']);

    // 6. Customer Authentication & Loyalty Program
    Route::post('/customer/register-loyalty', [\App\Http\Controllers\CustomerAuthController::class, 'registerLoyalty']);
    Route::post('/customer/login', [\App\Http\Controllers\CustomerAuthController::class, 'loginLoyalty']);
    Route::post('/customer/login-loyalty', [\App\Http\Controllers\CustomerAuthController::class, 'loginLoyalty']);
    Route::post('/customer/resend-activation', [\App\Http\Controllers\CustomerAuthController::class, 'resendActivation']);
    Route::post('/customer/update-stores', [\App\Http\Controllers\CustomerAuthController::class, 'updateStoreAffiliations']);
    Route::post('/customer/update-profile', [\App\Http\Controllers\CustomerAuthController::class, 'updateProfile']);
    Route::get('/customer/profile-data', [\App\Http\Controllers\CustomerAuthController::class, 'getProfileData']);
    Route::post('/customer/lookup-by-email', [\App\Http\Controllers\CustomerAuthController::class, 'lookupByEmail']);
    Route::get('/customer/current-session', [\App\Http\Controllers\CustomerAuthController::class, 'currentSession']);
    Route::post('/customer/logout', [\App\Http\Controllers\CustomerAuthController::class, 'logout']);

    // 7. Store Loyalty Promos (Public / Storefront)
    Route::get('/stores/{store_id}/loyalty-promos', function ($store_id) {
        $promos = \App\Models\LoyaltyPromo::where('store_id', $store_id)
            ->where('is_active', true)
            ->with('menus')
            ->get()
            ->filter(fn($p) => $p->isCurrentlyActive())
            ->values();
        return response()->json(['success' => true, 'data' => $promos]);
    });
});

