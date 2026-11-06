<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StoreApiController;
use App\Http\Controllers\Api\CampaignApiController;
use App\Http\Controllers\Api\ClientApiController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\Admin\AdminAuthController;
use App\Http\Controllers\Api\Admin\AdminCampaignController;
use App\Http\Controllers\Api\Admin\AdminCategoryController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminLoyaltyController;
use App\Http\Controllers\Api\Admin\AdminMenuController;
use App\Http\Controllers\Api\Admin\AdminNotificationController;
use App\Http\Controllers\Api\Admin\AdminOrderController;
use App\Http\Controllers\Api\Admin\AdminReportController;
use App\Http\Controllers\Api\Admin\AdminSettingsController;

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

    // 5. Payment Gateway Webhook (Midtrans & Mayar.id)
    Route::match(['get', 'post'], '/payment/midtrans/notification/{store_slug?}', [\App\Http\Controllers\Api\MidtransWebhookController::class, 'handle']);
    Route::match(['get', 'post'], '/payment/midtrans/webhook/{store_slug?}', [\App\Http\Controllers\Api\MidtransWebhookController::class, 'handle']);
    Route::match(['get', 'post'], '/midtrans/notification/{store_slug?}', [\App\Http\Controllers\Api\MidtransWebhookController::class, 'handle']);
    Route::match(['get', 'post'], '/payment/mayar/webhook/{store_slug?}', [\App\Http\Controllers\Api\MayarWebhookController::class, 'handle']);
    Route::match(['get', 'post'], '/payment/mayar/callback/{store_slug?}', [\App\Http\Controllers\Api\MayarWebhookController::class, 'handle']);
    Route::match(['get', 'post'], '/mayar/webhook/{store_slug?}', [\App\Http\Controllers\Api\MayarWebhookController::class, 'handle']);

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

    /*
    |--------------------------------------------------------------------------
    | 8. ADMIN TOKO (Aplikasi Mobile naumil_rice_apps) - Bearer token Sanctum
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->group(function () {
        Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:10,1');

        Route::middleware(['auth:sanctum', 'store.api'])->group(function () {
            Route::get('/me', [AdminAuthController::class, 'me']);
            Route::post('/logout', [AdminAuthController::class, 'logout']);

            Route::get('/dashboard', [AdminDashboardController::class, 'index']);

            // Notifikasi realtime (riwayat + tandai dibaca)
            Route::get('/notifications', [AdminNotificationController::class, 'feed']);
            Route::post('/notifications/read-all', [AdminNotificationController::class, 'markAllRead']);
            Route::post('/notifications/{notification}/read', [AdminNotificationController::class, 'read'])->whereNumber('notification');

            // Pesanan & KDS
            Route::get('/orders', [AdminOrderController::class, 'index']);
            Route::get('/orders/kds', [AdminOrderController::class, 'kds']);
            Route::get('/orders/{order}', [AdminOrderController::class, 'show']);
            Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus']);
            Route::post('/orders/{order}/verify-payment', [AdminOrderController::class, 'verifyPayment']);
            Route::post('/orders/{order}/sync-midtrans', [AdminOrderController::class, 'syncMidtrans']);
            Route::post('/orders/{order}/sync-mayar', [AdminOrderController::class, 'syncMayar']);

            // Katalog
            Route::get('/categories', [AdminCategoryController::class, 'index']);
            Route::post('/categories', [AdminCategoryController::class, 'store']);
            Route::put('/categories/{category}', [AdminCategoryController::class, 'update']);
            Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy']);

            Route::get('/menus', [AdminMenuController::class, 'index']);
            Route::post('/menus', [AdminMenuController::class, 'store']);
            Route::post('/menus/{menu}', [AdminMenuController::class, 'update']);
            Route::patch('/menus/{menu}/toggle', [AdminMenuController::class, 'toggle']);
            Route::delete('/menus/{menu}', [AdminMenuController::class, 'destroy']);

            // Campaign Open PO
            Route::get('/campaigns', [AdminCampaignController::class, 'index']);
            Route::post('/campaigns', [AdminCampaignController::class, 'store']);
            Route::get('/campaigns/{campaign}', [AdminCampaignController::class, 'show']);
            Route::put('/campaigns/{campaign}', [AdminCampaignController::class, 'update']);
            Route::delete('/campaigns/{campaign}', [AdminCampaignController::class, 'destroy']);

            // Pelanggan setia & promo
            Route::get('/loyalty/customers', [AdminLoyaltyController::class, 'customers']);
            Route::get('/loyalty/customers/{customer}', [AdminLoyaltyController::class, 'customer']);
            Route::post('/loyalty/customers/{customer}/adjust-points', [AdminLoyaltyController::class, 'adjustPoints']);
            Route::get('/loyalty/promos', [AdminLoyaltyController::class, 'promos']);
            Route::post('/loyalty/promos', [AdminLoyaltyController::class, 'storePromo']);
            Route::put('/loyalty/promos/{promo}', [AdminLoyaltyController::class, 'updatePromo']);
            Route::delete('/loyalty/promos/{promo}', [AdminLoyaltyController::class, 'destroyPromo']);

            // Laporan & pengaturan
            Route::get('/reports', [AdminReportController::class, 'index']);
            Route::get('/settings', [AdminSettingsController::class, 'show']);
            Route::post('/settings', [AdminSettingsController::class, 'update']);
            Route::post('/settings/test-midtrans', [AdminSettingsController::class, 'testMidtrans']);
            Route::post('/settings/test-mayar', [AdminSettingsController::class, 'testMayar']);
        });
    });
});

