<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StoreApiController;
use App\Http\Controllers\Api\CampaignApiController;
use App\Http\Controllers\Api\ClientApiController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\Admin\AdminAuthController;
use App\Http\Controllers\Api\Admin\AdminCampaignController;
use App\Http\Controllers\Api\Admin\AdminCashierAccountController;
use App\Http\Controllers\Api\Admin\AdminCategoryController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminLoyaltyController;
use App\Http\Controllers\Api\Admin\AdminMenuController;
use App\Http\Controllers\Api\Admin\AdminNotificationController;
use App\Http\Controllers\Api\Admin\AdminOrderController;
use App\Http\Controllers\Api\Admin\AdminReportController;
use App\Http\Controllers\Api\Admin\AdminSettingsController;
use App\Http\Controllers\Api\Cashier\CashierPosApiController;

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
    Route::post('/orders/{orderNumber}/cancel-otp', [OrderApiController::class, 'sendCancelOtp']);
    Route::post('/orders/{orderNumber}/cancel', [OrderApiController::class, 'cancelOrder']);
    Route::get('/orders/kds', [OrderApiController::class, 'kdsQueue']);
    Route::patch('/orders/{id}/status', [OrderApiController::class, 'updateStatus']);

    // 4b. Master Cancellation Reasons (Customer & Admin)
    Route::get('/cancellation-reasons', [\App\Http\Controllers\Api\CancellationReasonApiController::class, 'index']);

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

    // 6b. Customer Address Book (Pelanggan Setia Multi-Alamat)
    Route::get('/customer/addresses', [\App\Http\Controllers\Api\ClientAddressController::class, 'index']);
    Route::post('/customer/addresses', [\App\Http\Controllers\Api\ClientAddressController::class, 'store']);
    Route::put('/customer/addresses/{address}', [\App\Http\Controllers\Api\ClientAddressController::class, 'update']);
    Route::post('/customer/addresses/{address}/default', [\App\Http\Controllers\Api\ClientAddressController::class, 'setDefault']);
    Route::delete('/customer/addresses/{address}', [\App\Http\Controllers\Api\ClientAddressController::class, 'destroy']);

    // 6c. KiriminAja Shipping, Coverage Area, OpenStreetMap Geotag & Webhook
    Route::prefix('shipping')->group(function () {
        Route::get('/provinces', [\App\Http\Controllers\Api\ShippingApiController::class, 'provinces']);
        Route::get('/cities', [\App\Http\Controllers\Api\ShippingApiController::class, 'cities']);
        Route::get('/districts', [\App\Http\Controllers\Api\ShippingApiController::class, 'districts']);
        Route::get('/subdistricts', [\App\Http\Controllers\Api\ShippingApiController::class, 'subdistricts']);
        Route::get('/search-addresses', [\App\Http\Controllers\Api\ShippingApiController::class, 'searchAddresses']);
        Route::post('/quote', [\App\Http\Controllers\Api\ShippingApiController::class, 'quote']);
    });

    Route::prefix('geo')->group(function () {
        Route::get('/reverse', [\App\Http\Controllers\Api\ShippingApiController::class, 'geoReverse']);
        Route::get('/search', [\App\Http\Controllers\Api\ShippingApiController::class, 'geoSearch']);
    });

    Route::post('/kiriminaja/webhook', [\App\Http\Controllers\Api\ShippingApiController::class, 'webhook']);

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

        // Profil, logout & notifikasi realtime: dapat dipanggil oleh Admin Toko maupun Kasir cabang aktif
        Route::middleware(['auth:sanctum', 'store.api.member'])->group(function () {
            Route::get('/me', [AdminAuthController::class, 'me']);
            Route::post('/logout', [AdminAuthController::class, 'logout']);

            // Notifikasi realtime (riwayat + tandai dibaca + polling fallback jingle & TTS)
            Route::get('/notifications', [AdminNotificationController::class, 'feed']);
            Route::post('/notifications/read-all', [AdminNotificationController::class, 'markAllRead']);
            Route::post('/notifications/{notification}/read', [AdminNotificationController::class, 'read'])->whereNumber('notification');
        });

        Route::middleware(['auth:sanctum', 'store.api'])->group(function () {
            Route::get('/dashboard', [AdminDashboardController::class, 'index']);

            // Pesanan & KDS
            Route::get('/orders', [AdminOrderController::class, 'index']);
            Route::get('/orders/kds', [AdminOrderController::class, 'kds']);
            Route::get('/orders/{order}', [AdminOrderController::class, 'show']);
            Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus']);
            Route::post('/orders/{order}/cancel', [AdminOrderController::class, 'cancel']);
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

            // Manajemen Akun Kasir per Toko (khusus Admin Toko)
            Route::get('/cashiers', [AdminCashierAccountController::class, 'index']);
            Route::post('/cashiers', [AdminCashierAccountController::class, 'store']);
            Route::put('/cashiers/{cashier}', [AdminCashierAccountController::class, 'update']);
            Route::patch('/cashiers/{cashier}/toggle', [AdminCashierAccountController::class, 'toggle']);
            Route::delete('/cashiers/{cashier}', [AdminCashierAccountController::class, 'destroy']);

            // Laporan & pengaturan
            Route::get('/reports', [AdminReportController::class, 'index']);
            Route::get('/settings', [AdminSettingsController::class, 'show']);
            Route::post('/settings', [AdminSettingsController::class, 'update']);
            Route::post('/settings/test-midtrans', [AdminSettingsController::class, 'testMidtrans']);
            Route::post('/settings/test-mayar', [AdminSettingsController::class, 'testMayar']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 9. MODE KASIR / POS & KDS RINGKAS (Kasir & Admin Toko via Bearer token Sanctum)
    |--------------------------------------------------------------------------
    */
    Route::prefix('cashier')->middleware(['auth:sanctum', 'store.api.member'])->group(function () {
        Route::get('/menus', [CashierPosApiController::class, 'menus']);
        Route::post('/orders', [CashierPosApiController::class, 'createOrder']);
        Route::get('/orders/recent', [CashierPosApiController::class, 'recentOrders']);
        Route::post('/orders/{order}/mark-paid', [CashierPosApiController::class, 'markPaid']);
        Route::post('/orders/{order}/sync-midtrans', [CashierPosApiController::class, 'syncMidtrans']);
        Route::get('/kds', [CashierPosApiController::class, 'kdsQueue']);
        Route::patch('/kds/{order}/status', [CashierPosApiController::class, 'updateKdsStatus']);
    });
});

